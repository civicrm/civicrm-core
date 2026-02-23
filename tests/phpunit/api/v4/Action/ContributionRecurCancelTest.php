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
use Civi\Api4\ContributionRecur;

/**
 * Test API4 ContributionRecur::updateAmountOnRecur
 *
 * @group headless
 */
class ContributionRecurCancelTest extends \CiviUnitTestCase {

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
   * Test that cancelSubscription updates the recur correctly.
   */
  public function testCancelNoNotify(): void {
    $preContributionRecur = ContributionRecur::get(FALSE)
      ->addWhere('id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->execute()
      ->first();
    $this->assertNotEmpty($preContributionRecur);
    $this->assertEmpty($preContributionRecur['cancel_date']);

    // Call cancelSubscription
    ContributionRecur::cancelSubscription(FALSE)
      ->setNotifyPaymentProcessor(FALSE)
      ->setCancelReason('I want to cancel')
      ->addWhere('id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->execute()
      ->first();

    $updatedRecur = ContributionRecur::get(FALSE)
      ->addSelect('cancel_date', 'cancel_reason', 'contribution_status_id:name')
      ->addWhere('id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->execute()
      ->first();

    $this->assertEquals('Cancelled', $updatedRecur['contribution_status_id:name']);
    $this->assertEquals('I want to cancel', $updatedRecur['cancel_reason']);
    $this->assertNotEmpty($updatedRecur['cancel_date']);
  }

  /**
   * Test that cancelSubscription updates the recur correctly.
   */
  public function testCancelNotify(): void {
    $preContributionRecur = ContributionRecur::get(FALSE)
      ->addWhere('id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->execute()
      ->first();
    $this->assertNotEmpty($preContributionRecur);
    $this->assertEmpty($preContributionRecur['cancel_date']);

    $paymentProcessorID = $this->dummyProcessorCreate()->getID();
    ContributionRecur::update(FALSE)
      ->addWhere('id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->addValue('payment_processor_id', $paymentProcessorID)
      ->addValue('processor_id', 'test-processor-id')
      ->execute();

    // Call cancelSubscription
    $result = ContributionRecur::cancelSubscription(FALSE)
      ->setNotifyPaymentProcessor(TRUE)
      ->setCancelReason('I want to cancel')
      ->addWhere('id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->execute()
      ->first();
    $this->assertEquals('Recurring contribution cancelled', $result['message']);

    $updatedRecur = ContributionRecur::get(FALSE)
      ->addSelect('cancel_date', 'cancel_reason', 'contribution_status_id:name', 'processor_id')
      ->addWhere('id', '=', $this->recurs[$this->ids['Contact']['EmberRecur']])
      ->execute()
      ->first();

    $this->assertEquals('Cancelled', $updatedRecur['contribution_status_id:name']);
    $this->assertEquals('I want to cancel', $updatedRecur['cancel_reason']);
    $this->assertNotEmpty($updatedRecur['cancel_date']);
    $this->assertEquals('test-processor-id', $updatedRecur['processor_id']);
  }

}
