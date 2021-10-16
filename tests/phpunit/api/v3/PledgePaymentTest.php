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
   */
  public function setUp(): void {
    parent::setUp();
    $this->_individualId = $this->individualCreate();
    $this->_pledgeID = $this->pledgeCreate(['contact_id' => $this->_individualId]);
    $this->_contributionID = $this->contributionCreate(['contact_id' => $this->_individualId]);
  }

  /**
   * Clean up after function.
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  public function testGetPledgePayment(): void {
    $params = [];
    $result = $this->callAPIAndDocument('PledgePayment', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(5, $result['count']);
  }

  /**
   * Test that passing in a single variable works.
   */
  public function testGetSinglePledgePayment(): void {
    $this->callAPISuccess('PledgePayment', 'create', [
      'contact_id' => $this->_individualId,
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'status_id' => 1,
    ]);
    $result = $this->callAPISuccess('PledgePayment', 'get', ['contribution_id' => $this->_contributionID]);
    $this->assertEquals(1, $result['count']);
  }

  /**
   * Test process_pledge job log.
   */
  public function testProcessPledgeJob(): void {
    //Make first payment.
    $paymentParams = [
      'contact_id' => $this->_individualId,
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'scheduled_date' => date('Ymd', strtotime('-1 days')),
      'status_id' => 'Pending',
    ];
    $this->callAPISuccess('PledgePayment', 'create', $paymentParams);
    //Status should be 'Pending' after first incomplete payment.
    $checkStatus = $this->callAPISuccess('Pledge', 'getsingle', [
      'id' => $this->_pledgeID,
      'return' => 'pledge_status',
    ]);
    $this->assertEquals('Pending Label**', $checkStatus['pledge_status']);

    //Execute process_pledge job log.
    $result = $this->callAPISuccess('Job', 'process_pledge', []);
    $this->assertEquals("Checking if status update is needed for Pledge Id: $this->_pledgeID (current status is Pending)\n\r- status updated to: Overdue\n\r1 records updated.", $result['values']);

    //Status should be 'Overdue' after processing.
    $statusAfterProcessing = $this->callAPISuccess('Pledge', 'getsingle', [
      'id' => $this->_pledgeID,
      'return' => 'pledge_status',
    ]);
    $this->assertEquals('Overdue', $statusAfterProcessing['pledge_status']);
  }

  /**
   * Test status of pledge on payments and cancellation.
   */
  public function testPledgeStatus(): void {
    //Status should initially be Pending.
    $checkStatus = $this->callAPISuccess('Pledge', 'getsingle', [
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
    $firstPayment = $this->callAPISuccess('PledgePayment', 'create', $paymentParams);

    //Status should be 'In Progress' after first payment.
    $checkStatus = $this->callAPISuccess('Pledge', 'getsingle', [
      'id' => $this->_pledgeID,
      'return' => 'pledge_status',
    ]);
    $this->assertEquals('In Progress', $checkStatus['pledge_status']);

    //Cancel the Pledge.
    $paymentStatusTypes = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $updateParams = [
      'id' => $this->_pledgeID,
      'status_id' => 'Cancelled',
    ];
    $this->callAPISuccess('Pledge', 'create', $updateParams);

    //Status should be calculated as Cancelled.
    $pledgeStatus = CRM_Pledge_BAO_PledgePayment::calculatePledgeStatus($this->_pledgeID);
    $this->assertEquals('Cancelled', $paymentStatusTypes[$pledgeStatus]);

    //Already completed payments should not be cancelled.
    $checkPaymentStatus = $this->callAPISuccess('PledgePayment', 'getsingle', [
      'id' => $firstPayment['id'],
      'return' => 'status_id',
    ]);
    $this->assertEquals('Completed', CRM_Core_PseudoConstant::getName('CRM_Pledge_BAO_Pledge', 'status_id', $checkPaymentStatus['status_id']));
  }

  /**
   * Test that passing in a single variable works:: status_id
   */
  public function testGetSinglePledgePaymentByStatusID(): void {
    $this->callAPISuccess('PledgePayment', 'create', [
      'contact_id' => $this->_individualId,
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'status_id' => 1,
    ]);
    $params = [
      'status_id' => 1,
    ];

    $result = $this->callAPISuccess('PledgePayment', 'get', $params);
    $this->assertEquals(1, $result['count']);
  }

  /**
   * Test that creating a payment will add the contribution ID.
   */
  public function testCreatePledgePayment(): void {
    //check that 5 pledge payments exist at the start
    $beforeAdd = $this->callAPISuccess('PledgePayment', 'get', []);
    $this->assertEquals(5, $beforeAdd['count']);

    //test the pledge_payment_create function
    $params = [
      'contact_id' => $this->_individualId,
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'status_id' => 1,
      'actual_amount' => 20,
    ];
    $result = $this->callAPIAndDocument('PledgePayment', 'create', $params, __FUNCTION__, __FILE__);

    //check existing updated not new one created - 'create' means add contribution_id in this context
    $afterAdd = $this->callAPISuccess('PledgePayment', 'get', []);
    $this->assertEquals(5, $afterAdd['count']);

    //get the created payment & check it out
    $getParams['id'] = $result['id'];
    $getIndPayment = $this->callAPISuccess('PledgePayment', 'get', $getParams);
    $this->assertEquals(1, $getIndPayment['count']);
    $this->assertEquals(20, $getIndPayment['values'][$result['id']]['actual_amount']);

    //create a second pledge payment - need a contribution first &can't use the CiviUnitTest case function as invoice is hard-coded
    $contributionParams = [
      'total_amount' => 20,
      'contact_id' => $this->_individualId,
      'financial_type_id' => $this->_financialTypeId,
    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $contributionParams);
    $params['contribution_id'] = $contribution['id'];

    $resultCont2 = $this->callAPISuccess('PledgePayment', 'create', $params);
    //make sure original is untouched & has not been updated
    $this->assertGreaterThan($result['id'], $resultCont2['id']);
    $getIndPaymentAgain = $this->callAPISuccess('PledgePayment', 'get', $getParams);
    $this->assertEquals(1, $getIndPaymentAgain['count']);
    $this->assertEquals($this->_contributionID, $getIndPaymentAgain['values'][$result['id']]['contribution_id']);
  }

  /**
   * Test checks behaviour when more payments are created than should be possible.
   */
  public function testCreatePledgePaymentAllCreated(): void {
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
      if ($i < 5) {
        $this->callAPISuccess('PledgePayment', 'create', $params);
      }
      else {
        $this->callAPIFailure('PledgePayment', 'create', $params, "There are no unmatched payment on this pledge. Pass in the pledge_payment id to specify one or 'option.create_new' to create one");
      }
      $i++;
    }
    // check that only 5 exist & we got an error setting the 6th
    $result = $this->callAPISuccess('PledgePayment', 'Get', [
      'pledge_id' => $this->_pledgeID,
    ]);
    // the last one above should result in an error
    $this->assertEquals(5, $result['count']);

    $params['option.create_new'] = 1;
    $params['scheduled_amount'] = 20;
    $params['scheduled_date'] = '20131212';
    $this->callAPISuccess('PledgePayment', 'create', $params);
    $result = $this->callAPISuccess('PledgePayment', 'Get', [
      'pledge_id' => $this->_pledgeID,
    ]);

    $this->assertEquals(6, $result['count']);
  }

  /**
   * Test that creating a payment adds the contribution ID where only one pledge payment is in schedule.
   */
  public function testCreatePledgePaymentWhereOnlyOnePayment(): void {
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
    $this->callAPISuccess('PledgePayment', 'create', $params);

    //check existing updated not new one created - 'create' means add contribution_id in this context
    $afterAdd = $this->callAPISuccess('PledgePayment', 'get', [
      'contribution_id' => $contributionID,
    ]);
    $this->assertEquals(1, $afterAdd['count']);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testUpdatePledgePayment(): void {
    $params = [
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'status_id' => 2,
      'actual_amount' => 20,
    ];
    $result = $this->callAPISuccess('PledgePayment', 'create', $params);
    $updateParams = [
      'id' => $result['id'],
      'status_id' => 1,
    ];

    $result = $this->callAPIAndDocument('PledgePayment', 'update', $updateParams, __FUNCTION__, __FILE__);
    $this->getAndCheck(array_merge($params, $updateParams), $result['id'], $this->_entity);
  }

  public function testDeletePledgePayment(): void {
    $params = [
      'contact_id' => $this->_individualId,
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'status_id' => 1,
      'sequential' => 1,
      'actual_amount' => 20,
    ];
    $pledgePayment = $this->callAPISuccess('PledgePayment', 'create', $params);

    $deleteParams = [
      'id' => $pledgePayment['id'],
    ];
    $this->callAPIAndDocument('PledgePayment', 'delete', $deleteParams, __FUNCTION__, __FILE__);
  }

  public function testGetFields(): void {
    $result = $this->callAPISuccess('PledgePayment', 'GetFields', []);
    $this->assertIsArray($result);
  }

}
