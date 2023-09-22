<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * A lazy-array works much like a regular array or ArrayObject. However, it is
 * initially empty - and it is only populated if used.
 */
class CRM_Utils_LazyArray implements ArrayAccess, IteratorAggregate, Countable {

  /**
   * A function which generates a list of values.
   *
   * @var callable
   *   function(): iterable
   */
  private $func;

  /**
   * Cached values
   *
   * @var array|null
   */
  private $cache;

  /**
   * CRM_Utils_LazyList constructor.
   *
   * @param callable $func
   *   Function which provides a list of values (array/iterator/generator).
   */
  public function __construct($func) {
    $this->func = $func;
  }

  /**
   * Determine if the content has been fetched.
   *
   * @return bool
   */
  public function isLoaded() {
    return $this->cache !== NULL;
  }

  public function load($force = FALSE) {
    if ($this->cache === NULL || $force) {
      $this->cache = CRM_Utils_Array::cast(call_user_func($this->func));
    }
    return $this;
  }

  public function offsetExists($offset): bool {
    return isset($this->load()->cache[$offset]);
  }

  #[\ReturnTypeWillChange]
  public function &offsetGet($offset) {
    return $this->load()->cache[$offset];
  }

  public function offsetSet($offset, $value): void {
    if ($offset === NULL) {
      $this->load()->cache[] = $value;
    }
    else {
      $this->load()->cache[$offset] = $value;
    }
  }

  public function offsetUnset($offset): void {
    unset($this->load()->cache[$offset]);
  }

  #[\ReturnTypeWillChange]
  public function getIterator() {
    return new ArrayIterator($this->load()->cache);
  }

  /**
   * @return array
   */
  public function getArrayCopy() {
    return $this->load()->cache;
  }

  public function count(): int {
    return count($this->load()->cache);
  }

}
