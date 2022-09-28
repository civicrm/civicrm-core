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
class CRM_Contribute_Form_CancelSubscriptionTest extends CiviUnitTestCase {

  use CRMTraits_Contribute_RecurFormsTrait;

  /**
   * Test the mail sent on update.
   *
   * @throws \CRM_Core_Exception
   */
  public function testMail(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->addContribution();
    /** @var CRM_Contribute_Form_CancelSubscription $form */
    $form = $this->getFormObject('CRM_Contribute_Form_CancelSubscription', ['is_notify' => TRUE]);
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
      "Subject: Recurring Contribution Cancellation Notification - Mr. Anthony\n Anderson II",
      'Return-Path: bob@example.org',
      'Dear Anthony,',
      'Your recurring contribution of $10.00, every 1 month has been cancelled as requested',
    ];
  }

  /**
   * Test if the full fledged form is displayed on cancelling the Recurring
   * Contribution with a payment processor which does not support cancelling a
   * Recurring Contribution
   *
   * @throws \CRM_Core_Exception
   */
  public function testCancelSubscriptionForm(): void {
    $this->addContribution();
    /** @var CRM_Contribute_Form_CancelSubscription $form */
    $form = $this->getFormObject('CRM_Contribute_Form_CancelSubscription', ['is_notify' => TRUE]);
    $form->set('crid', $this->getContributionRecurID());
    $form->buildForm();

    /* Set the Payment processor to not support 'Cancel Recurring' */
    $paymentProcessorObj = Civi\Payment\System::singleton()->getById(CRM_Contribute_BAO_ContributionRecur::getPaymentProcessorID($this->getContributionRecurID()));
    $paymentProcessorObj->setSupports([
      'CancelRecurring' => FALSE,
    ]);

    $actions = CRM_Contribute_Page_Tab::recurLinks($this->getContributionRecurID());
    // Using "Cancel Recurring" form
    $this->assertEquals('civicrm/contribute/unsubscribe', $actions[CRM_Core_Action::DISABLE]['url']);
  }

}
