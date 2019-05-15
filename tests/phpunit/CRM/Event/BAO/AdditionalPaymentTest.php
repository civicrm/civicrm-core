<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Event_BAO_AdditionalPaymentTest
 * @group headless
 */
class CRM_Event_BAO_AdditionalPaymentTest extends CiviUnitTestCase {

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
   * @throws Exception
   */
  protected function addParticipantWithPayment($feeTotal, $actualPaidAmt, $participantParams = [], $contributionParams = []) {
    $priceSetId = $this->eventPriceSetCreate($feeTotal);
    CRM_Price_BAO_PriceSet::addTo('civicrm_event', $this->_eventId, $priceSetId);

    // create participant record
    $eventId = $this->_eventId;
    $participantParams = array_merge(
      [
        'send_receipt' => 1,
        'is_test' => 0,
        'is_pay_later' => 0,
        'event_id' => $eventId,
        'register_date' => date('Y-m-d') . " 00:00:00",
        'role_id' => 1,
        'status_id' => 14,
        'source' => 'Event_' . $eventId,
        'contact_id' => $this->_contactId,
        'note' => 'Note added for Event_' . $eventId,
        'fee_level' => 'Price_Field - 55',
      ],
      $participantParams
    );
    $participant = $this->callAPISuccess('participant', 'create', $participantParams);
    $this->callAPISuccessGetSingle('participant', array('id' => $participant['id']));
    // create participant contribution with partial payment
    $contributionParams = array_merge(
      [
        'total_amount' => $actualPaidAmt,
        'source' => 'Fall Fundraiser Dinner: Offline registration',
        'currency' => 'USD',
        'receipt_date' => date('Y-m-d') . " 00:00:00",
        'contact_id' => $this->_contactId,
        'financial_type_id' => 4,
        'payment_instrument_id' => 4,
        'contribution_status_id' => 1,
        'receive_date' => date('Y-m-d') . " 00:00:00",
        'skipLineItem' => 1,
        'partial_payment_total' => $feeTotal,
        'partial_amount_to_pay' => $actualPaidAmt,
      ],
      $contributionParams
    );

    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    $contributionId = $contribution['id'];
    $participant = $this->callAPISuccessGetSingle('participant', array('id' => $participant['id']));

    // add participant payment entry
    $this->callAPISuccess('participant_payment', 'create', array(
      'participant_id' => $participant['id'],
      'contribution_id' => $contributionId,
    ));

    // -- processing priceSet using the BAO
    $lineItem = array();
    $priceSet = CRM_Price_BAO_PriceSet::getSetDetail($priceSetId, TRUE, FALSE);
    $priceSet = CRM_Utils_Array::value($priceSetId, $priceSet);
    $feeBlock = CRM_Utils_Array::value('fields', $priceSet);
    $params['price_2'] = $feeTotal;
    $tempParams = $params;
    $templineItems = $lineItem;
    CRM_Price_BAO_PriceSet::processAmount($feeBlock,
      $params, $lineItem
    );
    $lineItemVal[$priceSetId] = $lineItem;
    CRM_Price_BAO_LineItem::processPriceSet($participant['id'], $lineItemVal, $this->getContributionObject($contributionId), 'civicrm_participant');

    return array(
      'participant' => $participant,
      'contribution' => $contribution['values'][$contribution['id']],
      'lineItem' => $templineItems,
      'params' => $tempParams,
      'feeBlock' => $feeBlock,
      'priceSetId' => $priceSetId,
    );
  }

  /**
   * See https://lab.civicrm.org/dev/core/issues/153
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
    $this->assertEquals(round($paymentInfo['total']), $feeAmt, 'Total amount recorded is not proper');
    $this->assertEquals(round($paymentInfo['paid']), $amtPaid, 'Amount paid is not proper');
    $this->assertEquals(round($paymentInfo['balance']), $feeAmt, 'Balance amount is not proper');
    $this->assertEquals($paymentInfo['contribution_status'], 'Pending', 'Contribution status is not proper');

    // make additional payment via 'Record Payment' form
    $form = new CRM_Contribute_Form_AdditionalPayment();
    $submitParams = array(
      'contact_id' => $result['contribution']['contact_id'],
      'contribution_id' => $contributionID,
      'total_amount' => 100,
      'currency' => 'USD',
      'trxn_date' => '2017-04-11 13:05:11',
      'payment_processor_id' => 0,
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check'),
      'check_number' => '#123',
    );
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
   */
  public function testAddPartialPayment() {
    $feeAmt = 100;
    $amtPaid = 60;
    $balance = $feeAmt - $amtPaid;
    $result = $this->addParticipantWithPayment($feeAmt, $amtPaid);
    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($result['participant']['id'], 'event');

    // amount checking
    $this->assertEquals(round($paymentInfo['total']), $feeAmt, 'Total amount recorded is not proper');
    $this->assertEquals(round($paymentInfo['paid']), $amtPaid, 'Amount paid is not proper');
    $this->assertEquals(round($paymentInfo['balance']), $balance, 'Balance amount is not proper');

    // status checking
    $this->assertEquals($result['participant']['participant_status_id'], 14, 'Status record is not proper for participant');
    $this->assertEquals($result['contribution']['contribution_status_id'], 8, 'Status record is not proper for contribution');
  }

  /**
   * Test owed/refund info is listed on view payments.
   */
  public function testTransactionInfo() {
    $feeAmt = 100;
    $amtPaid = 80;
    $result = $this->addParticipantWithPayment($feeAmt, $amtPaid);
    $contributionID = $result['contribution']['id'];

    //Complete the partial payment.
    $submittedValues = array(
      'total_amount' => 20,
      'payment_instrument_id' => 3,
    );
    CRM_Contribute_BAO_Contribution::recordAdditionalPayment($contributionID, $submittedValues, 'owed', $result['participant']['id']);

    //Change selection to a lower amount.
    $params['price_2'] = 50;
    CRM_Price_BAO_LineItem::changeFeeSelections($params, $result['participant']['id'], 'participant', $contributionID, $result['feeBlock'], $result['lineItem']);

    //Record a refund of the remaining amount.
    $submittedValues['total_amount'] = 50;
    CRM_Contribute_BAO_Contribution::recordAdditionalPayment($contributionID, $submittedValues, 'refund', $result['participant']['id']);
    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($result['participant']['id'], 'event', TRUE);
    $transaction = $paymentInfo['transaction'];

    //Assert all transaction(owed and refund) are listed on view payments.
    $this->assertEquals(count($transaction), 3, 'Transaction Details is not proper');
    $this->assertEquals($transaction[0]['total_amount'], 80.00);
    $this->assertEquals($transaction[0]['status'], 'Completed');

    $this->assertEquals($transaction[1]['total_amount'], 20.00);
    $this->assertEquals($transaction[1]['status'], 'Completed');

    $this->assertEquals($transaction[2]['total_amount'], -50.00);
    $this->assertEquals($transaction[2]['status'], 'Refunded');
  }

}
