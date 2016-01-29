<?php

class CiviTestDB {

  /**
   * @var CiviTestPdoUtils
   */
  protected $pdoUtils;

  /**
   * CiviTestDB constructor.
   * @param CiviTestPdoUtils $pdoUtils
   */
  public function __construct($pdoUtils) {
    $this->pdoUtils = $pdoUtils;
  }

  /**
   * Autocreate the schema.
   *
   * Check civitest_schema_rev. If it's out-of-date, drop the old schema and load the
   * new one.
   *
   * @return mixed
   */
  public function updateSchema() {
    $schemaFile = dirname(dirname(dirname(dirname(__FILE__)))) . "/sql/civicrm.mysql";
    if (!is_file($schemaFile)) {
      return $this->fatal("Failed to find schema: $schemaFile");
    }

    // $schemaFileRev = md5(@file_get_contents($schemaFile));
    $schemaFileRev = filemtime($schemaFile) . ' ' . filectime($schemaFile);

    $tables = $this->getCurrentTables('BASE TABLE');
    $liveSchemaRev = '';
    if (in_array('civitest_schema_rev', $tables)) {
      $pdoStmt = $this->pdoUtils->pdo->query("SELECT schema_rev FROM {$this->pdoUtils->dbName}.civitest_schema_rev");
      foreach ($pdoStmt as $row) {
        $liveSchemaRev = $row['schema_rev'];
      }
    }

    if ($liveSchemaRev === $schemaFileRev) {
      return;
    }

    echo "Installing {$this->pdoUtils->dbName} schema\n";
    $this->dropSchema();
    if ($this->pdoUtils->do_query(@file_get_contents($schemaFile)) === FALSE) {
      return $this->fatal("Cannot load $schemaFile. Aborting.");
    }
    $query = sprintf(
      "USE {$this->pdoUtils->dbName};"
      . "DROP TABLE IF EXISTS civitest_schema_rev;"
      . "CREATE TABLE civitest_schema_rev (schema_rev VARCHAR(64));"
      . "INSERT INTO civitest_schema_rev (schema_rev) VALUES (%s);",
      $this->pdoUtils->pdo->quote($schemaFileRev)
    );

    if ($this->pdoUtils->do_query($query) === FALSE) {
      return $this->fatal("Failed to flag schema version: $query");
    }
  }

  public function dropSchema() {
    $queries = array(
      "USE {$this->pdoUtils->dbName};",
      "SET foreign_key_checks = 0",
      // SQL mode needs to be strict, that's our standard
      "SET SQL_MODE='STRICT_ALL_TABLES';",
      "SET global innodb_flush_log_at_trx_commit = 2;",
    );

    foreach ($this->getCurrentTables('VIEW') as $table) {
      if (preg_match('/^(civicrm_|log_)/', $table)) {
        $queries[] = "DROP VIEW $table";
      }
    }

    foreach ($this->getCurrentTables('BASE TABLE') as $table) {
      if (preg_match('/^(civicrm_|log_)/', $table)) {
        $queries[] = "DROP TABLE $table";
      }
    }

    $queries[] = "set global innodb_flush_log_at_trx_commit = 1;";
    $queries[] = "SET foreign_key_checks = 1";

    foreach ($queries as $query) {
      if ($this->pdoUtils->do_query($query) === FALSE) {
        return $this->fatal("dropSchema: Query failed: $query");
      }
    }
  }

  /**
   * @return bool
   */
  public function populate() {
    $pdoUtils = $this->pdoUtils;
    $tables = $this->getCurrentTables('BASE TABLE');

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

    $dbName = $pdoUtils->dbName;
    $queries = array(
      "USE {$dbName};",
      "SET foreign_key_checks = 0",
      // SQL mode needs to be strict, that's our standard
      "SET SQL_MODE='STRICT_ALL_TABLES';",
      "SET global innodb_flush_log_at_trx_commit = 2;",
    );
    $queries = array_merge($queries, $truncates);
    $queries = array_merge($queries, $drops);
    foreach ($queries as $query) {
      if ($pdoUtils->do_query($query) === FALSE) {
        return $this->fatal("Query failed: $query");
      }
    }

    //  initialize test database
    $sql_file2 = dirname(dirname(dirname(dirname(__FILE__)))) . "/sql/civicrm_data.mysql";
    $sql_file3 = dirname(dirname(dirname(dirname(__FILE__)))) . "/sql/test_data.mysql";
    $sql_file4 = dirname(dirname(dirname(dirname(__FILE__)))) . "/sql/test_data_second_domain.mysql";

    $query2 = file_get_contents($sql_file2);
    $query3 = file_get_contents($sql_file3);
    $query4 = file_get_contents($sql_file4);
    if ($pdoUtils->do_query($query2) === FALSE) {
      return $this->fatal("Cannot load civicrm_data.mysql. Aborting.");
    }
    if ($pdoUtils->do_query($query3) === FALSE) {
      return $this->fatal("Cannot load test_data.mysql. Aborting.");
    }
    if ($pdoUtils->do_query($query4) === FALSE) {
      return $this->fatal("Cannot load test_data.mysql. Aborting.");
    }

    // done with all the loading, get transactions back
    if ($pdoUtils->do_query("set global innodb_flush_log_at_trx_commit = 1;") === FALSE) {
      return $this->fatal("Cannot set global? Huh?");
    }

    if ($pdoUtils->do_query("SET foreign_key_checks = 1") === FALSE) {
      return $this->fatal("Cannot get foreign keys back? Huh?");
    }

    unset($query, $query2, $query3);

    // Rebuild triggers
    civicrm_api('system', 'flush', array('version' => 3, 'triggers' => 1));

    CRM_Core_BAO_ConfigSetting::setEnabledComponents(array(
      'CiviEvent',
      'CiviContribute',
      'CiviMember',
      'CiviMail',
      'CiviReport',
      'CiviPledge',
    ));

    return TRUE;
  }

  /**
   * @param $message
   * @return mixed
   */
  protected function fatal($message) {
    echo "$message\n";
    exit(1);
  }

  /**
   * @param string $type
   *   'BASE TABLE' or 'VIEW'.
   * @return array
   */
  protected function getCurrentTables($type) {
    $pdo = $this->pdoUtils->pdo;
    // only consider real tables and not views
    $query = sprintf(
      "SELECT table_name FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = %s AND TABLE_TYPE = %s",
      $pdo->quote($this->pdoUtils->dbName),
      $pdo->quote($type)
    );
    $tables = $pdo->query($query);
    $result = array();
    foreach ($tables as $table) {
      $result[] = $table['table_name'];
    }
    return $result;
  }

}
