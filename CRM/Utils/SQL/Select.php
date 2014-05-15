<?php

/**
 * Dear God Why Do I Have To Write This (Dumb SQL Builder)
 *
 * Usage:
 * $select = new CRM_Utils_SQL_Select('civicrm_activity act');
 * $select
 *     ->join('absence', 'inner join civicrm_activity absence on absence.id = act.source_record_id')
 *     ->where('activity_type_id = #type', array('#type' => 234))
 *     ->where('status_id IN (#statuses)', array('#statuses' => array(1,2,3))
 *     ->where('subject like @subj', array('@subj' => '%hello%'))
 *     ->where('!dynamicColumn = 1', array('!dynamicColumn' => 'coalesce(is_active,0)'))
 *     ->where('!column = @value', array(
 *        '!column' => $customField->column_name,
 *        '@value' => $form['foo']
 *      ))
 * echo $select->toSQL();
 *
 * Design principles:
 *  - Portable
 *    - No knowledge of the underlying SQL API (except for escaping -- CRM_Core_DAO::escapeString)
 *    - No knowledge of the underlying data model
 *    - Single file
 *  - SQL clauses correspond to PHP functions ($select->where("foo_id=123"))
 *  - Variable escaping is concise and controllable based on prefixes, eg
 *    - similar to Drupal's t()
 *    - use "@varname" to insert the escaped value
 *    - use "!varname" to insert raw (unescaped) values
 *    - use "#varname" to insert a numerical value (these are validated but not escaped)
 *    - to disable any preprocessing, simply omit the variable list
 *  - Variables may be individual values or arrays; arrays are imploded with commas
 *  - Conditionals are AND'd; if you need OR's, do it yourself
 *  - Use classes/functions with documentation (rather than undocumented array-trees)
 */
class CRM_Utils_SQL_Select {
  private $selects = array();
  private $from;
  private $joins = array();
  private $wheres = array();
  private $groupBys = array();
  private $havings = array();
  private $orderBys = array();
  private $limit = NULL;
  private $offset = NULL;

  /**
   * Create a new SELECT query
   *
   * @param string $from table-name and optional alias
   * @return CRM_Utils_SQL_Select
   */
  public static function from($from) {
    return new self($from);
  }

  /**
   * Create a new SELECT query
   *
   * @param string $from table-name and optional alias
   */
  public function __construct($from) {
    $this->from = $from;
  }

  /**
   * Add a new JOIN clause
   *
   * @param string $name the effective alias of the joined table
   * @param string $expr the complete join expression (eg "INNER JOIN mytable myalias ON mytable.id = maintable.foo_id")
   * @param array|null $args
   * @return CRM_Utils_SQL_Select
   */
  public function join($name, $expr, $args = NULL) {
    $this->joins[$name] = $this->interpolate($expr, $args);
    return $this;
  }

  /**
   * Specify the column(s)/value(s) to return by adding to the SELECT clause
   *
   * @param string|array $exprs list of SQL expressions
   * @param null|array $args use NULL to disable interpolation; use an array of variables to enable
   * @return CRM_Utils_SQL_Select
   */
  public function select($exprs, $args = NULL) {
    $exprs = (array) $exprs;
    foreach ($exprs as $expr) {
      $this->selects[$expr] = $this->interpolate($expr, $args);
    }
    return $this;
  }

  /**
   * Limit results by adding extra condition(s) to the WHERE clause
   *
   * @param string|array $exprs list of SQL expressions
   * @param null|array $args use NULL to disable interpolation; use an array of variables to enable
   * @return CRM_Utils_SQL_Select
   */
  public function where($exprs, $args = NULL) {
    $exprs = (array) $exprs;
    foreach ($exprs as $expr) {
      $this->wheres[$expr] = $this->interpolate($expr, $args);
    }
    return $this;
  }

  /**
   * Group results by adding extra items to the GROUP BY clause
   *
   * @param string|array $exprs list of SQL expressions
   * @param null|array $args use NULL to disable interpolation; use an array of variables to enable
   * @return CRM_Utils_SQL_Select
   */
  public function groupBy($exprs, $args = NULL) {
    $exprs = (array) $exprs;
    foreach ($exprs as $expr) {
      $this->groupBys[$expr] = $this->interpolate($expr, $args);
    }
    return $this;
  }

  /**
   * Limit results by adding extra condition(s) to the HAVING clause
   *
   * @param string|array $exprs list of SQL expressions
   * @param null|array $args use NULL to disable interpolation; use an array of variables to enable
   * @return CRM_Utils_SQL_Select
   */
  public function having($exprs, $args = NULL) {
    $exprs = (array) $exprs;
    foreach ($exprs as $expr) {
      $this->havings[$expr] = $this->interpolate($expr, $args);
    }
    return $this;
  }

  /**
   * Sort results by adding extra items to the ORDER BY clause
   *
   * @param string|array $exprs list of SQL expressions
   * @param null|array $args use NULL to disable interpolation; use an array of variables to enable
   * @return CRM_Utils_SQL_Select
   */
  public function orderBy($exprs, $args = NULL) {
    $exprs = (array) $exprs;
    foreach ($exprs as $expr) {
      $this->orderBys[$expr] = $this->interpolate($expr, $args);
    }
    return $this;
  }

  /**
   * Set a limit on the number of records to return
   *
   * @param int $limit
   * @param int $offset
   * @return CRM_Utils_SQL_Select
   * @throws CRM_Core_Exception
   */
  public function limit($limit, $offset = 0) {
    if ($limit !== NULL && !is_numeric($limit)) {
      throw new CRM_Core_Exception("Illegal limit");
    }
    if ($offset !== NULL && !is_numeric($offset)) {
      throw new CRM_Core_Exception("Illegal offset");
    }
    $this->limit = $limit;
    $this->offset = $offset;
    return $this;
  }

  /**
   * Given a string like "field_name = @value", replace "@value" with an escaped SQL string
   *
   * @param string SQL expression
   * @param null|array $args a list of values to insert into the SQL expression; keys are prefix-coded:
   *   prefix '@' => escape SQL
   *   prefix '#' => literal number, skip escaping but do validation
   *   prefix '!' => literal, skip escaping and validation
   *   if a value is an array, then it will be imploded
   *
   * PHP NULL's will be treated as SQL NULL's. The PHP string "null" will be treated as a string.
   *
   * @throws CRM_Core_Exception
   * @return string SQL expression
   */
  public function interpolate($expr, $args) {
    if ($args === NULL) {
      return $expr;
    }
    else {
      foreach (array_keys($args) as $key) {
        $values = is_array($args[$key]) ? $args[$key] : array($args[$key]);
        if ($key{0} == '@') {
          $parts = array_map(array($this, 'escapeString'), $values);
          $args[$key] = implode(', ', $parts);
        }
        elseif ($key{0} == '!') {
          $args[$key] = implode(', ', $values);
        }
        elseif ($key{0} == '#') {
          foreach ($values as $valueKey => $value) {
            if ($value === NULL) {
              $values[$valueKey] = 'NULL';
            }
            elseif (!is_numeric($value)) {
              //throw new API_Exception("Failed encoding non-numeric value" . var_export(array($key => $args[$key]), TRUE));
              throw new CRM_Core_Exception("Failed encoding non-numeric value");
            }
          }
          $args[$key] = implode(', ', $values);
        }
        else {
          throw new CRM_Core_Exception("Bad SQL parameter key: $key");
        }
      }
      return strtr($expr, $args);
    }
  }

  /**
   * @param string|NULL $value
   * @return string SQL expression, e.g. "it\'s great" (with-quotes) or NULL (without-quotes)
   */
  protected function escapeString($value) {
    return $value === NULL ? 'NULL' : '"' . CRM_Core_DAO::escapeString($value) . '"';
  }

  /**
   * @return string SQL statement
   */
  public function toSQL() {
    if ($this->selects) {
      $sql = 'SELECT ' . implode(', ', $this->selects) . "\n";
    }
    else {
      $sql = 'SELECT *' . "\n";
    }
    $sql .= 'FROM ' . $this->from . "\n";
    foreach ($this->joins as $join) {
      $sql .= $join . "\n";
    }
    if ($this->wheres) {
      $sql .= 'WHERE (' . implode(') AND (', $this->wheres) . ")\n";
    }
    if ($this->groupBys) {
      $sql .= 'GROUP BY ' . implode(', ', $this->groupBys) . "\n";
    }
    if ($this->havings) {
      $sql .= 'HAVING (' . implode(') AND (', $this->havings) . ")\n";
    }
    if ($this->orderBys) {
      $sql .= 'ORDER BY ' . implode(', ', $this->orderBys) . "\n";
    }
    if ($this->limit !== NULL) {
      $sql .= 'LIMIT ' . $this->limit . "\n";
      if ($this->offset !== NULL) {
        $sql .= 'OFFSET ' . $this->offset . "\n";
      }
    }
    return $sql;
  }
}
