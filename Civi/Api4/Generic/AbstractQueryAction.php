<?php

namespace Civi\Api4\Generic;

/**
 * Base class for all actions that need to fetch records (Get, Update, Delete, etc)
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
   * Criteria for selecting items.
   *
   * $example->addWhere('contact_type', 'IN', array('Individual', 'Household'))
   *
   * @var array
   */
  protected $where = [];

  /**
   * Array of field(s) to use in ordering the results
   *
   * Defaults to id ASC
   *
   * $example->addOrderBy('sort_name', 'ASC')
   *
   * @var array
   */
  protected $orderBy = [];

  /**
   * Maximum number of results to return.
   *
   * Defaults to unlimited.
   *
   * Note: the Api Explorer sets this to 25 by default to avoid timeouts.
   * Change or remove this default for your application code.
   *
   * @var int
   */
  protected $limit = 0;

  /**
   * Zero-based index of first result to return.
   *
   * Defaults to "0" - first record.
   *
   * @var int
   */
  protected $offset = 0;

  /**
   * @param string $field
   * @param string $op
   * @param mixed $value
   * @return $this
   * @throws \API_Exception
   */
  public function addWhere($field, $op, $value = NULL) {
    if (!in_array($op, \CRM_Core_DAO::acceptedSQLOperators())) {
      throw new \API_Exception('Unsupported operator');
    }
    $this->where[] = [$field, $op, $value];
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
   * @throws \API_Exception
   */
  public function addClause($operator, $condition1) {
    if (!is_array($condition1[0])) {
      $condition1 = array_slice(func_get_args(), 1);
    }
    $this->where[] = [$operator, $condition1];
    return $this;
  }

  /**
   * @param string $field
   * @param string $direction
   * @return $this
   */
  public function addOrderBy($field, $direction = 'ASC') {
    $this->orderBy[$field] = $direction;
    return $this;
  }

  /**
   * A human-readable where clause, for the reading enjoyment of you humans.
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
    elseif (isset($whereClause[1]) && in_array($whereClause[1], \CRM_Core_DAO::acceptedSQLOperators())) {
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
