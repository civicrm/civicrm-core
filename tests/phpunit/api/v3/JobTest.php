<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * @version $Id: Job.php 30879 2010-11-22 15:45:55Z shot $
 *
 */

/**
 * Class api_v3_JobTest
 * @group headless
 */
class api_v3_JobTest extends CiviUnitTestCase {
  protected $_apiversion = 3;

  public $DBResetRequired = FALSE;
  public $_entity = 'Job';
  public $_params = array();

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->_params = array(
      'sequential' => 1,
      'name' => 'API_Test_Job',
      'description' => 'A long description written by hand in cursive',
      'run_frequency' => 'Daily',
      'api_entity' => 'ApiTestEntity',
      'api_action' => 'apitestaction',
      'parameters' => 'Semi-formal explanation of runtime job parameters',
      'is_active' => 1,
    );
  }

  /**
   * Check with no name.
   */
  public function testCreateWithoutName() {
    $params = array(
      'is_active' => 1,
    );
    $this->callAPIFailure('job', 'create', $params,
      'Mandatory key(s) missing from params array: run_frequency, name, api_entity, api_action'
    );
  }

  /**
   * Create job with an invalid "run_frequency" value.
   */
  public function testCreateWithInvalidFrequency() {
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
   * Create job.
   */
  public function testCreate() {
    $result = $this->callAPIAndDocument('job', 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['values'][0]['id']);

    // mutate $params to match expected return value
    unset($this->_params['sequential']);
    //assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Core_DAO_Job', $result['id'], $this->_params);
  }

  /**
   * Check with empty array.
   */
  public function testDeleteEmpty() {
    $params = array();
    $result = $this->callAPIFailure('job', 'delete', $params);
  }

  /**
   * Check with No array.
   */
  public function testDeleteParamsNotArray() {
    $result = $this->callAPIFailure('job', 'delete', 'string');
  }

  /**
   * Check if required fields are not passed.
   */
  public function testDeleteWithoutRequired() {
    $params = array(
      'name' => 'API_Test_PP',
      'title' => 'API Test Payment Processor',
      'class_name' => 'CRM_Core_Payment_APITest',
    );

    $result = $this->callAPIFailure('job', 'delete', $params);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: id');
  }

  /**
   * Check with incorrect required fields.
   */
  public function testDeleteWithIncorrectData() {
    $params = array(
      'id' => 'abcd',
    );
    $result = $this->callAPIFailure('job', 'delete', $params);
  }

  /**
   * Check job delete.
   */
  public function testDelete() {
    $createResult = $this->callAPISuccess('job', 'create', $this->_params);
    $params = array('id' => $createResult['id']);
    $result = $this->callAPIAndDocument('job', 'delete', $params, __FUNCTION__, __FILE__);
    $this->assertAPIDeleted($this->_entity, $createResult['id']);
  }

  /**
   *
   * public function testCallUpdateGreetingMissingParams() {
   * $result = $this->callAPISuccess($this->_entity, 'update_greeting', array('gt' => 1));
   * $this->assertEquals('Mandatory key(s) missing from params array: ct', $result['error_message']);
   * }
   *
   * public function testCallUpdateGreetingIncorrectParams() {
   * $result = $this->callAPISuccess($this->_entity, 'update_greeting', array('gt' => 1, 'ct' => 'djkfhdskjfhds'));
   * $this->assertEquals('ct `djkfhdskjfhds` is not valid.', $result['error_message']);
   * }
   * /*
   * Note that this test is about tesing the metadata / calling of the function & doesn't test the success of the called function
   */
  public function testCallUpdateGreetingSuccess() {
    $result = $this->callAPISuccess($this->_entity, 'update_greeting', array(
      'gt' => 'postal_greeting',
      'ct' => 'Individual',
    ));
  }

  public function testCallUpdateGreetingCommaSeparatedParamsSuccess() {
    $gt = 'postal_greeting,email_greeting,addressee';
    $ct = 'Individual,Household';
    $result = $this->callAPISuccess($this->_entity, 'update_greeting', array('gt' => $gt, 'ct' => $ct));
  }

  /**
   * Test the call reminder success sends more than 25 reminders & is not incorrectly limited.
   *
   * Note that this particular test sends the reminders to the additional recipients only
   * as no real reminder person is configured
   *
   * Also note that this is testing a 'job' api so is in this class rather than scheduled_reminder - which
   * seems a cleaner place to build up a collection of scheduled reminder testing functions. However, it seems
   * that the api itself would need to be moved to the scheduled_reminder fn to do that  with the job wrapper being respected for legacy functions
   */
  public function testCallSendReminderSuccessMoreThanDefaultLimit() {
    $membershipTypeID = $this->membershipTypeCreate();
    $this->membershipStatusCreate();
    $createTotal = 30;
    for ($i = 1; $i <= $createTotal; $i++) {
      $contactID = $this->individualCreate();
      $groupID = $this->groupCreate(array('name' => $i, 'title' => $i));
      $result = $this->callAPISuccess('action_schedule', 'create', array(
        'title' => " job $i",
        'subject' => "job $i",
        'entity_value' => $membershipTypeID,
        'mapping_id' => 4,
        'start_action_date' => 'membership_join_date',
        'start_action_offset' => 0,
        'start_action_condition' => 'before',
        'start_action_unit' => 'hour',
        'group_id' => $groupID,
        'limit_to' => FALSE,
      ));
      $this->callAPISuccess('group_contact', 'create', array(
        'contact_id' => $contactID,
        'status' => 'Added',
        'group_id' => $groupID,
      ));
    }
    $result = $this->callAPISuccess('job', 'send_reminder', array());
    $successfulCronCount = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_action_log");
    $this->assertEquals($successfulCronCount, $createTotal);
  }

  /**
   * Test scheduled reminders respect limit to (since above identified addition_to handling issue).
   *
   * We create 3 contacts - 1 is in our group, 1 has our membership & the chosen one has both
   * & check that only the chosen one got the reminder
   */
  public function testCallSendReminderLimitTo() {
    $membershipTypeID = $this->membershipTypeCreate();
    $this->membershipStatusCreate();
    $createTotal = 3;
    $groupID = $this->groupCreate(array('name' => 'Texan drawlers', 'title' => 'a...'));
    for ($i = 1; $i <= $createTotal; $i++) {
      $contactID = $this->individualCreate();
      if ($i == 2) {
        $theChosenOneID = $contactID;
      }
      if ($i < 3) {
        $this->callAPISuccess('group_contact', 'create', array(
          'contact_id' => $contactID,
          'status' => 'Added',
          'group_id' => $groupID,
        ));
      }
      if ($i > 1) {
        $this->callAPISuccess('membership', 'create', array(
            'contact_id' => $contactID,
            'membership_type_id' => $membershipTypeID,
            'join_date' => '+ 1 hour',
          )
        );
      }
    }
    $result = $this->callAPISuccess('action_schedule', 'create', array(
      'title' => " remind all Texans",
      'subject' => "drawling renewal",
      'entity_value' => $membershipTypeID,
      'mapping_id' => 4,
      'start_action_date' => 'membership_join_date',
      'start_action_offset' => 0,
      'start_action_condition' => 'before',
      'start_action_unit' => 'hour',
      'group_id' => $groupID,
      'limit_to' => TRUE,
    ));
    $result = $this->callAPISuccess('job', 'send_reminder', array());
    $successfulCronCount = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_action_log");
    $this->assertEquals($successfulCronCount, 1);
    $sentToID = CRM_Core_DAO::singleValueQuery("SELECT contact_id FROM civicrm_action_log");
    $this->assertEquals($sentToID, $theChosenOneID);
  }

  public function testCallDisableExpiredRelationships() {
    $individualID = $this->individualCreate();
    $orgID = $this->organizationCreate();
    CRM_Utils_Hook_UnitTests::singleton()->setHook('civicrm_pre', array($this, 'hookPreRelationship'));
    $relationshipTypeID = $this->callAPISuccess('relationship_type', 'getvalue', array(
      'return' => 'id',
      'name_a_b' => 'Employee of',
    ));
    $result = $this->callAPISuccess('relationship', 'create', array(
      'relationship_type_id' => $relationshipTypeID,
      'contact_id_a' => $individualID,
      'contact_id_b' => $orgID,
      'is_active' => 1,
      'end_date' => 'yesterday',
    ));
    $relationshipID = $result['id'];
    $this->assertEquals('Hooked', $result['values'][$relationshipID]['description']);
    $this->callAPISuccess($this->_entity, 'disable_expired_relationships', array());
    $result = $this->callAPISuccess('relationship', 'get', array());
    $this->assertEquals('Go Go you good thing', $result['values'][$relationshipID]['description']);
    $this->contactDelete($individualID);
    $this->contactDelete($orgID);
  }

  /**
   * @param $op
   * @param string $objectName
   * @param int $id
   * @param array $params
   */
  public function hookPreRelationship($op, $objectName, $id, &$params) {
    if ($op == 'delete') {
      return;
    }
    if ($params['is_active']) {
      $params['description'] = 'Hooked';
    }
    else {
      $params['description'] = 'Go Go you good thing';
    }
  }

}
