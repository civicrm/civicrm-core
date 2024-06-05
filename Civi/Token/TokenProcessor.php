<?php
namespace Civi\Token;

use Brick\Money\Money;
use Civi\Token\Event\TokenRegisterEvent;
use Civi\Token\Event\TokenRenderEvent;
use Civi\Token\Event\TokenValueEvent;
use Traversable;

/**
 * The TokenProcessor is a template/token-engine. It is heavily influenced by
 * traditional expectations of CiviMail, but it's adapted to an object-oriented,
 * extensible design.
 *
 * BACKGROUND
 *
 * The CiviMail heritage gives the following expectations:
 *
 * - Messages are often composed of multiple parts (e.g. HTML-part, text-part, and subject-part).
 * - Messages are often composed in batches for multiple recipients.
 * - Tokens are denoted as `{foo.bar}`.
 * - Data should be loaded in an optimized fashion - fetch only the needed
 *   columns, and fetch them with one query (per-table).
 *
 * The question of "optimized" data-loading is a key differentiator/complication.
 * This requires some kind of communication/integration between the template-parser and data-loader.
 *
 * USAGE
 *
 * There are generally two perspectives on using TokenProcessor:
 *
 * 1. Composing messages: You need to specify the template contents (eg `addMessage(...)`)
 *    and the recipients' key data (eg `addRow(['contact_id' => 123])`).
 * 2. Defining tokens/entities/data-loaders: You need to listen for TokenProcessor
 *    events; if any of your tokens/entities are used, then load the batch of data.
 *
 * Each use-case is presented with examples in the Developer Guide:
 *
 * @link https://docs.civicrm.org/dev/en/latest/framework/token/
 */
class TokenProcessor {

  /**
   * @var array
   *   Description of the context in which the tokens are being processed.
   *   Ex: Array('class'=>'CRM_Core_BAO_ActionSchedule', 'schedule' => $dao, 'mapping' => $dao).
   *   Ex: Array('class'=>'CRM_Mailing_BAO_MailingJob', 'mailing' => $dao).
   *
   * For lack of a better place, here's a list of known/intended context values:
   *
   *   - controller: string, the class which is managing the mail-merge.
   *   - smarty: bool, whether to enable smarty support.
   *   - smartyTokenAlias: array, Define Smarty variables that are populated
   *      based on token-content. Ex: ['theInvoiceId' => 'contribution.invoice_id']
   *   - contactId: int, the main person/org discussed in the message.
   *   - contact: array, the main person/org discussed in the message.
   *     (Optional for performance tweaking; if omitted, will load
   *     automatically from contactId.)
   *   - actionSchedule: DAO, the rule which triggered the mailing
   *     [for CRM_Core_BAO_ActionScheduler].
   *   - locale: string, the name of a locale (eg 'fr_CA') to use for {ts} strings in the view.
   *   - schema: array, a list of fields that will be provided for each row.
   *     This is automatically populated with any general context
   *     keys, but you may need to add extra keys for token-row data.
   *     ex: ['contactId', 'activityId'].
   */
  public $context;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * @var array
   *   Each message is an array with keys:
   *    - string: Unprocessed message (eg "Hello, {display_name}.").
   *    - format: Media type (eg "text/plain").
   *    - tokens: List of tokens which are actually used in this message.
   */
  protected $messages;

  /**
   * DO NOT access field this directly. Use TokenRow. This is
   * marked as public only to benefit TokenRow.
   *
   * @var array
   *   Array(int $pos => array $keyValues);
   */
  public $rowContexts;

  /**
   * DO NOT access field this directly. Use TokenRow. This is
   * marked as public only to benefit TokenRow.
   *
   * @var array
   *   Ex: $rowValues[$rowPos][$format][$entity][$field] = 'something';
   *    Ex: $rowValues[3]['text/plain']['contact']['display_name'] = 'something';
   */
  public $rowValues;

  /**
   * A list of available tokens
   * @var array
   *   Array(string $dottedName => array('entity'=>string, 'field'=>string, 'label'=>string)).
   */
  protected $tokens = NULL;

  /**
   * A list of available tokens formatted for display
   * @var array
   *   Array('{' . $dottedName . '}' => 'labelString')
   */
  protected $listTokens = NULL;

  protected $next = 0;

  /**
   * @param \Civi\Core\CiviEventDispatcher $dispatcher
   * @param array $context
   */
  public function __construct($dispatcher, $context) {
    $context['schema'] = isset($context['schema'])
      ? array_unique(array_merge($context['schema'], array_keys($context)))
      : array_keys($context);
    $this->dispatcher = $dispatcher;
    $this->context = $context;
  }

  /**
   * Add new elements to the field schema.
   *
   * @param string|string[] $fieldNames
   * @return TokenProcessor
   */
  public function addSchema($fieldNames) {
    $this->context['schema'] = array_unique(array_merge($this->context['schema'], (array) $fieldNames));
    return $this;
  }

  /**
   * Register a string for which we'll need to merge in tokens.
   *
   * @param string $name
   *   Ex: 'subject', 'body_html'.
   * @param string $value
   *   Ex: '<p>Hello {contact.name}</p>'.
   * @param string $format
   *   Ex: 'text/html'.
   * @return TokenProcessor
   */
  public function addMessage($name, $value, $format) {
    $tokens = [];
    $this->visitTokens($value ?: '', function (?string $fullToken, ?string $entity, ?string $field, ?array $modifier) use (&$tokens) {
      $tokens[$entity][] = $field;
    }, $format);
    $this->messages[$name] = [
      'string' => $value,
      'format' => $format,
      'tokens' => $tokens,
    ];
    return $this;
  }

  /**
   * Add a row of data.
   *
   * @param array|null $context
   *   Optionally, initialize the context for this row.
   *   Ex: ['contact_id' => 123].
   * @return TokenRow
   */
  public function addRow($context = NULL) {
    $key = $this->next++;
    $this->rowContexts[$key] = [];
    $this->rowValues[$key] = [
      'text/plain' => [],
      'text/html' => [],
    ];

    $row = new TokenRow($this, $key);
    if ($context !== NULL) {
      $row->context($context);
    }
    return $row;
  }

  /**
   * Add several rows.
   *
   * @param array $contexts
   *   List of rows to add.
   *   Ex: [['contact_id'=>123], ['contact_id'=>456]]
   * @return TokenRow[]
   *   List of row objects
   */
  public function addRows($contexts) {
    $rows = [];
    foreach ($contexts as $context) {
      $row = $this->addRow($context);
      $rows[$row->tokenRow] = $row;
    }
    return $rows;
  }

  /**
   * @param array $params
   *   Array with keys:
   *    - entity: string, e.g. "profile".
   *    - field: string, e.g. "viewUrl".
   *    - label: string, e.g. "Default Profile URL (View Mode)".
   * @return TokenProcessor
   */
  public function addToken($params) {
    $key = $params['entity'] . '.' . $params['field'];
    $this->tokens[$key] = $params;
    return $this;
  }

  /**
   * @param string $name
   * @return array
   *   Keys:
   *    - string: Unprocessed message (eg "Hello, {display_name}.").
   *    - format: Media type (eg "text/plain").
   */
  public function getMessage($name) {
    return $this->messages[$name];
  }

  /**
   * Get a list of all tokens used in registered messages.
   *
   * @return array
   *   The list of activated tokens, indexed by object/entity.
   *   Array(string $entityName => string[] $fieldNames)
   *
   *   Ex: If a message says 'Hello {contact.first_name} {contact.last_name}!',
   *   then $result['contact'] would be ['first_name', 'last_name'].
   */
  public function getMessageTokens() {
    $tokens = [];
    foreach ($this->messages as $message) {
      $tokens = \CRM_Utils_Array::crmArrayMerge($tokens, $message['tokens']);
    }
    foreach (array_keys($tokens) as $e) {
      $tokens[$e] = array_unique($tokens[$e]);
      sort($tokens[$e]);
    }
    return $tokens;
  }

  /**
   * Get a specific row (i.e. target or recipient).
   *
   * Ex: echo $p->getRow(2)->context['contact_id'];
   * Ex: $p->getRow(3)->token('profile', 'viewUrl', 'http://example.com/profile?cid=3');
   *
   * @param int $key
   *   The row ID
   * @return \Civi\Token\TokenRow
   *   The row is presented with a fluent, OOP facade.
   * @see TokenRow
   */
  public function getRow($key) {
    return new TokenRow($this, $key);
  }

  /**
   * Get the list of rows (i.e. targets/recipients to generate).
   *
   * @see TokenRow
   * @return \Traversable<TokenRow>
   *   Each row is presented with a fluent, OOP facade.
   */
  public function getRows() {
    return new TokenRowIterator($this, new \ArrayIterator($this->rowContexts ?: []));
  }

  /**
   * Get a list of all unique values for a given context field,
   * whether defined at the processor or row level.
   *
   * @param string $field
   *   Ex: 'contactId'.
   * @param string|null $subfield
   * @return array
   *   Ex: [12, 34, 56].
   */
  public function getContextValues($field, $subfield = NULL) {
    $values = [];
    if (isset($this->context[$field])) {
      if ($subfield) {
        if (isset($this->context[$field]->$subfield)) {
          $values[] = $this->context[$field]->$subfield;
        }
      }
      else {
        $values[] = $this->context[$field];
      }
    }
    foreach ($this->getRows() as $row) {
      if (isset($row->context[$field])) {
        if ($subfield) {
          if (isset($row->context[$field]->$subfield)) {
            $values[] = $row->context[$field]->$subfield;
          }
        }
        else {
          $values[] = $row->context[$field];
        }
      }
    }
    $values = array_unique($values);
    return $values;
  }

  /**
   * Get the list of available tokens.
   *
   * @return array
   *   Ex: $tokens['event'] = ['location', 'start_date', 'end_date'].
   */
  public function getTokens() {
    if ($this->tokens === NULL) {
      $this->tokens = [];
      $event = new TokenRegisterEvent($this, ['entity' => 'undefined']);
      $this->dispatcher->dispatch('civi.token.list', $event);
    }
    return $this->tokens;
  }

  /**
   * Get the list of available tokens, formatted for display
   *
   * @return array
   *   Ex: $tokens['{token.name}'] = "Token label"
   */
  public function listTokens() {
    if ($this->listTokens === NULL) {
      $this->listTokens = [];
      foreach ($this->getTokens() as $token => $values) {
        $this->listTokens['{' . $token . '}'] = $values['label'];
      }
    }
    return $this->listTokens;
  }

  /**
   * Compute and store token values.
   */
  public function evaluate() {
    $event = new TokenValueEvent($this);
    $this->dispatcher->dispatch('civi.token.eval', $event);
    return $this;
  }

  /**
   * Render a message.
   *
   * @param string $name
   *   The name previously registered with addMessage().
   * @param TokenRow|int $row
   *   The object or ID for the row previously registered with addRow().
   * @return string
   *   Fully rendered message, with tokens merged.
   */
  public function render($name, $row) {
    if (!is_object($row)) {
      $row = $this->getRow($row);
    }

    $swapLocale = empty($row->context['locale']) ? NULL : \CRM_Utils_AutoClean::swapLocale($row->context['locale']);

    $message = $this->getMessage($name);
    $row->fill($message['format']);
    $useSmarty = !empty($row->context['smarty']);

    $tokens = $this->rowValues[$row->tokenRow][$message['format']];
    $getToken = function(?string $fullToken, ?string $entity, ?string $field, ?array $modifier) use ($tokens, $useSmarty, $row, $message) {
      if (isset($tokens[$entity][$field])) {
        $v = $tokens[$entity][$field];
        $v = $this->filterTokenValue($v, $modifier, $row, $message['format']);
        if ($useSmarty) {
          $v = \CRM_Utils_Token::tokenEscapeSmarty($v);
        }
        return $v;
      }
      return $fullToken;
    };

    $event = new TokenRenderEvent($this);
    $event->message = $message;
    $event->context = $row->context;
    $event->row = $row;
    $event->string = $this->visitTokens($message['string'] ?? '', $getToken, $message['format']);
    $this->dispatcher->dispatch('civi.token.render', $event);
    return $event->string;
  }

  /**
   * Examine a token string and filter each token expression.
   *
   * @internal
   *   This function is only intended for use within civicrm-core. The name/location/callback-signature may change.
   * @param string $expression
   *   Ex: 'Hello {foo.bar} and {whiz.bang|filter:"arg"}!'
   * @param callable $callback
   *   A function which visits (and substitutes) each token.
   *   function(?string $fullToken, ?string $entity, ?string $field, ?array $modifier)
   * @param string|null $format
   *
   * @return string
   */
  public function visitTokens(string $expression, callable $callback, ?string $format = 'text/html'): string {
    // Regex examples: '{foo.bar}', '{foo.bar|whiz}', '{foo.bar|whiz:"bang"}', '{foo.bar|whiz:"bang":"bang"}'
    // Regex counter-examples: '{foobar}', '{foo bar}', '{$foo.bar}', '{$foo.bar|whiz}', '{foo.bar|whiz{bang}}'
    // Key observations: Civi tokens MUST have a `.` and MUST NOT have a `$`. Civi filters MUST NOT have `{}`s or `$`s.

    $quoteStrings = $format === 'text/html' ? [
      // Note we just treat left & right quotes as quotes. Our brains are not big enough to enforce them
      // & maybe user brains are not big enough to use them correctly anyway.
      '"',
      '&lquote\;',
      '&rquote\;',
      '&quot\;',
      '&#8221\;',
      '&#8220\;',
      '&#x22\;',
    ] : ['"'];

    // The regex is a bit complicated, we so break it down into fragments.
    // Consider the example '{foo.bar|whiz:"bang":"bang"}'. Each fragment matches the following:

    $tokenRegex = '([\w]+)\.([\w:\.]+)';
    $quoteRegex = '(?:' . implode('|', $quoteStrings) . ')';
    /* MATCHES: 'foo.bar' */
    $filterArgRegex = ':[\w' . $quoteRegex . ': %\-_()\[\]\+/#@!,\.\?]*'; /* MATCHES: ':"bang":"bang"' */
    // Key rule of filterArgRegex is to prohibit '{}'s because they may parse ambiguously. So you *might* relax it to:
    // $filterArgRegex = ':[^{}\n]*'; /* MATCHES: ':"bang":"bang"' */
    $filterNameRegex = "\w+"; /* MATCHES: 'whiz' */
    $filterRegex = "\|($filterNameRegex(?:$filterArgRegex)?)"; /* MATCHES: '|whiz:"bang":"bang"' */
    $fullRegex = ";\{$tokenRegex(?:$filterRegex)?\};";

    return preg_replace_callback($fullRegex, function($m) use ($callback, $quoteStrings) {
      $filterParts = NULL;
      if (isset($m[3])) {
        $filterParts = [];
        $enqueue = function($m) use (&$filterParts) {
          $filterParts[] = $m[1];
          return '';
        };
        $quoteOptions = implode('|', $quoteStrings);
        $quotedRegex = ':' . '(?:' . $quoteOptions . ')' . '(.+?(?=' . $quoteOptions . ')+)' . '(?:' . $quoteOptions . ')';
        $unmatched = preg_replace_callback_array([
          '/^(\w+)/' => $enqueue,
          ';' . $quotedRegex . ';' => $enqueue,
        ], $m[3]);
        if ($unmatched) {
          throw new \CRM_Core_Exception('Malformed token parameters (' . $m[0] . ')');
        }
      }
      return $callback($m[0] ?? NULL, $m[1] ?? NULL, $m[2] ?? NULL, $filterParts);
    }, $expression);
  }

  /**
   * Given a token value, run it through any filters.
   *
   * @param mixed $value
   *   Raw token value (e.g. from `$row->tokens['foo']['bar']`).
   * @param array|null $filter
   * @param TokenRow $row
   *   The current target/row.
   * @param string $messageFormat
   *   Ex: 'text/plain' or 'text/html'
   * @return string
   * @throws \CRM_Core_Exception
   */
  private function filterTokenValue($value, ?array $filter, TokenRow $row, string $messageFormat) {
    // KISS demonstration. This should change... e.g. provide a filter-registry or reuse Smarty's registry...

    if ($value instanceof \DateTime && $filter === NULL) {
      $filter = ['crmDate'];
      if ($value->format('His') === '000000') {
        // if time is 'midnight' default to just date.
        $filter[1] = 'Full';
      }
    }

    // TODO: Move this to StandardFilters
    if ($value instanceof Money) {
      switch ($filter[0] ?? NULL) {
        case NULL:
        case 'crmMoney':
          return \Civi::format()->money($value->getAmount(), $value->getCurrency(), $filter[1] ?? NULL);

        case 'boolean':
          // We resolve boolean to 0 or 1 or smarty chokes on FALSE.
          return (int) $value->getAmount()->isGreaterThan(0);

        case 'raw':
          return $value->getAmount();

        default:
          throw new \CRM_Core_Exception('Invalid token filter: ' . json_encode($filter, JSON_UNESCAPED_SLASHES));
      }
    }

    if (!isset($filter[0])) {
      return $value;
    }
    elseif (is_callable([StandardFilters::class, $filter[0]])) {
      return call_user_func([StandardFilters::class, $filter[0]], $value, $filter, $messageFormat);
    }
    else {
      throw new \CRM_Core_Exception('Invalid token filter: ' . json_encode($filter, JSON_UNESCAPED_SLASHES));
    }
  }

}

class TokenRowIterator extends \IteratorIterator {

  /**
   * @var \Civi\Token\TokenProcessor
   */
  protected $tokenProcessor;

  /**
   * @param TokenProcessor $tokenProcessor
   * @param \Traversable $iterator
   */
  public function __construct(TokenProcessor $tokenProcessor, Traversable $iterator) {
    // TODO: Change the autogenerated stub
    parent::__construct($iterator);
    $this->tokenProcessor = $tokenProcessor;
  }

  #[\ReturnTypeWillChange]
  public function current() {
    return new TokenRow($this->tokenProcessor, parent::key());
  }

}
