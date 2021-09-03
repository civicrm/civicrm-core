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
 * Class CRM_Contribute_Form_UpdateSubscriptionTest
 */
class CRM_Contribute_Form_UpdateSubscriptionTest extends CiviUnitTestCase {

  /**
   * Test the mail sent on update.
   *
   * @throws \CRM_Core_Exception|\API_Exception
   */
  public function testMail(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->addContribution();
    /* @var CRM_Contribute_Form_UpdateSubscription $form */
    $form = $this->getFormObject('CRM_Contribute_Form_UpdateSubscription', ['is_notify' => TRUE]);
    $form->set('crid', $this->getContributionRecurID());
    $form->buildForm();
    try {
      $form->postProcess();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $mut->checkMailLog($this->getExpectedMailStrings());
      return;
    }
    $this->fail('should not be reachable');
  }

  /**
   * Get the strings to check for.
   *
   * @return string[]
   */
  public function getExpectedMailStrings(): array {
    return [
      'MIME-Version: 1.0',
      'From: "Bob" <bob@example.org>',
      'To: Anthony Anderson <anthony_anderson@civicrm.org>',
      'Subject: Recurring Contribution Update Notification - Mr. Anthony Anderson II',
      'Return-Path: bob@example.org',
      'Dear Anthony,',
      'Your recurring contribution has been updated as requested:',
      'Recurring contribution is for $ 10.00, every 1 month(s) for 12 installments.',
      'If you have questions please contact us at "Bob" <bob@example.org>.',
    ];
  }

  /**
   * Get contact id.
   *
   *  return int
   */
  public function getContactID(): int {
    if (!isset($this->ids['Contact'][0])) {
      $this->ids['Contact'][0] = $this->individualCreate();
    }
    return $this->ids['Contact'][0];
  }

  /**
   *
   */
  public function addContribution(): void {
    $this->paymentProcessorId = $this->processorCreate();
    $this->callAPISuccess('Order', 'create', [
      'contact_id' => $this->getContactID(),
      'contribution_recur_id' => $this->getContributionRecurID(),
      'financial_type_id' => 'Donation',
      'total_amount' => 10,
      'contribution_page_id' => $this->getContributionPageID(),
      'api.Payment.create' => [
        'total_amount' => 10,
        'payment_processor_id' => $this->paymentProcessorId,
        'is_send_contribution_notification' => FALSE,
      ],
    ]);
  }

  /**
   * Get contribution recur ID.
   *
   * return int
   */
  public function getContributionRecurID(): int {
    if (!isset($this->ids['ContributionRecur'][0])) {
      $this->ids['ContributionRecur'][0] = $this->callAPISuccess('ContributionRecur', 'create', [
        'contact_id' => $this->getContactID(),
        'amount' => 10,
        'installments' => 12,
        'frequency_interval' => 1,
        'frequency_unit' => 'month',
      ])['id'];
    }
    return $this->ids['ContributionRecur'][0];
  }

  /**
   * Get a contribution page id.
   *
   * @return int
   */
  public function getContributionPageID(): int {
    if (!isset($this->ids['ContributionPage'][0])) {
      $this->ids['ContributionPage'][0] = $this->callAPISuccess('ContributionPage', 'create', [
        'receipt_from_name' => 'Bob',
        'receipt_from_email' => 'bob@example.org',
        'financial_type_id' => 'Donation',
      ])['id'];
    }
    return $this->ids['ContributionPage'][0];
  }

}
