<?php

/**
 * Class CRM_Utils_OptionBag
 */
class CRM_Utils_OptionBag implements ArrayAccess, IteratorAggregate, Countable {
  protected $data;

  /**
   * @param array $data
   */
  public function __construct($data = array()) {
    $this->data = $data;
  }

  /**
   * @return array
   */
  public function getArray() {
    return $this->data;
  }

  /**
   * Retrieve a value from the bag
   *
   * @param string $key
   * @param string|null $type
   * @param mixed $default
   * @return mixed
   * @throws API_Exception
   */
  public function get($key, $type = NULL, $default = NULL) {
    if (!array_key_exists($key, $this->data)) {
      return $default;
    }
    if (!$type) {
      return $this->data[$key];
    }
    $r = CRM_Utils_Type::validate($this->data[$key], $type);
    if ($r !== NULL) {
      return $r;
    }
    else {
      throw new \API_Exception(ts("Could not find valid value for %1 (%2)", array(1 => $key, 2 => $type)));
    }
  }

  /**
   * @param $key
   *
   * @return bool
   */
  public function has($key) {
    return isset($this->data[$key]);
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Whether a offset exists
   * @link http://php.net/manual/en/arrayaccess.offsetexists.php
   * @param mixed $offset <p>
   * An offset to check for.
   * </p>
   * @return boolean true on success or false on failure.
   * </p>
   * <p>
   * The return value will be casted to boolean if non-boolean was returned.
   */
  public function offsetExists($offset) {
    return array_key_exists($offset, $this->data);
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Offset to retrieve
   * @link http://php.net/manual/en/arrayaccess.offsetget.php
   * @param mixed $offset <p>
   * The offset to retrieve.
   * </p>
   * @return mixed Can return all value types.
   */
  public function offsetGet($offset) {
    return $this->data[$offset];
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Offset to set
   * @link http://php.net/manual/en/arrayaccess.offsetset.php
   * @param mixed $offset <p>
   * The offset to assign the value to.
   * </p>
   * @param mixed $value <p>
   * The value to set.
   * </p>
   * @return void
   */
  public function offsetSet($offset, $value) {
    $this->data[$offset] = $value;
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Offset to unset
   * @link http://php.net/manual/en/arrayaccess.offsetunset.php
   * @param mixed $offset <p>
   * The offset to unset.
   * </p>
   * @return void
   */
  public function offsetUnset($offset) {
    unset($this->data[$offset]);
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Retrieve an external iterator
   * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
   * @return Traversable An instance of an object implementing <b>Iterator</b> or
   * <b>Traversable</b>
   */
  public function getIterator() {
    return new ArrayIterator($this->data);
  }

  /**
   * (PHP 5 &gt;= 5.1.0)<br/>
   * Count elements of an object
   * @link http://php.net/manual/en/countable.count.php
   * @return int The custom count as an integer.
   * </p>
   * <p>
   * The return value is cast to an integer.
   */
  public function count() {
    return count($this->data);
  }


}
