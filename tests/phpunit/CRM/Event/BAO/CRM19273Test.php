<?php
/**
 * Class CRM_Event_BAO_AdditionalPaymentTest
 * @group headless
 */
class CRM_Event_BAO_CRM19273 extends CiviUnitTestCase {

  protected $_priceSetID;
  protected $_cheapFee = 80;
  protected $_expensiveFee = 100;
  protected $_veryExpensive = 120;

  /**
   * @var int
   */
  protected $contributionID;

  /**
   * @var int
   */
  protected $participantID;

  /**
   * Price set field id.
   *
   * @var int
   */
  protected $priceSetFieldID;

  /**
   * Set up for test.
   */
  public function setUp() {
    parent::setUp();
    $this->cleanup();
    $this->_contactId = $this->individualCreate();
    $event = $this->eventCreate(array('is_monetary' => 1));
    $this->_eventId = $event['id'];
    $this->_priceSetID = $this->eventPriceSetCreate();
    CRM_Price_BAO_PriceSet::addTo('civicrm_event', $this->_eventId, $this->_priceSetID);
    $priceSet = CRM_Price_BAO_PriceSet::getSetDetail($this->_priceSetID, TRUE, FALSE);
    $priceSet = CRM_Utils_Array::value($this->_priceSetID, $priceSet);
    $this->_feeBlock = CRM_Utils_Array::value('fields', $priceSet);
    $this->registerParticipantAndPay();
  }

  /**
   * Clean up after test.
   */
  public function tearDown() {
    $this->eventDelete($this->_eventId);
    $this->quickCleanUpFinancialEntities();
  }


  /**
   * Remove default price field stuff.
   *
   * This is not actually good. However resolving this requires
   * a lot more fixes & we have a bit of work to do on event tests.
   *
   * @throws \Exception
   */
  protected function cleanup() {
    $this->quickCleanup(
      array(
        'civicrm_price_field_value',
        'civicrm_price_field',
        'civicrm_price_set',
      )
      );
  }

  /**
   * Create an event with a price set.
   *
   * @todo resolve this with parent function.
   *
   * @param int $amount
   * @param int $min_fee
   * @return int
   */
  protected function eventPriceSetCreate($amount = 0, $min_fee = 0) {

    $paramsSet['title'] = 'Two Options';
    $paramsSet['name'] = CRM_Utils_String::titleToVar('Two Options');
    $paramsSet['is_active'] = FALSE;
    $paramsSet['extends'] = 1;

    $priceSet = CRM_Price_BAO_PriceSet::create($paramsSet);

    $paramsField = array(
      'label' => 'Price Field',
      'name' => CRM_Utils_String::titleToVar('Two Options'),
      'html_type' => 'Radio',
      //'price' => $feeTotal,
      'option_label' => array('1' => 'Expensive Room', '2' => "Cheap Room", '3' => 'Very Expensive'),
      'option_value' => array('1' => 'E', '2' => 'C', '3' => 'V'),
      'option_name' => array('1' => 'Expensive', '2' => "Cheap", "3" => "Very Expensive"),
      'option_weight' => array('1' => 1, '2' => 2, '3' => 3),
      'option_amount' => array('1' => $this->_expensiveFee, '2' => $this->_cheapFee, '3' => $this->_veryExpensive),
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'is_active' => array('1' => 1),
      'price_set_id' => $priceSet->id,
      'is_enter_qty' => 1,
      'financial_type_id' => $this->getFinancialTypeId('Event Fee'),
    );
    $field = CRM_Price_BAO_PriceField::create($paramsField);
    $this->priceSetFieldID = $field->id;
    return $priceSet->id;
  }

  /**
   * Get the total for the invoice.
   *
   * @param int $contributionId
   * @return mixed
   */
  private function contributionInvoice($contributionId) {

    $query = "
         SELECT SUM(line_total) total
         FROM   civicrm_line_item
         WHERE  entity_table = 'civicrm_participant'
         AND    entity_id = {$contributionId}";
    $dao = CRM_Core_DAO::executeQuery($query);

    $this->assertTrue($dao->fetch(), "Succeeded retrieving invoicetotal");
    return $dao->total;
  }

  /**
   * Get the total income from the participant record.
   *
   * @param int $participantId
   *
   * @return mixed
   */
  private function totalIncome($participantId) {

    // @todo use INNER JOINS, this is not our style.
    $query = "
      SELECT SUM(et.amount) total
      FROM civicrm_entity_financial_trxn et
      ,      civicrm_financial_item fi
      ,      civicrm_line_item      li
      WHERE  et.entity_table='civicrm_financial_item'
      AND    fi.id = et.entity_id
      AND    fi.entity_table='civicrm_line_item'
      AND    fi.entity_id = li.id
      AND    li.entity_table = 'civicrm_participant'
      AND    li.entity_id = ${participantId}
    ";
    $dao = CRM_Core_DAO::executeQuery($query);

    $this->assertTrue($dao->fetch(), "Succeeded retrieving total Income");
    return $dao->total;
  }

  /**
   * Check the relevant entity balances.
   *
   * @param float $amount
   */
  private function balanceCheck($amount) {
    $this->assertEquals($this->contributionInvoice($this->contributionID), $amount, "Invoice must a total of $amount");
    $this->assertEquals($this->totalIncome($this->participantID), $amount, "The recorded income must be $amount ");
    $this->assertEquals($this->totalIncome($this->contributionID), $amount, "The accumulated assets must be $amount ");
  }

  /**
   * Prepare records for editing.
   */
  public function registerParticipantAndPay() {
    $params = array(
      'send_receipt' => 1,
      'is_test' => 0,
      'is_pay_later' => 0,
      'event_id' => $this->_eventId,
      'register_date' => date('Y-m-d') . " 00:00:00",
      'role_id' => 1,
      'status_id' => 1,
      'source' => 'Event_' . $this->_eventId,
      'contact_id' => $this->_contactId,
      //'fee_level' => CRM_Core_DAO::VALUE_SEPARATOR.'Expensive Room'.CRM_Core_DAO::VALUE_SEPARATOR,
    );
    $participant = $this->callAPISuccess('Participant', 'create', $params);
    $this->_participantId = $participant['id'];

    $actualPaidAmt = $this->_expensiveFee;

    $contributionParams = array(
      'total_amount' => $actualPaidAmt,
      'source' => 'Testset with information',
      'currency' => 'USD',
      'non_deductible_amount' => 'null',
      'receipt_date' => date('Y-m-d') . " 00:00:00",
      'contact_id' => $this->_contactId,
      'financial_type_id' => 4,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 1,
      'receive_date' => date('Y-m-d') . " 00:00:00",
      'skipLineItem' => 1,
      'partial_payment_total' => $this->_expensiveFee,
      'partial_amount_to_pay' => $actualPaidAmt,
    );

    $contribution = CRM_Contribute_BAO_Contribution::create($contributionParams);
    $this->_contributionId = $contribution->id;

    $this->callAPISuccess('participant_payment', 'create', array(
      'participant_id'  => $this->_participantId,
      'contribution_id' => $this->_contributionId,
    ));

    $PSparams['price_1'] = 1; // 1 is the option of the expensive room
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($this->_participantId, 'participant');
    CRM_Price_BAO_PriceSet::processAmount($this->_feeBlock, $PSparams, $lineItem);
    $lineItemVal[$this->_priceSetID] = $lineItem;
    CRM_Price_BAO_LineItem::processPriceSet($participant['id'], $lineItemVal, $contribution, 'civicrm_participant');

    $this->contributionID = $this->callAPISuccessGetValue('Contribution', array('return' => 'id'));
    $this->participantID = $this->callAPISuccessGetValue('Participant', array('return' => 'id'));
    $this->balanceCheck($this->_expensiveFee);
  }

  public function testCRM19273() {

    $PSparams['price_1'] = 2;
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($this->participantID, 'participant');
    CRM_Event_BAO_Participant::changeFeeSelections($PSparams, $this->participantID, $this->_contributionId, $this->_feeBlock, $lineItem, $this->_expensiveFee, $this->_priceSetID);
    $this->balanceCheck($this->_cheapFee);

    $PSparams['price_1'] = 1;
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($this->participantID, 'participant');
    CRM_Event_BAO_Participant::changeFeeSelections($PSparams, $this->participantID, $this->_contributionId, $this->_feeBlock, $lineItem, $this->_expensiveFee, $this->_priceSetID);
    $this->balanceCheck($this->_expensiveFee);

    $PSparams['price_1'] = 3;
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($this->participantID, 'participant');

    CRM_Event_BAO_Participant::changeFeeSelections($PSparams, $this->participantID, $this->_contributionId, $this->_feeBlock, $lineItem, $this->_expensiveFee, $this->_priceSetID);
    $this->balanceCheck($this->_veryExpensive);

  }

  /**
   * Test that proper financial items are recorded for cancelled line items
   */
  public function testCRM20611() {
    $PSparams['price_1'] = 1;
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($this->participantID, 'participant');
    CRM_Event_BAO_Participant::changeFeeSelections($PSparams, $this->participantID, $this->_contributionId, $this->_feeBlock, $lineItem, $this->_expensiveFee, $this->_priceSetID);
    $this->balanceCheck($this->_expensiveFee);

    $PSparams['price_1'] = 2;
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($this->participantID, 'participant');
    CRM_Event_BAO_Participant::changeFeeSelections($PSparams, $this->participantID, $this->_contributionId, $this->_feeBlock, $lineItem, $this->_expensiveFee, $this->_priceSetID);
    $this->balanceCheck($this->_cheapFee);

    //Complete the refund payment.
    $submittedValues = array(
      'total_amount' => 120,
      'payment_instrument_id' => 3,
    );
    CRM_Contribute_BAO_Contribution::recordAdditionalPayment($this->_contributionId, $submittedValues, 'refund', $this->participantID);

    // retrieve the cancelled line-item information
    $cancelledLineItem = $this->callAPISuccessGetSingle('LineItem', array(
      'entity_table' => 'civicrm_participant',
      'entity_id' => $this->participantID,
      'qty' => 0,
    ));
    // retrieve the related financial lin-items
    $financialItems = $this->callAPISuccess('FinancialItem', 'Get', array(
      'entity_id' => $cancelledLineItem['id'],
      'entity_table' => 'civicrm_line_item',
    ));
    $this->assertEquals($financialItems['count'], 2, 'Financial Items for Cancelled fee is not proper');

    $contributionCompletedStatusID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
    $expectedAmount = 100.00;
    foreach ($financialItems['values'] as $id => $financialItem) {
      $this->assertEquals($expectedAmount, $financialItem['amount']);
      $this->assertNotEmpty($financialItem['financial_account_id']);
      $this->assertEquals($contributionCompletedStatusID, $financialItem['status_id']);
      $expectedAmount = -$expectedAmount;
    }
  }

}
