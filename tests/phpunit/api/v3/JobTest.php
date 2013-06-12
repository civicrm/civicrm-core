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
  protected $_apiversion;

  public $_eNoticeCompliant = TRUE;
  public $DBResetRequired = FALSE;
  public $_entity = 'Job';
  public $_apiVersion = 3;

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
    $params = array(
      'is_active' => 1,
      'version' => $this->_apiVersion,
    );
    $result = civicrm_api('job', 'create', $params);

    $this->assertAPIFailure($result);
    $this->assertEquals($result['error_message'],
      'Mandatory key(s) missing from params array: run_frequency, name, api_entity, api_action'
    );
  }

  /**
   * create job with an invalid "run_frequency" value
   */
  function testCreateWithInvalidFrequency() {
    $params = array(
      'version' => $this->_apiVersion,
      'sequential' => 1,
      'name' => 'API_Test_Job',
      'description' => 'A long description written by hand in cursive',
      'run_frequency' => 'Fortnightly',
      'api_entity' => 'ApiTestEntity',
      'api_action' => 'apitestaction',
      'parameters' => 'Semi-formal explanation of runtime job parameters',
      'is_active' => 1,
    );
    $result = civicrm_api('job', 'create', $params);
    $this->assertAPIFailure($result);
  }

  /**
   * create job
   */
  function testCreate() {
    $params = array(
      'version' => $this->_apiVersion,
      'sequential' => 1,
      'name' => 'API_Test_Job',
      'description' => 'A long description written by hand in cursive',
      'run_frequency' => 'Daily',
      'api_entity' => 'ApiTestEntity',
      'api_action' => 'apitestaction',
      'parameters' => 'Semi-formal explanation of runtime job parameters',
      'is_active' => 1,
    );
    $result = civicrm_api('job', 'create', $params);
    $this->assertAPISuccess($result);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['values'][0]['id'], 'in line ' . __LINE__);

    // mutate $params to match expected return value
    unset($params['version']);
    unset($params['sequential']);
    //assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Core_DAO_Job', $result['id'], $params);
  }

  /**
   * check with empty array
   */
  function testDeleteEmpty() {
    $params = array();
    $result = civicrm_api('job', 'delete', $params);
    $this->assertAPIFailure($result);
  }

  /**
   * check with No array
   */
  function testDeleteParamsNotArray() {
    $result = civicrm_api('job', 'delete', 'string');
    $this->assertAPIFailure($result);
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

    $result = civicrm_api('job', 'delete', $params);
    $this->assertAPIFailure($result);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: version, id');
  }

  /**
   * check with incorrect required fields
   */
  function testDeleteWithIncorrectData() {
    $params = array(
      'id' => 'abcd',
      'version' => $this->_apiVersion,
    );

    $result = civicrm_api('job', 'delete', $params);

    $this->assertAPIFailure($result);
    $this->assertEquals($result['error_message'], 'Invalid value for job ID');
  }

  /**
   * check job delete
   */
  function testDelete() {
    $createParams = array(
      'version' => $this->_apiVersion,
      'sequential' => 1,
      'name' => 'API_Test_Job',
      'description' => 'A long description written by hand in cursive',
      'run_frequency' => 'Daily',
      'api_entity' => 'ApiTestEntity',
      'api_action' => 'apitestaction',
      'parameters' => 'Semi-formal explanation of runtime job parameters',
      'is_active' => 1,
    );
    $createResult = civicrm_api('job', 'create', $createParams);
    $this->assertAPISuccess($createResult);

    $params = array(
      'id' => $createResult['id'],
      'version' => $this->_apiVersion,
    );
    $result = civicrm_api('job', 'delete', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);
  }

  /**

  public function testCallUpdateGreetingMissingParams() {
    $result = civicrm_api($this->_entity, 'update_greeting', array('gt' => 1, 'version' => $this->_apiVersion));
    $this->assertEquals('Mandatory key(s) missing from params array: ct', $result['error_message']);
  }

  public function testCallUpdateGreetingIncorrectParams() {
    $result = civicrm_api($this->_entity, 'update_greeting', array('gt' => 1, 'ct' => 'djkfhdskjfhds', 'version' => $this->_apiVersion));
    $this->assertEquals('ct `djkfhdskjfhds` is not valid.', $result['error_message']);
  }
/*
 * Note that this test is about tesing the metadata / calling of the function & doesn't test the success of the called function
 */
  public function testCallUpdateGreetingSuccess() {
    $result = civicrm_api($this->_entity, 'update_greeting', array('gt' => 'postal_greeting', 'ct' => 'Individual', 'version' => $this->_apiVersion));
    $this->assertAPISuccess($result);
   }

  public function testCallUpdateGreetingCommaSeparatedParamsSuccess() {
    $gt = 'postal_greeting,email_greeting,addressee';
    $ct = 'Individual,Household';
    $result = civicrm_api($this->_entity, 'update_greeting', array('gt' => $gt, 'ct' => $ct, 'version' => $this->_apiVersion));
    $this->assertAPISuccess($result);
  }
}

