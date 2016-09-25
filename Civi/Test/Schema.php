<?php
namespace Civi\Test;

use RuntimeException;

/**
 * Class Schema
 *
 * Manage the entire database. This is useful for destroying or loading the schema.
 */
class Schema {

  /**
   * @param string $type
   *   'BASE TABLE' or 'VIEW'.
   * @return array
   */
  public function getTables($type) {
    $pdo = \Civi\Test::pdo();
    // only consider real tables and not views
    $query = sprintf(
      "SELECT table_name FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = %s AND TABLE_TYPE = %s",
      $pdo->quote(\Civi\Test::dsn('database')),
      $pdo->quote($type)
    );
    $tables = $pdo->query($query);
    $result = array();
    foreach ($tables as $table) {
      $result[] = $table['table_name'];
    }
    return $result;
  }

  public function setStrict($checks) {
    $dbName = \Civi\Test::dsn('database');
    if ($checks) {
      $queries = array(
        "USE {$dbName};",
        "SET global innodb_flush_log_at_trx_commit = 1;",
        "SET SQL_MODE='STRICT_ALL_TABLES';",
        "SET foreign_key_checks = 1;",
      );
    }
    else {
      $queries = array(
        "USE {$dbName};",
        "SET foreign_key_checks = 0",
        "SET SQL_MODE='STRICT_ALL_TABLES';",
        "SET global innodb_flush_log_at_trx_commit = 2;",
      );
    }
    foreach ($queries as $query) {
      if (\Civi\Test::execute($query) === FALSE) {
        throw new RuntimeException("Query failed: $query");
      }
    }
    return $this;
  }

  public function dropAll() {
    $queries = array();
    foreach ($this->getTables('VIEW') as $table) {
      if (preg_match('/^(civicrm_|log_)/', $table)) {
        $queries[] = "DROP VIEW $table";
      }
    }

    foreach ($this->getTables('BASE TABLE') as $table) {
      if (preg_match('/^(civicrm_|log_)/', $table)) {
        $queries[] = "DROP TABLE $table";
      }
    }

    $this->setStrict(FALSE);
    foreach ($queries as $query) {
      if (\Civi\Test::execute($query) === FALSE) {
        throw new RuntimeException("dropSchema: Query failed: $query");
      }
    }
    $this->setStrict(TRUE);

    return $this;
  }

  /**
   * @return array
   */
  public function truncateAll() {
    $tables = \Civi\Test::schema()->getTables('BASE TABLE');

    $truncates = array();
    $drops = array();
    foreach ($tables as $table) {
      // skip log tables
      if (substr($table, 0, 4) == 'log_') {
        continue;
      }

      // don't change list of installed extensions
      if ($table == 'civicrm_extension') {
        continue;
      }

      if (substr($table, 0, 14) == 'civicrm_value_') {
        $drops[] = 'DROP TABLE ' . $table . ';';
      }
      elseif (substr($table, 0, 9) == 'civitest_') {
        // ignore
      }
      else {
        $truncates[] = 'TRUNCATE ' . $table . ';';
      }
    }

    \Civi\Test::schema()->setStrict(FALSE);
    $queries = array_merge($truncates, $drops);
    foreach ($queries as $query) {
      if (\Civi\Test::execute($query) === FALSE) {
        throw new RuntimeException("Query failed: $query");
      }
    }
    \Civi\Test::schema()->setStrict(TRUE);

    return $this;
  }

}
