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

use Civi\Test\FormTrait;

/**
 * Class CRM_Contribute_Form_UpdateSubscriptionTest
 */
class CRM_Contribute_Form_UpdateSubscriptionTest extends CiviUnitTestCase {

  use CRMTraits_Contribute_RecurFormsTrait;
  use FormTrait;

  /**
   * Test the mail sent on update.
   */
  public function testMail(): void {
    $this->addContribution();
    $form = $this->getTestForm('CRM_Contribute_Form_UpdateSubscription',
      ['is_notify' => TRUE],
      ['crid' => $this->getContributionRecurID()]);
    $form->processForm();
    $this->assertMailSentContainingHeaderStrings([
      'Return-Path: bob@example.org',
      'Anthony Anderson <anthony_anderson@civicrm.org>',
      'Subject: Recurring Contribution Update Notification - Mr. Anthony Anderson II',
    ]);
    $this->assertMailSentContainingStrings($this->getExpectedMailStrings());
  }

  /**
   * Get the strings to check for.
   *
   * @return string[]
   */
  public function getExpectedMailStrings(): array {
    return [
      '"Bob" <bob@example.org>',
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
