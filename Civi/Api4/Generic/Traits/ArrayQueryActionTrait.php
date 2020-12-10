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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
    $values = $this->sortArray($values);
    // Set total count before applying limit
    $result->rowCount = count($values);
    $values = $this->limitArray($values);
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
   * @return bool
   * @throws \Civi\API\Exception\NotImplementedException
   */
  private function walkFilters($row, $filters) {
    switch ($filters[0]) {
      case 'AND':
      case 'NOT':
        $result = TRUE;
        foreach ($filters[1] as $filter) {
          if (!$this->walkFilters($row, $filter)) {
            $result = FALSE;
            break;
          }
        }
        return $result == ($filters[0] == 'AND');

      case 'OR':
        $result = !count($filters[1]);
        foreach ($filters[1] as $filter) {
          if ($this->walkFilters($row, $filter)) {
            return TRUE;
          }
        }
        return $result;

      default:
        return $this->filterCompare($row, $filters);
    }
  }

  /**
   * @param array $row
   * @param array $condition
   * @return bool
   * @throws \Civi\API\Exception\NotImplementedException
   */
  private function filterCompare($row, $condition) {
    if (!is_array($condition)) {
      throw new NotImplementedException('Unexpected where syntax; expecting array.');
    }
    $value = $row[$condition[0]] ?? NULL;
    $operator = $condition[1];
    $expected = $condition[2] ?? NULL;
    switch ($operator) {
      case '=':
      case '!=':
      case '<>':
        $equal = $value == $expected;
        // PHP is too imprecise about comparing the number 0
        if ($expected === 0 || $expected === '0') {
          $equal = ($value === 0 || $value === '0');
        }
        // PHP is too imprecise about comparing empty strings
        if ($expected === '') {
          $equal = ($value === '');
        }
        return $equal == ($operator == '=');

      case 'IS NULL':
      case 'IS NOT NULL':
        return is_null($value) == ($operator == 'IS NULL');

      case '>':
        return $value > $expected;

      case '>=':
        return $value >= $expected;

      case '<':
        return $value < $expected;

      case '<=':
        return $value <= $expected;

      case 'BETWEEN':
      case 'NOT BETWEEN':
        $between = ($value >= $expected[0] && $value <= $expected[1]);
        return $between == ($operator == 'BETWEEN');

      case 'LIKE':
      case 'NOT LIKE':
        $pattern = '/^' . str_replace('%', '.*', preg_quote($expected, '/')) . '$/i';
        return !preg_match($pattern, $value) == ($operator != 'LIKE');

      case 'IN':
        return in_array($value, $expected);

      case 'NOT IN':
        return !in_array($value, $expected);

      case 'CONTAINS':
        if (is_array($value)) {
          return in_array($expected, $value);
        }
        elseif (is_string($value) || is_numeric($value)) {
          return strpos((string) $value, (string) $expected) !== FALSE;
        }
        return $value == $expected;

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
      if (isset($a[$field]) && isset($b[$field])) {
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
    if ($this->getSelect() === ['row_count']) {
      $values = [['row_count' => count($values)]];
    }
    elseif ($this->getSelect()) {
      // Return only fields specified by SELECT
      foreach ($values as &$value) {
        $value = array_intersect_key($value, array_flip($this->getSelect()));
      }
    }
    else {
      // With no SELECT specified, return all values that are keyed by plain field name; omit those with :pseudoconstant suffixes
      foreach ($values as &$value) {
        $value = array_filter($value, function($key) {
          return strpos($key, ':') === FALSE;
        }, ARRAY_FILTER_USE_KEY);
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
