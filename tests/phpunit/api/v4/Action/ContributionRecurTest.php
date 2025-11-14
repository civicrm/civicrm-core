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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

namespace api\v4\Action;

use Civi\Api4\Payment;
use Civi\Test\TransactionalInterface;
use Civi\Api4\Contribution;
use Civi\Api4\ContributionRecur;

/**
 * Test API4 ContributionRecur::updateAmountOnRecur
 *
 * @group headless
 */
class ContributionRecurTest extends \CiviUnitTestCase implements TransactionalInterface {

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_contact']);
    parent::tearDown();
  }

  /**
   * @var int
   */
  private int $contributionID;

  /**
   * @var array
   * name => ContactID
   */
  protected array $contacts;

  /**
   * @var array
   * contactID => recurID
   */
  protected array $recurs;

  /**
   * Populates properties: $this->contacts, $this->recurs.
   *
   * Requires $this->pps to be set if $paymentProcessorName is given.
   *
   * @var ?string $paymentProcessorName
   * @var int $n number of contacts & recurs pairs to create (1 - 3).
   */
  protected function createFixture(?string $paymentProcessorName = NULL, int $n = 3): void {
    $contacts = array_slice([
      ['amount' => 1, 'display_name' => 'WilmaUpgrader'],
      ['amount' => 3, 'display_name' => 'BarneyDowngrader'],
      ['amount' => 2, 'display_name' => 'EmberMaintainer'],
    ], 0, $n);

    $records = array_map(function($_) {
      unset($_['amount']);
      return $_;
    }, $contacts);

    $this->contacts = \Civi\Api4\Contact::save(FALSE)
      ->setDefaults([
        'contact_type' => 'Individual',
      ])
      ->setRecords($contacts)
      ->execute()
      ->indexBy('display_name')
      ->column('id');

    $defaults = [
      'currency' => 'USD',
      'contribution_status_id:name' => 'In Progress',
      'financial_type_id:name' => 'Donation',
    ];
    if ($paymentProcessorName !== NULL) {
      $defaults['payment_processor_id'] = $this->pps[$paymentProcessorName];
    }
    $records = array_map(function($_) {
      return [
        'contact_id' => $this->contacts[$_['display_name']],
        'amount' => $_['amount'],
      ];
    }, $contacts);
    $this->recurs = ContributionRecur::save(FALSE)
      ->setDefaults($defaults)
      ->setRecords($records)
      ->execute()
      ->indexBy('contact_id')
      ->column('id');
  }

  private function createOrder() {
    $financialTypeID = \Civi\Api4\FinancialType::get(FALSE)
      ->addWhere('name', '=', 'Donation')
      ->execute()
      ->first()['id'];
    $paymentInstrumentID = \Civi\Api4\OptionValue::get(FALSE)
      ->addWhere('option_group_id:name', '=', 'payment_instrument')
      ->execute()
      ->first()['value'];

    foreach ($this->recurs as $contactID => $recurID) {
      $recur = ContributionRecur::get(FALSE)
        ->addWhere('id', '=', $recurID)
        ->execute()
        ->first();

      $orderBAO = new \CRM_Financial_BAO_Order();

      $orderCreateParams = [
        'receive_date' => date('YmdHis'),
        // trxn_date is necessary for membership date calcs.
        'trxn_date' => date('YmdHis'),
        'trxn_id' => 'testtrxnid' . $recur['id'],
        'total_amount' => $recur['amount'],
        'contact_id' => $contactID,
        'payment_instrument_id' => $paymentInstrumentID,
        'currency' => 'USD',
        'financial_type_id' => $financialTypeID,
        'is_email_receipt' => FALSE,
        'is_test' => FALSE,
        'contribution_recur_id' => $recurID,
        'contribution_status_id' => 'Pending',
        'contribution_source' => 'my test description',
      ];
      $lineItemParams = [
        'contact_id' => $contactID,
      ];
      $lineItem = [
        'line_total' => $recur['amount'],
        'unit_price' => $recur['amount'],
        'price_field_id' => $orderBAO->getDefaultPriceFieldID(),
        'price_field_value_id' => $orderBAO->getDefaultPriceFieldValueID(),
        'financial_type_id' => $financialTypeID,
        'qty' => 1,
      ];
      $orderCreateParams['line_items'] = [
        [
          'params' => $lineItemParams,
          'line_item' => [$lineItem],
        ],
      ];
      $this->contributionID = civicrm_api3('Order', 'create', $orderCreateParams)['id'];

      Payment::create(FALSE)
        ->addValue('contribution_id', $this->contributionID)
        ->addValue('total_amount', $recur['amount'])
        ->addValue('is_send_contribution_notification', FALSE)
        ->addValue('trxn_date', date('YmdHis'))
        ->execute();
    }
  }

  /**
   * Test that we can increase an amount on a recurring contribution
   */
  public function testNoChangeAmount(): void {
    $this->createFixture();
    $this->createOrder();

    // We should not have a template contribution yet
    $preTemplateContribution = Contribution::get(FALSE)
      ->addSelect('*', 'line_item.*')
      ->addJoin('LineItem AS line_item', 'LEFT')
      ->addWhere('contribution_recur_id', '=', $this->recurs[$this->contacts['WilmaUpgrader']])
      ->addWhere('is_template', '=', TRUE)
      ->addOrderBy('id', 'DESC')
      ->execute()
      ->first();
    $this->assertEmpty($preTemplateContribution);

    $preContributionRecur = ContributionRecur::get(FALSE)
      ->addWhere('id', '=', $this->recurs[$this->contacts['WilmaUpgrader']])
      ->execute()
      ->first();
    $this->assertNotEmpty($preContributionRecur);

    // But we should have a completed contribution
    $preContribution = Contribution::get(FALSE)
      ->addWhere('contribution_status_id:name', '=', 'Completed')
      ->addWhere('contribution_recur_id', '=', $this->recurs[$this->contacts['WilmaUpgrader']])
      ->execute()
      ->first();
    $this->assertNotEmpty($preContribution);
    $this->assertEquals($preContributionRecur['amount'], $preContribution['total_amount']);

    $newAmount = 1.0;
    ContributionRecur::updateAmountOnRecur(FALSE)
      ->setAmount($newAmount)
      ->addWhere('id', '=', $this->recurs[$this->contacts['WilmaUpgrader']])
      ->execute()
      ->first();
    $updatedRecur = ContributionRecur::get(FALSE)
      ->addWhere('id', '=', $this->recurs[$this->contacts['WilmaUpgrader']])
      ->execute()
      ->first();
    $templateContribution = Contribution::get(FALSE)
      ->addSelect('*', 'line_item.*')
      ->addJoin('LineItem AS line_item', 'LEFT')
      ->addWhere('contribution_recur_id', '=', $this->recurs[$this->contacts['WilmaUpgrader']])
      ->addWhere('is_template', '=', TRUE)
      ->addOrderBy('id', 'DESC')
      ->execute()
      ->first();
    $this->assertEmpty($templateContribution);
    $paramsToAssertEqual = [
      $newAmount => $updatedRecur['amount'],
    ];
    foreach ($paramsToAssertEqual as $expected => $actual) {
      $this->assertEquals($expected, $actual);
    }
  }

  /**
   * Test that we can increase an amount on a recurring contribution
   */
  public function testIncreaseAmount(): void {
    $this->createFixture();
    $this->createOrder();

    // We should not have a template contribution yet
    $preTemplateContribution = Contribution::get(FALSE)
      ->addSelect('*', 'line_item.*')
      ->addJoin('LineItem AS line_item', 'LEFT')
      ->addWhere('contribution_recur_id', '=', $this->recurs[$this->contacts['WilmaUpgrader']])
      ->addWhere('is_template', '=', TRUE)
      ->addOrderBy('id', 'DESC')
      ->execute()
      ->first();
    $this->assertEmpty($preTemplateContribution);

    $preContributionRecur = ContributionRecur::get(FALSE)
      ->addWhere('id', '=', $this->recurs[$this->contacts['WilmaUpgrader']])
      ->execute()
      ->first();
    $this->assertNotEmpty($preContributionRecur);

    // But we should have a completed contribution
    $preContribution = Contribution::get(FALSE)
      ->addWhere('contribution_status_id:name', '=', 'Completed')
      ->addWhere('contribution_recur_id', '=', $this->recurs[$this->contacts['WilmaUpgrader']])
      ->execute()
      ->first();
    $this->assertNotEmpty($preContribution);
    $this->assertEquals($preContributionRecur['amount'], $preContribution['total_amount']);

    $newAmount = 3.0;
    ContributionRecur::updateAmountOnRecur(FALSE)
      ->setAmount($newAmount)
      ->addWhere('id', '=', $this->recurs[$this->contacts['WilmaUpgrader']])
      ->execute()
      ->first();
    $updatedRecur = ContributionRecur::get(FALSE)
      ->addWhere('id', '=', $this->recurs[$this->contacts['WilmaUpgrader']])
      ->execute()
      ->first();
    $templateContribution = Contribution::get(FALSE)
      ->addSelect('*', 'line_item.*')
      ->addJoin('LineItem AS line_item', 'LEFT')
      ->addWhere('contribution_recur_id', '=', $this->recurs[$this->contacts['WilmaUpgrader']])
      ->addWhere('is_template', '=', TRUE)
      ->addOrderBy('id', 'DESC')
      ->execute()
      ->first();
    $paramsToAssertEqual = [
      $newAmount => $updatedRecur['amount'],
      $newAmount => $templateContribution['total_amount'],
      $newAmount => $templateContribution['line_item.line_total'],
      0.0 => $templateContribution['fee_amount'],
    ];
    foreach ($paramsToAssertEqual as $expected => $actual) {
      $this->assertEquals($expected, $actual);
    }
  }

  /**
   * Test that we can't set a recurring contribution to 0.0
   */
  public function testNewAmountZero(): void {
    $this->createFixture();
    $this->createOrder();

    // We should not have a template contribution yet
    $preTemplateContribution = Contribution::get(FALSE)
      ->addSelect('*', 'line_item.*')
      ->addJoin('LineItem AS line_item', 'LEFT')
      ->addWhere('contribution_recur_id', '=', $this->recurs[$this->contacts['WilmaUpgrader']])
      ->addWhere('is_template', '=', TRUE)
      ->addOrderBy('id', 'DESC')
      ->execute()
      ->first();
    $this->assertEmpty($preTemplateContribution);

    $preContributionRecur = ContributionRecur::get(FALSE)
      ->addWhere('id', '=', $this->recurs[$this->contacts['WilmaUpgrader']])
      ->execute()
      ->first();
    $this->assertNotEmpty($preContributionRecur);

    // But we should have a completed contribution
    $preContribution = Contribution::get(FALSE)
      ->addWhere('contribution_status_id:name', '=', 'Completed')
      ->addWhere('contribution_recur_id', '=', $this->recurs[$this->contacts['WilmaUpgrader']])
      ->execute()
      ->first();
    $this->assertNotEmpty($preContribution);
    $this->assertEquals($preContributionRecur['amount'], $preContribution['total_amount']);

    $newAmount = 0.0;
    $message = '';
    try {
      ContributionRecur::updateAmountOnRecur(FALSE)
        ->setAmount($newAmount)
        ->addWhere('id', '=', $this->recurs[$this->contacts['WilmaUpgrader']])
        ->execute()
        ->first();
    }
    catch (\CRM_Core_Exception $e) {
      $message = $e->getMessage();
    }
    $this->assertEquals('Amount must be greater than 0.0', $message);
  }

}
