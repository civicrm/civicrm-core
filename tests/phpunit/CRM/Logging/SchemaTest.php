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

  /**
   * Test correct creation of modified date triggers.
   *
   * Specifically we are testing that the contact table modified date and
   * ONLY the contact table modified date is updated when the custom field is updated.
   *
   * (At point of writing this the modification was leaking to the mailing table).
   */
  public function testTriggers() {
    $customGroup = $this->entityCustomGroupWithSingleFieldCreate('Contact', 'ContactTest....');
    Civi::service('sql_triggers')->rebuild();
    $log_table = CRM_Core_DAO::executeQuery("SHOW TRIGGERS WHERE `Trigger` LIKE 'civicrm_value_contact_{$customGroup['custom_group_id']}_after_insert%'");

    while ($log_table->fetch()) {
      $this->assertContains('UPDATE civicrm_contact SET modified_date = CURRENT_TIMESTAMP WHERE id = NEW.entity_id;', $log_table->Statement, "Contact modification update should be in the trigger :\n" . $log_table->Statement);
      $this->assertNotContains('civicrm_mailing', $log_table->Statement, 'Contact field should not update mailing table');
      $this->assertEquals(1, substr_count($log_table->Statement, 'SET modified_date'), 'Modified date should only be updated on one table (here it is contact)');
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

  /**
   * Test logging trigger definition
   */
  public function testTriggerInfo() {
    $info = [];
    $schema = new CRM_Logging_Schema();
    $schema->enableLogging();
    $schema->triggerInfo($info, 'civicrm_group');
    // should have 3 triggers (insert/update/delete)
    $this->assertCount(3, $info);
    foreach ($info as $trigger) {
      // table for trigger should be civicrm_group
      $this->assertEquals('civicrm_group', $trigger['table'][0]);
      if ($trigger['event'][0] == 'UPDATE') {
        // civicrm_group.cache_date should be an exception, i.e. not logged
        $this->assertNotContains(
          "IFNULL(OLD.`cache_date`,'') <> IFNULL(NEW.`cache_date`,'')",
          $trigger['sql']
        );
      }
    }
  }

}
