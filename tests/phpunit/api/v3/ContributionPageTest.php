<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 *  Test APIv3 civicrm_contribute_recur* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Contribution
 */

class api_v3_ContributionPageTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $testAmount = 34567;
  protected $params;
  protected $id = 0;
  protected $contactIds = array();
  protected $_entity = 'contribution_page';
  protected $contribution_result = null;

  public $DBResetRequired = TRUE;
  public function setUp() {
    parent::setUp();
    $this->contactIds[] = $this->individualCreate();
    $this->params = array(
      'title' => "Test Contribution Page",
      'financial_type_id' => 1,
      'currency' => 'NZD',
      'goal_amount' => $this->testAmount,
      'is_pay_later' => 1,
    );
  }

  function tearDown() {
    foreach ($this->contactIds as $id) {
      $this->callAPISuccess('contact', 'delete', array('id' => $id));
    }if(!empty($this->id)){
       $this->callAPISuccess('contribution_page', 'delete', array('id' => $this->id));
    }
  }

  public function testCreateContributionPage() {
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
  }

  public function testGetBasicContributionPage() {
    $createResult = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $this->id = $createResult['id'];
    $getParams = array(
      'currency' => 'NZD',
      'financial_type_id' => 1,
    );
    $getResult = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $getResult['count']);
  }

  public function testGetContributionPageByAmount() {
    $createResult = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $this->id = $createResult['id'];
    $getParams = array(
      'amount' => ''. $this->testAmount, // 3456
      'currency' => 'NZD',
      'financial_type_id' => 1,
    );
    $getResult = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $getResult['count']);
  }

  public function testDeleteContributionPage() {
    $createResult = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $deleteParams = array('id' => $createResult['id']);
    $deleteResult = $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', array());
    $this->assertEquals(0, $checkDeleted['count']);
  }

  public function testGetFieldsContributionPage() {
    $result = $this->callAPISuccess($this->_entity, 'getfields', array('action' => 'create'));
    $this->assertEquals(12, $result['values']['start_date']['type']);
  }


  public function testSubmit() {
    $contributionPageResult = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $priceSetParams = array(
      'is_quick_config' => 1,
      'extends' => 'CiviContribute',
      'financial_type_id' => 'Donation',
      'title' => 'my Page'
    );

    $priceSet = $this->callAPISuccess('price_set', 'create', $priceSetParams);
    CRM_Price_BAO_PriceSet::addTo('civicrm_contribution_page', $contributionPageResult['id'], $priceSet['id']);
    $priceField = $this->callAPISuccess('price_field', 'create', array(
      'price_set_id' => $priceSet['id'],
      'label' => 'Goat Breed',
      'html_type' => 'Radio',
    ));
    $this->callAPISuccess('price_field_value', 'create', array(
      'price_set_id' => $priceSet['id'],
      'price_field_id' => $priceField['id'],
      'label' => 'Long Haired Goat',
      'amount' => 20,
      )
    );
    $priceFieldValue2 = $this->callAPISuccess('price_field_value', 'create', array(
      'price_set_id' => $priceSet['id'],
      'price_field_id' => $priceField['id'],
      'label' => 'Shoe-eating Goat',
      'amount' => 10,
      )
    );
    $submitParams = array(
      'price_' . $priceField['id'] => $priceFieldValue2['id'],
      'id' => (int) $contributionPageResult['id'],
      'amount' => 10
    );

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $this->callAPISuccess('contribution', 'getsingle', array('contribution_page_id' => $contributionPageResult['id']));
  }

  public static function setUpBeforeClass() {
      // put stuff here that should happen before all tests in this unit
  }

  public static function tearDownAfterClass(){
    $tablesToTruncate = array(
      'civicrm_contact',
      'civicrm_financial_type',
      'civicrm_contribution',
      'civicrm_contribution_page',
    );
    $unitTest = new CiviUnitTestCase();
    $unitTest->quickCleanup($tablesToTruncate);
  }
}

