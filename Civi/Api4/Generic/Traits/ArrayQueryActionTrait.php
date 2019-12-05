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
 * $Id$
 *
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
   *   List of all rows
   * @return array
   *   Filtered list of rows
   */
  protected function queryArray($values) {
    $values = $this->filterArray($values);
    $values = $this->sortArray($values);
    $values = $this->limitArray($values);
    $values = $this->selectArray($values);
    return $values;
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
    $where = $this->getWhere();
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
    $value = isset($row[$condition[0]]) ? $row[$condition[0]] : NULL;
    $operator = $condition[1];
    $expected = isset($condition[2]) ? $condition[2] : NULL;
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
      foreach ($values as &$value) {
        $value = array_intersect_key($value, array_flip($this->getSelect()));
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
