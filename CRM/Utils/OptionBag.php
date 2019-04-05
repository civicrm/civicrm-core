<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Class CRM_Utils_OptionBag.
 */
class CRM_Utils_OptionBag implements ArrayAccess, IteratorAggregate, Countable {
  protected $data;

  /**
   * @param array $data
   */
  public function __construct($data = []) {
    $this->data = $data;
  }

  /**
   * @return array
   */
  public function getArray() {
    return $this->data;
  }

  /**
   * Retrieve a value from the bag.
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
      throw new \API_Exception(ts("Could not find valid value for %1 (%2)", [1 => $key, 2 => $type]));
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
   * (PHP 5 &gt;= 5.0.0)
   * Whether a offset exists
   * @link http://php.net/manual/en/arrayaccess.offsetexists.php
   *
   * @param mixed $offset
   *   An offset to check for.
   *
   * @return bool
   *   true on success or false on failure.
   *   The return value will be casted to boolean if non-boolean was returned.
   */
  public function offsetExists($offset) {
    return array_key_exists($offset, $this->data);
  }

  /**
   * (PHP 5 &gt;= 5.0.0)
   * Offset to retrieve
   * @link http://php.net/manual/en/arrayaccess.offsetget.php
   *
   * @param mixed $offset
   *   The offset to retrieve.
   *
   * @return mixed
   *   Can return all value types.
   */
  public function offsetGet($offset) {
    return $this->data[$offset];
  }

  /**
   * (PHP 5 &gt;= 5.0.0)
   * Offset to set
   * @link http://php.net/manual/en/arrayaccess.offsetset.php
   *
   * @param mixed $offset
   *   The offset to assign the value to.
   *
   * @param mixed $value
   *   The value to set.
   */
  public function offsetSet($offset, $value) {
    $this->data[$offset] = $value;
  }

  /**
   * (PHP 5 &gt;= 5.0.0)
   * Offset to unset
   * @link http://php.net/manual/en/arrayaccess.offsetunset.php
   *
   * @param mixed $offset
   *   The offset to unset.
   */
  public function offsetUnset($offset) {
    unset($this->data[$offset]);
  }

  /**
   * (PHP 5 &gt;= 5.0.0)
   * Retrieve an external iterator
   * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
   *
   * @return Traversable
   *   An instance of an object implementing Iterator or
   *   Traversable
   */
  public function getIterator() {
    return new ArrayIterator($this->data);
  }

  /**
   * (PHP 5 &gt;= 5.1.0)
   * Count elements of an object
   * @link http://php.net/manual/en/countable.count.php
   *
   * @return int
   *   The custom count as an integer.
   *   The return value is cast to an integer.
   */
  public function count() {
    return count($this->data);
  }

}
