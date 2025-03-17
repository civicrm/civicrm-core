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
    $result = [];
    if (!empty($tables)) {
      foreach ($tables as $table) {
        $result[] = $table['TABLE_NAME'] ?? $table['table_name'];
      }
    }
    return $result;
  }

  public function setStrict($checks) {
    $dbName = \Civi\Test::dsn('database');
    if ($checks) {
      $queries = [
        "USE {$dbName};",
        "SET global innodb_flush_log_at_trx_commit = 1;",
        "SET SQL_MODE='STRICT_ALL_TABLES';",
        "SET foreign_key_checks = 1;",
      ];
    }
    else {
      $queries = [
        "USE {$dbName};",
        "SET foreign_key_checks = 0",
        "SET SQL_MODE='STRICT_ALL_TABLES';",
        "SET global innodb_flush_log_at_trx_commit = 2;",
      ];
    }
    foreach ($queries as $query) {
      if (\Civi\Test::execute($query) === FALSE) {
        throw new RuntimeException("Query failed: $query");
      }
    }
    return $this;
  }

  public function dropAll() {
    $queries = [];
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
   * @return Schema
   */
  public function truncateAll() {
    $tables = \Civi\Test::schema()->getTables('BASE TABLE');

    $truncates = [];
    $drops = [];
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

  /**
   * Load a snapshot into CiviCRM's database.
   *
   * @param string $file
   *   Ex: '/path/to/civicrm-4.5.6-foobar.sql.bz2' or '/path/to/civicrm-4.5.6-foobar.mysql.gz'
   * @return Schema
   */
  public function loadSnapshot(string $file) {
    $dsn = \Civi\Test::dsn();
    $defaultsFile = $this->createMysqlDefaultsFile($dsn);
    if (preg_match(';sql.bz2$;', $file)) {
      $cmd = sprintf('bzip2 -d -c %s | mysql --defaults-file=%s %s', escapeshellarg($file), escapeshellarg($defaultsFile), escapeshellarg($dsn['database']));
    }
    elseif (preg_match(';sql.gz$;', $file)) {
      $cmd = sprintf('gzip -d -c %s | mysql --defaults-file=%s %s', escapeshellarg($file), escapeshellarg($defaultsFile), escapeshellarg($dsn['database']));
    }
    else {
      $cmd = sprintf('cat %s | mysql --defaults-file=%s %s', escapeshellarg($file), escapeshellarg($defaultsFile), escapeshellarg($dsn['database']));
    }
    ProcessHelper::runOk($cmd);
    return $this;
  }

  /**
   * When calling "mysql" subprocess, it helps to put DB credentials into "my.cnf"-style file.
   *
   * @param array $dsn
   * @return string
   *   Path to the new "my.cnf" file.
   */
  protected function createMysqlDefaultsFile(array $dsn): string {
    $data = "[client]\n";
    $data .= "host={$dsn['hostspec']}\n";
    $data .= "user={$dsn['username']}\n";
    $data .= "password={$dsn['password']}\n";
    if (!empty($dsn['port'])) {
      $data .= "port={$dsn['port']}\n";
    }

    $file = sys_get_temp_dir() . '/my.cnf-' . hash('sha256', __FILE__ . stat(__FILE__)['mtime'] . $data);
    if (!file_exists($file)) {
      if (!file_put_contents($file, $data)) {
        throw new \RuntimeException("Failed to create temporary my.cnf connection file.");
      }
    }
    return $file;
  }

}
