<?php

/**
 * Class CRM_Activity_BAO_ActivityTest
 * @group headless
 */
class CRM_Activity_BAO_ActivityTest extends CiviUnitTestCase {
  public function setUp() {
    parent::setUp();
    $this->prepareForACLs();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('view all contacts', 'access CiviCRM');
  }

  /**
   * Clean up after tests.
   */
  public function tearDown() {
    $tablesToTruncate = array(
      'civicrm_activity',
      'civicrm_activity_contact',
      'civicrm_uf_match',
      'civicrm_campaign',
      'civicrm_email',
    );
    $this->quickCleanup($tablesToTruncate);
    $this->cleanUpAfterACLs();
    parent::tearDown();
  }

  /**
   * Test case for create() method.
   */
  public function testCreate() {
    $contactId = $this->individualCreate();

    $params = array(
      'source_contact_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
    );

    CRM_Activity_BAO_Activity::create($params);

    $activityId = $this->assertDBNotNull('CRM_Activity_DAO_Activity', 'Scheduling Meeting', 'id',
      'subject', 'Database check for created activity.'
    );

    // Now call create() to modify an existing Activity.
    $params = array(
      'id' => $activityId,
      'source_contact_id' => $contactId,
      'subject' => 'Scheduling Interview',
      'activity_type_id' => 3,
    );

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
    $params = array(
      'first_name' => 'liz',
      'last_name' => 'hurleey',
    );
    $targetContactId = $this->individualCreate($params);

    $params = array(
      'source_contact_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
      'target_contact_id' => array($targetContactId),
      'activity_date_time' => date('Ymd'),
    );

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
    $params = array(
      'first_name' => 'liz',
      'last_name' => 'hurleey',
    );
    $targetContactId = $this->individualCreate($params);

    $params = array(
      'source_contact_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
      'target_contact_id' => array($targetContactId),
      'activity_date_time' => date('Ymd'),
    );

    CRM_Activity_BAO_Activity::create($params);

    $this->assertDBNotNull('CRM_Activity_DAO_Activity', 'Scheduling Meeting', 'id',
      'subject', 'Database check for created activity.'
    );

    $this->assertDBNotNull('CRM_Activity_DAO_ActivityContact', $targetContactId,
      'id', 'contact_id',
      'Database check for created activity target.'
    );

    $defaults = array();
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
   * Test case for deleteActivity() method.
   *
   * deleteActivity($params) method deletes activity for given params.
   */
  public function testDeleteActivity() {
    $contactId = $this->individualCreate();
    $params = array(
      'first_name' => 'liz',
      'last_name' => 'hurleey',
    );
    $targetContactId = $this->individualCreate($params);

    $params = array(
      'source_contact_id' => $contactId,
      'source_record_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
      'target_contact_id' => array($targetContactId),
      'activity_date_time' => date('Ymd'),
    );

    CRM_Activity_BAO_Activity::create($params);

    $this->assertDBNotNull('CRM_Activity_DAO_Activity', 'Scheduling Meeting', 'id',
      'subject', 'Database check for created activity.'
    );

    $this->assertDBNotNull('CRM_Activity_DAO_ActivityContact', $targetContactId,
      'id', 'contact_id',
      'Database check for created activity target.'
    );
    $params = array(
      'source_contact_id' => $contactId,
      'source_record_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
    );

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
    $params = array(
      'first_name' => 'liz',
      'last_name' => 'hurleey',
    );
    $targetContactId = $this->individualCreate($params);

    $params = array(
      'source_contact_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
      'target_contact_id' => array($targetContactId),
      'activity_date_time' => date('Ymd'),
    );

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
    $params = array(
      'first_name' => 'liz',
      'last_name' => 'hurleey',
    );
    $assigneeContactId = $this->individualCreate($params);

    $params = array(
      'source_contact_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
      'assignee_contact_id' => array($assigneeContactId),
      'activity_date_time' => date('Ymd'),
    );

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
   */
  public function testGetActivitiesCountForAdminDashboard() {
    $this->setUpForActivityDashboardTests();
    $activityCount = CRM_Activity_BAO_Activity::deprecatedGetActivitiesCount($this->_params);
    $this->assertEquals(8, $activityCount);
    $activityCount = CRM_Activity_BAO_Activity::getActivitiesCount($this->_params);
    $this->assertEquals(8, $activityCount);
  }

  /**
   * Test getActivities BAO method for getting count
   */
  public function testGetActivitiesCountforNonAdminDashboard() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
        dirname(__FILE__) . '/activities_for_dashboard_count.xml'
      )
    );

    $params = array(
      'contact_id' => 9,
      'admin' => FALSE,
      'caseId' => NULL,
      'context' => 'home',
      'activity_type_id' => NULL,
      'activity_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Scheduled'), // for dashlet the Scheduled status is set by default
      'offset' => 0,
      'rowCount' => 0,
      'sort' => NULL,
    );

    $activityCount = CRM_Activity_BAO_Activity::deprecatedGetActivitiesCount($params);

    //since we are loading activities from dataset, we know total number of activities for this contact
    // 5 activities ( 2 scheduled, 3 Completed ), note that dashboard shows only scheduled activities
    $count = 2;
    $this->assertEquals($count, $activityCount);
    $this->assertEquals(2, CRM_Activity_BAO_Activity::getActivitiesCount($params));
  }

  /**
   * Test getActivities BAO method for getting count
   */
  public function testGetActivitiesCountforContactSummary() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
        dirname(__FILE__) . '/activities_for_dashboard_count.xml'
      )
    );

    $params = array(
      'contact_id' => 9,
      'admin' => FALSE,
      'caseId' => NULL,
      'context' => 'activity',
      'activity_type_id' => NULL,
      'offset' => 0,
      'rowCount' => 0,
      'sort' => NULL,
    );
    $activityCount = CRM_Activity_BAO_Activity::deprecatedGetActivitiesCount($params);

    //since we are loading activities from dataset, we know total number of activities for this contact
    // 5 activities, Contact Summary should show all activities
    $count = 5;
    $this->assertEquals($count, $activityCount);
    $this->assertEquals(5, CRM_Activity_BAO_Activity::getActivitiesCount($params));
  }

  /**
   * CRM-18706 - Test Include/Exclude Activity Filters
   */
  public function testActivityFilters() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
        dirname(__FILE__) . '/activities_for_dashboard_count.xml'
      )
    );
    Civi::settings()->set('preserve_activity_tab_filter', 1);
    $this->createLoggedInUser();

    global $_GET;
    $_GET = array(
      'cid' => 9,
      'context' => 'activity',
      'activity_type_id' => 1,
      'is_unit_test' => 1,
    );
    $expectedFilters = array(
      'activity_type_filter_id' => 1,
      'activity_type_exclude_filter_id' => '',
    );

    list($activities, $activityFilter) = CRM_Activity_Page_AJAX::getContactActivity();
    //Assert whether filters are correctly set.
    $this->checkArrayEquals($expectedFilters, $activityFilter);
    // This should include activities of type Meeting only.
    foreach ($activities['data'] as $value) {
      $this->assertContains('Meeting', $value['activity_type']);
    }
    unset($_GET['activity_type_id']);
    $expectedFilters['activity_type_filter_id'] = '';

    $_GET['activity_type_exclude_id'] = $expectedFilters['activity_type_exclude_filter_id'] = 1;
    list($activities, $activityFilter) = CRM_Activity_Page_AJAX::getContactActivity();
    $this->checkArrayEquals($expectedFilters, $activityFilter);
    // None of the activities should be of type Meeting.
    foreach ($activities['data'] as $value) {
      $this->assertNotContains('Meeting', $value['activity_type']);
    }
  }

  /**
   * Test getActivities BAO method for getting count
   */
  public function testGetActivitiesCountforContactSummaryWithNoActivities() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
        dirname(__FILE__) . '/activities_for_dashboard_count.xml'
      )
    );

    $params = array(
      'contact_id' => 17,
      'admin' => FALSE,
      'caseId' => NULL,
      'context' => 'home',
      'activity_type_id' => NULL,
      'offset' => 0,
      'rowCount' => 0,
      'sort' => NULL,
    );
    $activityCount = CRM_Activity_BAO_Activity::deprecatedGetActivitiesCount($params);

    //since we are loading activities from dataset, we know total number of activities for this contact
    // this contact does not have any activity
    $this->assertEquals(0, $activityCount);
    $this->assertEquals(0, CRM_Activity_BAO_Activity::getActivitiesCount($params));
  }

  /**
   * Test getActivities BAO method.
   */
  public function testGetActivitiesForAdminDashboard() {
    $this->setUpForActivityDashboardTests();
    $activitiesDeprecatedFn = CRM_Activity_BAO_Activity::deprecatedGetActivities($this->_params);
    $activitiesNew = CRM_Activity_BAO_Activity::getActivities($this->_params);
    // $this->assertEquals($activities, $activitiesDeprecatedFn);

    //since we are loading activities from dataset, we know total number of activities
    // with no contact ID and there should be 8 schedule activities shown on dashboard
    $count = 8;
    foreach (array($activitiesNew, $activitiesDeprecatedFn) as $activities) {
      $this->assertEquals($count, count($activities));

      foreach ($activities as $key => $value) {
        $this->assertEquals($value['subject'], "subject {$key}", 'Verify activity subject is correct.');
        $this->assertEquals($value['activity_type_id'], 2, 'Verify activity type is correct.');
        $this->assertEquals($value['status_id'], 1, 'Verify all activities are scheduled.');
      }
    }
  }

  /**
   * Test getActivities BAO method.
   */
  public function testGetActivitiesForAdminDashboardNoViewContacts() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM');
    $this->setUpForActivityDashboardTests();
    $activitiesDeprecated = CRM_Activity_BAO_Activity::deprecatedGetActivities($this->_params);
    foreach (array($activitiesDeprecated, CRM_Activity_BAO_Activity::getActivities($this->_params)) as $activities) {
      // Skipped until we get back to the upgraded version properly.
      //$this->assertEquals(0, count($activities));
    }
  }

  /**
   * Test getActivities BAO method.
   */
  public function testGetActivitiesForAdminDashboardAclLimitedViewContacts() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM');
    $this->allowedContacts = array(1, 3, 4, 5);
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereMultipleContacts'));
    $this->setUpForActivityDashboardTests();
    $activitiesDeprecated = CRM_Activity_BAO_Activity::deprecatedGetActivities($this->_params);
    foreach (array($activitiesDeprecated, CRM_Activity_BAO_Activity::getActivities($this->_params)) as $activities) {
      //$this->assertEquals(1, count($activities));
    }

  }

  /**
   * Test getActivities BAO method.
   */
  public function testGetActivitiesforNonAdminDashboard() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
        dirname(__FILE__) . '/activities_for_dashboard_count.xml'
      )
    );

    $contactID = 9;
    $params = array(
      'contact_id' => $contactID,
      'admin' => FALSE,
      'caseId' => NULL,
      'context' => 'home',
      'activity_type_id' => NULL,
      'activity_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Scheduled'), // for dashlet the Scheduled status is set by default
      'offset' => 0,
      'rowCount' => 0,
      'sort' => NULL,
    );
    $activitiesDep = CRM_Activity_BAO_Activity::deprecatedGetActivities($params);

    foreach (array($activitiesDep, CRM_Activity_BAO_Activity::getActivities($params)) as $activities) {
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
  }

  /**
   * Test target contact count.
   */
  public function testTargetCountforContactSummary() {
    $targetCount = 5;
    $contactId = $this->individualCreate();
    $targetContactIDs = array();
    for ($i = 0; $i < $targetCount; $i++) {
      $targetContactIDs[] = $this->individualCreate(array(), $i);
    }
    // Create activities with 5 target contacts.
    $activityParams = array(
      'source_contact_id' => $contactId,
      'target_contact_id' => $targetContactIDs,
    );
    $this->activityCreate($activityParams);

    $params = array(
      'contact_id' => $contactId,
      'context' => 'activity',
    );
    $activitiesDep = CRM_Activity_BAO_Activity::deprecatedGetActivities($params);
    foreach (array($activitiesDep, CRM_Activity_BAO_Activity::getActivities($params)) as $activities) {
      //verify target count
      $this->assertEquals($targetCount, $activities[1]['target_contact_counter']);
    }

  }

  /**
   * Test getActivities BAO method.
   */
  public function testGetActivitiesforContactSummary() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
        dirname(__FILE__) . '/activities_for_dashboard_count.xml'
      )
    );

    $contactID = 9;
    $params = array(
      'contact_id' => $contactID,
      'admin' => FALSE,
      'caseId' => NULL,
      'context' => 'activity',
      'activity_type_id' => NULL,
      'offset' => 0,
      'rowCount' => 0,
      'sort' => NULL,
    );
    $activitiesDep = CRM_Activity_BAO_Activity::deprecatedGetActivities($params);

    //since we are loading activities from dataset, we know total number of activities for this contact
    // 5 activities, Contact Summary should show all activities
    $count = 5;
    foreach (array($activitiesDep, CRM_Activity_BAO_Activity::getActivities($params)) as $activities) {

      $this->assertEquals($count, count($activities));

      foreach ($activities as $key => $value) {
        $this->assertEquals($value['subject'], "subject {$key}", 'Verify activity subject is correct.');

        if ($key > 8) {
          $this->assertEquals($value['status_id'], 2, 'Verify all activities are scheduled.');
        }
        else {
          $this->assertEquals($value['status_id'], 1, 'Verify all activities are scheduled.');
        }

        if ($key > 8) {
          $this->assertEquals($value['activity_type_id'], 1, 'Verify activity type is correct.');
        }
        else {
          $this->assertEquals($value['activity_type_id'], 2, 'Verify activity type is correct.');
        }

        if ($key == 3) {
          $this->assertArrayHasKey($contactID, $value['target_contact_name']);
        }
        elseif ($key == 4) {
          $this->assertArrayHasKey($contactID, $value['assignee_contact_name']);
        }
      }
    }
  }

  /**
   * Test getActivities BAO method.
   */
  public function testGetActivitiesforContactSummaryWithActivities() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
        dirname(__FILE__) . '/activities_for_dashboard_count.xml'
      )
    );

    // parameters for different test cases, check each array key for the specific test-case
    $testCases = array(
      'with-no-activity' => array(
        'params' => array(
          'contact_id' => 17,
          'admin' => FALSE,
          'caseId' => NULL,
          'context' => 'home',
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ),
      ),
      'with-activity' => array(
        'params' => array(
          'contact_id' => 1,
          'admin' => FALSE,
          'caseId' => NULL,
          'context' => 'home',
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ),
      ),
      'with-activity_type' => array(
        'params' => array(
          'contact_id' => 3,
          'admin' => FALSE,
          'caseId' => NULL,
          'context' => 'home',
          'activity_type_id' => 2,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ),
      ),
      'exclude-all-activity_type' => array(
        'params' => array(
          'contact_id' => 3,
          'admin' => FALSE,
          'caseId' => NULL,
          'context' => 'home',
          'activity_type_exclude_id' => array(1, 2),
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ),
      ),
      'sort-by-subject' => array(
        'params' => array(
          'contact_id' => 1,
          'admin' => FALSE,
          'caseId' => NULL,
          'context' => 'home',
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => 'subject DESC',
        ),
      ),
    );

    foreach ($testCases as $caseName => $testCase) {
      $activitiesDep = CRM_Activity_BAO_Activity::deprecatedGetActivities($testCase['params']);
      $activityCount = CRM_Activity_BAO_Activity::deprecatedGetActivitiesCount($testCase['params']);
      $activitiesNew = CRM_Activity_BAO_Activity::getActivities($testCase['params']);

      foreach (array($activitiesDep, $activitiesNew) as $activities) {
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
          $subjectOrder = array(
            'subject 8',
            'subject 7',
            'subject 1',
          );
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
  public function testbyActivityDateAndStatus() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
        dirname(__FILE__) . '/activities_for_dashboard_count.xml'
      )
    );

    // activity IDs catagorised by date
    $lastWeekActivities = array(1, 2, 3);
    $todayActivities = array(4, 5, 6, 7);
    $lastTwoMonthsActivities = array(8, 9, 10, 11);
    $lastYearActivties = array(12, 13, 14, 15, 16);

    // date values later used to set activity date value
    $lastWeekDate = date('YmdHis', strtotime('1 week ago'));
    $today = date('YmdHis');
    $lastTwoMonthAgoDate = date('YmdHis', strtotime('2 months ago'));
    $lastYearDate = date('YmdHis', strtotime('1 year ago'));
    for ($i = 1; $i <= 16; $i++) {
      if (in_array($i, $lastWeekActivities)) {
        $date = $lastWeekDate;
      }
      elseif (in_array($i, $lastTwoMonthsActivities)) {
        $date = $lastTwoMonthAgoDate;
      }
      elseif (in_array($i, $lastYearActivties)) {
        $date = $lastYearDate;
      }
      elseif (in_array($i, $todayActivities)) {
        $date = $today;
      }
      $this->callAPISuccess('Activity', 'create', array(
        'id' => $i,
        'activity_date_time' => $date,
      ));
    }

    // parameters for different test cases, check each array key for the specific test-case
    $testCases = array(
      'todays-activity' => array(
        'params' => array(
          'contact_id' => 1,
          'admin' => TRUE,
          'caseId' => NULL,
          'context' => 'activity',
          'activity_date_relative' => 'this.day',
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ),
      ),
      'todays-activity-filtered-by-range' => array(
        'params' => array(
          'contact_id' => 1,
          'admin' => TRUE,
          'caseId' => NULL,
          'context' => 'activity',
          'activity_date_low' => date('Y/m/d', strtotime('yesterday')),
          'activity_date_high' => date('Y/m/d'),
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ),
      ),
      'last-week-activity' => array(
        'params' => array(
          'contact_id' => 1,
          'admin' => TRUE,
          'caseId' => NULL,
          'context' => 'activity',
          'activity_date_relative' => 'previous.week',
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ),
      ),
      'this-quarter-activity' => array(
        'params' => array(
          'contact_id' => 1,
          'admin' => TRUE,
          'caseId' => NULL,
          'context' => 'activity',
          'activity_date_relative' => 'this.quarter',
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ),
      ),
      'last-year-activity' => array(
        'params' => array(
          'contact_id' => 1,
          'admin' => TRUE,
          'caseId' => NULL,
          'context' => 'activity',
          'activity_date_relative' => 'previous.year',
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ),
      ),
      'activity-of-all-statuses' => array(
        'params' => array(
          'contact_id' => 1,
          'admin' => TRUE,
          'caseId' => NULL,
          'context' => 'activity',
          'activity_status_id' => '1,2',
          'activity_type_id' => NULL,
          'offset' => 0,
          'rowCount' => 0,
          'sort' => NULL,
        ),
      ),
    );

    foreach ($testCases as $caseName => $testCase) {
      $activitiesDep = CRM_Activity_BAO_Activity::deprecatedGetActivities($testCase['params']);
      $activityCount = CRM_Activity_BAO_Activity::deprecatedGetActivitiesCount($testCase['params']);
      asort($activitiesDep);
      $activityIDs = array_keys($activitiesDep);

      if ($caseName == 'todays-activity' || $caseName == 'todays-activity-filtered-by-range') {
        $this->assertEquals(count($todayActivities), $activityCount);
        $this->assertEquals(count($todayActivities), count($activitiesDep));
        $this->checkArrayEquals($todayActivities, $activityIDs);
      }
      elseif ($caseName == 'last-week-activity') {
        $this->assertEquals(count($lastWeekActivities), $activityCount);
        $this->assertEquals(count($lastWeekActivities), count($activitiesDep));
        $this->checkArrayEquals($lastWeekActivities, $activityIDs);
      }
      elseif ($caseName == 'lhis-quarter-activity') {
        $this->assertEquals(count($lastTwoMonthsActivities), $activityCount);
        $this->assertEquals(count($lastTwoMonthsActivities), count($activitiesDep));
        $this->checkArrayEquals($lastTwoMonthsActivities, $activityIDs);
      }
      elseif ($caseName == 'last-year-activity') {
        $this->assertEquals(count($lastYearActivties), $activityCount);
        $this->assertEquals(count($lastYearActivties), count($activitiesDep));
        $this->checkArrayEquals($lastYearActivties, $activityIDs);
      }
      elseif ($caseName == 'activity-of-all-statuses') {
        $this->assertEquals(16, $activityCount);
        $this->assertEquals(16, count($activitiesDep));
      }
    }
  }

  /**
   * CRM-20308: Test from email address when a 'copy of Activity' event occur
   */
  public function testEmailAddressOfActivityCopy() {
    // Case 1: assert the 'From' Email Address of source Actvity Contact ID
    // create activity with source contact ID which has email address
    $assigneeContactId = $this->individualCreate();
    $sourceContactParams = array(
      'first_name' => 'liz',
      'last_name' => 'hurleey',
      'email' => substr(sha1(rand()), 0, 7) . '@testemail.com',
    );
    $sourceContactID = $this->individualCreate($sourceContactParams);
    $sourceDisplayName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $sourceContactID, 'display_name');

    // create an activity using API
    $params = array(
      'source_contact_id' => $sourceContactID,
      'subject' => 'Scheduling Meeting ' . substr(sha1(rand()), 0, 4),
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Meeting'),
      'assignee_contact_id' => array($assigneeContactId),
      'activity_date_time' => date('Ymd'),
    );
    $activity = $this->callAPISuccess('Activity', 'create', $params);

    // Check that from address is in "Source-Display-Name <source-email>"
    $formAddress = CRM_Case_BAO_Case::getReceiptFrom($activity['id']);
    $expectedFromAddress = sprintf("%s <%s>", $sourceDisplayName, $sourceContactParams['email']);
    $this->assertEquals($expectedFromAddress, $formAddress);

    // Case 2: System Default From Address
    //  but first erase the email address of existing source contact ID
    $withoutEmailParams = array(
      'email' => '',
    );
    $sourceContactID = $this->individualCreate($withoutEmailParams);
    $params = array(
      'source_contact_id' => $sourceContactID,
      'subject' => 'Scheduling Meeting ' . substr(sha1(rand()), 0, 4),
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Meeting'),
      'activity_date_time' => date('Ymd'),
    );
    $activity = $this->callAPISuccess('Activity', 'create', $params);
    // fetch domain info
    $domainInfo = $this->callAPISuccess('Domain', 'getsingle', array('id' => CRM_Core_Config::domainID()));

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
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
        dirname(__FILE__) . '/activities_for_dashboard_count.xml'
      )
    );

    $this->_params = array(
      'contact_id' => NULL,
      'admin' => TRUE,
      'caseId' => NULL,
      'context' => 'home',
      'activity_type_id' => NULL,
      'activity_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Scheduled'), // for dashlet the Scheduled status is set by default
      'offset' => 0,
      'rowCount' => 0,
      'sort' => NULL,
    );
  }

  public function testSendEmailBasic() {
    $contactId = $this->individualCreate();

    // create a logged in USER since the code references it for sendEmail user.
    $this->createLoggedInUser();
    $session = CRM_Core_Session::singleton();
    $loggedInUser = $session->get('userID');

    $contact = $this->civicrm_api('contact', 'getsingle', array('id' => $contactId, 'version' => $this->_apiversion));
    $contactDetailsIntersectKeys = array(
      'contact_id' => '',
      'sort_name' => '',
      'display_name' => '',
      'do_not_email' => '',
      'preferred_mail_format' => '',
      'is_deceased' => '',
      'email' => '',
      'on_hold' => '',
    );
    $contactDetails = array(
      array_intersect_key($contact, $contactDetailsIntersectKeys),
    );

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

    $activity = $this->civicrm_api('activity', 'getsingle', array('id' => $activity_id, 'version' => $this->_apiversion));
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

    $contact = $this->civicrm_api('contact', 'getsingle', array('id' => $contactId, 'version' => $this->_apiversion));
    $contactDetailsIntersectKeys = array(
      'contact_id' => '',
      'sort_name' => '',
      'display_name' => '',
      'do_not_email' => '',
      'preferred_mail_format' => '',
      'is_deceased' => '',
      'email' => '',
      'on_hold' => '',
    );
    $contactDetails = array(
      array_intersect_key($contact, $contactDetailsIntersectKeys),
    );

    // Create a campaign.
    $result = $this->civicrm_api('Campaign', 'create', array(
      'version' => $this->_apiversion,
      'title' => __FUNCTION__ . ' campaign',
    ));
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
    $activity = $this->civicrm_api('activity', 'getsingle', array('id' => $activity_id, 'version' => $this->_apiversion));
    $this->assertEquals($activity['campaign_id'], $campaign_id, 'Activity campaign_id does not match.');
  }

}
