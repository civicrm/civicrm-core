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
class Result extends \ArrayObject implements \JsonSerializable {
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
  /**
   * Not for public use. Instead, please use countFetched(), countMatched() and count().
   *
   * @var int
   */
  public $rowCount;

  /**
   * How many entities matched the query, regardless of LIMIT clauses.
   *
   * This requires that row_count is included in the SELECT.
   *
   * @var int
   */
  protected $matchedCount;

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
   * Return the one-and-only result record.
   *
   * If there are too many or too few results, then throw an exception.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function single() {
    return \CRM_Utils_Array::single($this, "{$this->entity} record");
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
   * Unlike $this->rekey, this rewrites the row keys not the column keys.
   *
   * @param string $key
   * @return $this
   * @throws \CRM_Core_Exception
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
        throw new \CRM_Core_Exception("Key $key not found in api results");
      }
      $this->exchangeArray($newResults);
    }
    return $this;
  }

  /**
   * Returns the number of results.
   *
   * If row_count was included in the select fields, then this will be the
   * number of matched entities, even if this differs from the number of
   * entities fetched.
   *
   * If row_count was not included, then this returns the number of entities
   * fetched, which may or may not be the number of matches.
   *
   * Your code might be easier to reason about if you use countFetched() or
   * countMatched() instead.
   *
   * @return int
   */
  public function count(): int {
    return $this->rowCount ?? parent::count();
  }

  /**
   * Returns the number of results fetched.
   *
   * If a limit was used, this will be a number up to that limit.
   *
   * In the case that *only* the row_count was fetched, this will be zero, since no *entities* were fetched.
   *
   * @return int
   */
  public function countFetched(): int {
    return parent::count();
  }

  /**
   * Returns the number of results matched. This is different from the number of results returned:
   *
   * - For `get` actions, this is the total number of records regardless of LIMIT, so can be >= the number of results returned.
   * - For `save` actions, this is the number of records UPDATED (not created), so can be <= the number of results returned.
   *
   * @return int
   */
  public function countMatched(): int {
    if (!isset($this->matchedCount)) {
      throw new \CRM_Core_Exception("countMatched can only be used if there was no limit set or if row_count was included in the select fields.");
    }
    return $this->matchedCount;
  }

  /**
   * Provides a way for API implementations to set the *matched* count.
   */
  public function setCountMatched(int $c) {
    $this->matchedCount = $c;
  }

  public function hasCountMatched(): bool {
    return isset($this->matchedCount);
  }

  /**
   * Reduce each result to one field
   *
   * @param string $columnName
   * @param string|null $indexBy
   * @return array
   */
  public function column($columnName, $indexBy = NULL): array {
    return array_column($this->getArrayCopy(), $columnName, $indexBy ?? $this->indexedBy);
  }

  /**
   * Rewrite keys in each result according to a map or a callback function.
   *
   * Unlike $this->indexBy, this rewrites the column keys not the row keys.
   *
   * @param array|callable $map
   *   Map of keys to convert e.g. `[old_key => new_key]`
   *   Or a callback function like `fn($key, $value) => $newKey`
   * @return $this
   */
  public function rekey($map) {
    $callback = is_callable($map) ? $map : fn($key) => $map[$key] ?? $key;
    foreach ($this as &$items) {
      $items = \CRM_Utils_Array::rekey($items, $callback);
    }
    return $this;
  }

  /**
   * @return array
   */
  public function jsonSerialize(): array {
    return $this->getArrayCopy();
  }

}
