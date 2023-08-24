<?php

/**
 * Class CRM_Upgrade_SnapshotTest
 * @group headless
 */
class CRM_Upgrade_SnapshotTest extends CiviUnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    CRM_Upgrade_Snapshot::$pageSize = 10;
    CRM_Upgrade_Snapshot::$cleanupAfter = 4;
  }

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testTableNamesGood(): void {
    $this->assertEquals('snap_civicrm_v5_45_stuff', CRM_Upgrade_Snapshot::createTableName('civicrm', '5.45', 'stuff'));
    $this->assertEquals('snap_civicrm_v5_50_stuffy_things', CRM_Upgrade_Snapshot::createTableName('civicrm', '5.50', 'stuffy_things'));
    $this->assertEquals('snap_oauth_client_v12_34_ext_things', CRM_Upgrade_Snapshot::createTableName('oauth_client', '12.34', 'ext_things'));
    $this->assertEquals('snap_oauth_client_v0_1234_ext_things', CRM_Upgrade_Snapshot::createTableName('oauth_client', '0.1234', 'ext_things'));
  }

  public function testTableNamesBad(): void {
    try {
      CRM_Upgrade_Snapshot::createTableName('civicrm', '5.45', 'ab&cd');
      $this->fail('Accepted invalid name');
    }
    catch (CRM_Core_Exception $e) {
      $this->assertMatchesRegularExpression('/Malformed snapshot name/', $e->getMessage());
    }
    try {
      CRM_Upgrade_Snapshot::createTableName('civicrm', '5.45', 'long_table_name_that_is_too_long_for_the_validation');
      $this->fail('Accepted excessive name');
    }
    catch (CRM_Core_Exception $e) {
      $this->assertMatchesRegularExpression('/Snapshot name is too long/', $e->getMessage());
    }
  }

  /**
   * This example creates a snapshot based on particular sliver of data (ie
   * the "display_name" and "sort_name" for "Individual" records). It ensures
   * that:
   *
   * 1. Some columns are copied - while other columns are not (based on
   * `select()`).
   * 2. Some rows are copied - while other rows are not (based on `where()`).
   * 3. Multiple pages of data are copied.
   *
   * @throws \CRM_Core_Exception
   */
  public function testContent(): void {
    for ($i = 0; $i < 15; $i++) {
      $this->individualCreate([], $i);
      $this->organizationCreate([], $i);
    }

    $this->runAll(CRM_Upgrade_Snapshot::createTasks('civicrm', '5.45', 'names', CRM_Utils_SQL_Select::from('civicrm_contact')
      ->select('id, display_name, sort_name, modified_date')
      ->where('contact_type = "Individual"')
    ));
    $this->assertTrue(CRM_Core_DAO::checkTableExists('snap_civicrm_v5_45_names'));
    $this->assertSameSchema('civicrm_contact.display_name', 'snap_civicrm_v5_45_names.display_name');
    $this->assertSameSchema('civicrm_contact.sort_name', 'snap_civicrm_v5_45_names.sort_name');
    $this->assertSameSchema('civicrm_contact.modified_date', 'snap_civicrm_v5_45_names.modified_date');
    $this->assertTrue(CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_contact', 'legal_name'));
    $this->assertFalse(CRM_Core_BAO_SchemaHandler::checkIfFieldExists('snap_civicrm_v5_45_names', 'legal_name'));

    $liveContacts = CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_contact');
    $liveIndividuals = CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_contact WHERE contact_type = "Individual"');
    $snapCount = CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM snap_civicrm_v5_45_names');
    $this->assertEquals($liveIndividuals, $snapCount, 'The snapshot should have as many records as live table.');
    $this->assertTrue($liveContacts > $liveIndividuals);
    $this->assertGreaterThan(CRM_Upgrade_Snapshot::$pageSize, $snapCount, "There should be more than 1 page of data in the snapshot. Found $snapCount records.");

    CRM_Core_DAO::executeQuery('DROP TABLE snap_civicrm_v5_45_names');
  }

  /**
   * This example creates multiple snapshots (attributed to different versions,
   * v5.45 and v5.50), and it ensures that they can be cleaned-up by future
   * upgrades (eg v5.52 and v5.58).
   *
   * @throws \CRM_Core_Exception
   */
  public function testBasicLifecycle(): void {
    for ($i = 0; $i < 15; $i++) {
      $this->individualCreate([], $i);
      $this->organizationCreate([], $i);
    }
    $this->eventCreateUnpaid([]);
    $this->eventCreateUnpaid([], 'second');

    $this->runAll(CRM_Upgrade_Snapshot::createTasks('civicrm', '5.45', 'names', CRM_Utils_SQL_Select::from('civicrm_contact')
      ->select('id, display_name, sort_name')
      ->where('contact_type = "Individual"')
    ));
    $this->assertTrue(CRM_Core_DAO::checkTableExists('snap_civicrm_v5_45_names'));
    $this->assertSameSchema('civicrm_contact.display_name', 'snap_civicrm_v5_45_names.display_name');
    $this->assertGreaterThan(1, CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM snap_civicrm_v5_45_names'));

    $this->runAll(CRM_Upgrade_Snapshot::createTasks('civicrm', '5.50', 'dates', CRM_Utils_SQL_Select::from('civicrm_event')
      ->select('id, start_date, end_date, registration_start_date, registration_end_date')
    ));
    $this->assertTrue(CRM_Core_DAO::checkTableExists('snap_civicrm_v5_50_dates'));
    $this->assertSameSchema('civicrm_event.start_date', 'snap_civicrm_v5_50_dates.start_date');
    $this->assertGreaterThan(1, CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM snap_civicrm_v5_50_dates'));

    CRM_Upgrade_Snapshot::cleanupTask(NULL, 'civicrm', '5.52', 6);
    $this->assertFalse(CRM_Core_DAO::checkTableExists('snap_civicrm_v5_45_names'));
    $this->assertTrue(CRM_Core_DAO::checkTableExists('snap_civicrm_v5_50_dates'));

    CRM_Upgrade_Snapshot::cleanupTask(NULL, 'civicrm', '5.58', 6);
    $this->assertFalse(CRM_Core_DAO::checkTableExists('snap_civicrm_v5_45_names'));
    $this->assertFalse(CRM_Core_DAO::checkTableExists('snap_civicrm_v5_50_dates'));
  }

  /**
   * Assert that two columns have the same schema.
   *
   * @param string $expectField
   *   ex: "table_1.column_1"
   * @param string $actualField
   *   ex: "table_2.column_2"
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  protected function assertSameSchema(string $expectField, string $actualField): void {
    [$expectTable, $expectColumn] = explode('.', $expectField);
    [$actualTable, $actualColumn] = explode('.', $actualField);

    $expectDao = CRM_Core_DAO::executeQuery("SHOW COLUMNS FROM {$expectTable} LIKE %1", [
      1 => [$expectColumn, 'String'],
    ]);
    $expectDao->fetch();

    $actualDao = CRM_Core_DAO::executeQuery("SHOW COLUMNS FROM {$actualTable} LIKE %1", [
      1 => [$actualColumn, 'String'],
    ]);
    $actualDao->fetch();

    foreach (['Type', 'Null'] as $fieldProp) {
      $this->assertEquals($expectDao->{$fieldProp}, $actualDao->{$fieldProp}, "The fields $expectField and $actualField should have the same schema.");
    }
  }

  protected function runAll(iterable $tasks): void {
    $queue = Civi::queue('snap-test', ['type' => 'Memory']);
    foreach ($tasks as $task) {
      $queue->createItem($task);
    }
    $r = new CRM_Queue_Runner([
      'queue' => $queue,
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
    ]);
    $r->runAll();
  }

}
