<?php
require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';
class CRM_Activity_BAO_ActivityTest extends CiviUnitTestCase {
  function get_info() {
    return array(
      'name' => 'Activity BAOs',
      'description' => 'Test all Activity_BAO_Activity methods.',
      'group' => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();
  }

  function tearDown() {
    // truncate a few tables
    $tablesToTruncate = array('civicrm_contact', 'civicrm_activity', 'civicrm_activity_target', 'civicrm_activity_assignment');
    $this->quickCleanup($tablesToTruncate);
  }

  /**
   * testcases for create() method
   * create() method Add/Edit activity.
   */
  function testCreate() {
    $contactId = Contact::createIndividual();

    $params = array(
      'source_contact_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
    );

    CRM_Activity_BAO_Activity::create($params);

    $activityId = $this->assertDBNotNull('CRM_Activity_DAO_Activity', 'Scheduling Meeting', 'id',
      'subject', 'Database check for created activity.'
    );

    // Now call create() to modify an existing Activity

    $params = array();
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

    Contact::delete($contactId);
  }

  /**
   * testcase for getContactActivity() method.
   * getContactActivity() method get activities detail for given target contact id
   */
  function testGetContactActivity() {
    $contactId = Contact::createIndividual();
    $params = array(
      'first_name' => 'liz',
      'last_name' => 'hurleey',
    );
    $targetContactId = Contact::createIndividual($params);

    $params = array(
      'source_contact_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
      'target_contact_id' => array($targetContactId),
      'activity_date_time' => date('Ymd'),
    );

    CRM_Activity_BAO_Activity::create($params);

    $activityId = $this->assertDBNotNull('CRM_Activity_DAO_Activity', 'Scheduling Meeting',
      'id',
      'subject', 'Database check for created activity.'
    );

    $activities = CRM_Activity_BAO_Activity::getContactActivity($targetContactId);

    $this->assertEquals($activities[$activityId]['subject'], 'Scheduling Meeting', 'Verify activity subject is correct.');

    Contact::delete($contactId);
    Contact::delete($targetContactId);
  }

  /**
   * testcase for retrieve() method.
   * retrieve($params, $defaults) method return activity detail for given params
   *                              and set defaults.
   */
  function testRetrieve() {
    $contactId = Contact::createIndividual();
    $params = array(
      'first_name' => 'liz',
      'last_name' => 'hurleey',
    );
    $targetContactId = Contact::createIndividual($params);

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

    $activityTargetId = $this->assertDBNotNull('CRM_Activity_DAO_ActivityTarget', $targetContactId,
      'id', 'target_contact_id',
      'Database check for created activity target.'
    );

    $defaults = array();
    $activity = CRM_Activity_BAO_Activity::retrieve($params, $defaults);

    $this->assertEquals($activity->subject, 'Scheduling Meeting', 'Verify activity subject is correct.');
    $this->assertEquals($activity->source_contact_id, $contactId, 'Verify source contact id is correct.');
    $this->assertEquals($activity->activity_type_id, 2, 'Verify activity type id is correct.');

    $this->assertEquals($defaults['subject'], 'Scheduling Meeting', 'Verify activity subject is correct.');
    $this->assertEquals($defaults['source_contact_id'], $contactId, 'Verify source contact id is correct.');
    $this->assertEquals($defaults['activity_type_id'], 2, 'Verify activity type id is correct.');

    $this->assertEquals($defaults['target_contact'][0], $targetContactId, 'Verify target contact id is correct.');

    Contact::delete($contactId);
    Contact::delete($targetContactId);
  }

  /**
   * testcase for deleteActivity() method.
   * deleteActivity($params) method deletes activity for given params.
   */
  function testDeleteActivity() {
    $contactId = Contact::createIndividual();
    $params = array(
      'first_name' => 'liz',
      'last_name' => 'hurleey',
    );
    $targetContactId = Contact::createIndividual($params);

    $params = array(
      'source_contact_id' => $contactId,
      'source_record_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
      'target_contact_id' => array($targetContactId),
      'activity_date_time' => date('Ymd'),
    );

    CRM_Activity_BAO_Activity::create($params);

    $activityId = $this->assertDBNotNull('CRM_Activity_DAO_Activity', 'Scheduling Meeting', 'id',
      'subject', 'Database check for created activity.'
    );

    $activityTargetId = $this->assertDBNotNull('CRM_Activity_DAO_ActivityTarget', $targetContactId,
      'id', 'target_contact_id',
      'Database check for created activity target.'
    );
    $params = array(
      'source_contact_id' => $contactId,
      'source_record_id' => $contactId,
      'subject' => 'Scheduling Meeting',
      'activity_type_id' => 2,
    );

    $result = CRM_Activity_BAO_Activity::deleteActivity($params);

    $activityId = $this->assertDBNull('CRM_Activity_DAO_Activity', 'Scheduling Meeting', 'id',
      'subject', 'Database check for deleted activity.'
    );
    Contact::delete($contactId);
    Contact::delete($targetContactId);
  }

  /**
   * testcase for deleteActivityTarget() method.
   * deleteActivityTarget($activityId) method deletes activity target for given activity id.
   */
  function testDeleteActivityTarget() {
    $contactId = Contact::createIndividual();
    $params = array(
      'first_name' => 'liz',
      'last_name' => 'hurleey',
    );
    $targetContactId = Contact::createIndividual($params);

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

    $activityTargetId = $this->assertDBNotNull('CRM_Activity_DAO_ActivityTarget', $targetContactId,
      'id', 'target_contact_id',
      'Database check for created activity target.'
    );

    CRM_Activity_BAO_Activity::deleteActivityTarget($activityId);

    $this->assertDBNull('CRM_Activity_DAO_ActivityTarget', $targetContactId, 'id',
      'target_contact_id', 'Database check for deleted activity target.'
    );

    Contact::delete($contactId);
    Contact::delete($targetContactId);
  }

  /**
   * testcase for deleteActivityAssignment() method.
   * deleteActivityAssignment($activityId) method deletes activity assignment for given activity id.
   */
  function testDeleteActivityAssignment() {
    $contactId = Contact::createIndividual();
    $params = array(
      'first_name' => 'liz',
      'last_name' => 'hurleey',
    );
    $assigneeContactId = Contact::createIndividual($params);

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

    $activityAssignmentId = $this->assertDBNotNull('CRM_Activity_DAO_ActivityAssignment',
      $assigneeContactId, 'id', 'target_contact_id',
      'Database check for created activity assignment.'
    );

    CRM_Activity_BAO_Activity::deleteActivityAssignment($activityId);

    $this->assertDBNull('CRM_Activity_DAO_ActivityAssignment', $assigneeContactId, 'id',
      'assignee_contact_id', 'Database check for deleted activity assignment.'
    );

    Contact::delete($contactId);
    Contact::delete($assigneeContactId);
  }

  /**
   * Function to test getActivitiesCount BAO method
   */
  function testGetActivitiesCountforAdminDashboard() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/activities_for_dashboard_count.xml'
      )
    );

    $params = array(
      'contact_id' => NULL,
      'admin' => TRUE,
      'caseId' => NULL,
      'context' => 'home',
      'activity_type_id' => NULL,
      'offset' => 0,
      'rowCount' => 0,
      'sort' => NULL,
    );
    $activityCount = CRM_Activity_BAO_Activity::getActivitiesCount($params);

    //since we are loading activities from dataset, we know total number of activities
    // 8 schedule activities that should be shown on dashboard
    $count = 8;
    $this->assertEquals($count, $activityCount, 'In line ' . __LINE__);
  }

  /**
   * Function to test getActivitiesCount BAO method
   */
  function testGetActivitiesCountforNonAdminDashboard() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/activities_for_dashboard_count.xml'
      )
    );

    $params = array(
      'contact_id' => 9,
      'admin' => FALSE,
      'caseId' => NULL,
      'context' => 'home',
      'activity_type_id' => NULL,
      'offset' => 0,
      'rowCount' => 0,
      'sort' => NULL,
    );

    $activityCount = CRM_Activity_BAO_Activity::getActivitiesCount($params);

    //since we are loading activities from dataset, we know total number of activities for this contact
    // 5 activities ( 2 scheduled, 3 Completed ), note that dashboard shows only scheduled activities
    $count = 2;
    $this->assertEquals($count, $activityCount, 'In line ' . __LINE__);
  }

  /**
   * Function to test getActivitiesCount BAO method
   */
  function testGetActivitiesCountforContactSummary() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
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
    $activityCount = CRM_Activity_BAO_Activity::getActivitiesCount($params);

    //since we are loading activities from dataset, we know total number of activities for this contact
    // 5 activities, Contact Summary should show all activities
    $count = 5;
    $this->assertEquals($count, $activityCount, 'In line ' . __LINE__);
  }

  /**
   * Function to test getActivitiesCount BAO method
   */
  function testGetActivitiesCountforContactSummaryWithNoActivities() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
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
    $activityCount = CRM_Activity_BAO_Activity::getActivitiesCount($params);

    //since we are loading activities from dataset, we know total number of activities for this contact
    // this contact does not have any activity
    $this->assertEquals(0, $activityCount, 'In line ' . __LINE__);
  }

  /**
   * Function to test getActivities BAO method
   */
  function testGetActivitiesforAdminDashboard() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/activities_for_dashboard_count.xml'
      )
    );

    $params = array(
      'contact_id' => 5,
      'admin' => TRUE,
      'caseId' => NULL,
      'context' => 'home',
      'activity_type_id' => NULL,
      'offset' => 0,
      'rowCount' => 0,
      'sort' => NULL,
    );
    $activities = CRM_Activity_BAO_Activity::getActivities($params);

    //since we are loading activities from dataset, we know total number of activities
    // 8 schedule activities that should be shown on dashboard
    $count = 8;
    $this->assertEquals($count, sizeof($activities), 'In line ' . __LINE__);

    foreach ($activities as $key => $value) {
      $this->assertEquals($value['subject'], "subject {$key}", 'Verify activity subject is correct.');
      $this->assertEquals($value['activity_type_id'], 2, 'Verify activity type is correct.');
      $this->assertEquals($value['status_id'], 1, 'Verify all activities are scheduled.');
    }
  }

  /**
   * Function to test getActivities BAO method
   */
  function testGetActivitiesforNonAdminDashboard() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
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
      'offset' => 0,
      'rowCount' => 0,
      'sort' => NULL,
    );
    $activities = CRM_Activity_BAO_Activity::getActivities($params);

    //since we are loading activities from dataset, we know total number of activities for this contact
    // 5 activities ( 2 scheduled, 3 Completed ), note that dashboard shows only scheduled activities
    $count = 2;
    $this->assertEquals($count, sizeof($activities), 'In line ' . __LINE__);

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

  /**
   * Function to test getActivities BAO method
   */
  function testGetActivitiesforContactSummary() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
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
    $activities = CRM_Activity_BAO_Activity::getActivities($params);

    //since we are loading activities from dataset, we know total number of activities for this contact
    // 5 activities, Contact Summary should show all activities
    $count = 5;
    $this->assertEquals($count, sizeof($activities), 'In line ' . __LINE__);

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

  /**
   * Function to test getActivities BAO method
   */
  function testGetActivitiesforContactSummaryWithNoActivities() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
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
    $activities = CRM_Activity_BAO_Activity::getActivities($params);

    //since we are loading activities from dataset, we know total number of activities for this contact
    // This contact does not have any activities
    $this->assertEquals(0, sizeof($activities), 'In line ' . __LINE__);
  }
}

