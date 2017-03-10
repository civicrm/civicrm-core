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
    parent::tearDown();
  }

  /**
   * Test creating logging schema when database is in multilingual mode.
   */
  public function testMultilingualLogging() {
    CRM_Core_I18n_Schema::makeMultilingual('en_US');
    $logging = new CRM_Logging_Schema();
    $logging->enableLogging();
    $value = CRM_Core_DAO::singleValueQuery("SELECT id FROM log_civicrm_contact LIMIT 1", array(), FALSE, FALSE);
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
    $value = CRM_Core_DAO::singleValueQuery("SELECT id FROM log_civicrm_contact LIMIT 1", array(), FALSE, FALSE);
    $this->assertNotNull($value, 'Logging not enabled successfully');
    $logging->disableLogging();
    CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_option_value` ADD COLUMN `logging_test` INT DEFAULT NULL", array(), FALSE, NULL, FALSE, TRUE);
    CRM_Core_I18n_Schema::rebuildMultilingualSchema(array('en_US'));
    $logging->enableLogging();
    $query = CRM_Core_DAO::executeQuery("SHOW CREATE TABLE `log_civicrm_option_value`", array(), TRUE, NULL, FALSE, FALSE);
    $query->fetch();
    $create = explode("\n", $query->Create_Table);
    CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_option_value` DROP COLUMN `logging_test`", array(), FALSE, NULL, FALSE, TRUE);
    $this->assertTrue(in_array("  `logging_test` int(11) DEFAULT NULL", $create));
    $logging->disableLogging();
  }

}
