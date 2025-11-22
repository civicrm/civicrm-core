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
 * Object-oriented SQL builder for SELECT queries.
 *
 * This class is foundational to CiviCRM's query functionality for the API,
 * SearchKit, ScheduledReminders, MailingRecipients, etc.
 *
 * Usage:
 * ```
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
 * ```
 *
 * Design principles:
 *  - Portable
 *    - No knowledge of the underlying SQL API (except for escaping -- CRM_Core_DAO::escapeString)
 *    - No knowledge of the underlying data model
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
 * ```
 * // Interpolate on input. Set params when using them.
 * $select->where('activity_type_id = #type', array(
 *   'type' => 234,
 * ));
 *
 * // Interpolate on output. Set params independently.
 * $select
 *     ->where('activity_type_id = #type')
 *     ->param('type', 234),
 * ```
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_SQL_Select extends CRM_Utils_SQL_BaseParamQuery {

  private $insertInto = NULL;
  private $insertVerb = 'INSERT INTO ';
  private $insertIntoFields = [];
  private $onDuplicates = [];
  private $selects = [];
  private $from;
  private $setOps;
  private $setAlias;
  private $joins = [];
  private $wheres = [];
  private $groupBys = [];
  private $havings = [];
  private $orderBys = [];
  private $limit = NULL;
  private $offset = NULL;
  private $distinct = NULL;

  /**
   * Create a new SELECT query.
   *
   * @param string $from
   *   Table-name and optional alias.
   * @param array $options
   * @return CRM_Utils_SQL_Select
   */
  public static function from($from, $options = []) {
    return new self($from, $options);
  }

  /**
   * Create a new SELECT-like query by performing set-operations (e.g. UNION).
   *
   * For example, if you want to query two tables and treat the results as one combined-set, then
   * this is s a set-operation.
   *
   * $queryA = CRM_Utils_SQL_Select::from('table_a');
   * $queryB = CRM_Utils_SQL_Select::from('table_b');
   * $querySet = CRM_Utils_SQL_Select::fromSet()->union('DISTINCT', [$queryA, $queryB])->toSQL();
   *
   * @param array $options
   *   Ex: ['setAlias' => 'uniondata']
   * @return CRM_Utils_SQL_Select
   */
  public static function fromSet($options = []) {
    $options = array_merge(['setAlias' => '_sql_set'], $options);
    $result = new self(NULL, $options);
    $result->setOps = [];
    return $result;
  }

  /**
   * Create a partial SELECT query.
   *
   * @param array $options
   * @return CRM_Utils_SQL_Select
   */
  public static function fragment($options = []) {
    return new self(NULL, $options);
  }

  /**
   * Create a new SELECT query.
   *
   * @param string $from
   *   Table-name and optional alias.
   * @param array $options
   */
  public function __construct($from, $options = []) {
    $this->from = $from;
    $this->mode = $options['mode'] ?? self::INTERPOLATE_AUTO;
    $this->setAlias = $options['setAlias'] ?? NULL;
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
   * @param array|CRM_Utils_SQL_Select $other
   * @param array|null $parts
   *   ex: 'joins', 'wheres'
   * @return CRM_Utils_SQL_Select
   */
  public function merge($other, $parts = NULL) {
    if ($other === NULL) {
      return $this;
    }

    if (is_array($other)) {
      foreach ($other as $fragment) {
        $this->merge($fragment, $parts);
      }
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

    $arrayFields = ['insertIntoFields', 'selects', 'joins', 'wheres', 'groupBys', 'havings', 'orderBys', 'params'];
    foreach ($arrayFields as $f) {
      if ($parts === NULL || in_array($f, $parts)) {
        $this->{$f} = array_merge($this->{$f}, $other->{$f});
      }
    }

    $flatFields = ['insertInto', 'from', 'limit', 'offset'];
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
   * @param string|null $name
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
   * @param int $weight
   * @return \CRM_Utils_SQL_Select
   */
  public function orderBy($exprs, $args = NULL, $weight = 0) {
    static $guid = 0;
    $exprs = (array) $exprs;
    foreach ($exprs as $expr) {
      $evaluatedExpr = $this->interpolate($expr, $args);
      $this->orderBys[$evaluatedExpr] = ['value' => $evaluatedExpr, 'weight' => $weight, 'guid' => $guid++];
    }
    return $this;
  }

  /**
   * Set one (or multiple) parameters to interpolate into the query.
   *
   * @param array|string $keys
   *   Key name, or an array of key-value pairs.
   * @param null|mixed $value
   *   The new value of the parameter.
   *   Values may be strings, ints, or arrays thereof -- provided that the
   *   SQL query uses appropriate prefix (e.g. "@", "!", "#").
   * @return \CRM_Utils_SQL_Select
   */
  public function param($keys, $value = NULL) {
    // Why bother with an override? To provide bett er type-hinting in `@return`.
    return parent::param($keys, $value);
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
   * Add a union to the list of set operations.
   *
   * Ex: CRM_Utils_SQL_Select::fromSet()->union([$subQuery1, $subQuery2])
   * Ex: CRM_Utils_SQL_Select::fromSet()->union($subQuery1)->union($subQuery2);
   *
   * @param string $type "DISTINCT"|"ALL"
   * @param CRM_Utils_SQL_Select[]|CRM_Utils_SQL_Select $subQueries
   * @return $this
   */
  public function union(string $type, $subQueries) {
    return $this->setOp("UNION $type", $subQueries);
  }

  /**
   * Add a set operation.
   *
   * Ex: CRM_Utils_SQL_Select::fromSet()->setOp('INTERSECT', [$subQuery1, $subQuery2])
   *
   * @param string $setOperation
   *   Ex: 'UNION DISTINCT', 'UNION ALL'.
   *   TODO: 'INTERSECT', 'EXCEPT' when moving to MySQL 8.
   * @param CRM_Utils_SQL_Select[]|CRM_Utils_SQL_Select $subQueries
   * @return $this
   * @see https://dev.mysql.com/doc/refman/8.0/en/set-operations.html
   */
  public function setOp(string $setOperation, $subQueries) {
    // TODO: Support more ops like 'INTERSECT' & 'EXCEPT'
    $supportedOps = ['UNION DISTINCT', 'UNION ALL'];
    if (!in_array($setOperation, $supportedOps, TRUE)) {
      throw new CRM_Core_Exception("Unsupported set-operation '$setOperation'. Must be one of (" . implode(', ', $supportedOps) . ')');
    }
    if ($this->from !== NULL || !is_array($this->setOps)) {
      throw new CRM_Core_Exception("Set-operation '$setOperation' must have a list of subqueries. Primitive FROM is not supported.");
    }
    $subQueries = is_array($subQueries) ? $subQueries : [$subQueries]; /* Simple (array)cast would mishandle objects. */
    foreach ($subQueries as $subQuery) {
      if ($this->setOps === []) {
        $this->setOps[] = ['', $subQuery];
      }
      else {
        $this->setOps[] = [$setOperation, $subQuery];
      }
    }
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
  public function insertInto($table, $fields = []) {
    $this->insertInto = $table;
    $this->insertIntoField($fields);
    return $this;
  }

  /**
   * Wrapper function of insertInto fn but sets insertVerb = "INSERT IGNORE INTO "
   *
   * @param string $table
   *   The name of the other table (which receives new data).
   * @param array $fields
   *   The fields to fill in the other table (in order).
   * @return CRM_Utils_SQL_Select
   */
  public function insertIgnoreInto($table, $fields = []) {
    $this->insertVerb = "INSERT IGNORE INTO ";
    return $this->insertInto($table, $fields);
  }

  /**
   * Wrapper function of insertInto fn but sets insertVerb = "REPLACE INTO "
   *
   * @param string $table
   *   The name of the other table (which receives new data).
   * @param array $fields
   *   The fields to fill in the other table (in order).
   */
  public function replaceInto($table, $fields = []) {
    $this->insertVerb = "REPLACE INTO ";
    return $this->insertInto($table, $fields);
  }

  /**
   * Take the results of the SELECT query and copy them into another
   * table.
   *
   * If the same record already exists in the other table (based on
   * primary-key or unique-key), then update the corresponding record.
   *
   * @param string $table
   *   The table to write data into.
   * @param array|string $keys
   *   List of PK/unique fields
   *   NOTE: This must match the unique-key that was declared in the schema.
   * @param array $mapping
   *   List of values to select and where to send them.
   *
   *   For example, consider:
   *     ['relationship_id' => 'rel.id']
   *
   *   This would select the value of 'rel.id' and write to 'relationship_id'.
   *
   * @param null|array $args
   *   Use NULL to skip interpolation; use an array of variables to enable.
   * @return $this
   */
  public function syncInto($table, $keys, $mapping, $args = NULL) {
    $keys = (array) $keys;

    $this->select(array_values($mapping), $args);
    $this->insertInto($table, array_keys($mapping));

    foreach ($mapping as $intoColumn => $fromValue) {
      if (!in_array($intoColumn, $keys)) {
        $this->onDuplicate("$intoColumn = $fromValue", $args);
      }
    }

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
   * For INSERT INTO...SELECT...' queries, you may give an "ON DUPLICATE UPDATE" clause.
   *
   * @param string|array $exprs list of SQL expressions
   * @param null|array $args use NULL to disable interpolation; use an array of variables to enable
   * @return CRM_Utils_SQL_Select
   */
  public function onDuplicate($exprs, $args = NULL) {
    $exprs = (array) $exprs;
    foreach ($exprs as $expr) {
      $evaluatedExpr = $this->interpolate($expr, $args);
      $this->onDuplicates[$evaluatedExpr] = $evaluatedExpr;
    }
    return $this;
  }

  /**
   * @param array|null $parts
   *   List of fields to check (e.g. 'selects', 'joins').
   *   Defaults to all.
   * @return bool
   */
  public function isEmpty($parts = NULL) {
    $empty = TRUE;
    $fields = [
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
    ];
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
   * @return string
   *   SQL statement
   */
  public function toSQL() {
    $sql = '';
    if ($this->insertInto) {
      $sql .= $this->insertVerb . $this->insertInto . ' (';
      $sql .= implode(', ', $this->insertIntoFields);
      $sql .= ")\n";
    }

    if ($this->selects) {
      $sql .= 'SELECT' . (isset($this->distinct) ? ' ' . $this->distinct : '') . "\n";
      $sql .= '  ' . implode(",\n  ", $this->selects) . "\n";
    }
    else {
      $sql .= "SELECT *\n";
    }
    if ($this->from !== NULL) {
      $sql .= 'FROM ' . $this->from . "\n";
    }
    elseif (is_array($this->setOps)) {
      $sql .= "FROM (\n";
      foreach ($this->setOps as $setOp) {
        // $setOp[0] is blank on the first iteration, and subsequently contains a keyword like "UNION ALL".
        $sql .= '  ' . ($setOp[0] ? "{$setOp[0]} " : '') . "(\n";
        $setSql = (is_object($setOp[1]) ? $setOp[1]->toSQL() : $setOp[1]);
        // Add indentation
        $setSql = trim(str_replace("\n", "\n    ", $setSql));
        $sql .= '    ' . $setSql . "\n  )\n";
      }
      $sql .= ") {$this->setAlias}\n";
    }
    foreach ($this->joins as $join) {
      $sql .= $join . "\n";
    }
    if ($this->wheres) {
      $sql .= 'WHERE (' . implode(")\n  AND (", $this->wheres) . ")\n";
    }
    if ($this->groupBys) {
      $sql .= "GROUP BY\n  " . implode(",\n  ", $this->groupBys) . "\n";
    }
    if ($this->havings) {
      $sql .= 'HAVING (' . implode(")\n  AND (", $this->havings) . ")\n";
    }
    if ($this->orderBys) {
      $orderBys = CRM_Utils_Array::crmArraySortByField($this->orderBys,
        ['weight', 'guid']);
      $orderBys = CRM_Utils_Array::collect('value', $orderBys);
      $sql .= "ORDER BY\n  " . implode(",\n  ", $orderBys) . "\n";
    }
    if ($this->limit !== NULL) {
      $sql .= 'LIMIT ' . $this->limit . "\n";
      if ($this->offset !== NULL) {
        $sql .= 'OFFSET ' . $this->offset . "\n";
      }
    }
    if ($this->onDuplicates) {
      if ($this->insertVerb === 'INSERT INTO ') {
        $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(", ", $this->onDuplicates) . "\n";
      }
      else {
        throw new \Exception("The ON DUPLICATE clause and only be used with INSERT INTO queries.");
      }
    }

    if ($this->mode === self::INTERPOLATE_OUTPUT) {
      $sql = $this->interpolate($sql, $this->params, self::INTERPOLATE_OUTPUT);
    }
    return $sql;
  }

  /**
   * Execute the query.
   *
   * To examine the results, use a function like `fetch()`, `fetchAll()`,
   * `fetchValue()`, or `fetchMap()`.
   *
   * @param string|null $daoName
   *   The return object should be an instance of this class.
   *   Ex: 'CRM_Contact_BAO_Contact'.
   * @param bool $i18nRewrite
   *   If the system has multilingual features, should the field/table
   *   names be rewritten?
   * @return CRM_Core_DAO
   * @see CRM_Core_DAO::executeQuery
   * @see CRM_Core_I18n_Schema::rewriteQuery
   */
  public function execute($daoName = NULL, $i18nRewrite = TRUE) {
    // Don't pass through $params. toSQL() handles interpolation.
    $params = [];

    // Don't pass through $abort, $trapException. Just use straight-up exceptions.
    $abort = TRUE;
    $trapException = FALSE;

    // Don't pass through freeDAO. You can do it yourself.
    $freeDAO = FALSE;

    return CRM_Core_DAO::executeQuery($this->toSQL(), $params, $abort, $daoName,
      $freeDAO, $i18nRewrite, $trapException);
  }

  /**
   * @return string
   */
  public function getFrom(): string {
    return $this->from;
  }

  /**
   * @return array
   */
  public function getWhere(): array {
    return $this->wheres;
  }

}
