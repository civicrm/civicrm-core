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

use Civi\Api4\Utils\CoreUtil;

/**
 * Base class for all actions that need to fetch records (`Get`, `Update`, `Delete`, etc.).
 *
 * @package Civi\Api4\Generic
 *
 * @method $this setWhere(array $wheres)
 * @method array getWhere()
 * @method $this setOrderBy(array $order)
 * @method array getOrderBy()
 * @method $this setLimit(int $limit)
 * @method int getLimit()
 * @method $this setOffset(int $offset)
 * @method int getOffset()
 */
abstract class AbstractQueryAction extends AbstractAction {

  /**
   * Criteria for selecting $ENTITIES.
   *
   * ```php
   * $example->addWhere('contact_type', 'IN', ['Individual', 'Household'])
   * ```
   * @var array
   */
  protected $where = [];

  /**
   * Array of field(s) to use in ordering the results.
   *
   * Defaults to id ASC
   *
   * ```php
   * $example->addOrderBy('sort_name', 'ASC')
   * ```
   * @var array
   */
  protected $orderBy = [];

  /**
   * Maximum number of $ENTITIES to return.
   *
   * Defaults to `0` - unlimited.
   *
   * Note: the Api Explorer sets this to `25` by default to avoid timeouts.
   * Change or remove this default for your application code.
   *
   * @var int
   */
  protected $limit = 0;

  /**
   * Zero-based index of first $ENTITY to return.
   *
   * Defaults to `0` - first $ENTITY found.
   *
   * @var int
   */
  protected $offset = 0;

  /**
   * @param string $fieldName
   * @param string $op
   * @param mixed $value
   * @return $this
   * @throws \CRM_Core_Exception
   */
  public function addWhere(string $fieldName, string $op, $value = NULL) {
    if (!in_array($op, CoreUtil::getOperators())) {
      throw new \CRM_Core_Exception('Unsupported operator');
    }
    $this->where[] = [$fieldName, $op, $value];
    return $this;
  }

  /**
   * Adds one or more AND/OR/NOT clause groups
   *
   * @param string $operator
   * @param mixed $condition1 ... $conditionN
   *   Either a nested array of arguments, or a variable number of arguments passed to this function.
   *
   * @return $this
   * @throws \CRM_Core_Exception
   */
  public function addClause(string $operator, $condition1) {
    if (!is_array($condition1[0])) {
      $condition1 = array_slice(func_get_args(), 1);
    }
    $this->where[] = [$operator, $condition1];
    return $this;
  }

  /**
   * Adds to the orderBy clause
   * @param string $fieldName
   * @param string $direction
   * @return $this
   */
  public function addOrderBy(string $fieldName, $direction = 'ASC') {
    $this->orderBy[$fieldName] = $direction;
    return $this;
  }

  /**
   * Produces a human-readable where clause, for the reading enjoyment of you humans.
   *
   * @param array $whereClause
   * @param string $op
   * @return string
   */
  protected function whereClauseToString($whereClause = NULL, $op = 'AND') {
    if ($whereClause === NULL) {
      $whereClause = $this->where;
    }
    $output = '';
    if (!is_array($whereClause) || !$whereClause) {
      return $output;
    }
    if (in_array($whereClause[0], ['AND', 'OR', 'NOT'])) {
      $op = array_shift($whereClause);
      if ($op == 'NOT') {
        $output = 'NOT ';
        $op = 'AND';
      }
      return $output . '(' . $this->whereClauseToString($whereClause, $op) . ')';
    }
    elseif (isset($whereClause[1]) && in_array($whereClause[1], CoreUtil::getOperators())) {
      $output = $whereClause[0] . ' ' . $whereClause[1] . ' ';
      if (isset($whereClause[2])) {
        $output .= is_array($whereClause[2]) ? '[' . implode(', ', $whereClause[2]) . ']' : $whereClause[2];
      }
    }
    else {
      $clauses = [];
      foreach (array_filter($whereClause) as $clause) {
        $clauses[] = $this->whereClauseToString($clause, $op);
      }
      $output = implode(" $op ", $clauses);
    }
    return $output;
  }

}
