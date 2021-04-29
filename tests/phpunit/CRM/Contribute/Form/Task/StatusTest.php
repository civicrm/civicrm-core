<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | Use of this source code is governed by the AGPL license with some  |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Contribute_Form_Task_StatusTest
 */
class CRM_Contribute_Form_Task_StatusTest extends CiviUnitTestCase {

  protected $_individualId;

  /**
   * Clean up after each test.
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    CRM_Utils_Hook::singleton()->reset();
  }

  /**
   * Test update pending contribution with sending a confirmation mail.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function testUpdatePendingContributionWithSendingEmail(): void {
    $this->_individualId = $this->individualCreate();
    $form = new CRM_Contribute_Form_Task_Status();

    $mut = new CiviMailUtils($this, TRUE);
    $mut->clearMessages();

    // create a pending contribution
    $contributionParams = [
      'contact_id' => $this->_individualId,
      'total_amount' => 100,
      'financial_type_id' => 'Donation',
      'contribution_status_id' => 2,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    $contributionId = $contribution['id'];
    $form->setContributionIds([$contributionId]);

    $form->buildQuickForm();

    $params = [
      "is_email_receipt" => '1',
      "contribution_status_id" => 1,
      "trxn_id_{$contributionId}" => NULL,
      "check_number_{$contributionId}" => NULL,
      "fee_amount_{$contributionId}" => 0,
      "trxn_date_{$contributionId}" => date('m/d/Y'),
      "payment_instrument_id_{$contributionId}" => 4,
    ];

    CRM_Contribute_Form_Task_Status::processForm($form, $params);

    $contribution = $this->callAPISuccess('Contribution', 'get', ['id' => $contributionId]);
    $updatedContribution = $contribution['values'][1];

    $this->assertEquals('', $updatedContribution['contribution_source']);
    $this->assertEquals(date("Y-m-d"), date("Y-m-d", strtotime($updatedContribution['receive_date'])));
    $this->assertNotEquals("00:00:00", date("H:i:s", strtotime($updatedContribution['receive_date'])));
    $this->assertEquals('Completed', $updatedContribution['contribution_status']);

    $msg = $mut->getMostRecentEmail();
    $this->assertNotEmpty($msg);
    $mut->stop();
  }

  /**
   * Test update pending contribution without sending a confirmation mail.
   */
  public function testUpdatePendingContributionWithoutSendingEmail() {
    $this->_individualId = $this->individualCreate();
    $form = new CRM_Contribute_Form_Task_Status();

    $mut = new CiviMailUtils($this, TRUE);
    $mut->clearMessages();

    // create a pending contribution
    $contributionParams = [
      'contact_id' => $this->_individualId,
      'total_amount' => 100,
      'financial_type_id' => 'Donation',
      'contribution_status_id' => 2,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    $contributionId = $contribution['id'];
    $form->setContributionIds([$contributionId]);

    $form->buildQuickForm();

    $params = [
      "is_email_receipt" => '0',
      "contribution_status_id" => 1,
      "trxn_id_{$contributionId}" => NULL,
      "check_number_{$contributionId}" => NULL,
      "fee_amount_{$contributionId}" => 0,
      "trxn_date_{$contributionId}" => date('m/d/Y'),
      "payment_instrument_id_{$contributionId}" => 4,
    ];

    CRM_Contribute_Form_Task_Status::processForm($form, $params);

    $contribution = $this->callAPISuccess('Contribution', 'get', ['id' => $contributionId]);
    $updatedContribution = $contribution['values'][1];

    $this->assertEquals('', $updatedContribution['contribution_source']);
    $this->assertEquals(date("Y-m-d"), date("Y-m-d", strtotime($updatedContribution['receive_date'])));
    $this->assertNotEquals("00:00:00", date("H:i:s", strtotime($updatedContribution['receive_date'])));
    $this->assertEquals('Completed', $updatedContribution['contribution_status']);

    $mut->assertMailLogEmpty();
    $mut->stop();
  }

}
