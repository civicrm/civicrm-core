<?php

/**
 * Class CRM_Logging_SchmeaTest
 * @group headless
 */
class CRM_Logging_SchemaTest extends CiviUnitTestCase {

  protected $databaseVersion;

  public function setUp(): void {
    $this->databaseVersion = CRM_Utils_SQL::getDatabaseVersion();
    parent::setUp();
  }

  /**
   * Clean up after test.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function tearDown(): void {
    $schema = new CRM_Logging_Schema();
    $schema->dropAllLogTables();
    Civi::settings()->set('logging', FALSE);
    $this->databaseVersion = NULL;
    parent::tearDown();
    $this->quickCleanup(['civicrm_contact'], TRUE);
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS civicrm_test_table');
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS civicrm_test_column_info');
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS civicrm_test_length_change');
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS civicrm_test_enum_change');
  }

  /**
   * Data provider for testing query re-writing.
   *
   * @return array
   */
  public function queryExamples(): array {
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
  public function testLogEngine(): void {
    Civi::settings()->set('logging', TRUE);
    $log_table = CRM_Core_DAO::executeQuery('SHOW CREATE TABLE log_civicrm_acl');
    while ($log_table->fetch()) {
      $this->assertRegexp('/ENGINE=InnoDB/', $log_table->Create_Table);
    }
  }

  /**
   * Test that the log table engine can be changed via hook to e.g. MyISAM
   */
  public function testHookLogEngine(): void {
    $this->hookClass->setHook('civicrm_alterLogTables', [$this, 'alterLogTables']);
    Civi::settings()->set('logging', TRUE);
    $log_table = CRM_Core_DAO::executeQuery('SHOW CREATE TABLE log_civicrm_acl');
    while ($log_table->fetch()) {
      $this->assertRegexp('/ENGINE=MyISAM/', $log_table->Create_Table);
    }
  }

  /**
   * Tests that choosing to ignore a custom table does not result in e-notices.
   */
  public function testIgnoreCustomTableByHook(): void {
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
  public function noCustomTables(&$logTableSpec): void {
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
  public function testArchiveEngineConversion(): void {
    Civi::settings()->set('logging', TRUE);
    $schema = new CRM_Logging_Schema();
    // change table to ARCHIVE
    CRM_Core_DAO::executeQuery('ALTER TABLE log_civicrm_acl ENGINE ARCHIVE');
    $log_table = CRM_Core_DAO::executeQuery('SHOW CREATE TABLE log_civicrm_acl');
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
      $this->assertStringContainsString('UPDATE civicrm_contact SET modified_date = CURRENT_TIMESTAMP WHERE id = NEW.entity_id;', $log_table->Statement, "Contact modification update should be in the trigger :\n" . $log_table->Statement);
      $this->assertStringNotContainsString('civicrm_mailing', $log_table->Statement, 'Contact field should not update mailing table');
      $this->assertEquals(1, substr_count($log_table->Statement, 'SET modified_date'), 'Modified date should only be updated on one table (here it is contact)');
    }
  }

  /**
   * Test that autoincrement keys are handled sensibly in logging table reconciliation.
   */
  public function testAutoIncrementNonIdColumn() {
    CRM_Core_DAO::executeQuery("CREATE TABLE `civicrm_test_table` (
      test_id  int(10) unsigned NOT NULL AUTO_INCREMENT,
      PRIMARY KEY (`test_id`)
    )  ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

    Civi::settings()->set('logging', TRUE);
    $schema = new CRM_Logging_Schema();
    $diffs = $schema->columnsWithDiffSpecs("civicrm_test_table", "log_civicrm_test_table");
    // Test that just having a non id named column with Auto Increment doesn't create diffs
    $this->assertTrue(empty($diffs['MODIFY']));
    $this->assertTrue(empty($diffs['ADD']));
    $this->assertTrue(empty($diffs['OBSOLETE']));

    // Check we can add a primary key to the log table and it will not be treated as obsolete.
    CRM_Core_DAO::executeQuery('
      ALTER TABLE log_civicrm_test_table ADD COLUMN `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      ADD PRIMARY KEY (`log_id`)
   ');
    Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
    $diffs = $schema->columnsWithDiffSpecs('civicrm_test_table', "log_civicrm_test_table");
    $this->assertEmpty($diffs['OBSOLETE']);

    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_test_table ADD COLUMN test_varchar varchar(255) DEFAULT NULL");
    Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
    // Check that it still picks up new columns added.
    $diffs = $schema->columnsWithDiffSpecs("civicrm_test_table", "log_civicrm_test_table");
    $this->assertTrue(!empty($diffs['ADD']));
    $this->assertTrue(empty($diffs['MODIFY']));
    $this->assertTrue(empty($diffs['OBSOLETE']));
    unset(Civi::$statics['CRM_Logging_Schema']);
    $schema->fixSchemaDifferences();
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_test_table CHANGE COLUMN test_varchar test_varchar varchar(400) DEFAULT NULL');
    // Check that it properly picks up modifications to columns.
    unset(Civi::$statics['CRM_Logging_Schema']);
    $diffs = $schema->columnsWithDiffSpecs("civicrm_test_table", "log_civicrm_test_table");
    $this->assertNotEmpty($diffs['MODIFY']);
    $this->assertEmpty($diffs['ADD']);
    $this->assertEmpty($diffs['OBSOLETE']);
    $schema->fixSchemaDifferences();
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_test_table CHANGE COLUMN test_varchar test_varchar varchar(300) DEFAULT NULL');
    // Check that when we reduce the size of column that the log table doesn't shrink as well.
    Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
    $diffs = $schema->columnsWithDiffSpecs('civicrm_test_table', "log_civicrm_test_table");
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
    Civi::settings()->set('logging', TRUE);
    $schema->triggerInfo($info, 'civicrm_group');
    // should have 3 triggers (insert/update/delete)
    $this->assertCount(3, $info);
    foreach ($info as $trigger) {
      // table for trigger should be civicrm_group
      $this->assertEquals('civicrm_group', $trigger['table'][0]);
      if ($trigger['event'][0] == 'UPDATE') {
        // civicrm_group.cache_date should be an exception, i.e. not logged
        $this->assertStringNotContainsString(
          "IFNULL(OLD.`cache_date`,'') <> IFNULL(NEW.`cache_date`,'')",
          $trigger['sql']
        );
      }
    }
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testColumnInfo(): void {
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
    Civi::settings()->set('logging', TRUE);

    $schema = new CRM_Logging_Schema();
    $schema->updateLogTableSchema(['updateChangedEngineConfig' => FALSE, 'forceEngineMigration' => FALSE]);
    $ci = Civi::$statics['CRM_Logging_Schema']['columnSpecs']['civicrm_test_column_info'];

    $this->assertEquals('test_id', $ci['test_id']['COLUMN_NAME']);
    $this->assertEquals('int', $ci['test_id']['DATA_TYPE']);
    $this->assertEquals('NO', $ci['test_id']['IS_NULLABLE']);
    $this->assertEquals('auto_increment', $ci['test_id']['EXTRA']);
    if (!$this->isMySQL8()) {
      $this->assertEquals('10', $ci['test_id']['LENGTH']);
    }

    $this->assertEquals('varchar', $ci['test_varchar']['DATA_TYPE']);
    $this->assertEquals('42', $ci['test_varchar']['LENGTH']);

    $this->assertEquals('int', $ci['test_integer']['DATA_TYPE']);
    if (!$this->isMySQL8()) {
      $this->assertEquals('8', $ci['test_integer']['LENGTH']);
    }
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
    Civi::settings()->set('logging', TRUE);
    $schema = new CRM_Logging_Schema();
    CRM_Core_DAO::executeQuery(
      "ALTER TABLE civicrm_test_length_change
      CHANGE COLUMN test_integer test_integer int(6) NULL,
      CHANGE COLUMN test_decimal test_decimal decimal(22,2) NULL"
    );
    $schema->fixSchemaDifferences();
    $ci = Civi::$statics['CRM_Logging_Schema']['columnSpecs'];
    // length should increase
    if (!$this->isMySQL8()) {
      $this->assertEquals(6, $ci['log_civicrm_test_length_change']['test_integer']['LENGTH']);
    }
    $this->assertEquals('22,2', $ci['log_civicrm_test_length_change']['test_decimal']['LENGTH']);
    CRM_Core_DAO::executeQuery(
      "ALTER TABLE civicrm_test_length_change
      CHANGE COLUMN test_integer test_integer int(4) NULL,
      CHANGE COLUMN test_decimal test_decimal decimal(20,2) NULL"
    );
    $schema->fixSchemaDifferences();
    $ci = Civi::$statics['CRM_Logging_Schema']['columnSpecs'];
    // length should not decrease
    if (!$this->isMySQL8()) {
      $this->assertEquals(6, $ci['log_civicrm_test_length_change']['test_integer']['LENGTH']);
    }
    $this->assertEquals('22,2', $ci['log_civicrm_test_length_change']['test_decimal']['LENGTH']);
  }

  /**
   * Test changing the enum.
   */
  public function testEnumChange(): void {
    CRM_Core_DAO::executeQuery("CREATE TABLE `civicrm_test_enum_change` (
      test_id int(10) unsigned NOT NULL AUTO_INCREMENT,
      test_enum enum('A','B','C') NULL,
      PRIMARY KEY (`test_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
    Civi::settings()->set('logging', TRUE);
    $schema = new CRM_Logging_Schema();
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_test_enum_change CHANGE COLUMN test_enum test_enum enum('A','B','C','D') NULL");
    Civi::$statics['CRM_Logging_Schema']['columnSpecs'] = [];
    $schema->fixSchemaDifferences();
    $ci = Civi::$statics['CRM_Logging_Schema']['columnSpecs'];
    // new enum value should be included
    $this->assertEquals("'A','B','C','D'", $ci['civicrm_test_enum_change']['test_enum']['ENUM_VALUES']);
  }

  /**
   * Test editing a custom field
   *
   * @throws \CRM_Core_Exception
   */
  public function testCustomFieldEdit(): void {
    Civi::settings()->set('logging', TRUE);
    $customGroup = $this->entityCustomGroupWithSingleFieldCreate('Contact', 'ContactTest.php');

    // get the custom group table name
    $params = ['id' => $customGroup['custom_group_id']];
    $custom_group = $this->callAPISuccess('custom_group', 'getsingle', $params);

    // get the field db column name
    $params = ['id' => $customGroup['custom_field_id']];
    $custom_field = $this->callAPISuccess('custom_field', 'getsingle', $params);

    // check it
    $dao = CRM_Core_DAO::executeQuery("SHOW CREATE TABLE `log_{$custom_group['table_name']}`");
    $dao->fetch();
    $this->assertStringContainsString("`{$custom_field['column_name']}` varchar(255)", $dao->Create_Table);

    // Edit the field
    $params = [
      'id' => $customGroup['custom_field_id'],
      'label' => 'Label changed',
      'text_length' => 768,
    ];
    $this->callAPISuccess('custom_field', 'create', $params);

    // update logging schema
    $schema = new CRM_Logging_Schema();
    $schema->fixSchemaDifferences();

    // verify
    $dao = CRM_Core_DAO::executeQuery("SHOW CREATE TABLE `log_{$custom_group['table_name']}`");
    $dao->fetch();
    $this->assertStringContainsString("`{$custom_field['column_name']}` varchar(768)", $dao->Create_Table);
  }

  /**
   * Test creating a table with SchemaHandler::createTable when logging
   * is enabled.
   */
  public function testCreateTableWithLogging(): void {
    Civi::settings()->set('logging', TRUE);

    CRM_Core_BAO_SchemaHandler::createTable([
      'name' => 'civicrm_test_table',
      'is_multiple' => FALSE,
      'attributes' => 'ENGINE=InnoDB',
      'fields' => [
        [
          'name' => 'id',
          'type' => 'int unsigned',
          'primary' => TRUE,
          'required' => TRUE,
          'attributes' => 'AUTO_INCREMENT',
          'comment' => 'Default MySQL primary key',
        ],
        [
          'name' => 'activity_id',
          'type' => 'int unsigned',
          'required' => TRUE,
          'comment' => 'FK to civicrm_activity',
          'fk_table_name' => 'civicrm_activity',
          'fk_field_name' => 'id',
          'fk_attributes' => 'ON DELETE CASCADE',
        ],
        [
          'name' => 'texty',
          'type' => 'varchar(255)',
          'required' => FALSE,
        ],
      ],
    ]);
    $dao = CRM_Core_DAO::executeQuery("SHOW CREATE TABLE civicrm_test_table");
    $dao->fetch();
    // using regex since not sure it's always int(10), so accept int(10), int(11), integer, etc...
    $this->assertRegExp('/`id` int(.*) unsigned NOT NULL AUTO_INCREMENT/', $dao->Create_Table);
    $this->assertRegExp('/`activity_id` int(.*) unsigned NOT NULL/', $dao->Create_Table);
    $this->assertStringContainsString('`texty` varchar(255)', $dao->Create_Table);
    $this->assertStringContainsString('ENGINE=InnoDB', $dao->Create_Table);
    $this->assertStringContainsString('FOREIGN KEY (`activity_id`) REFERENCES `civicrm_activity` (`id`) ON DELETE CASCADE', $dao->Create_Table);

    // Check log table.
    $dao = CRM_Core_DAO::executeQuery('SHOW CREATE TABLE log_civicrm_test_table');
    $dao->fetch();
    $this->assertStringNotContainsString('AUTO_INCREMENT', $dao->Create_Table);
    // This seems debatable whether `id` should lose its NOT NULL status
    $this->assertRegExp('/`id` int(.*) unsigned DEFAULT NULL/', $dao->Create_Table);
    $this->assertRegExp('/`activity_id` int(.*) unsigned DEFAULT NULL/', $dao->Create_Table);
    $this->assertStringContainsString('`texty` varchar(255)', $dao->Create_Table);
    $this->assertStringContainsString('ENGINE=InnoDB', $dao->Create_Table);
    $this->assertStringNotContainsString('FOREIGN KEY', $dao->Create_Table);
  }

  /**
   * Determine if we are running on MySQL 8 version 8.0.19 or later.
   *
   * @return bool
   */
  protected function isMySQL8(): bool {
    return (bool) (version_compare($this->databaseVersion, '8.0.19', '>=') && stripos($this->databaseVersion, 'mariadb') === FALSE);
  }

}
