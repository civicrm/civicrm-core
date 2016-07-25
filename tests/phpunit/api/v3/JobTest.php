<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
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
  /**
   * Created membership type.
   *
   * Must be created outside the transaction due to it breaking the transaction.
   *
   * @var
   */
  public $membershipTypeID;

  public function setUp() {
    parent::setUp();
    $this->membershipTypeID = $this->membershipTypeCreate(array('name' => 'General'));
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

  public function tearDown() {
    parent::tearDown();
    $this->membershipTypeDelete(array('id' => $this->membershipTypeID));
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
    $this->callAPIFailure('job', 'create', $params);
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
            'join_date' => 'now',
            'start_date' => '+ 1 day',
          )
        );
      }
    }
    $this->callAPISuccess('action_schedule', 'create', array(
      'title' => " remind all Texans",
      'subject' => "drawling renewal",
      'entity_value' => $membershipTypeID,
      'mapping_id' => 4,
      'start_action_date' => 'membership_start_date',
      'start_action_offset' => 1,
      'start_action_condition' => 'before',
      'start_action_unit' => 'day',
      'group_id' => $groupID,
      'limit_to' => TRUE,
    ));
    $this->callAPISuccess('job', 'send_reminder', array());
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
   * Test the batch merge function.
   *
   * We are just checking it returns without error here.
   */
  public function testBatchMerge() {
    $this->callAPISuccess('Job', 'process_batch_merge', array());
  }

  /**
   * Test the batch merge function actually works!
   *
   * @dataProvider getMergeSets
   *
   * @param $dataSet
   */
  public function testBatchMergeWorks($dataSet) {
    foreach ($dataSet['contacts'] as $params) {
      $this->callAPISuccess('Contact', 'create', $params);
    }

    $result = $this->callAPISuccess('Job', 'process_batch_merge', array('mode' => $dataSet['mode']));
    $this->assertEquals($dataSet['skipped'], count($result['values']['skipped']), 'Failed to skip the right number:' . $dataSet['skipped']);
    $this->assertEquals($dataSet['merged'], count($result['values']['merged']));
    $result = $this->callAPISuccess('Contact', 'get', array(
      'contact_sub_type' => 'Student',
      'sequential' => 1,
      'is_deceased' => array('IN' => array(0, 1)),
      'options' => array('sort' => 'id ASC'),
    ));
    $this->assertEquals(count($dataSet['expected']), $result['count']);
    foreach ($dataSet['expected'] as $index => $contact) {
      foreach ($contact as $key => $value) {
        if ($key == 'gender_id') {
          $key = 'gender';
        }
        $this->assertEquals($value, $result['values'][$index][$key]);
      }
    }
  }

  /**
   * Test the batch merge function actually works!
   *
   * @dataProvider getMergeSets
   *
   * @param $dataSet
   */
  public function testBatchMergeConflictOnDeceased($dataSet) {
    foreach ($dataSet['contacts'] as $params) {
      $this->callAPISuccess('Contact', 'create', $params);
    }

    $result = $this->callAPISuccess('Job', 'process_batch_merge', array('mode' => $dataSet['mode']));
    $this->assertEquals($dataSet['skipped'], count($result['values']['skipped']), 'Failed to skip the right number:' . $dataSet['skipped']);
    $this->assertEquals($dataSet['merged'], count($result['values']['merged']));
    $result = $this->callAPISuccess('Contact', 'get', array(
      'contact_sub_type' => 'Student',
      'sequential' => 1,
      'options' => array('sort' => 'id ASC'),
    ));
    $this->assertEquals(count($dataSet['expected']), $result['count']);
    foreach ($dataSet['expected'] as $index => $contact) {
      foreach ($contact as $key => $value) {
        // Handle the fact it's in a different field in the return value.
        if ($key == 'gender_id') {
          $key = 'gender';
        }
        $this->assertEquals($value, $result['values'][$index][$key]);
      }
    }
  }

  /**
   * Check that the merge carries across various related entities.
   *
   * Note the group combinations & expected results:
   */
  public function testBatchMergeWithAssets() {
    $contactID = $this->individualCreate();
    $contact2ID = $this->individualCreate();
    $this->contributionCreate(array('contact_id' => $contactID));
    $this->contributionCreate(array('contact_id' => $contact2ID, 'invoice_id' => '2', 'trxn_id' => 2));
    $this->contactMembershipCreate(array('contact_id' => $contactID));
    $this->contactMembershipCreate(array('contact_id' => $contact2ID));
    $this->activityCreate(array('source_contact_id' => $contactID, 'target_contact_id' => $contactID, 'assignee_contact_id' => $contactID));
    $this->activityCreate(array('source_contact_id' => $contact2ID, 'target_contact_id' => $contact2ID, 'assignee_contact_id' => $contact2ID));
    $this->tagCreate(array('name' => 'Tall'));
    $this->tagCreate(array('name' => 'Short'));
    $this->entityTagAdd(array('contact_id' => $contactID, 'tag_id' => 'Tall'));
    $this->entityTagAdd(array('contact_id' => $contact2ID, 'tag_id' => 'Short'));
    $this->entityTagAdd(array('contact_id' => $contact2ID, 'tag_id' => 'Tall'));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array('mode' => 'safe'));
    $this->assertEquals(0, count($result['values']['skipped']));
    $this->assertEquals(1, count($result['values']['merged']));
    $this->callAPISuccessGetCount('Contribution', array('contact_id' => $contactID), 2);
    $this->callAPISuccessGetCount('Contribution', array('contact_id' => $contact2ID), 0);
    $this->callAPISuccessGetCount('FinancialItem', array('contact_id' => $contactID), 2);
    $this->callAPISuccessGetCount('FinancialItem', array('contact_id' => $contact2ID), 0);
    $this->callAPISuccessGetCount('Membership', array('contact_id' => $contactID), 2);
    $this->callAPISuccessGetCount('Membership', array('contact_id' => $contact2ID), 0);
    $this->callAPISuccessGetCount('EntityTag', array('contact_id' => $contactID), 2);
    $this->callAPISuccessGetCount('EntityTag', array('contact_id' => $contact2ID), 0);
    // 12 activities is one for each contribution (2), one for each membership (+2 = 4)
    // 3 for each of the added activities as there are 3 roles (+6 = 10
    // 2 for the (source & target) contact merged activity (+2 = 12)
    $this->callAPISuccessGetCount('ActivityContact', array('contact_id' => $contactID), 12);
    // 2 for the connection to the deleted by merge activity (source & target)
    $this->callAPISuccessGetCount('ActivityContact', array('contact_id' => $contact2ID), 2);
  }

  /**
   * Check that the merge carries across various related entities.
   *
   * Note the group combinations 'expected' results:
   *
   * Group 0  Added  null  Added
   * Group 1  Added  Added  Added
   * Group 2  Added  Removed  ****  Added
   * Group 3  Removed  null  **** null
   * Group 4  Removed  Added  **** Added
   * Group 5  Removed  Removed **** null
   * Group 6  null  Added  Added
   * Group 7  null  Removed  **** null
   *
   * The ones with **** are the ones where I think a case could be made to change the behaviour.
   */
  public function testBatchMergeMergesGroups() {
    $contactID = $this->individualCreate();
    $contact2ID = $this->individualCreate();
    $groups = array();
    for ($i = 0; $i < 8; $i++) {
      $groups[] = $this->groupCreate(array(
        'name' => 'mergeGroup' . $i,
        'title' => 'merge group' . $i,
      ));
    }

    $this->callAPISuccess('GroupContact', 'create', array(
      'contact_id' => $contactID,
      'group_id' => $groups[0],
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'contact_id' => $contactID,
      'group_id' => $groups[1],
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'contact_id' => $contactID,
      'group_id' => $groups[2],
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'contact_id' => $contactID,
      'group_id' => $groups[3],
      'status' => 'Removed',
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'contact_id' => $contactID,
      'group_id' => $groups[4],
      'status' => 'Removed',
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'contact_id' => $contactID,
      'group_id' => $groups[5],
      'status' => 'Removed',
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'contact_id' => $contact2ID,
      'group_id' => $groups[1],
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'contact_id' => $contact2ID,
      'group_id' => $groups[2],
      'status' => 'Removed',
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'contact_id' => $contact2ID,
      'group_id' => $groups[4],
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'contact_id' => $contact2ID,
      'group_id' => $groups[5],
      'status' => 'Removed',
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'contact_id' => $contact2ID,
      'group_id' => $groups[6],
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'contact_id' => $contact2ID,
      'group_id' => $groups[7],
      'status' => 'Removed',
    ));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array('mode' => 'safe'));
    $this->assertEquals(0, count($result['values']['skipped']));
    $this->assertEquals(1, count($result['values']['merged']));
    $groupResult = $this->callAPISuccess('GroupContact', 'get', array());
    $this->assertEquals(5, $groupResult['count']);
    $expectedGroups = array(
      $groups[0],
      $groups[1],
      $groups[2],
      $groups[4],
      $groups[6],
    );
    foreach ($groupResult['values'] as $groupValues) {
      $this->assertEquals($contactID, $groupValues['contact_id']);
      $this->assertEquals('Added', $groupValues['status']);
      $this->assertTrue(in_array($groupValues['group_id'], $expectedGroups));

    }
  }

  /**
   * Test the decisions made for addresses when merging.
   *
   * @dataProvider getMergeLocationData
   *
   * Scenarios:
   * (the ones with **** could be disputed as whether it is the best outcome).
   *   'matching_primary' - Primary matches, including location_type_id. One contact has an additional address.
   *     - result - primary is the shared one. Additional address is retained.
   *   'matching_primary_reverse' - Primary matches, including location_type_id. Keep both. (opposite order)
   *     - result - primary is the shared one. Additional address is retained.
   *   'only_one_has_address' - Only one contact has addresses (retain)
   *      - the (only) address is retained
   *   'only_one_has_address_reverse'
   *     - the (only) address is retained
   *   'different_primaries_with_different_location_type' Primaries are different but do not clash due to diff type
   *     - result - both addresses kept. The one from the kept (lowest ID) contact is primary
   *   'different_primaries_with_different_location_type_reverse' Primaries are different but do not clash due to diff type
   *     - result - both addresses kept. The one from the kept (lowest ID) contact is primary
   *   'different_primaries_location_match_only_one_address' per previous but a second address matches the primary but is not primary
   *      - result - both addresses kept. The one from the kept (lowest ID) contact is primary
   *   'different_primaries_location_match_only_one_address_reverse' per previous but a second address matches the primary but is not primary
   *      - result - both addresses kept. The one from the kept (lowest ID) contact is primary
   *  'same_primaries_different_location' Primary addresses are the same but have different location type IDs
   *    - result primary kept with the lowest ID. Other address retained too (to preserve location type info).
   *  'same_primaries_different_location_reverse' Primary addresses are the same but have different location type IDs
   *    - result primary kept with the lowest ID. Other address retained too (to preserve location type info).
   *
   * @param array $dataSet
   */
  public function testBatchMergesAddresses($dataSet) {
    $contactID1 = $this->individualCreate();
    $contactID2 = $this->individualCreate();
    foreach ($dataSet['contact_1'] as $address) {
      $this->callAPISuccess($dataSet['entity'], 'create', array_merge(array('contact_id' => $contactID1), $address));
    }
    foreach ($dataSet['contact_2'] as $address) {
      $this->callAPISuccess($dataSet['entity'], 'create', array_merge(array('contact_id' => $contactID2), $address));
    }

    $result = $this->callAPISuccess('Job', 'process_batch_merge', array('mode' => 'safe'));
    $this->assertEquals(1, count($result['values']['merged']));
    $addresses = $this->callAPISuccess($dataSet['entity'], 'get', array('contact_id' => $contactID1, 'sequential' => 1));
    $this->assertEquals(count($dataSet['expected']), $addresses['count'], "Did not get the expected result for " . $dataSet['entity'] . (!empty($dataSet['description']) ? " on dataset {$dataSet['description']}" : ''));
    $locationTypes = $this->callAPISuccess($dataSet['entity'], 'getoptions', array('field' => 'location_type_id'));
    foreach ($dataSet['expected'] as $index => $expectedAddress) {
      foreach ($expectedAddress as $key => $value) {
        if ($key == 'location_type_id') {
          $this->assertEquals($locationTypes['values'][$addresses['values'][$index][$key]], $value);
        }
        else {
          $this->assertEquals($addresses['values'][$index][$key], $value, "mismatch on $key" . (!empty($dataSet['description']) ? " on dataset {$dataSet['description']}" : ''));
        }
      }
    }
  }

  /**
   * Test altering the address decision by hook.
   *
   * @dataProvider getMergeLocationData
   *
   * @param array $dataSet
   */
  public function testBatchMergesAddressesHook($dataSet) {
    $contactID1 = $this->individualCreate();
    $contactID2 = $this->individualCreate();
    $this->contributionCreate(array('contact_id' => $contactID1, 'receive_date' => '2010-01-01', 'invoice_id' => 1, 'trxn_id' => 1));
    $this->contributionCreate(array('contact_id' => $contactID2, 'receive_date' => '2012-01-01', 'invoice_id' => 2, 'trxn_id' => 2));
    foreach ($dataSet['contact_1'] as $address) {
      $this->callAPISuccess($dataSet['entity'], 'create', array_merge(array('contact_id' => $contactID1), $address));
    }
    foreach ($dataSet['contact_2'] as $address) {
      $this->callAPISuccess($dataSet['entity'], 'create', array_merge(array('contact_id' => $contactID2), $address));
    }
    $this->hookClass->setHook('civicrm_alterLocationMergeData', array($this, 'hookMostRecentDonor'));

    $result = $this->callAPISuccess('Job', 'process_batch_merge', array('mode' => 'safe'));
    $this->assertEquals(1, count($result['values']['merged']));
    $addresses = $this->callAPISuccess($dataSet['entity'], 'get', array('contact_id' => $contactID1, 'sequential' => 1));
    $this->assertEquals(count($dataSet['expected_hook']), $addresses['count']);
    $locationTypes = $this->callAPISuccess($dataSet['entity'], 'getoptions', array('field' => 'location_type_id'));
    foreach ($dataSet['expected_hook'] as $index => $expectedAddress) {
      foreach ($expectedAddress as $key => $value) {
        if ($key == 'location_type_id') {
          $this->assertEquals($locationTypes['values'][$addresses['values'][$index][$key]], $value);
        }
        else {
          $this->assertEquals($value, $addresses['values'][$index][$key], $dataSet['entity'] . ': Unexpected value for ' . $key . (!empty($dataSet['description']) ? " on dataset {$dataSet['description']}" : ''));
        }
      }
    }
  }

  /**
   * Test the organization will not be matched to an individual.
   */
  public function testBatchMergeWillNotMergeOrganizationToIndividual() {
    $individual = $this->callAPISuccess('Contact', 'create', array(
      'contact_type' => 'Individual',
      'organization_name' => 'Anon',
      'email' => 'anonymous@hacker.com',
    ));
    $organization = $this->callAPISuccess('Contact', 'create', array(
      'contact_type' => 'Organization',
      'organization_name' => 'Anon',
      'email' => 'anonymous@hacker.com',
    ));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array('mode' => 'aggressive'));
    $this->assertEquals(0, count($result['values']['skipped']));
    $this->assertEquals(0, count($result['values']['merged']));
    $this->callAPISuccessGetSingle('Contact', array('id' => $individual['id']));
    $this->callAPISuccessGetSingle('Contact', array('id' => $organization['id']));

  }

  /**
   * Test hook allowing modification of the data calculated for merging locations.
   *
   * We are testing a nuanced real life situation where the address data of the
   * most recent donor gets priority - resulting in the primary address being set
   * to the primary address of the most recent donor and address data on a per
   * location type basis also being set to the most recent donor. Hook also excludes
   * a fully matching address with a different location.
   *
   * This has been added to the test suite to ensure the code supports more this
   * type of intervention.
   *
   * @param array $blocksDAO
   *   Array of location DAO to be saved. These are arrays in 2 keys 'update' & 'delete'.
   * @param int $mainId
   *   Contact_id of the contact that survives the merge.
   * @param int $otherId
   *   Contact_id of the contact that will be absorbed and deleted.
   * @param array $migrationInfo
   *   Calculated migration info, informational only.
   *
   * @return mixed
   */
  public function hookMostRecentDonor(&$blocksDAO, $mainId, $otherId, $migrationInfo) {

    $lastDonorID = $this->callAPISuccessGetValue('Contribution', array(
      'return' => 'contact_id',
      'contact_id' => array('IN' => array($mainId, $otherId)),
      'options' => array('sort' => 'receive_date DESC', 'limit' => 1),
    ));
    // Since the last donor is not the main ID we are prioritising info from the last donor.
    // In the test this should always be true - but keep the check in case
    // something changes that we need to detect.
    if ($lastDonorID != $mainId) {
      foreach ($migrationInfo['other_details']['location_blocks'] as $blockType => $blocks) {
        foreach ($blocks as $block) {
          if ($block['is_primary']) {
            $primaryAddressID = $block['id'];
            if (!empty($migrationInfo['main_details']['location_blocks'][$blockType])) {
              foreach ($migrationInfo['main_details']['location_blocks'][$blockType] as $mainBlock) {
                if (empty($blocksDAO[$blockType]['update'][$block['id']]) && $mainBlock['location_type_id'] == $block['location_type_id']) {
                  // This was an address match - we just need to check the is_primary
                  // is true on the matching kept address.
                  $primaryAddressID = $mainBlock['id'];
                  $blocksDAO[$blockType]['update'][$primaryAddressID] = _civicrm_api3_load_DAO($blockType);
                  $blocksDAO[$blockType]['update'][$primaryAddressID]->id = $primaryAddressID;
                }
                $mainLocationTypeID = $mainBlock['location_type_id'];
                // We also want to be more ruthless about removing matching addresses.
                unset($mainBlock['location_type_id']);
                if (CRM_Dedupe_Merger::locationIsSame($block, $mainBlock)
                  && (!isset($blocksDAO[$blockType]['update']) || !isset($blocksDAO[$blockType]['update'][$mainBlock['id']]))
                  && (!isset($blocksDAO[$blockType]['delete']) || !isset($blocksDAO[$blockType]['delete'][$mainBlock['id']]))
                ) {
                  $blocksDAO[$blockType]['delete'][$mainBlock['id']] = _civicrm_api3_load_DAO($blockType);
                  $blocksDAO[$blockType]['delete'][$mainBlock['id']]->id = $mainBlock['id'];
                }
                // Arguably the right way to handle this is just to set is_primary for the primary
                // and for the merge fn to call something like BAO::add & hooks to work etc.
                // if that happens though this should keep working...
                elseif ($mainBlock['is_primary'] && $mainLocationTypeID != $block['location_type_id']) {
                  $blocksDAO['address']['update'][$mainBlock['id']] = _civicrm_api3_load_DAO($blockType);
                  $blocksDAO['address']['update'][$mainBlock['id']]->is_primary = 0;
                  $blocksDAO['address']['update'][$mainBlock['id']]->id = $mainBlock['id'];
                }

              }
              $blocksDAO[$blockType]['update'][$primaryAddressID]->is_primary = 1;
            }
          }
        }
      }
    }
  }

  /**
   * Get address combinations for the merge test.
   *
   * @return array
   */
  public function getMergeLocationData() {
    $address1 = array('street_address' => 'Buckingham Palace', 'city' => 'London');
    $address2 = array('street_address' => 'The Doghouse', 'supplemental_address_1' => 'under the blanket');
    $data = $this->getMergeLocations($address1, $address2, 'Address');
    $data = array_merge($data, $this->getMergeLocations(array('phone' => '12345', 'phone_type_id' => 1), array('phone' => '678910', 'phone_type_id' => 1), 'Phone'));
    $data = array_merge($data, $this->getMergeLocations(array('phone' => '12345'), array('phone' => '678910'), 'Phone'));
    $data = array_merge($data, $this->getMergeLocations(array('email' => 'mini@me.com'), array('email' => 'mini@me.org'), 'Email', array(array(
      'email' => 'anthony_anderson@civicrm.org',
      'location_type_id' => 'Home',
    ))));
    return $data;

  }

  /**
   * Test the batch merge does not create duplicate emails.
   *
   * Test CRM-18546, a 4.7 regression whereby a merged contact gets duplicate emails.
   */
  public function testBatchMergeEmailHandling() {
    for ($x = 0; $x <= 4; $x++) {
      $id = $this->individualCreate(array('email' => 'batman@gotham.met'));
    }
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array());
    $this->assertEquals(4, count($result['values']['merged']));
    $this->callAPISuccessGetCount('Contact', array('email' => 'batman@gotham.met'), 1);
    $contacts = $this->callAPISuccess('Contact', 'get', array('is_deleted' => 0));
    $deletedContacts = $this->callAPISuccess('Contact', 'get', array('is_deleted' => 1));
    $this->callAPISuccessGetCount('Email', array(
      'email' => 'batman@gotham.met',
      'contact_id' => array('IN' => array_keys($contacts['values'])),
    ), 1);
    $this->callAPISuccessGetCount('Email', array(
      'email' => 'batman@gotham.met',
      'contact_id' => array('IN' => array_keys($deletedContacts['values'])),
    ), 4);
  }

  /**
   * Test the batch merge does not fatal on an empty rule.
   *
   * @dataProvider getRuleSets
   *
   * @param string $contactType
   * @param string $used
   * @param bool $isReserved
   * @param int $threshold
   */
  public function testBatchMergeEmptyRule($contactType, $used, $name, $isReserved, $threshold) {
    $ruleGroup = $this->callAPISuccess('RuleGroup', 'create', array(
      'contact_type' => $contactType,
      'threshold' => $threshold,
      'used' => $used,
      'name' => $name,
      'is_reserved' => $isReserved,
    ));
    $this->callAPISuccess('Job', 'process_batch_merge', array('rule_group_id' => $ruleGroup['id']));
    $this->callAPISuccess('RuleGroup', 'delete', array('id' => $ruleGroup['id']));
  }

  /**
   * Get the various rule combinations.
   */
  public function getRuleSets() {
    $contactTypes = array('Individual', 'Organization', 'Household');
    $useds = array('Unsupervised', 'General', 'Supervised');
    $ruleGroups = array();
    foreach ($contactTypes as $contactType) {
      foreach ($useds as $used) {
        $ruleGroups[] = array($contactType, $used, 'Bob', FALSE, 0);
        $ruleGroups[] = array($contactType, $used, 'Bob', FALSE, 10);
        $ruleGroups[] = array($contactType, $used, 'Bob', TRUE, 10);
        $ruleGroups[] = array($contactType, $used, $contactType . $used, FALSE, 10);
        $ruleGroups[] = array($contactType, $used, $contactType . $used, TRUE, 10);
      }
    }
    return $ruleGroups;
  }

  /**
   * Test the batch merge does not create duplicate emails.
   *
   * Test CRM-18546, a 4.7 regression whereby a merged contact gets duplicate emails.
   */
  public function testBatchMergeMatchingAddress() {
    for ($x = 0; $x <= 2; $x++) {
      $this->individualCreate(array(
        'api.address.create' => array(
          'location_type_id' => 'Home',
          'street_address' => 'Appt 115, The Batcave',
          'city' => 'Gotham',
          'postal_code' => 'Nananananana',
        ),
      ));
    }
    // Different location type, still merge, identical.
    $this->individualCreate(array(
      'api.address.create' => array(
        'location_type_id' => 'Main',
        'street_address' => 'Appt 115, The Batcave',
        'city' => 'Gotham',
        'postal_code' => 'Nananananana',
      ),
    ));

    $this->individualCreate(array(
      'api.address.create' => array(
        'location_type_id' => 'Home',
        'street_address' => 'Appt 115, The Batcave',
        'city' => 'Gotham',
        'postal_code' => 'Batman',
      ),
    ));

    $result = $this->callAPISuccess('Job', 'process_batch_merge', array());
    $this->assertEquals(3, count($result['values']['merged']));
    $this->assertEquals(1, count($result['values']['skipped']));
    $this->callAPISuccessGetCount('Contact', array('street_address' => 'Appt 115, The Batcave'), 2);
    $contacts = $this->callAPISuccess('Contact', 'get', array('is_deleted' => 0));
    $deletedContacts = $this->callAPISuccess('Contact', 'get', array('is_deleted' => 1));
    $this->callAPISuccessGetCount('Address', array(
      'street_address' => 'Appt 115, The Batcave',
      'contact_id' => array('IN' => array_keys($contacts['values'])),
    ), 3);

    $this->callAPISuccessGetCount('Address', array(
      'street_address' => 'Appt 115, The Batcave',
      'contact_id' => array('IN' => array_keys($deletedContacts['values'])),
    ), 2);
  }

  /**
   * Test the batch merge by id range.
   *
   * We have 2 sets of 5 matches & set the merge only to merge the lower set.
   */
  public function testBatchMergeIDRange() {
    for ($x = 0; $x <= 4; $x++) {
      $id = $this->individualCreate(array('email' => 'batman@gotham.met'));
    }
    for ($x = 0; $x <= 4; $x++) {
      $this->individualCreate(array('email' => 'robin@gotham.met'));
    }
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array('criteria' => array('contact' => array('id' => array('<' => $id)))));
    $this->assertEquals(4, count($result['values']['merged']));
    $this->callAPISuccessGetCount('Contact', array('email' => 'batman@gotham.met'), 1);
    $this->callAPISuccessGetCount('Contact', array('email' => 'robin@gotham.met'), 5);
    $contacts = $this->callAPISuccess('Contact', 'get', array('is_deleted' => 0));
    $deletedContacts = $this->callAPISuccess('Contact', 'get', array('is_deleted' => 0));
    $this->callAPISuccessGetCount('Email', array(
      'email' => 'batman@gotham.met',
      'contact_id' => array('IN' => array_keys($contacts['values'])),
    ), 1);
    $this->callAPISuccessGetCount('Email', array(
      'email' => 'batman@gotham.met',
      'contact_id' => array('IN' => array_keys($deletedContacts['values'])),
    ), 1);
    $this->callAPISuccessGetCount('Email', array(
      'email' => 'robin@gotham.met',
      'contact_id' => array('IN' => array_keys($contacts['values'])),
    ), 5);

  }

  /**
   * Test the batch merge function actually works!
   *
   * @dataProvider getMergeSets
   *
   * @param $dataSet
   */
  public function testBatchMergeWorksCheckPermissionsTrue($dataSet) {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM', 'administer CiviCRM');
    foreach ($dataSet['contacts'] as $params) {
      $this->callAPISuccess('Contact', 'create', $params);
    }

    $result = $this->callAPISuccess('Job', 'process_batch_merge', array('check_permissions' => 1, 'mode' => $dataSet['mode']));
    $this->assertEquals(0, count($result['values']['merged']), 'User does not have permission to any contacts, so no merging');
    $this->assertEquals(0, count($result['values']['skipped']), 'User does not have permission to any contacts, so no skip visibility');
  }

  /**
   * Test the batch merge function actually works!
   *
   * @dataProvider getMergeSets
   *
   * @param $dataSet
   */
  public function testBatchMergeWorksCheckPermissionsFalse($dataSet) {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM', 'edit my contact');
    foreach ($dataSet['contacts'] as $params) {
      $this->callAPISuccess('Contact', 'create', $params);
    }

    $result = $this->callAPISuccess('Job', 'process_batch_merge', array('check_permissions' => 0, 'mode' => $dataSet['mode']));
    $this->assertEquals($dataSet['skipped'], count($result['values']['skipped']), 'Failed to skip the right number:' . $dataSet['skipped']);
    $this->assertEquals($dataSet['merged'], count($result['values']['merged']));
  }

  /**
   * Get data for batch merge.
   */
  public function getMergeSets() {
    $data = array(
      array(
        array(
          'mode' => 'safe',
          'contacts' => array(
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Address.create' => array(
                'street_address' => 'big house',
                'location_type_id' => 'Home',
              ),
            ),
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
            ),
          ),
          'skipped' => 0,
          'merged' => 1,
          'expected' => array(
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
            ),
          ),
        ),
      ),
      array(
        array(
          'mode' => 'safe',
          'contacts' => array(
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Address.create' => array(
                'street_address' => 'big house',
                'location_type_id' => 'Home',
              ),
            ),
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Address.create' => array(
                'street_address' => 'bigger house',
                'location_type_id' => 'Home',
              ),
            ),
          ),
          'skipped' => 1,
          'merged' => 0,
          'expected' => array(
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'street_address' => 'big house',
            ),
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'street_address' => 'bigger house',
            ),
          ),
        ),
      ),
      array(
        array(
          'mode' => 'safe',
          'contacts' => array(
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Email.create' => array(
                'email' => 'big.slog@work.co.nz',
                'location_type_id' => 'Work',
              ),
            ),
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Email.create' => array(
                'email' => 'big.slog@work.com',
                'location_type_id' => 'Work',
              ),
            ),
          ),
          'skipped' => 1,
          'merged' => 0,
          'expected' => array(
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
            ),
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
            ),
          ),
        ),
      ),
      array(
        array(
          'mode' => 'safe',
          'contacts' => array(
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Phone.create' => array(
                'phone' => '123456',
                'location_type_id' => 'Work',
              ),
            ),
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Phone.create' => array(
                'phone' => '23456',
                'location_type_id' => 'Work',
              ),
            ),
          ),
          'skipped' => 1,
          'merged' => 0,
          'expected' => array(
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
            ),
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
            ),
          ),
        ),
      ),
      array(
        array(
          'mode' => 'aggressive',
          'contacts' => array(
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Address.create' => array(
                'street_address' => 'big house',
                'location_type_id' => 'Home',
              ),
            ),
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Address.create' => array(
                'street_address' => 'bigger house',
                'location_type_id' => 'Home',
              ),
            ),
          ),
          'skipped' => 0,
          'merged' => 1,
          'expected' => array(
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'street_address' => 'big house',
            ),
          ),
        ),
      ),
      array(
        array(
          'mode' => 'safe',
          'contacts' => array(
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Address.create' => array(
                'street_address' => 'big house',
                'location_type_id' => 'Home',
              ),
            ),
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'is_deceased' => 1,
            ),
          ),
          'skipped' => 1,
          'merged' => 0,
          'expected' => array(
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'is_deceased' => 0,
            ),
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'is_deceased' => 1,
            ),
          ),
        ),
      ),
      array(
        array(
          'mode' => 'safe',
          'contacts' => array(
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Address.create' => array(
                'street_address' => 'big house',
                'location_type_id' => 'Home',
              ),
              'is_deceased' => 1,
            ),
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
            ),
          ),
          'skipped' => 1,
          'merged' => 0,
          'expected' => array(
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'is_deceased' => 1,
            ),
            array(
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'is_deceased' => 0,
            ),
          ),
        ),
      ),
    );

    $conflictPairs = array(
      'first_name' => 'Dianna',
      'last_name' => 'McAndrew',
      'middle_name' => 'Prancer',
      'birth_date' => '2015-12-25',
      'gender_id' => 'Female',
      'job_title' => 'Thriller',
    );

    foreach ($conflictPairs as $key => $value) {
      $contactParams = array(
        'first_name' => 'Michael',
        'middle_name' => 'Dancer',
        'last_name' => 'Jackson',
        'birth_date' => '2015-02-25',
        'email' => 'michael@neverland.com',
        'contact_type' => 'Individual',
        'contact_sub_type' => array('Student'),
        'gender_id' => 'Male',
        'job_title' => 'Entertainer',
      );
      $contact2 = $contactParams;

      $contact2[$key] = $value;
      $data[$key . '_conflict'] = array(
        array(
          'mode' => 'safe',
          'contacts' => array($contactParams, $contact2),
          'skipped' => 1,
          'merged' => 0,
          'expected' => array($contactParams, $contact2),
        ),
      );
    }

    return $data;
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

  /**
   * Get the location data set.
   *
   * @param array $locationParams1
   * @param array $locationParams2
   * @param string $entity
   *
   * @return array
   */
  public function getMergeLocations($locationParams1, $locationParams2, $entity, $additionalExpected = array()) {
    $data = array(
      array(
        'matching_primary' => array(
          'entity' => $entity,
          'contact_1' => array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
          ),
          'contact_2' => array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
          ),
          'expected' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
          )),
          'expected_hook' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
          )),
        ),
      ),
      array(
        'matching_primary_reverse' => array(
          'entity' => $entity,
          'contact_1' => array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
          ),
          'contact_2' => array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
          ),
          'expected' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
          )),
          'expected_hook' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
          )),
        ),
      ),
      array(
        'only_one_has_address' => array(
          'entity' => $entity,
          'contact_1' => array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
          ),
          'contact_2' => array(),
          'expected' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
          )),
          'expected_hook' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Main',
              // When dealing with email we don't have a clean slate - the existing
              // primary will be primary.
              'is_primary' => ($entity == 'Email' ? 0 : 1),
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
          )),
        ),
      ),
      array(
        'only_one_has_address_reverse' => array(
          'description' => 'The destination contact does not have an address. secondary contact should be merged in.',
          'entity' => $entity,
          'contact_1' => array(),
          'contact_2' => array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
          ),
          'expected' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Main',
              // When dealing with email we don't have a clean slate - the existing
              // primary will be primary.
              'is_primary' => ($entity == 'Email' ? 0 : 1),
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
          )),
          'expected_hook' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
          )),
        ),
      ),
      array(
        'different_primaries_with_different_location_type' => array(
          'description' => 'Primaries are different with different location. Keep both addresses. Set primary to be that of lower id',
          'entity' => $entity,
          'contact_1' => array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
          ),
          'contact_2' => array(
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ), $locationParams2),
          ),
          'expected' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
          )),
          'expected_hook' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 0,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ), $locationParams2),
          )),
        ),
      ),
      array(
        'different_primaries_with_different_location_type_reverse' => array(
          'entity' => $entity,
          'contact_1' => array(
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ), $locationParams2),
          ),
          'contact_2' => array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
          ),
          'expected' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ), $locationParams2),
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 0,
            ), $locationParams1),
          )),
          'expected_hook' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
          )),
        ),
      ),
      array(
        'different_primaries_location_match_only_one_address' => array(
          'entity' => $entity,
          'contact_1' => array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
          ),
          'contact_2' => array(
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ), $locationParams2),

          ),
          'expected' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
          )),
          'expected_hook' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 0,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ), $locationParams2),
          )),
        ),
      ),
      array(
        'different_primaries_location_match_only_one_address_reverse' => array(
          'entity' => $entity,
          'contact_1' => array(
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ), $locationParams2),
          ),
          'contact_2' => array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
          ),
          'expected' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ), $locationParams2),
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 0,
            ), $locationParams1),
          )),
          'expected_hook' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams2),
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
          )),
        ),
      ),
      array(
        'same_primaries_different_location' => array(
          'entity' => $entity,
          'contact_1' => array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
          ),
          'contact_2' => array(
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ), $locationParams1),

          ),
          'expected' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ), $locationParams1),
          )),
          'expected_hook' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ), $locationParams1),
          )),
        ),
      ),
      array(
        'same_primaries_different_location_reverse' => array(
          'entity' => $entity,
          'contact_1' => array(
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ), $locationParams1),
          ),
          'contact_2' => array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
          ),
          'expected' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ), $locationParams1),
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 0,
            ), $locationParams1),
          )),
          'expected_hook' => array_merge($additionalExpected, array(
            array_merge(array(
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ), $locationParams1),
          )),
        ),
      ),
    );
    return $data;
  }

}
