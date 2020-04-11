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
 *  Test APIv3 civicrm_participant_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Event
 * @group headless
 */
class api_v3_ParticipantPaymentTest extends CiviUnitTestCase {

  protected $_apiversion = 3;
  protected $_contactID;
  protected $_createdParticipants;
  protected $_participantID;
  protected $_eventID;
  protected $_participantPaymentID;
  protected $_financialTypeId;

  /**
   * Set up for tests.
   */
  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
    $event = $this->eventCreate(NULL);
    $this->_eventID = $event['id'];
    $this->_contactID = $this->individualCreate();
    $this->_createdParticipants = [];
    $this->_individualId = $this->individualCreate();
    $this->_financialTypeId = 1;

    $this->_participantID = $this->participantCreate([
      'contactID' => $this->_contactID,
      'eventID' => $this->_eventID,
    ]);
    $this->_contactID2 = $this->individualCreate();
    $this->_participantID2 = $this->participantCreate([
      'contactID' => $this->_contactID2,
      'eventID' => $this->_eventID,
    ]);
    $this->_participantID3 = $this->participantCreate([
      'contactID' => $this->_contactID2,
      'eventID' => $this->_eventID,
    ]);

    $this->_contactID3 = $this->individualCreate();
    $this->_participantID4 = $this->participantCreate([
      'contactID' => $this->_contactID3,
      'eventID' => $this->_eventID,
    ]);
  }

  /**
   * Test civicrm_participant_payment_create with empty params.
   */
  public function testPaymentCreateEmptyParams() {
    $params = [];
    $this->callAPIFailure('participant_payment', 'create', $params);
  }

  /**
   * Check without contribution_id.
   */
  public function testPaymentCreateMissingContributionId() {
    //Without Payment EntityID
    $params = [
      'participant_id' => $this->_participantID,
    ];
    $this->callAPIFailure('participant_payment', 'create', $params);
  }

  /**
   * Check with valid array.
   */
  public function testPaymentCreate() {
    //Create Contribution & get contribution ID
    $contributionID = $this->contributionCreate(['contact_id' => $this->_contactID]);

    //Create Participant Payment record With Values
    $params = [
      'participant_id' => $this->_participantID,
      'contribution_id' => $contributionID,
    ];

    $result = $this->callAPIAndDocument('participant_payment', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertTrue(array_key_exists('id', $result));

    //delete created contribution
    $this->contributionDelete($contributionID);
  }

  /**
   * Test getPaymentInfo() returns correct
   * information of the participant payment
   */
  public function testPaymentInfoForEvent() {
    //Create Contribution & get contribution ID
    $contributionID = $this->contributionCreate(['contact_id' => $this->_contactID]);

    //Create Participant Payment record With Values
    $params = [
      'participant_id' => $this->_participantID4,
      'contribution_id' => $contributionID,
    ];
    $this->callAPISuccess('participant_payment', 'create', $params);

    //Check if participant payment is correctly retrieved.
    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($this->_participantID4, 'event');
    $this->assertEquals('Completed', $paymentInfo['contribution_status']);
    $this->assertEquals('100.00', $paymentInfo['total']);
  }

  ///////////////// civicrm_participant_payment_create methods

  /**
   * Check with empty array.
   */
  public function testPaymentUpdateEmpty() {
    $this->callAPIFailure('participant_payment', 'create', []);
  }

  /**
   * Check with missing participant_id.
   */
  public function testPaymentUpdateMissingParticipantId() {
    $params = [
      'contribution_id' => '3',
    ];
    $this->callAPIFailure('participant_payment', 'create', $params);
  }

  /**
   * Check with missing contribution_id.
   */
  public function testPaymentUpdateMissingContributionId() {
    $params = [
      'participant_id' => $this->_participantID,
    ];
    $participantPayment = $this->callAPIFailure('participant_payment', 'create', $params);
  }

  /**
   * Check financial records for offline Participants.
   */
  public function testPaymentOffline() {

    // create contribution w/o fee
    $contributionID = $this->contributionCreate([
      'contact_id' => $this->_contactID,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 4,
      'fee_amount' => 0,
      'net_amount' => 100,
    ]);

    $this->_participantPaymentID = $this->participantPaymentCreate($this->_participantID, $contributionID);
    $params = [
      'id' => $this->_participantPaymentID,
      'participant_id' => $this->_participantID,
      'contribution_id' => $contributionID,
    ];

    // Update Payment
    $participantPayment = $this->callAPISuccess('participant_payment', 'create', $params);
    $this->assertEquals($participantPayment['id'], $this->_participantPaymentID);
    $this->assertTrue(array_key_exists('id', $participantPayment));
    // check Financial records
    $this->_checkFinancialRecords($params, 'offline');
    $params = [
      'id' => $this->_participantPaymentID,
    ];
    $deletePayment = $this->callAPISuccess('participant_payment', 'delete', $params);
  }

  /**
   * Check financial records for online Participant.
   */
  public function testPaymentOnline() {

    $pageParams['processor_id'] = $this->processorCreate();
    $contributionPage = $this->contributionPageCreate($pageParams);
    $contributionParams = [
      'contact_id' => $this->_contactID,
      'contribution_page_id' => $contributionPage['id'],
      'payment_processor' => $pageParams['processor_id'],
      'financial_type_id' => 1,
    ];
    $contributionID = $this->contributionCreate($contributionParams);

    $this->_participantPaymentID = $this->participantPaymentCreate($this->_participantID, $contributionID);
    $params = [
      'id' => $this->_participantPaymentID,
      'participant_id' => $this->_participantID,
      'contribution_id' => $contributionID,
    ];

    // Update Payment
    $participantPayment = $this->callAPISuccess('participant_payment', 'create', $params);
    $this->assertEquals($participantPayment['id'], $this->_participantPaymentID);
    $this->assertTrue(array_key_exists('id', $participantPayment));
    // check Financial records
    $this->_checkFinancialRecords($params, 'online');
    $params = [
      'id' => $this->_participantPaymentID,
    ];
    $this->callAPISuccess('participant_payment', 'delete', $params);
  }

  /**
   * Check financial records for online Participant pay later scenario.
   */
  public function testPaymentPayLaterOnline() {
    $pageParams['processor_id'] = $this->processorCreate();
    $pageParams['is_pay_later'] = 1;
    $contributionPage = $this->contributionPageCreate($pageParams);
    $contributionParams = [
      'contact_id' => $this->_contactID,
      'contribution_page_id' => $contributionPage['id'],
      'contribution_status_id' => 2,
      'is_pay_later' => 1,
      'financial_type_id' => 1,
    ];
    $contributionID = $this->contributionCreate($contributionParams);

    $this->_participantPaymentID = $this->participantPaymentCreate($this->_participantID, $contributionID);
    $params = [
      'id' => $this->_participantPaymentID,
      'participant_id' => $this->_participantID,
      'contribution_id' => $contributionID,
    ];

    // Update Payment
    $participantPayment = $this->callAPISuccess('participant_payment', 'create', $params);
    // check Financial Records
    $this->_checkFinancialRecords($params, 'payLater');
    $this->assertEquals($participantPayment['id'], $this->_participantPaymentID);
    $this->assertTrue(array_key_exists('id', $participantPayment));
    $params = [
      'id' => $this->_participantPaymentID,
    ];
    $this->callAPISuccess('participant_payment', 'delete', $params);
  }

  /**
   * Check with empty array.
   */
  public function testPaymentDeleteWithEmptyParams() {
    $params = [];
    $deletePayment = $this->callAPIFailure('participant_payment', 'delete', $params);
    $this->assertEquals('Mandatory key(s) missing from params array: id', $deletePayment['error_message']);
  }

  /**
   * Check with wrong id.
   */
  public function testPaymentDeleteWithWrongID() {
    $params = [
      'id' => 0,
    ];
    $deletePayment = $this->callAPIFailure('participant_payment', 'delete', $params);
    $this->assertEquals($deletePayment['error_message'], 'Error while deleting participantPayment');
  }

  /**
   * Check with valid array.
   */
  public function testPaymentDelete() {
    $contributionID = $this->contributionCreate([
      'contact_id' => $this->_contactID,
    ]);

    $this->_participantPaymentID = $this->participantPaymentCreate($this->_participantID, $contributionID);

    $params = [
      'id' => $this->_participantPaymentID,
    ];
    $this->callAPIAndDocument('participant_payment', 'delete', $params, __FUNCTION__, __FILE__);
  }

  /**
   * Test civicrm_participantPayment_get - success expected.
   */
  public function testGet() {
    $contributionID = $this->contributionCreate(['contact_id' => $this->_contactID3]);
    $this->participantPaymentCreate($this->_participantID4, $contributionID);

    //Create Participant Payment record With Values
    $params = [
      'participant_id' => $this->_participantID4,
      'contribution_id' => $contributionID,
    ];

    $result = $this->callAPIAndDocument('participant_payment', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][$result['id']]['participant_id'], $this->_participantID4, 'Check Participant Id');
    $this->assertEquals($result['values'][$result['id']]['contribution_id'], $contributionID, 'Check Contribution Id');
  }

  /**
   * @param array $params
   * @param $context
   */
  public function _checkFinancialRecords($params, $context) {
    $entityParams = [
      'entity_id' => $params['id'],
      'entity_table' => 'civicrm_contribution',
    ];
    $trxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams));
    $trxnParams = [
      'id' => $trxn['financial_trxn_id'],
    ];

    switch ($context) {
      case 'online':
        $compareParams = [
          'to_financial_account_id' => 12,
          'total_amount' => 100,
          'status_id' => 1,
        ];
        break;

      case 'offline':
        $compareParams = [
          'to_financial_account_id' => 6,
          'total_amount' => 100,
          'status_id' => 1,
        ];
        break;

      case 'payLater':
        $compareParams = [
          'to_financial_account_id' => 7,
          'total_amount' => 100,
          'status_id' => 2,
        ];
        break;
    }

    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn', $trxnParams, $compareParams);
    $entityParams = [
      'financial_trxn_id' => $trxn['financial_trxn_id'],
      'entity_table' => 'civicrm_financial_item',
    ];
    $entityTrxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams));
    $fitemParams = [
      'id' => $entityTrxn['entity_id'],
    ];
    if ($context == 'offline' || $context == 'online') {
      $compareParams = [
        'amount' => 100,
        'status_id' => 1,
        'financial_account_id' => 1,
      ];
    }
    elseif ($context == 'payLater') {
      $compareParams = [
        'amount' => 100,
        'status_id' => 3,
        'financial_account_id' => 1,
      ];
    }
    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialItem', $fitemParams, $compareParams);
  }

  /**
   * test getParticipantIds() function
   */
  public function testGetParticipantIds() {
    $contributionID = $this->contributionCreate(['contact_id' => $this->_contactID]);
    $expectedParticipants = [$this->_participantID, $this->_participantID2];

    //Create Participant Payment record With Values
    foreach ($expectedParticipants as $pid) {
      $params = [
        'participant_id' => $pid,
        'contribution_id' => $contributionID,
      ];
      $this->callAPISuccess('participant_payment', 'create', $params);
    }
    //Check if all participants are listed.
    $participants = CRM_Event_BAO_Participant::getParticipantIds($contributionID);
    $this->checkArrayEquals($expectedParticipants, $participants);
    //delete created contribution
    $this->contributionDelete($contributionID);
  }

}
