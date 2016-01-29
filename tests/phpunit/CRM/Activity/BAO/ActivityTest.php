<?php

require_once 'CiviTest/Contact.php';

/**
 * Class CRM_Activity_BAO_ActivityTest
 */
class CRM_Activity_BAO_ActivityTest extends CiviUnitTestCase {
  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    // truncate a few tables
    $tablesToTruncate = array('civicrm_contact', 'civicrm_activity', 'civicrm_activity_contact');
    $this->quickCleanup($tablesToTruncate);
  }

  /**
   * Test case for create() method.
   */
  public function testCreate() {
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
   * Test case for getContactActivity() method.
   *
   * getContactActivity() method get activities detail for given target contact id.
   */
  public function testGetContactActivity() {
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
   * Test case for retrieve() method.
   *
   * Retrieve($params, $defaults) method return activity detail for given params
   *                              and set defaults.
   */
  public function testRetrieve() {
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

    $activityTargetId = $this->assertDBNotNull('CRM_Activity_DAO_ActivityContact', $targetContactId,
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

    Contact::delete($contactId);
    Contact::delete($targetContactId);
  }

  /**
   * Test case for deleteActivity() method.
   *
   * deleteActivity($params) method deletes activity for given params.
   */
  public function testDeleteActivity() {
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

    $activityTargetId = $this->assertDBNotNull('CRM_Activity_DAO_ActivityContact', $targetContactId,
      'id', 'contact_id',
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
   * Test case for deleteActivityTarget() method.
   *
   * deleteActivityTarget($activityId) method deletes activity target for given activity id.
   */
  public function testDeleteActivityTarget() {
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

    $activityTargetId = $this->assertDBNotNull('CRM_Activity_DAO_ActivityContact', $targetContactId,
      'id', 'contact_id',
      'Database check for created activity target.'
    );

    CRM_Activity_BAO_Activity::deleteActivityContact($activityId, 3);

    $this->assertDBNull('CRM_Activity_DAO_ActivityContact', $targetContactId, 'id',
      'contact_id', 'Database check for deleted activity target.'
    );

    Contact::delete($contactId);
    Contact::delete($targetContactId);
  }

  /**
   * Test case for deleteActivityAssignment() method.
   *
   * deleteActivityAssignment($activityId) method deletes activity assignment for given activity id.
   */
  public function testDeleteActivityAssignment() {
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

    $activityAssignmentId = $this->assertDBNotNull('CRM_Activity_DAO_ActivityContact',
      $assigneeContactId, 'id', 'contact_id',
      'Database check for created activity assignment.'
    );

    CRM_Activity_BAO_Activity::deleteActivityContact($activityId, 1);

    $this->assertDBNull('CRM_Activity_DAO_ActivityContact', $assigneeContactId, 'id',
      'contact_id', 'Database check for deleted activity assignment.'
    );

    Contact::delete($contactId);
    Contact::delete($assigneeContactId);
  }

  /**
   * Test getActivitiesCount BAO method.
   */
  public function testGetActivitiesCountforAdminDashboard() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
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
    $this->assertEquals($count, $activityCount);
  }

  /**
   * Test getActivitiesCount BAO method.
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
      'offset' => 0,
      'rowCount' => 0,
      'sort' => NULL,
    );

    $activityCount = CRM_Activity_BAO_Activity::getActivitiesCount($params);

    //since we are loading activities from dataset, we know total number of activities for this contact
    // 5 activities ( 2 scheduled, 3 Completed ), note that dashboard shows only scheduled activities
    $count = 2;
    $this->assertEquals($count, $activityCount);
  }

  /**
   * Test getActivitiesCount BAO method.
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
    $activityCount = CRM_Activity_BAO_Activity::getActivitiesCount($params);

    //since we are loading activities from dataset, we know total number of activities for this contact
    // 5 activities, Contact Summary should show all activities
    $count = 5;
    $this->assertEquals($count, $activityCount);
  }

  /**
   * Test getActivitiesCount BAO method.
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
    $activityCount = CRM_Activity_BAO_Activity::getActivitiesCount($params);

    //since we are loading activities from dataset, we know total number of activities for this contact
    // this contact does not have any activity
    $this->assertEquals(0, $activityCount);
  }

  /**
   * Test getActivities BAO method.
   */
  public function testGetActivitiesforAdminDashboard() {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
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
    $this->assertEquals($count, count($activities));

    foreach ($activities as $key => $value) {
      $this->assertEquals($value['subject'], "subject {$key}", 'Verify activity subject is correct.');
      $this->assertEquals($value['activity_type_id'], 2, 'Verify activity type is correct.');
      $this->assertEquals($value['status_id'], 1, 'Verify all activities are scheduled.');
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
      'offset' => 0,
      'rowCount' => 0,
      'sort' => NULL,
    );
    $activities = CRM_Activity_BAO_Activity::getActivities($params);

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
    $activities = CRM_Activity_BAO_Activity::getActivities($params);

    //since we are loading activities from dataset, we know total number of activities for this contact
    // 5 activities, Contact Summary should show all activities
    $count = 5;
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

  /**
   * Test getActivities BAO method.
   */
  public function testGetActivitiesforContactSummaryWithNoActivities() {
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
    $activities = CRM_Activity_BAO_Activity::getActivities($params);

    //since we are loading activities from dataset, we know total number of activities for this contact
    // This contact does not have any activities
    $this->assertEquals(0, count($activities));
  }

}
