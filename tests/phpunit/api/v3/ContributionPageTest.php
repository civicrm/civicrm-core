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
  public $_eNoticeCompliant = TRUE;
  public $DBResetRequired = TRUE;
  public function setUp() {
    parent::setUp();
    $this->contactIds[] = $this->individualCreate();
    $this->params = array(
      'version' => $this->_apiversion,
      'title' => "Test Contribution Page",
      'financial_type_id' => 1,
      'currency' => 'NZD',
      'goal_amount' => $this->testAmount,
    );
  }

  function tearDown() {
    foreach ($this->contactIds as $id) {
      civicrm_api('contact', 'delete', array('version' => $this->_apiversion, 'id' => $id));
    }
    civicrm_api('contribution_page', 'delete', array('version' => $this->_apiversion, 'id' => $this->id));
  }

  public function testCreateContributionPage() {
    $result = civicrm_api($this->_entity, 'create', $this->params);
    $this->id = $result['id'];
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
  }

  public function testGetBasicContributionPage() {
    $createResult = civicrm_api($this->_entity, 'create', $this->params);
    $this->id = $createResult['id'];
    $this->assertAPISuccess($createResult);
    $getParams = array(
      'version' => $this->_apiversion,
      'currency' => 'NZD',
      'financial_type_id' => 1,
    );
    $getResult = civicrm_api($this->_entity, 'get', $getParams);
    $this->documentMe($getParams, $getResult, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($getResult, 'In line ' . __LINE__);
    $this->assertEquals(1, $getResult['count'], 'In line ' . __LINE__);
  }

  public function testGetContributionPageByAmount() {
    $createResult = civicrm_api($this->_entity, 'create', $this->params);
    $this->id = $createResult['id'];
    $this->assertAPISuccess($createResult);
    $getParams = array(
      'version' => $this->_apiversion,
      'amount' => ''. $this->testAmount, // 3456
      'currency' => 'NZD',
      'financial_type_id' => 1,
    );
    $getResult = civicrm_api($this->_entity, 'get', $getParams);
    $this->documentMe($getParams, $getResult, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($getResult, 'In line ' . __LINE__);
    $this->assertEquals(1, $getResult['count'], 'In line ' . __LINE__);
  }

  public function testDeleteContributionPage() {
    $createResult = civicrm_api($this->_entity, 'create', $this->params);
    $deleteParams = array('version' => $this->_apiversion, 'id' => $createResult['id']);
    $deleteResult = civicrm_api($this->_entity, 'delete', $deleteParams);
    $this->documentMe($deleteParams, $deleteResult, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($deleteResult, 'In line ' . __LINE__);
    $checkDeleted = civicrm_api($this->_entity, 'get', array(
      'version' => $this->_apiversion,
      ));
    $this->assertEquals(0, $checkDeleted['count'], 'In line ' . __LINE__);
  }

  public function testGetFieldsContributionPage() {
    $result = civicrm_api($this->_entity, 'getfields', array('version' => $this->_apiversion, 'action' => 'create'));
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(12, $result['values']['start_date']['type']);
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

