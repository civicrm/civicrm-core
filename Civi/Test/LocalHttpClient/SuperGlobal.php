<?php
namespace Civi\Test\LocalHttpClient;

/**
 * @internal
 */
class SuperGlobal {

  protected string $name;

  /**
   * @param string $name
   */
  public function __construct(string $name) {
    $this->name = $name;
  }

  public function getValues() {
    return $GLOBALS[$this->name];
  }

  public function setValues(iterable $values): void {
    foreach ($values as $key => $value) {
      $GLOBALS[$this->name][$key] = $value;
    }
  }

  public function unsetKeys(iterable $keys): void {
    foreach ($keys as $key) {
      unset($GLOBALS[$this->name][$key]);
    }
  }

}
