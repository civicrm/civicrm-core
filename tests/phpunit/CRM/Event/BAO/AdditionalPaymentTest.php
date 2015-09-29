<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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


require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';
require_once 'CiviTest/Event.php';
require_once 'CiviTest/Participant.php';

/**
 * Class CRM_Event_BAO_AdditionalPaymentTest
 */
class CRM_Event_BAO_AdditionalPaymentTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->_contactId = Contact::createIndividual();
    $this->_eventId = Event::create($this->_contactId);
  }

  public function tearDown() {
    $this->eventDelete($this->_eventId);
    $this->quickCleanup(
      array(
        'civicrm_contact',
        'civicrm_contribution',
        'civicrm_participant',
        'civicrm_participant_payment',
        'civicrm_line_item',
        'civicrm_financial_item',
        'civicrm_financial_trxn',
        'civicrm_entity_financial_trxn',
      ),
      TRUE
    );
  }

  /**
   * helper function to record participant with paid contribution.
   * @param $feeTotal
   * @param $actualPaidAmt
   *
   * @return array
   * @throws Exception
   */
  public function _addParticipantWithPayment($feeTotal, $actualPaidAmt) {
    // creating price set, price field
    $paramsSet['title'] = 'Price Set';
    $paramsSet['name'] = CRM_Utils_String::titleToVar('Price Set');
    $paramsSet['is_active'] = FALSE;
    $paramsSet['extends'] = 1;

    $priceset = CRM_Price_BAO_PriceSet::create($paramsSet);
    CRM_Price_BAO_PriceSet::addTo('civicrm_event', $this->_eventId, $priceset->id);
    $priceSetId = $priceset->id;

    //Checking for priceset added in the table.
    $this->assertDBCompareValue('CRM_Price_BAO_PriceSet', $priceSetId, 'title',
      'id', $paramsSet['title'], 'Check DB for created priceset'
    );
    $paramsField = array(
      'label' => 'Price Field',
      'name' => CRM_Utils_String::titleToVar('Price Field'),
      'html_type' => 'Text',
      'price' => $feeTotal,
      'option_label' => array('1' => 'Price Field'),
      'option_value' => array('1' => $feeTotal),
      'option_name' => array('1' => $feeTotal),
      'option_weight' => array('1' => 1),
      'option_amount' => array('1' => 1),
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'is_active' => array('1' => 1),
      'price_set_id' => $priceset->id,
      'is_enter_qty' => 1,
    );

    $ids = array();
    $pricefield = CRM_Price_BAO_PriceField::create($paramsField, $ids);

    //Checking for priceset added in the table.
    $this->assertDBCompareValue('CRM_Price_BAO_PriceField', $pricefield->id, 'label',
      'id', $paramsField['label'], 'Check DB for created pricefield'
    );

    // create participant record
    $eventId = $this->_eventId;
    $participantParams = array(
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
    );
    $participant = $this->callAPISuccess('participant', 'create', $participantParams);
    $this->callAPISuccessGetSingle('participant', array('id' => $participant['id']));
    // create participant contribution with partial payment
    $contributionParams = array(
      'total_amount' => $actualPaidAmt,
      'source' => 'Fall Fundraiser Dinner: Offline registration',
      'currency' => 'USD',
      'non_deductible_amount' => 'null',
      'receipt_date' => date('Y-m-d') . " 00:00:00",
      'contact_id' => $this->_contactId,
      'financial_type_id' => 4,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 1,
      'receive_date' => date('Y-m-d') . " 00:00:00",
      'skipLineItem' => 1,
      'partial_payment_total' => $feeTotal,
      'partial_amount_pay' => $actualPaidAmt,
    );

    $contribution = CRM_Contribute_BAO_Contribution::create($contributionParams);
    $contributionId = $contribution->id;
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
    CRM_Price_BAO_PriceSet::processAmount($feeBlock,
      $params, $lineItem
    );
    $lineItemVal[$priceSetId] = $lineItem;
    CRM_Price_BAO_LineItem::processPriceSet($participant['id'], $lineItemVal, $contribution, 'civicrm_participant');
    return array($participant, $contribution);
  }

  /**
   * CRM-13964
   */
  public function testAddPartialPayment() {
    $feeAmt = 100;
    $amtPaid = 60;
    $balance = $feeAmt - $amtPaid;
    list($participant, $contribution) = $this->_addParticipantWithPayment($feeAmt, $amtPaid);
    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($participant['id'], 'event');

    // amount checking
    $this->assertEquals(round($paymentInfo['total']), $feeAmt, 'Total amount recorded is not proper');
    $this->assertEquals(round($paymentInfo['paid']), $amtPaid, 'Amount paid is not proper');
    $this->assertEquals(round($paymentInfo['balance']), $balance, 'Balance amount is not proper');

    // status checking
    $this->assertEquals($participant['participant_status_id'], 14, 'Status record is not proper for participant');
    $this->assertEquals($contribution->contribution_status_id, 8, 'Status record is not proper for contribution');
  }

}
