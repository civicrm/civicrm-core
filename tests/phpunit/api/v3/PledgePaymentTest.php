<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

/**
 * Test class for Pledge API - civicrm_pledge_*
 *
 *  @package CiviCRM_APIv3
 */

class api_v3_PledgePaymentTest extends CiviUnitTestCase {

  /**
   * Assume empty database with just civicrm_data
   */
  protected $_individualId;
  protected $_pledgeID;
  protected $_apiversion;
  protected $_contributionID;
  protected $_contributionTypeId;
  protected $_entity = 'PledgePayment';

  public $DBResetRequired = TRUE; function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
    $tablesToTruncate = array(
      'civicrm_contribution',
      'civicrm_contact', 'civicrm_pledge',
    );

    $this->quickCleanup($tablesToTruncate);
    $this->_contributionTypeId = $this->contributionTypeCreate();
    $this->_individualId = $this->individualCreate(NULL);
    $this->_pledgeID = $this->pledgeCreate($this->_individualId);
    $this->_contributionID = $this->contributionCreate($this->_individualId, $this->_contributionTypeId);
  }

  function tearDown() {
    $tablesToTruncate = array(
      'civicrm_contribution',
      'civicrm_contact',
      'civicrm_pledge',
      'civicrm_pledge_payment',
      'civicrm_line_item',
    );

    $this->quickCleanup($tablesToTruncate);
    $this->contributionTypeDelete();
  }

  function testGetPledgePayment() {
    $params = array(
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('pledge_payment', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], " in line " . __LINE__);
    $this->assertEquals(5, $result['count'], " in line " . __LINE__);
  }

  /*
     * Test that passing in a single variable works
     */
  function testGetSinglePledgePayment() {


    $createparams = array(
      'contact_id' => $this->_individualId,
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'version' => $this->_apiversion,
      'status_id' => 1,
    );
    $createResult = civicrm_api('pledge_payment', 'create', $createparams);
    $this->assertEquals(0, $createResult['is_error'], " in line " . __LINE__);
    $params = array(
      'version' => $this->_apiversion,
      'contribution_id' => $this->_contributionID,
    );
    $result = civicrm_api('pledge_payment', 'get', $params);
    $this->assertEquals(0, $result['is_error'], " in line " . __LINE__);
    $this->assertEquals(1, $result['count'], " in line " . __LINE__);
  }

  /*
     * Test that passing in a single variable works:: status_id
     */
  function testGetSinglePledgePaymentByStatusID() {


    $createparams = array(
      'contact_id' => $this->_individualId,
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'version' => $this->_apiversion,
      'status_id' => 1,
    );
    $createResult = civicrm_api('pledge_payment', 'create', $createparams);
    $this->assertEquals(0, $createResult['is_error'], " in line " . __LINE__);
    $params = array(
      'version' => $this->_apiversion,
      'status_id' => 1,
    );

    $result = civicrm_api('pledge_payment', 'get', $params);
    $this->assertEquals(0, $result['is_error'], " in line " . __LINE__);
    $this->assertEquals(1, $result['count'], " in line " . __LINE__);
  }

  /*
 * Test that creating a payment will add the contribution ID
 */
  function testCreatePledgePayment() {
    //check that 5 pledge payments exist at the start
    $getParams = array(
      'version' => $this->_apiversion,
    );
    $beforeAdd = civicrm_api('pledge_payment', 'get', $getParams);
    $this->assertEquals(0, $beforeAdd['is_error'], " in line " . __LINE__);
    $this->assertEquals(5, $beforeAdd['count'], " in line " . __LINE__);

    //test the pledge_payment_create function
    $params = array(
      'contact_id' => $this->_individualId,
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'version' => $this->_apiversion,
      'status_id' => 1,
      'actual_amount' => 20,
    );
    $result = civicrm_api('pledge_payment', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], " in line " . __LINE__);

    //check existing updated not new one created - 'create' means add contribution_id in this context
    $afterAdd = civicrm_api('pledge_payment', 'get', $getParams);
    $this->assertEquals(0, $beforeAdd['is_error'], " in line " . __LINE__);
    $this->assertEquals(5, $afterAdd['count'], " in line " . __LINE__);

    //get the created payment & check it out
    $getParams['id'] = $result['id'];
    $getIndPayment = civicrm_api('pledge_payment', 'get', $getParams);
    $this->assertEquals(1, $getIndPayment['count'], " in line " . __LINE__);
    $this->assertEquals(20, $getIndPayment['values'][$result['id']]['actual_amount'], " in line " . __LINE__);

    //create a second pledge payment - need a contribution first &can't use the CiviUnitTest case function as invoice is hard-coded
    $contributionParams = array(
      'version' => 3,
      'total_amount' => 20,
      'contact_id' => $this->_individualId,
      'financial_type_id' => $this->_contributionTypeId,
    );
    $contribution = civicrm_api('contribution', 'create', $contributionParams);

    $this->assertEquals(0, $contribution['is_error'], " in line " . __LINE__);

    $params['contribution_id'] = $contribution['id'];


    $resultCont2 = civicrm_api('pledge_payment', 'create', $params);
    $this->assertEquals(0, $resultCont2['is_error'], " in line " . __LINE__);
    //make sure original is untouched & has not been updated
    $this->assertGreaterThan($result['id'], $resultCont2['id'], " in line " . __LINE__);
    $getIndPaymentAgain = civicrm_api('pledge_payment', 'get', $getParams);
    $this->assertEquals(1, $getIndPaymentAgain['count'], " in line " . __LINE__);
    $this->assertEquals($this->_contributionID, $getIndPaymentAgain['values'][$result['id']]['contribution_id'], " in line " . __LINE__);
  }

  /*
     * test checks behaviour when more payments are created than should be possible
     */
  function testCreatePledgePaymentAllCreated() {
    $params = array(
      'version' => 3,
      'pledge_id' => $this->_pledgeID,
      'status_id' => 1,
    );
    // create one more pledge than there are spaces for
    $i = 0;
    while ($i <= 5) {
      $contributionParams = array(
        'version' => 3,
        'total_amount' => 20,
        'contact_id' => $this->_individualId,
        'financial_type_id' => $this->_contributionTypeId,
      );
      $contribution = civicrm_api('contribution', 'create', $contributionParams);

      $this->assertEquals(0, $contribution['is_error'], " in line " . __LINE__);

      $params['contribution_id'] = $contribution['id'];
      $resultCont2 = civicrm_api('pledge_payment', 'create', $params);
      $i++;
    }
    // check that only 5 exist & we got an error setting the 6th
    $result = civicrm_api('PledgePayment', 'Get', array(
      'version' => 3,
      'pledge_id' => $this->_pledgeID,
    ));

    $this->assertEquals(5, $result['count']);
    $this->assertEquals(1, $resultCont2['is_error']);
    $this->assertEquals("There are no unmatched payment on this pledge. Pass in the pledge_payment id to specify one or 'option.create_new' to create one", $resultCont2['error_message']);

    $params['option.create_new'] = 1;
    $params['scheduled_amount'] = 20;
    $params['scheduled_date'] = '20131212';
    $resultcreatenew = civicrm_api('pledge_payment', 'create', $params);
    $this->assertAPISuccess($resultcreatenew);
    
    $this->assertEquals(0, $resultcreatenew['is_error'], "in line " . __LINE__);
    $result = civicrm_api('PledgePayment', 'Get', array(
      'version' => 3,
        'pledge_id' => $this->_pledgeID,
      ));

    $this->assertEquals(6, $result['count']);
  }
  /*
 * Test that creating a payment will add the contribution ID where only one pledge payment
 * in schedule
 */
  function testCreatePledgePaymentWhereOnlyOnePayment() {

    $pledgeParams = array(
      'contact_id' => $this->_individualId,
      'pledge_create_date' => date('Ymd'),
      'start_date' => date('Ymd'),
      'scheduled_date' => $this->scheduled_date,
      'pledge_amount' => 100.00,
      'pledge_status_id' => '2',
      'pledge_financial_type_id' => '1',
      'pledge_original_installment_amount' => 20,
      'frequency_interval' => 5,
      'frequency_unit' => 'year',
      'frequency_day' => 15,
      'installments' => 1,
      'sequential' => 1,
      'version' => $this->_apiversion,
    );

    $contributionID = $this->contributionCreate($this->_individualId, $this->_contributionTypeId, 45, 45);
    $pledge = civicrm_api('Pledge', 'Create', $pledgeParams);
    $this->assertEquals(0, $pledge['is_error'], " in line " . __LINE__);

    //test the pledge_payment_create function
    $params = array(
      'contact_id' => $this->_individualId,
      'pledge_id' => $pledge['id'],
      'contribution_id' => $contributionID,
      'version' => $this->_apiversion,
      'status_id' => 1,
      'actual_amount' => 20,
    );
    $result = civicrm_api('pledge_payment', 'create', $params);

    $this->assertEquals(0, $result['is_error'], " in line " . __LINE__);

    //check existing updated not new one created - 'create' means add contribution_id in this context
    $afterAdd = civicrm_api('pledge_payment', 'get', array(
      'version' => 3, 'contribution_id' => $contributionID,
      ));
    $this->assertEquals(1, $afterAdd['count'], " in line " . __LINE__);
  }

  function testUpdatePledgePayment() {
    $params = array(
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'version' => $this->_apiversion,
      'status_id' => 2,
      'actual_amount' => 20,
    );
    $result = civicrm_api('pledge_payment', 'create', $params);
    $updateparams = array(
      'id' => $result['id'],
      'status_id' => 1,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('pledge_payment', 'update', $updateparams);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $this->getAndCheck(array_merge($params,$updateparams), $result['id'], $this->_entity);
  }

  function testDeletePledgePayment() {
    $params = array(
      'contact_id' => $this->_individualId,
      'pledge_id' => $this->_pledgeID,
      'contribution_id' => $this->_contributionID,
      'version' => $this->_apiversion,
      'status_id' => 1,
      'sequential' => 1,
      'actual_amount' => 20,
    );
    $pledgePayment = civicrm_api('pledge_payment', 'create', $params);

    $deleteParams = array(
      'id' => $pledgePayment['id'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('pledge_payment', 'delete', $deleteParams);
    $this->documentMe($deleteParams, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], " in line " . __LINE__);
  }

  function testGetFields() {
    $result = civicrm_api('PledgePayment', 'GetFields', array());
    $this->assertType('array', $result);
  }
}

