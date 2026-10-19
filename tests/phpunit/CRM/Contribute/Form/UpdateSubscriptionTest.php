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

use Civi\Api4\Activity;
use Civi\Api4\Contribution;
use Civi\Api4\ContributionRecur;
use Civi\Test\FormTrait;

/**
 * Class CRM_Contribute_Form_UpdateSubscriptionTest
 */
class CRM_Contribute_Form_UpdateSubscriptionTest extends CiviUnitTestCase {

  use CRMTraits_Contribute_RecurFormsTrait;
  use FormTrait;

  /**
   * Clean up after test.
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Test the mail sent on update.
   */
  public function testMail(): void {
    $this->addContribution();
    $form = $this->getTestForm('CRM_Contribute_Form_UpdateSubscription',
      ['amount' => 10, 'installments' => 12, 'is_notify' => TRUE],
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
   * Test an amount entered in the site's money format is saved on the recurring & template contributions.
   *
   * @param string $thousandSeparator
   *
   * @dataProvider getThousandSeparators
   */
  public function testUpdateAmount(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $this->addContribution();
    $this->getTestForm('CRM_Contribute_Form_UpdateSubscription', [
      'amount' => $this->formatMoneyInput(25.50),
      'installments' => 12,
    ], ['crid' => $this->getContributionRecurID()])->processForm();

    $contributionRecur = ContributionRecur::get(FALSE)
      ->addWhere('id', '=', $this->getContributionRecurID())
      ->execute()->single();
    $this->assertEquals(25.50, $contributionRecur['amount']);
    $templateContribution = Contribution::get(FALSE)
      ->addWhere('contribution_recur_id', '=', $this->getContributionRecurID())
      ->addWhere('is_template', '=', TRUE)
      ->execute()->single();
    $this->assertEquals(25.50, $templateContribution['total_amount']);
    $activity = Activity::get(FALSE)
      ->addWhere('activity_type_id:name', '=', 'Update Recurring Contribution')
      ->addWhere('source_contact_id', '=', $this->getContactID())
      ->execute()->single();
    $this->assertStringContainsString(CRM_Utils_Money::format(25.50, 'USD'), $activity['details']);
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
