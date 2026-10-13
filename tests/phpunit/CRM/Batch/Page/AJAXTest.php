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
 * Test class for CRM_Batch_Page_AJAX.
 *
 * @group headless
 */
class CRM_Batch_Page_AJAXTest extends CiviUnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
  }

  public function tearDown(): void {
    unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    $_POST = [];
    parent::tearDown();
  }

  /**
   * Test batchSave succeeds when user has permission to edit the batch.
   */
  public function testBatchSaveSuccess(): void {
    $loggedInUserId = $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviContribute', 'edit own manual batches']);

    $batch = $this->createTestEntity('Batch', [
      'title' => 'Test Open Batch',
      'created_id' => $loggedInUserId,
      'status_id:name' => 'Data Entry',
    ]);

    $_POST = [
      'batch_id' => $batch['id'],
      'qfKey' => 'secret_qf_key',
      'field_1_total_amount' => '100.00',
    ];

    try {
      CRM_Batch_Page_AJAX::batchSave();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      // Expected from civiExit
    }

    $savedData = CRM_Core_DAO::getFieldValue('CRM_Batch_DAO_Batch', $batch['id'], 'data');
    $this->assertNotEmpty($savedData);
    $decoded = json_decode($savedData, TRUE);
    $this->assertEquals('100.00', $decoded['values']['field_1_total_amount']);
    $this->assertArrayNotHasKey('qfKey', $decoded['values']);
  }

  /**
   * Test batchSave is denied when user lacks edit permissions.
   */
  public function testBatchSaveDeniedWithoutPermission(): void {
    $ownerId = $this->individualCreate([], 'owner');
    $batch = $this->createTestEntity('Batch', [
      'title' => 'Other User Batch',
      'created_id' => $ownerId,
      'status_id:name' => 'Data Entry',
    ]);

    $this->createLoggedInUser();
    // User only has access CiviContribute but not edit permissions for other users' batches
    $this->setPermissions(['access CiviCRM', 'access CiviContribute', 'edit own manual batches']);

    $_POST = [
      'batch_id' => $batch['id'],
      'field_1_total_amount' => '999.00',
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Batch_Page_AJAX::batchSave();
  }

  /**
   * Test batchSave is denied on closed batches.
   */
  public function testBatchSaveDeniedOnClosedBatch(): void {
    $loggedInUserId = $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviContribute', 'edit all manual batches']);

    $batch = $this->createTestEntity('Batch', [
      'title' => 'Closed Batch',
      'created_id' => $loggedInUserId,
      'status_id:name' => 'Closed',
    ]);

    $_POST = [
      'batch_id' => $batch['id'],
      'field_1_total_amount' => '50.00',
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Batch_Page_AJAX::batchSave();
  }

  /**
   * Test batchSave is denied when batch_id is invalid or missing.
   */
  public function testBatchSaveDeniedInvalidBatchId(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviContribute', 'edit all manual batches']);

    $_POST = [
      'batch_id' => '99999999',
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Batch_Page_AJAX::batchSave();
  }

}
