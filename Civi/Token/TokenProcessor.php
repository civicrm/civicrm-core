<?php
namespace Civi\Token;

use Civi\Token\Event\TokenRegisterEvent;
use Civi\Token\Event\TokenRenderEvent;
use Civi\Token\Event\TokenValueEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Traversable;

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
   *   - contactId: int, the main person/org discussed in the message.
   *   - contact: array, the main person/org discussed in the message.
   *     (Optional for performance tweaking; if omitted, will load
   *     automatically from contactId.)
   *   - actionSchedule: DAO, the rule which triggered the mailing
   *     [for CRM_Core_BAO_ActionScheduler].
   */
  public $context;

  /**
   * @var EventDispatcherInterface
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

  protected $next = 0;

  /**
   * @param EventDispatcherInterface $dispatcher
   * @param array $context
   */
  public function __construct($dispatcher, $context) {
    $this->dispatcher = $dispatcher;
    $this->context = $context;
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
   * @return $this
   */
  public function addMessage($name, $value, $format) {
    $this->messages[$name] = array(
      'string' => $value,
      'format' => $format,
      'tokens' => \CRM_Utils_Token::getTokens($value),
    );
    return $this;
  }

  /**
   * Add a row of data.
   *
   * @return TokenRow
   */
  public function addRow() {
    $key = $this->next++;
    $this->rowContexts[$key] = array();
    $this->rowValues[$key] = array(
      'text/plain' => array(),
      'text/html' => array(),
    );

    return new TokenRow($this, $key);
  }

  /**
   * @param array $params
   *   Array with keys:
   *    - entity: string, e.g. "profile".
   *    - field: string, e.g. "viewUrl".
   *    - label: string, e.g. "Default Profile URL (View Mode)".
   * @return $this
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
   */
  public function getMessageTokens() {
    $tokens = array();
    foreach ($this->messages as $message) {
      $tokens = \CRM_Utils_Array::crmArrayMerge($tokens, $message['tokens']);
    }
    foreach (array_keys($tokens) as $e) {
      $tokens[$e] = array_unique($tokens[$e]);
      sort($tokens[$e]);
    }
    return $tokens;
  }

  public function getRow($key) {
    return new TokenRow($this, $key);
  }

  /**
   * @return \Traversable<TokenRow>
   */
  public function getRows() {
    return new TokenRowIterator($this, new \ArrayIterator($this->rowContexts));
  }

  /**
   * Get the list of available tokens.
   *
   * @return array
   *   Ex: $tokens['event'] = array('location', 'start_date', 'end_date').
   */
  public function getTokens() {
    if ($this->tokens === NULL) {
      $this->tokens = array();
      $event = new TokenRegisterEvent($this, array('entity' => 'undefined'));
      $this->dispatcher->dispatch(Events::TOKEN_REGISTER, $event);
    }
    return $this->tokens;
  }

  /**
   * Compute and store token values.
   */
  public function evaluate() {
    $event = new TokenValueEvent($this);
    $this->dispatcher->dispatch(Events::TOKEN_EVALUATE, $event);
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

    $message = $this->getMessage($name);
    $row->fill($message['format']);
    $useSmarty = !empty($row->context['smarty']);

    // FIXME preg_callback.
    $tokens = $this->rowValues[$row->tokenRow][$message['format']];
    $flatTokens = array();
    \CRM_Utils_Array::flatten($tokens, $flatTokens, '', '.');
    $filteredTokens = array();
    foreach ($flatTokens as $k => $v) {
      $filteredTokens['{' . $k . '}'] = ($useSmarty ? \CRM_Utils_Token::tokenEscapeSmarty($v) : $v);
    }

    $event = new TokenRenderEvent($this);
    $event->message = $message;
    $event->context = $row->context;
    $event->row = $row;
    $event->string = strtr($message['string'], $filteredTokens);
    $this->dispatcher->dispatch(Events::TOKEN_RENDER, $event);
    return $event->string;
  }

}

class TokenRowIterator extends \IteratorIterator {

  protected $tokenProcessor;

  /**
   * @param TokenProcessor $tokenProcessor
   * @param Traversable $iterator
   */
  public function __construct(TokenProcessor $tokenProcessor, Traversable $iterator) {
    parent::__construct($iterator); // TODO: Change the autogenerated stub
    $this->tokenProcessor = $tokenProcessor;
  }

  public function current() {
    return new TokenRow($this->tokenProcessor, parent::key());
  }

}
