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

  protected function cleanup() {
    $this->quickCleanup(
      array(
        'civicrm_contact',
        'civicrm_contribution',
        'civicrm_participant',
        'civicrm_participant_payment',
        'civicrm_line_item',
        'civicrm_activity',
        'civicrm_financial_item',
        'civicrm_financial_trxn',
        'civicrm_entity_financial_trxn',
        'civicrm_price_field_value',
        'civicrm_price_field',
        'civicrm_price_set',
        'civicrm_event',
      ),
      TRUE
      );
  }

  protected function eventPriceSetCreate() {

    $paramsSet['title'] = 'Two Options';
    $paramsSet['name'] = CRM_Utils_String::titleToVar('Two Options');
    $paramsSet['is_active'] = FALSE;
    $paramsSet['extends'] = 1;

    $priceset = CRM_Price_BAO_PriceSet::create($paramsSet);

    //Checking for priceset added in the table.
    $this->assertDBCompareValue('CRM_Price_BAO_PriceSet', $priceset->id, 'title',
        'id', $paramsSet['title'], 'Check DB for created priceset'
    );
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
      'price_set_id' => $priceset->id,
      'is_enter_qty' => 1,
      'financial_type_id' => $this->getFinancialTypeId('Event Fee'),
    );
    CRM_Price_BAO_PriceField::create($paramsField);
    return $priceset->id;
  }

  public function setUp() {
    parent::setUp();
    $this->cleanup();
    $this->_contactId = $this->individualCreate();
    $event = $this->eventCreate();
    $this->_eventId = $event['id'];
    $this->_priceSetID = $this->eventPriceSetCreate();
    CRM_Price_BAO_PriceSet::addTo('civicrm_event', $this->_eventId, $this->_priceSetID);
    $priceSet = CRM_Price_BAO_PriceSet::getSetDetail($this->_priceSetID, TRUE, FALSE);
    $priceSet = CRM_Utils_Array::value($this->_priceSetID, $priceSet);
    $this->_feeBlock = CRM_Utils_Array::value('fields', $priceSet);
    $this->registerParticipantAndPay();
  }

  private function contributionInvoice($contributionId) {

    $query = "
         select sum(line_total) total
         from   civicrm_line_item
         where  entity_table = 'civicrm_participant'
         and    entity_id = {$contributionId}";
    $dao = CRM_Core_DAO::executeQuery($query);

    $this->assertTrue($dao->fetch(), "Succeeded retrieving invoicetotal");
    return $dao->total;
  }

  private function totalIncome($participantId) {

    $query = "
        select sum(et.amount) total
        from   civicrm_entity_financial_trxn et
        ,      civicrm_financial_item fi
        ,      civicrm_line_item      li
        where  et.entity_table='civicrm_financial_item'
        and    fi.id = et.entity_id
        and    fi.entity_table='civicrm_line_item'
        and    fi.entity_id = li.id
        and    li.entity_table = 'civicrm_participant'
        and    li.entity_id = ${participantId}";
    $dao = CRM_Core_DAO::executeQuery($query);

    $this->assertTrue($dao->fetch(), "Succeeded retrieving total Income");
    return $dao->total;
  }

  private function totalAssets($contributionId) {

    $query = "
        select sum(amount) total
        from   civicrm_entity_financial_trxn et
        where  et.entity_table = 'civicrm_contribution'
        and    et.entity_id = $contributionId";
    $dao = CRM_Core_DAO::executeQuery($query);

    $this->assertTrue($dao->fetch(), "Succeeded retrieving total assets");
    return $dao->total;
  }

  private function balanceCheck($amount) {

    $this->assertEquals($this->contributionInvoice($this->_contributionId), $amount, "Invoice must a total of $amount");
    $this->assertEquals($this->totalIncome($this->_participantId), $amount, "The recorded income must be $amount ");
    $this->assertEquals($this->totalIncome($this->_contributionId), $amount, "The accumulated assets must be $amount ");
  }

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
      'partial_amount_pay' => $actualPaidAmt,
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

    $this->balanceCheck($this->_expensiveFee);
  }

  public function testCRM19273() {

    $PSparams['price_1'] = 2;
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($this->_participantId, 'participant');
    CRM_Event_BAO_Participant::changeFeeSelections($PSparams, $this->_participantId, $this->_contributionId, $this->_feeBlock, $lineItem, $this->_expensiveFee, $this->_priceSetID);
    $this->balanceCheck($this->_cheapFee);

    $PSparams['price_1'] = 1;
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($this->_participantId, 'participant');
    CRM_Event_BAO_Participant::changeFeeSelections($PSparams, $this->_participantId, $this->_contributionId, $this->_feeBlock, $lineItem, $this->_expensiveFee, $this->_priceSetID);
    $this->balanceCheck($this->_expensiveFee);

    $PSparams['price_1'] = 3;
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($this->_participantId, 'participant');

    CRM_Event_BAO_Participant::changeFeeSelections($PSparams, $this->_participantId, $this->_contributionId, $this->_feeBlock, $lineItem, $this->_expensiveFee, $this->_priceSetID);
    $this->balanceCheck($this->_veryExpensive);
  }

  public function tearDown() {
    $this->eventDelete($this->_eventId);
    $this->cleanup();
  }

}
