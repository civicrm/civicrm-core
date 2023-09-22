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

  use CRMTraits_Contribute_RecurFormsTrait;

  /**
   * Test the mail sent on update.
   *
   * @throws \CRM_Core_Exception
   */
  public function testMail(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->addContribution();
    /** @var CRM_Contribute_Form_UpdateSubscription $form */
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
      'Recurring contribution is for $10.00, every 1 month(s) for 12 installments.',
      'If you have questions please contact us at "Bob" <bob@example.org>.',
    ];
  }

  /**
   * Test the Additional Details pane loads for recurring contributions.
   */
  public function testAdditionalDetails(): void {
    $this->addContribution();
    $templateContribution = CRM_Contribute_BAO_ContributionRecur::getTemplateContribution($this->getContributionRecurID());
    $_GET['q'] = $_REQUEST['q'] = 'civicrm/contact/view/contribution';
    $_GET['snippet'] = $_REQUEST['snippet'] = 4;
    $_GET['id'] = $_REQUEST['id'] = $templateContribution['id'];
    $_GET['formType'] = $_REQUEST['formType'] = 'AdditionalDetail';
    $form = $this->getFormObject('CRM_Contribute_Form_Contribution', []);
    $form->buildForm();
    unset($_GET['q'], $_REQUEST['q']);
    unset($_GET['snippet'], $_REQUEST['snippet']);
    unset($_GET['id'], $_REQUEST['id']);
    unset($_GET['formType'], $_REQUEST['formType']);
  }

}
