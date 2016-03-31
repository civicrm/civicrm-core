<?php
/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2015                                |
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
    parent::setUp();
  }

  /**
   * Clean up log tables.
   */
  protected function tearDown() {
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
    $this->assertEquals(0, $this->callAPISuccessGetValue('Setting', array('name' => 'logging', 'group' => 'core')));
    $this->assertLoggingEnabled(FALSE);

    $this->callAPISuccess('Setting', 'create', array('logging' => TRUE));
    $this->assertLoggingEnabled(TRUE);
    $this->checkLogTableCreated();
    $this->checkTriggersCreated(TRUE);
    // Create a contact to make sure they aren't borked.
    $this->individualCreate();
    $this->assertTrue($this->callAPISuccessGetValue('Setting', array('name' => 'logging', 'group' => 'core')));
    $this->assertEquals(1, $this->callAPISuccessGetValue('Setting', array('name' => 'logging_all_tables_uniquid', 'group' => 'core')));
    $this->assertEquals(
      date('Y-m-d'),
      date('Y-m-d', strtotime($this->callAPISuccessGetValue('Setting', array('name' => 'logging_uniqueid_date', 'group' => 'core'))))
    );

    $this->callAPISuccess('Setting', 'create', array('logging' => FALSE));
    $this->assertEquals(0, $this->callAPISuccessGetValue('Setting', array('name' => 'logging', 'group' => 'core')));
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
    $this->assertEquals(0, $this->callAPISuccessGetValue('Setting', array('name' => 'logging_all_tables_uniquid', 'group' => 'core')));
    $this->assertEmpty($this->callAPISuccessGetValue('Setting', array('name' => 'logging_uniqueid_date', 'group' => 'core')));
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
    $this->assertEquals(0, $this->callAPISuccessGetValue('Setting', array('name' => 'logging_all_tables_uniquid', 'group' => 'core')));
    $this->assertEquals(
      date('Y-m-d'),
      date('Y-m-d', strtotime($this->callAPISuccessGetValue('Setting', array('name' => 'logging_uniqueid_date', 'group' => 'core'))))
    );
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
    CRM_Core_DAO::executeQuery("SET @uniqueID = 'woot'");
    $timeStamp = date('Y-m-d H:i:s');
    $this->callAPISuccess('Contact', 'create', array(
      'id' => $contactId,
      'first_name' => 'Dopey',
      'api.email.create' => array('email' => 'dopey@mail.com'))
    );
    $email = $this->callAPISuccessGetSingle('email', array('email' => 'dopey@mail.com'));
    $this->callAPIAndDocument('Logging', 'revert', array('log_conn_id' => 'woot', 'log_date' => $timeStamp), __FILE__, 'Revert');
    $this->assertEquals('Anthony', $this->callAPISuccessGetValue('contact', array('id' => $contactId, 'return' => 'first_name')));
    $this->callAPISuccessGetCount('Email', array('id' => $email['id']), 0);
  }

}
