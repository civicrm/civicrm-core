<?php

/**
 * Class CRM_Core_DAOTest
 * @group headless
 * @group locale
 */
class CRM_Logging_LoggingTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  public function tearDown(): void {
    Civi::settings()->set('logging', FALSE);
    global $dbLocale;
    if ($dbLocale) {
      $this->disableMultilingual();
    }
    $logging = new CRM_Logging_Schema();
    $logging->dropAllLogTables();
    $this->cleanupCustomGroups();
    parent::tearDown();
  }

  /**
   * Check that log tables are created even for non standard custom fields
   * tables.
   *
   * @throws \CRM_Core_Exception
   */
  public function testLoggingNonStandardCustomTableName(): void {
    $this->createCustomGroupWithFieldOfType(['table_name' => 'abcd']);
    Civi::settings()->set('logging', TRUE);
    $this->assertNotEmpty(CRM_Core_DAO::singleValueQuery("SHOW tables LIKE 'log_abcd'"));
  }

  /**
   * Test that hooks removing tables from logging are respected during custom field add.
   *
   * During custom field save logging is only handled for the affected table.
   * We need to make sure this respects hooks to remove from the logging set.
   */
  public function testLoggingHookIgnore(): void {
    $this->hookClass->setHook('civicrm_alterLogTables', [$this, 'ignoreSillyName']);
    Civi::settings()->set('logging', TRUE);
    $this->createCustomGroupWithFieldOfType(['table_name' => 'silly_name']);
    $this->assertEmpty(CRM_Core_DAO::singleValueQuery("SHOW tables LIKE 'log_silly_name'"));
  }

  /**
   * Implement hook to cause our log table to be ignored.
   *
   * @param array $logTableSpec
   */
  public function ignoreSillyName(array &$logTableSpec): void {
    unset($logTableSpec['silly_name']);
  }

  /**
   * Test creating logging schema when database is in multilingual mode.
   */
  public function testMultilingualLogging(): void {
    $this->enableMultilingual();
    Civi::settings()->set('logging', TRUE);
    $value = CRM_Core_DAO::singleValueQuery('SELECT id FROM log_civicrm_contact LIMIT 1', [], FALSE, FALSE);
    $this->assertNotNull($value, 'Logging not enabled successfully');
  }

  /**
   * Test creating logging schema when database is in multilingual mode.
   * Also test altering a multilingual table.
   */
  public function testMultilingualAlterSchemaLogging(): void {
    $this->enableMultilingual();
    Civi::settings()->set('logging', TRUE);
    $logging = new CRM_Logging_Schema();
    $value = CRM_Core_DAO::singleValueQuery('SELECT id FROM log_civicrm_contact LIMIT 1', [], FALSE, FALSE);
    $this->assertNotNull($value, 'Logging not enabled successfully');
    CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_option_value` ADD COLUMN `logging_test` INT DEFAULT '0'", [], FALSE, NULL, FALSE, FALSE);
    CRM_Core_I18n_Schema::rebuildMultilingualSchema(['en_US']);
    Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
    $logging->fixSchemaDifferencesFor('civicrm_option_value');
    Civi::service('sql_triggers')->rebuild('civicrm_option_value');
    $query = CRM_Core_DAO::executeQuery('SHOW CREATE TABLE `log_civicrm_option_value`', [], TRUE, NULL, FALSE, FALSE);
    $query->fetch();
    $create = explode("\n", $query->Create_Table);
    // MySQL may return "DEFAULT 0" or "DEFAULT '0'" depending on version
    $this->assertTrue(
      in_array("  `logging_test` int(11) DEFAULT '0'", $create, TRUE)
      || in_array('  `logging_test` int(11) DEFAULT 0', $create, TRUE)
      || in_array("  `logging_test` int DEFAULT '0'", $create, TRUE)
      || in_array('  `logging_test` int DEFAULT 0', $create, TRUE)
    );
    CRM_Core_DAO::executeQuery('ALTER TABLE `civicrm_option_value` DROP COLUMN `logging_test`', [], FALSE, NULL, FALSE, FALSE);
    $query = CRM_Core_DAO::executeQuery('SHOW CREATE TABLE `log_civicrm_option_value`', [], TRUE, NULL, FALSE, FALSE);
    $query->fetch();
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
    Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
    CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales);
    $logging->fixSchemaDifferencesFor('civicrm_option_value');
    Civi::service('sql_triggers')->rebuild('civicrm_option_value');
    $this->assertTrue(
      in_array("  `logging_test` int(11) DEFAULT '0'", $create, TRUE)
      || in_array('  `logging_test` int(11) DEFAULT 0', $create, TRUE)
      || in_array("  `logging_test` int DEFAULT '0'", $create, TRUE)
      || in_array('  `logging_test` int DEFAULT 0', $create, TRUE)
    );
  }

  public function testDiffsInTableWithAllTables(): void {
    Civi::settings()->set('logging', TRUE);

    // create a contact and update it
    $cid = $this->individualCreate();
    CRM_Core_DAO::executeQuery("UPDATE civicrm_contact SET first_name = 'Crash' WHERE id=%1", [1 => [$cid, 'Integer']]);

    // Now delete the older log records so we just have our update record
    CRM_Core_DAO::executeQuery("DELETE FROM log_civicrm_contact WHERE first_name <> 'Crash' AND id=%1", [1 => [$cid, 'Integer']]);

    // get the conn_id for the update log record to give to the differ
    $log_conn_id = CRM_Core_DAO::singleValueQuery("SELECT log_conn_id FROM log_civicrm_contact WHERE log_action='Update' AND first_name='Crash' AND id=%1", [1 => [$cid, 'Integer']]);

    // Pretend there was a 2 second delay between the create and update since
    // otherwise the differ will include unrelated junk from other tables
    // from the create.
    $log_date = date('YmdHis', strtotime('+2 seconds'));
    CRM_Core_DAO::executeQuery("UPDATE log_civicrm_contact SET log_date=%2 WHERE log_action='Update' AND first_name='Crash' AND id=%1", [
      1 => [$cid, 'Integer'],
      2 => [$log_date, 'Timestamp'],
    ]);

    // now getting the diffs shouldn't crash even though all that's there is the update log record.
    $schema = new CRM_Logging_Schema();
    $tables = $schema->getLogTablesForContact();
    $differ = new CRM_Logging_Differ($log_conn_id, $log_date, '1 SECOND');
    $diffs = [];
    foreach ($tables as $table) {
      $diffs = array_merge($diffs, $differ->diffsInTable($table, $cid));
    }
    $this->assertEmpty($diffs);
  }

}
