<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * Dear God Why Do I Have To Write This (Dumb SQL Builder)
 *
 * Usage:
 * @code
 * $select = CRM_Utils_SQL_Select::from('civicrm_activity act')
 *     ->join('absence', 'inner join civicrm_activity absence on absence.id = act.source_record_id')
 *     ->where('activity_type_id = #type', array('type' => 234))
 *     ->where('status_id IN (#statuses)', array('statuses' => array(1,2,3))
 *     ->where('subject like @subj', array('subj' => '%hello%'))
 *     ->where('!dynamicColumn = 1', array('dynamicColumn' => 'coalesce(is_active,0)'))
 *     ->where('!column = @value', array(
 *        'column' => $customField->column_name,
 *        'value' => $form['foo']
 *      ))
 * echo $select->toSQL();
 * @endcode
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
 *    - control characters (@!#) are mandatory in expressions but optional in arg-keys
 *  - Variables may be individual values or arrays; arrays are imploded with commas
 *  - Conditionals are AND'd; if you need OR's, do it yourself
 *  - Use classes/functions with documentation (rather than undocumented array-trees)
 *  - For any given string, interpolation is only performed once. After an interpolation,
 *    a string may never again be subjected to interpolation.
 *
 * The "interpolate-once" principle can be enforced by either interpolating on input
 * xor output. The notations for input and output interpolation are a bit different,
 * and they may not be mixed.
 *
 * @code
 * // Interpolate on input. Set params when using them.
 * $select->where('activity_type_id = #type', array(
 *   'type' => 234,
 * ));
 *
 * // Interpolate on output. Set params independently.
 * $select
 *     ->where('activity_type_id = #type')
 *     ->param('type', 234),
 * @endcode
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Utils_SQL_Select implements ArrayAccess {

  /**
   * Interpolate values as soon as they are passed in (where(), join(), etc).
   *
   * Default.
   *
   * Pro: Every clause has its own unique namespace for parameters.
   * Con: Probably slower.
   * Advice: Use this when aggregating SQL fragments from agents who
   *   maintained by different parties.
   */
  const INTERPOLATE_INPUT = 'in';

  /**
   * Interpolate values when rendering SQL output (toSQL()).
   *
   * Pro: Probably faster.
   * Con: Must maintain an aggregated list of all parameters.
   * Advice: Use this when you have control over the entire query.
   */
  const INTERPOLATE_OUTPUT = 'out';

  /**
   * Determine mode automatically. When the first attempt is made
   * to use input-interpolation (eg `where(..., array(...))`) or
   * output-interpolation (eg `param(...)`), the mode will be
   * set. Subsequent calls will be validated using the same mode.
   */
  const INTERPOLATE_AUTO = 'auto';

  private $mode = NULL;
  private $insertInto = NULL;
  private $insertIntoFields = array();
  private $selects = array();
  private $from;
  private $joins = array();
  private $wheres = array();
  private $groupBys = array();
  private $havings = array();
  private $orderBys = array();
  private $limit = NULL;
  private $offset = NULL;
  private $params = array();
  private $distinct = NULL;

  // Public to work-around PHP 5.3 limit.
  public $strict = NULL;

  /**
   * Create a new SELECT query.
   *
   * @param string $from
   *   Table-name and optional alias.
   * @param array $options
   * @return CRM_Utils_SQL_Select
   */
  public static function from($from, $options = array()) {
    return new self($from, $options);
  }

  /**
   * Create a partial SELECT query.
   *
   * @param array $options
   * @return CRM_Utils_SQL_Select
   */
  public static function fragment($options = array()) {
    return new self(NULL, $options);
  }

  /**
   * Create a new SELECT query.
   *
   * @param string $from
   *   Table-name and optional alias.
   * @param array $options
   */
  public function __construct($from, $options = array()) {
    $this->from = $from;
    $this->mode = isset($options['mode']) ? $options['mode'] : self::INTERPOLATE_AUTO;
  }

  /**
   * Make a new copy of this query.
   *
   * @return CRM_Utils_SQL_Select
   */
  public function copy() {
    return clone $this;
  }

  /**
   * Merge something or other.
   *
   * @param CRM_Utils_SQL_Select $other
   * @param array|NULL $parts
   *   ex: 'joins', 'wheres'
   * @return CRM_Utils_SQL_Select
   */
  public function merge($other, $parts = NULL) {
    if ($other === NULL) {
      return $this;
    }

    if ($this->mode === self::INTERPOLATE_AUTO) {
      $this->mode = $other->mode;
    }
    elseif ($other->mode === self::INTERPOLATE_AUTO) {
      // Noop.
    }
    elseif ($this->mode !== $other->mode) {
      // Mixing modes will lead to someone getting an expected substitution.
      throw new RuntimeException("Cannot merge queries that use different interpolation modes ({$this->mode} vs {$other->mode}).");
    }

    $arrayFields = array('insertIntoFields', 'selects', 'joins', 'wheres', 'groupBys', 'havings', 'orderBys', 'params');
    foreach ($arrayFields as $f) {
      if ($parts === NULL || in_array($f, $parts)) {
        $this->{$f} = array_merge($this->{$f}, $other->{$f});
      }
    }

    $flatFields = array('insertInto', 'from', 'limit', 'offset');
    foreach ($flatFields as $f) {
      if ($parts === NULL || in_array($f, $parts)) {
        if ($other->{$f} !== NULL) {
          $this->{$f} = $other->{$f};
        }
      }
    }

    return $this;
  }

  /**
   * Add a new JOIN clause.
   *
   * Note: To add multiple JOINs at once, use $name===NULL and
   * pass an array of $exprs.
   *
   * @param string|NULL $name
   *   The effective alias of the joined table.
   * @param string|array $exprs
   *   The complete join expression (eg "INNER JOIN mytable myalias ON mytable.id = maintable.foo_id").
   * @param array|null $args
   * @return CRM_Utils_SQL_Select
   */
  public function join($name, $exprs, $args = NULL) {
    if ($name !== NULL) {
      $this->joins[$name] = $this->interpolate($exprs, $args);
    }
    else {
      foreach ($exprs as $name => $expr) {
        $this->joins[$name] = $this->interpolate($expr, $args);
      }
      return $this;
    }
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
      $this->selects[] = $this->interpolate($expr, $args);
    }
    return $this;
  }

  /**
   * Return only distinct values
   *
   * @param bool $isDistinct allow DISTINCT select or not
   * @return CRM_Utils_SQL_Select
   */
  public function distinct($isDistinct = TRUE) {
    if ($isDistinct) {
      $this->distinct = 'DISTINCT ';
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
      $evaluatedExpr = $this->interpolate($expr, $args);
      $this->wheres[$evaluatedExpr] = $evaluatedExpr;
    }
    return $this;
  }

  /**
   * Group results by adding extra items to the GROUP BY clause.
   *
   * @param string|array $exprs list of SQL expressions
   * @param null|array $args use NULL to disable interpolation; use an array of variables to enable
   * @return CRM_Utils_SQL_Select
   */
  public function groupBy($exprs, $args = NULL) {
    $exprs = (array) $exprs;
    foreach ($exprs as $expr) {
      $evaluatedExpr = $this->interpolate($expr, $args);
      $this->groupBys[$evaluatedExpr] = $evaluatedExpr;
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
      $evaluatedExpr = $this->interpolate($expr, $args);
      $this->havings[$evaluatedExpr] = $evaluatedExpr;
    }
    return $this;
  }

  /**
   * Sort results by adding extra items to the ORDER BY clause.
   *
   * @param string|array $exprs list of SQL expressions
   * @param null|array $args use NULL to disable interpolation; use an array of variables to enable
   * @return CRM_Utils_SQL_Select
   */
  public function orderBy($exprs, $args = NULL) {
    $exprs = (array) $exprs;
    foreach ($exprs as $expr) {
      $evaluatedExpr = $this->interpolate($expr, $args);
      $this->orderBys[$evaluatedExpr] = $evaluatedExpr;
    }
    return $this;
  }

  /**
   * Set one (or multiple) parameters to interpolate into the query.
   *
   * @param array|string $keys
   *   Key name, or an array of key-value pairs.
   * @param null|mixed $value
   * @return \CRM_Utils_SQL_Select
   */
  public function param($keys, $value = NULL) {
    if ($this->mode === self::INTERPOLATE_AUTO) {
      $this->mode = self::INTERPOLATE_OUTPUT;
    }
    elseif ($this->mode !== self::INTERPOLATE_OUTPUT) {
      throw new RuntimeException("Select::param() only makes sense when interpolating on output.");
    }

    if (is_array($keys)) {
      foreach ($keys as $k => $v) {
        $this->params[$k] = $v;
      }
    }
    else {
      $this->params[$keys] = $value;
    }
    return $this;
  }

  /**
   * Set a limit on the number of records to return.
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
   * Insert the results of the SELECT query into another
   * table.
   *
   * @param string $table
   *   The name of the other table (which receives new data).
   * @param array $fields
   *   The fields to fill in the other table (in order).
   * @return CRM_Utils_SQL_Select
   * @see insertIntoField
   */
  public function insertInto($table, $fields = array()) {
    $this->insertInto = $table;
    $this->insertIntoField($fields);
    return $this;
  }

  /**
   * @param array $fields
   *   The fields to fill in the other table (in order).
   * @return CRM_Utils_SQL_Select
   */
  public function insertIntoField($fields) {
    $fields = (array) $fields;
    foreach ($fields as $field) {
      $this->insertIntoFields[] = $field;
    }
    return $this;
  }

  /**
   * @param array|NULL $parts
   *   List of fields to check (e.g. 'selects', 'joins').
   *   Defaults to all.
   * @return bool
   */
  public function isEmpty($parts = NULL) {
    $empty = TRUE;
    $fields = array(
      'insertInto',
      'insertIntoFields',
      'selects',
      'from',
      'joins',
      'wheres',
      'groupBys',
      'havings',
      'orderBys',
      'limit',
      'offset',
    );
    if ($parts !== NULL) {
      $fields = array_intersect($fields, $parts);
    }
    foreach ($fields as $field) {
      if (!empty($this->{$field})) {
        $empty = FALSE;
      }
    }
    return $empty;
  }

  /**
   * Enable (or disable) strict mode.
   *
   * In strict mode, unknown variables will generate exceptions.
   *
   * @param bool $strict
   * @return CRM_Utils_SQL_Select
   */
  public function strict($strict = TRUE) {
    $this->strict = $strict;
    return $this;
  }

  /**
   * Given a string like "field_name = @value", replace "@value" with an escaped SQL string
   *
   * @param $expr SQL expression
   * @param null|array $args a list of values to insert into the SQL expression; keys are prefix-coded:
   *   prefix '@' => escape SQL
   *   prefix '#' => literal number, skip escaping but do validation
   *   prefix '!' => literal, skip escaping and validation
   *   if a value is an array, then it will be imploded
   *
   * PHP NULL's will be treated as SQL NULL's. The PHP string "null" will be treated as a string.
   *
   * @param string $activeMode
   *
   * @return string
   */
  public function interpolate($expr, $args, $activeMode = self::INTERPOLATE_INPUT) {
    if ($args === NULL) {
      return $expr;
    }
    else {
      if ($this->mode === self::INTERPOLATE_AUTO) {
        $this->mode = $activeMode;
      }
      elseif ($activeMode !== $this->mode) {
        throw new RuntimeException("Cannot mix interpolation modes.");
      }

      $select = $this;
      return preg_replace_callback('/([#!@])([a-zA-Z0-9_]+)/', function($m) use ($select, $args) {
        if (isset($args[$m[2]])) {
          $values = $args[$m[2]];
        }
        elseif (isset($args[$m[1] . $m[2]])) {
          // Backward compat. Keys in $args look like "#myNumber" or "@myString".
          $values = $args[$m[1] . $m[2]];
        }
        elseif ($select->strict) {
          throw new CRM_Core_Exception('Cannot build query. Variable "' . $m[1] . $m[2] . '" is unknown.');
        }
        else {
          // Unrecognized variables are ignored. Mitigate risk of accidents.
          return $m[0];
        }
        $values = is_array($values) ? $values : array($values);
        switch ($m[1]) {
          case '@':
            $parts = array_map(array($select, 'escapeString'), $values);
            return implode(', ', $parts);

          // TODO: ensure all uses of this un-escaped literal are safe
          case '!':
            return implode(', ', $values);

          case '#':
            foreach ($values as $valueKey => $value) {
              if ($value === NULL) {
                $values[$valueKey] = 'NULL';
              }
              elseif (!is_numeric($value)) {
                //throw new API_Exception("Failed encoding non-numeric value" . var_export(array($m[0] => $values), TRUE));
                throw new CRM_Core_Exception("Failed encoding non-numeric value (" . $m[0] . ")");
              }
            }
            return implode(', ', $values);

          default:
            throw new CRM_Core_Exception("Unrecognized prefix");
        }
      }, $expr);
    }
  }

  /**
   * @param string|NULL $value
   * @return string
   *   SQL expression, e.g. "it\'s great" (with-quotes) or NULL (without-quotes)
   */
  public function escapeString($value) {
    return $value === NULL ? 'NULL' : '"' . CRM_Core_DAO::escapeString($value) . '"';
  }

  /**
   * @return string
   *   SQL statement
   */
  public function toSQL() {
    $sql = '';
    if ($this->insertInto) {
      $sql .= 'INSERT INTO ' . $this->insertInto . ' (';
      $sql .= implode(', ', $this->insertIntoFields);
      $sql .= ")\n";
    }
    if ($this->selects) {
      $sql .= 'SELECT ' . $this->distinct . implode(', ', $this->selects) . "\n";
    }
    else {
      $sql .= 'SELECT *' . "\n";
    }
    if ($this->from !== NULL) {
      $sql .= 'FROM ' . $this->from . "\n";
    }
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
    if ($this->mode === self::INTERPOLATE_OUTPUT) {
      $sql = $this->interpolate($sql, $this->params, self::INTERPOLATE_OUTPUT);
    }
    return $sql;
  }

  public function offsetExists($offset) {
    return isset($this->params[$offset]);
  }

  public function offsetGet($offset) {
    return $this->params[$offset];
  }

  public function offsetSet($offset, $value) {
    $this->param($offset, $value);
  }

  public function offsetUnset($offset) {
    unset($this->params[$offset]);
  }

}
