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
 * Test class for Pledge API - civicrm_pledge_*
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_PledgePaymentTest extends CiviUnitTestCase {

  protected $_individualId;
  protected $_pledgeID;
  protected $_contributionID;
  protected $_financialTypeId = 1;
  protected $_entity = 'PledgePayment';

  /**
   * Setup for tests.
   *
   * @throws \CRM_Core_Exception
   */
  public function setUp() {
    parent::setUp();
    $this->_individualId = $this->individualCreate();
    $this->_pledgeID = $this->pledgeCreate(['contact_id' => $this->_individualId]);
    $this->_contributionID = $this->contributionCreate(['contact_id' => $this->_individualId]);
  }

  /**
   * Clean up after function.
   * @throws \CRM_Core_Exception
   */
  public function tearDown() {
    $tablesToTruncate = [
      'civicrm_contribution',
      'civicrm_contact',
      'civicrm_pledge',
      'civicrm_pledge_payment',
      'civicrm_line_item',
    ];

    $this->quickCleanup($tablesToTruncate);
    parent::tearDown();
  }

  public function testGetPledgePayment() {
    $params = [];
    $result = $this->callAPIAndDocument('pledge_payment', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(5, $result['count'], " in line " . __LINE__);
  }

  /**
   * Test that passing in a single variable works.
   */
  public function testGetSinglePledgePayment() {
    $createparams = [
      'contact_id' => $this->_individualId,
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'status_id' => 1,
    ];
    $createResult = $this->callAPISuccess('pledge_payment', 'create', $createparams);
    $params = [
      'contribution_id' => $this->_contributionID,
    ];
    $result = $this->callAPISuccess('pledge_payment', 'get', $params);
    $this->assertEquals(1, $result['count'], " in line " . __LINE__);
  }

  /**
   * Test process_pledge job log.
   */
  public function testProcessPledgeJob() {
    $pledgeStatuses = CRM_Core_OptionGroup::values('pledge_status',
      FALSE, FALSE, FALSE, NULL, 'name'
    );
    //Make first payment.
    $paymentParams = [
      'contact_id' => $this->_individualId,
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'scheduled_date' => date('Ymd', strtotime("-1 days")),
      'status_id' => array_search('Pending', $pledgeStatuses),
    ];
    $firstPayment = $this->callAPISuccess('pledge_payment', 'create', $paymentParams);
    //Status should be 'Pending' after first incomplete payment.
    $checkStatus = $this->callAPISuccess('pledge', 'getsingle', [
      'id' => $this->_pledgeID,
      'return' => 'pledge_status',
    ]);
    $this->assertEquals('Pending Label**', $checkStatus['pledge_status']);

    //Execute process_pledge job log.
    $result = $this->callAPISuccess('Job', 'process_pledge', []);
    $this->assertEquals("Checking if status update is needed for Pledge Id: {$this->_pledgeID} (current status is Pending)\n\r- status updated to: Overdue\n\r1 records updated.", $result['values']);

    //Status should be 'Overdue' after processing.
    $statusAfterProcessing = $this->callAPISuccess('pledge', 'getsingle', [
      'id' => $this->_pledgeID,
      'return' => 'pledge_status',
    ]);
    $this->assertEquals('Overdue', $statusAfterProcessing['pledge_status']);
  }

  /**
   * Test status of pledge on payments and cancellation.
   */
  public function testPledgeStatus() {
    //Status should initially be Pending.
    $checkStatus = $this->callAPISuccess('pledge', 'getsingle', [
      'id' => $this->_pledgeID,
      'return' => 'pledge_status',
    ]);
    $this->assertEquals('Pending Label**', $checkStatus['pledge_status']);

    //Make first payment.
    $paymentParams = [
      'contact_id' => $this->_individualId,
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'status_id' => 1,
    ];
    $firstPayment = $this->callAPISuccess('pledge_payment', 'create', $paymentParams);

    //Status should be 'In Progress' after first payment.
    $checkStatus = $this->callAPISuccess('pledge', 'getsingle', [
      'id' => $this->_pledgeID,
      'return' => 'pledge_status',
    ]);
    $this->assertEquals('In Progress', $checkStatus['pledge_status']);

    //Cancel the Pledge.
    $paymentStatusTypes = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $updateParams = [
      'id' => $this->_pledgeID,
      'status_id' => array_search('Cancelled', $paymentStatusTypes),
    ];
    $this->callAPISuccess('pledge', 'create', $updateParams);

    //Status should be calculated as Cancelled.
    $pledgeStatus = CRM_Pledge_BAO_PledgePayment::calculatePledgeStatus($this->_pledgeID);
    $this->assertEquals('Cancelled', $paymentStatusTypes[$pledgeStatus]);

    //Already completed payments should not be cancelled.
    $checkPaymentStatus = $this->callAPISuccess('pledge_payment', 'getsingle', [
      'id' => $firstPayment['id'],
      'return' => 'status_id',
    ]);
    $this->assertEquals(array_search('Completed', $paymentStatusTypes), $checkPaymentStatus['status_id']);
  }

  /**
   * Test that passing in a single variable works:: status_id
   */
  public function testGetSinglePledgePaymentByStatusID() {
    $createparams = [
      'contact_id' => $this->_individualId,
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'status_id' => 1,
    ];
    $createResult = $this->callAPISuccess('pledge_payment', 'create', $createparams);
    $params = [
      'status_id' => 1,
    ];

    $result = $this->callAPISuccess('pledge_payment', 'get', $params);
    $this->assertEquals(1, $result['count'], " in line " . __LINE__);
  }

  /**
   * Test that creating a payment will add the contribution ID.
   */
  public function testCreatePledgePayment() {
    //check that 5 pledge payments exist at the start
    $beforeAdd = $this->callAPISuccess('pledge_payment', 'get', []);
    $this->assertEquals(5, $beforeAdd['count'], " in line " . __LINE__);

    //test the pledge_payment_create function
    $params = [
      'contact_id' => $this->_individualId,
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'status_id' => 1,
      'actual_amount' => 20,
    ];
    $result = $this->callAPIAndDocument('pledge_payment', 'create', $params, __FUNCTION__, __FILE__);

    //check existing updated not new one created - 'create' means add contribution_id in this context
    $afterAdd = $this->callAPISuccess('pledge_payment', 'get', []);
    $this->assertEquals(5, $afterAdd['count'], " in line " . __LINE__);

    //get the created payment & check it out
    $getParams['id'] = $result['id'];
    $getIndPayment = $this->callAPISuccess('pledge_payment', 'get', $getParams);
    $this->assertEquals(1, $getIndPayment['count'], " in line " . __LINE__);
    $this->assertEquals(20, $getIndPayment['values'][$result['id']]['actual_amount'], " in line " . __LINE__);

    //create a second pledge payment - need a contribution first &can't use the CiviUnitTest case function as invoice is hard-coded
    $contributionParams = [
      'total_amount' => 20,
      'contact_id' => $this->_individualId,
      'financial_type_id' => $this->_financialTypeId,
    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $contributionParams);
    $params['contribution_id'] = $contribution['id'];

    $resultCont2 = $this->callAPISuccess('pledge_payment', 'create', $params);
    //make sure original is untouched & has not been updated
    $this->assertGreaterThan($result['id'], $resultCont2['id'], " in line " . __LINE__);
    $getIndPaymentAgain = $this->callAPISuccess('pledge_payment', 'get', $getParams);
    $this->assertEquals(1, $getIndPaymentAgain['count'], " in line " . __LINE__);
    $this->assertEquals($this->_contributionID, $getIndPaymentAgain['values'][$result['id']]['contribution_id'], " in line " . __LINE__);
  }

  /**
   * Test checks behaviour when more payments are created than should be possible.
   */
  public function testCreatePledgePaymentAllCreated() {
    $params = [
      'pledge_id' => $this->_pledgeID,
      'status_id' => 1,
    ];
    // create one more pledge than there are spaces for
    $i = 0;
    while ($i <= 5) {
      $contributionParams = [
        'total_amount' => 20,
        'contact_id' => $this->_individualId,
        'financial_type_id' => $this->_financialTypeId,
      ];
      $contribution = $this->callAPISuccess('contribution', 'create', $contributionParams);

      $params['contribution_id'] = $contribution['id'];

      $resultCont2 = civicrm_api('pledge_payment', 'create', $params + ['version' => $this->_apiversion]);
      $i++;
    }
    // check that only 5 exist & we got an error setting the 6th
    $result = $this->callAPISuccess('PledgePayment', 'Get', [
      'pledge_id' => $this->_pledgeID,
    ]);
    // the last one above should result in an error
    $this->assertEquals("There are no unmatched payment on this pledge. Pass in the pledge_payment id to specify one or 'option.create_new' to create one", $resultCont2['error_message']);
    $this->assertEquals(5, $result['count']);

    $params['option.create_new'] = 1;
    $params['scheduled_amount'] = 20;
    $params['scheduled_date'] = '20131212';
    $resultcreatenew = $this->callAPISuccess('pledge_payment', 'create', $params);
    $result = $this->callAPISuccess('PledgePayment', 'Get', [
      'pledge_id' => $this->_pledgeID,
    ]);

    $this->assertEquals(6, $result['count']);
  }

  /**
   * Test that creating a payment adds the contribution ID where only one pledge payment is in schedule.
   */
  public function testCreatePledgePaymentWhereOnlyOnePayment() {
    $pledgeParams = [
      'contact_id' => $this->_individualId,
      'pledge_create_date' => date('Ymd'),
      'start_date' => date('Ymd'),
      'scheduled_date' => 'first day 2015',
      'pledge_amount' => 100.00,
      'pledge_status_id' => '2',
      'pledge_financial_type_id' => '1',
      'pledge_original_installment_amount' => 20,
      'frequency_interval' => 5,
      'frequency_unit' => 'year',
      'frequency_day' => 15,
      'installments' => 1,
      'sequential' => 1,
    ];

    $contributionID = $this->contributionCreate([
      'contact_id' => $this->_individualId,
      'financial_type_id' => $this->_financialTypeId,
      'invoice_id' => 45,
      'trxn_id' => 45,
    ]);
    $pledge = $this->callAPISuccess('Pledge', 'Create', $pledgeParams);

    //test the pledge_payment_create function
    $params = [
      'contact_id' => $this->_individualId,
      'pledge_id' => $pledge['id'],
      'contribution_id' => $contributionID,
      'status_id' => 1,
      'actual_amount' => 20,
    ];
    $result = $this->callAPISuccess('pledge_payment', 'create', $params);

    //check existing updated not new one created - 'create' means add contribution_id in this context
    $afterAdd = $this->callAPISuccess('pledge_payment', 'get', [
      'contribution_id' => $contributionID,
    ]);
    $this->assertEquals(1, $afterAdd['count'], " in line " . __LINE__);
  }

  public function testUpdatePledgePayment() {
    $params = [
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'status_id' => 2,
      'actual_amount' => 20,
    ];
    $result = $this->callAPISuccess('pledge_payment', 'create', $params);
    $updateparams = [
      'id' => $result['id'],
      'status_id' => 1,
    ];

    $result = $this->callAPIAndDocument('pledge_payment', 'update', $updateparams, __FUNCTION__, __FILE__);
    $this->getAndCheck(array_merge($params, $updateparams), $result['id'], $this->_entity);
  }

  public function testDeletePledgePayment() {
    $params = [
      'contact_id' => $this->_individualId,
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'status_id' => 1,
      'sequential' => 1,
      'actual_amount' => 20,
    ];
    $pledgePayment = $this->callAPISuccess('pledge_payment', 'create', $params);

    $deleteParams = [
      'id' => $pledgePayment['id'],
    ];
    $result = $this->callAPIAndDocument('pledge_payment', 'delete', $deleteParams, __FUNCTION__, __FILE__);
  }

  public function testGetFields() {
    $result = $this->callAPISuccess('PledgePayment', 'GetFields', []);
    $this->assertType('array', $result);
  }

}
