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

use Civi\Api4\FinancialType;
use Civi\Api4\OptionValue;
use Civi\Api4\Order;
use Civi\Api4\Payment;
use Civi\Api4\Contribution;
use Civi\Api4\ContributionRecur;

/**
 * Test API4 ContributionRecur::updateAmountOnRecur
 *
 * @group headless
 */
class ContributionRecurUpdateAmountTest extends \CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->createFixture();
    $this->createOrder();
  }

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->contactDelete($this->ids['Contact']['EmberRecur']);
    parent::tearDown();
  }

  /**
   * @var array
   * contactID => recurID
   */
  protected array $recurs;

  /**
   * Populates properties: $this->recurs.
   */
  protected function createFixture(): void {
    $this->individualCreate(['first_name' => 'Ember', 'last_name' => 'Recur'], 'EmberRecur');

    $recur = [
      'currency' => 'USD',
      'contribution_status_id:name' => 'In Progress',
      'financial_type_id:name' => 'Donation',
      'contact_id' => $this->ids['Contact']['EmberRecur'],
      'amount' => 2,
    ];
    $this->recurs = ContributionRecur::save(FALSE)
      ->addRecord($recur)
      ->execute()
      ->indexBy('contact_id')
      ->column('id');
  }

  private function createOrder() {
    $financialTypeID = FinancialType::get(FALSE)
      ->addWhere('name', '=', 'Donation')
      ->execute()
      ->first()['id'];
    $paymentInstrumentID = OptionValue::get(FALSE)
      ->addWhere('option_group_id:name', '=', 'payment_instrument')
      ->execute()
      ->first()['value'];

    foreach ($this->recurs as $contactID => $recurID) {
      $recur = ContributionRecur::get(FALSE)
        ->addWhere('id', '=', $recurID)
        ->execute()
        ->first();

      $orderBAO = new \CRM_Financial_BAO_Order();

      $orderAPI = Order::create(FALSE)
        ->setContributionValues([
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
        ]);

      $lineItem = [
        'line_total' => $recur['amount'],
        'unit_price' => $recur['amount'],
        'price_field_id' => $orderBAO->getDefaultPriceFieldID(),
        'price_field_value_id' => $orderBAO->getDefaultPriceFieldValueID(),
        'financial_type_id' => $financialTypeID,
        'qty' => 1,
      ];

      $orderAPI->addLineItem($lineItem);
      $contributionID = $orderAPI->execute()->single()['id'];

      Payment::create(FALSE)
        ->addValue('contribution_id', $contributionID)
        ->addValue('total_amount', $recur['amount'])
        ->addValue('is_send_contribution_notification', FALSE)
        ->addValue('trxn_date', date('YmdHis'))
        ->execute();
    }
  }

  /**
   * Test that calling updateAmountOnRecur with an unchanged amount
   * has no effect.
   */
  public function testNoChangeAmount(): void {
    // We should not have a template contribution yet
    $preTemplateContribution = Contribution::get(FALSE)
      ->addSelect('*', 'line_item.*')
      ->addJoin('LineItem AS line_item', 'LEFT')
      ->addWhere('contribution_recur_id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->addWhere('is_template', '=', TRUE)
      ->addOrderBy('id', 'DESC')
      ->execute()
      ->first();
    $this->assertEmpty($preTemplateContribution);

    $preContributionRecur = ContributionRecur::get(FALSE)
      ->addWhere('id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->execute()
      ->first();
    $this->assertNotEmpty($preContributionRecur);

    // But we should have a completed contribution
    $preContribution = Contribution::get(FALSE)
      ->addWhere('contribution_status_id:name', '=', 'Completed')
      ->addWhere('contribution_recur_id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->execute()
      ->first();
    $this->assertNotEmpty($preContribution);
    $this->assertEquals($preContributionRecur['amount'], $preContribution['total_amount']);

    // Call updateAmountOnRecur with the same amount.
    $newAmount = 2.0;
    ContributionRecur::updateAmountOnRecur(FALSE)
      ->setAmount($newAmount)
      ->addWhere('id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->execute()
      ->first();
    $updatedRecur = ContributionRecur::get(FALSE)
      ->addWhere('id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->execute()
      ->first();
    $templateContribution = Contribution::get(FALSE)
      ->addSelect('*', 'line_item.*')
      ->addJoin('LineItem AS line_item', 'LEFT')
      ->addWhere('contribution_recur_id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->addWhere('is_template', '=', TRUE)
      ->addOrderBy('id', 'DESC')
      ->execute()
      ->first();
    $this->assertEmpty($templateContribution);
    $this->assertEquals($newAmount, $updatedRecur['amount']);
  }

  /**
   * Test that we can increase an amount on a recurring contribution
   */
  public function testIncreaseAmount(): void {
    // We should not have a template contribution yet
    $preTemplateContribution = Contribution::get(FALSE)
      ->addSelect('*', 'line_item.*')
      ->addJoin('LineItem AS line_item', 'LEFT')
      ->addWhere('contribution_recur_id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->addWhere('is_template', '=', TRUE)
      ->addOrderBy('id', 'DESC')
      ->execute()
      ->first();
    $this->assertEmpty($preTemplateContribution);

    $preContributionRecur = ContributionRecur::get(FALSE)
      ->addWhere('id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->execute()
      ->first();
    $this->assertNotEmpty($preContributionRecur);

    // But we should have a completed contribution
    $preContribution = Contribution::get(FALSE)
      ->addWhere('contribution_status_id:name', '=', 'Completed')
      ->addWhere('contribution_recur_id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->execute()
      ->first();
    $this->assertNotEmpty($preContribution);
    $this->assertEquals($preContributionRecur['amount'], $preContribution['total_amount']);

    $newAmount = 3.0;
    ContributionRecur::updateAmountOnRecur(FALSE)
      ->setAmount($newAmount)
      ->addWhere('id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->execute()
      ->first();
    $updatedRecur = ContributionRecur::get(FALSE)
      ->addWhere('id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->execute()
      ->first();
    $templateContribution = Contribution::get(FALSE)
      ->addSelect('*', 'line_item.*')
      ->addJoin('LineItem AS line_item', 'LEFT')
      ->addWhere('contribution_recur_id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->addWhere('is_template', '=', TRUE)
      ->addOrderBy('id', 'DESC')
      ->execute()
      ->first();
    $paramsToAssertEqual = [
      [$newAmount, $updatedRecur['amount']],
      [$newAmount, $templateContribution['total_amount']],
      [$newAmount, $templateContribution['line_item.line_total']],
      [0.0, $templateContribution['fee_amount']],
    ];
    foreach ($paramsToAssertEqual as [$expected, $actual]) {
      $this->assertEquals($expected, $actual);
    }
  }

  /**
   * Test that we can't set a recurring contribution to 0.0
   */
  public function testNewAmountZero(): void {
    // We should not have a template contribution yet
    $preTemplateContribution = Contribution::get(FALSE)
      ->addSelect('*', 'line_item.*')
      ->addJoin('LineItem AS line_item', 'LEFT')
      ->addWhere('contribution_recur_id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->addWhere('is_template', '=', TRUE)
      ->addOrderBy('id', 'DESC')
      ->execute()
      ->first();
    $this->assertEmpty($preTemplateContribution);

    $preContributionRecur = ContributionRecur::get(FALSE)
      ->addWhere('id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->execute()
      ->first();
    $this->assertNotEmpty($preContributionRecur);

    // But we should have a completed contribution
    $preContribution = Contribution::get(FALSE)
      ->addWhere('contribution_status_id:name', '=', 'Completed')
      ->addWhere('contribution_recur_id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->execute()
      ->first();
    $this->assertNotEmpty($preContribution);
    $this->assertEquals($preContributionRecur['amount'], $preContribution['total_amount']);

    $newAmount = 0.0;
    $message = '';
    try {
      ContributionRecur::updateAmountOnRecur(FALSE)
        ->setAmount($newAmount)
        ->addWhere('id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
        ->execute()
        ->first();
    }
    catch (\CRM_Core_Exception $e) {
      $message = $e->getMessage();
    }
    $this->assertEquals('Amount must be greater than 0.0', $message);
  }

}
