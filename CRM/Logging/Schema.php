<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Logging_Schema {
  private $logs = array();
  private $tables = array();

  private $db;
  private $useDBPrefix = TRUE;

  private $reports = array(
    'logging/contact/detail',
    'logging/contact/summary',
    'logging/contribute/detail',
    'logging/contribute/summary',
  );

  //CRM-13028 / NYSS-6933 - table => array (cols) - to be excluded from the update statement
  private $exceptions = array(
    'civicrm_job' => array('last_run'),
    'civicrm_group' => array('cache_date', 'refresh_date'),
  );

  /**
   * Setting Callback - Validate.
   *
   * @param mixed $value
   * @param array $fieldSpec
   *
   * @return bool
   * @throws API_Exception
   */
  public static function checkLoggingSupport(&$value, $fieldSpec) {
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    if (!(CRM_Core_DAO::checkTriggerViewPermission(FALSE)) && $value) {
      throw new API_Exception("In order to use this functionality, the installation's database user must have privileges to create triggers (in MySQL 5.0 – and in MySQL 5.1 if binary logging is enabled – this means the SUPER privilege). This install either does not seem to have the required privilege enabled.");
    }
    return TRUE;
  }

  /**
   * Setting Callback - On Change.
   *
   * Respond to changes in the "logging" setting. Set up or destroy
   * triggers, etal.
   *
   * @param array $oldValue
   *   List of component names.
   * @param array $newValue
   *   List of component names.
   * @param array $metadata
   *   Specification of the setting (per *.settings.php).
   */
  public static function onToggle($oldValue, $newValue, $metadata) {
    if ($oldValue == $newValue) {
      return;
    }

    $logging = new CRM_Logging_Schema();
    if ($newValue) {
      $logging->enableLogging();
    }
    else {
      $logging->disableLogging();
    }
  }

  /**
   * Populate $this->tables and $this->logs with current db state.
   */
  public function __construct() {
    $dao = new CRM_Contact_DAO_Contact();
    $civiDBName = $dao->_database;

    $dao = CRM_Core_DAO::executeQuery("
SELECT TABLE_NAME
FROM   INFORMATION_SCHEMA.TABLES
WHERE  TABLE_SCHEMA = '{$civiDBName}'
AND    TABLE_TYPE = 'BASE TABLE'
AND    TABLE_NAME LIKE 'civicrm_%'
");
    while ($dao->fetch()) {
      $this->tables[] = $dao->TABLE_NAME;
    }

    // do not log temp import, cache, menu and log tables
    $this->tables = preg_grep('/^civicrm_import_job_/', $this->tables, PREG_GREP_INVERT);
    $this->tables = preg_grep('/_cache$/', $this->tables, PREG_GREP_INVERT);
    $this->tables = preg_grep('/_log/', $this->tables, PREG_GREP_INVERT);
    $this->tables = preg_grep('/^civicrm_queue_/', $this->tables, PREG_GREP_INVERT);
    //CRM-14672
    $this->tables = preg_grep('/^civicrm_menu/', $this->tables, PREG_GREP_INVERT);
    $this->tables = preg_grep('/_temp_/', $this->tables, PREG_GREP_INVERT);

    // do not log civicrm_mailing_event* tables, CRM-12300
    $this->tables = preg_grep('/^civicrm_mailing_event_/', $this->tables, PREG_GREP_INVERT);

    // do not log civicrm_mailing_recipients table, CRM-16193
    $this->tables = array_diff($this->tables, array('civicrm_mailing_recipients'));

    if (defined('CIVICRM_LOGGING_DSN')) {
      $dsn = DB::parseDSN(CIVICRM_LOGGING_DSN);
      $this->useDBPrefix = (CIVICRM_LOGGING_DSN != CIVICRM_DSN);
    }
    else {
      $dsn = DB::parseDSN(CIVICRM_DSN);
      $this->useDBPrefix = FALSE;
    }
    $this->db = $dsn['database'];

    $dao = CRM_Core_DAO::executeQuery("
SELECT TABLE_NAME
FROM   INFORMATION_SCHEMA.TABLES
WHERE  TABLE_SCHEMA = '{$this->db}'
AND    TABLE_TYPE = 'BASE TABLE'
AND    TABLE_NAME LIKE 'log_civicrm_%'
");
    while ($dao->fetch()) {
      $log = $dao->TABLE_NAME;
      $this->logs[substr($log, 4)] = $log;
    }
  }

  /**
   * Return logging custom data tables.
   */
  public function customDataLogTables() {
    return preg_grep('/^log_civicrm_value_/', $this->logs);
  }

  /**
   * Return custom data tables for specified entity / extends.
   *
   * @param string $extends
   *
   * @return array
   */
  public function entityCustomDataLogTables($extends) {
    $customGroupTables = array();
    $customGroupDAO = CRM_Core_BAO_CustomGroup::getAllCustomGroupsByBaseEntity($extends);
    $customGroupDAO->find();
    while ($customGroupDAO->fetch()) {
      $customGroupTables[$customGroupDAO->table_name] = $this->logs[$customGroupDAO->table_name];
    }
    return $customGroupTables;
  }

  /**
   * Disable logging by dropping the triggers (but keep the log tables intact).
   */
  public function disableLogging() {
    $config = CRM_Core_Config::singleton();
    $config->logging = FALSE;

    $this->dropTriggers();

    // invoke the meta trigger creation call
    CRM_Core_DAO::triggerRebuild();

    $this->deleteReports();
  }

  /**
   * Drop triggers for all logged tables.
   *
   * @param string $tableName
   */
  public function dropTriggers($tableName = NULL) {
    $dao = new CRM_Core_DAO();

    if ($tableName) {
      $tableNames = array($tableName);
    }
    else {
      $tableNames = $this->tables;
    }

    foreach ($tableNames as $table) {
      $validName = CRM_Core_DAO::shortenSQLName($table, 48, TRUE);

      // before triggers
      $dao->executeQuery("DROP TRIGGER IF EXISTS {$validName}_before_insert");
      $dao->executeQuery("DROP TRIGGER IF EXISTS {$validName}_before_update");
      $dao->executeQuery("DROP TRIGGER IF EXISTS {$validName}_before_delete");

      // after triggers
      $dao->executeQuery("DROP TRIGGER IF EXISTS {$validName}_after_insert");
      $dao->executeQuery("DROP TRIGGER IF EXISTS {$validName}_after_update");
      $dao->executeQuery("DROP TRIGGER IF EXISTS {$validName}_after_delete");
    }

    // now lets also be safe and drop all triggers that start with
    // civicrm_ if we are dropping all triggers
    // we need to do this to capture all the leftover triggers since
    // we did the shortening trigger name for CRM-11794
    if ($tableName === NULL) {
      $triggers = $dao->executeQuery("SHOW TRIGGERS LIKE 'civicrm_%'");

      while ($triggers->fetch()) {
        // note that drop trigger has a weird syntax and hence we do not
        // send the trigger name as a string (i.e. its not quoted
        $dao->executeQuery("DROP TRIGGER IF EXISTS {$triggers->Trigger}");
      }
    }
  }

  /**
   * Enable site-wide logging.
   */
  public function enableLogging() {
    $this->fixSchemaDifferences(TRUE);
    $this->addReports();
  }

  /**
   * Sync log tables and rebuild triggers.
   *
   * @param bool $enableLogging : Ensure logging is enabled
   */
  public function fixSchemaDifferences($enableLogging = FALSE) {
    $config = CRM_Core_Config::singleton();
    if ($enableLogging) {
      $config->logging = TRUE;
    }
    if ($config->logging) {
      $this->fixSchemaDifferencesForALL();
    }
    // invoke the meta trigger creation call
    CRM_Core_DAO::triggerRebuild(NULL, TRUE);
  }

  /**
   * Add missing (potentially specified) log table columns for the given table.
   *
   * @param string $table
   *   name of the relevant table.
   * @param array $cols
   *   Mixed array of columns to add or null (to check for the missing columns).
   * @param bool $rebuildTrigger
   *   should we rebuild the triggers.
   *
   * @return bool
   */
  public function fixSchemaDifferencesFor($table, $cols = array(), $rebuildTrigger = FALSE) {
    if (empty($table)) {
      return FALSE;
    }
    if (empty($this->logs[$table])) {
      $this->createLogTableFor($table);
      return TRUE;
    }

    if (empty($cols)) {
      $cols = $this->columnsWithDiffSpecs($table, "log_$table");
    }

    // use the relevant lines from CREATE TABLE to add colums to the log table
    $create = $this->_getCreateQuery($table);
    foreach ((array('ADD', 'MODIFY')) as $alterType) {
      if (!empty($cols[$alterType])) {
        foreach ($cols[$alterType] as $col) {
          $line = $this->_getColumnQuery($col, $create);
          CRM_Core_DAO::executeQuery("ALTER TABLE `{$this->db}`.log_$table {$alterType} {$line}");
        }
      }
    }

    // for any obsolete columns (not null) we just make the column nullable.
    if (!empty($cols['OBSOLETE'])) {
      $create = $this->_getCreateQuery("`{$this->db}`.log_{$table}");
      foreach ($cols['OBSOLETE'] as $col) {
        $line = $this->_getColumnQuery($col, $create);
        // This is just going to make a not null column to nullable
        CRM_Core_DAO::executeQuery("ALTER TABLE `{$this->db}`.log_$table MODIFY {$line}");
      }
    }

    if ($rebuildTrigger) {
      // invoke the meta trigger creation call
      CRM_Core_DAO::triggerRebuild($table);
    }
    return TRUE;
  }

  /**
   * Get query table.
   *
   * @param string $table
   *
   * @return array
   */
  private function _getCreateQuery($table) {
    $dao = CRM_Core_DAO::executeQuery("SHOW CREATE TABLE {$table}");
    $dao->fetch();
    $create = explode("\n", $dao->Create_Table);
    return $create;
  }

  /**
   * Get column query.
   *
   * @param string $col
   * @param bool $createQuery
   *
   * @return array|mixed|string
   */
  private function _getColumnQuery($col, $createQuery) {
    $line = preg_grep("/^  `$col` /", $createQuery);
    $line = rtrim(array_pop($line), ',');
    // CRM-11179
    $line = $this->fixTimeStampAndNotNullSQL($line);
    return $line;
  }

  /**
   * Fix schema differences.
   *
   * @param bool $rebuildTrigger
   */
  public function fixSchemaDifferencesForAll($rebuildTrigger = FALSE) {
    $diffs = array();
    foreach ($this->tables as $table) {
      if (empty($this->logs[$table])) {
        $this->createLogTableFor($table);
      }
      else {
        $diffs[$table] = $this->columnsWithDiffSpecs($table, "log_$table");
      }
    }

    foreach ($diffs as $table => $cols) {
      $this->fixSchemaDifferencesFor($table, $cols, FALSE);
    }

    if ($rebuildTrigger) {
      // invoke the meta trigger creation call
      CRM_Core_DAO::triggerRebuild($table);
    }
  }

  /**
   * Fix timestamp.
   *
   * Log_civicrm_contact.modified_date for example would always be copied from civicrm_contact.modified_date,
   * so there's no need for a default timestamp and therefore we remove such default timestamps
   * also eliminate the NOT NULL constraint, since we always copy and schema can change down the road)
   *
   * @param string $query
   *
   * @return mixed
   */
  public function fixTimeStampAndNotNullSQL($query) {
    $query = str_ireplace("TIMESTAMP NOT NULL", "TIMESTAMP NULL", $query);
    $query = str_ireplace("DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", '', $query);
    $query = str_ireplace("DEFAULT CURRENT_TIMESTAMP", '', $query);
    $query = str_ireplace("NOT NULL", '', $query);
    return $query;
  }

  /**
   * Add reports.
   */
  private function addReports() {
    $titles = array(
      'logging/contact/detail' => ts('Logging Details'),
      'logging/contact/summary' => ts('Contact Logging Report (Summary)'),
      'logging/contribute/detail' => ts('Contribution Logging Report (Detail)'),
      'logging/contribute/summary' => ts('Contribution Logging Report (Summary)'),
    );
    // enable logging templates
    CRM_Core_DAO::executeQuery("
            UPDATE civicrm_option_value
            SET is_active = 1
            WHERE value IN ('" . implode("', '", $this->reports) . "')
        ");

    // add report instances
    $domain_id = CRM_Core_Config::domainID();
    foreach ($this->reports as $report) {
      $dao = new CRM_Report_DAO_ReportInstance();
      $dao->domain_id = $domain_id;
      $dao->report_id = $report;
      $dao->title = $titles[$report];
      $dao->permission = 'administer CiviCRM';
      if ($report == 'logging/contact/summary') {
        $dao->is_reserved = 1;
      }
      $dao->insert();
    }
  }

  /**
   * Get an array of column names of the given table.
   *
   * @param string $table
   * @param bool $force
   *
   * @return array
   */
  private function columnsOf($table, $force = FALSE) {
    static $columnsOf = array();

    $from = (substr($table, 0, 4) == 'log_') ? "`{$this->db}`.$table" : $table;

    if (!isset($columnsOf[$table]) || $force) {
      $errorScope = CRM_Core_TemporaryErrorScope::ignoreException();
      $dao = CRM_Core_DAO::executeQuery("SHOW COLUMNS FROM $from", CRM_Core_DAO::$_nullArray, TRUE, NULL, FALSE, FALSE);
      if (is_a($dao, 'DB_Error')) {
        return array();
      }
      $columnsOf[$table] = array();
      while ($dao->fetch()) {
        $columnsOf[$table][] = $dao->Field;
      }
    }

    return $columnsOf[$table];
  }

  /**
   * Get an array of columns and their details like DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT for the given table.
   *
   * @param string $table
   *
   * @return array
   */
  private function columnSpecsOf($table) {
    static $columnSpecs = array(), $civiDB = NULL;

    if (empty($columnSpecs)) {
      if (!$civiDB) {
        $dao = new CRM_Contact_DAO_Contact();
        $civiDB = $dao->_database;
      }
      CRM_Core_TemporaryErrorScope::ignoreException();
      // NOTE: W.r.t Performance using one query to find all details and storing in static array is much faster
      // than firing query for every given table.
      $query = "
SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_TYPE
FROM   INFORMATION_SCHEMA.COLUMNS
WHERE  table_schema IN ('{$this->db}', '{$civiDB}')";
      $dao = CRM_Core_DAO::executeQuery($query);
      if (is_a($dao, 'DB_Error')) {
        return array();
      }
      while ($dao->fetch()) {
        if (!array_key_exists($dao->TABLE_NAME, $columnSpecs)) {
          $columnSpecs[$dao->TABLE_NAME] = array();
        }
        $columnSpecs[$dao->TABLE_NAME][$dao->COLUMN_NAME] = array(
          'COLUMN_NAME' => $dao->COLUMN_NAME,
          'DATA_TYPE' => $dao->DATA_TYPE,
          'IS_NULLABLE' => $dao->IS_NULLABLE,
          'COLUMN_DEFAULT' => $dao->COLUMN_DEFAULT,
        );
        if (($first = strpos($dao->COLUMN_TYPE, '(')) != 0) {
          $columnSpecs[$dao->TABLE_NAME][$dao->COLUMN_NAME]['LENGTH'] = substr($dao->COLUMN_TYPE, $first, strpos($dao->COLUMN_TYPE, ')'));
        }
      }
    }
    return $columnSpecs[$table];
  }

  /**
   * Get columns that have changed.
   *
   * @param string $civiTable
   * @param string $logTable
   *
   * @return array
   */
  public function columnsWithDiffSpecs($civiTable, $logTable) {
    $civiTableSpecs = $this->columnSpecsOf($civiTable);
    $logTableSpecs = $this->columnSpecsOf($logTable);

    $diff = array('ADD' => array(), 'MODIFY' => array(), 'OBSOLETE' => array());

    // columns to be added
    $diff['ADD'] = array_diff(array_keys($civiTableSpecs), array_keys($logTableSpecs));

    // columns to be modified
    // NOTE: we consider only those columns for modifications where there is a spec change, and that the column definition
    // wasn't deliberately modified by fixTimeStampAndNotNullSQL() method.
    foreach ($civiTableSpecs as $col => $colSpecs) {
      if (!isset($logTableSpecs[$col]) || !is_array($logTableSpecs[$col])) {
        $logTableSpecs[$col] = array();
      }

      $specDiff = array_diff($civiTableSpecs[$col], $logTableSpecs[$col]);
      if (!empty($specDiff) && $col != 'id' && !array_key_exists($col, $diff['ADD'])) {
        // ignore 'id' column for any spec changes, to avoid any auto-increment mysql errors
        if ($civiTableSpecs[$col]['DATA_TYPE'] != CRM_Utils_Array::value('DATA_TYPE', $logTableSpecs[$col])
        // We won't alter the log if the length is decreased in case some of the existing data won't fit.
        || CRM_Utils_Array::value('LENGTH', $civiTableSpecs[$col]) > CRM_Utils_Array::value('LENGTH', $logTableSpecs[$col])
        ) {
          // if data-type is different, surely consider the column
          $diff['MODIFY'][] = $col;
        }
        elseif ($civiTableSpecs[$col]['IS_NULLABLE'] != CRM_Utils_Array::value('IS_NULLABLE', $logTableSpecs[$col]) &&
          $logTableSpecs[$col]['IS_NULLABLE'] == 'NO'
        ) {
          // if is-null property is different, and log table's column is NOT-NULL, surely consider the column
          $diff['MODIFY'][] = $col;
        }
        elseif ($civiTableSpecs[$col]['COLUMN_DEFAULT'] != CRM_Utils_Array::value('COLUMN_DEFAULT', $logTableSpecs[$col]) &&
          !strstr($civiTableSpecs[$col]['COLUMN_DEFAULT'], 'TIMESTAMP')
        ) {
          // if default property is different, and its not about a timestamp column, consider it
          $diff['MODIFY'][] = $col;
        }
      }
    }

    // columns to made obsolete by turning into not-null
    $oldCols = array_diff(array_keys($logTableSpecs), array_keys($civiTableSpecs));
    foreach ($oldCols as $col) {
      if (!in_array($col, array('log_date', 'log_conn_id', 'log_user_id', 'log_action')) &&
        $logTableSpecs[$col]['IS_NULLABLE'] == 'NO'
      ) {
        // if its a column present only in log table, not among those used by log tables for special purpose, and not-null
        $diff['OBSOLETE'][] = $col;
      }
    }

    return $diff;
  }

  /**
   * Create a log table with schema mirroring the given table’s structure and seeding it with the given table’s contents.
   *
   * @param string $table
   */
  private function createLogTableFor($table) {
    $dao = CRM_Core_DAO::executeQuery("SHOW CREATE TABLE $table", CRM_Core_DAO::$_nullArray, TRUE, NULL, FALSE, FALSE);
    $dao->fetch();
    $query = $dao->Create_Table;

    // rewrite the queries into CREATE TABLE queries for log tables:
    $cols = <<<COLS
            ,
            log_date    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            log_conn_id INTEGER,
            log_user_id INTEGER,
            log_action  ENUM('Initialization', 'Insert', 'Update', 'Delete')
COLS;

    // - prepend the name with log_
    // - drop AUTO_INCREMENT columns
    // - drop non-column rows of the query (keys, constraints, etc.)
    // - set the ENGINE to ARCHIVE
    // - add log-specific columns (at the end of the table)
    $query = preg_replace("/^CREATE TABLE `$table`/i", "CREATE TABLE `{$this->db}`.log_$table", $query);
    $query = preg_replace("/ AUTO_INCREMENT/i", '', $query);
    $query = preg_replace("/^  [^`].*$/m", '', $query);
    $query = preg_replace("/^\) ENGINE=[^ ]+ /im", ') ENGINE=ARCHIVE ', $query);

    // log_civicrm_contact.modified_date for example would always be copied from civicrm_contact.modified_date,
    // so there's no need for a default timestamp and therefore we remove such default timestamps
    // also eliminate the NOT NULL constraint, since we always copy and schema can change down the road)
    $query = self::fixTimeStampAndNotNullSQL($query);
    $query = preg_replace("/(,*\n*\) )ENGINE/m", "$cols\n) ENGINE", $query);

    CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray, TRUE, NULL, FALSE, FALSE);

    $columns = implode(', ', $this->columnsOf($table));
    CRM_Core_DAO::executeQuery("INSERT INTO `{$this->db}`.log_$table ($columns, log_conn_id, log_user_id, log_action) SELECT $columns, CONNECTION_ID(), @civicrm_user_id, 'Initialization' FROM {$table}", CRM_Core_DAO::$_nullArray, TRUE, NULL, FALSE, FALSE);

    $this->tables[] = $table;
    $this->logs[$table] = "log_$table";
  }

  /**
   * Delete reports.
   */
  private function deleteReports() {
    // disable logging templates
    CRM_Core_DAO::executeQuery("
            UPDATE civicrm_option_value
            SET is_active = 0
            WHERE value IN ('" . implode("', '", $this->reports) . "')
        ");

    // delete report instances
    $domain_id = CRM_Core_Config::domainID();
    foreach ($this->reports as $report) {
      $dao = new CRM_Report_DAO_ReportInstance();
      $dao->domain_id = $domain_id;
      $dao->report_id = $report;
      $dao->delete();
    }
  }

  /**
   * Predicate whether logging is enabled.
   */
  public function isEnabled() {
    $config = CRM_Core_Config::singleton();

    if ($config->logging) {
      return $this->tablesExist() and $this->triggersExist();
    }
    return FALSE;
  }

  /**
   * Predicate whether any log tables exist.
   */
  private function tablesExist() {
    return !empty($this->logs);
  }

  /**
   * Predicate whether the logging triggers are in place.
   */
  private function triggersExist() {
    // FIXME: probably should be a bit more thorough…
    // note that the LIKE parameter is TABLE NAME
    return (bool) CRM_Core_DAO::singleValueQuery("SHOW TRIGGERS LIKE 'civicrm_contact'");
  }

  /**
   * Get trigger info.
   *
   * @param array $info
   * @param null $tableName
   * @param bool $force
   */
  public function triggerInfo(&$info, $tableName = NULL, $force = FALSE) {
    // check if we have logging enabled
    $config =& CRM_Core_Config::singleton();
    if (!$config->logging) {
      return;
    }

    $insert = array('INSERT');
    $update = array('UPDATE');
    $delete = array('DELETE');

    if ($tableName) {
      $tableNames = array($tableName);
    }
    else {
      $tableNames = $this->tables;
    }

    // logging is enabled, so now lets create the trigger info tables
    foreach ($tableNames as $table) {
      $columns = $this->columnsOf($table, $force);

      // only do the change if any data has changed
      $cond = array();
      foreach ($columns as $column) {
        // ignore modified_date changes
        if ($column != 'modified_date' && !in_array($column, CRM_Utils_Array::value($table, $this->exceptions, array()))) {
          $cond[] = "IFNULL(OLD.$column,'') <> IFNULL(NEW.$column,'')";
        }
      }
      $suppressLoggingCond = "@civicrm_disable_logging IS NULL OR @civicrm_disable_logging = 0";
      $updateSQL = "IF ( (" . implode(' OR ', $cond) . ") AND ( $suppressLoggingCond ) ) THEN ";

      if ($this->useDBPrefix) {
        $sqlStmt = "INSERT INTO `{$this->db}`.log_{tableName} (";
      }
      else {
        $sqlStmt = "INSERT INTO log_{tableName} (";
      }
      foreach ($columns as $column) {
        $sqlStmt .= "$column, ";
      }
      $sqlStmt .= "log_conn_id, log_user_id, log_action) VALUES (";

      $insertSQL = $deleteSQL = "IF ( $suppressLoggingCond ) THEN $sqlStmt ";
      $updateSQL .= $sqlStmt;

      $sqlStmt = '';
      foreach ($columns as $column) {
        $sqlStmt .= "NEW.$column, ";
        $deleteSQL .= "OLD.$column, ";
      }
      $sqlStmt .= "CONNECTION_ID(), @civicrm_user_id, '{eventName}');";
      $deleteSQL .= "CONNECTION_ID(), @civicrm_user_id, '{eventName}');";

      $sqlStmt .= "END IF;";
      $deleteSQL .= "END IF;";

      $insertSQL .= $sqlStmt;
      $updateSQL .= $sqlStmt;

      $info[] = array(
        'table' => array($table),
        'when' => 'AFTER',
        'event' => $insert,
        'sql' => $insertSQL,
      );

      $info[] = array(
        'table' => array($table),
        'when' => 'AFTER',
        'event' => $update,
        'sql' => $updateSQL,
      );

      $info[] = array(
        'table' => array($table),
        'when' => 'AFTER',
        'event' => $delete,
        'sql' => $deleteSQL,
      );
    }
  }

  /**
   * Disable logging temporarily.
   *
   * This allow logging to be temporarily disabled for certain cases
   * where we want to do a mass cleanup but dont want to bother with
   * an audit trail
   */
  public static function disableLoggingForThisConnection() {
    // do this only if logging is enabled
    $config = CRM_Core_Config::singleton();
    if ($config->logging) {
      CRM_Core_DAO::executeQuery('SET @civicrm_disable_logging = 1');
    }
  }

}
