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
    $this->checkTriggersCreated();
    // Create a contact to make sure they aren't borked.
    $this->individualCreate();
    $this->assertTrue($this->callAPISuccessGetValue('Setting', array('name' => 'logging', 'group' => 'core')));

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
    $this->checkTriggersCreated();
    // Create a contact to make sure they aren't borked.
    $this->individualCreate();
    $this->callAPISuccess('Setting', 'create', array('logging' => FALSE));
  }

  /**
   * Use a hook to declare an INNODB engine for the contact log table.
   *
   * @param array $logTableSpec
   */
  public function innodbLogTableSpec(&$logTableSpec) {
    $logTableSpec['civicrm_contact'] = array(
      'engine' => 'INNODB',
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
    $this->assertContains('`log_conn_id` varchar(17) COLLATE utf8_unicode_ci DEFAULT NULL,', $dao->$tableField);
    return $dao->$tableField;
  }

  /**
   * Check the log tables were created and reflect the INNODB hook.
   */
  protected function checkINNODBLogTableCreated() {
    $createTableString = $this->checkLogTableCreated();
    $this->assertContains('ENGINE=InnoDB', $createTableString);
    $this->assertContains('KEY `index_id` (`id`),', $createTableString);
  }

  /**
   * Check the triggers were created and look OK.
   */
  protected function checkTriggersCreated() {
    $dao = CRM_Core_DAO::executeQuery("SHOW TRIGGERS LIKE 'civicrm_contact'");
    while ($dao->fetch()) {
      if ($dao->Timing == 'After') {
        $this->assertContains('@uniqueID', $dao->Statement);
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

}
