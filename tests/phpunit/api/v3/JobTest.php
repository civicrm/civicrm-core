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
 * File for the CiviCRM APIv3 job functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Job
 *
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class api_v3_JobTest
 *
 * @group headless
 */
class api_v3_JobTest extends CiviUnitTestCase {

  public $DBResetRequired = FALSE;

  public $_entity = 'Job';

  /**
   * Created membership type.
   *
   * Must be created outside the transaction due to it breaking the transaction.
   *
   * @var int
   */
  public $membershipTypeID;

  /**
   * Report instance used in mail_report tests.
   * @var array
   */
  private $report_instance;

  /**
   * Set up for tests.
   */
  public function setUp() {
    parent::setUp();
    $this->membershipTypeID = $this->membershipTypeCreate(['name' => 'General']);
    $this->useTransaction(TRUE);
    $this->_params = [
      'sequential' => 1,
      'name' => 'API_Test_Job',
      'description' => 'A long description written by hand in cursive',
      'run_frequency' => 'Daily',
      'api_entity' => 'ApiTestEntity',
      'api_action' => 'apitestaction',
      'parameters' => 'Semi-formal explanation of runtime job parameters',
      'is_active' => 1,
    ];
    $this->report_instance = $this->createReportInstance();
  }

  /**
   * Cleanup after test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown() {
    parent::tearDown();
    // The membershipType create breaks transactions so this extra cleanup is needed.
    $this->membershipTypeDelete(['id' => $this->membershipTypeID]);
    $this->cleanUpSetUpIDs();
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_contact', 'civicrm_address', 'civicrm_email', 'civicrm_website', 'civicrm_phone'], TRUE);
    parent::tearDown();
  }

  /**
   * Check with no name.
   */
  public function testCreateWithoutName() {
    $params = [
      'is_active' => 1,
    ];
    $this->callAPIFailure('job', 'create', $params,
      'Mandatory key(s) missing from params array: run_frequency, name, api_entity, api_action'
    );
  }

  /**
   * Create job with an invalid "run_frequency" value.
   */
  public function testCreateWithInvalidFrequency() {
    $params = [
      'sequential' => 1,
      'name' => 'API_Test_Job',
      'description' => 'A long description written by hand in cursive',
      'run_frequency' => 'Fortnightly',
      'api_entity' => 'ApiTestEntity',
      'api_action' => 'apitestaction',
      'parameters' => 'Semi-formal explanation of runtime job parameters',
      'is_active' => 1,
    ];
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
   * Clone job
   *
   * @throws \CRM_Core_Exception
   */
  public function testClone() {
    $createResult = $this->callAPISuccess('job', 'create', $this->_params);
    $params = ['id' => $createResult['id']];
    $cloneResult = $this->callAPIAndDocument('job', 'clone', $params, __FUNCTION__, __FILE__);
    $clonedJob = $cloneResult['values'][$cloneResult['id']];
    $this->assertEquals($this->_params['name'] . ' - Copy', $clonedJob['name']);
    $this->assertEquals($this->_params['description'], $clonedJob['description']);
    $this->assertEquals($this->_params['parameters'], $clonedJob['parameters']);
    $this->assertEquals($this->_params['is_active'], $clonedJob['is_active']);
    $this->assertArrayNotHasKey('last_run', $clonedJob);
    $this->assertArrayNotHasKey('scheduled_run_date', $clonedJob);
  }

  /**
   * Check if required fields are not passed.
   */
  public function testDeleteWithoutRequired() {
    $params = [
      'name' => 'API_Test_PP',
      'title' => 'API Test Payment Processor',
      'class_name' => 'CRM_Core_Payment_APITest',
    ];

    $result = $this->callAPIFailure('job', 'delete', $params);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: id');
  }

  /**
   * Check with incorrect required fields.
   */
  public function testDeleteWithIncorrectData() {
    $params = [
      'id' => 'abcd',
    ];
    $this->callAPIFailure('job', 'delete', $params);
  }

  /**
   * Check job delete.
   */
  public function testDelete() {
    $createResult = $this->callAPISuccess('job', 'create', $this->_params);
    $params = ['id' => $createResult['id']];
    $this->callAPIAndDocument('job', 'delete', $params, __FUNCTION__, __FILE__);
    $this->assertAPIDeleted($this->_entity, $createResult['id']);
  }

  /**
   * Test greeting update job.
   *
   * Note that this test is about tesing the metadata / calling of the function & doesn't test the success of the called function
   *
   * @throws \CRM_Core_Exception
   */
  public function testCallUpdateGreetingSuccess() {
    $this->callAPISuccess($this->_entity, 'update_greeting', [
      'gt' => 'postal_greeting',
      'ct' => 'Individual',
    ]);
  }

  /**
   * Test greeting update handles comma separated params.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCallUpdateGreetingCommaSeparatedParamsSuccess() {
    $gt = 'postal_greeting,email_greeting,addressee';
    $ct = 'Individual,Household';
    $this->callAPISuccess($this->_entity, 'update_greeting', ['gt' => $gt, 'ct' => $ct]);
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testCallSendReminderSuccessMoreThanDefaultLimit() {
    $membershipTypeID = $this->membershipTypeCreate();
    $this->membershipStatusCreate();
    $createTotal = 30;
    for ($i = 1; $i <= $createTotal; $i++) {
      $contactID = $this->individualCreate();
      $groupID = $this->groupCreate(['name' => $i, 'title' => $i]);
      $this->callAPISuccess('action_schedule', 'create', [
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
      ]);
      $this->callAPISuccess('group_contact', 'create', [
        'contact_id' => $contactID,
        'status' => 'Added',
        'group_id' => $groupID,
      ]);
    }
    $this->callAPISuccess('job', 'send_reminder', []);
    $successfulCronCount = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_action_log");
    $this->assertEquals($successfulCronCount, $createTotal);
  }

  /**
   * Test scheduled reminders respect limit to (since above identified addition_to handling issue).
   *
   * We create 3 contacts - 1 is in our group, 1 has our membership & the chosen one has both
   * & check that only the chosen one got the reminder
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testCallSendReminderLimitToSMS() {
    list($membershipTypeID, $groupID, $theChosenOneID, $provider) = $this->setUpMembershipSMSReminders();
    $this->callAPISuccess('action_schedule', 'create', [
      'title' => ' remind all Texans',
      'subject' => 'drawling renewal',
      'entity_value' => $membershipTypeID,
      'mapping_id' => 4,
      'start_action_date' => 'membership_start_date',
      'start_action_offset' => 1,
      'start_action_condition' => 'before',
      'start_action_unit' => 'day',
      'group_id' => $groupID,
      'limit_to' => TRUE,
      'sms_provider_id' => $provider['id'],
      'mode' => 'User_Preference',
    ]);
    $this->callAPISuccess('job', 'send_reminder', []);
    $successfulCronCount = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_action_log");
    $this->assertEquals($successfulCronCount, 1);
    $sentToID = CRM_Core_DAO::singleValueQuery("SELECT contact_id FROM civicrm_action_log");
    $this->assertEquals($sentToID, $theChosenOneID);
    $this->assertEquals(0, CRM_Core_DAO::singleValueQuery("SELECT is_error FROM civicrm_action_log"));
    $this->setupForSmsTests(TRUE);
  }

  /**
   * Test disabling expired relationships.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCallDisableExpiredRelationships() {
    $individualID = $this->individualCreate();
    $orgID = $this->organizationCreate();
    CRM_Utils_Hook_UnitTests::singleton()->setHook('civicrm_pre', [$this, 'hookPreRelationship']);
    $relationshipTypeID = $this->callAPISuccess('relationship_type', 'getvalue', [
      'return' => 'id',
      'name_a_b' => 'Employee of',
    ]);
    $result = $this->callAPISuccess('relationship', 'create', [
      'relationship_type_id' => $relationshipTypeID,
      'contact_id_a' => $individualID,
      'contact_id_b' => $orgID,
      'is_active' => 1,
      'end_date' => 'yesterday',
    ]);
    $relationshipID = $result['id'];
    $this->assertEquals('Hooked', $result['values'][$relationshipID]['description']);
    $this->callAPISuccess($this->_entity, 'disable_expired_relationships', []);
    $result = $this->callAPISuccess('relationship', 'get', []);
    $this->assertEquals('Go Go you good thing', $result['values'][$relationshipID]['description']);
    $this->contactDelete($individualID);
    $this->contactDelete($orgID);
  }

  /**
   * Event templates should not send reminders to additional contacts.
   *
   * @throws \CRM_Core_Exception
   */
  public function testTemplateRemindAddlContacts() {
    $contactId = $this->individualCreate();
    $groupId = $this->groupCreate(['name' => 'Additional Contacts', 'title' => 'Additional Contacts']);
    $this->callAPISuccess('GroupContact', 'create', [
      'contact_id' => $contactId,
      'group_id' => $groupId,
    ]);
    $event = $this->eventCreate(['is_template' => 1, 'template_title' => "I'm a template", 'title' => NULL]);
    $eventId = $event['id'];

    $this->callAPISuccess('action_schedule', 'create', [
      'title' => 'Do not send me',
      'subject' => 'I am a reminder attached to a template.',
      'entity_value' => $eventId,
      'mapping_id' => 5,
      'start_action_date' => 'start_date',
      'start_action_offset' => 1,
      'start_action_condition' => 'before',
      'start_action_unit' => 'day',
      'group_id' => $groupId,
      'limit_to' => FALSE,
      'mode' => 'Email',
    ]);

    $this->callAPISuccess('job', 'send_reminder', []);
    $successfulCronCount = CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_action_log');
    $this->assertEquals(0, $successfulCronCount);
  }

  /**
   * Test scheduled reminders respect limit to (since above identified addition_to handling issue).
   *
   * We create 3 contacts - 1 is in our group, 1 has our membership & the chosen one has both
   * & check that only the chosen one got the reminder
   *
   * Also check no hard fail on cron job with running a reminder that has a deleted SMS provider
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testCallSendReminderLimitToSMSWithDeletedProvider() {
    list($membershipTypeID, $groupID, $theChosenOneID, $provider) = $this->setUpMembershipSMSReminders();
    $this->callAPISuccess('action_schedule', 'create', [
      'title' => ' remind all Texans',
      'subject' => 'drawling renewal',
      'entity_value' => $membershipTypeID,
      'mapping_id' => 4,
      'start_action_date' => 'membership_start_date',
      'start_action_offset' => 1,
      'start_action_condition' => 'before',
      'start_action_unit' => 'day',
      'group_id' => $groupID,
      'limit_to' => TRUE,
      'sms_provider_id' => $provider['id'],
      'mode' => 'SMS',
    ]);
    $this->callAPISuccess('SmsProvider', 'delete', ['id' => $provider['id']]);
    $this->callAPISuccess('job', 'send_reminder', []);
    $cronCount = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_action_log");
    $this->assertEquals($cronCount, 1);
    $sentToID = CRM_Core_DAO::singleValueQuery("SELECT contact_id FROM civicrm_action_log");
    $this->assertEquals($sentToID, $theChosenOneID);
    $cronlog = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_action_log")->fetchAll()[0];
    $this->assertEquals(1, $cronlog['is_error']);
    $this->assertEquals('SMS reminder cannot be sent because the SMS provider has been deleted.', $cronlog['message']);
    $this->setupForSmsTests(TRUE);
  }

  /**
   * Test the batch merge function.
   *
   * We are just checking it returns without error here.
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMerge() {
    $this->callAPISuccess('Job', 'process_batch_merge', []);
  }

  /**
   * Test the batch merge function actually works!
   *
   * @dataProvider getMergeSets
   *
   * @param $dataSet
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergeWorks($dataSet) {
    foreach ($dataSet['contacts'] as $params) {
      $this->callAPISuccess('Contact', 'create', $params);
    }

    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['mode' => $dataSet['mode']]);
    $this->assertCount($dataSet['skipped'], $result['values']['skipped'], 'Failed to skip the right number:' . $dataSet['skipped']);
    $this->assertCount($dataSet['merged'], $result['values']['merged']);
    $result = $this->callAPISuccess('Contact', 'get', [
      'contact_sub_type' => 'Student',
      'sequential' => 1,
      'is_deceased' => ['IN' => [0, 1]],
      'options' => ['sort' => 'id ASC'],
    ]);
    $this->assertEquals(count($dataSet['expected']), $result['count']);
    foreach ($dataSet['expected'] as $index => $contact) {
      foreach ($contact as $key => $value) {
        if ($key === 'gender_id') {
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergeWithAssets() {
    $contactID = $this->individualCreate();
    $contact2ID = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $contactID]);
    $this->contributionCreate(['contact_id' => $contact2ID, 'invoice_id' => '2', 'trxn_id' => 2]);
    $this->contactMembershipCreate(['contact_id' => $contactID]);
    $this->contactMembershipCreate(['contact_id' => $contact2ID]);
    $this->activityCreate(['source_contact_id' => $contactID, 'target_contact_id' => $contactID, 'assignee_contact_id' => $contactID]);
    $this->activityCreate(['source_contact_id' => $contact2ID, 'target_contact_id' => $contact2ID, 'assignee_contact_id' => $contact2ID]);
    $this->tagCreate(['name' => 'Tall']);
    $this->tagCreate(['name' => 'Short']);
    $this->entityTagAdd(['contact_id' => $contactID, 'tag_id' => 'Tall']);
    $this->entityTagAdd(['contact_id' => $contact2ID, 'tag_id' => 'Short']);
    $this->entityTagAdd(['contact_id' => $contact2ID, 'tag_id' => 'Tall']);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['mode' => 'safe']);
    $this->assertCount(0, $result['values']['skipped']);
    $this->assertCount(1, $result['values']['merged']);
    $this->callAPISuccessGetCount('Contribution', ['contact_id' => $contactID], 2);
    $this->callAPISuccessGetCount('Contribution', ['contact_id' => $contact2ID], 0);
    $this->callAPISuccessGetCount('FinancialItem', ['contact_id' => $contactID], 2);
    $this->callAPISuccessGetCount('FinancialItem', ['contact_id' => $contact2ID], 0);
    $this->callAPISuccessGetCount('Membership', ['contact_id' => $contactID], 2);
    $this->callAPISuccessGetCount('Membership', ['contact_id' => $contact2ID], 0);
    $this->callAPISuccessGetCount('EntityTag', ['contact_id' => $contactID], 2);
    $this->callAPISuccessGetCount('EntityTag', ['contact_id' => $contact2ID], 0);
    // 14 activities is one for each contribution (2), two (source + target) for each membership (+(2x2) = 6)
    // 3 for each of the added activities as there are 3 roles (+6 = 12
    // 2 for the (source & target) contact merged activity (+2 = 14)
    $this->callAPISuccessGetCount('ActivityContact', ['contact_id' => $contactID], 14);
    // 2 for the connection to the deleted by merge activity (source & target)
    $this->callAPISuccessGetCount('ActivityContact', ['contact_id' => $contact2ID], 2);
  }

  /**
   * Test that non-contact entity tags are untouched in merge.
   *
   * @throws \CRM_Core_Exception
   */
  public function testContributionEntityTag() {
    $this->callAPISuccess('OptionValue', 'create', ['option_group_id' => 'tag_used_for', 'value' => 'civicrm_contribution', 'label' => 'Contribution']);
    $tagID = $this->tagCreate(['name' => 'Big', 'used_for' => 'civicrm_contribution'])['id'];
    $contact1 = (int) $this->individualCreate();
    $contact2 = (int) $this->individualCreate();
    $contributionID = NULL;
    while ($contributionID !== $contact2) {
      $contributionID = (int) $this->callAPISuccess('Contribution', 'create', ['contact_id' => $contact1, 'total_amount' => 5, 'financial_type_id' => 'Donation'])['id'];
    }
    $entityTagParams = ['entity_id' => $contributionID, 'entity_table' => 'civicrm_contribution', 'tag_id' => $tagID];
    $this->callAPISuccess('EntityTag', 'create', $entityTagParams);
    $this->callAPISuccessGetSingle('EntityTag', $entityTagParams);
    $this->callAPISuccess('Job', 'process_batch_merge', ['mode' => 'safe']);
    $this->callAPISuccessGetSingle('EntityTag', $entityTagParams);
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergeMergesGroups() {
    $contactID = $this->individualCreate();
    $contact2ID = $this->individualCreate();
    $groups = [];
    for ($i = 0; $i < 8; $i++) {
      $groups[] = $this->groupCreate([
        'name' => 'mergeGroup' . $i,
        'title' => 'merge group' . $i,
      ]);
    }

    $this->callAPISuccess('GroupContact', 'create', [
      'contact_id' => $contactID,
      'group_id' => $groups[0],
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'contact_id' => $contactID,
      'group_id' => $groups[1],
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'contact_id' => $contactID,
      'group_id' => $groups[2],
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'contact_id' => $contactID,
      'group_id' => $groups[3],
      'status' => 'Removed',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'contact_id' => $contactID,
      'group_id' => $groups[4],
      'status' => 'Removed',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'contact_id' => $contactID,
      'group_id' => $groups[5],
      'status' => 'Removed',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'contact_id' => $contact2ID,
      'group_id' => $groups[1],
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'contact_id' => $contact2ID,
      'group_id' => $groups[2],
      'status' => 'Removed',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'contact_id' => $contact2ID,
      'group_id' => $groups[4],
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'contact_id' => $contact2ID,
      'group_id' => $groups[5],
      'status' => 'Removed',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'contact_id' => $contact2ID,
      'group_id' => $groups[6],
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'contact_id' => $contact2ID,
      'group_id' => $groups[7],
      'status' => 'Removed',
    ]);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['mode' => 'safe']);
    $this->assertCount(0, $result['values']['skipped']);
    $this->assertCount(1, $result['values']['merged']);
    $groupResult = $this->callAPISuccess('GroupContact', 'get', []);
    $this->assertEquals(5, $groupResult['count']);
    $expectedGroups = [
      $groups[0],
      $groups[1],
      $groups[2],
      $groups[4],
      $groups[6],
    ];
    foreach ($groupResult['values'] as $groupValues) {
      $this->assertEquals($contactID, $groupValues['contact_id']);
      $this->assertEquals('Added', $groupValues['status']);
      $this->assertContains($groupValues['group_id'], $expectedGroups);

    }
  }

  /**
   * Test that we handle cache entries without clashes.
   */
  public function testMergeCaches() {
    $contactID = $this->individualCreate();
    $contact2ID = $this->individualCreate();
    $groupID = $this->groupCreate();
    $this->callAPISuccess('GroupContact', 'create', ['group_id' => $groupID, 'contact_id' => $contactID]);
    $this->callAPISuccess('GroupContact', 'create', ['group_id' => $groupID, 'contact_id' => $contact2ID]);
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_group_contact_cache(group_id, contact_id) VALUES
      ($groupID, $contactID),
      ($groupID, $contact2ID)
    ");
    $this->callAPISuccess('Job', 'process_batch_merge', ['mode' => 'safe']);
  }

  /**
   * Test that we handle cache entries without clashes.
   */
  public function testMergeSharedActivity() {
    $contactID = $this->individualCreate();
    $contact2ID = $this->individualCreate();
    $activityID = $this->activityCreate(['target_contact_id' => [$contactID, $contact2ID]]);
    $this->callAPISuccess('Job', 'process_batch_merge', ['mode' => 'safe']);
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergesAddresses($dataSet) {
    $contactID1 = $this->individualCreate();
    $contactID2 = $this->individualCreate();
    foreach ($dataSet['contact_1'] as $address) {
      $this->callAPISuccess($dataSet['entity'], 'create', array_merge(['contact_id' => $contactID1], $address));
    }
    foreach ($dataSet['contact_2'] as $address) {
      $this->callAPISuccess($dataSet['entity'], 'create', array_merge(['contact_id' => $contactID2], $address));
    }

    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['mode' => 'safe']);
    $this->assertCount(1, $result['values']['merged']);
    $addresses = $this->callAPISuccess($dataSet['entity'], 'get', ['contact_id' => $contactID1, 'sequential' => 1]);
    $this->assertEquals(count($dataSet['expected']), $addresses['count'], 'Did not get the expected result for ' . $dataSet['entity'] . (!empty($dataSet['description']) ? " on dataset {$dataSet['description']}" : ''));
    $locationTypes = $this->callAPISuccess($dataSet['entity'], 'getoptions', ['field' => 'location_type_id']);
    foreach ($dataSet['expected'] as $index => $expectedAddress) {
      foreach ($expectedAddress as $key => $value) {
        if ($key === 'location_type_id') {
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergesAddressesHook($dataSet) {
    $contactID1 = $this->individualCreate();
    $contactID2 = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $contactID1, 'receive_date' => '2010-01-01', 'invoice_id' => 1, 'trxn_id' => 1]);
    $this->contributionCreate(['contact_id' => $contactID2, 'receive_date' => '2012-01-01', 'invoice_id' => 2, 'trxn_id' => 2]);
    foreach ($dataSet['contact_1'] as $address) {
      $this->callAPISuccess($dataSet['entity'], 'create', array_merge(['contact_id' => $contactID1], $address));
    }
    foreach ($dataSet['contact_2'] as $address) {
      $this->callAPISuccess($dataSet['entity'], 'create', array_merge(['contact_id' => $contactID2], $address));
    }
    $this->hookClass->setHook('civicrm_alterLocationMergeData', [$this, 'hookMostRecentDonor']);

    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['mode' => 'safe']);
    $this->assertCount(1, $result['values']['merged']);
    $addresses = $this->callAPISuccess($dataSet['entity'], 'get', ['contact_id' => $contactID1, 'sequential' => 1]);
    $this->assertEquals(count($dataSet['expected_hook']), $addresses['count']);
    $locationTypes = $this->callAPISuccess($dataSet['entity'], 'getoptions', ['field' => 'location_type_id']);
    foreach ($dataSet['expected_hook'] as $index => $expectedAddress) {
      foreach ($expectedAddress as $key => $value) {
        if ($key === 'location_type_id') {
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergeWillNotMergeOrganizationToIndividual() {
    $individual = $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'organization_name' => 'Anon',
      'email' => 'anonymous@hacker.com',
    ]);
    $organization = $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Organization',
      'organization_name' => 'Anon',
      'email' => 'anonymous@hacker.com',
    ]);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['mode' => 'aggressive']);
    $this->assertCount(0, $result['values']['skipped']);
    $this->assertCount(0, $result['values']['merged']);
    $this->callAPISuccessGetSingle('Contact', ['id' => $individual['id']]);
    $this->callAPISuccessGetSingle('Contact', ['id' => $organization['id']]);

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
   * @throws \CRM_Core_Exception
   */
  public function hookMostRecentDonor(&$blocksDAO, $mainId, $otherId, $migrationInfo) {

    $lastDonorID = $this->callAPISuccessGetValue('Contribution', [
      'return' => 'contact_id',
      'contact_id' => ['IN' => [$mainId, $otherId]],
      'options' => ['sort' => 'receive_date DESC', 'limit' => 1],
    ]);
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
    $address1 = ['street_address' => 'Buckingham Palace', 'city' => 'London'];
    $address2 = ['street_address' => 'The Doghouse', 'supplemental_address_1' => 'under the blanket'];
    $data = $this->getMergeLocations($address1, $address2, 'Address');
    $data = array_merge($data, $this->getMergeLocations(['phone' => '12345', 'phone_type_id' => 1], ['phone' => '678910', 'phone_type_id' => 1], 'Phone'));
    $data = array_merge($data, $this->getMergeLocations(['phone' => '12345'], ['phone' => '678910'], 'Phone'));
    $data = array_merge($data, $this->getMergeLocations(['email' => 'mini@me.com'], ['email' => 'mini@me.org'], 'Email', [
      [
        'email' => 'anthony_anderson@civicrm.org',
        'location_type_id' => 'Home',
      ],
    ]));
    return $data;

  }

  /**
   * Test weird characters don't mess with merge & cause a fatal.
   *
   * @throws \CRM_Core_Exception
   */
  public function testNoErrorOnOdd() {
    $this->individualCreate();
    $this->individualCreate(['first_name' => 'Gerrit%0a%2e%0a']);
    $this->callAPISuccess('Job', 'process_batch_merge', []);

    $this->individualCreate();
    $this->individualCreate(['first_name' => '[foo\\bar\'baz']);
    $this->callAPISuccess('Job', 'process_batch_merge', []);
    $this->callAPISuccessGetSingle('Contact', ['first_name' => '[foo\\bar\'baz']);
  }

  /**
   * Test the batch merge does not create duplicate emails.
   *
   * Test CRM-18546, a 4.7 regression whereby a merged contact gets duplicate emails.
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergeEmailHandling() {
    for ($x = 0; $x <= 4; $x++) {
      $id = $this->individualCreate(['email' => 'batman@gotham.met']);
    }
    $result = $this->callAPISuccess('Job', 'process_batch_merge', []);
    $this->assertCount(4, $result['values']['merged']);
    $this->callAPISuccessGetCount('Contact', ['email' => 'batman@gotham.met'], 1);
    $contacts = $this->callAPISuccess('Contact', 'get', ['is_deleted' => 0]);
    $deletedContacts = $this->callAPISuccess('Contact', 'get', ['is_deleted' => 1]);
    $this->callAPISuccessGetCount('Email', [
      'email' => 'batman@gotham.met',
      'contact_id' => ['IN' => array_keys($contacts['values'])],
    ], 1);
    $this->callAPISuccessGetCount('Email', [
      'email' => 'batman@gotham.met',
      'contact_id' => ['IN' => array_keys($deletedContacts['values'])],
    ], 4);
  }

  /**
   * Test the batch merge respects email "on hold".
   *
   * Test CRM-19148, Batch merge - Email on hold data lost when there is a conflict.
   *
   * @dataProvider getOnHoldSets
   *
   * @param bool $onHold1
   * @param bool $onHold2
   * @param bool $merge
   * @param string $conflictText
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergeEmailOnHold($onHold1, $onHold2, $merge, $conflictText) {
    $this->individualCreate([
      'api.email.create' => [
        'email' => 'batman@gotham.met',
        'location_type_id' => 'Work',
        'is_primary' => 1,
        'on_hold' => $onHold1,
      ],
    ]);
    $this->individualCreate([
      'api.email.create' => [
        'email' => 'batman@gotham.met',
        'location_type_id' => 'Work',
        'is_primary' => 1,
        'on_hold' => $onHold2,
      ],
    ]);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', []);
    $this->assertCount($merge, $result['values']['merged']);
    if ($conflictText) {
      $defaultRuleGroupID = $this->callAPISuccessGetValue('RuleGroup', [
        'contact_type' => 'Individual',
        'used' => 'Unsupervised',
        'return' => 'id',
        'options' => ['limit' => 1],
      ]);

      $duplicates = $this->callAPISuccess('Dedupe', 'getduplicates', ['rule_group_id' => $defaultRuleGroupID]);
      $this->assertEquals($conflictText, $duplicates['values'][0]['conflicts']);
    }
  }

  /**
   * Data provider for testBatchMergeEmailOnHold: combinations of on_hold & expected outcomes.
   */
  public function getOnHoldSets() {
    // Each row specifies: contact 1 on_hold, contact 2 on_hold, merge? (0 or 1),
    return [
      [0, 0, 1, NULL],
      [0, 1, 0, "Email 2 (Work): 'batman@gotham.met' vs. 'batman@gotham.met\n(On Hold)'"],
      [1, 0, 0, "Email 2 (Work): 'batman@gotham.met\n(On Hold)' vs. 'batman@gotham.met'"],
      [1, 1, 1, NULL],
    ];
  }

  /**
   * Test the batch merge does not fatal on an empty rule.
   *
   * @dataProvider getRuleSets
   *
   * @param string $contactType
   * @param string $used
   * @param string $name
   * @param bool $isReserved
   * @param int $threshold
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergeEmptyRule($contactType, $used, $name, $isReserved, $threshold) {
    $ruleGroup = $this->callAPISuccess('RuleGroup', 'create', [
      'contact_type' => $contactType,
      'threshold' => $threshold,
      'used' => $used,
      'name' => $name,
      'is_reserved' => $isReserved,
    ]);
    $this->callAPISuccess('Job', 'process_batch_merge', ['rule_group_id' => $ruleGroup['id']]);
    $this->callAPISuccess('RuleGroup', 'delete', ['id' => $ruleGroup['id']]);
  }

  /**
   * Get the various rule combinations.
   */
  public function getRuleSets() {
    $contactTypes = ['Individual', 'Organization', 'Household'];
    $useds = ['Unsupervised', 'General', 'Supervised'];
    $ruleGroups = [];
    foreach ($contactTypes as $contactType) {
      foreach ($useds as $used) {
        $ruleGroups[] = [$contactType, $used, 'Bob', FALSE, 0];
        $ruleGroups[] = [$contactType, $used, 'Bob', FALSE, 10];
        $ruleGroups[] = [$contactType, $used, 'Bob', TRUE, 10];
        $ruleGroups[] = [$contactType, $used, $contactType . $used, FALSE, 10];
        $ruleGroups[] = [$contactType, $used, $contactType . $used, TRUE, 10];
      }
    }
    return $ruleGroups;
  }

  /**
   * Test the batch merge does not create duplicate emails.
   *
   * Test CRM-18546, a 4.7 regression whereby a merged contact gets duplicate emails.
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergeMatchingAddress() {
    for ($x = 0; $x <= 2; $x++) {
      $this->individualCreate([
        'api.address.create' => [
          'location_type_id' => 'Home',
          'street_address' => 'Appt 115, The Batcave',
          'city' => 'Gotham',
          'postal_code' => 'Nananananana',
        ],
      ]);
    }
    // Different location type, still merge, identical.
    $this->individualCreate([
      'api.address.create' => [
        'location_type_id' => 'Main',
        'street_address' => 'Appt 115, The Batcave',
        'city' => 'Gotham',
        'postal_code' => 'Nananananana',
      ],
    ]);

    $this->individualCreate([
      'api.address.create' => [
        'location_type_id' => 'Home',
        'street_address' => 'Appt 115, The Batcave',
        'city' => 'Gotham',
        'postal_code' => 'Batman',
      ],
    ]);

    $result = $this->callAPISuccess('Job', 'process_batch_merge', []);
    $this->assertEquals(3, count($result['values']['merged']));
    $this->assertEquals(1, count($result['values']['skipped']));
    $this->callAPISuccessGetCount('Contact', ['street_address' => 'Appt 115, The Batcave'], 2);
    $contacts = $this->callAPISuccess('Contact', 'get', ['is_deleted' => 0]);
    $deletedContacts = $this->callAPISuccess('Contact', 'get', ['is_deleted' => 1]);
    $this->callAPISuccessGetCount('Address', [
      'street_address' => 'Appt 115, The Batcave',
      'contact_id' => ['IN' => array_keys($contacts['values'])],
    ], 3);

    $this->callAPISuccessGetCount('Address', [
      'street_address' => 'Appt 115, The Batcave',
      'contact_id' => ['IN' => array_keys($deletedContacts['values'])],
    ], 2);
  }

  /**
   * Test the batch merge by id range.
   *
   * We have 2 sets of 5 matches & set the merge only to merge the lower set.
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergeIDRange() {
    for ($x = 0; $x <= 4; $x++) {
      $id = $this->individualCreate(['email' => 'batman@gotham.met']);
    }
    for ($x = 0; $x <= 4; $x++) {
      $this->individualCreate(['email' => 'robin@gotham.met']);
    }
    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['criteria' => ['contact' => ['id' => ['<' => $id]]]]);
    $this->assertEquals(4, count($result['values']['merged']));
    $this->callAPISuccessGetCount('Contact', ['email' => 'batman@gotham.met'], 1);
    $this->callAPISuccessGetCount('Contact', ['email' => 'robin@gotham.met'], 5);
    $contacts = $this->callAPISuccess('Contact', 'get', ['is_deleted' => 0]);
    $deletedContacts = $this->callAPISuccess('Contact', 'get', ['is_deleted' => 0]);
    $this->callAPISuccessGetCount('Email', [
      'email' => 'batman@gotham.met',
      'contact_id' => ['IN' => array_keys($contacts['values'])],
    ], 1);
    $this->callAPISuccessGetCount('Email', [
      'email' => 'batman@gotham.met',
      'contact_id' => ['IN' => array_keys($deletedContacts['values'])],
    ], 1);
    $this->callAPISuccessGetCount('Email', [
      'email' => 'robin@gotham.met',
      'contact_id' => ['IN' => array_keys($contacts['values'])],
    ], 5);

  }

  /**
   * Test the batch merge copes with view only custom data field.
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergeCustomDataViewOnlyField() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'edit my contact'];
    $mouseParams = ['first_name' => 'Mickey', 'last_name' => 'Mouse', 'email' => 'tha_mouse@mouse.com'];
    $this->individualCreate($mouseParams);

    $customGroup = $this->customGroupCreate();
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id'], 'is_view' => 1]);
    $this->individualCreate(array_merge($mouseParams, ['custom_' . $customField['id'] => 'blah']));

    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['check_permissions' => 0, 'mode' => 'safe']);
    $this->assertEquals(1, count($result['values']['merged']));
    $mouseParams['return'] = 'custom_' . $customField['id'];
    $mouse = $this->callAPISuccess('Contact', 'getsingle', $mouseParams);
    $this->assertEquals('blah', $mouse['custom_' . $customField['id']]);

    $this->customFieldDelete($customField['id']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Test the batch merge retains 0 as a valid custom field value.
   *
   * Note that we set 0 on 2 fields with one on each contact to ensure that
   * both merged & mergee fields are respected.
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergeCustomDataZeroValueField() {
    $customGroup = $this->customGroupCreate();
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id'], 'default_value' => NULL]);

    $mouseParams = ['first_name' => 'Mickey', 'last_name' => 'Mouse', 'email' => 'tha_mouse@mouse.com'];
    $this->individualCreate(array_merge($mouseParams, ['custom_' . $customField['id'] => '']));
    $this->individualCreate(array_merge($mouseParams, ['custom_' . $customField['id'] => 0]));

    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['check_permissions' => 0, 'mode' => 'safe']);
    $this->assertCount(1, $result['values']['merged']);
    $mouseParams['return'] = 'custom_' . $customField['id'];
    $mouse = $this->callAPISuccess('Contact', 'getsingle', $mouseParams);
    $this->assertEquals(0, $mouse['custom_' . $customField['id']]);

    $this->individualCreate(array_merge($mouseParams, ['custom_' . $customField['id'] => NULL]));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['check_permissions' => 0, 'mode' => 'safe']);
    $this->assertEquals(1, count($result['values']['merged']));
    $mouseParams['return'] = 'custom_' . $customField['id'];
    $mouse = $this->callAPISuccess('Contact', 'getsingle', $mouseParams);
    $this->assertEquals(0, $mouse['custom_' . $customField['id']]);

    $this->customFieldDelete($customField['id']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Test the batch merge treats 0 vs 1 as a conflict.
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergeCustomDataZeroValueFieldWithConflict() {
    $customGroup = $this->customGroupCreate();
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id'], 'default_value' => NULL]);

    $mouseParams = ['first_name' => 'Mickey', 'last_name' => 'Mouse', 'email' => 'tha_mouse@mouse.com'];
    $mouse1 = $this->individualCreate(array_merge($mouseParams, ['custom_' . $customField['id'] => 0]));
    $mouse2 = $this->individualCreate(array_merge($mouseParams, ['custom_' . $customField['id'] => 1]));

    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['check_permissions' => 0, 'mode' => 'safe']);
    $this->assertCount(0, $result['values']['merged']);

    // Reverse which mouse has the zero to test we still get a conflict.
    $this->individualCreate(array_merge($mouseParams, ['id' => $mouse1, 'custom_' . $customField['id'] => 1]));
    $this->individualCreate(array_merge($mouseParams, ['id' => $mouse2, 'custom_' . $customField['id'] => 0]));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['check_permissions' => 0, 'mode' => 'safe']);
    $this->assertEquals(0, count($result['values']['merged']));

    $this->customFieldDelete($customField['id']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Test the batch merge function actually works!
   *
   * @dataProvider getMergeSets
   *
   * @param array $dataSet
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergeWorksCheckPermissionsTrue($dataSet) {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'administer CiviCRM', 'merge duplicate contacts', 'force merge duplicate contacts'];
    foreach ($dataSet['contacts'] as $params) {
      $this->callAPISuccess('Contact', 'create', $params);
    }

    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['check_permissions' => 1, 'mode' => $dataSet['mode']]);
    $this->assertCount(0, $result['values']['merged'], 'User does not have permission to any contacts, so no merging');
    $this->assertCount(0, $result['values']['skipped'], 'User does not have permission to any contacts, so no skip visibility');
  }

  /**
   * Test the batch merge function actually works!
   *
   * @dataProvider getMergeSets
   *
   * @param array $dataSet
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergeWorksCheckPermissionsFalse($dataSet) {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'edit my contact'];
    foreach ($dataSet['contacts'] as $params) {
      $this->callAPISuccess('Contact', 'create', $params);
    }

    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['check_permissions' => 0, 'mode' => $dataSet['mode']]);
    $this->assertEquals($dataSet['skipped'], count($result['values']['skipped']), 'Failed to skip the right number:' . $dataSet['skipped']);
    $this->assertEquals($dataSet['merged'], count($result['values']['merged']));
  }

  /**
   * Get data for batch merge.
   */
  public function getMergeSets() {
    $data = [
      [
        [
          'mode' => 'safe',
          'contacts' => [
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Address.create' => [
                'street_address' => 'big house',
                'location_type_id' => 'Home',
              ],
            ],
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
            ],
          ],
          'skipped' => 0,
          'merged' => 1,
          'expected' => [
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
            ],
          ],
        ],
      ],
      [
        [
          'mode' => 'safe',
          'contacts' => [
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Address.create' => [
                'street_address' => 'big house',
                'location_type_id' => 'Home',
              ],
            ],
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Address.create' => [
                'street_address' => 'bigger house',
                'location_type_id' => 'Home',
              ],
            ],
          ],
          'skipped' => 1,
          'merged' => 0,
          'expected' => [
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'street_address' => 'big house',
            ],
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'street_address' => 'bigger house',
            ],
          ],
        ],
      ],
      [
        [
          'mode' => 'safe',
          'contacts' => [
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Email.create' => [
                'email' => 'big.slog@work.co.nz',
                'location_type_id' => 'Work',
              ],
            ],
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Email.create' => [
                'email' => 'big.slog@work.com',
                'location_type_id' => 'Work',
              ],
            ],
          ],
          'skipped' => 1,
          'merged' => 0,
          'expected' => [
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
            ],
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
            ],
          ],
        ],
      ],
      [
        [
          'mode' => 'safe',
          'contacts' => [
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Phone.create' => [
                'phone' => '123456',
                'location_type_id' => 'Work',
              ],
            ],
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Phone.create' => [
                'phone' => '23456',
                'location_type_id' => 'Work',
              ],
            ],
          ],
          'skipped' => 1,
          'merged' => 0,
          'expected' => [
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
            ],
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
            ],
          ],
        ],
      ],
      [
        [
          'mode' => 'aggressive',
          'contacts' => [
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Address.create' => [
                'street_address' => 'big house',
                'location_type_id' => 'Home',
              ],
            ],
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Address.create' => [
                'street_address' => 'bigger house',
                'location_type_id' => 'Home',
              ],
            ],
          ],
          'skipped' => 0,
          'merged' => 1,
          'expected' => [
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'street_address' => 'big house',
            ],
          ],
        ],
      ],
      [
        [
          'mode' => 'safe',
          'contacts' => [
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Address.create' => [
                'street_address' => 'big house',
                'location_type_id' => 'Home',
              ],
            ],
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'is_deceased' => 1,
            ],
          ],
          'skipped' => 1,
          'merged' => 0,
          'expected' => [
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'is_deceased' => 0,
            ],
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'is_deceased' => 1,
            ],
          ],
        ],
      ],
      [
        [
          'mode' => 'safe',
          'contacts' => [
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
              'api.Address.create' => [
                'street_address' => 'big house',
                'location_type_id' => 'Home',
              ],
              'is_deceased' => 1,
            ],
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'contact_sub_type' => 'Student',
            ],
          ],
          'skipped' => 1,
          'merged' => 0,
          'expected' => [
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'is_deceased' => 1,
            ],
            [
              'first_name' => 'Michael',
              'last_name' => 'Jackson',
              'email' => 'michael@neverland.com',
              'contact_type' => 'Individual',
              'is_deceased' => 0,
            ],
          ],
        ],
      ],
    ];

    $conflictPairs = [
      'first_name' => 'Dianna',
      'last_name' => 'McAndrew',
      'middle_name' => 'Prancer',
      'birth_date' => '2015-12-25',
      'gender_id' => 'Female',
      'job_title' => 'Thriller',
    ];

    foreach ($conflictPairs as $key => $value) {
      $contactParams = [
        'first_name' => 'Michael',
        'middle_name' => 'Dancer',
        'last_name' => 'Jackson',
        'birth_date' => '2015-02-25',
        'email' => 'michael@neverland.com',
        'contact_type' => 'Individual',
        'contact_sub_type' => ['Student'],
        'gender_id' => 'Male',
        'job_title' => 'Entertainer',
      ];
      $contact2 = $contactParams;

      $contact2[$key] = $value;
      $data[$key . '_conflict'] = [
        [
          'mode' => 'safe',
          'contacts' => [$contactParams, $contact2],
          'skipped' => 1,
          'merged' => 0,
          'expected' => [$contactParams, $contact2],
        ],
      ];
    }

    return $data;
  }

  /**
   * Implements pre hook on relationships.
   *
   * @param string $op
   * @param string $objectName
   * @param int $id
   * @param array $params
   */
  public function hookPreRelationship($op, $objectName, $id, &$params) {
    if ($op === 'delete') {
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
   * @param array $additionalExpected
   *
   * @return array
   */
  public function getMergeLocations($locationParams1, $locationParams2, $entity, $additionalExpected = []) {
    return [
      [
        'matching_primary' => [
          'entity' => $entity,
          'contact_1' => [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
          ],
          'contact_2' => [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
          ],
          'expected' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
          ]),
          'expected_hook' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
          ]),
        ],
      ],
      [
        'matching_primary_reverse' => [
          'entity' => $entity,
          'contact_1' => [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
          ],
          'contact_2' => [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
          ],
          'expected' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
          ]),
          'expected_hook' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
          ]),
        ],
      ],
      [
        'only_one_has_address' => [
          'entity' => $entity,
          'contact_1' => [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
          ],
          'contact_2' => [],
          'expected' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
          ]),
          'expected_hook' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Main',
              // When dealing with email we don't have a clean slate - the existing
              // primary will be primary.
              'is_primary' => ($entity == 'Email' ? 0 : 1),
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
          ]),
        ],
      ],
      [
        'only_one_has_address_reverse' => [
          'description' => 'The destination contact does not have an address. secondary contact should be merged in.',
          'entity' => $entity,
          'contact_1' => [],
          'contact_2' => [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
          ],
          'expected' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Main',
              // When dealing with email we don't have a clean slate - the existing
              // primary will be primary.
              'is_primary' => ($entity == 'Email' ? 0 : 1),
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
          ]),
          'expected_hook' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
          ]),
        ],
      ],
      [
        'different_primaries_with_different_location_type' => [
          'description' => 'Primaries are different with different location. Keep both addresses. Set primary to be that of lower id',
          'entity' => $entity,
          'contact_1' => [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
          ],
          'contact_2' => [
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ], $locationParams2),
          ],
          'expected' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
          ]),
          'expected_hook' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 0,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ], $locationParams2),
          ]),
        ],
      ],
      [
        'different_primaries_with_different_location_type_reverse' => [
          'entity' => $entity,
          'contact_1' => [
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ], $locationParams2),
          ],
          'contact_2' => [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
          ],
          'expected' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ], $locationParams2),
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 0,
            ], $locationParams1),
          ]),
          'expected_hook' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
          ]),
        ],
      ],
      [
        'different_primaries_location_match_only_one_address' => [
          'entity' => $entity,
          'contact_1' => [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
          ],
          'contact_2' => [
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ], $locationParams2),

          ],
          'expected' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
          ]),
          'expected_hook' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 0,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ], $locationParams2),
          ]),
        ],
      ],
      [
        'different_primaries_location_match_only_one_address_reverse' => [
          'entity' => $entity,
          'contact_1' => [
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ], $locationParams2),
          ],
          'contact_2' => [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
          ],
          'expected' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ], $locationParams2),
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 0,
            ], $locationParams1),
          ]),
          'expected_hook' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams2),
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
          ]),
        ],
      ],
      [
        'same_primaries_different_location' => [
          'entity' => $entity,
          'contact_1' => [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
          ],
          'contact_2' => [
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ], $locationParams1),

          ],
          'expected' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 0,
            ], $locationParams1),
          ]),
          'expected_hook' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ], $locationParams1),
          ]),
        ],
      ],
      [
        'same_primaries_different_location_reverse' => [
          'entity' => $entity,
          'contact_1' => [
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ], $locationParams1),
          ],
          'contact_2' => [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
          ],
          'expected' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Work',
              'is_primary' => 1,
            ], $locationParams1),
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 0,
            ], $locationParams1),
          ]),
          'expected_hook' => array_merge($additionalExpected, [
            array_merge([
              'location_type_id' => 'Main',
              'is_primary' => 1,
            ], $locationParams1),
          ]),
        ],
      ],
    ];
  }

  /**
   * Test processing membership for deceased contacts.
   *
   * @throws \CRM_Core_Exception
   */
  public function testProcessMembershipDeceased() {
    $this->callAPISuccess('Job', 'process_membership', []);
    $deadManWalkingID = $this->individualCreate();
    $membershipID = $this->contactMembershipCreate(['contact_id' => $deadManWalkingID]);
    $this->callAPISuccess('Contact', 'create', ['id' => $deadManWalkingID, 'is_deceased' => 1]);
    $this->callAPISuccess('Job', 'process_membership', []);
    $membership = $this->callAPISuccessGetSingle('Membership', ['id' => $membershipID]);
    $deceasedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Deceased');
    $this->assertEquals($deceasedStatusId, $membership['status_id']);
  }

  /**
   * Test we get an error is deceased status is disabled.
   *
   * @throws \CRM_Core_Exception
   */
  public function testProcessMembershipNoDeceasedStatus() {
    $deceasedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Deceased');
    $this->callAPISuccess('MembershipStatus', 'create', ['is_active' => 0, 'id' => $deceasedStatusId]);
    CRM_Core_PseudoConstant::flush();

    $deadManWalkingID = $this->individualCreate();
    $this->contactMembershipCreate(['contact_id' => $deadManWalkingID]);
    $this->callAPISuccess('Contact', 'create', ['id' => $deadManWalkingID, 'is_deceased' => 1]);
    $this->callAPIFailure('Job', 'process_membership', []);

    $this->callAPISuccess('MembershipStatus', 'create', ['is_active' => 1, 'id' => $deceasedStatusId]);
  }

  /**
   * Test processing membership: check that status is updated when it should be
   * and left alone when it shouldn't.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testProcessMembershipUpdateStatus() {
    $this->ids['MembershipType'] = $this->membershipTypeCreate();

    // Create admin-only membership status and get all statuses.
    $this->callAPISuccess('membership_status', 'create', ['name' => 'Admin', 'is_admin' => 1])['id'];

    // Create membership with incorrect statuses for the given dates and also some (pending, cancelled, admin override) which should not be updated.
    $memberships = [
      [
        'start_date' => 'now',
        'end_date' => '+ 1 year',
        'initial_status' => 'Current',
        'expected_processed_status' => 'New',
      ],
      [
        'start_date' => '- 6 month',
        'end_date' => '+ 6 month',
        'initial_status' => 'New',
        'expected_processed_status' => 'Current',
      ],
      [
        'start_date' => '- 53 week',
        'end_date' => '-1 week',
        'initial_status' => 'Current',
        'expected_processed_status' => 'Grace',
      ],
      [
        'start_date' => '- 16 month',
        'end_date' => '- 4 month',
        'initial_status' => 'Grace',
        'expected_processed_status' => 'Expired',
      ],
      [
        'start_date' => 'now',
        'end_date' => '+ 1 year',
        'initial_status' => 'Pending',
        'expected_processed_status' => 'Pending',
      ],
      [
        'start_date' => '- 6 month',
        'end_date' => '+ 6 month',
        'initial_status' => 'Cancelled',
        'expected_processed_status' => 'Cancelled',
      ],
      [
        'start_date' => '- 16 month',
        'end_date' => '- 4 month',
        'initial_status' => 'Current',
        'is_override' => TRUE,
        'expected_processed_status' => 'Current',
      ],
      [
        // @todo this looks like it's covering something up. If we pass isAdminOverride it is the same as the line above. Without it the test fails.
        // this smells of something that should work (or someone thought should work & so put in a test) doesn't & test has been adjusted to cope.
        'start_date' => '- 16 month',
        'end_date' => '- 4 month',
        'initial_status' => 'Admin',
        'is_override' => TRUE,
        'expected_processed_status' => 'Admin',
      ],
    ];
    foreach ($memberships as $index => $membership) {
      $memberships[$index]['id'] = $this->createMembershipNeedingStatusProcessing($membership['start_date'], $membership['end_date'], $membership['initial_status'], $membership['is_override'] ?? FALSE);
    }

    /*
     * Create membership type with inheritence and check processing of secondary memberships.
     */
    $employerRelationshipId = $this->callAPISuccessGetValue('RelationshipType', [
      'return' => 'id',
      'name_b_a' => 'Employer Of',
    ]);
    // Create membership type: inherited through employment.
    $membershipOrgId = $this->organizationCreate();
    $params = [
      'name' => 'Corporate Membership',
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'member_of_contact_id' => $membershipOrgId,
      'domain_id' => 1,
      'financial_type_id' => 1,
      'relationship_type_id' => $employerRelationshipId,
      'relationship_direction' => 'b_a',
      'is_active' => 1,
    ];
    $membershipTypeId = $this->callAPISuccess('membership_type', 'create', $params)['id'];

    // Create employer and first employee
    $employerId = $this->organizationCreate([], 1);
    $memberContactId = $this->individualCreate(['employer_id' => $employerId], 0);

    // Create inherited membership with incorrect status but dates implying status Expired.
    $params = [
      'contact_id' => $employerId,
      'membership_type_id' => $membershipTypeId,
      'source' => 'Test suite',
      'join_date' => date('Y-m-d', strtotime('now - 16 month')),
      'start_date' => date('Y-m-d', strtotime('now - 16 month')),
      'end_date' => date('Y-m-d', strtotime('now - 4 month')),
      // Intentionally incorrect status.
      'status_id' => 'Grace',
      // Don't calculate status.
      'skipStatusCal' => 1,
    ];
    $organizationMembershipID = $this->contactMembershipCreate($params);

    // Check that the employee inherited the membership and status.
    $expiredInheritedRelationship = $this->callAPISuccessGetSingle('membership', [
      'contact_id' => $memberContactId,
      'membership_type_id' => $membershipTypeId,
    ]);
    $this->assertEquals($organizationMembershipID, $expiredInheritedRelationship['owner_membership_id']);
    $this->assertMembershipStatus('Grace', (int) $expiredInheritedRelationship['status_id']);

    // Reset static $relatedContactIds array in createRelatedMemberships(),
    // to avoid bug where inherited membership gets deleted.
    $var = TRUE;
    CRM_Member_BAO_Membership::createRelatedMemberships($var, $var, TRUE);
    // Check that after running process_membership job, statuses are correct.
    $this->callAPISuccess('Job', 'process_membership', []);

    foreach ($memberships as $expectation) {
      $membership = $this->callAPISuccessGetSingle('membership', ['id' => $expectation['id']]);
      $this->assertMembershipStatus($expectation['expected_processed_status'], (int) $membership['status_id']);
    }

    // Inherit Expired - should get updated.
    $membership = $this->callAPISuccess('membership', 'getsingle', ['id' => $expiredInheritedRelationship['id']]);
    $this->assertMembershipStatus('Expired', $membership['status_id']);
  }

  /**
   * Test procesing membership where is_override is set to 0 rather than NULL
   *
   * @throws \CRM_Core_Exception
   */
  public function testProcessMembershipIsOverrideNotNullNot1either() {
    $membershipTypeId = $this->membershipTypeCreate();

    // Create admin-only membership status and get all statuses.
    $result = $this->callAPISuccess('membership_status', 'create', ['name' => 'Admin', 'is_admin' => 1, 'sequential' => 1]);
    $membershipStatusIdAdmin = $result['values'][0]['id'];
    $memStatus = CRM_Member_PseudoConstant::membershipStatus();

    // Default params, which we'll expand on below.
    $params = [
      'membership_type_id' => $membershipTypeId,
      // Don't calculate status.
      'skipStatusCal' => 1,
      'source' => 'Test',
      'sequential' => 1,
    ];

    // Create membership with incorrect status but dates implying status Current.
    $params['contact_id'] = $this->individualCreate();
    $params['join_date'] = date('Y-m-d', strtotime('now - 6 month'));
    $params['start_date'] = date('Y-m-d', strtotime('now - 6 month'));
    $params['end_date'] = date('Y-m-d', strtotime('now + 6 month'));
    // Intentionally incorrect status.
    $params['status_id'] = 'New';
    $resultCurrent = $this->callAPISuccess('Membership', 'create', $params);
    // Ensure that is_override is set to 0 by doing through DB given API not seem to accept id
    CRM_Core_DAO::executeQuery("Update civicrm_membership SET is_override = 0 WHERE id = %1", [1 => [$resultCurrent['id'], 'Positive']]);
    $this->assertEquals(array_search('New', $memStatus, TRUE), $resultCurrent['values'][0]['status_id']);
    $jobResult = $this->callAPISuccess('Job', 'process_membership', []);
    $this->assertEquals('Processed 1 membership records. Updated 1 records.', $jobResult['values']);
    $this->assertEquals(array_search('Current', $memStatus, TRUE), $this->callAPISuccess('Membership', 'get', ['id' => $resultCurrent['id']])['values'][$resultCurrent['id']]['status_id']);
  }

  /**
   * @param string $expectedStatusName
   * @param int $actualStatusID
   */
  protected function assertMembershipStatus(string $expectedStatusName, int $actualStatusID) {
    $this->assertEquals($expectedStatusName, CRM_Core_PseudoConstant::getName('CRM_Member_BAO_Membership', 'status_id', $actualStatusID));
  }

  /**
   * @param string $startDate
   *   Date in strtotime format - e.g 'now', '+1 day'
   * @param string $endDate
   *   Date in strtotime format - e.g 'now', '+1 day'
   * @param string $status
   *   Status override
   * @param bool $isAdminOverride
   *   Is administratively overridden (if so the status is fixed).
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  protected function createMembershipNeedingStatusProcessing(string $startDate, string $endDate, string $status, bool $isAdminOverride = FALSE): int {
    $params = [
      'membership_type_id' => $this->ids['MembershipType'],
      // Don't calculate status.
      'skipStatusCal' => 1,
      'source' => 'Test',
      'sequential' => 1,
    ];
    $params['contact_id'] = $this->individualCreate();
    $params['join_date'] = date('Y-m-d', strtotime($startDate));
    $params['start_date'] = date('Y-m-d', strtotime($startDate));
    $params['end_date'] = date('Y-m-d', strtotime($endDate));
    $params['sequential'] = TRUE;
    $params['is_override'] = $isAdminOverride;
    // Intentionally incorrect status.
    $params['status_id'] = $status;
    $resultNew = $this->callAPISuccess('Membership', 'create', $params);
    $this->assertMembershipStatus($status, (int) $resultNew['values'][0]['status_id']);
    return (int) $resultNew['id'];
  }

  /**
   * Shared set up for SMS reminder tests.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  protected function setUpMembershipSMSReminders(): array {
    $membershipTypeID = $this->membershipTypeCreate();
    $this->membershipStatusCreate();
    $createTotal = 3;
    $groupID = $this->groupCreate(['name' => 'Texan drawlers', 'title' => 'a...']);
    for ($i = 1; $i <= $createTotal; $i++) {
      $contactID = $this->individualCreate();
      $this->callAPISuccess('Phone', 'create', [
        'contact_id' => $contactID,
        'phone' => '555 123 1234',
        'phone_type_id' => 'Mobile',
        'location_type_id' => 'Billing',
      ]);
      if ($i === 2) {
        $theChosenOneID = $contactID;
      }
      if ($i < 3) {
        $this->callAPISuccess('group_contact', 'create', [
          'contact_id' => $contactID,
          'status' => 'Added',
          'group_id' => $groupID,
        ]);
      }
      if ($i > 1) {
        $this->callAPISuccess('membership', 'create', [
          'contact_id' => $contactID,
          'membership_type_id' => $membershipTypeID,
          'join_date' => 'now',
          'start_date' => '+ 1 day',
        ]);
      }
    }
    $this->setupForSmsTests();
    $provider = civicrm_api3('SmsProvider', 'create', [
      'name' => 'CiviTestSMSProvider',
      'api_type' => '1',
      'username' => '1',
      'password' => '1',
      'api_url' => '1',
      'api_params' => 'a=1',
      'is_default' => '1',
      'is_active' => '1',
      'domain_id' => '1',
    ]);
    return [$membershipTypeID, $groupID, $theChosenOneID, $provider];
  }

  /**
   * Test that the mail_report job sends an email for 'print' format.
   *
   * We're not testing that the report itself is correct since in 'print'
   * format it's a little difficult to parse out, so we're just testing that
   * the email was sent and it more or less looks like an email we'd expect.
   */
  public function testMailReportForPrint() {
    $mut = new CiviMailUtils($this, TRUE);

    // avoid warnings
    if (empty($_SERVER['QUERY_STRING'])) {
      $_SERVER['QUERY_STRING'] = 'reset=1';
    }

    $this->callAPISuccess('job', 'mail_report', [
      'instanceId' => $this->report_instance['id'],
      'format' => 'print',
    ]);

    $message = $mut->getMostRecentEmail('ezc');

    $this->assertEquals('This is the email subject', $message->subject);
    $this->assertEquals('reportperson@example.com', $message->to[0]->email);

    $parts = $message->fetchParts(NULL, TRUE);
    $this->assertCount(1, $parts);
    $this->assertStringContainsString('test report', $parts[0]->text);

    $mut->clearMessages();
    $mut->stop();
  }

  /**
   * Test that the mail_report job sends an email for 'pdf' format.
   *
   * We're not testing that the report itself is correct since in 'pdf'
   * format it's a little difficult to parse out, so we're just testing that
   * the email was sent and it more or less looks like an email we'd expect.
   */
  public function testMailReportForPdf() {
    $mut = new CiviMailUtils($this, TRUE);

    // avoid warnings
    if (empty($_SERVER['QUERY_STRING'])) {
      $_SERVER['QUERY_STRING'] = 'reset=1';
    }

    $this->callAPISuccess('job', 'mail_report', [
      'instanceId' => $this->report_instance['id'],
      'format' => 'pdf',
    ]);

    $message = $mut->getMostRecentEmail('ezc');

    $this->assertEquals('This is the email subject', $message->subject);
    $this->assertEquals('reportperson@example.com', $message->to[0]->email);

    $parts = $message->fetchParts(NULL, TRUE);
    $this->assertCount(2, $parts);
    $this->assertStringContainsString('<title>CiviCRM Report</title>', $parts[0]->text);
    $this->assertEquals(ezcMailFilePart::CONTENT_TYPE_APPLICATION, $parts[1]->contentType);
    $this->assertEquals('pdf', $parts[1]->mimeType);
    $this->assertEquals(ezcMailFilePart::DISPLAY_ATTACHMENT, $parts[1]->dispositionType);
    $this->assertGreaterThan(0, filesize($parts[1]->fileName));

    $mut->clearMessages();
    $mut->stop();
  }

  /**
   * Test that the mail_report job sends an email for 'csv' format.
   *
   * As with the print and pdf we're not super-concerned about report
   * functionality itself - we're more concerned with the mailing part,
   * but since it's csv we can easily check the output.
   */
  public function testMailReportForCsv() {
    // Create many contacts, in particular so that the report would be more
    // than a one-pager.
    for ($i = 0; $i < 110; $i++) {
      $this->individualCreate([], $i, TRUE);
    }

    $mut = new CiviMailUtils($this, TRUE);

    // avoid warnings
    if (empty($_SERVER['QUERY_STRING'])) {
      $_SERVER['QUERY_STRING'] = 'reset=1';
    }

    $this->callAPISuccess('job', 'mail_report', [
      'instanceId' => $this->report_instance['id'],
      'format' => 'csv',
    ]);

    $message = $mut->getMostRecentEmail('ezc');

    $this->assertEquals('This is the email subject', $message->subject);
    $this->assertEquals('reportperson@example.com', $message->to[0]->email);

    $parts = $message->fetchParts(NULL, TRUE);
    $this->assertCount(2, $parts);
    $this->assertStringContainsString('<title>CiviCRM Report</title>', $parts[0]->text);
    $this->assertEquals('csv', $parts[1]->subType);

    // Pull all the contacts to get our expected output.
    $contacts = $this->callAPISuccess('Contact', 'get', [
      'return' => 'sort_name',
      'options' => [
        'limit' => 0,
        'sort' => 'sort_name',
      ],
    ]);
    $rows = [];
    foreach ($contacts['values'] as $contact) {
      $rows[] = ['civicrm_contact_sort_name' => $contact['sort_name']];
    }
    // need this for makeCsv()
    $fakeForm = new CRM_Report_Form();
    $fakeForm->_columnHeaders = [
      'civicrm_contact_sort_name' => [
        'title' => 'Contact Name',
        'type' => 2,
      ],
    ];

    $this->assertEquals(
      CRM_Report_Utils_Report::makeCsv($fakeForm, $rows),
      $parts[1]->text
    );

    $mut->clearMessages();
    $mut->stop();
  }

  /**
   * Helper to create a report instance of the contact summary report.
   */
  private function createReportInstance() {
    return $this->callAPISuccess('ReportInstance', 'create', [
      'report_id' => 'contact/summary',
      'title' => 'test report',
      'form_values' => [
        serialize([
          'fields' => [
            'sort_name' => '1',
            'street_address' => '1',
            'city' => '1',
            'country_id' => '1',
          ],
          'sort_name_op' => 'has',
          'sort_name_value' => '',
          'source_op' => 'has',
          'source_value' => '',
          'id_min' => '',
          'id_max' => '',
          'id_op' => 'lte',
          'id_value' => '',
          'country_id_op' => 'in',
          'country_id_value' => [],
          'state_province_id_op' => 'in',
          'state_province_id_value' => [],
          'gid_op' => 'in',
          'gid_value' => [],
          'tagid_op' => 'in',
          'tagid_value' => [],
          'description' => 'Provides a list of address and telephone information for constituent records in your system.',
          'email_subject' => 'This is the email subject',
          'email_to' => 'reportperson@example.com',
          'email_cc' => '',
          'permission' => 'view all contacts',
          'groups' => '',
          'domain_id' => 1,
        ]),
      ],
      // Email params need to be repeated outside form_values for some reason
      'email_subject' => 'This is the email subject',
      'email_to' => 'reportperson@example.com',
    ]);
  }

}
