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

use Civi\Api4\Contribution;
use Civi\Api4\EntityFinancialAccount;

/**
 * Class CRM_Event_BAO_AdditionalPaymentTest
 *
 * @group headless
 */
class CRM_Event_BAO_AdditionalPaymentTest extends CiviUnitTestCase {

  /**
   * Contact ID.
   *
   * @var int
   */
  protected $contactID;

  /**
   * Event ID.
   *
   * @var int
   */
  protected $eventID;

  /**
   * Set up.
   */
  public function setUp(): void {
    parent::setUp();
    $this->contactID = $this->individualCreate();
    $event = $this->eventCreatePaid();
    $this->eventID = $event['id'];
  }

  /**
   * Cleanup after test.
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Helper function to record participant with paid contribution.
   *
   * @param float $actualPaidAmt
   * @param array $participantParams
   * @param array $contributionParams
   *
   * @return array
   */
  protected function addParticipantWithPayment(float $actualPaidAmt, array $participantParams = [], array $contributionParams = []): array {
    // -- processing priceSet using the BAO
    $lineItems = [];

    $participantParams = array_merge(
      [
        'send_receipt' => 1,
        'is_test' => 0,
        'is_pay_later' => 0,
        'event_id' => $this->eventID,
        'register_date' => date('Y-m-d') . ' 00:00:00',
        'role_id' => 1,
        'status_id' => 14,
        'source' => 'Event_' . $this->eventID,
        'contact_id' => $this->contactID,
        'note' => 'Note added for Event_' . $this->eventID,
        'fee_level' => 'Price_Field - 55',
      ],
      $participantParams
    );

    // create participant contribution with partial payment
    $contributionParams = array_merge(
      [
        'source' => 'Fall Fundraiser Dinner: Offline registration',
        'currency' => 'USD',
        'receipt_date' => 'today',
        'contact_id' => $this->contactID,
        'financial_type_id' => 4,
        'payment_instrument_id' => 4,
        'contribution_status_id' => 'Pending',
        'receive_date' => 'today',
        'api.Payment.create' => ['total_amount' => $actualPaidAmt],
        'line_items' => [
          [
            'line_item' => [
              [
                'entity_table' => 'civicrm_participant',
                'price_field_value_id' => $this->ids['PriceFieldValue']['PaidEvent_student'],
              ],
            ],
            'params' => $participantParams,
          ],
        ],
      ],
      $contributionParams
    );

    $contribution = $this->callAPISuccess('Order', 'create', $contributionParams);
    $this->ids['Contribution']['PaidEvent'] = $contribution['id'];
    $participant = $this->callAPISuccessGetSingle('Participant', []);
    $this->callAPISuccessGetSingle('ParticipantPayment', ['contribution_id' => $contribution['id'], 'participant_id' => $participant['id']]);

    // Check it is correct.
    try {
      $contribution = Contribution::get(FALSE)
        ->addWhere('id', '=', $contribution['id'])
        ->addSelect('paid_amount', 'total_amount', 'balance_amount', 'contribution_status_id:label')
        ->execute()
        ->first();

      $this->assertEquals(100, $contribution['total_amount']);
      $this->assertEquals($actualPaidAmt, $contribution['paid_amount'], 'Amount paid is not correct');
      $this->assertEquals(100 - $actualPaidAmt, $contribution['balance_amount'], 'Balance is not correct');
      if ($actualPaidAmt > 0) {
        $this->assertEquals('Partially paid', $contribution['contribution_status_id:label'], 'Contribution status is not correct');
      }
      else {
        $this->assertEquals('Pending Label**', $contribution['contribution_status_id:label'], 'Contribution status is not correct');
      }
    }
    catch (CRM_Core_Exception $e) {
      $this->fail('Failed to retrieve contribution ' . $e->getMessage());
    }
    return [
      'participant' => $participant,
      'contribution' => $this->callAPISuccessGetSingle('Contribution', ['id' => $contribution['id']]),
    ];
  }

  /**
   * See https://lab.civicrm.org/dev/core/issues/153
   *
   * @throws \CRM_Core_Exception
   */
  public function testPaymentWithCustomPaymentInstrument(): void {
    // Create undetermined Payment Instrument
    $paymentInstrumentID = $this->createPaymentInstrument(['label' => 'Undetermined'], 'Accounts Receivable');

    // record pending payment for an event
    $result = $this->addParticipantWithPayment(
      0,
      ['is_pay_later' => 1],
      [
        'total_amount' => 100,
        'payment_instrument_id' => $paymentInstrumentID,
        'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'),
      ]
    );
    $contributionID = $this->ids['Contribution']['PaidEvent'];

    $_REQUEST['id'] = $contributionID;
    // make additional payment via 'Record Payment' form
    $form = $this->getFormObject('CRM_Contribute_Form_AdditionalPayment', [
      'contact_id' => $result['contribution']['contact_id'],
      'contribution_id' => $contributionID,
      'total_amount' => 100,
      'currency' => 'USD',
      'trxn_date' => '2017-04-11 13:05:11',
      'payment_processor_id' => 0,
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check'),
      'check_number' => '#123',
    ]);
    $form->preProcess();
    $form->buildForm();
    $form->postProcess();

    $contribution = Contribution::get(FALSE)
      ->addWhere('id', '=', $contributionID)
      ->addSelect('paid_amount', 'total_amount', 'balance_amount', 'contribution_status_id:label')
      ->execute()
      ->first();

    $this->assertEquals(100, $contribution['total_amount']);
    $this->assertEquals(100, $contribution['paid_amount'], 'Amount paid is not correct');
    $this->assertEquals(0, $contribution['balance_amount'], 'Balance is not correct');
    $this->assertEquals('Completed', $contribution['contribution_status_id:label'], 'Contribution status is not correct');

    $this->callAPISuccess('OptionValue', 'delete', ['id' => $paymentInstrumentID]);
  }

  /**
   * Create Payment Instrument.
   *
   * @param array $params
   * @param string $financialAccountName
   *
   * @return int
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function createPaymentInstrument(array $params = [], string $financialAccountName = 'Donation'): int {
    $params = array_merge([
      'label' => 'Payment Instrument - new',
      'option_group_id' => 'payment_instrument',
      'is_active' => 1,
    ], $params);
    $newPaymentInstrument = $this->callAPISuccess('OptionValue', 'create', $params)['id'];

    $relationTypeID = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Asset Account is' "));

    $financialAccountParams = [
      'entity_table' => 'civicrm_option_value',
      'entity_id' => $newPaymentInstrument,
      'account_relationship' => $relationTypeID,
      'financial_account_id' => $this->callAPISuccess('FinancialAccount', 'getValue', ['name' => $financialAccountName, 'return' => 'id']),
    ];
    EntityFinancialAccount::create()->setValues($financialAccountParams)->execute();

    return CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', $params['label']);
  }

  /**
   * @see https://issues.civicrm.org/jira/browse/CRM-13964
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddPartialPayment(): void {
    // First add an unrelated contribution since otherwise the contribution and
    // participant ids are all 1 so they might match by accident.
    $this->contributionCreate(['contact_id' => $this->individualCreate([], 0, TRUE)]);

    $amtPaid = (float) 60;
    $result = $this->addParticipantWithPayment($amtPaid);
    $this->assertEquals('Partially paid', $result['participant']['participant_status']);

    // Check the record payment link has the right id and that it doesn't
    // match by accident.
    $this->assertNotEquals($this->ids['Contribution']['PaidEvent'], $result['participant']['id']);
    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($this->ids['Contribution']['PaidEvent']);
    $this->assertEquals('Record Payment', $paymentInfo['payment_links'][0]['title']);
    $this->assertEquals($this->ids['Contribution']['PaidEvent'], $paymentInfo['payment_links'][0]['qs']['id']);
  }

  /**
   * Test owed/refund info is listed on view payments.
   *
   * @throws \CRM_Core_Exception
   */
  public function testTransactionInfo(): void {
    $amtPaid = 80;
    $result = $this->addParticipantWithPayment($amtPaid);
    $contributionID = $result['contribution']['id'];

    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $contributionID,
      'total_amount' => 20,
      'payment_instrument_id' => 3,
      'participant_id' => $result['participant']['id'],
    ]);

    //Change selection to a lower amount.
    $params['price_2'] = $this->ids['PriceFieldValue']['PaidEvent_student_early'];
    $priceSet = CRM_Price_BAO_PriceSet::getSetDetail($this->ids['PriceSet']['PaidEvent']);
    $priceSet = $priceSet[$this->ids['PriceSet']['PaidEvent']] ?? NULL;
    $feeBlock = $priceSet['fields'] ?? NULL;
    CRM_Price_BAO_LineItem::changeFeeSelections($params, $result['participant']['id'], 'participant', $contributionID);

    $this->callAPISuccess('Payment', 'create', [
      'total_amount' => -50,
      'contribution_id' => $contributionID,
      'participant_id' => $result['participant']['id'],
      'payment_instrument_id' => 3,
    ]);
    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($result['participant']['id'], 'event', TRUE);
    $transaction = $paymentInfo['transaction'];

    // Assert all transaction(owed and refund) are listed on view payments.
    $this->assertCount(3, $transaction, 'Transaction Details is not proper');
    $this->assertEquals(80.00, $transaction[0]['total_amount']);
    $this->assertEquals('Completed', $transaction[0]['status']);

    $this->assertEquals(20.00, $transaction[1]['total_amount']);
    $this->assertEquals('Completed', $transaction[1]['status']);

    $this->assertEquals(-50.00, $transaction[2]['total_amount']);
    $this->assertEquals('Refunded Label**', $transaction[2]['status']);
  }

}
