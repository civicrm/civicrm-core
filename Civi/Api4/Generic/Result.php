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

namespace Civi\Api4\Generic;

/**
 * Container for api results.
 *
 * The Result object has three functions:
 *
 *  1. Store the results of the API call (accessible via ArrayAccess).
 *  2. Store metadata like the Entity & Action names.
 *     - Note: some actions extend the Result object to store extra metadata.
 *       For example, BasicReplaceAction returns ReplaceResult which includes the additional $deleted property to list any items deleted by the operation.
 *  3. Provide convenience methods like `$result->first()` and `$result->indexBy($field)`.
 */
class Result extends \ArrayObject {
  /**
   * @var string
   */
  public $entity;
  /**
   * @var string
   */
  public $action;
  /**
   * @var array
   */
  public $debug;
  /**
   * Api version
   * @var int
   */
  public $version = 4;

  private $indexedBy;

  /**
   * Return first result.
   * @return array|null
   */
  public function first() {
    foreach ($this as $values) {
      return $values;
    }
    return NULL;
  }

  /**
   * Return last result.
   * @return array|null
   */
  public function last() {
    $items = $this->getArrayCopy();
    return array_pop($items);
  }

  /**
   * @param int $index
   * @return array|null
   */
  public function itemAt($index) {
    $length = $index < 0 ? 0 - $index : $index + 1;
    if ($length > count($this)) {
      return NULL;
    }
    return array_slice(array_values($this->getArrayCopy()), $index, 1)[0];
  }

  /**
   * Re-index the results array (which by default is non-associative)
   *
   * Drops any item from the results that does not contain the specified key
   *
   * @param string $key
   * @return $this
   * @throws \API_Exception
   */
  public function indexBy($key) {
    $this->indexedBy = $key;
    if (count($this)) {
      $newResults = [];
      foreach ($this as $values) {
        if (isset($values[$key])) {
          $newResults[$values[$key]] = $values;
        }
      }
      if (!$newResults) {
        throw new \API_Exception("Key $key not found in api results");
      }
      $this->exchangeArray($newResults);
    }
    return $this;
  }

  /**
   * Returns the number of results
   *
   * @return int
   */
  public function count() {
    $count = parent::count();
    if ($count == 1 && is_array($this->first()) && array_keys($this->first()) == ['row_count']) {
      return $this->first()['row_count'];
    }
    return $count;
  }

  /**
   * Reduce each result to one field
   *
   * @param $name
   * @return array
   */
  public function column($name) {
    return array_column($this->getArrayCopy(), $name, $this->indexedBy);
  }

}
