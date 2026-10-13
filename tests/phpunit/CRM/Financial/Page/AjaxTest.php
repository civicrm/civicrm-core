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
 * Test for CRM_Financial_Page_Ajax class.
 * @group headless
 */
class CRM_Financial_Page_AjaxTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
  }

  public function tearDown(): void {
    unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    $_REQUEST = [];
    $_GET = [];
    $_POST = [];
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanUp([
      'civicrm_entity_batch',
      'civicrm_batch',
    ]);
    parent::tearDown();
  }

  /**
   * Test the ajax function to get financial transactions.
   *
   * Test focus is on ensuring changes to how labels are retrieved does not cause regression.
   */
  public function testGetFinancialTransactionsList(): void {
    $individualID = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $individualID, 'trxn_id' => 12345]);
    $batch = $this->callAPISuccess('Batch', 'create', ['title' => 'test', 'status_id' => 'Open']);
    CRM_Core_DAO::executeQuery("
     INSERT INTO civicrm_entity_batch (entity_table, entity_id, batch_id)
     values('civicrm_financial_trxn', 1, 1)
   ");
    $_REQUEST['sEcho'] = 1;
    $_REQUEST['entityID'] = $batch['id'];
    $_REQUEST['return'] = TRUE;
    $json = CRM_Financial_Page_AJAX::getFinancialTransactionsList();
    $json = str_replace(rtrim(CIVICRM_UF_BASEURL, '/'), 'http://FIX ME', $json);
    $this->assertEquals('{"sEcho": 1, "iTotalRecords": 1, "iTotalDisplayRecords": 1, "aaData": [ ["","<a href=\"/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=3\" data-tooltip-url=\"/index.php?q=civicrm/profile/view&amp;reset=1&amp;gid=7&amp;id=3&amp;snippet=4&amp;is_show_email_task=1\" class=\"crm-summary-link\" aria-labelledby=\"crm-contactname-content\">'
    . '<i class=\"crm-i fa-fw fa-user\" title=\"\" role=\"img\" aria-hidden=\"true\"></i></a>","<a href=\"/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=3\">Anderson, Anthony II</a>","$100.00","12345","' . CRM_Utils_Date::customFormat(date('Ymd')) . ' 12:00 AM","' . CRM_Utils_Date::customFormat(date('Ymd')) . ' 12:00 AM",'
    . '"Credit Card","Completed","Donation","<span><a href=\"/index.php?q=civicrm/contact/view/contribution&amp;reset=1&amp;id=1&amp;cid=3&amp;action=view&amp;context=contribution&amp;'
    . 'selectedChild=contribute\" class=\"action-item crm-hover-button\" title=\'View Contribution\' >View</a></span>"]] }', $json);
  }

  /**
   * Test getting open batch.
   */
  public function testGetFinancialTransactionsListOpenBatch(): void {
    $individualID = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $individualID, 'trxn_id' => 12345]);
    $batch = $this->callAPISuccess('Batch', 'create', ['title' => 'test', 'status_id' => 'Open']);
    CRM_Core_DAO::executeQuery("
     INSERT INTO civicrm_entity_batch (entity_table, entity_id, batch_id)
     values('civicrm_financial_trxn', 1, 1)
   ");
    $_REQUEST['sEcho'] = 1;
    $_REQUEST['entityID'] = $batch['id'];
    $_REQUEST['statusID'] = 1;
    $_REQUEST['notPresent'] = 1;
    $_REQUEST['return'] = TRUE;
    $json = CRM_Financial_Page_AJAX::getFinancialTransactionsList();
    $json = str_replace(rtrim(CIVICRM_UF_BASEURL, '/'), 'http://FIX ME', $json);
    $this->assertEquals('{"sEcho": 1, "iTotalRecords": 1, "iTotalDisplayRecords": 1, "aaData": [ ["<input type=\'checkbox\' id=\'mark_x_2\' name=\'mark_x_2\' value=\'1\' onclick=enableActions(\'x\')></input>","<a href=\"/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=3\" data-tooltip-url=\"/index.php?q=civicrm/profile/view&amp;reset=1&amp;gid=7&amp;id=3&amp;snippet=4&amp;is_show_email_task=1\" class=\"crm-summary-link\" aria-labelledby=\"crm-contactname-content\">'
    . '<i class=\"crm-i fa-fw fa-user\" title=\"\" role=\"img\" aria-hidden=\"true\"></i></a>","<a href=\"/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=3\">Anderson, Anthony II</a>","$5.00","12345","' . CRM_Utils_Date::customFormat(date('Ymd')) . ' 12:00 AM","' . CRM_Utils_Date::customFormat(date('Ymd')) . ' 12:00 AM",'
    . '"Credit Card","Completed","Donation","<span><a href=\"/index.php?q=civicrm/contact/view/contribution&amp;reset=1&amp;id=1&amp;cid=3&amp;action=view&amp;context=contribution&amp;'
    . 'selectedChild=contribute\" class=\"action-item crm-hover-button\" title=\'View Contribution\' >View</a><a href=\"#\" class=\"action-item crm-hover-button disable-action\" title=\'Assign Transaction\' onclick = \"assignRemove( 2,\'assign\' );\">Assign</a></span>"]] }', $json);
  }

  /**
   * Test that passing an invalid sort parameter (SQL injection payload) to getFinancialTransactionsList throws an exception.
   */
  public function testGetFinancialTransactionsListSQLInjection(): void {
    $_REQUEST['sEcho'] = 1;
    $_REQUEST['entityID'] = 1;
    $_REQUEST['sSortDir_0'] = '(if(1=1,sleep(0.5),0))';

    $this->expectException(CRM_Core_Exception::class);
    CRM_Financial_Page_AJAX::getFinancialTransactionsList();
  }

  /**
   * Test that passing an invalid entityID parameter to getFinancialTransactionsList throws an exception.
   */
  public function testGetFinancialTransactionsListEntityIDSQLInjection(): void {
    $_REQUEST['sEcho'] = 1;
    $_REQUEST['entityID'] = '1 OR 1=1';

    $this->expectException(CRM_Core_Exception::class);
    CRM_Financial_Page_AJAX::getFinancialTransactionsList();
  }

  /**
   * Test that passing an invalid statusID parameter to getFinancialTransactionsList throws an exception.
   */
  public function testGetFinancialTransactionsListStatusIDSQLInjection(): void {
    $_REQUEST['sEcho'] = 1;
    $_REQUEST['statusID'] = '1 OR 1=1';

    $this->expectException(CRM_Core_Exception::class);
    CRM_Financial_Page_AJAX::getFinancialTransactionsList();
  }

  /**
   * Test that passing an invalid notPresent parameter to getFinancialTransactionsList throws an exception.
   */
  public function testGetFinancialTransactionsListNotPresentSQLInjection(): void {
    $_REQUEST['sEcho'] = 1;
    $_REQUEST['notPresent'] = '1 OR 1=1';

    $this->expectException(CRM_Core_Exception::class);
    CRM_Financial_Page_AJAX::getFinancialTransactionsList();
  }

  /**
   * Test that passing an invalid entityID parameter to bulkAssignRemove throws an exception.
   */
  public function testBulkAssignRemoveEntityIDSQLInjection(): void {
    $_REQUEST['ID'] = ['mark_x_1'];
    $_REQUEST['entityID'] = '1 OR 1=1';
    $_REQUEST['action'] = 'Assign';

    $this->expectException(CRM_Core_Exception::class);
    CRM_Financial_Page_AJAX::bulkAssignRemove();
  }

  /**
   * Test bulkAssignRemove succeeds for assign and remove when user has permission.
   */
  public function testBulkAssignRemoveSuccess(): void {
    $loggedInUserId = $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviContribute', 'edit own manual batches']);

    $batch = $this->createTestEntity('Batch', [
      'title' => 'Open Manual Batch',
      'created_id' => $loggedInUserId,
      'status_id:name' => 'Data Entry',
    ]);

    $individualID = $this->individualCreate();
    $contributionId = $this->contributionCreate(['contact_id' => $individualID, 'trxn_id' => 998877]);
    $financialTrxnId = CRM_Core_DAO::singleValueQuery(
      "SELECT financial_trxn_id FROM civicrm_entity_financial_trxn WHERE entity_table = 'civicrm_contribution' AND entity_id = %1",
      [1 => [$contributionId, 'Positive']]
    );

    // 1. Assign transaction to batch
    $_POST = $_REQUEST = [
      'entityID' => $batch['id'],
      'action' => 'Assign',
      'ID' => ['mark_x_' . $financialTrxnId],
    ];

    try {
      CRM_Financial_Page_AJAX::bulkAssignRemove();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals(['status' => 'record-updated-success', 'error_code' => 0], $e->errorData);
    }

    $assigned = CRM_Core_DAO::singleValueQuery(
      "SELECT count(*) FROM civicrm_entity_batch WHERE entity_table = 'civicrm_financial_trxn' AND entity_id = %1 AND batch_id = %2",
      [1 => [$financialTrxnId, 'Positive'], 2 => [$batch['id'], 'Positive']]
    );
    $this->assertEquals(1, $assigned);

    // 2. Remove transaction from batch
    $_POST = $_REQUEST = [
      'entityID' => $batch['id'],
      'action' => 'Remove',
      'ID' => ['mark_y_' . $financialTrxnId],
    ];

    try {
      CRM_Financial_Page_AJAX::bulkAssignRemove();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals(['status' => 'record-updated-success', 'error_code' => 0], $e->errorData);
    }

    $assignedAfter = CRM_Core_DAO::singleValueQuery(
      "SELECT count(*) FROM civicrm_entity_batch WHERE entity_table = 'civicrm_financial_trxn' AND entity_id = %1 AND batch_id = %2",
      [1 => [$financialTrxnId, 'Positive'], 2 => [$batch['id'], 'Positive']]
    );
    $this->assertEquals(0, $assignedAfter);
  }

  /**
   * Test bulkAssignRemove is denied when user lacks edit permission on the batch.
   */
  public function testBulkAssignRemoveDeniedWithoutPermission(): void {
    $ownerId = $this->individualCreate([], 'owner');
    $batch = $this->createTestEntity('Batch', [
      'title' => 'Other User Batch',
      'created_id' => $ownerId,
      'status_id:name' => 'Data Entry',
    ]);

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviContribute', 'edit own manual batches']);

    $_POST = $_REQUEST = [
      'entityID' => $batch['id'],
      'action' => 'Assign',
      'ID' => ['mark_x_1'],
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Financial_Page_AJAX::bulkAssignRemove();
  }

  /**
   * Test bulkAssignRemove is denied on closed batches.
   */
  public function testBulkAssignRemoveDeniedOnClosedBatch(): void {
    $loggedInUserId = $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviContribute', 'edit all manual batches']);

    $batch = $this->createTestEntity('Batch', [
      'title' => 'Closed Batch',
      'created_id' => $loggedInUserId,
      'status_id:name' => 'Closed',
    ]);

    $_POST = $_REQUEST = [
      'entityID' => $batch['id'],
      'action' => 'Assign',
      'ID' => ['mark_x_1'],
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Financial_Page_AJAX::bulkAssignRemove();
  }

  /**
   * Test getFinancialTransactionsList is denied for other user's batch when holding only 'view own manual batches'.
   */
  public function testGetFinancialTransactionsListDeniedForOtherUserBatch(): void {
    $ownerId = $this->individualCreate([], 'owner');
    $batch = $this->createTestEntity('Batch', [
      'title' => 'Other User Batch',
      'created_id' => $ownerId,
      'status_id:name' => 'Data Entry',
    ]);

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviContribute', 'view own manual batches']);

    $_REQUEST['entityID'] = $batch['id'];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Financial_Page_AJAX::getFinancialTransactionsList();
  }

  /**
   * Test getBatchSummary is denied for other user's batch when holding only 'view own manual batches'.
   */
  public function testGetBatchSummaryDeniedForOtherUserBatch(): void {
    $ownerId = $this->individualCreate([], 'owner');
    $batch = $this->createTestEntity('Batch', [
      'title' => 'Other User Batch',
      'created_id' => $ownerId,
      'status_id:name' => 'Data Entry',
    ]);

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviContribute', 'view own manual batches']);

    $_REQUEST['batchID'] = $batch['id'];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Financial_Page_AJAX::getBatchSummary();
  }

  /**
   * Test getBatchSummary succeeds when user has permission.
   */
  public function testGetBatchSummarySuccess(): void {
    $loggedInUserId = $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviContribute', 'view own manual batches']);

    $batch = $this->createTestEntity('Batch', [
      'title' => 'Summary Test Batch',
      'created_id' => $loggedInUserId,
      'status_id:name' => 'Data Entry',
      'payment_instrument_id:name' => 'Cash',
      'item_count' => 5,
      'total' => 100.00,
    ]);

    $_REQUEST['batchID'] = $batch['id'];

    try {
      CRM_Financial_Page_AJAX::getBatchSummary();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals('Data Entry', $e->errorData['status']);
      $this->assertEquals('Cash', $e->errorData['payment_instrument']);
      $this->assertEquals(5, $e->errorData['item_count']);
    }
  }

}
