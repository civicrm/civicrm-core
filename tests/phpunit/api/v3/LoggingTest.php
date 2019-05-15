<?php
/*
 +--------------------------------------------------------------------+
| CiviCRM version 5                                                  |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2019                                |
+--------------------------------------------------------------------+
| This file is a part of CiviCRM.                                    |
|                                                                    |
| CiviCRM is free software; you can copy, modify, and distribute it  |
| under the terms of the GNU Affero General Public License           |
| Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
|                                                                    |
| CiviCRM is distributed in the hope that it will be useful, but     |
| WITHOUT ANY WARRANTY; without even the implied warranty of         |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
| See the GNU Affero General Public License for more details.        |
|                                                                    |
| You should have received a copy of the GNU Affero General Public   |
| License and the CiviCRM Licensing Exception along                  |
| with this program; if not, contact CiviCRM LLC                     |
| at info[AT]civicrm[DOT]org. If you have questions about the        |
| GNU Affero General Public License or the licensing of CiviCRM,     |
| see the CiviCRM license FAQ at http://civicrm.org/licensing        |
+--------------------------------------------------------------------+
 */

/**
 * Test class for Logging API.
 *
 * @package CiviCRM
 * @group headless
 */
class api_v3_LoggingTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    $this->ensureTempColIsCleanedUp();
    parent::setUp();
  }

  /**
   * Clean up log tables.
   */
  protected function tearDown() {
    $this->quickCleanup(array('civicrm_email', 'civicrm_address'));
    parent::tearDown();
    $this->callAPISuccess('Setting', 'create', array('logging' => FALSE));
    $schema = new CRM_Logging_Schema();
    $schema->dropAllLogTables();
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_setting WHERE name LIKE 'logg%'");
  }

  /**
   * Test that logging is successfully enabled and disabled.
   */
  public function testEnableDisableLogging() {
    $this->assertEquals(0, $this->callAPISuccessGetValue('Setting', array('name' => 'logging')));
    $this->assertLoggingEnabled(FALSE);

    $this->callAPISuccess('Setting', 'create', array('logging' => TRUE));
    $this->assertLoggingEnabled(TRUE);
    $this->checkLogTableCreated();
    $this->checkTriggersCreated(TRUE);
    // Create a contact to make sure they aren't borked.
    $this->individualCreate();
    $this->assertTrue($this->callAPISuccessGetValue('Setting', array('name' => 'logging')));
    $this->assertEquals(1, $this->callAPISuccessGetValue('Setting', array('name' => 'logging_all_tables_uniquid')));
    $this->assertEquals(
      date('Y-m-d'),
      date('Y-m-d', strtotime($this->callAPISuccessGetValue('Setting', array('name' => 'logging_uniqueid_date'))))
    );

    $this->callAPISuccess('Setting', 'create', array('logging' => FALSE));
    $this->assertEquals(0, $this->callAPISuccessGetValue('Setting', array('name' => 'logging')));
    $this->assertLoggingEnabled(FALSE);
  }

  /**
   * Test that logging is successfully enabled and disabled.
   */
  public function testEnableDisableLoggingWithTriggerHook() {
    $this->hookClass->setHook('civicrm_alterLogTables', array($this, 'innodbLogTableSpec'));
    $this->callAPISuccess('Setting', 'create', array('logging' => TRUE));
    $this->checkINNODBLogTableCreated();
    $this->checkTriggersCreated(TRUE);
    // Create a contact to make sure they aren't borked.
    $this->individualCreate();
    $this->callAPISuccess('Setting', 'create', array('logging' => FALSE));
  }

  /**
   * Check responsible creation when old structure log table exists.
   *
   * When an existing table exists NEW tables will have the varchar type for log_conn_id.
   *
   * Existing tables will be unchanged, and the trigger will use log_conn_id
   * rather than uniqueId to be consistent across the tables.
   *
   * The settings for unique id will not be set.
   */
  public function testEnableLoggingLegacyLogTableExists() {
    $this->createLegacyStyleContactLogTable();
    $this->callAPISuccess('Setting', 'create', array('logging' => TRUE));
    $this->checkTriggersCreated(FALSE);
    $this->assertEquals(0, $this->callAPISuccessGetValue('Setting', array('name' => 'logging_all_tables_uniquid')));
    $this->assertEmpty($this->callAPISuccessGetValue('Setting', array('name' => 'logging_uniqueid_date')));
  }

  /**
   * Check we can update legacy log tables using the api function.
   */
  public function testUpdateLegacyLogTable() {
    $this->createLegacyStyleContactLogTable();
    $this->callAPISuccess('Setting', 'create', array('logging' => TRUE));
    $this->callAPISuccess('System', 'updatelogtables', array());
    $this->checkLogTableCreated();
    $this->checkTriggersCreated(TRUE);
    $this->assertEquals(0, $this->callAPISuccessGetValue('Setting', array('name' => 'logging_all_tables_uniquid')));
    $this->assertEquals(
      date('Y-m-d'),
      date('Y-m-d', strtotime($this->callAPISuccessGetValue('Setting', array('name' => 'logging_uniqueid_date'))))
    );
  }

  /**
   * Check if we can create missing log tables using api.
   */
  public function testCreateMissingLogTables() {
    $this->callAPISuccess('Setting', 'create', array('logging' => TRUE));
    CRM_Core_DAO::executeQuery("DROP TABLE log_civicrm_contact");
    $this->callAPISuccess('System', 'createmissinglogtables', array());

    //Assert if log_civicrm_contact is created.
    $this->checkLogTableCreated();
  }

  /**
   * Check we can update legacy log tables using the api function.
   */
  public function testUpdateLogTableHookINNODB() {
    $this->createLegacyStyleContactLogTable();
    $this->callAPISuccess('Setting', 'create', array('logging' => TRUE));
    $this->hookClass->setHook('civicrm_alterLogTables', array($this, 'innodbLogTableSpec'));
    $this->callAPISuccess('System', 'updatelogtables', array());
    $this->checkINNODBLogTableCreated();
    $this->checkTriggersCreated(TRUE);
    // Make sure that the absence of a hook specifying INNODB does not cause revert to archive.
    // Only a positive action, like specifying ARCHIVE in a hook should trigger a change back to archive.
    $this->hookClass->setHook('civicrm_alterLogTables', array());
    $schema = new CRM_Logging_Schema();
    $spec = $schema->getLogTableSpec();
    $this->assertEquals(array(), $spec['civicrm_contact']);
    $this->callAPISuccess('System', 'updatelogtables', array());
    $this->checkINNODBLogTableCreated();
    // Check if API creates new indexes when they're added by hook
    $this->hookClass->setHook('civicrm_alterLogTables', [$this, 'innodbLogTableSpecNewIndex']);
    $this->callAPISuccess('System', 'updatelogtables', array());
    $this->checkINNODBLogTableCreated();
    $this->assertContains('KEY `index_log_user_id` (`log_user_id`)', $this->checkLogTableCreated());
  }

  /**
   * Check that if a field is added then the trigger is updated on refresh.
   */
  public function testRebuildTriggerAfterSchemaChange() {
    $this->callAPISuccess('Setting', 'create', array('logging' => TRUE));
    $tables = array('civicrm_acl', 'civicrm_website');
    foreach ($tables as $table) {
      CRM_Core_DAO::executeQuery("ALTER TABLE $table ADD column temp_col INT(10)");
    }

    $schema = new CRM_Logging_Schema();
    $schema->fixSchemaDifferencesForAll(TRUE);

    foreach ($tables as $table) {
      $this->assertTrue($this->checkColumnExistsInTable('log_' . $table, 'temp_col'), 'log_' . $table . ' has temp_col');
      $dao = CRM_Core_DAO::executeQuery("SHOW TRIGGERS LIKE '{$table}'");
      while ($dao->fetch()) {
        $this->assertContains('temp_col', $dao->Statement);
      }
    }
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_acl DROP column temp_col");
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_website DROP column temp_col");
  }

  /**
   * Use a hook to declare an INNODB engine for the contact log table.
   *
   * @param array $logTableSpec
   */
  public function innodbLogTableSpec(&$logTableSpec) {
    $logTableSpec['civicrm_contact'] = array(
      'engine' => 'InnoDB',
      'engine_config' => 'ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=4',
      'indexes' => array(
        'index_id' => 'id',
        'index_log_conn_id' => 'log_conn_id',
        'index_log_date' => 'log_date',
      ),
    );
  }

  /**
   * Set log engine to InnoDB and add one index
   *
   * @param array $logTableSpec
   */
  public function innodbLogTableSpecNewIndex(&$logTableSpec) {
    $logTableSpec['civicrm_contact'] = array(
      'engine' => 'InnoDB',
      'engine_config' => 'ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=4',
      'indexes' => array(
        'index_id' => 'id',
        'index_log_conn_id' => 'log_conn_id',
        'index_log_date' => 'log_date',
        // new index
        'index_log_user_id' => 'log_user_id',
      ),
    );
  }

  /**
   * Check the log tables were created and look OK.
   */
  protected function checkLogTableCreated() {
    $dao = CRM_Core_DAO::executeQuery("SHOW CREATE TABLE log_civicrm_contact");
    $dao->fetch();
    $this->assertEquals('log_civicrm_contact', $dao->Table);
    $tableField = 'Create_Table';
    $this->assertContains('`log_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,', $dao->$tableField);
    $this->assertContains('`log_conn_id` varchar(17)', $dao->$tableField);
    return $dao->$tableField;
  }

  /**
   * Check the log tables were created and reflect the INNODB hook.
   */
  protected function checkINNODBLogTableCreated() {
    $createTableString = $this->checkLogTableCreated();
    $this->assertContains('ENGINE=InnoDB', $createTableString);
    $this->assertContains('ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=4', $createTableString);
    $this->assertContains('KEY `index_id` (`id`),', $createTableString);
  }

  /**
   * Check the triggers were created and look OK.
   *
   * @param bool $unique
   *   Is the site configured for unique logging connection IDs per CRM-18193?
   */
  protected function checkTriggersCreated($unique) {
    $dao = CRM_Core_DAO::executeQuery("SHOW TRIGGERS LIKE 'civicrm_contact'");
    while ($dao->fetch()) {
      if ($dao->Timing == 'After') {
        if ($unique) {
          $this->assertContains('@uniqueID', $dao->Statement);
        }
        else {
          $this->assertContains('CONNECTION_ID()', $dao->Statement);
        }
      }
    }
  }

  /**
   * Assert logging is enabled or disabled as per input parameter.
   *
   * @param bool $expected
   *   Do we expect it to be enabled.
   */
  protected function assertLoggingEnabled($expected) {
    $schema = new CRM_Logging_Schema();
    $this->assertTrue($schema->isEnabled() === $expected);
  }

  /**
   * Create the contact log table with log_conn_id as an integer.
   */
  protected function createLegacyStyleContactLogTable() {
    CRM_Core_DAO::executeQuery("
      CREATE TABLE log_civicrm_contact
      (log_conn_id INT NULL, log_user_id INT NULL, log_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP)
      ENGINE=ARCHIVE
      (SELECT c.*, CURRENT_TIMESTAMP as log_date, 'Initialize' as 'log_action'
      FROM civicrm_contact c)
    ");
  }

  /**
   * Test changes can be reverted.
   */
  public function testRevert() {
    $contactId = $this->individualCreate();
    $this->callAPISuccess('Setting', 'create', array('logging' => TRUE));
    // Pause for one second here to ensure the timestamps between the first create action
    // and the second differ.
    sleep(1);
    CRM_Core_DAO::executeQuery("SET @uniqueID = 'woot'");
    $timeStamp = date('Y-m-d H:i:s');
    $this->callAPISuccess('Contact', 'create', array(
      'id' => $contactId,
      'first_name' => 'Dopey',
      'api.email.create' => array('email' => 'dopey@mail.com'),
    )
    );
    $email = $this->callAPISuccessGetSingle('email', array('email' => 'dopey@mail.com'));
    $this->callAPIAndDocument('Logging', 'revert', array('log_conn_id' => 'woot', 'log_date' => $timeStamp), __FILE__, 'Revert');
    $this->assertEquals('Anthony', $this->callAPISuccessGetValue('contact', array('id' => $contactId, 'return' => 'first_name')));
    $this->callAPISuccessGetCount('Email', array('id' => $email['id']), 0);
  }

  /**
   * Test changes can be reverted.
   */
  public function testRevertNoDate() {
    $contactId = $this->individualCreate();
    $this->callAPISuccess('Setting', 'create', array('logging' => TRUE));
    // Pause for one second here to ensure the timestamps between the first create action
    // and the second differ.
    sleep(1);
    CRM_Core_DAO::executeQuery("SET @uniqueID = 'Wot woot'");
    $this->callAPISuccess('Contact', 'create', array(
      'id' => $contactId,
      'first_name' => 'Dopey',
      'api.email.create' => array('email' => 'dopey@mail.com'),
    ));
    $email = $this->callAPISuccessGetSingle('email', array('email' => 'dopey@mail.com'));
    $this->callAPISuccess('Logging', 'revert', array('log_conn_id' => 'Wot woot'));
    $this->assertEquals('Anthony', $this->callAPISuccessGetValue('contact', array('id' => $contactId, 'return' => 'first_name')));
    $this->callAPISuccessGetCount('Email', array('id' => $email['id']), 0);
  }

  /**
   * Ensure that a limited list of tables can be reverted.
   *
   * In this case ONLY civicrm_address is reverted and we check that email, contact and contribution
   * entities have not been.
   *
   * @throws \Exception
   */
  public function testRevertRestrictedTables() {

    CRM_Core_DAO::executeQuery("SET @uniqueID = 'temp name'");
    $this->callAPISuccessGetValue('Setting', array('name' => 'logging_all_tables_uniquid'), TRUE);
    $this->callAPISuccess('Setting', 'create', array('logging' => TRUE));

    $contactId = $this->individualCreate(array('address' => array(array('street_address' => '27 Cool way', 'location_type_id' => 1))));
    $contact = $this->callAPISuccessGetSingle('contact', array('id' => $contactId));
    $this->assertEquals('Anthony', $contact['first_name']);
    $this->assertEquals('anthony_anderson@civicrm.org', $contact['email']);
    $this->assertEquals('27 Cool way', $contact['street_address']);

    sleep(1);
    CRM_Core_DAO::executeQuery("SET @uniqueID = 'bitty bot bot'");
    $this->callAPISuccess('Contact', 'create', array(
      'id' => $contactId,
      'first_name' => 'Dopey',
      'address' => array(array('street_address' => '25 Dorky way', 'location_type_id' => 1)),
      'email' => array('email' => array('email' => 'dopey@mail.com', 'location_type_id' => 1)),
      'api.contribution.create' => array('financial_type_id' => 'Donation', 'receive_date' => 'now', 'total_amount' => 10),
    ));
    $contact = $this->callAPISuccessGetSingle('contact', array('id' => $contactId, 'return' => array('first_name', 'email', 'modified_date', 'street_address')));
    $this->assertEquals('Dopey', $contact['first_name']);
    $this->assertEquals('dopey@mail.com', $contact['email']);
    $this->assertEquals('25 Dorky way', $contact['street_address']);
    $modifiedDate = $contact['modified_date'];
    // To protect against the modified date not changing due to the updates being too close together.
    sleep(1);
    $loggings = $this->callAPISuccess('Logging', 'get', array('log_conn_id' => 'bitty bot bot', 'tables' => array('civicrm_address')));
    $this->assertEquals('civicrm_address', $loggings['values'][0]['table'], CRM_Core_DAO::executeQuery('SELECT * FROM log_civicrm_address')->toArray());
    $this->assertEquals(1, $loggings['count'], CRM_Core_DAO::executeQuery('SELECT * FROM log_civicrm_address')->toArray());
    $this->assertEquals('27 Cool way', $loggings['values'][0]['from']);
    $this->assertEquals('25 Dorky way', $loggings['values'][0]['to']);
    $this->callAPISuccess('Logging', 'revert', array('log_conn_id' => 'bitty bot bot', 'tables' => array('civicrm_address')));

    $contact = $this->callAPISuccessGetSingle('contact', array('id' => $contactId, 'return' => array('first_name', 'email', 'modified_date', 'street_address')));
    $this->assertEquals('Dopey', $contact['first_name']);
    $this->assertEquals('dopey@mail.com', $contact['email']);
    $this->assertEquals('27 Cool way', $contact['street_address']);
    $this->callAPISuccessGetCount('Contribution', array('contact_id' => $contactId), 1);
    $this->assertTrue(strtotime($modifiedDate) < strtotime($contact['modified_date']));
  }

  /**
   * Test changes can be reverted.
   */
  public function testRevertNoDateNotUnique() {
    $contactId = $this->individualCreate();
    $this->callAPISuccess('Setting', 'create', array('logging' => TRUE));
    CRM_Core_DAO::executeQuery("SET @uniqueID = 'Wopity woot'");
    $this->callAPISuccess('Contact', 'create', array(
      'id' => $contactId,
      'first_name' => 'Dopey',
      'api.email.create' => array('email' => 'dopey@mail.com'),
    ));
    $this->callAPISuccess('Setting', 'create', array('logging_all_tables_uniquid' => FALSE));
    $this->callAPISuccess('Setting', 'create', array('logging_uniqueid_date' => date('Y-m-d H:i:s', strtotime('+ 1 hour'))));
    $this->callAPIFailure(
      'Logging',
      'revert',
      array('log_conn_id' => 'Wopity woot'),
      'The connection date must be passed in to disambiguate this logging entry per CRM-18193'
    );
  }

  /**
   * Test changes can be retrieved.
   */
  public function testGet() {
    $contactId = $this->individualCreate();
    $this->callAPISuccess('Setting', 'create', array('logging' => TRUE));
    CRM_Core_DAO::executeQuery("SET @uniqueID = 'wooty woot'");
    // Add delay so the update is actually enough after the create that the timestamps differ
    sleep(1);
    $timeStamp = date('Y-m-d H:i:s');
    $this->callAPISuccess('Contact', 'create', array(
      'id' => $contactId,
      'first_name' => 'Dopey',
      'last_name' => 'Dwarf',
      'api.email.create' => array('email' => 'dopey@mail.com'),
    ));
    $this->callAPISuccessGetSingle('email', array('email' => 'dopey@mail.com'));
    $diffs = $this->callAPISuccess('Logging', 'get', array('log_conn_id' => 'wooty woot', 'log_date' => $timeStamp), __FUNCTION__, __FILE__);
    $this->assertLoggingIncludes($diffs['values'], array('to' => 'Dwarf, Dopey'));
    $this->assertLoggingIncludes($diffs['values'], array('to' => 'Mr. Dopey Dwarf II', 'table' => 'civicrm_contact', 'action' => 'Update', 'field' => 'display_name'));
    $this->assertLoggingIncludes($diffs['values'], array('to' => 'dopey@mail.com', 'table' => 'civicrm_email', 'action' => 'Insert', 'field' => 'email'));
  }

  /**
   * Test changes can be retrieved without log_date being required.
   */
  public function testGetNoDate() {
    $contactId = $this->individualCreate();
    $this->callAPISuccess('Setting', 'create', array('logging' => TRUE));
    CRM_Core_DAO::executeQuery("SET @uniqueID = 'wooty wop wop'");
    // Perhaps if initialize & create are exactly the same time it can't cope.
    // 1 second delay
    sleep(1);
    $this->callAPISuccess('Contact', 'create', array(
      'id' => $contactId,
      'first_name' => 'Dopey',
      'last_name' => 'Dwarf',
      'api.email.create' => array('email' => 'dopey@mail.com'),
    ));
    $this->callAPISuccessGetSingle('email', array('email' => 'dopey@mail.com'));
    $diffs = $this->callAPIAndDocument('Logging', 'get', array('log_conn_id' => 'wooty wop wop'), __FUNCTION__, __FILE__);
    $this->assertLoggingIncludes($diffs['values'], array('to' => 'Dwarf, Dopey'));
    $this->assertLoggingIncludes($diffs['values'], array('to' => 'Mr. Dopey Dwarf II', 'table' => 'civicrm_contact', 'action' => 'Update', 'field' => 'display_name'));
    $this->assertLoggingIncludes($diffs['values'], array('to' => 'dopey@mail.com', 'table' => 'civicrm_email', 'action' => 'Insert', 'field' => 'email'));
  }

  /**
   * Assert the values in the $expect array in included in the logging diff.
   *
   * @param array $diffs
   * @param array $expect
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function assertLoggingIncludes($diffs, $expect) {
    foreach ($diffs as $diff) {
      foreach ($expect as $expectKey => $expectValue) {
        if ($diff[$expectKey] != $expectValue) {
          continue;
        }
        return TRUE;
      }
    }
    throw new CRM_Core_Exception("No match found for key : $expectKey with value : $expectValue" . print_r($diffs, 1));
  }

  /**
   * Check if the column exists in the table.
   *
   * @param string $table
   * @param string $column
   *
   * @return bool
   */
  protected function checkColumnExistsInTable($table, $column) {
    $dao = CRM_Core_DAO::executeQuery("SHOW columns FROM {$table} WHERE Field = '{$column}'");
    $dao->fetch(TRUE);
    return ($dao->N == 1);
  }

  /**
   * Helper for when it crashes and clean up needs to be done.
   */
  protected function ensureTempColIsCleanedUp() {
    if ($this->checkColumnExistsInTable('civicrm_acl', 'temp_col')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_acl DROP Column temp_col");
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_website DROP Column temp_col");
    }
  }

}
