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

  // protected function tearDown(): void {
  //   // We just throw a lot of random stuff in the DB. Don't care about slow cleanup, since this test is a schema-heavy oddball with low# iterations.
  //   // \Civi\Test::headless()->apply(TRUE);
  //   parent::tearDown();
  // }

  /**
   * "php" requirement (composer.json) should match
   * CRM_Upgrade_Incremental_General::MIN_INSTALL_PHP_VER.
   */
  public function testBasicLifecycle(): void {
    for ($i = 0; $i < 15; $i++) {
      $this->individualCreate([], $i);
      $this->organizationCreate([], $i);
    }

    $this->runAll(CRM_Upgrade_Snapshot::createTasks('5.45', 'names', CRM_Utils_SQL_Select::from('civicrm_contact')
      ->select('id, display_name, sort_name')
      ->where('contact_type = "Individual"')
    ));
    $this->assertTrue(CRM_Core_DAO::checkTableExists('civicrm_snap_v5_45_names'));
    $this->assertSameSchema('civicrm_contact.display_name', 'civicrm_snap_v5_45_names.display_name');
    $this->assertSameSchema('civicrm_contact.sort_name', 'civicrm_snap_v5_45_names.sort_name');
    $this->assertTrue(CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_contact', 'legal_name'));
    $this->assertFalse(CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_snap_v5_45_names', 'legal_name'));

    $liveContacts = CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_contact');
    $liveIndividuals = CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_contact WHERE contact_type = "Individual"');
    $snapCount = CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_snap_v5_45_names');
    $this->assertEquals($liveIndividuals, $snapCount, 'The snapshot should have as many records as live table.');
    $this->assertTrue($liveContacts > $liveIndividuals);
    $this->assertGreaterThan(CRM_Upgrade_Snapshot::$pageSize, $snapCount, "There should be more than 1 page of data in the snapshot. Found $snapCount records.");

    $this->runAll(CRM_Upgrade_Snapshot::createTasks('5.50', 'dates', CRM_Utils_SQL_Select::from('civicrm_event')
      ->select('id, start_date, end_date, registration_start_date, registration_end_date')
    ));
    $this->assertTrue(CRM_Core_DAO::checkTableExists('civicrm_snap_v5_50_dates'));
    $this->assertSameSchema('civicrm_event.start_date', 'civicrm_snap_v5_50_dates.start_date');
    $this->assertSameSchema('civicrm_event.registration_end_date', 'civicrm_snap_v5_50_dates.registration_end_date');

    CRM_Upgrade_Snapshot::cleanupTask(NULL, '5.52', 6);
    $this->assertFalse(CRM_Core_DAO::checkTableExists('civicrm_snap_v5_45_names'));
    $this->assertTrue(CRM_Core_DAO::checkTableExists('civicrm_snap_v5_50_dates'));

    CRM_Upgrade_Snapshot::cleanupTask(NULL, '5.58', 6);
    $this->assertFalse(CRM_Core_DAO::checkTableExists('civicrm_snap_v5_45_names'));
    $this->assertFalse(CRM_Core_DAO::checkTableExists('civicrm_snap_v5_50_dates'));
  }

  /**
   * Assert that two columns have the same schema.
   *
   * @param string $expectField
   *   ex: "table_1.column_1"
   * @param string $actualField
   *   ex: "table_2.column_2"
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
    $queue = Civi::queue('snaptest', ['type' => 'Memory']);
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
