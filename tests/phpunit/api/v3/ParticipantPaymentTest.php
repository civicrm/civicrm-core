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

  /**
   * @var int
   */
  protected $contactID;

  /**
   * @var int
   */
  protected $contactID2;

  /**
   * @var int
   */
  protected $contactID3;

  /**
   * @var int
   */
  protected $participantID;

  /**
   * @var int
   */
  protected $participantID2;

  /**
   * @var int
   */
  protected $participantID3;

  /**
   * @var int
   */
  protected $participantID4;

  /**
   * Set up for tests.
   */
  public function setUp(): void {
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->eventCreatePaid();
    $this->contactID = $this->individualCreate();
    $this->individualCreate();

    $this->participantID = $this->participantCreate([
      'contact_id' => $this->contactID,
      'event_id' => $this->getEventID(),
    ]);
    $contactID2 = $this->individualCreate();
    $this->participantID2 = $this->participantCreate([
      'contact_id' => $contactID2,
      'event_id' => $this->getEventID(),
    ]);
    $this->participantID3 = $this->participantCreate([
      'contact_id' => $contactID2,
      'event_id' => $this->getEventID(),
    ]);

    $this->contactID3 = $this->individualCreate();
    $this->participantID4 = $this->participantCreate([
      'contact_id' => $this->contactID3,
      'event_id' => $this->getEventID(),
    ]);
  }

  /**
   * Check with valid array.
   */
  public function testPaymentCreate(): void {
    $this->callAPISuccess('ParticipantPayment', 'create', [
      'participant_id' => $this->participantID,
      'contribution_id' => $this->contributionCreate(['contact_id' => $this->individualCreate()]),
    ]);
  }

  /**
   * Test getPaymentInfo() returns correct
   * information of the participant payment
   *
   * @throws \CRM_Core_Exception
   */
  public function testPaymentInfoForEvent(): void {
    //Create Contribution & get contribution ID
    $contributionID = $this->contributionCreate(['contact_id' => $this->contactID]);

    //Create Participant Payment record With Values
    $params = [
      'participant_id' => $this->participantID4,
      'contribution_id' => $contributionID,
    ];
    $this->callAPISuccess('participantPayment', 'create', $params);

    //Check if participant payment is correctly retrieved.
    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($this->participantID4, 'event');
    $this->assertEquals('Completed', $paymentInfo['contribution_status']);
    $this->assertEquals('100.00', $paymentInfo['total']);
  }

  /**
   * Check financial records for offline Participants.
   */
  public function testPaymentOffline(): void {

    // create contribution w/o fee
    $contributionID = $this->contributionCreate([
      'contact_id' => $this->contactID,
      'financial_type_id' => 1,
      'payment_instrument_id' => 4,
      'fee_amount' => 0,
      'net_amount' => 100,
    ]);

    $participantPaymentID = $this->participantPaymentCreate($this->participantID, $contributionID);
    $params = [
      'id' => $participantPaymentID,
      'participant_id' => $this->participantID,
      'contribution_id' => $contributionID,
    ];

    // Update Payment
    $participantPayment = $this->callAPISuccess('participantPayment', 'create', $params);
    $this->assertEquals($participantPayment['id'], $participantPaymentID);
    $this->assertArrayHasKey('id', $participantPayment);
    // check Financial records
    $this->_checkFinancialRecords($params, 'offline');
  }

  /**
   * Check financial records for online Participant.
   */
  public function testPaymentOnline(): void {

    $pageParams['processor_id'] = $this->processorCreate();
    $contributionPage = $this->contributionPageCreate($pageParams);
    $contributionParams = [
      'contact_id' => $this->contactID,
      'contribution_page_id' => $contributionPage['id'],
      'payment_processor' => $pageParams['processor_id'],
      'financial_type_id' => 1,
    ];
    $contributionID = $this->contributionCreate($contributionParams);

    $participantPaymentID = $this->participantPaymentCreate($this->participantID, $contributionID);
    $params = [
      'id' => $participantPaymentID,
      'participant_id' => $this->participantID,
      'contribution_id' => $contributionID,
    ];

    // Update Payment
    $participantPayment = $this->callAPISuccess('participantPayment', 'create', $params);
    $this->assertEquals($participantPayment['id'], $participantPaymentID);
    $this->assertArrayHasKey('id', $participantPayment);
    // check Financial records
    $this->_checkFinancialRecords($params, 'online');
  }

  /**
   * Check financial records for online Participant pay later scenario.
   */
  public function testPaymentPayLaterOnline(): void {
    $pageParams['processor_id'] = $this->processorCreate();
    $pageParams['is_pay_later'] = 1;
    $contributionPage = $this->contributionPageCreate($pageParams);
    $contributionParams = [
      'contact_id' => $this->contactID,
      'contribution_page_id' => $contributionPage['id'],
      'contribution_status_id' => 2,
      'is_pay_later' => 1,
      'financial_type_id' => 1,
    ];
    $contributionID = $this->contributionCreate($contributionParams);

    $participantPaymentID = $this->participantPaymentCreate($this->participantID, $contributionID);
    $params = [
      'id' => $participantPaymentID,
      'participant_id' => $this->participantID,
      'contribution_id' => $contributionID,
    ];

    // Update Payment
    $participantPayment = $this->callAPISuccess('participantPayment', 'create', $params);
    // check Financial Records
    $this->_checkFinancialRecords($params, 'payLater');
    $this->assertEquals($participantPayment['id'], $participantPaymentID);
    $this->assertArrayHasKey('id', $participantPayment);
  }

  /**
   * Check with invalid id.
   */
  public function testPaymentDeleteWithWrongID(): void {
    $this->callAPIFailure('ParticipantPayment', 'delete', ['id' => 0], 'Error while deleting participantPayment');
  }

  /**
   * Check with valid array.
   */
  public function testPaymentDelete(): void {
    $contributionID = $this->contributionCreate([
      'contact_id' => $this->contactID,
    ]);

    $participantPaymentID = $this->participantPaymentCreate($this->participantID, $contributionID);

    $params = [
      'id' => $participantPaymentID,
    ];
    $this->callAPISuccess('participantPayment', 'delete', $params);
  }

  /**
   * Test civicrm_participantPayment_get - success expected.
   */
  public function testGet(): void {
    $contributionID = $this->contributionCreate(['contact_id' => $this->contactID3]);
    $this->participantPaymentCreate($this->participantID4, $contributionID);

    //Create Participant Payment record With Values
    $params = [
      'participant_id' => $this->participantID4,
      'contribution_id' => $contributionID,
    ];

    $result = $this->callAPISuccess('participantPayment', 'get', $params);
    $this->assertEquals($result['values'][$result['id']]['participant_id'], $this->participantID4, 'Check Participant Id');
    $this->assertEquals($result['values'][$result['id']]['contribution_id'], $contributionID, 'Check Contribution Id');
  }

  /**
   * @param array $params
   * @param $context
   */
  public function _checkFinancialRecords($params, $context): void {
    $entityParams = [
      'entity_id' => $params['id'],
      'entity_table' => 'civicrm_contribution',
    ];
    $trxn = current($this->retrieveEntityFinancialTrxn($entityParams));
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
    $entityTrxn = current($this->retrieveEntityFinancialTrxn($entityParams));
    $financialItemParams = [
      'id' => $entityTrxn['entity_id'],
    ];
    if ($context === 'offline' || $context === 'online') {
      $compareParams = [
        'amount' => 100,
        'status_id' => 1,
        'financial_account_id' => 1,
      ];
    }
    elseif ($context === 'payLater') {
      $compareParams = [
        'amount' => 100,
        'status_id' => 3,
        'financial_account_id' => 1,
      ];
    }
    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialItem', $financialItemParams, $compareParams);
  }

  /**
   * test getParticipantIds() function
   */
  public function testGetParticipantIDs(): void {
    $contributionID = $this->contributionCreate(['contact_id' => $this->contactID]);
    $expectedParticipants = [$this->participantID, $this->participantID2];

    //Create Participant Payment record With Values
    foreach ($expectedParticipants as $pid) {
      $params = [
        'participant_id' => $pid,
        'contribution_id' => $contributionID,
      ];
      $this->callAPISuccess('participantPayment', 'create', $params);
    }
    //Check if all participants are listed.
    $participants = CRM_Event_BAO_Participant::getParticipantIds($contributionID);
    $this->checkArrayEquals($expectedParticipants, $participants);
    //delete created contribution
    $this->contributionDelete($contributionID);
  }

}
