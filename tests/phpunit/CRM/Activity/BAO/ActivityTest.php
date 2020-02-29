<?php

/**
 * Class CRM_Activity_BAO_ActivityTest
 * @group headless
 */
class CRM_Activity_BAO_ActivityTest extends CiviUnitTestCase {

  private $allowedContactsACL = [];

  public function setUp() {
    parent::setUp();
    $this->prepareForACLs();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['view all contacts', 'access CiviCRM'];
    $this->setupForSmsTests();
  }

  /**
   * Clean up after tests.
   */
  public function tearDown() {
    $tablesToTruncate = [
      'civicrm_activity',
      'civicrm_activity_contact',
      'civicrm_uf_match',
      'civicrm_campaign',
      'civicrm_email',
    ];
    $this->quickCleanup($tablesToTruncate);
    $this->cleanUpAfterACLs();
    $this->setupForSmsTests(TRUE);
    parent::tearDown();
  }

  /**
   * Test case for create() method.
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
   * getContactActivity() method get activities detail for given target contact id.
   */
  public function testGetContactActivity() {
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
  public function testRetrieve() {
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
   * Test case for deleteActivity() method.
   *
   * deleteActivity($params) method deletes activity for given params.
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
   * Test case for deleteActivityTarget() method.
   *
   * deleteActivityTarget($activityId) method deletes activity target for given activity id.
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
   * deleteActivityAssignment($activityId) method deletes activity assignment for given activity id.
   */
  public function testDeleteActivityAssignment() {
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

    // If we're showing case activities, we exepct to see one more (the scheduled meeting)...
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
      'is_unit_test' => 1,
    ];
    $expectedFilters = [
      'activity_type_filter_id' => 1,
    ];

    list($activities, $activityFilter) = CRM_Activity_Page_AJAX::getContactActivity();
    //Assert whether filters are correctly set.
    $this->checkArrayEquals($expectedFilters, $activityFilter);
    // This should include activities of type Meeting only.
    foreach ($activities['data'] as $value) {
      $this->assertContains('Meeting', $value['activity_type']);
    }
    unset($_GET['activity_type_id']);

    $_GET['activity_type_exclude_id'] = $expectedFilters['activity_type_exclude_filter_id'] = 1;
    list($activities, $activityFilter) = CRM_Activity_Page_AJAX::getContactActivity();
    $this->assertEquals(['activity_type_exclude_filter_id' => 1], $activityFilter);
    // None of the activities should be of type Meeting.
    foreach ($activities['data'] as $value) {
      $this->assertNotContains('Meeting', $value['activity_type']);
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
      $this->assertEquals($count, count($activities));

      foreach ($activities as $key => $value) {
        $this->assertEquals($value['subject'], "subject {$key}", 'Verify activity subject is correct.');
        $this->assertEquals($value['activity_type_id'], 2, 'Verify activity type is correct.');
        $this->assertEquals($value['status_id'], 1, 'Verify all activities are scheduled.');
      }
    }

    // Now check that we get the scheduled meeting, if civicaseShowCaseActivities is set.
    $this->setShowCaseActivitiesInCore(TRUE);
    $activitiesNew = CRM_Activity_BAO_Activity::getActivities($this->_params);
    $this->assertEquals(9, count($activitiesNew));
    // Scan through to find the meeting.
    $this->assertTrue(in_array('test meeting activity', array_column($activitiesNew, 'subject')),
      "failed to find scheduled case Meeting activity");
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
      $this->assertEquals(0, count($activities));
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
        if ($caseName == 'with-no-activity') {
          $this->assertEquals(0, count($activities));
          $this->assertEquals(0, $activityCount);
        }
        elseif ($caseName == 'with-activity') {
          // contact id 1 is assigned as source, target and assignee for activity id 1, 7 and 8 respectively
          $this->assertEquals(3, count($activities));
          $this->assertEquals(3, $activityCount);
          $this->assertEquals(1, $activities[1]['source_contact_id']);
          // @todo - this is a discrepancy between old & new - review.
          //$this->assertEquals(TRUE, array_key_exists(1, $activities[7]['target_contact_name']));
          $this->assertEquals(TRUE, array_key_exists(1, $activities[8]['assignee_contact_name']));
        }
        elseif ($caseName == 'with-activity_type') {
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
        if ($caseName == 'exclude-all-activity_type') {
          $this->assertEquals(0, count($activities));
          $this->assertEquals(0, $activityCount);
        }
        if ($caseName == 'sort-by-subject') {
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
   */
  public function testByActivityDateAndStatus() {
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
    $lastOrNextYearDate = (date('M') == 'Jan') ? date('YmdHis', strtotime('+1 year')) : date('YmdHis', strtotime('1 year ago'));
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

      if ($caseName == 'todays-activity' || $caseName == 'todays-activity-filtered-by-range') {
        // Only one of the 4 activities today relates to contact id 1.
        $this->assertEquals(1, $activityCount);
        $this->assertEquals(1, count($activities));
        $this->assertEquals([7], array_keys($activities));
      }
      elseif ($caseName == 'last-week-activity') {
        // Only one of the 3 activities today relates to contact id 1.
        $this->assertEquals(1, $activityCount);
        $this->assertEquals(1, count($activities));
        $this->assertEquals([1], $activityIDs);
      }
      elseif ($caseName == 'lhis-quarter-activity') {
        $this->assertEquals(count($lastTwoMonthsActivities), $activityCount);
        $this->assertEquals(count($lastTwoMonthsActivities), count($activities));
        $this->checkArrayEquals($lastTwoMonthsActivities, $activityIDs);
      }
      elseif ($caseName == 'last-or-next-year-activity') {
        $this->assertEquals(count($lastOrNextYearActivities), $activityCount);
        $this->assertEquals(count($lastOrNextYearActivities), count($activities));
        $this->checkArrayEquals($lastOrNextYearActivities, $activityIDs);
      }
      elseif ($caseName == 'activity-of-all-statuses') {
        $this->assertEquals(3, $activityCount);
        $this->assertEquals(3, count($activities));
      }
    }
  }

  /**
   * @dataProvider getActivityDateData
   */
  public function testActivityRelativeDateFilter($params, $expected) {
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
   */
  public function testEmailAddressOfActivityCopy() {
    // Case 1: assert the 'From' Email Address of source Actvity Contact ID
    // create activity with source contact ID which has email address
    $assigneeContactId = $this->individualCreate();
    $sourceContactParams = [
      'first_name' => 'liz',
      'last_name' => 'hurleey',
      'email' => substr(sha1(rand()), 0, 7) . '@testemail.com',
    ];
    $sourceContactID = $this->individualCreate($sourceContactParams);
    $sourceDisplayName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $sourceContactID, 'display_name');

    // create an activity using API
    $params = [
      'source_contact_id' => $sourceContactID,
      'subject' => 'Scheduling Meeting ' . substr(sha1(rand()), 0, 4),
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Meeting'),
      'assignee_contact_id' => [$assigneeContactId],
      'activity_date_time' => date('Ymd'),
    ];
    $activity = $this->callAPISuccess('Activity', 'create', $params);

    // Check that from address is in "Source-Display-Name <source-email>"
    $formAddress = CRM_Case_BAO_Case::getReceiptFrom($activity['id']);
    $expectedFromAddress = sprintf("%s <%s>", $sourceDisplayName, $sourceContactParams['email']);
    $this->assertEquals($expectedFromAddress, $formAddress);

    // Case 2: System Default From Address
    //  but first erase the email address of existing source contact ID
    $withoutEmailParams = [
      'email' => '',
    ];
    $sourceContactID = $this->individualCreate($withoutEmailParams);
    $params = [
      'source_contact_id' => $sourceContactID,
      'subject' => 'Scheduling Meeting ' . substr(sha1(rand()), 0, 4),
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Meeting'),
      'activity_date_time' => date('Ymd'),
    ];
    $activity = $this->callAPISuccess('Activity', 'create', $params);
    // fetch domain info
    $domainInfo = $this->callAPISuccess('Domain', 'getsingle', ['id' => CRM_Core_Config::domainID()]);

    $formAddress = CRM_Case_BAO_Case::getReceiptFrom($activity['id']);
    if (!empty($domainInfo['from_email'])) {
      $expectedFromAddress = sprintf("%s <%s>", $domainInfo['from_name'], $domainInfo['from_email']);
    }
    // Case 3: fetch default Organization Contact email address
    elseif (!empty($domainInfo['domain_email'])) {
      $expectedFromAddress = sprintf("%s <%s>", $domainInfo['name'], $domainInfo['domain_email']);
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

  public function testSendEmailBasic() {
    $contactId = $this->individualCreate();

    // create a logged in USER since the code references it for sendEmail user.
    $this->createLoggedInUser();
    $session = CRM_Core_Session::singleton();
    $loggedInUser = $session->get('userID');

    $contact = $this->civicrm_api('contact', 'getsingle', ['id' => $contactId, 'version' => $this->_apiversion]);
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
    $contactDetails = [
      array_intersect_key($contact, $contactDetailsIntersectKeys),
    ];

    $subject = __FUNCTION__ . ' subject';
    $html = __FUNCTION__ . ' html';
    $text = __FUNCTION__ . ' text';
    $userID = $loggedInUser;

    list($sent, $activity_id) = $email_result = CRM_Activity_BAO_Activity::sendEmail(
      $contactDetails,
      $subject,
      $text,
      $html,
      $contact['email'],
      $userID,
      $from = __FUNCTION__ . '@example.com'
    );

    $activity = $this->civicrm_api('activity', 'getsingle', ['id' => $activity_id, 'version' => $this->_apiversion]);
    $details = "-ALTERNATIVE ITEM 0-
$html
-ALTERNATIVE ITEM 1-
$text
-ALTERNATIVE END-
";
    $this->assertEquals($activity['details'], $details, 'Activity details does not match.');
    $this->assertEquals($activity['subject'], $subject, 'Activity subject does not match.');
  }

  public function testSendEmailWithCampaign() {
    // Create a contact and contactDetails array.
    $contactId = $this->individualCreate();

    // create a logged in USER since the code references it for sendEmail user.
    $this->createLoggedInUser();
    $session = CRM_Core_Session::singleton();
    $loggedInUser = $session->get('userID');

    $contact = $this->civicrm_api('contact', 'getsingle', ['id' => $contactId, 'version' => $this->_apiversion]);
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
    $contactDetails = [
      array_intersect_key($contact, $contactDetailsIntersectKeys),
    ];

    // Create a campaign.
    $result = $this->civicrm_api('Campaign', 'create', [
      'version' => $this->_apiversion,
      'title' => __FUNCTION__ . ' campaign',
    ]);
    $campaign_id = $result['id'];

    $subject = __FUNCTION__ . ' subject';
    $html = __FUNCTION__ . ' html';
    $text = __FUNCTION__ . ' text';
    $userID = $loggedInUser;

    list($sent, $activity_id) = $email_result = CRM_Activity_BAO_Activity::sendEmail(
      $contactDetails,
      $subject,
      $text,
      $html,
      $contact['email'],
      $userID,
      $from = __FUNCTION__ . '@example.com',
      $attachments = NULL,
      $cc = NULL,
      $bcc = NULL,
      $contactIds = NULL,
      $additionalDetails = NULL,
      NULL,
      $campaign_id
    );
    $activity = $this->civicrm_api('activity', 'getsingle', ['id' => $activity_id, 'version' => $this->_apiversion]);
    $this->assertEquals($activity['campaign_id'], $campaign_id, 'Activity campaign_id does not match.');
  }

  /**
   * @expectedException CRM_Core_Exception
   * @expectedExceptionMessage You do not have the 'send SMS' permission
   */
  public function testSendSMSWithoutPermission() {
    $dummy = NULL;
    $session = CRM_Core_Session::singleton();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];

    CRM_Activity_BAO_Activity::sendSMS(
      $dummy,
      $dummy,
      $dummy,
      $dummy,
      $session->get('userID')
    );
  }

  public function testSendSmsNoPhoneNumber() {
    list($sent, $activityId, $success) = $this->createSendSmsTest(0);
    $activity = $this->civicrm_api('activity', 'getsingle', ['id' => $activityId, 'version' => $this->_apiversion]);

    $outBoundSmsActivityId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'SMS');
    $activityStatusCompleted = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed');
    $details = 'createSendSmsTest text';
    $this->assertEquals($activity['activity_type_id'], $outBoundSmsActivityId, 'Wrong activity type is set.');
    $this->assertEquals($activity['status_id'], $activityStatusCompleted, 'Expected activity status Completed.');
    $this->assertEquals($activity['subject'], 'createSendSmsTest subject', 'Activity subject does not match.');
    $this->assertEquals($activity['details'], $details, 'Activity details does not match.');
    $this->assertEquals("Recipient phone number is invalid or recipient does not want to receive SMS", $sent[0], "Expected error doesn't match");
    $this->assertEquals(0, $success, "Expected success to be 0");
  }

  public function testSendSmsFixedPhoneNumber() {
    list($sent, $activityId, $success) = $this->createSendSmsTest(1);
    $activity = $this->civicrm_api('activity', 'getsingle', ['id' => $activityId, 'version' => $this->_apiversion]);

    $outBoundSmsActivityId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'SMS');
    $activityStatusCompleted = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed');
    $details = 'createSendSmsTest text';
    $this->assertEquals($activity['activity_type_id'], $outBoundSmsActivityId, 'Wrong activity type is set.');
    $this->assertEquals($activity['status_id'], $activityStatusCompleted, 'Expected activity status Completed.');
    $this->assertEquals($activity['subject'], 'createSendSmsTest subject', 'Activity subject does not match.');
    $this->assertEquals($activity['details'], $details, 'Activity details does not match.');
    $this->assertEquals("Recipient phone number is invalid or recipient does not want to receive SMS", $sent[0], "Expected error doesn't match");
    $this->assertEquals(0, $success, "Expected success to be 0");
  }

  public function testSendSmsMobilePhoneNumber() {
    list($sent, $activityId, $success) = $this->createSendSmsTest(2);
    $activity = $this->civicrm_api('activity', 'getsingle', ['id' => $activityId, 'version' => $this->_apiversion]);

    $outBoundSmsActivityId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'SMS');
    $activityStatusCompleted = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed');
    $details = 'createSendSmsTest text';
    $this->assertEquals($activity['activity_type_id'], $outBoundSmsActivityId, 'Wrong activity type is set.');
    $this->assertEquals($activity['status_id'], $activityStatusCompleted, 'Expected activity status Completed.');
    $this->assertEquals($activity['subject'], 'createSendSmsTest subject', 'Activity subject does not match.');
    $this->assertEquals($activity['details'], $details, 'Activity details does not match.');
    $this->assertEquals(TRUE, $sent, "Expected sent should be true");
    $this->assertEquals(1, $success, "Expected success to be 1");
  }

  /**
   * Test that when a numbe ris specified in the To Param of the SMS provider parameters that an SMS is sent
   * @see dev/core/#273
   */
  public function testSendSMSMobileInToProviderParam() {
    list($sent, $activityId, $success) = $this->createSendSmsTest(2, TRUE);
    $this->assertEquals(TRUE, $sent, "Expected sent should be true");
    $this->assertEquals(1, $success, "Expected success to be 1");
  }

  /**
   * Test that when a numbe ris specified in the To Param of the SMS provider parameters that an SMS is sent
   * @see dev/core/#273
   */
  public function testSendSMSMobileInToProviderParamWithDoNotSMS() {
    list($sent, $activityId, $success) = $this->createSendSmsTest(2, TRUE, ['do_not_sms' => 1]);
    foreach ($sent as $error) {
      $this->assertEquals('Contact Does not accept SMS', $error);
    }
    $this->assertEquals(1, count($sent), "Expected sent should a PEAR Error");
    $this->assertEquals(0, $success, "Expected success to be 0");
  }

  /**
   * @param int $phoneType (0=no phone, phone_type option group (1=fixed, 2=mobile)
   * @param bool $passPhoneTypeInContactDetails
   * @param array $additionalContactParams additional contact creation params
   */
  public function createSendSmsTest($phoneType = 0, $passPhoneTypeInContactDetails = FALSE, $additionalContactParams = []) {
    $provider = civicrm_api3('SmsProvider', 'create', [
      'name' => "CiviTestSMSProvider",
      'api_type' => "1",
      "username" => "1",
      "password" => "1",
      "api_type" => "1",
      "api_url" => "1",
      "api_params" => "a=1",
      "is_default" => "1",
      "is_active" => "1",
      "domain_id" => "1",
    ]);

    $smsProviderParams['provider_id'] = $provider['id'];

    // Create a contact
    $contactId = $this->individualCreate();
    if (!empty($additionalContactParams)) {
      $this->callAPISuccess('contact', 'create', ['id' => $contactId] + $additionalContactParams);
    }
    $contactsResult = $this->callApiSuccess('contact', 'get', ['id' => $contactId, 'version' => $this->_apiversion]);
    $contactDetails = $contactsResult['values'];

    // Get contactIds from contact details
    foreach ($contactDetails as $contact) {
      $contactIds[] = $contact['contact_id'];
    }

    $activityParams['sms_text_message'] = __FUNCTION__ . ' text';
    $activityParams['activity_subject'] = __FUNCTION__ . ' subject';

    // Get a "logged in" user to set as source of Sms.
    $session = CRM_Core_Session::singleton();
    $sourceContactId = $session->get('userID');

    // Create a user
    $this->_testSmsContactId = $this->createLoggedInUser();

    // Give user permission to 'send SMS'
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'send SMS'];

    // Create a phone number
    switch ($phoneType) {
      case 0:
        // No phone number
        break;

      case 2:
        // Create a mobile phone number
        $phone = civicrm_api3('Phone', 'create', [
          'contact_id' => $contactId,
          'phone' => 123456,
          'phone_type_id' => "Mobile",
        ]);
        if ($passPhoneTypeInContactDetails) {
          $contactDetails[$contactId]['phone'] = $phone['values'][$phone['id']]['phone'];
          $contactDetails[$contactId]['phone_type_id'] = $phone['values'][$phone['id']]['phone_type_id'];
        }
        break;

      case 1:
        // Create a fixed phone number
        $phone = civicrm_api3('Phone', 'create', [
          'contact_id' => $contactId,
          'phone' => 654321,
          'phone_type_id' => "Phone",
        ]);
        if ($passPhoneTypeInContactDetails) {
          $contactDetails[$contactId]['phone'] = $phone['values'][$phone['id']]['phone'];
          $contactDetails[$contactId]['phone_type_id'] = $phone['values'][$phone['id']]['phone_type_id'];
        }
        break;
    }

    // Now run the actual test
    list($sent, $activityId, $success) = CRM_Activity_BAO_Activity::sendSms(
      $contactDetails,
      $activityParams,
      $smsProviderParams,
      $contactIds,
      $sourceContactId
    );

    return [$sent, $activityId, $success];
  }

  protected function createTestActivities() {
    $this->loadXMLDataSet(dirname(__FILE__) . '/activities_for_dashboard_count.xml');
    // Make changes to improve variation in php since the xml method is brittle & relies on option values being unchanged.
    $this->callAPISuccess('Activity', 'create', ['id' => 12, 'activity_type_id' => 'Bulk Email']);
  }

  /**
   * ACL HOOK implementation for various tests
   */
  public function hook_civicrm_aclWhereClause($type, &$tables, &$whereTables, &$contactID, &$where) {
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
    $contact = $this->callAPISuccess('Contact', 'get', ['id' => $contactId]);

    // create a logged in USER since the code references it for sendEmail user.
    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['view all contacts', 'access CiviCRM', 'access all cases and activities', 'administer CiviCase'];
    $session = CRM_Core_Session::singleton();
    $loggedInUser = $session->get('userID');

    // create a case for this user
    $result = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $contactId,
      'case_type_id' => 1,
      'subject' => "my case",
      'status_id' => "Open",
    ]);

    $caseId = $result['id'];

    $subject = __FUNCTION__ . ' subject {case.subject}';
    $html = __FUNCTION__ . ' html {case.subject}';
    $text = __FUNCTION__ . ' text';

    $mut = new CiviMailUtils($this, TRUE);
    list($sent, $activity_id) = $email_result = CRM_Activity_BAO_Activity::sendEmail(
      $contact['values'],
      $subject,
      $text,
      $html,
      $contact['values'][$contactId]['email'],
      $loggedInUser,
      $from = __FUNCTION__ . '@example.com',
      NULL,
      NULL,
      NULL,
      [$contactId],
      NULL,
      NULL,
      NULL,
      $caseId
    );
    $activity = $this->callAPISuccess('Activity', 'getsingle', ['id' => $activity_id, 'return' => ['case_id']]);
    $this->assertEquals($caseId, $activity['case_id'][0], 'Activity case_id does not match.');
    $mut->checkMailLog(['subject my case']);
    $mut->stop();
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
              "name" => "Open Case",
              "status" => "Completed",
              "label" => "Open Case",
              "default_assignee_type" => "1",
            ],
          ],
          "caseRoles" => [
            [
              "name" => "Case Coordinator",
              "creator" => "1",
              "manager" => "1",
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
    Civi::$statics['CRM_Core_Component']['info']['CiviCase']->info['showActivitiesInCore'] = $val;
  }

}
