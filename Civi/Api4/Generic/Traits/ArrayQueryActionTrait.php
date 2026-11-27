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

namespace Civi\Api4\Generic\Traits;

use Civi\API\Exception\NotImplementedException;

/**
 * Helper functions for performing api queries on arrays of data.
 *
 * @package Civi\Api4\Generic
 */
trait ArrayQueryActionTrait {

  /**
   * @param array $values
   *   List of all rows to be filtered
   * @param \Civi\Api4\Generic\Result $result
   *   Object to store result
   */
  protected function queryArray($values, $result) {
    $values = $this->filterArray($values);

    if (in_array('row_count', $this->getSelect())) {
      $result->setCountMatched(count($values));
    }
    // Set total count before applying limit
    //
    // This is kept here for backward compatibility, but could be confusing because
    // the API behaviour is different with ArrayQueryActionTrait than with DAO
    // queries. With DAO queries, the rowCount is only the same as the total
    // matched count in specific cases, whereas with the implementation here we are
    // setting rowCount explicitly to the matches count, before we apply limit.
    $result->rowCount = count($values);

    if ($this->select !== ['row_count']) {
      $values = $this->sortArray($values);
      $values = $this->limitArray($values);
    }
    $values = $this->selectArray($values);
    $result->exchangeArray($values);
  }

  /**
   * @param array $values
   * @return array
   */
  protected function filterArray($values) {
    if ($this->getWhere()) {
      $values = array_filter($values, [$this, 'evaluateFilters']);
    }
    return array_values($values);
  }

  /**
   * @param array $row
   * @return bool
   */
  private function evaluateFilters($row) {
    $where = array_values($this->getWhere());
    $allConditions = in_array($where[0], ['AND', 'OR', 'NOT']) ? $where : ['AND', $where];
    return $this->walkFilters($row, $allConditions);
  }

  /**
   * @param array $row
   * @param array $filters
   * @param callable|null $compare
   * @return bool
   * @throws \Civi\API\Exception\NotImplementedException
   */
  private function walkFilters($row, $filters, ?callable $compare = NULL) {
    switch ($filters[0]) {
      case 'AND':
      case 'NOT':
        $result = TRUE;
        foreach ($filters[1] as $filter) {
          if (!$this->walkFilters($row, $filter, $compare)) {
            $result = FALSE;
            break;
          }
        }
        return $result == ($filters[0] == 'AND');

      case 'OR':
        $result = !count($filters[1]);
        foreach ($filters[1] as $filter) {
          if ($this->walkFilters($row, $filter, $compare)) {
            return TRUE;
          }
        }
        return $result;

      default:
        return $compare ? $compare($row, $filters) : self::filterCompare($row, $filters);
    }
  }

  /**
   * @param array $row
   * @param array $condition
   * @param int|null $index
   * @return bool
   * @throws \Civi\API\Exception\NotImplementedException
   */
  public static function filterCompare(array $row, array $condition, ?int $index = NULL): bool {
    $value = $row[$condition[0]] ?? NULL;
    $operator = $condition[1];
    $expected = $condition[2] ?? NULL;
    // Comparison for aggregated values
    if (isset($index) && is_array($value) && $operator !== 'IN' && $operator !== 'NOT IN') {
      $value = $value[$index] ?? NULL;
    }
    return self::compareValues($value, $operator, $expected);
  }

  public static function compareValues($value, string $operator, $expected): bool {
    switch ($operator) {
      case '=':
        // For parity with SQL operators, do case-insensitive matching
        if (is_string($value)) {
          $value = strtolower($value);
        }
        if (is_string($expected)) {
          $expected = strtolower($expected);
        }
        $equal = $value == $expected;
        // PHP is too imprecise about comparing the number 0
        if ($expected === 0 || $expected === '0') {
          $equal = ($value === 0 || $value === '0');
        }
        // PHP is too imprecise about comparing empty strings
        if ($expected === '') {
          $equal = ($value === '');
        }
        return $equal;

      case '<>':
      case '!=':
        return !self::compareValues($value, '=', $expected);

      case 'IS NULL':
        return is_null($value);

      case 'IS NOT NULL':
        return !self::compareValues($value, 'IS NULL', $expected);

      case 'IS EMPTY':
        return empty($value);

      case 'IS NOT EMPTY':
        return !self::compareValues($value, 'IS EMPTY', $expected);

      case '>':
        return $value > $expected;

      case '>=':
        return $value >= $expected;

      case '<':
        return $value < $expected;

      case '<=':
        return $value <= $expected;

      case 'BETWEEN':
        return ($value >= $expected[0] && $value <= $expected[1]);

      case 'NOT BETWEEN':
        return !self::compareValues($value, 'BETWEEN', $expected);

      case 'LIKE':
        if ($value === NULL) {
          return FALSE;
        }
        $pattern = '/^' . str_replace('%', '.*', preg_quote($expected, '/')) . '$/i';
        return !!preg_match($pattern, $value);

      case 'NOT LIKE':
        return !self::compareValues($value, 'LIKE', $expected);

      case 'REGEXP':
      case 'REGEXP BINARY':
        if ($value === NULL) {
          return FALSE;
        }

        // Perform case-sensitive matching for BINARY operator, otherwise insensitive
        $i = str_ends_with($operator, 'BINARY') ? '' : 'i';
        $pattern = '/' . str_replace('/', '\\/', $expected) . "/$i";
        return !!preg_match($pattern, $value);

      case 'NOT REGEXP':
        if ($value === NULL) {
          // preexisting behaviour, but seems wrong?
          return FALSE;
        }
        return !self::compareValues($value, 'REGEXP', $expected);

      case 'NOT REGEXP BINARY':
        if ($value === NULL) {
          // preexisting behaviour, but seems wrong?
          return FALSE;
        }
        return !self::compareValues($value, 'REGEXP BINARY', $expected);

      case 'IN':
        return in_array($value, $expected);

      case 'NOT IN':
        return !self::compareValues($value, 'IN', $expected);

      case 'CONTAINS':
        if (is_array($value) && is_array($expected)) {
          return empty(array_diff($expected, $value));
        }

        if (!is_array($value) && is_array($expected)) {
          return in_array($value, $expected);
        }

        if (is_array($value) && !is_array($expected)) {
          return in_array($expected, $value);
        }

        if (is_string($value) || is_numeric($value)) {
          // Lowercase check if string contains string
          return str_contains(strtolower((string) $value), strtolower((string) $expected));
        }
        return $value == $expected;

      case 'NOT CONTAINS':
        return !self::compareValues($value, 'CONTAINS', $expected);

      case 'CONTAINS ONE OF':
        if (is_array($value) && is_array($expected)) {
          if (is_array($value) && is_array($expected)) {
            foreach ($value as $v) {
              if (in_array($v, $expected)) {
                return TRUE;
              }
            }
            return FALSE;
          }
        }

        if (!is_array($value) && is_array($expected)) {
          return in_array($value, $expected);
        }

        if (is_array($value) && !is_array($expected)) {
          return in_array($expected, $value);
        }

        if (is_string($value) || is_numeric($value)) {
          // Lowercase check if string contains string
          return strpos(strtolower((string) $value), strtolower((string) $expected)) !== FALSE;
        }
        return $value == $expected;

      case 'NOT CONTAINS ONE OF':
        return !self::compareValues($value, 'CONTAINS ONE OF', $expected);

      default:
        throw new NotImplementedException("Unsupported operator: '$operator' cannot be used with array data");
    }
  }

  /**
   * @param $values
   * @return array
   */
  protected function sortArray($values) {
    if ($this->getOrderBy()) {
      usort($values, [$this, 'sortCompare']);
    }
    return $values;
  }

  private function sortCompare($a, $b) {
    foreach ($this->getOrderBy() as $field => $dir) {
      $modifier = $dir == 'ASC' ? 1 : -1;
      if (isset($a[$field], $b[$field])) {
        if ($a[$field] == $b[$field]) {
          continue;
        }
        return (strnatcasecmp($a[$field], $b[$field]) * $modifier);
      }
      elseif (isset($a[$field]) || isset($b[$field])) {
        return ((isset($a[$field]) ? 1 : -1) * $modifier);
      }
    }
    return 0;
  }

  /**
   * @param $values
   * @return array
   */
  protected function selectArray($values) {
    $select = $this->getSelect();
    if ($select === ['row_count']) {
      $values = [];
    }
    elseif ($values && $select) {
      // Return only fields specified by SELECT
      $keys = array_flip($select);
      foreach ($values as &$value) {
        $value = array_intersect_key($value, $keys);
      }
    }
    elseif ($values) {
      // With no SELECT specified, return all values that are keyed by plain field name; omit those with :pseudoconstant suffixes
      $keysWithSuffixes = array_filter(array_keys(\CRM_Utils_Array::first($values)), fn($key) => str_contains($key, ':'));
      if ($keysWithSuffixes) {
        foreach ($values as &$value) {
          \CRM_Utils_Array::remove($value, $keysWithSuffixes);
        }
      }
    }
    return $values;
  }

  /**
   * @param $values
   * @return array
   */
  protected function limitArray($values) {
    if ($this->getOffset() || $this->getLimit()) {
      $values = array_slice($values, $this->getOffset() ?: 0, $this->getLimit() ?: NULL);
    }
    return $values;
  }

}
