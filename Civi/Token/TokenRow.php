<?php
namespace Civi\Token;

use Brick\Money\Money;

/**
 * Class TokenRow
 *
 * @package Civi\Token
 *
 * A TokenRow is a helper/stub providing simplified access to the TokenProcessor.
 * There are two common cases for using the TokenRow stub:
 *
 * (1) When setting up a job, you may specify general/baseline info.
 * This is called the "context" data. Here, we create two rows:
 *
 * ```
 * $proc->addRow()->context('contact_id', 123);
 * $proc->addRow()->context('contact_id', 456);
 * ```
 *
 * (2) When defining a token (eg `{profile.viewUrl}`), you might read the
 * context-data (`contact_id`) and set the token-data (`profile => viewUrl`):
 *
 * ```
 * foreach ($proc->getRows() as $row) {
 *   $row->tokens('profile', [
 *     'viewUrl' => 'http://example.com/profile?cid=' . urlencode($row->context['contact_id'];
 *   ]);
 * }
 * ```
 *
 * The context and tokens can be accessed using either methods or attributes.
 *
 * ```
 * # Setting context data
 * $row->context('contact_id', 123);
 * $row->context(['contact_id' => 123]);
 *
 * # Setting token data
 * $row->tokens('profile', ['viewUrl' => 'http://example.com/profile?cid=123']);
 * $row->tokens('profile', 'viewUrl, 'http://example.com/profile?cid=123');
 *
 * # Reading context data
 * echo $row->context['contact_id'];
 *
 * # Reading token data
 * echo $row->tokens['profile']['viewUrl'];
 * ```
 *
 * Note: The methods encourage a "fluent" style. They were written for PHP 5.3
 * (eg before short-array syntax was supported) and are fairly flexible about
 * input notations (e.g. `context(string $key, mixed $value)` vs `context(array $keyValuePairs)`).
 *
 * Note: An instance of `TokenRow` is a stub which only contains references to the
 * main data in `TokenProcessor`. There may be several `TokenRow` stubs
 * referencing the same `TokenProcessor`. You can think of `TokenRow` objects as
 * lightweight and disposable.
 */
class TokenRow {

  /**
   * The token-processor is where most data is actually stored.
   *
   * Note: Not intended for public usage. However, this is marked public to allow
   * interaction classes in this package (`TokenProcessor`<=>`TokenRow`<=>`TokenRowContext`).
   *
   * @var TokenProcessor
   */
  public $tokenProcessor;

  /**
   * Row ID - the record within TokenProcessor that we're accessing.
   *
   * @var int
   */
  public $tokenRow;

  /**
   * The MIME type associated with new token-values.
   *
   * This is generally manipulated as part of a fluent chain, eg
   *
   * $row->format('text/plain')->token(['display_name', 'Alice Bobdaughter']);
   *
   * @var string
   */
  public $format;

  /**
   * @var array|\ArrayAccess
   *   List of token values.
   *   This is a facade for the TokenProcessor::$rowValues.
   *   Ex: ['contact' => ['display_name' => 'Alice']]
   */
  public $tokens;

  /**
   * @var array|\ArrayAccess
   *   List of context values.
   *   This is a facade for the TokenProcessor::$rowContexts.
   *   Ex: ['controller' => 'CRM_Foo_Bar']
   */
  public $context;

  public function __construct(TokenProcessor $tokenProcessor, $key) {
    $this->tokenProcessor = $tokenProcessor;
    $this->tokenRow = $key;
    // Set a default.
    $this->format('text/plain');
    $this->context = new TokenRowContext($tokenProcessor, $key);
  }

  /**
   * @param string $format
   * @return TokenRow
   */
  public function format($format) {
    $this->format = $format;
    $this->tokens = &$this->tokenProcessor->rowValues[$this->tokenRow][$format];
    return $this;
  }

  /**
   * Update the value of a context element.
   *
   * @param string|array $a
   * @param mixed $b
   * @return TokenRow
   */
  public function context($a = NULL, $b = NULL) {
    if (is_array($a)) {
      \CRM_Utils_Array::extend($this->tokenProcessor->rowContexts[$this->tokenRow], $a);
    }
    elseif (is_array($b)) {
      \CRM_Utils_Array::extend($this->tokenProcessor->rowContexts[$this->tokenRow][$a], $b);
    }
    else {
      $this->tokenProcessor->rowContexts[$this->tokenRow][$a] = $b;
    }
    return $this;
  }

  /**
   * Update the value of a token.
   *
   * Eileen said: If you are reading this it probably means you can't follow this function.
   * Don't worry - I've stared at it & all I see is a bunch of letters. However,
   * the answer to your problem is almost certainly that you are passing in null
   * rather than an empty string for 'c'.
   * MJW said: I've renamed the bunch of letters so they might be a bit more meaningful.
   * Also, I don't think this function should ever be called without $tokenField being set
   * but tests do call it like that.
   *
   * @param string|array $tokenEntity
   * @param string|array $tokenField
   * @param string|array|\DateTime|\Brick\Money\Money $tokenValue
   *
   * @return TokenRow
   */
  public function tokens($tokenEntity, $tokenField = NULL, $tokenValue = NULL): TokenRow {
    if (is_array($tokenEntity)) {
      \CRM_Utils_Array::extend($this->tokens, $tokenEntity);
    }
    elseif (is_array($tokenField)) {
      \CRM_Utils_Array::extend($this->tokens[$tokenEntity], $tokenField);
    }
    elseif (is_array($tokenValue)) {
      \CRM_Utils_Array::extend($this->tokens[$tokenEntity][$tokenField], $tokenValue);
    }
    elseif ($tokenValue === NULL) {
      $this->tokens[$tokenEntity] = $tokenField;
    }
    else {
      $this->tokens[$tokenEntity][$tokenField] = $tokenValue;
    }
    return $this;
  }

  /**
   * Update the value of a custom field token.
   *
   * @param string $entity
   * @param int $customFieldID
   * @param int $entityID
   * @return TokenRow
   */
  public function customToken($entity, $customFieldID, $entityID) {
    $customFieldName = 'custom_' . $customFieldID;
    if (empty($entityID)) {
      return $this->format('text/html')->tokens($entity, $customFieldName, '');
    }
    $record = civicrm_api3($entity, 'getSingle', [
      'return' => $customFieldName,
      'id' => $entityID,
    ]);
    $fieldValue = $record[$customFieldName] ?? '';
    $originalValue = $fieldValue;
    // format the raw custom field value into proper display value
    if (isset($fieldValue)) {
      $fieldValue = (string) \CRM_Core_BAO_CustomField::displayValue($fieldValue, $customFieldID);
    }
    // This is a bit of a clumsy wy of detecting a link field but if you look into the displayValue
    // function you will understand.... By assigning the url as a plain token the text version can
    // use it as plain text (not html re-converted which kinda works but not in subject lines)
    if (is_string($fieldValue) && is_string($originalValue) && strpos($fieldValue, '<a href') !== FALSE && strpos($originalValue, '<a href') === FALSE) {
      $this->format('text/plain')->tokens($entity, $customFieldName, $originalValue);
    }

    return $this->format('text/html')->tokens($entity, $customFieldName, $fieldValue);
  }

  /**
   * Update the value of a token. Apply formatting based on DB schema.
   *
   * @param string $tokenEntity
   * @param string $tokenField
   * @param string $baoName
   * @param string $baoField
   * @param mixed $fieldValue
   * @return TokenRow
   * @throws \CRM_Core_Exception
   */
  public function dbToken($tokenEntity, $tokenField, $baoName, $baoField, $fieldValue) {
    \CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    if ($fieldValue === NULL || $fieldValue === '') {
      return $this->tokens($tokenEntity, $tokenField, '');
    }

    $fields = $baoName::fields();
    if (!empty($fields[$baoField]['pseudoconstant'])) {
      $options = $baoName::buildOptions($baoField, 'get');
      return $this->format('text/plain')->tokens($tokenEntity, $tokenField, $options[$fieldValue]);
    }

    switch ($fields[$baoField]['type']) {
      case \CRM_Utils_Type::T_DATE + \CRM_Utils_Type::T_TIME:
        return $this->format('text/plain')->tokens($tokenEntity, $tokenField, \CRM_Utils_Date::customFormat($fieldValue));

      case \CRM_Utils_Type::T_MONEY:
        // Is this something you should ever use? Seems like you need more context
        // to know which currency to use.
        return $this->format('text/plain')->tokens($tokenEntity, $tokenField, \CRM_Utils_Money::format($fieldValue));

      case \CRM_Utils_Type::T_STRING:
      case \CRM_Utils_Type::T_BOOLEAN:
      case \CRM_Utils_Type::T_INT:
      case \CRM_Utils_Type::T_TEXT:
        return $this->format('text/plain')->tokens($tokenEntity, $tokenField, $fieldValue);

    }

    throw new \CRM_Core_Exception("Cannot format token for field '$baoField' in '$baoName'");
  }

  /**
   * Auto-convert between different formats
   *
   * @param string $format
   *
   * @return TokenRow
   */
  public function fill($format = NULL) {
    if ($format === NULL) {
      $format = $this->format;
    }

    if (!isset($this->tokenProcessor->rowValues[$this->tokenRow]['text/html'])) {
      $this->tokenProcessor->rowValues[$this->tokenRow]['text/html'] = [];
    }
    if (!isset($this->tokenProcessor->rowValues[$this->tokenRow]['text/plain'])) {
      $this->tokenProcessor->rowValues[$this->tokenRow]['text/plain'] = [];
    }

    $htmlTokens = &$this->tokenProcessor->rowValues[$this->tokenRow]['text/html'];
    $textTokens = &$this->tokenProcessor->rowValues[$this->tokenRow]['text/plain'];

    switch ($format) {
      case 'text/html':
        // Plain => HTML.
        foreach ($textTokens as $entity => $values) {
          $entityFields = civicrm_api3($entity, "getFields", ['api_action' => 'get']);
          foreach ($values as $field => $value) {
            if (!isset($htmlTokens[$entity][$field])) {
              // CRM-18420 - Activity Details Field are enclosed within <p>,
              // hence if $body_text is empty, htmlentities will lead to
              // conversion of these tags resulting in raw HTML.
              if ($entity == 'activity' && $field == 'details') {
                $htmlTokens[$entity][$field] = $value;
              }
              elseif (($entityFields['values'][$field]['data_type'] ?? NULL) === 'Memo') {
                // Memo fields aka custom fields of type Note are html.
                $htmlTokens[$entity][$field] = \CRM_Utils_String::purifyHTML($value);
              }
              else {
                $htmlTokens[$entity][$field] = is_object($value) ? $value : rtrim(nl2br(htmlentities($value, ENT_QUOTES)), "\r\n");
              }
            }
          }
        }
        break;

      case 'text/plain':
        // HTML => Plain.
        foreach ($htmlTokens as $entity => $values) {
          foreach ($values as $field => $value) {
            if (!$value instanceof \DateTime && !$value instanceof Money) {
              // rtrim removes trailing lines from <p> tags.
              $value = rtrim(\CRM_Utils_String::htmlToText($value));
            }
            if (!isset($textTokens[$entity][$field])) {
              $textTokens[$entity][$field] = $value;
            }
          }
        }
        break;

      default:
        throw new \RuntimeException('Invalid format');
    }

    return $this;
  }

  /**
   * Render a message.
   *
   * @param string $name
   *   The name previously registered with TokenProcessor::addMessage.
   * @return string
   *   Fully rendered message, with tokens merged.
   */
  public function render($name) {
    return $this->tokenProcessor->render($name, $this);
  }

}

/**
 * Class TokenRowContext
 * @package Civi\Token
 *
 * Combine the row-context and general-context into a single array-like facade.
 */
class TokenRowContext implements \ArrayAccess, \IteratorAggregate, \Countable {

  /**
   * @var TokenProcessor
   */
  protected $tokenProcessor;

  protected $tokenRow;

  /**
   * Class constructor.
   *
   * @param array $tokenProcessor
   * @param array $tokenRow
   */
  public function __construct($tokenProcessor, $tokenRow) {
    $this->tokenProcessor = $tokenProcessor;
    $this->tokenRow = $tokenRow;
  }

  /**
   * Does offset exist.
   *
   * @param mixed $offset
   *
   * @return bool
   */
  public function offsetExists($offset): bool {
    return isset($this->tokenProcessor->rowContexts[$this->tokenRow][$offset])
      || isset($this->tokenProcessor->context[$offset]);
  }

  /**
   * Get offset.
   *
   * @param string $offset
   *
   * @return string
   */
  #[\ReturnTypeWillChange]
  public function &offsetGet($offset) {
    if (isset($this->tokenProcessor->rowContexts[$this->tokenRow][$offset])) {
      return $this->tokenProcessor->rowContexts[$this->tokenRow][$offset];
    }
    if (isset($this->tokenProcessor->context[$offset])) {
      return $this->tokenProcessor->context[$offset];
    }
    $val = NULL;
    return $val;
  }

  /**
   * Set offset.
   *
   * @param string $offset
   * @param mixed $value
   */
  public function offsetSet($offset, $value): void {
    $this->tokenProcessor->rowContexts[$this->tokenRow][$offset] = $value;
  }

  /**
   * Unset offset.
   *
   * @param mixed $offset
   */
  public function offsetUnset($offset): void {
    unset($this->tokenProcessor->rowContexts[$this->tokenRow][$offset]);
  }

  /**
   * Get iterator.
   *
   * @return \ArrayIterator
   */
  #[\ReturnTypeWillChange]
  public function getIterator() {
    return new \ArrayIterator($this->createMergedArray());
  }

  /**
   * Count.
   *
   * @return int
   */
  public function count(): int {
    return count($this->createMergedArray());
  }

  /**
   * Create merged array.
   *
   * @return array
   */
  protected function createMergedArray() {
    return array_merge(
      $this->tokenProcessor->rowContexts[$this->tokenRow],
      $this->tokenProcessor->context
    );
  }

}
