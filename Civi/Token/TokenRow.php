<?php
namespace Civi\Token;
use Civi\Token\Event\TokenRenderEvent;

/**
 * Class TokenRow
 * @package Civi\Token
 *
 * A TokenRow is a helper providing simplified access to the
 * TokenProcessor.
 *
 * A TokenRow combines two elements:
 *   - context: This is backend data provided by the controller.
 *   - tokens: This is frontend data that can be mail-merged.
 *
 * The context and tokens can be accessed using either methods
 * or attributes. The methods are appropriate for updates
 * (and generally accept a mix of arrays), and the attributes
 * are appropriate for reads.
 *
 * To update the context or the tokens, use the methods.
 * Note that the methods are fairly flexible about accepting
 * single values or arrays. If given an array, the values
 * will be merged recursively.
 *
 * @code
 * $row
 *   ->context('contact_id', 123)
 *   ->context(array('contact_id' => 123))
 *   ->tokens('profile', array('viewUrl' => 'http://example.com'))
 *   ->tokens('profile', 'viewUrl, 'http://example.com');
 *
 * echo $row->context['contact_id'];
 * echo $row->tokens['profile']['viewUrl'];
 *
 * $row->tokens('profile', array(
 *   'viewUrl' => 'http://example.com/view/' . urlencode($row->context['contact_id'];
 * ));
 * @endcode
 */
class TokenRow {

  /**
   * @var TokenProcessor
   */
  public $tokenProcessor;

  public $tokenRow;

  public $format;

  /**
   * @var array|ArrayAccess
   *   List of token values.
   *   Ex: array('contact' => array('display_name' => 'Alice')).
   */
  public $tokens;

  /**
   * @var array|ArrayAccess
   *   List of context values.
   *   Ex: array('controller' => 'CRM_Foo_Bar').
   */
  public $context;

  public function __construct(TokenProcessor $tokenProcessor, $key) {
    $this->tokenProcessor = $tokenProcessor;
    $this->tokenRow = $key;
    $this->format('text/plain'); // Set a default.
    $this->context = new TokenRowContext($tokenProcessor, $key);
  }

  /**
   * @param string $format
   * @return $this
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
   * @return $this
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
   * @param string|array $a
   * @param string|array $b
   * @param mixed $c
   * @return $this
   */
  public function tokens($a = NULL, $b = NULL, $c = NULL) {
    if (is_array($a)) {
      \CRM_Utils_Array::extend($this->tokens, $a);
    }
    elseif (is_array($b)) {
      \CRM_Utils_Array::extend($this->tokens[$a], $b);
    }
    elseif (is_array($c)) {
      \CRM_Utils_Array::extend($this->tokens[$a][$b], $c);
    }
    elseif ($c === NULL) {
      $this->tokens[$a] = $b;
    }
    else {
      $this->tokens[$a][$b] = $c;
    }
    return $this;
  }

  /**
   * Auto-convert between different formats
   */
  public function fill($format = NULL) {
    if ($format === NULL) {
      $format = $this->format;
    }

    if (!isset($this->tokenProcessor->rowValues[$this->tokenRow]['text/html'])) {
      $this->tokenProcessor->rowValues[$this->tokenRow]['text/html'] = array();
    }
    if (!isset($this->tokenProcessor->rowValues[$this->tokenRow]['text/plain'])) {
      $this->tokenProcessor->rowValues[$this->tokenRow]['text/plain'] = array();
    }

    $htmlTokens = &$this->tokenProcessor->rowValues[$this->tokenRow]['text/html'];
    $textTokens = &$this->tokenProcessor->rowValues[$this->tokenRow]['text/plain'];

    switch ($format) {
      case 'text/html':
        // Plain => HTML.
        foreach ($textTokens as $entity => $values) {
          foreach ($values as $field => $value) {
            if (!isset($htmlTokens[$entity][$field])) {
              $htmlTokens[$entity][$field] = htmlentities($value);
            }
          }
        }
        break;

      case 'text/plain':
        // HTML => Plain.
        foreach ($htmlTokens as $entity => $values) {
          foreach ($values as $field => $value) {
            if (!isset($textTokens[$entity][$field])) {
              $textTokens[$entity][$field] = html_entity_decode(strip_tags($value));
            }
          }
        }
        break;

      default:
        throw new \RuntimeException("Invalid format");
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
   * @param $tokenProcessor
   * @param $tokenRow
   */
  public function __construct($tokenProcessor, $tokenRow) {
    $this->tokenProcessor = $tokenProcessor;
    $this->tokenRow = $tokenRow;
  }

  public function offsetExists($offset) {
    return
      isset($this->tokenProcessor->rowContexts[$this->tokenRow][$offset])
      || isset($this->tokenProcessor->context[$offset]);
  }

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

  public function offsetSet($offset, $value) {
    $this->tokenProcessor->rowContexts[$this->tokenRow][$offset] = $value;
  }

  public function offsetUnset($offset) {
    unset($this->tokenProcessor->rowContexts[$this->tokenRow][$offset]);
  }

  public function getIterator() {
    return new \ArrayIterator($this->createMergedArray());
  }

  public function count() {
    return count($this->createMergedArray());
  }

  protected function createMergedArray() {
    return array_merge(
      $this->tokenProcessor->rowContexts[$this->tokenRow],
      $this->tokenProcessor->context
    );
  }

}
