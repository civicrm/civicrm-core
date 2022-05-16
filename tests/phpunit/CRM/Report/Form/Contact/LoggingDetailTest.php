<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *  Test Activity report outcome
 *
 * @package CiviCRM
 */
class CRM_Report_Form_Contact_LoggingDetailTest extends CiviReportTestCase {
  protected $_tablesToTruncate = [
    'civicrm_contact',
    'log_civicrm_contact',
  ];

  public function setUp(): void {
    parent::setUp();

    // Setup logging. This may create a series of backfilled log records.
    $this->callAPISuccess('Setting', 'create', ['logging' => TRUE]);
    $this->quickCleanup($this->_tablesToTruncate);

    // The test needs to create+read some log records. We want this to have a new/separate `log_conn_id`.
    unset(\Civi::$statics['CRM_Utils_Request']['id']);
    CRM_Core_DAO::init(CIVICRM_DSN);
  }

  public function tearDown(): void {
    $this->callAPISuccess('Setting', 'create', ['logging' => FALSE]);
    parent::tearDown();
    $log = new CRM_Logging_Schema();
    $log->dropAllLogTables();
  }

  /**
   * Ensure a missing label name on a DAO won't crash the Logging Detail Report.
   */
  public function testLabelFieldIsntRequired() {
    // Create an individual and a contribution in the same database connection (as if a new contact submitted a contribution online).
    $cid = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $cid]);
    $logConnId = CRM_Core_DAO::singleValueQuery('SELECT log_conn_id FROM log_civicrm_contribution ORDER BY log_date DESC LIMIT 1');

    // Logging Details report builds rows in the constructor so we have to pass the log_conn_id before getReportObject does.
    $tmpGlobals["_REQUEST"]["log_conn_id"] = $logConnId;
    CRM_Utils_GlobalStack::singleton()->push($tmpGlobals);
    // Run the report.
    $input = [
      'filters' => ['log_conn_id' => $logConnId],
    ];
    $obj = $this->getReportObject('CRM_Report_Form_Contact_LoggingDetail', $input);
  }

}
