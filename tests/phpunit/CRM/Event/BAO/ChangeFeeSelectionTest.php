<?php

use Civi\Api4\Contribution;
use Civi\Api4\PriceField;
use Civi\Api4\PriceFieldValue;

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
      'financial_type_id' => 4,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 'Pending',
      'receive_date' => date('Y-m-d') . ' 00:00:00',
      'line_items' => [],
      'api.Payment.create' => ['total_amount' => $actualPaidAmt],
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
          'status_id' => 1,
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
    // When a line item is 'resurrected' the financial_items attached to it are wrong.
    // We have to skip validatePayments until fixed.
    $this->isValidateFinancialsOnPostAssert = FALSE;
    $this->registerParticipantAndPay();

    $priceSetParams['price_' . $this->ids['PriceField']['PaidEvent']] = $this->getCheapFeeID();
    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $this->ids['Participant']['order'], 'participant', $this->ids['Contribution']['order']);
    $this->balanceCheck($this->_cheapFee);

    $priceSetParams['price_' . $this->getPriceFieldID('PaidEvent')] = $this->getExpensiveValueID();

    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $this->ids['Participant']['order'], 'participant', $this->ids['Contribution']['order']);

    $this->balanceCheck($this->_expensiveFee);

    $priceSetParams[$this->getPriceFieldFormLabel('PaidEvent')] = $this->getVeryExpensiveID();
    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $this->ids['Participant']['order'], 'participant', $this->ids['Contribution']['order']);
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

    $priceSetParams[$this->getPriceFieldFormLabel('PaidEvent')] = $this->getVeryExpensiveID();
    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $this->ids['Participant']['order'], 'participant', $this->ids['Contribution']['order']);
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
    $priceSetParams[$this->getPriceFieldFormLabel('PaidEvent')] = $this->getExpensiveValueID();
    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $this->ids['Participant']['order'], 'participant', $this->ids['Contribution']['order']);
    $this->balanceCheck($this->_expensiveFee);
    $contributionBalance = ($this->_expensiveFee - $actualPaidAmount);
    $this->assertEquals($contributionBalance, CRM_Contribute_BAO_Contribution::getContributionBalance($this->ids['Contribution']['order']));

    $priceSetParams[$this->getPriceFieldFormLabel('PaidEvent')] = $this->getCheapFeeID();
    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $this->ids['Participant']['order'], 'participant', $this->ids['Contribution']['order']);
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
    $textPriceFieldID = PriceField::create()->setValues([
      'price_set_id' => $this->getPriceSetID('PaidEvent'),
      'label' => 'Text Price Field',
      'name' => 'text_price_field',
      'html_type' => 'Text',
      'is_enter_qty' => 1,
    ])->execute()->first()['id'];

    PriceFieldValue::create()->setValues(['financial_type_id:name' => 'Event Fee', 'price_field_id' => $textPriceFieldID, 'amount' => 10, 'label' => 'ten'])->execute();
    $priceSet = CRM_Price_BAO_PriceSet::getSetDetail($this->getPriceSetID('PaidEvent'));
    $priceSet = $priceSet[$this->getPriceSetID('PaidEvent')];
    $feeBlock = $priceSet['fields'] ?? NULL;

    $params = [
      'send_receipt' => 1,
      'is_test' => 0,
      'is_pay_later' => 0,
      'event_id' => $this->getEventID(),
      'register_date' => date('Y-m-d') . ' 00:00:00',
      'role_id' => 1,
      'status_id' => 1,
      'source' => 'Event_' . $this->getEventID(),
      'contact_id' => $this->ids['Contact']['individual_0'],
    ];
    $participant = $this->callAPISuccess('Participant', 'create', $params);
    $this->ids['Participant']['order'] = $participant['id'];
    $contributionParams = [
      'total_amount' => 10,
      'source' => 'Test set with information',
      'currency' => 'USD',
      'receipt_date' => date('Y-m-d') . ' 00:00:00',
      'contact_id' => $this->ids['Contact']['individual_0'],
      'financial_type_id' => 4,
      'payment_instrument_id' => 4,
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_DAO_Contribution', 'contribution_status_id', 'Pending'),
      'receive_date' => date('Y-m-d') . ' 00:00:00',
      'skipLineItem' => 1,
    ];

    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    $this->ids['Contribution']['order'] = $contribution['id'];

    $this->callAPISuccess('ParticipantPayment', 'create', [
      'participant_id' => $this->ids['Participant']['order'],
      'contribution_id' => $this->ids['Contribution']['order'],
    ]);

    // CASE 1: Choose text price qty 1 (x$10 = $10 amount)
    $priceSetParams['price_' . $textPriceFieldID] = 1;
    $lineItem = $this->getParticipantLineItems();
    CRM_Price_BAO_PriceSet::processAmount($feeBlock, $priceSetParams, $lineItem);
    $lineItemVal[$this->getPriceSetID('PaidEvent')] = $lineItem;
    CRM_Price_BAO_LineItem::processPriceSet($this->ids['Participant']['order'], $lineItemVal, $this->getContributionObject($contribution['id']), 'civicrm_participant');

    // CASE 2: Choose text price qty 3 (x$10 = $30 amount)
    $priceSetParams['price_' . $textPriceFieldID] = 3;
    $lineItem = $this->getParticipantLineItems();
    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $participant['id'], 'participant', $this->ids['Contribution']['order']);

    // CASE 3: Choose text price qty 2 (x$10 = $20 amount)
    CRM_Price_BAO_LineItem::changeFeeSelections(['price_' . $textPriceFieldID => 2], $participant['id'], 'participant', $this->ids['Contribution']['order']);

    $financialItems = $this->callAPISuccess('FinancialItem', 'Get', [
      'entity_table' => 'civicrm_line_item',
      'entity_id' => ['IN' => array_keys($lineItem)],
      'sequential' => 1,
    ]);

    $unpaidStatus = CRM_Core_PseudoConstant::getKey('CRM_Financial_DAO_FinancialItem', 'status_id', 'Unpaid');
    $expectedResults = [
      [
        // when qty 1 is used
        'amount' => 10.00,
        'status_id' => $unpaidStatus,
        'entity_table' => 'civicrm_line_item',
        'entity_id' => 1,
      ],
      [
        // when qty 3 is used, add the surplus amount i.e. $30 - $10 = $20
        'amount' => 20.00,
        'status_id' => $unpaidStatus,
        'entity_table' => 'civicrm_line_item',
        'entity_id' => 1,
      ],
      [
        // when qty 2 is used, add the surplus amount i.e. $20 - $30 = -$10
        'amount' => -10.00,
        'status_id' => $unpaidStatus,
        'entity_table' => 'civicrm_line_item',
        'entity_id' => 1,
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
    // @todo figure out the financial validation issue - likely a real bug.
    $this->isValidateFinancialsOnPostAssert = FALSE;
    $this->registerParticipantAndPay();
    $this->validateContribution($this->_expensiveFee, 'Completed');

    $priceSetParams[$this->getPriceFieldFormLabel('PaidEvent')] = $this->getCheapFeeID();
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($this->ids['Participant']['order']);
    $this->assertEquals($this->_expensiveFee, $lineItem[1]['line_total']);
    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $this->ids['Participant']['order'], 'participant', $this->ids['Contribution']['order']);

    $this->validateContribution($this->_cheapFee, 'Pending refund');

    $priceSetParams[$this->getPriceFieldFormLabel('PaidEvent')] = $this->getExpensiveValueID();
    $lineItem = $this->getParticipantLineItems();
    $this->assertEquals('0.00', $lineItem[1]['line_total']);
    $this->assertEquals($this->_cheapFee, $lineItem[2]['line_total']);
    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $this->ids['Participant']['order'], 'participant', $this->ids['Contribution']['order']);
    $this->validateContribution($this->_cheapFee, 'Completed');

    $priceSetParams[$this->getPriceFieldFormLabel('PaidEvent')] = $this->getVeryExpensiveID();
    $lineItem = $this->getParticipantLineItems();
    $this->assertEquals($this->_expensiveFee, $lineItem[1]['line_total']);
    $this->assertEquals('0.00', $lineItem[2]['line_total']);
    // @todo this doesn't seem to work right even tho it should
    //$this->assertDBCompareValue('CRM_Contribute_BAO_Contribution', $this->ids['Contribution']['order'], 'total_amount', 'id', $this->_expensiveFee, "Total Amount equals " . $this->_expensiveFee);
    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $this->ids['Participant']['order'], 'participant', $this->ids['Contribution']['order']);
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($this->ids['Participant']['order']);
    $this->assertEquals('0.00', $lineItem[1]['line_total']);
    $this->assertEquals('0.00', $lineItem[2]['line_total']);
    $this->assertEquals($this->_veryExpensive, $lineItem[3]['line_total']);
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
    $priceSetParams[$this->getPriceFieldFormLabel('PaidEvent')] = $this->getExpensiveValueID();

    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $this->ids['Participant']['order'], 'participant', $this->ids['Contribution']['order']);
    $this->balanceCheck($this->_expensiveFee);
    $contributionBalance = ($this->_expensiveFee - $actualPaidAmount);
    $this->assertEquals($contributionBalance, CRM_Contribute_BAO_Contribution::getContributionBalance($this->ids['Contribution']['order']));

    $priceSetParams[$this->getPriceFieldFormLabel('PaidEvent')] = $this->ids['PriceFieldValue']['PaidEvent_free'];
    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $this->ids['Participant']['order'], 'participant', $this->ids['Contribution']['order']);
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
    $priceSetParams[$this->getPriceFieldFormLabel('PaidEvent')] = $this->getVeryExpensiveID();
    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $this->ids['Participant']['order'], 'participant', $this->ids['Contribution']['order']);
    $actualResults = $this->callAPISuccess('EntityFinancialTrxn', 'get', ['sequential' => 1, 'entity_table' => 'civicrm_financial_item'])['values'];
    $this->assertCount(3, $actualResults);
    $expectedResults = [
      [
        'id' => 2,
        'amount' => 100.0,
        'entity_id' => 1,
        'financial_trxn_id' => 1,
        'entity_table' => 'civicrm_financial_item',
      ],
      [
        'id' => 4,
        // ensure that reverse entry is entered in the EntityFinancialTrxn table on fee change to greater amount
        'amount' => -100.0,
        'entity_id' => 2,
        'financial_trxn_id' => 2,
        'entity_table' => 'civicrm_financial_item',
      ],
      [
        'id' => 5,
        'amount' => 300.00,
        'entity_id' => 3,
        'financial_trxn_id' => 2,
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
    $priceSetParams[$this->getPriceFieldFormLabel('PaidEvent')] = $this->getCheapFeeID();
    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $this->ids['Participant']['order'], 'participant', $this->ids['Contribution']['order']);
    $actualResults = $this->callAPISuccess('EntityFinancialTrxn', 'get', ['sequential' => 1, 'entity_table' => 'civicrm_financial_item', 'return' => ['amount', 'entity_id']])['values'];
    $expectedResults = [
      [
        'id' => 2,
        'amount' => 100.00,
        'entity_id' => 1,
      ],
      [
        'id' => 4,
        // ensure that reverse entry is entered in the EntityFinancialTrxn table
        'amount' => -100.00,
        'entity_id' => 2,
      ],
      [
        'id' => 5,
        'amount' => 50.00,
        'entity_id' => 3,
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
   * The BAO function is one we will ideally stop using in time, for now let's just
   * call it once in this file...
   *
   * @return array
   */
  protected function getParticipantLineItems(): array {
    return CRM_Price_BAO_LineItem::getLineItems($this->ids['Participant']['order']);
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

}
