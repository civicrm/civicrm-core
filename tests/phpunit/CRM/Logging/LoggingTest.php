<?php

/**
 * Class CRM_Core_DAOTest
 * @group headless
 */
class CRM_Logging_LoggingTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    CRM_Core_I18n_Schema::makeSinglelingual('en_US');
    $logging = new CRM_Logging_Schema();
    $logging->dropAllLogTables();
    \Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
    parent::tearDown();
  }

  /**
   * Test creating logging schema when database is in multilingual mode.
   */
  public function testMultilingualLogging() {
    CRM_Core_I18n_Schema::makeMultilingual('en_US');
    $logging = new CRM_Logging_Schema();
    $logging->enableLogging();
    $value = CRM_Core_DAO::singleValueQuery("SELECT id FROM log_civicrm_contact LIMIT 1", [], FALSE, FALSE);
    $this->assertNotNull($value, 'Logging not enabled successfully');
    $logging->disableLogging();
  }

  /**
   * Test creating logging schema when database is in multilingual mode.
   * Also test altering a multilingual table.
   */
  public function testMultilingualAlterSchemaLogging() {
    CRM_Core_I18n_Schema::makeMultilingual('en_US');
    $logging = new CRM_Logging_Schema();
    $logging->enableLogging();
    $value = CRM_Core_DAO::singleValueQuery("SELECT id FROM log_civicrm_contact LIMIT 1", [], FALSE, FALSE);
    $this->assertNotNull($value, 'Logging not enabled successfully');
    CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_option_value` ADD COLUMN `logging_test` INT DEFAULT '0'", [], FALSE, NULL, FALSE, FALSE);
    CRM_Core_I18n_Schema::rebuildMultilingualSchema(['en_US']);
    \Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
    $logging->fixSchemaDifferencesFor('civicrm_option_value');
    Civi::service('sql_triggers')->rebuild('civicrm_option_value');
    $query = CRM_Core_DAO::executeQuery("SHOW CREATE TABLE `log_civicrm_option_value`", [], TRUE, NULL, FALSE, FALSE);
    $query->fetch();
    $create = explode("\n", $query->Create_Table);
    // MySQL may return "DEFAULT 0" or "DEFAULT '0'" depending on version
    $this->assertTrue(
      in_array("  `logging_test` int(11) DEFAULT '0'", $create)
      || in_array("  `logging_test` int(11) DEFAULT 0", $create)
    );
    CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_option_value` DROP COLUMN `logging_test`", [], FALSE, NULL, FALSE, FALSE);
    $query = CRM_Core_DAO::executeQuery("SHOW CREATE TABLE `log_civicrm_option_value`", [], TRUE, NULL, FALSE, FALSE);
    $query->fetch();
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
    \Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
    CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales);
    $logging->fixSchemaDifferencesFor('civicrm_option_value');
    Civi::service('sql_triggers')->rebuild('civicrm_option_value');
    $this->assertTrue(
      in_array("  `logging_test` int(11) DEFAULT '0'", $create)
      || in_array("  `logging_test` int(11) DEFAULT 0", $create));
    $logging->disableLogging();
  }

}
