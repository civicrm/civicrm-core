<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

/**
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_ContributionSoft
 * @group headless
 */
class api_v3_ContributionSoftTest extends CiviUnitTestCase {

  /**
   * The hard credit contact.
   *
   * @var int
   */
  protected $_individualId;

  /**
   * The first soft credit contact.
   *
   * @var int
   */
  protected $_softIndividual1Id;

  /**
   * The second soft credit contact.
   *
   * @var int
   */
  protected $_softIndividual2Id;
  protected $_contributionId;
  protected $_financialTypeId = 1;
  protected $_apiversion = 3;
  protected $_entity = 'Contribution';
  public $debug = 0;
  protected $_params;

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->_individualId = $this->individualCreate();
    $this->_softIndividual1Id = $this->individualCreate();
    $this->_softIndividual2Id = $this->individualCreate();
    $this->_contributionId = $this->contributionCreate(array('contact_id' => $this->_individualId));

    $this->processorCreate();
    $this->_params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'source' => 'SSF',
      'contribution_status_id' => 1,
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
  }

  /**
   * Test get methods.
   *
   * @todo - this might be better broken down into more smaller tests
   */
  public function testGetContributionSoft() {
    //We don't test for PCP fields because there's no PCP API, so we can't create campaigns.
    $p = array(
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
      'soft_credit_type_id' => 4,
    );

    $this->_softcontribution = $this->callAPISuccess('contribution_soft', 'create', $p);
    $params = array(
      'id' => $this->_softcontribution['id'],
    );
    $softcontribution = $this->callAPIAndDocument('contribution_soft', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $softcontribution['count']);
    $this->assertEquals($softcontribution['values'][$this->_softcontribution['id']]['contribution_id'], $this->_contributionId);
    $this->assertEquals($softcontribution['values'][$this->_softcontribution['id']]['contact_id'], $this->_softIndividual1Id);
    $this->assertEquals($softcontribution['values'][$this->_softcontribution['id']]['amount'], '10.00');
    $this->assertEquals($softcontribution['values'][$this->_softcontribution['id']]['currency'], 'USD');
    $this->assertEquals($softcontribution['values'][$this->_softcontribution['id']]['soft_credit_type_id'], 4);

    //create a second soft contribution on the same hard contribution - we are testing that 'id' gets the right soft contribution id (not the contribution id)
    $p['contact_id'] = $this->_softIndividual2Id;
    $this->_softcontribution2 = $this->callAPISuccess('contribution_soft', 'create', $p);

    // now we have 2 - test getcount
    $softcontribution = $this->callAPISuccess('contribution_soft', 'getcount', array());
    $this->assertEquals(2, $softcontribution);

    //check first contribution
    $result = $this->callAPISuccess('contribution_soft', 'get', array(
      'id' => $this->_softcontribution['id'],
    ));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals($this->_softcontribution['id'], $result['id']);
    $this->assertEquals($this->_softcontribution['id'], $result['id'], print_r($softcontribution, TRUE));

    //test id only format - second soft credit
    $resultID2 = $this->callAPISuccess('contribution_soft', 'get', array(
      'id' => $this->_softcontribution2['id'],
      'format.only_id' => 1,
    ));
    $this->assertEquals($this->_softcontribution2['id'], $resultID2);

    //test get by contact id works
    $result = $this->callAPISuccess('contribution_soft', 'get', array(
      'contact_id' => $this->_softIndividual2Id,
    ));
    $this->assertEquals(1, $result['count']);

    $this->callAPISuccess('contribution_soft', 'Delete', array(
      'id' => $this->_softcontribution['id'],
    ));
    // check one soft credit remains
    $expectedCount = 1;
    $this->callAPISuccess('contribution_soft', 'getcount', array(), $expectedCount);

    //check id is same as 2
    $this->assertEquals($this->_softcontribution2['id'], $this->callAPISuccess('contribution_soft', 'getvalue', array('return' => 'id')));

    $this->callAPISuccess('ContributionSoft', 'Delete', array(
      'id' => $this->_softcontribution2['id'],
    ));
  }

  /**
   * civicrm_contribution_soft.
   */
  public function testCreateEmptyParamsContributionSoft() {
    $softcontribution = $this->callAPIFailure('contribution_soft', 'create', array(),
      'Mandatory key(s) missing from params array: contribution_id, amount, contact_id'
    );
  }

  public function testCreateParamsWithoutRequiredKeysContributionSoft() {
    $softcontribution = $this->callAPIFailure('contribution_soft', 'create', array(),
      'Mandatory key(s) missing from params array: contribution_id, amount, contact_id'
    );
  }

  public function testCreateContributionSoftInvalidContact() {
    $params = array(
      'contact_id' => 999,
      'contribution_id' => $this->_contributionId,
      'amount' => 10.00,
      'currency' => 'USD',
    );

    $softcontribution = $this->callAPIFailure('contribution_soft', 'create', $params,
      'contact_id is not valid : 999'
    );
  }

  public function testCreateContributionSoftInvalidContributionId() {
    $params = array(
      'contribution_id' => 999999,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
    );

    $softcontribution = $this->callAPIFailure('contribution_soft', 'create', $params,
      'contribution_id is not valid : 999999'
    );
  }

  /**
   * Function tests that additional financial records are created when fee amount is recorded.
   */
  public function testCreateContributionSoft() {
    $params = array(
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
      'soft_credit_type_id' => 5,
    );

    $softcontribution = $this->callAPIAndDocument('contribution_soft', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($softcontribution['values'][$softcontribution['id']]['contribution_id'], $this->_contributionId);
    $this->assertEquals($softcontribution['values'][$softcontribution['id']]['contact_id'], $this->_softIndividual1Id);
    $this->assertEquals($softcontribution['values'][$softcontribution['id']]['amount'], '10.00');
    $this->assertEquals($softcontribution['values'][$softcontribution['id']]['currency'], 'USD');
    $this->assertEquals($softcontribution['values'][$softcontribution['id']]['soft_credit_type_id'], 5);
  }

  /**
   * To Update Soft Contribution.
   *
   */
  public function testCreateUpdateContributionSoft() {
    //create a soft credit
    $params = array(
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
      'soft_credit_type_id' => 6,
    );

    $softcontribution = $this->callAPISuccess('contribution_soft', 'create', $params);
    $softcontributionID = $softcontribution['id'];

    $old_params = array(
      'contribution_soft_id' => $softcontributionID,
    );
    $original = $this->callAPISuccess('contribution_soft', 'get', $old_params);
    //Make sure it came back
    $this->assertEquals($original['id'], $softcontributionID);
    //set up list of old params, verify
    $old_contribution_id = $original['values'][$softcontributionID]['contribution_id'];
    $old_contact_id = $original['values'][$softcontributionID]['contact_id'];
    $old_amount = $original['values'][$softcontributionID]['amount'];
    $old_currency = $original['values'][$softcontributionID]['currency'];
    $old_soft_credit_type_id = $original['values'][$softcontributionID]['soft_credit_type_id'];

    //check against original values
    $this->assertEquals($old_contribution_id, $this->_contributionId);
    $this->assertEquals($old_contact_id, $this->_softIndividual1Id);
    $this->assertEquals($old_amount, 10.00);
    $this->assertEquals($old_currency, 'USD');
    $this->assertEquals($old_soft_credit_type_id, 6);
    $params = array(
      'id' => $softcontributionID,
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 7.00,
      'currency' => 'CAD',
      'soft_credit_type_id' => 7,
    );

    $softcontribution = $this->callAPISuccess('contribution_soft', 'create', $params);

    $new_params = array(
      'id' => $softcontribution['id'],
    );
    $softcontribution = $this->callAPISuccess('contribution_soft', 'get', $new_params);
    //check against original values
    $this->assertEquals($softcontribution['values'][$softcontributionID]['contribution_id'], $this->_contributionId);
    $this->assertEquals($softcontribution['values'][$softcontributionID]['contact_id'], $this->_softIndividual1Id);
    $this->assertEquals($softcontribution['values'][$softcontributionID]['amount'], 7.00);
    $this->assertEquals($softcontribution['values'][$softcontributionID]['currency'], 'CAD');
    $this->assertEquals($softcontribution['values'][$softcontributionID]['soft_credit_type_id'], 7);

    $params = array(
      'id' => $softcontributionID,
    );
    $this->callAPISuccess('contribution_soft', 'delete', $params);
  }

  /**
   * civicrm_contribution_soft_delete methods.
   *
   */
  public function testDeleteEmptyParamsContributionSoft() {
    $params = array();
    $softcontribution = $this->callAPIFailure('contribution_soft', 'delete', $params);
  }

  public function testDeleteWrongParamContributionSoft() {
    $params = array(
      'contribution_source' => 'SSF',
    );
    $this->callAPIFailure('contribution_soft', 'delete', $params);
  }

  public function testDeleteContributionSoft() {
    //create a soft credit
    $params = array(
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
    );

    $softcontribution = $this->callAPISuccess('contribution_soft', 'create', $params);
    $softcontributionID = $softcontribution['id'];
    $params = array(
      'id' => $softcontributionID,
    );
    $this->callAPIAndDocument('contribution_soft', 'delete', $params, __FUNCTION__, __FILE__);
  }

  ///////////////// civicrm_contribution_search methods

  /**
   * Test civicrm_contribution_search with empty params.
   * All available contributions expected.
   */
  public function testSearchEmptyParams() {
    $p = array(
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
    );
    $softcontribution = $this->callAPISuccess('contribution_soft', 'create', $p);

    $result = $this->callAPISuccess('contribution_soft', 'get', array());
    // We're taking the first element.
    $res = $result['values'][$softcontribution['id']];

    $this->assertEquals($p['contribution_id'], $res['contribution_id']);
    $this->assertEquals($p['contact_id'], $res['contact_id']);
    $this->assertEquals($p['amount'], $res['amount']);
    $this->assertEquals($p['currency'], $res['currency']);
  }

  /**
   * Test civicrm_contribution_soft_search. Success expected.
   */
  public function testSearch() {
    $p1 = array(
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
    );
    $softcontribution1 = $this->callAPISuccess('contribution_soft', 'create', $p1);

    $p2 = array(
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual2Id,
      'amount' => 25.00,
      'currency' => 'CAD',
    );
    $softcontribution2 = $this->callAPISuccess('contribution_soft', 'create', $p2);

    $params = array(
      'id' => $softcontribution2['id'],
    );
    $result = $this->callAPISuccess('contribution_soft', 'get', $params);
    $res = $result['values'][$softcontribution2['id']];

    $this->assertEquals($p2['contribution_id'], $res['contribution_id']);
    $this->assertEquals($p2['contact_id'], $res['contact_id']);
    $this->assertEquals($p2['amount'], $res['amount']);
    $this->assertEquals($p2['currency'], $res['currency']);
  }

}
