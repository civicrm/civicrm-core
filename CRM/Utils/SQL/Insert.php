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
   * array<string> list of column names
   */
  private $columns;

  /**
   * Create a new INSERT query
   *
   * @param string $table table-name and optional alias
   * @return CRM_Utils_SQL_Insert
   */
  public static function into($table) {
    return new self($table);
  }

  /**
   * Create a new SELECT query
   *
   * @param string $from table-name and optional alias
   */
  public function __construct($table) {
    $this->table = $table;
    $this->rows = array();
  }

  /**
   * @param array $rows
   * @return CRM_Utils_SQL_Insert
   */
  public function rows($rows) {
    foreach ($rows as $row) {
      $this->row($row);
    }
    return $this;
  }

  /**
   * @param array $row
   * @return CRM_Utils_SQL_Insert
   * @throws CRM_Core_Exception
   */
  public function row($row) {
    $columns = array_keys($row);
    sort($columns);

    if ($this->columns === NULL) {
      $this->columns = $columns;
    }
    elseif ($this->columns != $columns) {
      throw new CRM_Core_Exception("Inconsistent column names");
    }

    $escapedRow = array();
    foreach ($columns as $column) {
      $escapedRow[$column] = $this->escapeString($row[$column]);
    }
    $this->rows[] = $escapedRow;

    return $this;
  }

  /**
   * Use REPLACE INTO instead of INSERT INTO
   *
   * @param bool $asReplace
   * @return CRM_Utils_SQL_Insert
   */
  public function usingReplace($asReplace = TRUE) {
    $this->verb = $asReplace ? 'REPLACE INTO' : 'INSERT INTO';
    return $this;
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
