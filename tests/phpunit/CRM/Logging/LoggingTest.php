<?php

/**
 * Class CRM_Core_DAOTest
 * @group headless
 */
class CRM_Logging_LoggingTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  /**
   * Has the db been set to multilingual.
   *
   * @var bool
   */
  protected $isDBMultilingual = FALSE;

  public function tearDown(): void {
    Civi::settings()->set('logging', FALSE);
    if ($this->isDBMultilingual) {
      CRM_Core_I18n_Schema::makeSinglelingual('en_US');
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
   * @throws \API_Exception
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
    $this->makeMultilingual();
    Civi::settings()->set('logging', TRUE);
    $value = CRM_Core_DAO::singleValueQuery('SELECT id FROM log_civicrm_contact LIMIT 1', [], FALSE, FALSE);
    $this->assertNotNull($value, 'Logging not enabled successfully');
  }

  /**
   * Test creating logging schema when database is in multilingual mode.
   * Also test altering a multilingual table.
   */
  public function testMultilingualAlterSchemaLogging(): void {
    $this->makeMultilingual();
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

  /**
   * Convert the database to multilingual mode.
   */
  protected function makeMultilingual(): void {
    CRM_Core_I18n_Schema::makeMultilingual('en_US');
    $this->isDBMultilingual = TRUE;
  }

}
