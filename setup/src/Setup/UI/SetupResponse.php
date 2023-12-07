<?php
namespace Civi\Setup\UI;

/**
 * This represents a response from the Setup UI.
 *
 * Previously, responses where an array of the form:
 *   [0 => array $headers, 1 => string $body].
 *
 * This implements \ArrayAccess for backward compatibility.
 */
class SetupResponse implements \ArrayAccess {

  /**
   * @var bool
   *
   * TRUE if the body represents a fully formed HTML page.
   * FALSE if the body is a fragment of an HTML page.
   */
  public $isComplete = TRUE;

  /**
   * @var array
   *   Ex: ['Content-Type': 'text/html']
   */
  public $headers = [];

  /**
   * @var array
   *   Ex: $assets[0] = ['type' => 'script-url', 'url' => 'http://foobar'];
   */
  public $assets = [];

  /**
   * @var string
   *   Ex: '<h1>Hello world</h1>'
   */
  public $body = '';

  /**
   * @var string|null
   *   The title of the response page (if it's an HTML response).
   */
  public $title = NULL;

  /**
   * @var int
   */
  public $code = 200;

  /**
   * @var array
   *   Array(int $oldPos => string $newName).
   */
  protected $oldFieldMap;

  /**
   * SetupResponse constructor.
   */
  public function __construct() {
    $this->oldFieldMap = [
      0 => 'headers',
      1 => 'body',
    ];
  }

  public function offsetExists($offset): bool {
    return isset($this->oldFieldMap[$offset]);
  }

  #[\ReturnTypeWillChange]
  public function &offsetGet($offset) {
    if (isset($this->oldFieldMap[$offset])) {
      $field = $this->oldFieldMap[$offset];
      return $this->{$field};
    }
    else {
      return NULL;
    }
  }

  public function offsetSet($offset, $value): void {
    if (isset($this->oldFieldMap[$offset])) {
      $field = $this->oldFieldMap[$offset];
      $this->{$field} = $value;
    }
  }

  public function offsetUnset($offset): void {
    if (isset($this->oldFieldMap[$offset])) {
      $field = $this->oldFieldMap[$offset];
      unset($this->{$field});
    }
  }

}
