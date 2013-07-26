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

/**
 * File for the CiviCRM APIv3 job functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Job
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: Job.php 30879 2010-11-22 15:45:55Z shot $
 *
 */
require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_JobTest extends CiviUnitTestCase {
  protected $_apiversion = 3;

  public $_eNoticeCompliant = TRUE;
  public $DBResetRequired = FALSE;
  public $_entity = 'Job';

  function setUp() {
    parent::setUp();
    $this->quickCleanup(array('civicrm_job'));
  }

  function tearDown() {
    $this->quickCleanup(array('civicrm_job'));
    parent::tearDown();
  }

  /**
   * check with no name
   */
  function testCreateWithoutName() {
    $params = array('is_active' => 1);
    $result = $this->callAPIFailure('job', 'create', $params,
      'Mandatory key(s) missing from params array: run_frequency, name, api_entity, api_action'
    );
  }
  /**
   * create job with an valid "run_frequency" value
   */
  function testCreateWithValidFrequency() {
    $params = array(
      'sequential' => 1,
      'name' => 'API_Test_Job',
      'description' => 'A long description written by hand in cursive',
      'run_frequency' => 'Hourly',
      'api_entity' => 'ApiTestEntity',
      'api_action' => 'apitestaction',
      'parameters' => 'Semi-formal explanation of runtime job parameters',
      'is_active' => 1,
    );
    $result = $this->callAPISuccess('job', 'create', $params);
  }

  /**
   * create job with an invalid "run_frequency" value
   */
  function testCreateWithInvalidFrequency() {
    $params = array(
      'sequential' => 1,
      'name' => 'API_Test_Job',
      'description' => 'A long description written by hand in cursive',
      'run_frequency' => 'Fortnightly',
      'api_entity' => 'ApiTestEntity',
      'api_action' => 'apitestaction',
      'parameters' => 'Semi-formal explanation of runtime job parameters',
      'is_active' => 1,
    );
    $result = $this->callAPIFailure('job', 'create', $params);
  }

  /**
   * create job
   */
  function testCreate() {
    $params = array(
      'sequential' => 1,
      'name' => 'API_Test_Job',
      'description' => 'A long description written by hand in cursive',
      'run_frequency' => 'Daily',
      'api_entity' => 'ApiTestEntity',
      'api_action' => 'apitestaction',
      'parameters' => 'Semi-formal explanation of runtime job parameters',
      'is_active' => 1,
    );
    $result = $this->callAPIAndDocument('job', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['values'][0]['id'], 'in line ' . __LINE__);

    // mutate $params to match expected return value
    unset($params['sequential']);
    //assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Core_DAO_Job', $result['id'], $params);
  }

  /**
   * check with empty array
   */
  function testDeleteEmpty() {
    $params = array();
    $result = $this->callAPIFailure('job', 'delete', $params);
  }

  /**
   * check with No array
   */
  function testDeleteParamsNotArray() {
    $result = $this->callAPIFailure('job', 'delete', 'string');
  }

  /**
   * check if required fields are not passed
   */
  function testDeleteWithoutRequired() {
    $params = array(
      'name' => 'API_Test_PP',
      'title' => 'API Test Payment Processor',
      'class_name' => 'CRM_Core_Payment_APITest',
    );

    $result = $this->callAPIFailure('job', 'delete', $params);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: id');
  }

  /**
   * check with incorrect required fields
   * note to copy & pasters - this test is of marginal value
   * and effort would be better put into making the one in syntax
   * conformance work for all entities
   */
  function testDeleteWithIncorrectData() {
    $params = array(
      'id' => 'abcd');
    $result = $this->callAPIFailure('job', 'delete', $params);
  }

  /**
   * check job delete
   */
  function testDelete() {
    $createParams = array(
      'sequential' => 1,
      'name' => 'API_Test_Job',
      'description' => 'A long description written by hand in cursive',
      'run_frequency' => 'Daily',
      'api_entity' => 'ApiTestEntity',
      'api_action' => 'apitestaction',
      'parameters' => 'Semi-formal explanation of runtime job parameters',
      'is_active' => 1,
    );
    $createResult = $this->callAPISuccess('job', 'create', $createParams);
    $this->assertAPISuccess($createResult);

    $params = array(
      'id' => $createResult['id'],
    );
    $result = $this->callAPIAndDocument('job', 'delete', $params, __FUNCTION__, __FILE__);
  }

  /**

  public function testCallUpdateGreetingMissingParams() {
    $result = $this->callAPISuccess($this->_entity, 'update_greeting', array('gt' => 1));
    $this->assertEquals('Mandatory key(s) missing from params array: ct', $result['error_message']);
  }

  public function testCallUpdateGreetingIncorrectParams() {
    $result = $this->callAPISuccess($this->_entity, 'update_greeting', array('gt' => 1, 'ct' => 'djkfhdskjfhds'));
    $this->assertEquals('ct `djkfhdskjfhds` is not valid.', $result['error_message']);
  }
/*
 * Note that this test is about tesing the metadata / calling of the function & doesn't test the success of the called function
 */
  public function testCallUpdateGreetingSuccess() {
    $result = $this->callAPISuccess($this->_entity, 'update_greeting', array('gt' => 'postal_greeting', 'ct' => 'Individual'));
    $this->assertAPISuccess($result);
   }

  public function testCallUpdateGreetingCommaSeparatedParamsSuccess() {
    $gt = 'postal_greeting,email_greeting,addressee';
    $ct = 'Individual,Household';
    $result = $this->callAPISuccess($this->_entity, 'update_greeting', array('gt' => $gt, 'ct' => $ct));
    $this->assertAPISuccess($result);
  }
}

