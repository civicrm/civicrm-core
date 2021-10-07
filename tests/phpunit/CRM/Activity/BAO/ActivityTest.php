<?php

use Civi\Api4\Activity;
use Civi\Api4\OptionValue;

/**
 * Class CRM_Activity_BAO_ActivityTest
 * @group headless
 */
class CRM_Activity_BAO_ActivityTest extends CiviUnitTestCase {

  private $allowedContactsACL = [];

  private $loggedInUserId = NULL;

  private $someContacts = [];

  /**
   * Set up for test.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function setUp():void {
    parent::setUp();
    $this->prepareForACLs();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['view all contacts', 'access CiviCRM'];
    $this->setupForSmsTests();
  }

  /**
   * Clean up after tests.
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function tearDown(): void {
    $tablesToTruncate = [
      'civicrm_activity',
      'civicrm_activity_contact',
      'civicrm_uf_match',
      'civicrm_campaign',
      'civicrm_email',
      'civicrm_file',
      'civicrm_entity_file',
    ];
    $this->quickCleanup($tablesToTruncate);
    $this->cleanUpAfterACLs();
    OptionValue::delete(FALSE)->addWhere('name', '=', 'CiviTestSMSProvider')->execute();
    parent::tearDown();
  }

  /**
   * Test case for create() method.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreate() {
    $contactId = $this->individualCreate();

    $params = [
      'source_contact_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
    ];

    CRM_Activity_BAO_Activity::create($params);

    $activityId = $this->assertDBNotNull('CRM_Activity_DAO_Activity', 'Scheduling Meeting', 'id',
      'subject', 'Database check for created activity.'
    );

    // Now call create() to modify an existing Activity.
    $params = [
      'id' => $activityId,
      'source_contact_id' => $contactId,
      'subject' => 'Scheduling Interview',
      'activity_type_id' => 3,
    ];

    CRM_Activity_BAO_Activity::create($params);

    $activityTypeId = $this->assertDBNotNull('CRM_Activity_DAO_Activity', 'Scheduling Interview',
      'activity_type_id',
      'subject', 'Database check on updated activity record.'
    );
    $this->assertEquals($activityTypeId, 3, 'Verify activity type id is 3.');

    $this->contactDelete($contactId);
  }

  /**
   * Test case for getContactActivity() method.
   *
   * getContactActivity() method get activities detail for given target contact
   * id.
   */
  public function testGetContactActivity(): void {
    $contactId = $this->individualCreate();
    $params = [
      'first_name' => 'liz',
      'last_name' => 'hurleey',
    ];
    $targetContactId = $this->individualCreate($params);

    $params = [
      'source_contact_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
      'target_contact_id' => [$targetContactId],
      'activity_date_time' => date('Ymd'),
    ];

    $this->callAPISuccess('Activity', 'create', $params);

    $activityId = $this->assertDBNotNull('CRM_Activity_DAO_Activity', 'Scheduling Meeting',
      'id',
      'subject', 'Database check for created activity.'
    );

    // @todo - remove this deprecated functions
    $activities = CRM_Activity_BAO_Activity::getContactActivity($targetContactId);

    $this->assertEquals($activities[$activityId]['subject'], 'Scheduling Meeting', 'Verify activity subject is correct.');

    $this->contactDelete($contactId);
    $this->contactDelete($targetContactId);
  }

  /**
   * Test case for retrieve() method.
   *
   * Retrieve($params, $defaults) method return activity detail for given params
   *                              and set defaults.
   */
  public function testRetrieve(): void {
    $contactId = $this->individualCreate();
    $params = [
      'first_name' => 'liz',
      'last_name' => 'hurleey',
    ];
    $targetContactId = $this->individualCreate($params);

    $params = [
      'source_contact_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
      'target_contact_id' => [$targetContactId],
      'activity_date_time' => date('Ymd'),
    ];

    CRM_Activity_BAO_Activity::create($params);

    $this->assertDBNotNull('CRM_Activity_DAO_Activity', 'Scheduling Meeting', 'id',
      'subject', 'Database check for created activity.'
    );

    $this->assertDBNotNull('CRM_Activity_DAO_ActivityContact', $targetContactId,
      'id', 'contact_id',
      'Database check for created activity target.'
    );

    $defaults = [];
    $activity = CRM_Activity_BAO_Activity::retrieve($params, $defaults);

    $this->assertEquals($activity->subject, 'Scheduling Meeting', 'Verify activity subject is correct.');
    $this->assertEquals($activity->activity_type_id, 2, 'Verify activity type id is correct.');
    $this->assertEquals($defaults['source_contact_id'], $contactId, 'Verify source contact id is correct.');

    $this->assertEquals($defaults['subject'], 'Scheduling Meeting', 'Verify activity subject is correct.');
    $this->assertEquals($defaults['activity_type_id'], 2, 'Verify activity type id is correct.');

    $this->assertEquals($defaults['target_contact'][0], $targetContactId, 'Verify target contact id is correct.');

    $this->contactDelete($contactId);
    $this->contactDelete($targetContactId);
  }

  /**
   * Test Assigning a target contact but then the logged in user cannot see the contact
   */
  public function testTargetContactNotavaliable() {
    $contactId = $this->individualCreate();
    $params = [
      'first_name' => 'liz',
      'last_name' => 'hurleey',
    ];
    $targetContactId = $this->individualCreate($params);

    $params = [
      'source_contact_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
      'target_contact_id' => [$targetContactId],
      'activity_date_time' => date('Ymd'),
    ];

    CRM_Activity_BAO_Activity::create($params);

    // set custom hook
    $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'hook_civicrm_aclWhereClause']);

    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];

    $this->allowedContactsACL = [$contactId];

    // get logged in user
    $user_id = $this->createLoggedInUser();
    $activityGetParams = CRM_Core_Page_AJAX::defaultSortAndPagerParams();
    $activityGetParams += ['contact_id' => $contactId];
    $activities = CRM_Activity_BAO_Activity::getContactActivitySelector($activityGetParams);
    // Aseert that we have sensible data to display in the contact tab
    $this->assertEquals('Anderson, Anthony', $activities['data'][0]['source_contact_name']);
    // Note that becasue there is a target contact but it is not accessable the output is an empty string not n/a
    $this->assertEquals('', $activities['data'][0]['target_contact_name']);
    // verify that doing the underlying query shows we get a target contact_id
    $this->assertEquals(1, CRM_Activity_BAO_Activity::getActivities(['contact_id' => $contactId])[1]['target_contact_count']);
    $this->cleanUpAfterACLs();
  }

  /**
   * Check for errors when viewing a contact's activity tab when there
   * is an activity that doesn't have a target (With Contact).
   */
  public function testActivitySelectorNoTargets() {
    $contact_id = $this->individualCreate([], 0, TRUE);
    $activity = $this->callAPISuccess('activity', 'create', [
      'source_contact_id' => $contact_id,
      'activity_type_id' => 'Meeting',
      'subject' => 'Lonely Meeting',
      'details' => 'Here at this meeting all by myself and no other contacts.',
    ]);
    $input = [
      '_raw_values' => [],
      'offset' => 0,
      'rp' => 25,
      'page' => 1,
      'context' => 'activity',
      'contact_id' => $contact_id,
    ];
    $output = CRM_Activity_BAO_Activity::getContactActivitySelector($input);
    $this->assertEquals($activity['id'], $output['data'][0]['DT_RowId']);
    $this->assertEquals('<em>n/a</em>', $output['data'][0]['target_contact_name']);
    $this->assertEquals('Lonely Meeting', $output['data'][0]['subject']);

    $this->callAPISuccess('activity', 'delete', ['id' => $activity['id']]);
    $this->callAPISuccess('contact', 'delete', ['id' => $contact_id]);
  }

  /**
   * Test case for deleteActivity() method.
   *
   * deleteActivity($params) method deletes activity for given params.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testDeleteActivity() {
    $contactId = $this->individualCreate();
    $params = [
      'first_name' => 'liz',
      'last_name' => 'hurleey',
    ];
    $targetContactId = $this->individualCreate($params);

    $params = [
      'source_contact_id' => $contactId,
      'source_record_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
      'target_contact_id' => [$targetContactId],
      'activity_date_time' => date('Ymd'),
    ];

    CRM_Activity_BAO_Activity::create($params);

    $this->assertDBNotNull('CRM_Activity_DAO_Activity', 'Scheduling Meeting', 'id',
      'subject', 'Database check for created activity.'
    );

    $this->assertDBNotNull('CRM_Activity_DAO_ActivityContact', $targetContactId,
      'id', 'contact_id',
      'Database check for created activity target.'
    );

    $paramOptions = ['0))+and+0+--+-f', ['0))+and+0+--+-f']];
    $paramField = ['source_record_id', 'activity_type_id'];
    foreach ($paramField as $field) {
      foreach ($paramOptions as $paramOption) {
        $params = [
          $field => $paramOption,
        ];
        try {
          CRM_Activity_BAO_Activity::deleteActivity($params);
        }
        catch (Exception $e) {
          if ($e->getMessage() === 'DB Error: syntax error') {
            $this->fail('Delete Activity function did not validate field: ' . $field);
          }
        }
      }
    }
    $params = [
      'source_contact_id' => $contactId,
      'source_record_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
    ];

    CRM_Activity_BAO_Activity::deleteActivity($params);

    $this->assertDBNull('CRM_Activity_DAO_Activity', 'Scheduling Meeting', 'id',
      'subject', 'Database check for deleted activity.'
    );
    $this->contactDelete($contactId);
    $this->contactDelete($targetContactId);
  }

  /**
   * Test case for deleteActivityContact() method.
   */
  public function testDeleteActivityTarget() {
    $contactId = $this->individualCreate();
    $params = [
      'first_name' => 'liz',
      'last_name' => 'hurleey',
    ];
    $targetContactId = $this->individualCreate($params);

    $params = [
      'source_contact_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
      'target_contact_id' => [$targetContactId],
      'activity_date_time' => date('Ymd'),
    ];

    CRM_Activity_BAO_Activity::create($params);

    $activityId = $this->assertDBNotNull('CRM_Activity_DAO_Activity', 'Scheduling Meeting', 'id',
      'subject', 'Database check for created activity.'
    );

    $this->assertDBNotNull('CRM_Activity_DAO_ActivityContact', $targetContactId,
      'id', 'contact_id',
      'Database check for created activity target.'
    );

    CRM_Activity_BAO_Activity::deleteActivityContact($activityId, 3);

    $this->assertDBNull('CRM_Activity_DAO_ActivityContact', $targetContactId, 'id',
      'contact_id', 'Database check for deleted activity target.'
    );

    $this->contactDelete($contactId);
    $this->contactDelete($targetContactId);
  }

  /**
   * Test case for deleteActivityAssignment() method.
   *
   * deleteActivityAssignment($activityId) method deletes activity assignment
   * for given activity id.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testDeleteActivityAssignment(): void {
    $contactId = $this->individualCreate();
    $params = [
      'first_name' => 'liz',
      'last_name' => 'hurleey',
    ];
    $assigneeContactId = $this->individualCreate($params);

    $params = [
      'source_contact_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
      'assignee_contact_id' => [$assigneeContactId],
      'activity_date_time' => date('Ymd'),
    ];

    CRM_Activity_BAO_Activity::create($params);

    $activityId = $this->assertDBNotNull('CRM_Activity_DAO_Activity', 'Scheduling Meeting', 'id',
      'subject', 'Database check for created activity.'
    );

    $this->assertDBNotNull('CRM_Activity_DAO_ActivityContact',
      $assigneeContactId, 'id', 'contact_id',
      'Database check for created activity assignment.'
    );

    CRM_Activity_BAO_Activity::deleteActivityContact($activityId, 1);

    $this->assertDBNull('CRM_Activity_DAO_ActivityContact', $assigneeContactId, 'id',
      'contact_id', 'Database check for deleted activity assignment.'
    );

    $this->contactDelete($contactId);
    $this->contactDelete($assigneeContactId);
  }

  /**
   * Test getActivities BAO method for getting count.
   *
   */
  public function testGetActivitiesCountForAdminDashboard() {
    // Reset to default
    $this->setShowCaseActivitiesInCore(FALSE);
    $this->setUpForActivityDashboardTests();
    $this->addCaseWithActivity();
    CRM_Core_Config::singleton()->userPermissionClass->permissions[] = 'access all cases and activities';

    $activityCount = CRM_Activity_BAO_Activity::getActivitiesCount($this->_params);
    $this->assertEquals(8, $activityCount);

    // If we're showing case activities, we exepct to see one more (the scheduled meeting)...
    $this->setShowCaseActivitiesInCore(TRUE);
    $activityCount = CRM_Activity_BAO_Activity::getActivitiesCount($this->_params);
    $this->assertEquals(9, $activityCount);
    // Reset to default
    $this->setShowCaseActivitiesInCore(FALSE);
  }

  /**
   * Test getActivities BAO method for getting count
   *
   */
  public function testGetActivitiesCountforNonAdminDashboard() {
    // Reset to default
    $this->setShowCaseActivitiesInCore(FALSE);
    $this->createTestActivities();
    $this->addCaseWithActivity();
    CRM_Core_Config::singleton()->userPermissionClass->permissions[] = 'access all cases and activities';

    $params = [
      'contact_id' => 9,
      'admin' => FALSE,
      'caseId' => NULL,
      'context' => 'home',
      'activity_type_id' => NULL,
      // for dashlet the Scheduled status is set by default
      'activity_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Scheduled'),
      'offset' => 0,
      'rowCount' => 0,
      'sort' => NULL,
    ];

    //since we are loading activities from dataset, we know total number of activities for this contact
    // 5 activities ( 2 scheduled, 3 Completed, 1 Scheduled Case activity ),
    // note that dashboard shows only scheduled activities
    $this->assertEquals(2, CRM_Activity_BAO_Activity::getActivitiesCount($params));

    // If we're showing case activities, we expect to see one more (the scheduled meeting)...
    $this->setShowCaseActivitiesInCore(TRUE);
    $this->assertEquals(3, CRM_Activity_BAO_Activity::getActivitiesCount($params));
    // Reset to default
    $this->setShowCaseActivitiesInCore(FALSE);
  }

  /**
   * Test getActivities BAO method for getting count
   *
   */
  public function testGetActivitiesCountforContactSummary() {
    // Reset to default
    $this->setShowCaseActivitiesInCore(FALSE);
    $this->createTestActivities();
    $this->addCaseWithActivity();
    CRM_Core_Config::singleton()->userPermissionClass->permissions[] = 'access all cases and activities';

    $params = [
      'contact_id' => 9,
      'admin' => FALSE,
      'caseId' => NULL,
      'context' => 'activity',
      'activity_type_id' => NULL,
      'offset' => 0,
      'rowCount' => 0,
      'sort' => NULL,
    ];

    //since we are loading activities from dataset, we know total number of activities for this contact
    // 5 activities
    $this->assertEquals(5, CRM_Activity_BAO_Activity::getActivitiesCount($params));

    // If we're showing case activities, we exepct to see one more (the scheduled meeting)...
    $this->setShowCaseActivitiesInCore(TRUE);
    $this->assertEquals(6, CRM_Activity_BAO_Activity::getActivitiesCount($params));
    // Reset to default
    $this->setShowCaseActivitiesInCore(FALSE);
  }

  /**
   * CRM-18706 - Test Include/Exclude Activity Filters
   */
  public function testActivityFilters() {
    $this->createTestActivities();
    Civi::settings()->set('preserve_activity_tab_filter', 1);
    $this->createLoggedInUser();

    global $_GET;
    $_GET = [
      'cid' => 9,
      'context' => 'activity',
      'activity_type_id' => 1,
    ];
    $expectedFilters = [
      'activity_type_filter_id' => 1,
    ];

    try {
      CRM_Activity_Page_AJAX::getContactActivity();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $activityFilter = Civi::contactSettings()->get('activity_tab_filter');
      $activities = $e->errorData;
    }
    //Assert whether filters are correctly set.
    $this->checkArrayEquals($expectedFilters, $activityFilter);
    // This should include activities of type Meeting only.
    foreach ($activities['data'] as $value) {
      $this->assertStringContainsString('Meeting', $value['activity_type']);
    }
    unset($_GET['activity_type_id']);

    $_GET['activity_type_exclude_id'] = $expectedFilters['activity_type_exclude_filter_id'] = 1;
    try {
      CRM_Activity_Page_AJAX::getContactActivity();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $activityFilter = Civi::contactSettings()->get('activity_tab_filter');
      $activities = $e->errorData;
    }
    $this->assertEquals(['activity_type_exclude_filter_id' => 1], $activityFilter);
    // None of the activities should be of type Meeting.
    foreach ($activities['data'] as $value) {
      $this->assertStringNotContainsString('Meeting', $value['activity_type']);
    }
  }

  /**
   * Test getActivities BAO method for getting count
   */
  public function testGetActivitiesCountforContactSummaryWithNoActivities() {
    $this->createTestActivities();

    $params = [
      'contact_id' => 17,
      'admin' => FALSE,
      'caseId' => NULL,
      'context' => 'home',
      'activity_type_id' => NULL,
      'offset' => 0,
      'rowCount' => 0,
      'sort' => NULL,
    ];

    //since we are loading activities from dataset, we know total number of activities for this contact
    // this contact does not have any activity
    $this->assertEquals(0, CRM_Activity_BAO_Activity::getActivitiesCount($params));
  }

  /**
   * Test getActivities BAO method.
   */
  public function testGetActivitiesForAdminDashboard() {
    $this->setShowCaseActivitiesInCore(FALSE);
    $this->setUpForActivityDashboardTests();
    $this->addCaseWithActivity();
    CRM_Core_Config::singleton()->userPermissionClass->permissions[] = 'access all cases and activities';

    $activitiesNew = CRM_Activity_BAO_Activity::getActivities($this->_params);
    // $this->assertEquals($activities, $activitiesDeprecatedFn);

    //since we are loading activities from dataset, we know total number of activities
    // with no contact ID and there should be 8 schedule activities shown on dashboard
    $count = 8;
    foreach ([$activitiesNew] as $activities) {
      $this->assertCount($count, $activities);

      foreach ($activities as $key => $value) {
        $this->assertEquals($value['subject'], "subject {$key}", 'Verify activity subject is correct.');
        $this->assertEquals($value['activity_type_id'], 2, 'Verify activity type is correct.');
        $this->assertEquals($value['status_id'], 1, 'Verify all activities are scheduled.');
      }
    }

    // Now check that we get the scheduled meeting, if civicaseShowCaseActivities is set.
    $this->setShowCaseActivitiesInCore(TRUE);
    $activitiesNew = CRM_Activity_BAO_Activity::getActivities($this->_params);
    $this->assertCount(9, $activitiesNew);
    // Scan through to find the meeting.
    $this->assertContains('test meeting activity', array_column($activitiesNew, 'subject'), "failed to find scheduled case Meeting activity");
    // Reset to default
    $this->setShowCaseActivitiesInCore(FALSE);
  }

  /**
   * Test getActivities BAO method.
   */
  public function testGetActivitiesForAdminDashboardNoViewContacts() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->setUpForActivityDashboardTests();
    foreach ([CRM_Activity_BAO_Activity::getActivities($this->_params)] as $activities) {
      // Skipped until we get back to the upgraded version properly.
      $this->assertCount(0, $activities);
    }
  }

  /**
   * Test getActivities BAO method.
   */
  public function testGetActivitiesForAdminDashboardAclLimitedViewContacts() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->allowedContacts = [1, 3, 4, 5];
    $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);
    $this->setUpForActivityDashboardTests();
    $this->assertEquals(7, count(CRM_Activity_BAO_Activity::getActivities($this->_params)));
  }

  /**
   * Test getActivities BAO method.
   */
  public function testGetActivitiesforNonAdminDashboard() {
    $this->setShowCaseActivitiesInCore(FALSE);
    $this->createTestActivities();
    $this->addCaseWithActivity();
    CRM_Core_Config::singleton()->userPermissionClass->permissions[] = 'access all cases and activities';

    $contactID = 9;
    $params = [
      'contact_id' => $contactID,
      'admin' => FALSE,
      'caseId' => NULL,
      'context' => 'home',
      'activity_type_id' => NULL,
      // for dashlet the Scheduled status is set by default
      'activity_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Scheduled'),
      'offset' => 0,
      'rowCount' => 0,
      'sort' => NULL,
    ];

    foreach ([CRM_Activity_BAO_Activity::getActivities($params)] as $activities) {
      //since we are loading activities from dataset, we know total number of activities for this contact
      // 5 activities ( 2 scheduled, 3 Completed ), note that dashboard shows only scheduled activities
      $count = 2;
      $this->assertEquals($count, count($activities));

      foreach ($activities as $key => $value) {
        $this->assertEquals($value['subject'], "subject {$key}", 'Verify activity subject is correct.');
        $this->assertEquals($value['activity_type_id'], 2, 'Verify activity type is correct.');
        $this->assertEquals($value['status_id'], 1, 'Verify all activities are scheduled.');

        if ($key == 3) {
          $this->assertArrayHasKey($contactID, $value['target_contact_name']);
        }
        elseif ($key == 4) {
          $this->assertArrayHasKey($contactID, $value['assignee_contact_name']);
        }
      }
    }

    // Now check that we get the scheduled meeting, if civicaseShowCaseActivities is set.
    $this->setShowCaseActivitiesInCore(TRUE);
    $activities = CRM_Activity_BAO_Activity::getActivities($params);
    $this->assertEquals(3, count($activities));
    // Scan through to find the meeting.
    $this->assertTrue(in_array('test meeting activity', array_column($activities, 'subject')),
      "failed to find scheduled case Meeting activity");

    // Reset to default
    $this->setShowCaseActivitiesInCore(FALSE);
  }

  /**
   * Test target contact count.
   */
  public function testTargetCountforContactSummary() {
    $targetCount = 5;
    $contactId = $this->individualCreate();
    $targetContactIDs = [];
    for ($i = 0; $i < $targetCount; $i++) {
      $targetContactIDs[] = $this->individualCreate([], $i);
    }
    // Create activities with 5 target contacts.
    $activityParams = [
      'source_contact_id' => $contactId,
      'target_contact_id' => $targetContactIDs,
    ];
    $this->activityCreate($activityParams);

    $params = [
      'contact_id' => $contactId,
      'context' => 'activity',
    ];
    $activities = CRM_Activity_BAO_Activity::getActivities($params);
    //verify target count
    $this->assertEquals($targetCount, $activities[1]['target_contact_count']);
    $this->assertEquals([$targetContactIDs[0] => 'Anderson, Anthony'], $activities[1]['target_contact_name']);
    $this->assertEquals('Anderson, Anthony', $activities[1]['source_contact_name']);
    $this->assertEquals('Anderson, Anthony', $activities[1]['assignee_contact_name'][4]);
  }

  /**
   * Test getActivities BAO method.
   */
  public function testGetActivitiesforContactSummaryWithSortOptions() {
    $this->createTestActivities();
    $params = [
      'contact_id' => 9,
      'admin' => FALSE,
      'caseId' => NULL,
      'context' => 'activity',
      'activity_type_id' => NULL,
      'offset' => 0,
      'rowCount' => 0,
      'sort' => 'source_contact_name desc',
    ];

    $activities = CRM_Activity_BAO_Activity::getActivities($params);
    $alphaOrder = ['Test Contact 11', 'Test Contact 12', 'Test Contact 3', 'Test Contact 4', 'Test Contact 9'];
    foreach ($activities as $activity) {
      $this->assertEquals(array_pop($alphaOrder), $activity['source_contact_name']);
    }

  }

  /**
   * Test getActivities BAO method.
   */
  public function testGetActivitiesForContactSummary() {
    // Reset to default
    $this->setShowCaseActivitiesInCore(FALSE);
    $this->createTestActivities();
    $this->addCaseWithActivity();
    CRM_Core_Config::singleton()->userPermissionClass->permissions[] = 'access all cases and activities';

    $contactID = 9;
    $params = [
      'contact_id' => $contactID,
      'admin' => FALSE,
      'caseId' => NULL,
      'context' => 'activity',
      'activity_type_id' => NULL,
      'offset' => 0,
      'rowCount' => 0,
    ];

    //since we are loading activities from dataset, we know total number of activities for this contact
    // 5 activities, Contact Summary should show all activities
    $count = 5;
    $activities = CRM_Activity_BAO_Activity::getActivities($params);
    $this->assertEquals($count, count($activities));
    foreach ($activities as $key => $value) {
      $this->assertEquals($value['subject'], "subject {$key}", 'Verify activity subject is correct.');

      if ($key > 8) {
        $this->assertEquals($value['status_id'], 2, 'Verify all activities are scheduled.');
      }
      else {
        $this->assertEquals($value['status_id'], 1, 'Verify all activities are scheduled.');
      }

      if ($key === 12) {
        $this->assertEquals($value['activity_type'], 'Bulk Email', 'Verify activity type is correct.');
        $this->assertEquals('(2 recipients)', $value['recipients']);
        $targetContactID = key($value['target_contact_name']);
        // The 2 targets have ids 10 & 11. Since they are not sorted it could be either on some systems.
        $this->assertTrue(in_array($targetContactID, [10, 11]));
      }
      elseif ($key > 8) {
        $this->assertEquals($value['activity_type_id'], 1, 'Verify activity type is correct.');
      }
      else {
        $this->assertEquals($value['activity_type_id'], 2, 'Verify activity type is correct.');
      }

      if ($key == 3) {
        $this->assertEquals([$contactID => 'Test Contact ' . $contactID], $value['target_contact_name']);
      }
      elseif ($key == 4) {
        $this->assertArrayHasKey($contactID, $value['assignee_contact_name']);
      }
    }

    // Now check that we get the scheduled meeting, if civicaseShowCaseActivities is set.
    $this->setShowCaseActivitiesInCore(TRUE);
    $activities = CRM_Activity_BAO_Activity::getActivities($params);
    $this->assertEquals(6, count($activities));
    // Scan through to find the meeting.
    $this->assertTrue(in_array('test meeting activity', array_column($activities, 'subject')),
      "failed to find scheduled case Meeting activity");
    // Reset to default
    $this->setShowCaseActivitiesInCore(FALSE);
  }

  /**
   * Test getActivities BAO method.
   */
  public function testGetActivitiesforContactSummaryWithActivities() {
    // Reset to default
    $this->setShowCaseActivitiesInCore(FALSE);
    $this->createTestActivities();

    // parameters for different test cases, check each array key for the specific test-case
    $testCases = [
      'with-no-activity' => [
        'params' => [
          'contact_id' => 17,
          'admin' => FALSE,
          'caseId' => NULL,
          'context' => 'home',
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ],
      ],
      'with-activity' => [
        'params' => [
          'contact_id' => 1,
          'admin' => FALSE,
          'caseId' => NULL,
          'context' => 'home',
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ],
      ],
      'with-activity_type' => [
        'params' => [
          'contact_id' => 3,
          'admin' => FALSE,
          'caseId' => NULL,
          'context' => 'home',
          'activity_type_id' => 2,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ],
      ],
      'exclude-all-activity_type' => [
        'params' => [
          'contact_id' => 3,
          'admin' => FALSE,
          'caseId' => NULL,
          'context' => 'home',
          'activity_type_exclude_id' => [1, 2],
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ],
      ],
      'sort-by-subject' => [
        'params' => [
          'contact_id' => 1,
          'admin' => FALSE,
          'caseId' => NULL,
          'context' => 'home',
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => 'subject DESC',
        ],
      ],
    ];

    foreach ($testCases as $caseName => $testCase) {
      $activityCount = CRM_Activity_BAO_Activity::getActivitiesCount($testCase['params']);
      $activitiesNew = CRM_Activity_BAO_Activity::getActivities($testCase['params']);

      foreach ([$activitiesNew] as $activities) {
        //$this->assertEquals($activityCount, CRM_Activity_BAO_Activity::getActivitiesCount($testCase['params']));
        if ($caseName === 'with-no-activity') {
          $this->assertEquals(0, count($activities));
          $this->assertEquals(0, $activityCount);
        }
        elseif ($caseName === 'with-activity') {
          // contact id 1 is assigned as source, target and assignee for activity id 1, 7 and 8 respectively
          $this->assertEquals(3, count($activities));
          $this->assertEquals(3, $activityCount);
          $this->assertEquals(1, $activities[1]['source_contact_id']);
          // @todo - this is a discrepancy between old & new - review.
          //$this->assertEquals(TRUE, array_key_exists(1, $activities[7]['target_contact_name']));
          $this->assertEquals(TRUE, array_key_exists(1, $activities[8]['assignee_contact_name']));
        }
        elseif ($caseName === 'with-activity_type') {
          // contact id 3 for activity type 2 is assigned as assignee, source and target for
          // activity id 1, 3 and 8 respectively
          $this->assertEquals(3, count($activities));
          $this->assertEquals(3, $activityCount);
          // ensure activity type id is 2
          $this->assertEquals(2, $activities[1]['activity_type_id']);
          $this->assertEquals(3, $activities[3]['source_contact_id']);
          // @todo review inconsistency between 2 versions.
          // $this->assertEquals(TRUE, array_key_exists(3, $activities[8]['target_contact_name']));
          $this->assertEquals(TRUE, array_key_exists(3, $activities[1]['assignee_contact_name']));
        }
        if ($caseName === 'exclude-all-activity_type') {
          $this->assertEquals(0, count($activities));
          $this->assertEquals(0, $activityCount);
        }
        if ($caseName === 'sort-by-subject') {
          $this->assertEquals(3, count($activities));
          $this->assertEquals(3, $activityCount);
          // activities should be order by 'subject DESC'
          $subjectOrder = [
            'subject 8',
            'subject 7',
            'subject 1',
          ];
          $count = 0;
          foreach ($activities as $activity) {
            $this->assertEquals($subjectOrder[$count], $activity['subject']);
            $count++;
          }
        }
      }
    }
  }

  /**
   * CRM-20793 : Test getActivities by using activity date and status filter
   *
   * @throws \CRM_Core_Exception
   */
  public function testByActivityDateAndStatus(): void {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['view all contacts', 'access CiviCRM'];
    $this->createTestActivities();

    // activity IDs catagorised by date
    $lastWeekActivities = [1, 2, 3];
    $todayActivities = [4, 5, 6, 7];
    $lastTwoMonthsActivities = [8, 9, 10, 11];
    $lastOrNextYearActivities = [12, 13, 14, 15, 16];

    // date values later used to set activity date value
    $lastWeekDate = date('YmdHis', strtotime('1 week ago'));
    $today = date('YmdHis');
    $lastTwoMonthAgoDate = date('YmdHis', strtotime('2 months ago'));
    // if current month is Jan then choose next year date otherwise the search result will include
    //  the previous week and last two months activities which are still in previous year and hence leads to improper result
    $lastOrNextYearDate = (date('M') === 'Jan') ? date('YmdHis', strtotime('+1 year')) : date('YmdHis', strtotime('1 year ago'));
    for ($i = 1; $i <= 16; $i++) {
      if (in_array($i, $lastWeekActivities)) {
        $date = $lastWeekDate;
      }
      elseif (in_array($i, $lastTwoMonthsActivities)) {
        $date = $lastTwoMonthAgoDate;
      }
      elseif (in_array($i, $lastOrNextYearActivities)) {
        $date = $lastOrNextYearDate;
      }
      elseif (in_array($i, $todayActivities)) {
        $date = $today;
      }
      $this->callAPISuccess('Activity', 'create', [
        'id' => $i,
        'activity_date_time' => $date,
      ]);
    }

    // parameters for different test cases, check each array key for the specific test-case
    $testCases = [
      'todays-activity' => [
        'params' => [
          'contact_id' => 1,
          'admin' => TRUE,
          'caseId' => NULL,
          'context' => 'activity',
          'activity_date_time_relative' => 'this.day',
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ],
      ],
      'todays-activity-filtered-by-range' => [
        'params' => [
          'contact_id' => 1,
          'admin' => TRUE,
          'caseId' => NULL,
          'context' => 'activity',
          'activity_date_time_low' => date('Y/m/d', strtotime('yesterday')),
          'activity_date_time_high' => date('Y/m/d'),
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ],
      ],
      'last-week-activity' => [
        'params' => [
          'contact_id' => 1,
          'admin' => TRUE,
          'caseId' => NULL,
          'context' => 'activity',
          'activity_date_time_relative' => 'previous.week',
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ],
      ],
      'this-quarter-activity' => [
        'params' => [
          'contact_id' => 1,
          'admin' => TRUE,
          'caseId' => NULL,
          'context' => 'activity',
          'activity_date_time_relative' => 'this.quarter',
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ],
      ],
      'activity-of-all-statuses' => [
        'params' => [
          'contact_id' => 1,
          'admin' => TRUE,
          'caseId' => NULL,
          'context' => 'activity',
          'activity_status_id' => '1,2',
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ],
      ],
    ];

    foreach ($testCases as $caseName => $testCase) {
      CRM_Utils_Date::convertFormDateToApiFormat($testCase['params'], 'activity_date_time', FALSE);
      $activities = CRM_Activity_BAO_Activity::getActivities($testCase['params']);
      $activityCount = CRM_Activity_BAO_Activity::getActivitiesCount($testCase['params']);
      asort($activities);
      $activityIDs = array_keys($activities);

      if ($caseName === 'todays-activity' || $caseName === 'todays-activity-filtered-by-range') {
        // Only one of the 4 activities today relates to contact id 1.
        $this->assertEquals(1, $activityCount);
        $this->assertEquals(1, count($activities));
        $this->assertEquals([7], array_keys($activities));
      }
      elseif ($caseName === 'last-week-activity') {
        // Only one of the 3 activities today relates to contact id 1.
        $this->assertEquals(1, $activityCount);
        $this->assertEquals(1, count($activities));
        $this->assertEquals([1], $activityIDs);
      }
      elseif ($caseName === 'lhis-quarter-activity') {
        $this->assertEquals(count($lastTwoMonthsActivities), $activityCount);
        $this->assertEquals(count($lastTwoMonthsActivities), count($activities));
        $this->checkArrayEquals($lastTwoMonthsActivities, $activityIDs);
      }
      elseif ($caseName === 'last-or-next-year-activity') {
        $this->assertEquals(count($lastOrNextYearActivities), $activityCount);
        $this->assertEquals(count($lastOrNextYearActivities), count($activities));
        $this->checkArrayEquals($lastOrNextYearActivities, $activityIDs);
      }
      elseif ($caseName === 'activity-of-all-statuses') {
        $this->assertEquals(3, $activityCount);
        $this->assertEquals(3, count($activities));
      }
    }
  }

  /**
   * @dataProvider getActivityDateData
   *
   * @param $params
   * @param $expected
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testActivityRelativeDateFilter($params, $expected): void {
    $thisYear = date('Y');
    $dates = [
      date('Y-m-d', strtotime(($thisYear - 1) . '-01-01')),
      date('Y-m-d', strtotime(($thisYear - 1) . '-12-31')),
      date('Y-m-d', strtotime($thisYear . '-01-01')),
      date('Y-m-d', strtotime($thisYear . '-12-31')),
      date('Y-m-d', strtotime(($thisYear + 1) . '-01-01')),
      date('Y-m-d', strtotime(($thisYear + 1) . '-12-31')),
    ];
    foreach ($dates as $date) {
      $this->activityCreate(['activity_date_time' => $date]);
    }
    $activitiesDep = CRM_Activity_BAO_Activity::getActivities($params);
    $activityCount = CRM_Activity_BAO_Activity::getActivitiesCount($params);
    $this->assertEquals(count($activitiesDep), $activityCount);
    foreach ($activitiesDep as $activity) {
      $this->assertTrue(strtotime($activity['activity_date_time']) >= $expected['earliest'], $activity['activity_date_time'] . ' should be no earlier than ' . date('Y-m-d H:i:s', $expected['earliest']));
      $this->assertTrue(strtotime($activity['activity_date_time']) < $expected['latest'], $activity['activity_date_time'] . ' should be before ' . date('Y-m-d H:i:s', $expected['latest']));
    }

  }

  /**
   * Get activity date data.
   *
   * Later we might migrate rework the rest of
   * testByActivityDateAndStatus
   * to use data provider methodology as it's way complex!
   *
   * @return array
   */
  public function getActivityDateData() {
    return [
      'last-year-activity' => [
        'params' => [
          'contact_id' => 1,
          'admin' => TRUE,
          'caseId' => NULL,
          'context' => 'activity',
          'activity_date_relative' => 'previous.year',
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ],
        'expected' => [
          'count' => 2,
          'earliest' => strtotime('first day of january last year'),
          'latest' => strtotime('first day of january this year'),
        ],
      ],
    ];
  }

  /**
   * CRM-20308: Test from email address when a 'copy of Activity' event occur
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testEmailAddressOfActivityCopy() {
    // Case 1: assert the 'From' Email Address of source Actvity Contact ID
    // create activity with source contact ID which has email address
    $assigneeContactId = $this->individualCreate();
    $sourceContactParams = [
      'first_name' => 'liz',
      'last_name' => 'hurleey',
      'email' => 'liz@testemail.com',
    ];
    $sourceContactID = $this->individualCreate($sourceContactParams);
    $sourceDisplayName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $sourceContactID, 'display_name');

    // create an activity using API
    $params = [
      'source_contact_id' => $sourceContactID,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 'Meeting',
      'assignee_contact_id' => [$assigneeContactId],
      'activity_date_time' => 'now',
    ];
    $activity = $this->callAPISuccess('Activity', 'create', $params);

    // Check that from address is in "Source-Display-Name <source-email>"
    $formAddress = CRM_Case_BAO_Case::getReceiptFrom($activity['id']);
    $expectedFromAddress = sprintf('%s <%s>', $sourceDisplayName, $sourceContactParams['email']);
    $this->assertEquals($expectedFromAddress, $formAddress);

    // Case 2: System Default From Address
    //  but first erase the email address of existing source contact ID
    $withoutEmailParams = [
      'email' => '',
    ];
    $sourceContactID = $this->individualCreate($withoutEmailParams);
    $params = [
      'source_contact_id' => $sourceContactID,
      'subject' => 'Scheduling Meeting 2',
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Meeting'),
      'activity_date_time' => date('Ymd'),
    ];
    $activity = $this->callAPISuccess('Activity', 'create', $params);
    // fetch domain info
    $domainInfo = $this->callAPISuccess('Domain', 'getsingle', ['id' => CRM_Core_Config::domainID()]);

    $formAddress = CRM_Case_BAO_Case::getReceiptFrom($activity['id']);
    if (!empty($domainInfo['from_email'])) {
      $expectedFromAddress = sprintf('%s <%s>', $domainInfo['from_name'], $domainInfo['from_email']);
    }
    // Case 3: fetch default Organization Contact email address
    elseif (!empty($domainInfo['domain_email'])) {
      $expectedFromAddress = sprintf('%s <%s>', $domainInfo['name'], $domainInfo['domain_email']);
    }
    $this->assertEquals($expectedFromAddress, $formAddress);

    // TODO: Case 4 about checking the $formAddress on basis of logged contact ID respectively needs,
    //  to change the domain setting, which isn't straight forward in test environment
  }

  /**
   * Set up for testing activity queries.
   */
  protected function setUpForActivityDashboardTests() {
    $this->createTestActivities();

    $this->_params = [
      'contact_id' => NULL,
      'admin' => TRUE,
      'caseId' => NULL,
      'context' => 'home',
      'activity_type_id' => NULL,
    // for dashlet the Scheduled status is set by default
      'activity_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Scheduled'),
      'offset' => 0,
      'rowCount' => 0,
      'sort' => NULL,
    ];
  }

  /**
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSendEmailBasic(): void {
    $contactId = $this->getContactID();

    // create a logged in USER since the code references it for sendEmail user.
    $this->createLoggedInUser();

    $contactDetailsIntersectKeys = [
      'contact_id' => '',
      'sort_name' => '',
      'display_name' => '',
      'do_not_email' => '',
      'preferred_mail_format' => '',
      'is_deceased' => '',
      'email' => '',
      'on_hold' => '',
    ];

    $contact = $this->callAPISuccess('Contact', 'getsingle', ['id' => $contactId, 'return' => array_keys($contactDetailsIntersectKeys)]);

    $subject = __FUNCTION__ . ' subject';
    $html = __FUNCTION__ . ' html {contact.display_name} {case.case_type_id:label}';
    $text = __FUNCTION__ . ' text {contact.display_name} {case.case_type_id:label}';
    $form = $this->getCaseEmailTaskForm($contactId, [
      'subject' => $subject,
      'html_message' => $html,
      'text_message' => $text,
    ]);
    $mut = new CiviMailUtils($this, TRUE);
    $form->postProcess();
    $activity = Activity::get()
      ->addSelect('activity_type_id:label', 'subject', 'details')
      ->addWhere('activity_type_id:name', '=', 'Email')
      ->execute()->first();

    $details = '-ALTERNATIVE ITEM 0-
' . __FUNCTION__ . ' html ' . $contact['display_name'] . ' Housing Support
-ALTERNATIVE ITEM 1-
' . __FUNCTION__ . ' text ' . $contact['display_name'] . ' Housing Support
-ALTERNATIVE END-
';
    $this->assertEquals($details, $activity['details'], 'Activity details do not match.');
    $this->assertEquals($subject, $activity['subject'], 'Activity subject do not match.');
    $mut->checkMailLog([
      'Mr. Anthony Anderson II Housing Support',
    ]);
    $mut->stop();
  }

  /**
   * Get case ID.
   *
   * @return int
   */
  protected function getCaseID(): int {
    if (!isset($this->ids['Case'][0])) {
      CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
      $this->ids['Case'][0] = $this->callAPISuccess('Case', 'create', [
        'case_type_id' => 'housing_support',
        'activity_subject' => 'Case Subject',
        'client_id' => $this->getContactID(),
        'status_id' => 1,
        'subject' => 'Case Subject',
        'start_date' => '2021-07-23 15:39:20',
        // Note end_date is inconsistent with status Ongoing but for the
        // purposes of testing tokens is ok. Creating it with status Resolved
        // then ignores our known fixed end date.
        'end_date' => '2021-07-26 18:07:20',
        'medium_id' => 2,
        'details' => 'case details',
        'activity_details' => 'blah blah',
        'sequential' => 1,
      ])['id'];
    }
    return $this->ids['Case'][0];
  }

  /**
   * @return int
   */
  protected function getContactID(): int {
    if (!isset($this->ids['Contact'][0])) {
      $this->ids['Contact'][0] = $this->individualCreate();
    }
    return $this->ids['Contact'][0];
  }

  /**
   * This is different from SentEmailBasic to try to help prevent code that
   * assumes an email always has tokens in it.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSendEmailBasicWithoutAnyTokens(): void {
    $contactId = $this->individualCreate();

    // create a logged in USER since the code references it for sendEmail user.
    $this->createLoggedInUser();

    $subject = __FUNCTION__ . ' subject';
    $html = __FUNCTION__ . ' html';
    $text = __FUNCTION__ . ' text';
    $form = $this->getContactEmailTaskForm($contactId, [
      'subject' => $subject,
      'html_message' => $html,
      'text_message' => $text,
    ]);
    $mut = new CiviMailUtils($this, TRUE);
    $form->postProcess();

    $activity = Activity::get()
      ->addSelect('activity_type_id:label', 'subject', 'details')
      ->addWhere('activity_type_id:name', '=', 'Email')
      ->execute()->first();

    $details = "-ALTERNATIVE ITEM 0-
$html
-ALTERNATIVE ITEM 1-
$text
-ALTERNATIVE END-
";
    $this->assertEquals($activity['details'], $details, 'Activity details does not match.');
    $this->assertEquals($activity['subject'], $subject, 'Activity subject does not match.');
    $mut->checkMailLog([
      'From: from@example.com',
      'To: Anthony Anderson <email@example.com>',
      "Subject: $subject",
      $html,
      $text,
    ]);
    $mut->stop();
  }

  /**
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSendEmailWithCampaign(): void {
    // Create a contact and contactDetails array.
    $contactId = $this->individualCreate();

    // create a logged in USER since the code references it for sendEmail user.
    $this->createLoggedInUser();
    $this->enableCiviCampaign();

    // Create a campaign.
    $result = $this->civicrm_api('Campaign', 'create', [
      'version' => $this->_apiversion,
      'title' => __FUNCTION__ . ' campaign',
    ]);
    $campaign_id = $result['id'];

    $html = __FUNCTION__ . ' html';
    $text = __FUNCTION__ . ' text';
    /* @var CRM_Activity_Form_Task_Email $form */
    $form = $this->getCaseEmailTaskForm($contactId, [
      'subject' => '',
      'html_message' => $html,
      'text_message' => $text,
      'campaign_id' => $campaign_id,
    ]);
    $form->postProcess();
    $activity = Activity::get()
      ->addSelect('activity_type_id:label', 'subject', 'details', 'campaign_id')
      ->addWhere('activity_type_id:name', '=', 'Email')
      ->execute()->first();

    $this->assertEquals($activity['campaign_id'], $campaign_id, 'Activity campaign_id does not match.');
  }

  /**
   */
  public function testSendSMSWithoutPermission(): void {
    $dummy = NULL;
    $session = CRM_Core_Session::singleton();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have the \'send SMS\' permission');
    CRM_Activity_BAO_Activity::sendSMS(
      $dummy,
      $dummy,
      $dummy,
      $dummy,
      $session->get('userID')
    );
  }

  /**
   * Test that a sms does not send when a phone number is not available.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSendSmsNoPhoneNumber(): void {
    $sent = $this->createSendSmsTest(FALSE);
    $this->assertEquals('Recipient phone number is invalid or recipient does not want to receive SMS', $sent[0], "Expected error doesn't match");
  }

  /**
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSendSmsLandLinePhoneNumber(): void {
    $sent = $this->createSendSmsTest(FALSE, 1);
    $this->assertEquals('Recipient phone number is invalid or recipient does not want to receive SMS', $sent[0], "Expected error doesn't match");
  }

  /**
   * Test successful SMS send.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSendSmsMobilePhoneNumber(): void {
    $sent = $this->createSendSmsTest(TRUE, 2);
    $this->assertEquals(TRUE, $sent[0]);
    /* @var CiviTestSMSProvider $provider $provider['id']*/
    $providerObj = CRM_SMS_Provider::singleton(['provider_id' => $this->ids['SmsProvider'][0]]);
    $this->assertEquals('text Anthony', $providerObj->getSentMessage());
  }

  /**
   * Test that when a number is specified in the To Param of the SMS provider parameters that an SMS is sent
   * @see dev/core/#273
   */
  public function testSendSMSMobileInToProviderParam(): void {
    $sent = $this->createSendSmsTest(TRUE, 2, TRUE);
    $this->assertEquals(TRUE, $sent[0], 'Expected sent should be true');
  }

  /**
   * Test that when a numbe ris specified in the To Param of the SMS provider parameters that an SMS is sent
   * @see dev/core/#273
   */
  public function testSendSMSMobileInToProviderParamWithDoNotSMS(): void {
    $sent = $this->createSendSmsTest(FALSE, 2, TRUE, ['do_not_sms' => 1]);
    foreach ($sent as $error) {
      $this->assertEquals('Contact Does not accept SMS', $error);
    }
    $this->assertCount(1, $sent, 'Expected sent should a PEAR Error');
  }

  /**
   * @param bool $expectSuccess
   * @param int $phoneType (0=no phone, phone_type option group (1=fixed,
   *   2=mobile)
   * @param bool $passPhoneTypeInContactDetails
   * @param array $additionalContactParams additional contact creation params
   *
   * @return array
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function createSendSmsTest(bool $expectSuccess = TRUE, int $phoneType = 0, bool $passPhoneTypeInContactDetails = FALSE, array $additionalContactParams = []): array {
    $this->ids['SmsProvider'][0] = civicrm_api3('SmsProvider', 'create', [
      'name' => 'CiviTestSMSProvider',
      'api_type' => 1,
      'username' => 1,
      'password' => 1,
      'api_url' => 1,
      'api_params' => 'a=1',
      'is_default' => 1,
      'is_active' => 1,
      'domain_id' => 1,
    ])['id'];

    $smsProviderParams['provider_id'] = $this->ids['SmsProvider'][0];

    // Create a contact
    $contactId = $this->individualCreate();
    if (!empty($additionalContactParams)) {
      $this->callAPISuccess('contact', 'create', ['id' => $contactId] + $additionalContactParams);
    }
    $contactsResult = $this->callApiSuccess('Contact', 'get', ['id' => $contactId, 'return' => ['id', 'phone_type_id', 'do_not_sms']]);
    $contactDetails = $contactsResult['values'];

    // Get contactIds from contact details
    foreach ($contactDetails as $contact) {
      $contactIds[] = $contact['contact_id'];
    }

    $activityParams['sms_text_message'] = 'text {contact.first_name}';
    $activityParams['activity_subject'] = 'subject';

    // Get a "logged in" user to set as source of Sms.
    $session = CRM_Core_Session::singleton();
    $sourceContactId = $session->get('userID');

    $this->createLoggedInUser();

    // Give user permission to 'send SMS'
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'send SMS'];

    // Create a phone number
    switch ($phoneType) {
      case 0:
        // No phone number
        break;

      case 2:
        // Create a mobile phone number
        $contactDetails = $this->createMobilePhone($contactId, $passPhoneTypeInContactDetails, $contactDetails);
        break;

      case 1:
        // Create a fixed phone number
        $phone = civicrm_api3('Phone', 'create', [
          'contact_id' => $contactId,
          'phone' => 654321,
          'phone_type_id' => 'Phone',
        ]);
        if ($passPhoneTypeInContactDetails) {
          $contactDetails[$contactId]['phone'] = $phone['values'][$phone['id']]['phone'];
          $contactDetails[$contactId]['phone_type_id'] = $phone['values'][$phone['id']]['phone_type_id'];
        }
        break;
    }

    // Now run the actual test
    [$sent, $activityId, $success] = CRM_Activity_BAO_Activity::sendSms(
      $contactDetails,
      $activityParams,
      $smsProviderParams,
      $contactIds,
      $sourceContactId
    );
    $this->validateActivity($activityId);
    $this->assertEquals($expectSuccess, $success);
    return (array) $sent;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function createTestActivities(): void {
    $this->loadXMLDataSet(__DIR__ . '/activities_for_dashboard_count.xml');
    // Make changes to improve variation in php since the xml method is brittle & relies on option values being unchanged.
    $this->callAPISuccess('Activity', 'create', ['id' => 12, 'activity_type_id' => 'Bulk Email']);
  }

  /**
   * ACL HOOK implementation for various tests
   */
  public function hook_civicrm_aclWhereClause($type, &$tables, &$whereTables, &$contactID, &$where): void {
    if (!empty($this->allowedContactsACL)) {
      $contact_id_list = implode(',', $this->allowedContactsACL);
      $where = " contact_a.id IN ($contact_id_list)";
    }
  }

  public function testCaseTokens() {
    $caseTest = new CiviCaseTestCase();
    $caseTest->setUp();
    // Create a contact and contactDetails array.
    $contactId = $this->individualCreate();

    // create a case for this user
    $result = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $contactId,
      'case_type_id' => '1',
      'subject' => "my case",
      'status_id' => "Open",
    ]);

    $caseId = $result['id'];
    $html_message = "<p>This is a test case with id: {case.id} and subject: {case.subject}</p>";
    $html_message = CRM_Utils_Token::replaceCaseTokens($caseId, $html_message);

    $this->assertTrue(strpos($html_message, 'id: ' . $caseId) !== 0);
    $this->assertTrue(strpos($html_message, 'subject: my case') !== 0);
    $caseTest->tearDown();
  }

  public function testSendEmailWithCaseId() {
    $caseTest = new CiviCaseTestCase();
    $caseTest->setUp();
    // Create a contact and contactDetails array.
    $contactId = $this->individualCreate();

    // create a logged in USER since the code references it for sendEmail user.
    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['view all contacts', 'access CiviCRM', 'access all cases and activities', 'administer CiviCase'];

    $subject = __FUNCTION__ . ' subject {case.subject}';
    $html = __FUNCTION__ . ' html {case.subject}';
    $text = __FUNCTION__ . ' text';

    $mut = new CiviMailUtils($this, TRUE);
    $form = $this->getCaseEmailTaskForm($contactId, [
      'subject' => $subject,
      'html_message' => $html,
      'text_message' => $text,
      'to' => $contactId . '::' . 'email@example.com',
    ]);
    $form->postProcess();
    $activity = Activity::get()
      ->addSelect('id')
      ->addWhere('activity_type_id:name', '=', 'Email')
      ->execute()->first();
    $activity = $this->callAPISuccess('Activity', 'getsingle', ['id' => $activity['id'], 'return' => ['case_id']]);
    $this->assertEquals($this->getCaseID(), $activity['case_id'][0], 'Activity case_id does not match.');
    $mut->checkMailLog(['subject Case Subject']);
    $mut->stop();
  }

  /**
   * Checks that tokens are uniquely replaced for contacts.
   */
  public function testSendEmailWillReplaceTokensUniquelyForEachContact(): void {
    $contactId1 = $this->individualCreate(['last_name' => 'Red']);
    $contactId2 = $this->individualCreate(['last_name' => 'Pink']);

    // create a logged in USER since the code references it for sendEmail user.
    $this->createLoggedInUser();
    $contact = $this->callAPISuccess('Contact', 'get', ['sequential' => 1, 'id' => ['IN' => [$contactId1, $contactId2]]]);

    // Create a campaign.
    $result = $this->callAPISuccess('Campaign', 'create', [
      'version' => $this->_apiversion,
      'title' => __FUNCTION__ . ' campaign',
    ]);
    $campaign_id = $result['id'];

    // Add contact tokens in subject, html , text.
    $subject = __FUNCTION__ . ' subject' . '{contact.display_name}';
    $html = __FUNCTION__ . ' html' . '{contact.display_name}';
    $text = __FUNCTION__ . ' text' . '{contact.display_name}';

    /* @var CRM_Contact_Form_Task_Email $form */
    $form = $this->getFormObject('CRM_Contact_Form_Task_Email', [
      'subject' => $subject,
      'html_message' => $html,
      'text_message' => $text,
      'campaign_id' => $campaign_id,
      'from_email_address' => 'from@example.com',
      'to' => $contactId1 . '::email@example.com,' . $contactId2 . '::email2@example.com',
    ], [], []);
    $form->set('cid', $contactId1 . ',' . $contactId2);
    $form->buildForm();
    $form->postProcess();

    $result = $this->callAPISuccess('activity', 'get', ['campaign_id' => $campaign_id]);
    // An activity created for each of the two contacts
    $this->assertEquals(2, $result['count']);
    $id = 0;
    foreach ($result['values'] as $activity) {
      $htmlValue = str_replace('{contact.display_name}', $contact['values'][$id]['display_name'], $html);
      $textValue = str_replace('{contact.display_name}', $contact['values'][$id]['display_name'], $text);
      $subjectValue = str_replace('{contact.display_name}', $contact['values'][$id]['display_name'], $subject);
      $details = "-ALTERNATIVE ITEM 0-
$htmlValue
-ALTERNATIVE ITEM 1-
$textValue
-ALTERNATIVE END-
";
      $this->assertEquals($activity['details'], $details, 'Activity details does not match.');
      $this->assertEquals($activity['subject'], $subjectValue, 'Activity subject does not match.');
      $id++;
    }
  }

  /**
   * Test that smarty is rendered, if enabled.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSmartyEnabled(): void {
    putenv('CIVICRM_MAIL_SMARTY=1');
    $this->createLoggedInUser();
    $contactID = $this->individualCreate(['last_name' => 'Red']);
    CRM_Activity_BAO_Activity::sendEmail(
      [
        $contactID => [
          'preferred_mail_format' => 'Both',
          'contact_id' => $contactID,
          'email' => 'a@example.com',
        ],
      ],
      '{contact.first_name} {$contact.first_name}',
      '{contact.first_name} {$contact.first_name}',
      '{contact.first_name} {$contact.first_name}',
      NULL,
      NULL,
      'mail@example.com',
      NULL,
      NULL,
      NULL,
      [$contactID]
    );
    $activity = $this->callAPISuccessGetValue('Activity', ['return' => 'details']);
    putenv('CIVICRM_MAIL_SMARTY=0');
  }

  /**
   * Same as testSendEmailWillReplaceTokensUniquelyForEachContact but with
   * 3 recipients and an attachment.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSendEmailWillReplaceTokensUniquelyForEachContact3(): void {
    $contactId1 = $this->individualCreate(['last_name' => 'Red']);
    $contactId2 = $this->individualCreate(['last_name' => 'Pink']);
    $contactId3 = $this->individualCreate(['last_name' => 'Ochre']);

    // create a logged in USER since the code references it for sendEmail user.
    $loggedInUser = $this->createLoggedInUser();
    $contact = $this->callAPISuccess('Contact', 'get', ['sequential' => 1, 'id' => ['IN' => [$contactId1, $contactId2, $contactId3]]]);

    // Add contact tokens in subject, html , text.
    $subject = __FUNCTION__ . ' subject' . '{contact.display_name}';
    $html = __FUNCTION__ . ' html' . '{contact.display_name}';
    // Check the smarty doesn't mess stuff up.
    $text = ' text' . '{contact.display_name} {$contact.first_name}';

    $filepath = Civi::paths()->getPath('[civicrm.files]/custom');
    $fileName = 'test_email_create.txt';
    $fileUri = "{$filepath}/{$fileName}";
    // Create a file.
    CRM_Utils_File::createFakeFile($filepath, 'aaaaaa', $fileName);
    $attachments = [
      'attachFile_1' =>
      [
        'uri' => $fileUri,
        'type' => 'text/plain',
        'location' => $fileUri,
      ],
    ];

    CRM_Activity_BAO_Activity::sendEmail(
      $contact['values'],
      $subject,
      $text,
      $html,
      $contact['values'][0]['email'],
      $loggedInUser,
      __FUNCTION__ . '@example.com',
      $attachments,
      NULL,
      NULL,
      array_column($contact['values'], 'id'),
      NULL,
      NULL,
      $this->getCampaignID()
    );
    $result = $this->callAPISuccess('Activity', 'get', ['campaign_id' => $this->getCampaignID()]);
    // An activity created for each of the two contacts
    $this->assertEquals(3, $result['count']);
    $id = 0;
    foreach ($result['values'] as $activity) {
      $htmlValue = str_replace('{contact.display_name}', $contact['values'][$id]['display_name'], $html);
      $textValue = str_replace('{contact.display_name}', $contact['values'][$id]['display_name'], $text);
      $subjectValue = str_replace('{contact.display_name}', $contact['values'][$id]['display_name'], $subject);
      $details = "-ALTERNATIVE ITEM 0-
$htmlValue
-ALTERNATIVE ITEM 1-
$textValue
-ALTERNATIVE END-
";
      $this->assertEquals($activity['details'], $details, 'Activity details does not match.');
      $this->assertEquals($activity['subject'], $subjectValue, 'Activity subject does not match.');
      $id++;
    }

    unlink($fileUri);
  }

  /**
   * Checks that attachments are not duplicated for activities.
   */
  public function testSendEmailDoesNotDuplicateAttachmentFileIdsForActivitiesCreated() {
    $contactId1 = $this->individualCreate(['last_name' => 'Red']);
    $contactId2 = $this->individualCreate(['last_name' => 'Pink']);

    // create a logged in USER since the code references it for sendEmail user.
    $this->createLoggedInUser();
    $session = CRM_Core_Session::singleton();
    $loggedInUser = $session->get('userID');
    $contact = $this->callAPISuccess('Contact', 'get', ['sequential' => 1, 'id' => ['IN' => [$contactId1, $contactId2]]]);

    // Create a campaign.
    $result = $this->callAPISuccess('Campaign', 'create', [
      'version' => $this->_apiversion,
      'title' => __FUNCTION__ . ' campaign',
    ]);
    $campaign_id = $result['id'];

    $subject = __FUNCTION__ . ' subject';
    $html = __FUNCTION__ . ' html';
    $text = __FUNCTION__ . ' text';
    $userID = $loggedInUser;

    $filepath = Civi::paths()->getPath('[civicrm.files]/custom');
    $fileName = "test_email_create.txt";
    $fileUri = "{$filepath}/{$fileName}";
    // Create a file.
    CRM_Utils_File::createFakeFile($filepath, 'Bananas do not bend themselves without a little help.', $fileName);
    $attachments = [
      'attachFile_1' =>
        [
          'uri' => $fileUri,
          'type' => 'text/plain',
          'location' => $fileUri,
        ],
    ];

    CRM_Activity_BAO_Activity::sendEmail(
      $contact['values'],
      $subject,
      $text,
      $html,
      $contact['values'][0]['email'],
      $userID,
      $from = __FUNCTION__ . '@example.com',
      $attachments,
      $cc = NULL,
      $bcc = NULL,
      $contactIds = array_column($contact['values'], 'id'),
      $additionalDetails = NULL,
      NULL,
      $campaign_id
    );
    $result = $this->callAPISuccess('activity', 'get', ['campaign_id' => $campaign_id]);
    // An activity created for each of the two contacts, i.e two activities.
    $this->assertEquals(2, $result['count']);
    $activityIds = array_column($result['values'], 'id');
    $result = $this->callAPISuccess('Activity', 'get', [
      'return' => ['file_id'],
      'id' => ['IN' => $activityIds],
      'sequential' => 1,
    ]);

    // Verify that the that both activities are linked to the same File Id.
    $this->assertEquals($result['values'][0]['file_id'], $result['values'][1]['file_id']);
  }

  /**
   * Adds a case with one activity.
   *
   */
  protected function addCaseWithActivity() {
    // case is not enabled by default do that now.
    $enableResult = CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
    $this->assertTrue($enableResult, 'Cannot enable CiviCase in line ' . __LINE__);

    // We need a minimal case setup.
    $case_type_id = civicrm_api3('CaseType', 'get', ['return' => 'id', 'name' => 'test_case_type'])['id'] ?? NULL;
    if (!$case_type_id) {
      $params = [
        'name'  => 'test_case_type',
        'title' => 'test_case_type',
        "is_active" => "1",
        "definition" => [
          "activityTypes" => [
            ["name" => "Open Case", "max_instances" => "1"],
            ["name" => "Meeting"],
          ],
          "activitySets" => [
            [
              "name" => "standard_timeline",
              "label" => "Standard Timeline",
              "timeline" => "1",
              "activityTypes" => [
                [
                  "name" => "Open Case",
                  "status" => "Completed",
                  "label" => "Open Case",
                  "default_assignee_type" => "1",
                ],
              ],
            ],
          ],
          "timelineActivityTypes" => [
            [
              "name" => 'Open Case',
              "status" => 'Completed',
              "label" => 'Open Case',
              "default_assignee_type" => "1",
            ],
          ],
          "caseRoles" => [
            [
              "name" => "Case Coordinator",
              "creator" => 1,
              "manager" => 1,
            ],
          ],
        ],
      ];
      $case_type_id = $this->callAPISuccess('CaseType', 'create', $params)['id'];
    }

    // Create a case with Contact #3 as the client.
    $case_id = civicrm_api3('case', 'get', ['subject' => 'test case 1'])['id'] ?? NULL;
    if (!$case_id) {
      // Create case
      $params = [
        'subject'       => 'test case 1',
        'contact_id'    => 3,
        'status_id'     => 'Open',
        'case_type_id'  => $case_type_id,
        'creator_id'    => 3,
      ];
      $case_id = $this->callAPISuccess('case', 'create', $params)['id'];
    }

    // Create a scheduled 'Meeting' activity that belongs to this case, but is
    // assigned to contact #9
    $activity_id = $this->callAPISuccess('Activity', 'create', [
      'activity_type_id' => 'Meeting',
      'status_id' => 'Scheduled',
      'case_id' => $case_id,
      'source_contact_id' => 3,
      'assignee_id' => [9],
      'subject' => 'test meeting activity',
    ])['id'] ?? NULL;
  }

  /**
   * Change setting, and the cache of it.
   */
  protected function setShowCaseActivitiesInCore(bool $val) {
    Civi::settings()->set('civicaseShowCaseActivities', $val ? 1 : 0);
    CRM_Core_Component::getEnabledComponents();
    Civi::$statics['CRM_Core_Component']['info']['CiviCase'] = new CRM_Case_Info('CiviCase', 'CRM_Case', 7);
    Civi::$statics['CRM_Core_Component']['info']['CiviCase']->info['showActivitiesInCore'] = $val;
  }

  /**
   * Test multiple variations of target and assignee contacts in create
   * and edit mode.
   *
   * @dataProvider targetAndAssigneeProvider
   *
   * @param array $do_first
   * @param array $do_second
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function testTargetAssigneeVariations(array $do_first, array $do_second) {
    // Originally wanted to put this in setUp() but it broke other tests.
    $this->loggedInUserId = $this->createLoggedInUser();
    for ($i = 1; $i <= 4; $i++) {
      $this->someContacts[$i] = $this->individualCreate([], $i - 1, TRUE);
    }

    $params = [
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Meeting'),
      'subject' => 'Test Meeting',
      'source_contact_id' => $this->loggedInUserId,
    ];

    // Create an activity first if specified.
    $activity = NULL;
    if (!empty($do_first)) {
      if (!empty($do_first['targets'])) {
        // e.g. if it is [1], then pick $someContacts[1]. If it's [1,2], then
        // pick $someContacts[1] and $someContacts[2].
        $params['target_contact_id'] = array_values(array_intersect_key($this->someContacts, array_flip($do_first['targets'])));
      }
      if (!empty($do_first['assignees'])) {
        $params['assignee_contact_id'] = array_values(array_intersect_key($this->someContacts, array_flip($do_first['assignees'])));
      }

      $activity = CRM_Activity_BAO_Activity::create($params);
      $this->assertNotEmpty($activity->id);

      $params['id'] = $activity->id;
    }

    // Now do the second one, which will either create or update depending what
    // we did first.
    $params['target_contact_id'] = array_values(array_intersect_key($this->someContacts, array_flip($do_second['targets'])));
    $params['assignee_contact_id'] = array_values(array_intersect_key($this->someContacts, array_flip($do_second['assignees'])));
    $activity = CRM_Activity_BAO_Activity::create($params);

    // Check targets
    $queryParams = [
      1 => [$activity->id, 'Integer'],
      2 => [CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Targets'), 'Integer'],
    ];
    $this->assertEquals($params['target_contact_id'], array_column(CRM_Core_DAO::executeQuery('SELECT contact_id FROM civicrm_activity_contact WHERE activity_id = %1 AND record_type_id = %2', $queryParams)->fetchAll(), 'contact_id'));

    // Check assignees
    $queryParams = [
      1 => [$activity->id, 'Integer'],
      2 => [CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Assignees'), 'Integer'],
    ];
    $this->assertEquals($params['assignee_contact_id'], array_column(CRM_Core_DAO::executeQuery('SELECT contact_id FROM civicrm_activity_contact WHERE activity_id = %1 AND record_type_id = %2', $queryParams)->fetchAll(), 'contact_id'));

    // Clean up
    foreach ($this->someContacts as $cid) {
      $this->callAPISuccess('Contact', 'delete', ['id' => $cid]);
    }
  }

  /**
   * Same as testTargetAssigneeVariations but passes the target/assignee
   * in as a scalar when there's only one of them.
   *
   * @dataProvider targetAndAssigneeProvider
   *
   * @param array $do_first
   * @param array $do_second
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testTargetAssigneeVariationsWithScalars(array $do_first, array $do_second) {
    // Originally wanted to put this in setUp() but it broke other tests.
    $this->loggedInUserId = $this->createLoggedInUser();
    for ($i = 1; $i <= 4; $i++) {
      $this->someContacts[$i] = $this->individualCreate([], $i - 1, TRUE);
    }

    $params = [
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Meeting'),
      'subject' => 'Test Meeting',
      'source_contact_id' => $this->loggedInUserId,
    ];

    // Create an activity first if specified.
    $activity = NULL;
    if (!empty($do_first)) {
      if (!empty($do_first['targets'])) {
        // e.g. if it is [1], then pick $someContacts[1]. If it's [1,2], then
        // pick $someContacts[1] and $someContacts[2].
        $params['target_contact_id'] = array_values(array_intersect_key($this->someContacts, array_flip($do_first['targets'])));
        if (count($params['target_contact_id']) == 1) {
          $params['target_contact_id'] = $params['target_contact_id'][0];
        }
      }
      if (!empty($do_first['assignees'])) {
        $params['assignee_contact_id'] = array_values(array_intersect_key($this->someContacts, array_flip($do_first['assignees'])));
        if (count($params['assignee_contact_id']) == 1) {
          $params['assignee_contact_id'] = $params['assignee_contact_id'][0];
        }
      }

      $activity = CRM_Activity_BAO_Activity::create($params);
      $this->assertNotEmpty($activity->id);

      $params['id'] = $activity->id;
    }

    // Now do the second one, which will either create or update depending what
    // we did first.
    $params['target_contact_id'] = array_values(array_intersect_key($this->someContacts, array_flip($do_second['targets'])));
    if (count($params['target_contact_id']) == 1) {
      $params['target_contact_id'] = $params['target_contact_id'][0];
    }
    $params['assignee_contact_id'] = array_values(array_intersect_key($this->someContacts, array_flip($do_second['assignees'])));
    if (count($params['assignee_contact_id']) == 1) {
      $params['assignee_contact_id'] = $params['assignee_contact_id'][0];
    }
    $activity = CRM_Activity_BAO_Activity::create($params);

    // Check targets
    $queryParams = [
      1 => [$activity->id, 'Integer'],
      2 => [CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Targets'), 'Integer'],
    ];
    $this->assertEquals((array) $params['target_contact_id'], array_column(CRM_Core_DAO::executeQuery('SELECT contact_id FROM civicrm_activity_contact WHERE activity_id = %1 AND record_type_id = %2', $queryParams)->fetchAll(), 'contact_id'));

    // Check assignees
    $queryParams = [
      1 => [$activity->id, 'Integer'],
      2 => [CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Assignees'), 'Integer'],
    ];
    $this->assertEquals((array) $params['assignee_contact_id'], array_column(CRM_Core_DAO::executeQuery('SELECT contact_id FROM civicrm_activity_contact WHERE activity_id = %1 AND record_type_id = %2', $queryParams)->fetchAll(), 'contact_id'));

    // Clean up
    foreach ($this->someContacts as $cid) {
      $this->callAPISuccess('Contact', 'delete', ['id' => $cid]);
    }
  }

  /**
   * Dataprovider for testTargetAssigneeVariations
   * @return array
   */
  public function targetAndAssigneeProvider():array {
    return [
      // Explicit index so that it's easy to see which one has failed without
      // having to finger count.
      0 => [
        'do first' => [
          // Completely empty array means don't create any activity first,
          // as opposed to the ones we do later where "do first" has member
          // elements but those are empty, which means create an activity first
          // but with no contacts.
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [],
        ],
      ],
      1 => [
        'do first' => [],
        'do second' => [
          'targets' => [1],
          'assignees' => [],
        ],
      ],
      2 => [
        'do first' => [],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [],
        ],
      ],
      3 => [
        'do first' => [],
        'do second' => [
          'targets' => [],
          'assignees' => [3],
        ],
      ],
      4 => [
        'do first' => [],
        'do second' => [
          'targets' => [],
          'assignees' => [3, 4],
        ],
      ],
      5 => [
        'do first' => [],
        'do second' => [
          'targets' => [1],
          'assignees' => [3],
        ],
      ],
      6 => [
        'do first' => [],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [3],
        ],
      ],
      7 => [
        'do first' => [],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [3, 4],
        ],
      ],
      // The next sets test the same thing again but updating an activity
      // that has no contacts
      8 => [
        'do first' => [
          'targets' => [],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [],
        ],
      ],
      9 => [
        'do first' => [
          'targets' => [],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [1],
          'assignees' => [],
        ],
      ],
      10 => [
        'do first' => [
          'targets' => [],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [],
        ],
      ],
      11 => [
        'do first' => [
          'targets' => [],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [3],
        ],
      ],
      12 => [
        'do first' => [
          'targets' => [],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [3, 4],
        ],
      ],
      13 => [
        'do first' => [
          'targets' => [],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [1],
          'assignees' => [3],
        ],
      ],
      14 => [
        'do first' => [
          'targets' => [],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [3],
        ],
      ],
      15 => [
        'do first' => [
          'targets' => [],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [3, 4],
        ],
      ],
      // And again but updating an activity with 1 contact
      16 => [
        'do first' => [
          'targets' => [1],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [],
        ],
      ],
      17 => [
        'do first' => [
          'targets' => [1],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [1],
          'assignees' => [],
        ],
      ],
      18 => [
        'do first' => [
          'targets' => [1],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [],
        ],
      ],
      19 => [
        'do first' => [
          'targets' => [1],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [3],
        ],
      ],
      20 => [
        'do first' => [
          'targets' => [1],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [3, 4],
        ],
      ],
      21 => [
        'do first' => [
          'targets' => [1],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [1],
          'assignees' => [3],
        ],
      ],
      22 => [
        'do first' => [
          'targets' => [1],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [3],
        ],
      ],
      23 => [
        'do first' => [
          'targets' => [1],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [3, 4],
        ],
      ],
      24 => [
        'do first' => [
          'targets' => [1],
          'assignees' => [],
        ],
        'do second' => [
          // a little different variation where we're changing the target as
          // opposed to adding one or deleting
          'targets' => [2],
          'assignees' => [],
        ],
      ],
      // And again but updating an activity with 2 contacts
      25 => [
        'do first' => [
          'targets' => [1, 2],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [],
        ],
      ],
      26 => [
        'do first' => [
          'targets' => [1, 2],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [1],
          'assignees' => [],
        ],
      ],
      27 => [
        'do first' => [
          'targets' => [1, 2],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [],
        ],
      ],
      28 => [
        'do first' => [
          'targets' => [1, 2],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [3],
        ],
      ],
      29 => [
        'do first' => [
          'targets' => [1, 2],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [3, 4],
        ],
      ],
      30 => [
        'do first' => [
          'targets' => [1, 2],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [1],
          'assignees' => [3],
        ],
      ],
      31 => [
        'do first' => [
          'targets' => [1, 2],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [3],
        ],
      ],
      32 => [
        'do first' => [
          'targets' => [1, 2],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [3, 4],
        ],
      ],
      33 => [
        'do first' => [
          'targets' => [1, 2],
          'assignees' => [],
        ],
        'do second' => [
          'targets' => [2],
          'assignees' => [],
        ],
      ],
      // And again but now start with assignees
      34 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3],
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [],
        ],
      ],
      35 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3],
        ],
        'do second' => [
          'targets' => [1],
          'assignees' => [],
        ],
      ],
      36 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3],
        ],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [],
        ],
      ],
      37 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3],
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [3],
        ],
      ],
      38 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3],
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [3, 4],
        ],
      ],
      39 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3],
        ],
        'do second' => [
          'targets' => [1],
          'assignees' => [3],
        ],
      ],
      40 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3],
        ],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [3],
        ],
      ],
      41 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3],
        ],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [3, 4],
        ],
      ],
      42 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3],
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [4],
        ],
      ],
      // And again but now start with 2 assignees
      43 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3, 4],
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [],
        ],
      ],
      44 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3, 4],
        ],
        'do second' => [
          'targets' => [1],
          'assignees' => [],
        ],
      ],
      45 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3, 4],
        ],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [],
        ],
      ],
      46 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3, 4],
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [3],
        ],
      ],
      47 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3, 4],
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [3, 4],
        ],
      ],
      48 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3, 4],
        ],
        'do second' => [
          'targets' => [1],
          'assignees' => [3],
        ],
      ],
      49 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3, 4],
        ],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [3],
        ],
      ],
      50 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3, 4],
        ],
        'do second' => [
          'targets' => [1, 2],
          'assignees' => [3, 4],
        ],
      ],
      51 => [
        'do first' => [
          'targets' => [],
          'assignees' => [3, 4],
        ],
        'do second' => [
          'targets' => [],
          'assignees' => [4],
        ],
      ],
    ];
  }

  /**
   * Test the returned activity ids when there are multiple "To" recipients.
   * Similar to testSendEmailWillReplaceTokensUniquelyForEachContact but we're
   * checking the activity ids returned from sendEmail.
   */
  public function testSendEmailWithMultipleToRecipients(): void {
    $contactId1 = $this->individualCreate(['first_name' => 'Aaaa', 'last_name' => 'Bbbb']);
    $contactId2 = $this->individualCreate(['first_name' => 'Cccc', 'last_name' => 'Dddd']);

    // create a logged in USER since the code references it for sendEmail user.
    $loggedInUser = $this->createLoggedInUser();
    $contacts = $this->callAPISuccess('Contact', 'get', [
      'sequential' => 1,
      'id' => ['IN' => [$contactId1, $contactId2]],
    ]);

    [$sent, $activityIds] = CRM_Activity_BAO_Activity::sendEmail(
      $contacts['values'],
      'a subject',
      'here is some text',
      '<p>here is some html</p>',
      $contacts['values'][0]['email'],
      $loggedInUser,
      $from = __FUNCTION__ . '@example.com',
      $attachments = NULL,
      $cc = NULL,
      $bcc = NULL,
      array_column($contacts['values'], 'id')
    );

    // Get all activities for these contacts
    $result = $this->callAPISuccess('activity', 'get', [
      'sequential' => 1,
      'return' => ['target_contact_id'],
      'target_contact_id' => ['IN' => [$contactId1, $contactId2]],
    ]);

    // There should be one activity created for each of the two contacts
    $this->assertEquals(2, $result['count']);

    // Activity ids returned from sendEmail should match the ones returned from api call.
    $this->assertEquals($activityIds, array_column($result['values'], 'id'));

    // Is it the right contacts?
    $this->assertEquals(
      [0 => [0 => $contactId1], 1 => [0 => $contactId2]],
      array_column($result['values'], 'target_contact_id')
    );
    $this->assertEquals(
      [0 => [$contactId1 => 'Bbbb, Aaaa'], 1 => [$contactId2 => 'Dddd, Cccc']],
      array_column($result['values'], 'target_contact_sort_name')
    );
  }

  /**
   * @param $activityId
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function validateActivity($activityId): void {
    $activity = Activity::get(FALSE)
      ->addSelect('activity_type_id', 'status_id', 'subject', 'details')
      ->addWhere('id', '=', $activityId)
      ->execute()->first();

    $outBoundSmsActivityId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'SMS');
    $activityStatusCompleted = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed');
    $this->assertEquals($outBoundSmsActivityId, $activity['activity_type_id'], 'Wrong activity type is set.');
    $this->assertEquals($activityStatusCompleted, $activity['status_id'], 'Expected activity status Completed.');
    $this->assertEquals('subject', $activity['subject'], 'Activity subject does not match.');
    // Token is not resolved here.
    $this->assertEquals('text {contact.first_name}', $activity['details'], 'Activity details does not match.');
  }

  /**
   * @param int $contactId
   * @param bool $passPhoneTypeInContactDetails
   * @param $contactDetails
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  protected function createMobilePhone(int $contactId, bool $passPhoneTypeInContactDetails, $contactDetails): array {
    $phone = civicrm_api3('Phone', 'create', [
      'contact_id' => $contactId,
      'phone' => 123456,
      'phone_type_id' => 'Mobile',
    ]);
    if ($passPhoneTypeInContactDetails) {
      $contactDetails[$contactId]['phone'] = $phone['values'][$phone['id']]['phone'];
      $contactDetails[$contactId]['phone_type_id'] = $phone['values'][$phone['id']]['phone_type_id'];
    }
    return $contactDetails;
  }

  /**
   * Get a campaign id - creating one if need be.
   *
   * @return int
   */
  protected function getCampaignID() {
    if (!isset($this->ids['Campaign'][0])) {
      $this->ids['Campaign'][0] = $this->callAPISuccess('Campaign', 'create', [
        'title' => 'campaign',
      ])['id'];
    }
    return $this->ids['Campaign'][0];
  }

  /**
   * @param int $contactId
   * @param array $submittedValues
   *
   * @return \CRM_Case_Form_Task_Email
   */
  protected function getCaseEmailTaskForm(int $contactId, array $submittedValues): CRM_Case_Form_Task_Email {
    $_REQUEST['cid'] = $contactId;
    $_REQUEST['caseid'] = $this->getCaseID();
    /* @var CRM_Case_Form_Task_Email $form */
    $form = $this->getFormObject('CRM_Case_Form_Task_Email', array_merge([
      'to' => $contactId . '::' . 'email@example.com',
      'from_email_address' => 'from@example.com',
    ], $submittedValues));
    $form->buildForm();
    return $form;
  }

  /**
   * @param int $contactId
   * @param array $submittedValues
   *
   * @return \CRM_Contact_Form_Task_Email
   */
  protected function getContactEmailTaskForm(int $contactId, array $submittedValues): CRM_Contact_Form_Task_Email {
    $_REQUEST['cid'] = $contactId;
    /* @var CRM_Contact_Form_Task_Email $form */
    $form = $this->getFormObject('CRM_Contact_Form_Task_Email', array_merge([
      'to' => $contactId . '::' . 'email@example.com',
      'from_email_address' => 'from@example.com',
    ], $submittedValues));
    $form->buildForm();
    return $form;
  }

}
