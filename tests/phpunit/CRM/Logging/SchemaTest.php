<?php

/**
 * Class CRM_Logging_SchmeaTest
 * @group headless
 */
class CRM_Logging_SchemaTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
    $schema = new CRM_Logging_Schema();
    $schema->disableLogging();
    $schema->dropAllLogTables();
    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS civicrm_test_table");
  }

  public function queryExamples() {
    $examples = [];
    $examples[] = ["`modified_date` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'When the mailing (or closely related entity) was created or modified or deleted.'", "`modified_date` timestamp NULL  COMMENT 'When the mailing (or closely related entity) was created or modified or deleted.'"];
    $examples[] = ["`modified_date` timestamp NULL DEFAULT current_timestamp ON UPDATE current_timestamp COMMENT 'When the mailing (or closely related entity) was created or modified or deleted.'", "`modified_date` timestamp NULL  COMMENT 'When the mailing (or closely related entity) was created or modified or deleted.'"];
    return $examples;
  }

  /**
   * Tests the function fixTimeStampAndNotNullSQL in CRM_Logging_Schema
   *
   * @dataProvider queryExamples
   */
  public function testQueryRewrite($query, $expectedQuery) {
    $this->assertEquals($expectedQuery, CRM_Logging_Schema::fixTimeStampAndNotNullSQL($query));
  }

  public function testLogEngine() {
    $schema = new CRM_Logging_Schema();
    $schema->enableLogging();
    $log_table = CRM_Core_DAO::executeQuery("SHOW CREATE TABLE log_civicrm_acl");
    while ($log_table->fetch()) {
      $this->assertRegexp('/ENGINE=ARCHIVE/', $log_table->Create_Table);
    }
  }

  public function testAutoIncrementNonIdColumn() {
    CRM_Core_DAO::executeQuery("CREATE TABLE `civicrm_test_table` (
      test_id  int(10) unsigned NOT NULL AUTO_INCREMENT,
      PRIMARY KEY (`test_id`)
    )  ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
    $schema = new CRM_Logging_Schema();
    $schema->enableLogging();
    $diffs = $schema->columnsWithDiffSpecs("civicrm_test_table", "log_civicrm_test_table");
    // Test that just havving a non id nanmed column with Auto Increment doesn't create diffs
    $this->assertTrue(empty($diffs['MODIFY']));
    $this->assertTrue(empty($diffs['ADD']));
    $this->assertTrue(empty($diffs['OBSOLETE']));
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_test_table ADD COLUMN test_varchar varchar(255) DEFAULT NULL");
    \Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = array();
    // Check that it still picks up new columns added.
    $diffs = $schema->columnsWithDiffSpecs("civicrm_test_table", "log_civicrm_test_table");
    $this->assertTrue(!empty($diffs['ADD']));
    $this->assertTrue(empty($diffs['MODIFY']));
    $this->assertTrue(empty($diffs['OBSOLETE']));
    $schema->fixSchemaDifferences();
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_test_table CHANGE COLUMN test_varchar test_varchar varchar(400) DEFAULT NULL");
    // Check that it properly picks up modifications to columns.
    \Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = array();
    $diffs = $schema->columnsWithDiffSpecs("civicrm_test_table", "log_civicrm_test_table");
    $this->assertTrue(!empty($diffs['MODIFY']));
    $this->assertTrue(empty($diffs['ADD']));
    $this->assertTrue(empty($diffs['OBSOLETE']));
    $schema->fixSchemaDifferences();
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_test_table CHANGE COLUMN test_varchar test_varchar varchar(300) DEFAULT NULL");
    // Check that when we reduce the size of column that the log table doesn't shrink as well.
    \Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = array();
    $diffs = $schema->columnsWithDiffSpecs("civicrm_test_table", "log_civicrm_test_table");
    $this->assertTrue(empty($diffs['MODIFY']));
    $this->assertTrue(empty($diffs['ADD']));
    $this->assertTrue(empty($diffs['OBSOLETE']));
  }

}
