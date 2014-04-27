<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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


require_once 'CiviTest/CiviUnitTestCase.php';
class CRM_Core_BAO_ActionScheduleTest extends CiviUnitTestCase {
  /**
   * @var object see CiviTest/CiviMailUtils
   */
  var $mut;

  function get_info() {
    return array(
      'name' => 'Action-Schedule BAO',
      'description' => 'Test sending of scheduled notifications.',
      'group' => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();

    require_once 'CiviTest/CiviMailUtils.php';
    $this->mut = new CiviMailUtils($this, true);

    $this->fixtures['rolling_membership'] = array( // createTestObject
      'membership_type_id' => array(
        'period_type' => 'rolling',
        'duration_unit' => 'month',
        'duration_interval' => '3',
        'is_active' => 1,
      ),
      'join_date' => '20120315',
      'start_date' => '20120315',
      'end_date' => '20120615',
      'is_override' => 0,
    );

    $this->fixtures['rolling_membership_past'] = array( // createTestObject
      'membership_type_id' => array(
        'period_type' => 'rolling',
        'duration_unit' => 'month',
        'duration_interval' => '3',
        'is_active' => 1,
      ),
      'join_date' => '20100310',
      'start_date' => '20100310',
      'end_date' => '20100610',
      'is_override' => 'NULL',
    );

    $this->fixtures['phonecall'] = array( // createTestObject
      'status_id' => 1,
      'activity_type_id' => 2,
      'activity_date_time' => '20120615100000',
      'is_current_revision' => 1,
      'is_deleted' => 0,
    );
    $this->fixtures['contact'] = array( // API
      'version' => 3,
      'is_deceased' => 0,
      'contact_type' => 'Individual',
      'email' => 'test-member@example.com',
    );
    $this->fixtures['sched_activity_1day'] = array( // create()
      'name' => 'One_Day_Phone_Call_Notice',
      'title' => 'One Day Phone Call Notice',
      'absolute_date' => NULL,
      'body_html' => '<p>1-Day (non-repeating)</p>',
      'body_text' => '1-Day (non-repeating)',
      'end_action' => NULL,
      'end_date' => NULL,
      'end_frequency_interval' => NULL,
      'end_frequency_unit' => NULL,
      'entity_status' => '1',
      'entity_value' => '2',
      'group_id' => NULL,
      'is_active' => '1',
      'is_repeat' => '0',
      'mapping_id' => '1',
      'msg_template_id' => NULL,
      'recipient' => '2',
      'recipient_listing' => NULL,
      'recipient_manual' => NULL,
      'record_activity' => NULL,
      'repetition_frequency_interval' => NULL,
      'repetition_frequency_unit' => NULL,
      'start_action_condition' => 'before',
      'start_action_date' => 'activity_date_time',
      'start_action_offset' => '1',
      'start_action_unit' => 'day',
      'subject' => '1-Day (non-repeating)',
    );
    $this->fixtures['sched_activity_1day_r'] = array(
      'name' => 'One_Day_Phone_Call_Notice_R',
      'title' => 'One Day Phone Call Notice R',
      'absolute_date' => NULL,
      'body_html' => '<p>1-Day (repeating)</p>',
      'body_text' => '1-Day (repeating)',
      'end_action' => 'after',
      'end_date' => 'activity_date_time',
      'end_frequency_interval' => '2',
      'end_frequency_unit' => 'day',
      'entity_status' => '1',
      'entity_value' => '2',
      'group_id' => NULL,
      'is_active' => '1',
      'is_repeat' => '1',
      'mapping_id' => '1',
      'msg_template_id' => NULL,
      'recipient' => '2',
      'recipient_listing' => NULL,
      'recipient_manual' => NULL,
      'record_activity' => NULL,
      'repetition_frequency_interval' => '6',
      'repetition_frequency_unit' => 'hour',
      'start_action_condition' => 'before',
      'start_action_date' => 'activity_date_time',
      'start_action_offset' => '1',
      'start_action_unit' => 'day',
      'subject' => '1-Day (repeating)',
    );
    $this->fixtures['sched_membership_join_2week'] = array( // create()
      'name' => 'sched_membership_join_2week',
      'title' => 'sched_membership_join_2week',
      'absolute_date' => '',
      'body_html' => '<p>body sched_membership_join_2week</p>',
      'body_text' => 'body sched_membership_join_2week',
      'end_action' => '',
      'end_date' => '',
      'end_frequency_interval' => '',
      'end_frequency_unit' => '',
      'entity_status' => '',
      'entity_value' => '',
      'group_id' => '',
      'is_active' => 1,
      'is_repeat' => '0',
      'mapping_id' => 4,
      'msg_template_id' => '',
      'recipient' => '',
      'recipient_listing' => '',
      'recipient_manual' => '',
      'record_activity' => 1,
      'repetition_frequency_interval' => '',
      'repetition_frequency_unit' => '',
      'start_action_condition' => 'after',
      'start_action_date' => 'membership_join_date',
      'start_action_offset' => '2',
      'start_action_unit' => 'week',
      'subject' => 'subject sched_membership_join_2week',
    );
    $this->fixtures['sched_membership_end_2week'] = array( // create()
      'name' => 'sched_membership_end_2week',
      'title' => 'sched_membership_end_2week',
      'absolute_date' => '',
      'body_html' => '<p>body sched_membership_end_2week</p>',
      'body_text' => 'body sched_membership_end_2week',
      'end_action' => '',
      'end_date' => '',
      'end_frequency_interval' => '',
      'end_frequency_unit' => '',
      'entity_status' => '',
      'entity_value' => '',
      'group_id' => '',
      'is_active' => 1,
      'is_repeat' => '0',
      'mapping_id' => 4,
      'msg_template_id' => '',
      'recipient' => '',
      'recipient_listing' => '',
      'recipient_manual' => '',
      'record_activity' => 1,
      'repetition_frequency_interval' => '',
      'repetition_frequency_unit' => '',
      'start_action_condition' => 'before',
      'start_action_date' => 'membership_end_date',
      'start_action_offset' => '2',
      'start_action_unit' => 'week',
      'subject' => 'subject sched_membership_end_2week',
    );

    $this->fixtures['sched_membership_end_2month'] = array( // create()
      'name' => 'sched_membership_end_2month',
      'title' => 'sched_membership_end_2month',
      'absolute_date' => '',
      'body_html' => '<p>body sched_membership_end_2month</p>',
      'body_text' => 'body sched_membership_end_2month',
      'end_action' => '',
      'end_date' => '',
      'end_frequency_interval' => '',
      'end_frequency_unit' => '',
      'entity_status' => '',
      'entity_value' => '',
      'group_id' => '',
      'is_active' => 1,
      'is_repeat' => '0',
      'mapping_id' => 4,
      'msg_template_id' => '',
      'recipient' => '',
      'recipient_listing' => '',
      'recipient_manual' => '',
      'record_activity' => 1,
      'repetition_frequency_interval' => '',
      'repetition_frequency_unit' => '',
      'start_action_condition' => 'after',
      'start_action_date' => 'membership_end_date',
      'start_action_offset' => '2',
      'start_action_unit' => 'month',
      'subject' => 'subject sched_membership_end_2month',
    );

    $this->_setUp();
    $this->quickCleanup(array('civicrm_action_log', 'civicrm_action_schedule'));
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  function tearDown() {
    parent::tearDown();

    $this->mut->clearMessages();
    $this->mut->stop();
    unset($this->mut);

    $this->_tearDown();
  }

  function testActivityDateTime_Match_NonRepeatableSchedule() {
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($this->fixtures['sched_activity_1day']);
    $this->assertTrue(is_numeric($actionScheduleDao->id));

    $activity = $this->createTestObject('CRM_Activity_DAO_Activity', $this->fixtures['phonecall']);
    $this->assertTrue(is_numeric($activity->id));
    $contact = civicrm_api('contact', 'create', $this->fixtures['contact']);
    $activity->save();

    $source['contact_id'] = $contact['id'];
    $source['activity_id'] = $activity->id;
    $source['record_type_id'] = 2;
    $activityContact = $this->createTestObject('CRM_Activity_DAO_ActivityContact', $source);
    $activityContact->save();

    $this->assertCronRuns(array(
      array( // Before the 24-hour mark, no email
        'time' => '2012-06-14 04:00:00',
        'recipients' => array(),
      ),
      array( // After the 24-hour mark, an email
        'time' => '2012-06-14 15:00:00',
        'recipients' => array(array('test-member@example.com')),
      ),
      array( // Run cron again; message already sent
        'time' => '',
        'recipients' => array(),
      ),
    ));
  }

  function testActivityDateTime_Match_RepeatableSchedule() {
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($this->fixtures['sched_activity_1day_r']);
    $this->assertTrue(is_numeric($actionScheduleDao->id));

    $activity = $this->createTestObject('CRM_Activity_DAO_Activity', $this->fixtures['phonecall']);
    $this->assertTrue(is_numeric($activity->id));
    $contact = civicrm_api('contact', 'create', $this->fixtures['contact']);
    $activity->save();

    $source['contact_id'] = $contact['id'];
    $source['activity_id'] = $activity->id;
    $source['record_type_id'] =2;
    $activityContact = $this->createTestObject('CRM_Activity_DAO_ActivityContact', $source);
    $activityContact->save();

    $this->assertCronRuns(array(
      array( // Before the 24-hour mark, no email
        'time' => '012-06-14 04:00:00',
        'recipients' => array(),
      ),
      array( // After the 24-hour mark, an email
        'time' => '2012-06-14 15:00:00',
        'recipients' => array(array('test-member@example.com')),
      ),
      array( // Run cron 4 hours later; first message already sent
        'time' => '2012-06-14 20:00:00',
        'recipients' => array(),
      ),
      array( // Run cron 6 hours later; send second message
        'time' => '2012-06-14 21:00:01',
        'recipients' => array(array('test-member@example.com')),
      ),
    ));
  }

  /**
   * For contacts/activities which don't match the schedule filter,
   * an email should *not* be sent.
   */
  // TODO // function testActivityDateTime_NonMatch() { }

  /**
   * For contacts/members which match schedule based on join date,
   * an email should be sent.
   */
  function testMembershipJoinDate_Match() {
    $membership = $this->createTestObject('CRM_Member_DAO_Membership', array_merge($this->fixtures['rolling_membership'], array('status_id' => 1)));
    $this->assertTrue(is_numeric($membership->id));
    $result = civicrm_api('Email', 'create', array(
      'contact_id' => $membership->contact_id,
      'email' => 'test-member@example.com',
      'location_type_id' => 1,
      'version' => 3,
    ));
    $this->assertAPISuccess($result);

    $contact = civicrm_api('contact', 'create', array_merge($this->fixtures['contact'], array('contact_id' => $membership->contact_id)));
    $actionSchedule = $this->fixtures['sched_membership_join_2week'];
    $actionSchedule['entity_value'] = $membership->membership_type_id;
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));

    // start_date=2012-03-15 ; schedule is 2 weeks after start_date
    $this->assertCronRuns(array(
      array( // Before the 2-week mark, no email
        'time' => '2012-03-28 01:00:00',
        'recipients' => array(),
      ),
      array( // After the 2-week mark, send an email
        'time' => '2012-03-29 01:00:00',
        'recipients' => array(array('test-member@example.com')),
      ),
    ));
  }

  /**
   * For contacts/members which match schedule based on join date,
   * an email should be sent.
   */
  function testMembershipJoinDate_NonMatch() {
    $membership = $this->createTestObject('CRM_Member_DAO_Membership', $this->fixtures['rolling_membership']);
    $this->assertTrue(is_numeric($membership->id));
    $result = civicrm_api('Email', 'create', array(
      'contact_id' => $membership->contact_id,
      'location_type_id' => 1,
      'email' => 'test-member@example.com',
      'version' => 3,
    ));
    $this->assertAPISuccess($result);

    // Add an alternative membership type, and only send messages for that type
    $extraMembershipType = $this->createTestObject('CRM_Member_DAO_MembershipType', array());
    $this->assertTrue(is_numeric($extraMembershipType->id));
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($this->fixtures['sched_membership_join_2week']);
    $this->assertTrue(is_numeric($actionScheduleDao->id));
    $actionScheduleDao->entity_value = $extraMembershipType->id;
    $actionScheduleDao->save();

    // start_date=2012-03-15 ; schedule is 2 weeks after start_date
    $this->assertCronRuns(array(
      array( // After the 2-week mark, don't send email because we have different membership type
        'time' => '2012-03-29 01:00:00',
        'recipients' => array(),
      ),
    ));
  }

  /**
   * For contacts/members which match schedule based on end date,
   * an email should be sent.
   */
  function testMembershipEndDate_Match() {
    // creates membership with end_date = 20120615
    $membership = $this->createTestObject('CRM_Member_DAO_Membership', array_merge($this->fixtures['rolling_membership'], array('status_id' => 2)));
    $this->assertTrue(is_numeric($membership->id));
    $result = civicrm_api('Email', 'create', array(
      'contact_id' => $membership->contact_id,
      'email' => 'test-member@example.com',
      'version' => 3,
    ));
    $contact = civicrm_api('contact', 'create', array_merge($this->fixtures['contact'], array('contact_id' => $membership->contact_id)));
    $this->assertAPISuccess($result);

    $actionSchedule = $this->fixtures['sched_membership_end_2week'];
    $actionSchedule['entity_value'] = $membership->membership_type_id;
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));

    // end_date=2012-06-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns(array(
      array( // Before the 2-week mark, no email
        'time' => '2012-05-31 01:00:00',
        // 'time' => '2012-06-01 01:00:00', // FIXME: Is this the right boundary?
        'recipients' => array(),
      ),
      array( // After the 2-week mark, send an email
        'time' => '2012-06-01 01:00:00',
        'recipients' => array(array('test-member@example.com')),
      ),
    ));
  }


  /**
  * For contacts/members which match schedule based on end date,
  * an email should be sent.
  */
  function testMembershipEndDate_NoMatch() {
    // creates membership with end_date = 20120615
    $membership = $this->createTestObject('CRM_Member_DAO_Membership', array_merge($this->fixtures['rolling_membership_past'], array('status_id' => 3)));
    $this->assertTrue(is_numeric($membership->id));
    $result = civicrm_api('Email', 'create', array(
      'contact_id' => $membership->contact_id,
      'email' => 'test-member@example.com',
      'version' => 3,
    ));
    $contact = civicrm_api('contact', 'create', array_merge($this->fixtures['contact'], array('contact_id' => $membership->contact_id)));
    $this->assertAPISuccess($result);

    $actionSchedule = $this->fixtures['sched_membership_end_2month'];
    $actionSchedule['entity_value'] = $membership->membership_type_id;
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));

    // end_date=2012-06-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns(array(
      array( // Before the 2-week mark, no email
        'time' => '2012-05-31 01:00:00',
        // 'time' => '2012-06-01 01:00:00', // FIXME: Is this the right boundary?
        'recipients' => array(),
      ),
      array( // After the 2-week mark, send an email
        'time' => '2013-05-01 01:00:00',
        'recipients' => array(),
      ),
    ));
  }



  // TODO // function testMembershipEndDate_NonMatch() { }
  // TODO // function testEventTypeStartDate_Match() { }
  // TODO // function testEventTypeEndDate_Match() { }
  // TODO // function testEventNameStartDate_Match() { }
  // TODO // function testEventNameEndDate_Match() { }

  /**
   * Run a series of cron jobs and make an assertion about email deliveries
   *
   * @param $jobSchedule array specifying when to run cron and what messages to expect; each item is an array with keys:
   *  - time: string, e.g. '2012-06-15 21:00:01'
   *  - recipients: array(array(string)), list of email addresses which should receive messages
   */
  function assertCronRuns($cronRuns) {
    foreach ($cronRuns as $cronRun) {
      CRM_Utils_Time::setTime($cronRun['time']);
      $result = civicrm_api('job', 'send_reminder', array(
        'version' => 3,
      ));
      $this->assertAPISuccess($result);
      $this->mut->assertRecipients($cronRun['recipients']);
      $this->mut->clearMessages();
    }
  }

  ////////////////////////////////
  ////////////////////////////////
  ////////////////////////////////
  ////////////////////////////////

  /**
   * @var array(DAO_Name => array(int)) List of items to garbage-collect during tearDown
   */
  private $_testObjects;

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   *
   * @access protected
   */
  protected function _setUp() {
    $this->_testObjects = array();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  protected function _tearDown() {
    parent::tearDown();
    $this->deleteTestObjects();
  }

  /**
   * This is a wrapper for CRM_Core_DAO::createTestObject which tracks
   * created entities and provides for brainless clenaup.
   *
   * @see CRM_Core_DAO::createTestObject
   */
  function createTestObject($daoName, $params = array(
    ), $numObjects = 1, $createOnly = FALSE) {
    $objects = CRM_Core_DAO::createTestObject($daoName, $params, $numObjects, $createOnly);
    if (is_array($objects)) {
      $this->registerTestObjects($objects);
    } else {
      $this->registerTestObjects(array($objects));
    }
    return $objects;
  }

  /**
   * @param $objects array(object) DAO or BAO objects
   */
  function registerTestObjects($objects) {
    //if (is_object($objects)) {
    //  $objects = array($objects);
    //}
    foreach ($objects as $object) {
      $daoName = preg_replace('/_BAO_/', '_DAO_', get_class($object));
      $this->_testObjects[$daoName][] = $object->id;
    }
  }

  function deleteTestObjects() {
    // Note: You might argue that the FK relations between test
    // objects could make this problematic; however, it should
    // behave intuitively as long as we mentally split our
    // test-objects between the "manual/primary records"
    // and the "automatic/secondary records"
    foreach ($this->_testObjects as $daoName => $daoIds) {
      foreach ($daoIds as $daoId) {
        CRM_Core_DAO::deleteTestObjects($daoName, array('id' => $daoId));
      }
    }
    $this->_testObjects = array();
  }

}
