<?php

/**
 * Object-oriented SQL builder for INSERT queries.
 *
 * Usage:
 * $insert = CRM_Utils_SQL_Insert::into('mytable')
 *  ->row(array('col1' => '1', 'col2' => '2' ))
 *  ->row(array('col1' => '2b', 'col2' => '1b'));
 * echo $insert->toSQL();
 *
 * Note: In MySQL, numeric values may be escaped. Except for NULL values,
 * it's reasonable for us to simply escape all values by default -- without
 * any knowledge of the underlying schema.
 *
 * Design principles:
 *  - Portable
 *    - No knowledge of the underlying SQL API (except for escaping -- CRM_Core_DAO::escapeString)
 *    - No knowledge of the underlying data model
 *    - Single file
 *  - SQL clauses correspond to PHP functions ($select->where("foo_id=123"))
 */
class CRM_Utils_SQL_Insert {

  /**
   * @var string
   *   Ex: 'INSERT INTO', 'REPLACE INTO'
   */
  private $verb;

  /**
   * @var string
   */
  private $table;

  /**
   * @var array
   */
  private $rows;

  /**
   * Array<string> list of column names
   * @var array
   */
  private $columns;

  /**
   * Create a new INSERT query.
   *
   * @param string $table
   *   Table-name and optional alias.
   * @param string $verb
   *   Ex: 'INSERT INTO', 'REPLACE INTO'
   * @return CRM_Utils_SQL_Insert
   */
  public static function into($table, string $verb = 'INSERT INTO') {
    return new self($table, $verb);
  }

  /**
   * Insert a record based on a DAO.
   *
   * @param \CRM_Core_DAO $dao
   * @return \CRM_Utils_SQL_Insert
   * @throws \CRM_Core_Exception
   */
  public static function dao(CRM_Core_DAO $dao) {
    $table = $dao::getLocaleTableName();
    $row = [];
    foreach ((array) $dao as $key => $value) {
      if ($value === 'null') {
        // Blerg!!!
        $value = NULL;
      }
      // Skip '_foobar' and '{\u00}*_options' and 'N'.
      if (preg_match('/[a-zA-Z]/', $key[0]) && $key !== 'N') {
        $row[$key] = $value;
      }
    }
    return self::into($table)->row($row);
  }

  /**
   * Create a new SELECT query.
   *
   * @param string $table
   *   Table-name and optional alias.
   * @param string $verb
   *   Ex: 'INSERT INTO', 'REPLACE INTO'
   */
  public function __construct($table, string $verb = 'INSERT INTO') {
    $this->table = $table;
    $this->verb = $verb;
    $this->rows = [];
  }

  /**
   * Get columns.
   *
   * @param array $columns
   *
   * @return CRM_Utils_SQL_Insert
   * @throws \CRM_Core_Exception
   */
  public function columns($columns) {
    if ($this->columns !== NULL) {
      throw new CRM_Core_Exception("Column order already specified.");
    }
    $this->columns = $columns;
    return $this;
  }

  /**
   * Get rows.
   *
   * @param array $rows
   *
   * @return CRM_Utils_SQL_Insert
   */
  public function rows($rows) {
    foreach ($rows as $row) {
      $this->row($row);
    }
    return $this;
  }

  /**
   * Get row.
   *
   * @param array $row
   *
   * @return CRM_Utils_SQL_Insert
   * @throws CRM_Core_Exception
   */
  public function row($row) {
    $columns = array_keys($row);

    if ($this->columns === NULL) {
      sort($columns);
      $this->columns = $columns;
    }
    elseif (array_diff($this->columns, $columns) !== []) {
      throw new CRM_Core_Exception("Inconsistent column names");
    }

    $escapedRow = [];
    foreach ($this->columns as $column) {
      if (is_bool($row[$column])) {
        $escapedRow[$column] = (int) $row[$column];
      }
      else {
        $escapedRow[$column] = $this->escapeString($row[$column]);
      }
    }
    $this->rows[] = $escapedRow;

    return $this;
  }

  /**
   * Use REPLACE INTO instead of INSERT INTO.
   *
   * @param bool $asReplace
   *
   * @return CRM_Utils_SQL_Insert
   */
  public function usingReplace($asReplace = TRUE) {
    $this->verb = $asReplace ? 'REPLACE INTO' : 'INSERT INTO';
    return $this;
  }

  use CRM_Utils_SQL_EscapeStringTrait;

  /**
   * Convert to SQL.
   *
   * @return string
   *   SQL statement
   */
  public function toSQL() {
    $columns = "`" . implode('`,`', $this->columns) . "`";
    $sql = "{$this->verb} {$this->table} ({$columns}) VALUES";

    $nextDelim = '';
    foreach ($this->rows as $row) {
      $sql .= "{$nextDelim}\n(" . implode(',', $row) . ")";
      $nextDelim = ',';
    }
    $sql .= "\n";

    return $sql;
  }

  /**
   * Execute the query.
   *
   * @param bool $i18nRewrite
   *   If the system has multilingual features, should the field/table
   *   names be rewritten?
   * @return CRM_Core_DAO
   * @see CRM_Core_DAO::executeQuery
   * @see CRM_Core_I18n_Schema::rewriteQuery
   */
  public function execute($i18nRewrite = TRUE) {
    return CRM_Core_DAO::executeQuery($this->toSQL(), [], TRUE, NULL,
      FALSE, $i18nRewrite);
  }

}
