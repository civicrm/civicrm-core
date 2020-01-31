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
 * Class CRM_Event_BAO_AdditionalPaymentTest
 *
 * @group headless
 */
class CRM_Event_BAO_AdditionalPaymentTest extends CiviUnitTestCase {

  /**
   * Set up.
   *
   * @throws \CRM_Core_Exception
   */
  public function setUp() {
    parent::setUp();
    $this->_contactId = $this->individualCreate();
    $event = $this->eventCreate();
    $this->_eventId = $event['id'];
  }

  public function tearDown() {
    $this->eventDelete($this->_eventId);
    $this->quickCleanUpFinancialEntities();
  }

  /**
   * Helper function to record participant with paid contribution.
   *
   * @param int $feeTotal
   * @param int $actualPaidAmt
   * @param array $participantParams
   * @param array $contributionParams
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function addParticipantWithPayment($feeTotal, $actualPaidAmt, $participantParams = [], $contributionParams = []) {
    $priceSetId = $this->eventPriceSetCreate($feeTotal);
    CRM_Price_BAO_PriceSet::addTo('civicrm_event', $this->_eventId, $priceSetId);
    // -- processing priceSet using the BAO
    $lineItems = [];
    $priceSet = CRM_Price_BAO_PriceSet::getSetDetail($priceSetId, TRUE, FALSE);
    $priceSet = CRM_Utils_Array::value($priceSetId, $priceSet);
    $feeBlock = CRM_Utils_Array::value('fields', $priceSet);
    $params['price_2'] = $feeTotal;
    $tempParams = $params;

    CRM_Price_BAO_PriceSet::processAmount($feeBlock,
      $params, $lineItems
    );
    foreach ($lineItems as $lineItemID => $lineItem) {
      $lineItems[$lineItemID]['entity_table'] = 'civicrm_participant';
    }

    $participantParams = array_merge(
      [
        'send_receipt' => 1,
        'is_test' => 0,
        'is_pay_later' => 0,
        'event_id' => $this->_eventId,
        'register_date' => date('Y-m-d') . " 00:00:00",
        'role_id' => 1,
        'status_id' => 14,
        'source' => 'Event_' . $this->_eventId,
        'contact_id' => $this->_contactId,
        'note' => 'Note added for Event_' . $this->_eventId,
        'fee_level' => 'Price_Field - 55',
      ],
      $participantParams
    );

    // create participant contribution with partial payment
    $contributionParams = array_merge(
      [
        'total_amount' => $feeTotal,
        'source' => 'Fall Fundraiser Dinner: Offline registration',
        'currency' => 'USD',
        'receipt_date' => 'today',
        'contact_id' => $this->_contactId,
        'financial_type_id' => 4,
        'payment_instrument_id' => 4,
        'contribution_status_id' => 'Pending',
        'receive_date' => 'today',
        'api.Payment.create' => ['total_amount' => $actualPaidAmt],
        'line_items' => [['line_item' => $lineItems, 'params' => $participantParams]],
      ],
      $contributionParams
    );

    $contribution = $this->callAPISuccess('Order', 'create', $contributionParams);
    $participant = $this->callAPISuccessGetSingle('participant', []);
    $this->callAPISuccessGetSingle('ParticipantPayment', ['contribution_id' => $contribution['id'], 'participant_id' => $participant['id']]);

    return [
      'participant' => $participant,
      'contribution' => $contribution['values'][$contribution['id']],
      'lineItem' => $lineItems,
      'params' => $tempParams,
      'feeBlock' => $feeBlock,
      'priceSetId' => $priceSetId,
    ];
  }

  /**
   * See https://lab.civicrm.org/dev/core/issues/153
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function testPaymentWithCustomPaymentInstrument() {
    $feeAmt = 100;
    $amtPaid = 0;

    // Create undetermined Payment Instrument
    $paymentInstrumentID = $this->createPaymentInstrument(['label' => 'Undetermined'], 'Accounts Receivable');

    // record pending payment for an event
    $result = $this->addParticipantWithPayment(
      $feeAmt, $amtPaid,
      ['is_pay_later' => 1],
      [
        'total_amount' => 100,
        'payment_instrument_id' => $paymentInstrumentID,
        'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'),
      ]
    );
    $contributionID = $result['contribution']['id'];

    // check payment info
    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($result['participant']['id'], 'event');
    $this->assertEquals($feeAmt, round($paymentInfo['total']), 'Total amount recorded is not correct');
    $this->assertEquals($amtPaid, round($paymentInfo['paid']), 'Amount paid is not correct');
    $this->assertEquals($feeAmt, round($paymentInfo['balance']), 'Balance amount is not proper');
    $this->assertEquals('Pending Label**', $paymentInfo['contribution_status'], 'Contribution status is not correct');

    // make additional payment via 'Record Payment' form
    $form = new CRM_Contribute_Form_AdditionalPayment();
    $submitParams = [
      'contact_id' => $result['contribution']['contact_id'],
      'contribution_id' => $contributionID,
      'total_amount' => 100,
      'currency' => 'USD',
      'trxn_date' => '2017-04-11 13:05:11',
      'payment_processor_id' => 0,
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check'),
      'check_number' => '#123',
    ];
    $form->cid = $result['contribution']['contact_id'];
    $form->testSubmit($submitParams);

    // check payment info again and see if the payment is completed
    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($result['participant']['id'], 'event');
    $this->assertEquals(round($paymentInfo['total']), $feeAmt, 'Total amount recorded is not proper');
    $this->assertEquals(round($paymentInfo['paid']), $feeAmt, 'Amount paid is not proper');
    $this->assertEquals(round($paymentInfo['balance']), 0, 'Balance amount is not proper');
    $this->assertEquals($paymentInfo['contribution_status'], 'Completed', 'Contribution status is not proper');

    $this->callAPISuccess('OptionValue', 'delete', ['id' => $paymentInstrumentID]);
  }

  /**
   * CRM-13964
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddPartialPayment() {
    $feeAmt = 100;
    $amtPaid = 60;
    $balance = $feeAmt - $amtPaid;
    $result = $this->addParticipantWithPayment($feeAmt, $amtPaid);
    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($result['participant']['id'], 'event');

    // amount checking
    $this->assertEquals($feeAmt, round($paymentInfo['total']), 'Total amount recorded is not correct');
    $this->assertEquals(round($paymentInfo['paid']), $amtPaid, 'Amount paid is not correct');
    $this->assertEquals(round($paymentInfo['balance']), $balance, 'Balance amount is not correct');

    // @todo fix Payment.create so it transitions appropriately & uncomment here.
    // $this->assertEquals('Partially Paid', CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $result['contribution']['contribution_status_id']));
    // $this->assertEquals('Partially Paid', CRM_Core_PseudoConstant::getName('CRM_Event_BAO_Participant', 'participant_status_id', $result['participant']['participant_status_id']));
  }

  /**
   * Test owed/refund info is listed on view payments.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function testTransactionInfo() {
    $feeAmt = 100;
    $amtPaid = 80;
    $result = $this->addParticipantWithPayment($feeAmt, $amtPaid);
    $contributionID = $result['contribution']['id'];

    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $contributionID,
      'total_amount' => 20,
      'payment_instrument_id' => 3,
      'participant_id' => $result['participant']['id'],
    ]);

    //Change selection to a lower amount.
    $params['price_2'] = 50;
    CRM_Price_BAO_LineItem::changeFeeSelections($params, $result['participant']['id'], 'participant', $contributionID, $result['feeBlock'], $result['lineItem']);

    $this->callAPISuccess('Payment', 'create', [
      'total_amount' => -50,
      'contribution_id' => $contributionID,
      'participant_id' => $result['participant']['id'],
      'payment_instrument_id' => 3,
    ]);
    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($result['participant']['id'], 'event', TRUE);
    $transaction = $paymentInfo['transaction'];

    //Assert all transaction(owed and refund) are listed on view payments.
    $this->assertEquals(count($transaction), 3, 'Transaction Details is not proper');
    $this->assertEquals($transaction[0]['total_amount'], 80.00);
    $this->assertEquals($transaction[0]['status'], 'Completed');

    $this->assertEquals($transaction[1]['total_amount'], 20.00);
    $this->assertEquals($transaction[1]['status'], 'Completed');

    $this->assertEquals($transaction[2]['total_amount'], -50.00);
    $this->assertEquals($transaction[2]['status'], 'Refunded Label**');
  }

}
