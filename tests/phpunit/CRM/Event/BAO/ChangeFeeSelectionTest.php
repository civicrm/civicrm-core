<?php

use Civi\Api4\Contribution;
use Civi\Api4\EntityFinancialTrxn;
use Civi\Api4\FinancialItem;
use Civi\Api4\FinancialTrxn;
use Civi\Api4\LineItem;
use Civi\Api4\Participant;
use Civi\Api4\ParticipantStatusType;
use Civi\Api4\Order;
use Civi\Api4\Payment;

/**
 * Class CRM_Event_BAO_AdditionalPaymentTest
 *
 * @group headless
 */
class CRM_Event_BAO_ChangeFeeSelectionTest extends CiviUnitTestCase {

  use CRMTraits_Financial_PriceSetTrait;

  protected $_cheapFee = '50.00';

  protected $_expensiveFee = '100.00';

  protected $_veryExpensive = '300.00';

  protected $_noFee = 0;

  /**
   * Set up for test.
   */
  public function setUp(): void {
    parent::setUp();
    $this->individualCreate();
    $this->eventCreatePaid();
  }

  /**
   * Clean up after test.
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    ParticipantStatusType::update()
      ->addValue('is_active', FALSE)
      ->addWhere('name', '=', 'On waitlist')
      ->execute();
    parent::tearDown();
  }

  /**
   * Get the total for the invoice.
   *
   * @param int $contributionID
   *
   * @return float
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  private function contributionInvoice(int $contributionID) {
    $query = "
         SELECT SUM(line_total) total
         FROM   civicrm_line_item
         WHERE  contribution_id = {$contributionID}";
    $dao = CRM_Core_DAO::executeQuery($query);

    $this->assertTrue($dao->fetch(), 'Succeeded retrieving invoice total');
    return $dao->total;
  }

  /**
   * Get the total income from the participant record.
   *
   * @param int $participantID
   *
   * @return int
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  private function totalIncome(int $participantID): int {
    $query = "
      SELECT SUM(fi.amount) total
      FROM civicrm_financial_item fi
        INNER JOIN civicrm_line_item li ON li.id = fi.entity_id AND fi.entity_table = 'civicrm_line_item'
      WHERE li.entity_table = 'civicrm_participant' AND li.entity_id = {$participantID}
    ";
    $dao = CRM_Core_DAO::executeQuery($query);

    $this->assertTrue($dao->fetch(), 'Succeeded retrieving total Income');
    return $dao->total;
  }

  /**
   * Check the relevant entity balances.
   *
   * @param float $amount
   */
  private function balanceCheck(float $amount): void {
    $this->assertEquals($amount, $this->contributionInvoice($this->ids['Contribution']['order']), "Invoice must a total of $amount");
    $this->assertEquals($amount, $this->totalIncome($this->ids['Participant']['order']), "The recorded income must be $amount ");
  }

  /**
   * Prepare records for editing.
   *
   * @param null $actualPaidAmt
   *
   * @throws \CRM_Core_Exception
   */
  public function registerParticipantAndPay($actualPaidAmt = NULL): void {
    $actualPaidAmt = $actualPaidAmt ?: $this->_expensiveFee;
    $lineItems = CRM_Price_BAO_LineItem::buildLineItemsForSubmittedPriceField(['price_' . $this->ids['PriceField']['PaidEvent'] => $this->getExpensiveValueID()]);
    $orderParams = [
      'total_amount' => $this->_expensiveFee,
      'source' => 'Test set with information',
      'currency' => 'USD',
      'receipt_date' => date('Y-m-d') . ' 00:00:00',
      'contact_id' => $this->ids['Contact']['individual_0'],
      'financial_type_id' => $this->getFinancialTypeID('Event Fee'),
      'payment_instrument_id' => 4,
      'contribution_status_id' => 'Pending',
      'receive_date' => date('Y-m-d') . ' 00:00:00',
      'line_items' => [],
      'api.Payment.create' => [
        'total_amount' => $actualPaidAmt,
        'is_send_contribution_notification' => FALSE,
      ],
    ];
    foreach ($lineItems as $lineItem) {
      $orderParams['line_items'][] = [
        'line_item' => [array_merge($lineItem, ['entity_table' => 'civicrm_participant'])],
        'params' => [
          'send_receipt' => 1,
          'is_pay_later' => 0,
          'event_id' => $this->getEventID(),
          'register_date' => date('Y-m-d') . ' 00:00:00',
          'role_id' => 1,
          'status_id' => $this->getParticipantStatusID('Registered'),
          'source' => 'Event_' . $this->getEventID(),
          'contact_id' => $this->ids['Contact']['individual_0'],
        ],
      ];
    }

    $order = $this->callAPISuccess('Order', 'create', $orderParams);
    $this->ids['Contribution']['order'] = $order['id'];

    $this->ids['Participant']['order'] = $this->callAPISuccess('participant_payment', 'getvalue', [
      'return' => 'participant_id',
      'contribution_id' => $this->ids['Contribution']['order'],
    ]);
    $this->balanceCheck($this->_expensiveFee);
    $this->assertEquals(($this->_expensiveFee - $actualPaidAmt), CRM_Contribute_BAO_Contribution::getContributionBalance($this->ids['Contribution']['order']));

  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testCRM19273(): void {
    $this->registerParticipantAndPay();

    $this->submitForm($this->getCheapFeeID());
    $this->balanceCheck($this->_cheapFee);

    $this->submitForm($this->getExpensiveValueID());

    $this->balanceCheck($this->_expensiveFee);

    $this->submitForm($this->getVeryExpensiveID());
    $this->balanceCheck($this->_veryExpensive);
  }

  /**
   * CRM-21245: Test that Contribution status doesn't changed to 'Pending Refund' from 'Partially Paid' if the partially paid amount is lower then newly selected fee amount
   *
   * @throws \CRM_Core_Exception
   */
  public function testCRM21245(): void {
    $this->registerParticipantAndPay(50);
    $partiallyPaidContributionStatus = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Partially paid');
    $this->assertEquals($this->callAPISuccessGetValue('Contribution', ['id' => $this->ids['Contribution']['order'], 'return' => 'contribution_status_id']), $partiallyPaidContributionStatus);

    $this->submitForm($this->getVeryExpensiveID());
    $this->assertEquals($this->callAPISuccessGetValue('Contribution', ['id' => $this->ids['Contribution']['order'], 'return' => 'contribution_status_id']), $partiallyPaidContributionStatus);
  }

  /**
   * Test that proper financial items are recorded for cancelled line items
   *
   * @throws \CRM_Core_Exception
   */
  public function testCRM20611(): void {
    $this->registerParticipantAndPay();
    $actualPaidAmount = 100;
    $this->submitForm($this->getExpensiveValueID());
    $this->balanceCheck($this->_expensiveFee);
    $contributionBalance = ($this->_expensiveFee - $actualPaidAmount);
    $this->assertEquals($contributionBalance, CRM_Contribute_BAO_Contribution::getContributionBalance($this->ids['Contribution']['order']));

    $this->submitForm($this->getCheapFeeID());
    $this->balanceCheck($this->_cheapFee);
    $contributionBalance = ($this->_cheapFee - $actualPaidAmount);
    $this->assertEquals($contributionBalance, CRM_Contribute_BAO_Contribution::getContributionBalance($this->ids['Contribution']['order']));

    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $this->ids['Contribution']['order'],
      'total_amount' => -300,
      'payment_instrument_id' => 3,
      'participant_id' => $this->ids['Participant']['order'],
    ]);
    $contributionBalance += 300;
    $this->assertEquals($contributionBalance, CRM_Contribute_BAO_Contribution::getContributionBalance($this->ids['Contribution']['order']));

    // retrieve the cancelled line-item information
    $cancelledLineItem = $this->callAPISuccessGetSingle('LineItem', [
      'entity_table' => 'civicrm_participant',
      'entity_id' => $this->ids['Participant']['order'],
      'qty' => 0,
    ]);
    // retrieve the related financial lin-items
    $financialItems = $this->callAPISuccess('FinancialItem', 'Get', [
      'entity_id' => $cancelledLineItem['id'],
      'entity_table' => 'civicrm_line_item',
    ]);
    $this->assertEquals(2, $financialItems['count'], 'Financial Items for Cancelled fee is not proper');

    $expectedAmount = 100.00;
    foreach ($financialItems['values'] as $financialItem) {
      $this->assertEquals($expectedAmount, $financialItem['amount']);
      $this->assertNotEmpty($financialItem['financial_account_id']);
      $expectedAmount = -$expectedAmount;
    }
  }

  /**
   * Test to ensure that correct financial records are entered on text price field fee change on event registration
   *
   * @throws \CRM_Core_Exception
   */
  public function testCRM21513(): void {
    $textPriceFieldID = $this->createTestEntity('PriceField', [
      'price_set_id' => $this->getPriceSetID('PaidEvent'),
      'label' => 'Text Price Field',
      'name' => 'text_price_field',
      'html_type' => 'Text',
      'is_enter_qty' => 1,
    ], 'textPriceField')['id'];

    $textPriceFieldValueID = $this->createTestEntity('PriceFieldValue', [
      'financial_type_id:name' => 'Event Fee',
      'price_field_id' => $textPriceFieldID,
      'amount' => 10,
      'label' => 'ten',
    ], 'textPriceFieldValue')['id'];

    // CASE 1: Choose text price qty 1 (x$10 = $10 amount)
    $contribution = Order::create(FALSE)
      ->setContributionValues([
        'contact_id' => $this->ids['Contact']['individual_0'],
        'currency' => 'USD',
        'financial_type_id' => $this->getFinancialTypeID('Event Fee'),
        'payment_instrument_id' => 4,
        'contribution_status_id:name' => 'Pending',
        'source' => 'Test set with information',
        'receive_date' => date('Y-m-d') . ' 00:00:00',
      ])
      ->addLineItem([
        'entity_table' => 'civicrm_participant',
        'entity_id.event_id' => $this->getEventID(),
        'entity_id.contact_id' => $this->ids['Contact']['individual_0'],
        'entity_id.status_id' => $this->getParticipantStatusID('Registered'),
        'entity_id.role_id' => 1,
        'entity_id.register_date' => date('Y-m-d') . ' 00:00:00',
        'entity_id.source' => 'Event_' . $this->getEventID(),
        'price_field_value_id' => $textPriceFieldValueID,
        'qty' => 1,
      ])
      ->execute()->single();
    $this->ids['Contribution']['order'] = $contribution['id'];
    $this->ids['Participant']['order'] = LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->execute()->single()['entity_id'];

    // CASE 2: Choose text price qty 3 (x$10 = $30 amount)
    $lineItems = $this->getParticipantLineItems();
    $lineItemIDs = [];
    foreach ($lineItems as $lineItem) {
      $lineItemIDs[] = $lineItem['id'];
    }
    $this->submitForm(NULL, ['price_' . $textPriceFieldID => 3]);

    // CASE 3: Choose text price qty 2 (x$10 = $20 amount)
    $this->submitForm(NULL, ['price_' . $textPriceFieldID => 2]);
    $financialItems = $this->callAPISuccess('FinancialItem', 'Get', [
      'entity_table' => 'civicrm_line_item',
      'entity_id' => ['IN' => $lineItemIDs],
      'sequential' => 1,
    ]);

    $unpaidStatus = CRM_Core_PseudoConstant::getKey('CRM_Financial_DAO_FinancialItem', 'status_id', 'Unpaid');
    $expectedResults = [
      [
        // when qty 1 is used
        'amount' => 10.00,
        'status_id' => $unpaidStatus,
        'entity_table' => 'civicrm_line_item',
        'entity_id' => $lineItemIDs[0],
      ],
      [
        // when qty 3 is used, add the surplus amount i.e. $30 - $10 = $20
        'amount' => 20.00,
        'status_id' => $unpaidStatus,
        'entity_table' => 'civicrm_line_item',
        'entity_id' => $lineItemIDs[0],
      ],
      [
        // when qty 2 is used, add the surplus amount i.e. $20 - $30 = -$10
        'amount' => -10.00,
        'status_id' => $unpaidStatus,
        'entity_table' => 'civicrm_line_item',
        'entity_id' => $lineItemIDs[0],
      ],
    ];
    // Check if 3 financial items were recorded
    $this->assertEquals(count($expectedResults), $financialItems['count']);
    foreach ($expectedResults as $key => $expectedResult) {
      foreach ($expectedResult as $column => $value) {
        $this->assertEquals($value, $financialItems['values'][$key][$column]);
      }
    }

    $this->balanceCheck(20);
  }

  /**
   * CRM-17151: Test that Contribution status change to 'Completed' if balance is zero.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCRM17151(): void {
    $this->registerParticipantAndPay();
    $this->validateContribution($this->_expensiveFee, 'Completed');
    $lineItem = $this->getParticipantLineItems();
    $this->assertEquals($this->_expensiveFee, $lineItem[0]['line_total']);

    $priceSetParams[$this->getPriceFieldFormLabel('PaidEvent')] = $this->getCheapFeeID();
    $this->submitForm($this->getCheapFeeID());
    $this->validateContribution($this->_cheapFee, 'Pending refund');
    $lineItem = $this->getParticipantLineItems();
    $this->assertEquals('0.00', $lineItem[1]['line_total']);
    $this->assertEquals($this->_cheapFee, $lineItem[0]['line_total']);

    $this->submitForm($this->getExpensiveValueID());
    $this->validateContribution($this->_expensiveFee, 'Completed');
    $lineItem = $this->getParticipantLineItems();
    $this->assertEquals($this->_expensiveFee, $lineItem[1]['line_total']);
    $this->assertEquals('0.00', $lineItem[0]['line_total']);

    $this->submitForm($this->getVeryExpensiveID());
    $lineItem = $this->getParticipantLineItems();
    $this->assertEquals('0.00', $lineItem[1]['line_total']);
    $this->assertEquals('0.00', $lineItem[2]['line_total']);
    $this->assertEquals($this->_veryExpensive, $lineItem[0]['line_total']);
    $this->validateContribution($this->_veryExpensive, 'Partially paid');
  }

  /**
   * Test that recording a refund when fee selection is 0 works
   *
   * @throws \CRM_Core_Exception
   */
  public function testRefundWithFeeAmount0(): void {
    $this->registerParticipantAndPay();
    $actualPaidAmount = 100;

    $this->submitForm($this->getExpensiveValueID());
    $this->balanceCheck($this->_expensiveFee);
    $contributionBalance = ($this->_expensiveFee - $actualPaidAmount);
    $this->assertEquals($contributionBalance, CRM_Contribute_BAO_Contribution::getContributionBalance($this->ids['Contribution']['order']));

    $this->submitForm($this->ids['PriceFieldValue']['PaidEvent_free']);
    $this->balanceCheck($this->_noFee);
    $contributionBalance = ($this->_noFee - $actualPaidAmount);
    $this->assertEquals($contributionBalance, CRM_Contribute_BAO_Contribution::getContributionBalance($this->ids['Contribution']['order']));

    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $this->ids['Contribution']['order'],
      'total_amount' => -100,
      'payment_instrument_id' => 3,
      'participant_id' => $this->ids['Participant']['order'],
    ]);
    $contributionBalance += 100;
    $this->assertEquals($contributionBalance, CRM_Contribute_BAO_Contribution::getContributionBalance($this->ids['Contribution']['order']));

    // retrieve the cancelled line-item information
    $cancelledLineItem = $this->callAPISuccessGetSingle('LineItem', [
      'entity_table' => 'civicrm_participant',
      'entity_id' => $this->ids['Participant']['order'],
      'qty' => 0,
    ]);
    // retrieve the related financial lin-items
    $financialItems = $this->callAPISuccess('FinancialItem', 'Get', [
      'entity_id' => $cancelledLineItem['id'],
      'entity_table' => 'civicrm_line_item',
    ]);
    $this->assertEquals(2, $financialItems['count'], 'Financial Items for Cancelled fee is not proper');

    $expectedAmount = 100.00;
    foreach ($financialItems['values'] as $financialItem) {
      $this->assertEquals($expectedAmount, $financialItem['amount']);
      $this->assertNotEmpty($financialItem['financial_account_id']);
      $expectedAmount = -$expectedAmount;
    }
  }

  /**
   * dev-financial-40: Test that partial payment entries in entity-financial-trxn table to ensure that reverse transaction is entered
   *
   * @throws \CRM_Core_Exception
   */
  public function testPartialPaymentEntries(): void {
    $this->registerParticipantAndPay($this->_expensiveFee);
    $this->submitForm($this->getVeryExpensiveID());
    $actualResults = $this->callAPISuccess('EntityFinancialTrxn', 'get', ['sequential' => 1, 'entity_table' => 'civicrm_financial_item'])['values'];
    $financialItemIDs = FinancialItem::get(FALSE)->addOrderBy('id')->execute()->column('id');
    $financialTrxnIDs = FinancialTrxn::get(FALSE)->addOrderBy('id')->execute()->column('id');
    $this->assertCount(3, $actualResults);
    $expectedResults = [
      [
        'amount' => 100.0,
        'entity_id' => $financialItemIDs[0],
        'financial_trxn_id' => $financialTrxnIDs[0],
        'entity_table' => 'civicrm_financial_item',
      ],
      [
        // ensure that reverse entry is entered in the EntityFinancialTrxn table on fee change to greater amount
        'amount' => -100.0,
        'entity_id' => $financialItemIDs[1],
        'financial_trxn_id' => $financialTrxnIDs[1],
        'entity_table' => 'civicrm_financial_item',
      ],
      [
        'amount' => 300.00,
        'entity_id' => $financialItemIDs[2],
        'financial_trxn_id' => $financialTrxnIDs[1],
        'entity_table' => 'civicrm_financial_item',
      ],
    ];
    foreach ($expectedResults as $key => $expectedResult) {
      $this->checkArrayEquals($expectedResult, $actualResults[$key]);
    }
  }

  /**
   * dev-financial-40: Test that refund payment entries in entity-financial-trxn table to ensure that reverse transaction is entered on fee change to lesser amount
   *
   * @throws \CRM_Core_Exception
   */
  public function testRefundPaymentEntries(): void {
    $this->registerParticipantAndPay($this->_expensiveFee);
    $this->submitForm($this->getCheapFeeID());
    $actualResults = $this->callAPISuccess('EntityFinancialTrxn', 'get', ['sequential' => 1, 'entity_table' => 'civicrm_financial_item', 'return' => ['amount', 'entity_id']])['values'];
    $financialItemIDs = FinancialItem::get(FALSE)->addOrderBy('id')->execute()->column('id');
    $expectedResults = [
      [
        'amount' => 100.00,
        'entity_id' => $financialItemIDs[0],
      ],
      [
        // ensure that reverse entry is entered in the EntityFinancialTrxn table
        'amount' => -100.00,
        'entity_id' => $financialItemIDs[1],
      ],
      [
        'amount' => 50.00,
        'entity_id' => $financialItemIDs[2],
      ],
    ];
    foreach ($expectedResults as $key => $expectedResult) {
      $this->checkArrayEquals($expectedResult, $actualResults[$key]);
    }
  }

  /**
   * Validate the contribution against the expected amount and status.
   *
   * @param string $amount
   * @param string $status
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function validateContribution(string $amount, string $status): void {
    $contribution = Contribution::get()
      ->addWhere('id', '=', $this->ids['Contribution']['order'])
      ->addSelect('total_amount', 'contribution_status_id:name')
      ->execute()
      ->first();
    $this->assertEquals($amount, $contribution['total_amount'], 'Total Amount should be ' . $amount);
    $this->assertEquals($status, $contribution['contribution_status_id:name'], 'Payment should ' . $status);
  }

  /**
   * Get the line items for the participant.
   *
   * @return array
   */
  protected function getParticipantLineItems(): array {
    return (array) LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $this->ids['Contribution']['order'])
      ->addOrderBy('id', 'DESC')
      ->execute();
  }

  /**
   * Get the ID for the $50 price field value option.
   *
   * @return int
   */
  protected function getCheapFeeID(): int {
    return $this->ids['PriceFieldValue']['PaidEvent_student_early'];
  }

  /**
   * Get the ID for the $100 price field value option.
   *
   * @return int
   */
  protected function getExpensiveValueID(): int {
    return $this->ids['PriceFieldValue']['PaidEvent_student'];
  }

  /**
   * Get the ID for the $300 price field value option.
   *
   * @return int
   */
  protected function getVeryExpensiveID(): int {
    return $this->ids['PriceFieldValue']['PaidEvent_standard'];
  }

  /**
   * @return array
   */
  public function getPriceFieldsMetadata(): array {
    $order = new CRM_Financial_BAO_Order();
    $order->setPriceSetID($this->getPriceSetID('PaidEvent'));
    return $order->getPriceFieldsMetadata();
  }

  /**
   * @param int|null $participantFee
   *
   * @param array $priceSetParams
   *
   * @return void
   */
  private function submitForm(?int $participantFee = NULL, array $priceSetParams = []): void {
    $this->getTestForm('CRM_Event_Form_ParticipantFeeSelection', $priceSetParams + [
      $this->getPriceFieldFormLabel('PaidEvent') => $participantFee,
      'status_id' => $this->getParticipantStatusID('Registered'),
    ], [
      'id' => $this->ids['Participant']['order'],
      'action' => CRM_Core_Action::UPDATE,
    ])->processForm();
  }

  /**
   * The "Confirmation Message" text entered on the back-office
   * "Change Registration" screen should be included in the confirmation
   * email when "Send Confirmation?" is ticked.
   *
   * https://lab.civicrm.org/dev/core/-/work_items/6731
   *
   * @throws \CRM_Core_Exception
   */
  public function testChangeFeeSelectionEmailIncludesConfirmationMessage(): void {
    $this->registerParticipantAndPay($this->_expensiveFee);
    ParticipantStatusType::update()
      ->addValue('is_active', TRUE)
      ->addWhere('name', '=', 'On waitlist')
      ->execute();
    Participant::update()
      ->addValue('status_id:name', 'On waitlist')
      ->addWhere('id', '=', $this->ids['Participant']['order'])
      ->execute();
    $fromEmailAddress = array_key_first(CRM_Event_BAO_Event::getFromEmailIds($this->getEventID())['from_email_id']);
    $this->getTestForm('CRM_Event_Form_ParticipantFeeSelection', [
      $this->getPriceFieldFormLabel('PaidEvent') => $this->getCheapFeeID(),
      'status_id' => $this->getParticipantStatusID('On waitlist'),
      'send_receipt' => 1,
      'from_email_address' => $fromEmailAddress,
      'receipt_text' => 'This is my distinctive confirmation message',
    ], [
      'id' => $this->ids['Participant']['order'],
      'action' => CRM_Core_Action::UPDATE,
    ])->processForm();
    $this->assertMailSentContainingString('You have been added to the WAIT LIST for this event');
    $this->assertMailSentContainingString('This is my distinctive confirmation message');
  }

  /**
   * An unchanged submitted line item retains its financial item.
   *
   * When no line items need updating, changeFeeSelections() used to incorrectly
   * reverse submitted, unchanged line items.
   */
  public function testUnchangedLineItemKeepsItsFinancialItem(): void {
    $this->createPriceField('ten_euros', 'Ten euros', 10.00);
    $this->createPriceField('thirty_five_euros', 'Thirty-five euros', 35.00);
    $this->registerAndPayForPriceFieldValues(['ten_euros', 'thirty_five_euros'], 45.00);

    $this->submitForm(NULL, [$this->getPriceFieldFormLabel('thirty_five_euros') => $this->ids['PriceFieldValue']['thirty_five_euros']]);

    $lineItem = $this->getLineItemForPriceFieldValue($this->ids['PriceFieldValue']['thirty_five_euros']);
    $amounts = $this->getFinancialItemAmountsForLineItem($lineItem['id']);
    $this->assertEquals(1.0, (float) $lineItem['qty']);
    $this->assertEqualsWithDelta(
      (float) $lineItem['line_total'],
      array_sum($amounts),
      0.001,
      'The submitted, unchanged line item should retain revenue equal to its line total.'
    );
    $this->assertEquals([35.00], $amounts, 'The unchanged line item should still have its single original financial item.');
  }

  /**
   * An omitted line item is still zeroed and financially reversed.
   */
  public function testOmittedLineItemIsStillReversed(): void {
    $this->createPriceField('ten_euros', 'Ten euros', 10.00);
    $this->createPriceField('thirty_five_euros', 'Thirty-five euros', 35.00);
    $this->registerAndPayForPriceFieldValues(['ten_euros', 'thirty_five_euros'], 45.00);

    $this->submitForm(NULL, [$this->getPriceFieldFormLabel('thirty_five_euros') => $this->ids['PriceFieldValue']['thirty_five_euros']]);

    $lineItem = $this->getLineItemForPriceFieldValue($this->ids['PriceFieldValue']['ten_euros']);
    $this->assertEquals(0.0, (float) $lineItem['qty']);
    $this->assertEquals(0.0, (float) $lineItem['line_total']);
    $this->assertEquals(
      [-10.00, 10.00],
      $this->getFinancialItemAmountsForLineItem($lineItem['id']),
      'The omitted line item should have its original item and exactly one reversal.'
    );
  }

  /**
   * An omitted line item with a negative amount is reversed as well.
   *
   * A discount line carries a negative financial item. Zeroing the line item
   * without reversing that item leaves the discount on the revenue account
   * forever, so the accounting side keeps a negative remainder that the
   * contribution no longer accounts for.
   */
  public function testOmittedNegativeLineItemIsAlsoReversed(): void {
    $this->createPriceField('ten_euros', 'Ten euros', 10.00);
    $this->createPriceField('discount_five_euros', 'Five euro discount', -5.00);
    $this->registerAndPayForPriceFieldValues(['ten_euros', 'discount_five_euros'], 5.00);

    $this->submitForm(NULL, [$this->getPriceFieldFormLabel('ten_euros') => $this->ids['PriceFieldValue']['ten_euros']]);

    $lineItem = $this->getLineItemForPriceFieldValue($this->ids['PriceFieldValue']['discount_five_euros']);
    $this->assertEquals(0.0, (float) $lineItem['qty']);
    $this->assertEquals(0.0, (float) $lineItem['line_total']);
    $this->assertEquals(
      [-5.00, 5.00],
      $this->getFinancialItemAmountsForLineItem($lineItem['id']),
      'The omitted discount line should have its negative item and exactly one reversal.'
    );
  }

  /**
   * A line item carrying sales tax has both of its financial items reversed.
   *
   * Revenue and tax are recorded as two financial items on one line item, each on
   * its own financial account. Both have to be reversed when the line is omitted,
   * and each on its own account: a single combined reversal would move the tax off
   * the tax account and onto the revenue account.
   */
  public function testOmittedTaxedLineItemReversesBothItems(): void {
    $this->enableTaxAndInvoicing();
    $this->addTaxAccountToFinancialType($this->getFinancialTypeID('Event Fee'));
    $this->createPriceField('ten_euros', 'Ten euros', 10.00);
    $this->createPriceField('thirty_five_euros', 'Thirty-five euros', 35.00);
    $this->registerAndPayForPriceFieldValues(['ten_euros', 'thirty_five_euros'], 49.50);

    $this->submitForm(NULL, [$this->getPriceFieldFormLabel('thirty_five_euros') => $this->ids['PriceFieldValue']['thirty_five_euros']]);

    $lineItem = $this->getLineItemForPriceFieldValue($this->ids['PriceFieldValue']['ten_euros']);
    $this->assertEquals(0.0, (float) $lineItem['qty']);
    $this->assertEquals(0.0, (float) $lineItem['line_total']);
    $this->assertEquals(
      [-10.00, -1.00, 1.00, 10.00],
      $this->getFinancialItemAmountsForLineItem($lineItem['id']),
      'Both the revenue and the tax item of the omitted line should be reversed.'
    );

    // Each reversal belongs on the account of the item it reverses.
    $perAccount = $this->getFinancialItemTotalsByAccount($lineItem['id']);
    $this->assertCount(2, $perAccount, 'Expected items on a revenue and a tax account.');
    foreach ($perAccount as $accountID => $total) {
      $this->assertEqualsWithDelta(0.0, $total, 0.001, 'Financial account ' . $accountID . ' should net to zero after the reversal.');
    }
  }

  /**
   * Swapping a price option for one of the same price leaves the payment intact.
   *
   * This pins down behaviour that used to be at risk. changeFeeSelections() held a
   * branch that reversed the financial transaction a financial item was linked to,
   * and that transaction is the payment itself, not the line's share of it. The
   * branch never executed because of a mismatch between the name it was called by
   * and the name it was defined under, so the payment survived by accident rather
   * than by design. With the branch removed the outcome is the same, and this test
   * keeps it that way.
   */
  public function testSwappingEqualPricedOptionKeepsThePayment(): void {
    $this->createPriceField('fifty_euros', 'Fifty euros', 50.00);
    $this->createPriceField('thirty_five_euros', 'Thirty-five euros', 35.00);
    $this->createPriceField('fifty_euros_again', 'Fifty euros again', 50.00);
    $this->registerAndPayForPriceFieldValues(['fifty_euros', 'thirty_five_euros'], 85.00);

    $this->submitForm(NULL, [
      $this->getPriceFieldFormLabel('fifty_euros_again') => $this->ids['PriceFieldValue']['fifty_euros_again'],
      $this->getPriceFieldFormLabel('thirty_five_euros') => $this->ids['PriceFieldValue']['thirty_five_euros'],
    ]);

    $contribution = Contribution::get(FALSE)
      ->addWhere('id', '=', $this->ids['Contribution']['order'])
      ->addSelect('total_amount')
      ->execute()->single();
    $this->assertEquals(85.00, (float) $contribution['total_amount'], 'The swap should not change what is owed.');

    $this->assertEquals(
      [85.00],
      $this->getContributionPaymentAmounts($this->ids['Contribution']['order']),
      'The contribution should still carry exactly its original payment, with no reversal.'
    );
  }

  /**
   * Swapping an option for an equal-priced one leaves the balance unchanged,
   * so the processing fee recorded on the contribution must stay.
   */
  public function testSwappingEqualPricedOptionKeepsTheProcessingFee(): void {
    $this->createPriceField('fifty_euros', 'Fifty euros', 50.00);
    $this->createPriceField('thirty_five_euros', 'Thirty-five euros', 35.00);
    $this->createPriceField('fifty_euros_again', 'Fifty euros again', 50.00);
    $this->registerAndPayForPriceFieldValues(['fifty_euros', 'thirty_five_euros'], 85.00);
    Contribution::update(FALSE)
      ->addWhere('id', '=', $this->ids['Contribution']['order'])
      ->addValue('fee_amount', 3.00)
      ->addValue('net_amount', 82.00)
      ->execute();

    $this->submitForm(NULL, [
      $this->getPriceFieldFormLabel('fifty_euros_again') => $this->ids['PriceFieldValue']['fifty_euros_again'],
      $this->getPriceFieldFormLabel('thirty_five_euros') => $this->ids['PriceFieldValue']['thirty_five_euros'],
    ]);

    $contribution = Contribution::get(FALSE)
      ->addWhere('id', '=', $this->ids['Contribution']['order'])
      ->addSelect('total_amount', 'fee_amount', 'net_amount')
      ->execute()->single();
    $this->assertEquals([85.00, 3.00, 82.00], [(float) $contribution['total_amount'], (float) $contribution['fee_amount'], (float) $contribution['net_amount']]);
  }

  /**
   * A contribution can cover more than one participant - eg. a group
   * registration paid for in one payment - and changing one participant's
   * fee selection must not cancel another participant's line item on the
   * same contribution.
   *
   * $submittedLineItems built by ParticipantFeeSelection::postProcess() only
   * ever covers the participant being edited, and
   * CRM_Contribute_BAO_FinancialProcessor::classifyLineItemChanges() treats any
   * of the contribution's other price_field_value_ids as having been
   * deselected. addLineItemsNotYetRepresented() is what keeps the other
   * participant's line item intact.
   *
   * @throws \CRM_Core_Exception
   */
  public function testFeeChangeForOneParticipantDoesNotCancelAnothers(): void {
    $participantA = $this->createTestEntity('Participant', [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'event_id' => $this->getEventID(),
      'status_id:name' => 'Registered',
      'role_id:name' => 'Attendee',
    ], 'participantA');
    $secondContactID = $this->individualCreate([], 'individual_1');
    $participantB = $this->createTestEntity('Participant', [
      'contact_id' => $secondContactID,
      'event_id' => $this->getEventID(),
      'status_id:name' => 'Registered',
      'role_id:name' => 'Attendee',
    ], 'participantB');

    $contribution = Order::create(FALSE)
      ->setContributionValues([
        'contact_id' => $this->ids['Contact']['individual_0'],
        'financial_type_id:name' => 'Event Fee',
      ])
      ->addLineItem([
        'entity_table' => 'civicrm_participant',
        'entity_id' => $participantA['id'],
        'price_field_value_id' => $this->getCheapFeeID(),
      ])
      ->addLineItem([
        'entity_table' => 'civicrm_participant',
        'entity_id' => $participantB['id'],
        'price_field_value_id' => $this->getExpensiveValueID(),
      ])
      ->execute()->single();

    Payment::create(FALSE)
      ->addValue('contribution_id', $contribution['id'])
      ->addValue('total_amount', (float) $this->_cheapFee + (float) $this->_expensiveFee)
      ->execute();

    $participantBLineBefore = LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->addWhere('entity_id', '=', $participantB['id'])
      ->execute()->single();

    // Change participant A's fee only.
    $this->getTestForm('CRM_Event_Form_ParticipantFeeSelection', [
      $this->getPriceFieldFormLabel('PaidEvent') => $this->getVeryExpensiveID(),
      'status_id' => $this->getParticipantStatusID('Registered'),
    ], [
      'id' => $participantA['id'],
      'action' => CRM_Core_Action::UPDATE,
    ])->processForm();

    $participantBLineAfter = $this->callAPISuccessGetSingle('LineItem', ['id' => $participantBLineBefore['id'], 'version' => 4]);
    $this->assertEquals(
      $participantBLineBefore['qty'],
      $participantBLineAfter['qty'],
      "Participant B's line item must survive a fee change made for participant A on the same contribution."
    );
    $this->assertGreaterThan(0, $participantBLineAfter['qty'], "Participant B's line item should not have been cancelled.");
  }

  /**
   * dev/core#6634: addLineItemsNotYetRepresented() keys the merged-in line
   * items by price_field_value_id. If the participant being edited switches
   * to the SAME price option another participant on the contribution
   * already holds, that key is already present in $submittedLineItems (as
   * the edited participant's own new selection), so the other participant's
   * "keep unchanged" line item is never merged in. getLineItemsToAdd()/
   * getLineItemsToAlter() then have only one entry for that
   * price_field_value_id to work from, and misattribute it to whichever
   * participant already had a line item for it.
   *
   * Reproduced on smaster.demo.civicrm.org (CiviCRM 6.21.alpha1): the
   * participant being edited ended up with their old line item cancelled
   * and no replacement, while the other participant's line item - and the
   * contribution total - were also wrong.
   *
   * @throws \CRM_Core_Exception
   */
  public function testFeeChangeMatchingAnotherParticipantsOptionDoesNotLoseTheLineItem(): void {
    $participantA = $this->createTestEntity('Participant', [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'event_id' => $this->getEventID(),
      'status_id:name' => 'Registered',
      'role_id:name' => 'Attendee',
    ], 'participantA');
    $secondContactID = $this->individualCreate([], 'individual_1');
    $participantB = $this->createTestEntity('Participant', [
      'contact_id' => $secondContactID,
      'event_id' => $this->getEventID(),
      'status_id:name' => 'Registered',
      'role_id:name' => 'Attendee',
    ], 'participantB');

    $contribution = Order::create(FALSE)
      ->setContributionValues([
        'contact_id' => $this->ids['Contact']['individual_0'],
        'financial_type_id:name' => 'Event Fee',
      ])
      ->addLineItem([
        'entity_table' => 'civicrm_participant',
        'entity_id' => $participantA['id'],
        'price_field_value_id' => $this->getExpensiveValueID(),
      ])
      ->addLineItem([
        'entity_table' => 'civicrm_participant',
        'entity_id' => $participantB['id'],
        'price_field_value_id' => $this->getCheapFeeID(),
      ])
      ->execute()->single();

    Payment::create(FALSE)
      ->addValue('contribution_id', $contribution['id'])
      ->addValue('total_amount', (float) $this->_cheapFee + (float) $this->_expensiveFee)
      ->execute();

    // Change participant A onto the SAME option participant B already holds.
    $this->getTestForm('CRM_Event_Form_ParticipantFeeSelection', [
      $this->getPriceFieldFormLabel('PaidEvent') => $this->getCheapFeeID(),
      'status_id' => $this->getParticipantStatusID('Registered'),
    ], [
      'id' => $participantA['id'],
      'action' => CRM_Core_Action::UPDATE,
    ])->processForm();

    $participantALine = LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->addWhere('entity_id', '=', $participantA['id'])
      ->addWhere('qty', '>', 0)
      ->execute();
    $this->assertCount(1, $participantALine, 'Participant A should have their own active line item for the new selection.');
    $this->assertEquals($this->_cheapFee, $participantALine->first()['line_total']);

    $participantBLine = LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->addWhere('entity_id', '=', $participantB['id'])
      ->execute()->single();
    $this->assertGreaterThan(0, $participantBLine['qty'], "Participant B's line item must survive participant A's fee change.");
    $this->assertEquals($this->_cheapFee, $participantBLine['line_total']);

    $this->assertEquals(
      (float) $this->_cheapFee * 2,
      $this->contributionInvoice($contribution['id']),
      'The contribution total must reflect both participants holding the cheap fee.'
    );
  }

  /**
   * dev/core#6634: getNonCancelledFinancialItems() groups a contribution's
   * financial items by price_field_value_id alone to spot already-reversed
   * +/- pairs. If a DIFFERENT participant on the same contribution has an
   * unrelated, already-fully-reversed pair under that same price option,
   * this participant's own still-active financial item can get swept into
   * that pairing and wrongly treated as already reversed - so when their
   * own fee changes, the required reversal is silently skipped.
   *
   * @throws \CRM_Core_Exception
   */
  public function testFeeChangeDoesNotPruneAnotherParticipantsUnrelatedFinancialItem(): void {
    $participantA = $this->createTestEntity('Participant', [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'event_id' => $this->getEventID(),
      'status_id:name' => 'Registered',
      'role_id:name' => 'Attendee',
    ], 'participantA');
    $secondContactID = $this->individualCreate([], 'individual_1');
    $participantB = $this->createTestEntity('Participant', [
      'contact_id' => $secondContactID,
      'event_id' => $this->getEventID(),
      'status_id:name' => 'Registered',
      'role_id:name' => 'Attendee',
    ], 'participantB');

    $contribution = Order::create(FALSE)
      ->setContributionValues([
        'contact_id' => $this->ids['Contact']['individual_0'],
        'financial_type_id:name' => 'Event Fee',
      ])
      ->addLineItem([
        'entity_table' => 'civicrm_participant',
        'entity_id' => $participantA['id'],
        'price_field_value_id' => $this->getCheapFeeID(),
      ])
      ->addLineItem([
        'entity_table' => 'civicrm_participant',
        'entity_id' => $participantB['id'],
        'price_field_value_id' => $this->getVeryExpensiveID(),
      ])
      ->execute()->single();

    Payment::create(FALSE)
      ->addValue('contribution_id', $contribution['id'])
      ->addValue('total_amount', (float) $this->_cheapFee + (float) $this->_veryExpensive)
      ->execute();

    $participantALine = LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->addWhere('entity_id', '=', $participantA['id'])
      ->execute()->single();

    // Participant B's unrelated, already-fully-reversed OLD selection at the
    // same price option participant A currently holds - built directly, as
    // a stand-in for a past fee change, so it carries its own original +
    // reversal financial items without a second form submission.
    $oldLineItemForB = $this->createTestEntity('LineItem', [
      'contribution_id' => $contribution['id'],
      'entity_table' => 'civicrm_participant',
      'entity_id' => $participantB['id'],
      'price_field_id' => $participantALine['price_field_id'],
      'price_field_value_id' => $this->getCheapFeeID(),
      'label' => $participantALine['label'],
      'qty' => 0,
      'unit_price' => $this->_cheapFee,
      'line_total' => 0,
      'financial_type_id' => $participantALine['financial_type_id'],
    ], 'oldLineItemB');

    $incomeAccountID = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
      $participantALine['financial_type_id'],
      'Income Account is'
    );
    $this->createTestEntity('FinancialItem', [
      'transaction_date' => date('Y-m-d'),
      'contact_id' => $secondContactID,
      'amount' => $this->_cheapFee,
      'currency' => 'USD',
      'entity_table' => 'civicrm_line_item',
      'entity_id' => $oldLineItemForB['id'],
      'description' => $participantALine['label'],
      'status_id:name' => 'Paid',
      'financial_account_id' => $incomeAccountID,
    ], 'oldFinancialItemB1');
    $this->createTestEntity('FinancialItem', [
      'transaction_date' => date('Y-m-d'),
      'contact_id' => $secondContactID,
      'amount' => -(float) $this->_cheapFee,
      'currency' => 'USD',
      'entity_table' => 'civicrm_line_item',
      'entity_id' => $oldLineItemForB['id'],
      'description' => $participantALine['label'],
      'status_id:name' => 'Paid',
      'financial_account_id' => $incomeAccountID,
    ], 'oldFinancialItemB2');

    // Change participant A's own fee - this is what triggers a scan of
    // every financial item on the contribution's line items, including
    // participant B's unrelated pair above.
    $this->getTestForm('CRM_Event_Form_ParticipantFeeSelection', [
      $this->getPriceFieldFormLabel('PaidEvent') => $this->getExpensiveValueID(),
      'status_id' => $this->getParticipantStatusID('Registered'),
    ], [
      'id' => $participantA['id'],
      'action' => CRM_Core_Action::UPDATE,
    ])->processForm();

    $participantAOldLineFinancialItems = array_map('floatval', FinancialItem::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_line_item')
      ->addWhere('entity_id', '=', $participantALine['id'])
      ->addSelect('amount')
      ->execute()
      ->column('amount'));
    sort($participantAOldLineFinancialItems);

    $this->assertEquals(
      [-(float) $this->_cheapFee, (float) $this->_cheapFee],
      $participantAOldLineFinancialItems,
      "Participant A's original financial item must be reversed on their own fee change, not wrongly treated as already reversed because participant B has an unrelated reversed pair under the same price option."
    );
  }

  /**
   * Create one radio price field, with one value, on the PaidEvent price set.
   *
   * @param string $name
   * @param string $label
   * @param float $amount
   */
  private function createPriceField(string $name, string $label, float $amount): void {
    $priceField = $this->createTestEntity('PriceField', [
      'price_set_id' => $this->getPriceSetID('PaidEvent'),
      'name' => $name,
      'label' => $label,
      'html_type' => 'Radio',
      'is_required' => 0,
      'financial_type_id:name' => 'Event Fee',
    ], $name);
    $this->createTestEntity('PriceFieldValue', [
      'price_field_id' => $priceField['id'],
      'name' => $name,
      'label' => $label,
      'amount' => $amount,
      'financial_type_id:name' => 'Event Fee',
    ], $name);
  }

  /**
   * Register a participant against the given price field values and pay in full.
   *
   * All line items are attached to a single new participant, and their amounts
   * (and tax, if enabled) are derived from their price field values.
   *
   * @param string[] $identifiers
   *   Identifiers previously passed to createPriceField().
   * @param float $totalAmount
   */
  private function registerAndPayForPriceFieldValues(array $identifiers, float $totalAmount): void {
    $participant = $this->createTestEntity('Participant', [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'event_id' => $this->getEventID(),
      'status_id:name' => 'Registered',
      'role_id:name' => 'Attendee',
    ], 'order');

    $order = Order::create(FALSE)->setContributionValues([
      'contact_id' => $this->ids['Contact']['individual_0'],
      'financial_type_id:name' => 'Event Fee',
    ]);
    foreach ($identifiers as $identifier) {
      $order->addLineItem([
        'entity_table' => 'civicrm_participant',
        'entity_id' => $participant['id'],
        'price_field_value_id' => $this->ids['PriceFieldValue'][$identifier],
      ]);
    }
    $contribution = $order->execute()->single();
    $this->ids['Contribution']['order'] = $contribution['id'];

    Payment::create(FALSE)
      ->addValue('contribution_id', $contribution['id'])
      ->addValue('total_amount', $totalAmount)
      ->execute();
  }

  /**
   * Get the qty, line_total and id of the line item for a price field value.
   *
   * @param int $priceFieldValueID
   *
   * @return array
   */
  private function getLineItemForPriceFieldValue(int $priceFieldValueID): array {
    return LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $this->ids['Contribution']['order'])
      ->addWhere('price_field_value_id', '=', $priceFieldValueID)
      ->addSelect('id', 'qty', 'line_total')
      ->execute()->single();
  }

  /**
   * Get the individual financial item amounts of one line item, ascending.
   *
   * Asserting on the separate amounts rather than only on their sum makes a
   * missing reversal and a duplicated one distinguishable: both leave a sum that
   * a laxer assertion would accept.
   *
   * @param int $lineItemID
   *
   * @return float[]
   */
  private function getFinancialItemAmountsForLineItem(int $lineItemID): array {
    $amounts = FinancialItem::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_line_item')
      ->addWhere('entity_id', '=', $lineItemID)
      ->addSelect('amount')
      ->execute()
      ->column('amount');
    $amounts = array_map('floatval', $amounts);
    sort($amounts);
    return $amounts;
  }

  /**
   * Get the payment transaction amounts recorded against a contribution, ascending.
   *
   * @param int $contributionID
   *
   * @return float[]
   */
  private function getContributionPaymentAmounts(int $contributionID): array {
    $amounts = EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_contribution')
      ->addWhere('entity_id', '=', $contributionID)
      ->addWhere('financial_trxn_id.is_payment', '=', TRUE)
      ->addSelect('amount')
      ->addOrderBy('id')
      ->execute()
      ->column('amount');
    return array_map('floatval', $amounts);
  }

  /**
   * Get the net financial item total per financial account for one line item.
   *
   * @param int $lineItemID
   *
   * @return array
   *   Financial account ID => net amount.
   */
  private function getFinancialItemTotalsByAccount(int $lineItemID): array {
    $items = FinancialItem::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_line_item')
      ->addWhere('entity_id', '=', $lineItemID)
      ->addSelect('financial_account_id', 'amount')
      ->execute();
    $totals = [];
    foreach ($items as $item) {
      $totals[$item['financial_account_id']] = ($totals[$item['financial_account_id']] ?? 0) + (float) $item['amount'];
    }
    return $totals;
  }

}
