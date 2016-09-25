<?php

/**
 * Dear God Why Do I Have To Write This (Dumb SQL Builder)
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

  private $verb = 'INSERT INTO';

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
   */
  private $columns;

  /**
   * Create a new INSERT query.
   *
   * @param string $table
   *   Table-name and optional alias.
   * @return CRM_Utils_SQL_Insert
   */
  public static function into($table) {
    return new self($table);
  }

  /**
   * Insert a record based on a DAO.
   *
   * @param \CRM_Core_DAO $dao
   * @return \CRM_Utils_SQL_Insert
   * @throws \CRM_Core_Exception
   */
  public static function dao(CRM_Core_DAO $dao) {
    $table = CRM_Core_DAO::getLocaleTableName($dao->getTableName());
    $row = array();
    foreach ((array) $dao as $key => $value) {
      if ($value === 'null') {
        $value = NULL; // Blerg!!!
      }
      // Skip '_foobar' and '{\u00}*_options' and 'N'.
      if (preg_match('/[a-zA-Z]/', $key{0}) && $key !== 'N') {
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
   */
  public function __construct($table) {
    $this->table = $table;
    $this->rows = array();
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
    elseif (array_diff($this->columns, $columns) !== array()) {
      throw new CRM_Core_Exception("Inconsistent column names");
    }

    $escapedRow = array();
    foreach ($this->columns as $column) {
      $escapedRow[$column] = $this->escapeString($row[$column]);
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

  /**
   * Escape string.
   *
   * @param string|NULL $value
   *
   * @return string
   *   SQL expression, e.g. "it\'s great" (with-quotes) or NULL (without-quotes)
   */
  protected function escapeString($value) {
    return $value === NULL ? 'NULL' : '"' . CRM_Core_DAO::escapeString($value) . '"';
  }

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

}
