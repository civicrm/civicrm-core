<?php

/**
 * Class CRM_Logging_SchmeaTest
 * @group headless
 */
class CRM_Logging_SchemaTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Clean up after test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown() {
    $schema = new CRM_Logging_Schema();
    $schema->disableLogging();
    parent::tearDown();
    $this->quickCleanup(['civicrm_contact'], TRUE);
    $schema->dropAllLogTables();
    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS civicrm_test_table");
    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS civicrm_test_column_info");
    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS civicrm_test_length_change");
    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS civicrm_test_enum_change");
  }

  /**
   * Data provider for testing query re-writing.
   *
   * @return array
   */
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

  /**
   * Test log tables are created as InnoDB by default
   */
  public function testLogEngine() {
    $schema = new CRM_Logging_Schema();
    $schema->enableLogging();
    $log_table = CRM_Core_DAO::executeQuery("SHOW CREATE TABLE log_civicrm_acl");
    while ($log_table->fetch()) {
      $this->assertRegexp('/ENGINE=InnoDB/', $log_table->Create_Table);
    }
  }

  /**
   * Test that the log table engine can be changed via hook to e.g. MyISAM
   */
  public function testHookLogEngine() {
    $this->hookClass->setHook('civicrm_alterLogTables', [$this, 'alterLogTables']);
    $schema = new CRM_Logging_Schema();
    $schema->enableLogging();
    $log_table = CRM_Core_DAO::executeQuery("SHOW CREATE TABLE log_civicrm_acl");
    while ($log_table->fetch()) {
      $this->assertRegexp('/ENGINE=MyISAM/', $log_table->Create_Table);
    }
  }

  /**
   * Tests that choosing to ignore a custom table does not result in e-notices.
   */
  public function testIgnoreCustomTableByHook() {
    $group = $this->customGroupCreate();
    Civi::settings()->set('logging', TRUE);
    $this->hookClass->setHook('civicrm_alterLogTables', [$this, 'noCustomTables']);
    $this->customFieldCreate(['custom_group_id' => $group['id']]);
  }

  /**
   * Remove all custom tables from tables to be logged.
   *
   * @param array $logTableSpec
   */
  public function noCustomTables(&$logTableSpec) {
    foreach (array_keys($logTableSpec) as $index) {
      if (substr($index, 0, 14) === 'civicrm_value_') {
        unset($logTableSpec[$index]);
      }
    }
  }

  /**
   * Test that existing log tables with ARCHIVE engine are converted to InnoDB
   *
   * @throws \Exception
   */
  public function testArchiveEngineConversion() {
    $schema = new CRM_Logging_Schema();
    $schema->enableLogging();
    // change table to ARCHIVE
    CRM_Core_DAO::executeQuery("ALTER TABLE log_civicrm_acl ENGINE ARCHIVE");
    $log_table = CRM_Core_DAO::executeQuery("SHOW CREATE TABLE log_civicrm_acl");
    while ($log_table->fetch()) {
      $this->assertRegexp('/ENGINE=ARCHIVE/', $log_table->Create_Table);
    }
    // engine should not change by default
    $schema->updateLogTableSchema(['updateChangedEngineConfig' => FALSE, 'forceEngineMigration' => FALSE]);
    $log_table = CRM_Core_DAO::executeQuery("SHOW CREATE TABLE log_civicrm_acl");
    while ($log_table->fetch()) {
      $this->assertRegExp('/ENGINE=ARCHIVE/', $log_table->Create_Table);
    }
    // update with forceEngineMigration should convert to InnoDB
    $schema->updateLogTableSchema(['updateChangedEngineConfig' => FALSE, 'forceEngineMigration' => TRUE]);
    $log_table = CRM_Core_DAO::executeQuery("SHOW CREATE TABLE log_civicrm_acl");
    while ($log_table->fetch()) {
      $this->assertRegExp('/ENGINE=InnoDB/', $log_table->Create_Table);
    }
  }

  /**
   * Alter the engine on the log tables.
   *
   * @param $logTableSpec
   */
  public function alterLogTables(&$logTableSpec) {
    foreach (array_keys($logTableSpec) as $tableName) {
      $logTableSpec[$tableName]['engine'] = 'MyISAM';
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
    \Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
    // Check that it still picks up new columns added.
    $diffs = $schema->columnsWithDiffSpecs("civicrm_test_table", "log_civicrm_test_table");
    $this->assertTrue(!empty($diffs['ADD']));
    $this->assertTrue(empty($diffs['MODIFY']));
    $this->assertTrue(empty($diffs['OBSOLETE']));
    $schema->fixSchemaDifferences();
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_test_table CHANGE COLUMN test_varchar test_varchar varchar(400) DEFAULT NULL");
    // Check that it properly picks up modifications to columns.
    \Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
    $diffs = $schema->columnsWithDiffSpecs("civicrm_test_table", "log_civicrm_test_table");
    $this->assertTrue(!empty($diffs['MODIFY']));
    $this->assertTrue(empty($diffs['ADD']));
    $this->assertTrue(empty($diffs['OBSOLETE']));
    $schema->fixSchemaDifferences();
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_test_table CHANGE COLUMN test_varchar test_varchar varchar(300) DEFAULT NULL");
    // Check that when we reduce the size of column that the log table doesn't shrink as well.
    \Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
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

  public function testColumnInfo() {
    CRM_Core_DAO::executeQuery("CREATE TABLE `civicrm_test_column_info` (
      test_id  int(10) unsigned NOT NULL AUTO_INCREMENT,
      test_varchar varchar(42) NOT NULL,
      test_integer int(8) NULL,
      test_decimal decimal(20,2),
      test_enum enum('A','B','C'),
      test_integer_default int(8) DEFAULT 42,
      test_date date DEFAULT NULL,
      PRIMARY KEY (`test_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
    $schema = new CRM_Logging_Schema();
    $schema->enableLogging();
    $schema->updateLogTableSchema(['updateChangedEngineConfig' => FALSE, 'forceEngineMigration' => FALSE]);
    $ci = \Civi::$statics['CRM_Logging_Schema']['columnSpecs']['civicrm_test_column_info'];

    $this->assertEquals('test_id', $ci['test_id']['COLUMN_NAME']);
    $this->assertEquals('int', $ci['test_id']['DATA_TYPE']);
    $this->assertEquals('NO', $ci['test_id']['IS_NULLABLE']);
    $this->assertEquals('auto_increment', $ci['test_id']['EXTRA']);
    $this->assertEquals('10', $ci['test_id']['LENGTH']);

    $this->assertEquals('varchar', $ci['test_varchar']['DATA_TYPE']);
    $this->assertEquals('42', $ci['test_varchar']['LENGTH']);

    $this->assertEquals('int', $ci['test_integer']['DATA_TYPE']);
    $this->assertEquals('8', $ci['test_integer']['LENGTH']);
    $this->assertEquals('YES', $ci['test_integer']['IS_NULLABLE']);

    $this->assertEquals('decimal', $ci['test_decimal']['DATA_TYPE']);
    $this->assertEquals('20,2', $ci['test_decimal']['LENGTH']);

    $this->assertEquals('enum', $ci['test_enum']['DATA_TYPE']);
    $this->assertEquals("'A','B','C'", $ci['test_enum']['ENUM_VALUES']);
    $this->assertArrayNotHasKey('LENGTH', $ci['test_enum']);

    $this->assertEquals('42', $ci['test_integer_default']['COLUMN_DEFAULT']);

    $this->assertEquals('date', $ci['test_date']['DATA_TYPE']);
  }

  public function testIndexes() {
    $schema = new CRM_Logging_Schema();
    $indexes = $schema->getIndexesForTable('civicrm_contact');
    $this->assertContains('PRIMARY', $indexes);
    $this->assertContains('UI_external_identifier', $indexes);
    $this->assertContains('FK_civicrm_contact_employer_id', $indexes);
    $this->assertContains('index_sort_name', $indexes);
  }

  public function testLengthChange() {
    CRM_Core_DAO::executeQuery("CREATE TABLE `civicrm_test_length_change` (
      test_id int(10) unsigned NOT NULL AUTO_INCREMENT,
      test_integer int(4) NULL,
      test_decimal decimal(20,2) NULL,
      PRIMARY KEY (`test_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
    $schema = new CRM_Logging_Schema();
    $schema->enableLogging();
    CRM_Core_DAO::executeQuery(
      "ALTER TABLE civicrm_test_length_change
      CHANGE COLUMN test_integer test_integer int(6) NULL,
      CHANGE COLUMN test_decimal test_decimal decimal(22,2) NULL"
    );
    \Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
    $schema->fixSchemaDifferences();
    // need to do it twice so the columnSpecs static is refreshed
    \Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
    $schema->fixSchemaDifferences();
    $ci = \Civi::$statics['CRM_Logging_Schema']['columnSpecs'];
    // length should increase
    $this->assertEquals(6, $ci['log_civicrm_test_length_change']['test_integer']['LENGTH']);
    $this->assertEquals('22,2', $ci['log_civicrm_test_length_change']['test_decimal']['LENGTH']);
    CRM_Core_DAO::executeQuery(
      "ALTER TABLE civicrm_test_length_change
      CHANGE COLUMN test_integer test_integer int(4) NULL,
      CHANGE COLUMN test_decimal test_decimal decimal(20,2) NULL"
    );
    \Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
    $schema->fixSchemaDifferences();
    \Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
    $schema->fixSchemaDifferences();
    $ci = \Civi::$statics['CRM_Logging_Schema']['columnSpecs'];
    // length should not decrease
    $this->assertEquals(6, $ci['log_civicrm_test_length_change']['test_integer']['LENGTH']);
    $this->assertEquals('22,2', $ci['log_civicrm_test_length_change']['test_decimal']['LENGTH']);
  }

  public function testEnumChange() {
    CRM_Core_DAO::executeQuery("CREATE TABLE `civicrm_test_enum_change` (
      test_id int(10) unsigned NOT NULL AUTO_INCREMENT,
      test_enum enum('A','B','C') NULL,
      PRIMARY KEY (`test_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
    $schema = new CRM_Logging_Schema();
    $schema->enableLogging();
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_test_enum_change CHANGE COLUMN test_enum test_enum enum('A','B','C','D') NULL");
    \Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
    $schema->fixSchemaDifferences();
    // need to do it twice so the columnSpecs static is refreshed
    \Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
    $schema->fixSchemaDifferences();
    $ci = \Civi::$statics['CRM_Logging_Schema']['columnSpecs'];
    // new enum value should be included
    $this->assertEquals("'A','B','C','D'", $ci['civicrm_test_enum_change']['test_enum']['ENUM_VALUES']);
  }

}
