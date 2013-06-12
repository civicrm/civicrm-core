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
//FIXME:This existed in ContributionTest,  don't think it's necessary
//require_once 'CiviTest/CiviMailUtils.php';


/**
 *  Test APIv3 civicrm_contribute_* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_ContributionSoft
 */

class api_v3_ContributionSoftTest extends CiviUnitTestCase {

  /**
   * Assume empty database with just civicrm_data
   */
  protected $_individualId; //the hard credit contact
  protected $_softIndividual1Id; //the first soft credit contact
  protected $_softIndividual2Id; //the second soft credit contact
  protected $_contributionId;
  protected $_contributionTypeId = 1;
  protected $_apiversion;
  protected $_entity = 'Contribution';
  public $debug = 0;
  protected $_params;
  public $_eNoticeCompliant = TRUE;

  function setUp() {
    parent::setUp();

    $this->_apiversion = 3;
    $this->_individualId = $this->individualCreate();
    $this->_softIndividual1Id = $this->individualCreate();
    $this->_softIndividual2Id = $this->individualCreate();
    $this->_contributionId = $this->contributionCreate($this->_individualId);

    $paymentProcessor = $this->processorCreate();
    $this->_params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id'   => $this->_contributionTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
    );
    $this->_processorParams = array(
      'domain_id' => 1,
      'name' => 'Dummy',
      'payment_processor_type_id' => 10,
      'financial_account_id' => 12,
      'is_active' => 1,
      'user_name' => '',
      'url_site' => 'http://dummy.com',
      'url_recur' => 'http://dummy.com',
      'billing_mode' => 1,
    );
//    $this->_pageParams = array(
//      'version' => 3,
//      'title' => 'Test Contribution Page',
//      'financial_type_id' => 1,
//      'currency' => 'USD',
//      'financial_account_id' => 1,
//      'payment_processor' => $paymentProcessor->id,
//      'is_active' => 1,
//      'is_allow_other_amount' => 1,
//      'min_amount' => 10,
//      'max_amount' => 1000,
//     );
  }

  function tearDown() {

    $this->contributionTypeDelete();
    $this->quickCleanup(array(
      'civicrm_contribution',
      'civicrm_event',
      'civicrm_contribution_page',
      'civicrm_participant',
      'civicrm_participant_payment',
      'civicrm_line_item',
      'civicrm_financial_trxn',
      'civicrm_financial_item',
      'civicrm_entity_financial_trxn',
      'civicrm_contact',
      'civicrm_contribution_soft'
    ));
  }

  function testGetEmptyParamsContributionSoft() {
    $params = array();
    $contribution = civicrm_api('contribution_soft', 'get', $params);
    $this->assertEquals($contribution['is_error'], 1);
    $this->assertEquals($contribution['error_message'], 'Mandatory key(s) missing from params array: version');
  }

  function testGetParamsNotArrayContributionSoft() {
    $params = 'contact_id= 1';
    $contribution = civicrm_api('contribution', 'get', $params);
    $this->assertEquals($contribution['is_error'], 1);
    $this->assertEquals($contribution['error_message'], 'Input variable `params` is not an array');
  }

  function testGetContributionSoft() {
    //We don't test for PCP fields because there's no PCP API, so we can't create campaigns.
    $p = array(
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
      'version' => $this->_apiversion,
    );

    $this->_softcontribution = civicrm_api('contribution_soft', 'create', $p);
    $this->assertEquals($this->_softcontribution['is_error'], 0, 'In line ' . __LINE__);
    $params = array(
      'id' => $this->_softcontribution['id'],
      'version' => $this->_apiversion,
    );
    $softcontribution = civicrm_api('contribution_soft', 'get', $params);
    $this->assertAPISuccess($softcontribution, 'In line ' . __LINE__);
    $this->assertEquals(1,$softcontribution['count']);

    $this->documentMe($params, $softcontribution, __FUNCTION__, __FILE__);
    $this->assertEquals($softcontribution['values'][$this->_softcontribution['id']]['contribution_id'], $this->_contributionId, 'In line ' . __LINE__);
    $this->assertEquals($softcontribution['values'][$this->_softcontribution['id']]['contact_id'], $this->_softIndividual1Id, 'In line ' . __LINE__);
    $this->assertEquals($softcontribution['values'][$this->_softcontribution['id']]['amount'], '10.00', 'In line ' . __LINE__);
    $this->assertEquals($softcontribution['values'][$this->_softcontribution['id']]['currency'], 'USD', 'In line ' . __LINE__);

    //create a second soft contribution on the same hard contribution - we are testing that 'id' gets the right soft contribution id (not the contribution id)
    $p['contact_id'] = $this->_softIndividual2Id;
    $this->_softcontribution2 = civicrm_api('contribution_soft', 'create', $p);
    $this->assertAPISuccess($this->_softcontribution2, 'In line ' . __LINE__);

    $params = array(
      'version' => $this->_apiversion,
    );
    // now we have 2 - test getcount
    $softcontribution = civicrm_api('contribution_soft', 'getcount', array(
      'version' => $this->_apiversion,
    ));

    $this->assertEquals(2, $softcontribution);
   //test id only format
    $softcontribution = civicrm_api('contribution_soft', 'get', array
      ('version' => $this->_apiversion,
        'id' => $this->_softcontribution['id'],
        'format.only_id' => 1,
      )
    );
    $this->assertEquals($this->_softcontribution['id'], $softcontribution, print_r($softcontribution,true) . " in line " . __LINE__);
    //test id only format - second soft credit
    $softcontribution = civicrm_api('contribution_soft', 'get', array
      ('version' => $this->_apiversion,
        'id' => $this->_softcontribution2['id'],
        'format.only_id' => 1,
      )
    );
    $this->assertEquals($this->_softcontribution2['id'], $softcontribution);
    $softcontribution = civicrm_api('contribution_soft', 'get', array(
      'version' => $this->_apiversion,
        'id' => $this->_softcontribution['id'],
      ));
    //test id as field
    $this->assertAPISuccess($softcontribution, 'In line ' . __LINE__);
    $this->assertEquals(1, $softcontribution['count'], 'In line ' . __LINE__);
    $this->assertEquals($this->_softcontribution['id'], $softcontribution['id'] )  ;
    //test get by contact id works
    $softcontribution = civicrm_api('contribution_soft', 'get', array('version' => $this->_apiversion, 'contact_id' => $this->_softIndividual2Id));
    $this->assertAPISuccess($softcontribution, 'In line ' . __LINE__ . "get with contact_id" . print_r(array('version' => $this->_apiversion, 'contact_id' => $this->_softIndividual2Id), TRUE));

    $this->assertEquals(1, $softcontribution['count'], 'In line ' . __LINE__);
    civicrm_api('contribution_soft', 'Delete', array(
      'id' => $this->_softcontribution['id'],
        'version' => $this->_apiversion,
      ));
    civicrm_api('Contribution', 'Delete', array(
      'id' => $this->_softcontribution2['id'],
        'version' => $this->_apiversion,
      ));
  }


  ///////////////// civicrm_contribution_soft
  function testCreateEmptyParamsContributionSoft() {


    $params = array('version' => $this->_apiversion);
    $softcontribution = civicrm_api('contribution_soft', 'create', $params);
    $this->assertEquals($softcontribution['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($softcontribution['error_message'], 'Mandatory key(s) missing from params array: contribution_id, amount, contact_id', 'In line ' . __LINE__);
  }

  function testCreateParamsNotArrayContributionSoft() {

    $params = 'contact_id= 1';
    $softcontribution = civicrm_api('contribution_soft', 'create', $params);
    $this->assertEquals($softcontribution['is_error'], 1);
    $this->assertEquals($softcontribution['error_message'], 'Input variable `params` is not an array');
  }

  function testCreateParamsWithoutRequiredKeysContributionSoft() {
    $params = array('version' => 3);
    $softcontribution = civicrm_api('contribution_soft', 'create', $params);
    $this->assertEquals($softcontribution['is_error'], 1);
    $this->assertEquals($softcontribution['error_message'], 'Mandatory key(s) missing from params array: contribution_id, amount, contact_id');
  }

  function testCreateContributionSoftInvalidContact() {

    $params = array(
      'contact_id' => 999,
      'contribution_id' => $this->_contributionId,
      'amount' => 10.00,
      'currency' => 'USD',
      'version' => $this->_apiversion,
    );

    $softcontribution = civicrm_api('contribution_soft', 'create', $params);
    $this->assertEquals($softcontribution['error_message'], 'contact_id is not valid : 999', 'In line ' . __LINE__);
  }

  function testCreateContributionSoftInvalidContributionId() {

    $params = array(
      'contribution_id' => 999999,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
      'version' => $this->_apiversion,
    );

    $softcontribution = civicrm_api('contribution_soft', 'create', $params);
    $this->assertEquals($softcontribution['error_message'], 'contribution_id is not valid : 999999', 'In line ' . __LINE__);
  }

  /*
   * Function tests that additional financial records are created when fee amount is recorded
   */
  function testCreateContributionSoft() {
    $params = array(
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
      'version' => $this->_apiversion,
    );

    $softcontribution = civicrm_api('contribution_soft', 'create', $params);
    $this->documentMe($params, $softcontribution, __FUNCTION__, __FILE__);
    $this->assertEquals($softcontribution['values'][$softcontribution['id']]['contribution_id'], $this->_contributionId, 'In line ' . __LINE__);
    $this->assertEquals($softcontribution['values'][$softcontribution['id']]['contact_id'], $this->_softIndividual1Id, 'In line ' . __LINE__);
    $this->assertEquals($softcontribution['values'][$softcontribution['id']]['amount'], '10.00', 'In line ' . __LINE__);
    $this->assertEquals($softcontribution['values'][$softcontribution['id']]['currency'], 'USD', 'In line ' . __LINE__);
  }

  /**
   *  Test  using example code
   */
//  function testContributionSoftCreateExample() {
//    //make sure at least one page exists since there is a truncate in tear down
//    $page = civicrm_api('contribution_page', 'create', $this->_pageParams);
//    $this->assertAPISuccess($page);
//    //FIXME: Can't written until ContributionSoftDelete is written
//    require_once 'api/v3/examples/ContributionSoftCreate.php';
//    $result         = contribution_soft_create_example();
//    $this->assertAPISuccess($result);
//    $contributionId = $result['id'];
//    $expectedResult = contribution_soft_create_expectedresult();
//    $this->checkArrayEquals($result, $expectedResult);
//    $this->contributionDelete($contributionId);
//  }
//
  //To Update Soft Contribution
  function testCreateUpdateContributionSoft() {
    //create a soft credit
    $params = array(
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
      'version' => $this->_apiversion,
    );

    $softcontribution = civicrm_api('contribution_soft', 'create', $params);
    $softcontributionID = $softcontribution['id'];

    $old_params = array(
      'contribution_soft_id' => $softcontributionID,
      'version' => $this->_apiversion,
    );
    $original = civicrm_api('contribution_soft', 'get', $old_params);
    //Make sure it came back
    $this->assertTrue(empty($original['is_error']), 'In line ' . __LINE__);
    $this->assertEquals($original['id'], $softcontributionID, 'In line ' . __LINE__);
    //set up list of old params, verify
    $old_contribution_id = $original['values'][$softcontributionID]['contribution_id'];
    $old_contact_id = $original['values'][$softcontributionID]['contact_id'];
    $old_amount = $original['values'][$softcontributionID]['amount'];
    $old_currency = $original['values'][$softcontributionID]['currency'];

    //check against original values
    $this->assertEquals($old_contribution_id, $this->_contributionId, 'In line ' . __LINE__);
    $this->assertEquals($old_contact_id, $this->_softIndividual1Id, 'In line ' . __LINE__);
    $this->assertEquals($old_amount, 10.00, 'In line ' . __LINE__);
    $this->assertEquals($old_currency, 'USD', 'In line ' . __LINE__);
    $params = array(
      'id' => $softcontributionID,
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 7.00,
      'currency' => 'CAD',
      'version' => $this->_apiversion,
    );

    $softcontribution = civicrm_api('contribution_soft', 'create', $params);

    $new_params = array(
      'id' => $softcontribution['id'],
      'version' => $this->_apiversion,
    );
    $softcontribution = civicrm_api('contribution_soft', 'get', $new_params);
    //check against original values
    $this->assertEquals($softcontribution['values'][$softcontributionID]['contribution_id'], $this->_contributionId, 'In line ' . __LINE__);
    $this->assertEquals($softcontribution['values'][$softcontributionID]['contact_id'], $this->_softIndividual1Id, 'In line ' . __LINE__);
    $this->assertEquals($softcontribution['values'][$softcontributionID]['amount'], 7.00, 'In line ' . __LINE__);
    $this->assertEquals($softcontribution['values'][$softcontributionID]['currency'], 'CAD', 'In line ' . __LINE__);

    $params = array(
      'id' => $softcontributionID,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('contribution_soft', 'delete', $params);
    $this->assertAPISuccess($result, 'in line' . __LINE__);
  }

  ///////////////// civicrm_contribution_soft_delete methods
  function testDeleteEmptyParamsContributionSoft() {
    $params = array('version' => $this->_apiversion);
    $softcontribution = civicrm_api('contribution_soft', 'delete', $params);
    $this->assertEquals($softcontribution['is_error'], 1);
  }

  function testDeleteParamsNotArrayContributionSoft() {
    $params = 'id= 1';
    $softcontribution = civicrm_api('contribution_soft', 'delete', $params);
    $this->assertEquals($softcontribution['is_error'], 1);
    $this->assertEquals($softcontribution['error_message'], 'Input variable `params` is not an array');
  }

  function testDeleteWrongParamContributionSoft() {
    $params = array(
      'contribution_source' => 'SSF',
      'version' => $this->_apiversion,
    );
    $softcontribution = civicrm_api('contribution_soft', 'delete', $params);
    $this->assertEquals($softcontribution['is_error'], 1);
  }

  function testDeleteContributionSoft() {
    //create a soft credit
    $params = array(
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
      'version' => $this->_apiversion,
    );

    $softcontribution = civicrm_api('contribution_soft', 'create', $params);
    $softcontributionID = $softcontribution['id'];
    $params = array(
      'id' => $softcontributionID,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('contribution_soft', 'delete', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
  }

  ///////////////// civicrm_contribution_search methods

  /**
   *  Test civicrm_contribution_soft_search with wrong params type
   */
  function testSearchWrongParamsType() {
    $params = 'a string';
    $result = civicrm_api('contribution_soft', 'get', $params);

    $this->assertAPIFailure($result, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input variable `params` is not an array', 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_contribution_search with empty params.
   *  All available contributions expected.
   */
  function testSearchEmptyParams() {
    $params = array('version' => $this->_apiversion);

    $p = array(
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
      'version' => $this->_apiversion,
    );
    $softcontribution = civicrm_api('contribution_soft', 'create', $p);

    $result = civicrm_api('contribution_soft', 'get', $params);
    // We're taking the first element.
    $res = $result['values'][$softcontribution['id']];

    $this->assertEquals($p['contribution_id'], $res['contribution_id'], 'In line ' . __LINE__);
    $this->assertEquals($p['contact_id'], $res['contact_id'], 'In line ' . __LINE__);
    $this->assertEquals($p['amount'], $res['amount'], 'In line ' . __LINE__);
    $this->assertEquals($p['currency'], $res['currency'], 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_contribution_soft_search. Success expected.
   */
  function testSearch() {
    $p1 = array(
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
      'version' => $this->_apiversion,
    );
    $softcontribution1 = civicrm_api('contribution_soft', 'create', $p1);

    $p2 = array(
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual2Id,
      'amount' => 25.00,
      'currency' => 'CAD',
      'version' => $this->_apiversion,
    );
    $softcontribution2 = civicrm_api('contribution_soft', 'create', $p2);

    $params = array(
      'id' => $softcontribution2['id'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('contribution_soft', 'get', $params);
    $res = $result['values'][$softcontribution2['id']];

    $this->assertEquals($p2['contribution_id'], $res['contribution_id'], 'In line ' . __LINE__);
    $this->assertEquals($p2['contact_id'], $res['contact_id'], 'In line ' . __LINE__);
    $this->assertEquals($p2['amount'], $res['amount'], 'In line ' . __LINE__);
    $this->assertEquals($p2['currency'], $res['currency'], 'In line ' . __LINE__ );
  }
}

