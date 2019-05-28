<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Class CRM_Core_BAO_ActionScheduleTest
 * @group ActionSchedule
 * @group headless
 *
 * There are additional tests for some specific entities in other classes:
 * @see CRM_Activity_ActionMappingTest
 * @see CRM_Contribute_ActionMapping_ByTypeTest
 */
class CRM_Core_BAO_ActionScheduleTest extends CiviUnitTestCase {

  /**
   * @var CiviMailUtils
   */
  public $mut;

  public function setUp() {
    parent::setUp();

    $this->mut = new CiviMailUtils($this, TRUE);

    $this->fixtures['rolling_membership'] = array(
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

    $this->fixtures['rolling_membership_past'] = array(
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
    $this->fixtures['participant'] = array(
      'event_id' => array(
        'is_active' => 1,
        'is_template' => 0,
        'title' => 'Example Event',
        'start_date' => '20120315',
        'end_date' => '20120615',
      ),
      // Attendee.
      'role_id' => '1',
      // No-show.
      'status_id' => '8',
    );

    $this->fixtures['phonecall'] = array(
      'status_id' => 1,
      'activity_type_id' => 2,
      'activity_date_time' => '20120615100000',
      'is_current_revision' => 1,
      'is_deleted' => 0,
    );
    $this->fixtures['contact'] = array(
      'is_deceased' => 0,
      'contact_type' => 'Individual',
      'email' => 'test-member@example.com',
      'gender_id' => 'Female',
      'first_name' => 'Churmondleia',
      'last_name' => 'Ōtākou',
    );
    $this->fixtures['contact_birthdate'] = array(
      'is_deceased' => 0,
      'contact_type' => 'Individual',
      'email' => 'test-bday@example.com',
      'birth_date' => '20050707',
    );
    $this->fixtures['sched_activity_1day'] = array(
      'name' => 'One_Day_Phone_Call_Notice',
      'title' => 'One Day Phone Call Notice',
      'limit_to' => '1',
      'absolute_date' => NULL,
      'body_html' => '<p>1-Day (non-repeating) (for {activity.subject})</p>',
      'body_text' => '1-Day (non-repeating) (for {activity.subject})',
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
      'record_activity' => 1,
      'repetition_frequency_interval' => NULL,
      'repetition_frequency_unit' => NULL,
      'start_action_condition' => 'before',
      'start_action_date' => 'activity_date_time',
      'start_action_offset' => '1',
      'start_action_unit' => 'day',
      'subject' => '1-Day (non-repeating) (about {activity.activity_type})',
    );
    $this->fixtures['sched_activity_1day_r'] = array(
      'name' => 'One_Day_Phone_Call_Notice_R',
      'title' => 'One Day Phone Call Notice R',
      'limit_to' => 1,
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
      'subject' => '1-Day (repeating) (about {activity.activity_type})',
    );
    $this->fixtures['sched_activity_1day_r_on_abs_date'] = array(
      'name' => 'One_Day_Phone_Call_Notice_R',
      'title' => 'One Day Phone Call Notice R',
      'limit_to' => 1,
      'absolute_date' => CRM_Utils_Date::processDate('20120614100000'),
      'body_html' => '<p>1-Day (repeating)</p>',
      'body_text' => '1-Day (repeating)',
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
      'end_action' => 'after',
      'end_date' => 'activity_date_time',
      'end_frequency_interval' => '2',
      'end_frequency_unit' => 'day',
      'start_action_condition' => '',
      'start_action_date' => '',
      'start_action_offset' => '',
      'start_action_unit' => '',
      'subject' => '1-Day (repeating) (about {activity.activity_type})',
    );
    $this->fixtures['sched_membership_join_2week'] = array(
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
      'subject' => 'subject sched_membership_join_2week (joined {membership.join_date})',
    );
    $this->fixtures['sched_membership_start_1week'] = array(
      'name' => 'sched_membership_start_1week',
      'title' => 'sched_membership_start_1week',
      'absolute_date' => '',
      'body_html' => '<p>body sched_membership_start_1week</p>',
      'body_text' => 'body sched_membership_start_1week',
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
      'start_action_date' => 'membership_start_date',
      'start_action_offset' => '1',
      'start_action_unit' => 'week',
      'subject' => 'subject sched_membership_start_1week (joined {membership.start_date})',
    );
    $this->fixtures['sched_membership_end_2week'] = array(
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
    $this->fixtures['sched_on_membership_end_date'] = array(
      'name' => 'sched_on_membership_end_date',
      'title' => 'sched_on_membership_end_date',
      'body_html' => '<p>Your membership expired today</p>',
      'body_text' => 'Your membership expired today',
      'is_active' => 1,
      'mapping_id' => 4,
      'record_activity' => 1,
      'start_action_condition' => 'after',
      'start_action_date' => 'membership_end_date',
      'start_action_offset' => '0',
      'start_action_unit' => 'hour',
      'subject' => 'subject send reminder on membership_end_date',
    );
    $this->fixtures['sched_after_1day_membership_end_date'] = array(
      'name' => 'sched_after_1day_membership_end_date',
      'title' => 'sched_after_1day_membership_end_date',
      'body_html' => '<p>Your membership expired yesterday</p>',
      'body_text' => 'Your membership expired yesterday',
      'is_active' => 1,
      'mapping_id' => 4,
      'record_activity' => 1,
      'start_action_condition' => 'after',
      'start_action_date' => 'membership_end_date',
      'start_action_offset' => '1',
      'start_action_unit' => 'day',
      'subject' => 'subject send reminder on membership_end_date',
    );

    $this->fixtures['sched_membership_end_2month'] = array(
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

    $this->fixtures['sched_contact_bday_yesterday'] = array(
      'name' => 'sched_contact_bday_yesterday',
      'title' => 'sched_contact_bday_yesterday',
      'absolute_date' => '',
      'body_html' => '<p>you look like you were born yesterday!</p>',
      'body_text' => 'you look like you were born yesterday!',
      'end_action' => '',
      'end_date' => '',
      'end_frequency_interval' => '',
      'end_frequency_unit' => '',
      'entity_status' => 1,
      'entity_value' => 'birth_date',
      'group_id' => '',
      'is_active' => 1,
      'is_repeat' => '0',
      'mapping_id' => 6,
      'msg_template_id' => '',
      'recipient' => '',
      'recipient_listing' => '',
      'recipient_manual' => '',
      'record_activity' => 1,
      'repetition_frequency_interval' => '',
      'repetition_frequency_unit' => '',
      'start_action_condition' => 'after',
      'start_action_date' => 'date_field',
      'start_action_offset' => '1',
      'start_action_unit' => 'day',
      'subject' => 'subject sched_contact_bday_yesterday',
    );

    $this->fixtures['sched_contact_bday_anniv'] = array(
      'name' => 'sched_contact_bday_anniv',
      'title' => 'sched_contact_bday_anniv',
      'absolute_date' => '',
      'body_html' => '<p>happy birthday!</p>',
      'body_text' => 'happy birthday!',
      'end_action' => '',
      'end_date' => '',
      'end_frequency_interval' => '',
      'end_frequency_unit' => '',
      'entity_status' => 2,
      'entity_value' => 'birth_date',
      'group_id' => '',
      'is_active' => 1,
      'is_repeat' => '0',
      'mapping_id' => 6,
      'msg_template_id' => '',
      'recipient' => '',
      'recipient_listing' => '',
      'recipient_manual' => '',
      'record_activity' => 1,
      'repetition_frequency_interval' => '',
      'repetition_frequency_unit' => '',
      'start_action_condition' => 'before',
      'start_action_date' => 'date_field',
      'start_action_offset' => '1',
      'start_action_unit' => 'day',
      'subject' => 'subject sched_contact_bday_anniv',
    );

    $this->fixtures['sched_contact_grad_tomorrow'] = array(
      'name' => 'sched_contact_grad_tomorrow',
      'title' => 'sched_contact_grad_tomorrow',
      'absolute_date' => '',
      'body_html' => '<p>congratulations on your graduation!</p>',
      'body_text' => 'congratulations on your graduation!',
      'end_action' => '',
      'end_date' => '',
      'end_frequency_interval' => '',
      'end_frequency_unit' => '',
      'entity_status' => 1,
      'group_id' => '',
      'is_active' => 1,
      'is_repeat' => '0',
      'mapping_id' => 6,
      'msg_template_id' => '',
      'recipient' => '',
      'recipient_listing' => '',
      'recipient_manual' => '',
      'record_activity' => 1,
      'repetition_frequency_interval' => '',
      'repetition_frequency_unit' => '',
      'start_action_condition' => 'before',
      'start_action_date' => 'date_field',
      'start_action_offset' => '1',
      'start_action_unit' => 'day',
      'subject' => 'subject sched_contact_grad_tomorrow',
    );

    $this->fixtures['sched_contact_grad_anniv'] = array(
      'name' => 'sched_contact_grad_anniv',
      'title' => 'sched_contact_grad_anniv',
      'absolute_date' => '',
      'body_html' => '<p>dear alum, please send us money.</p>',
      'body_text' => 'dear alum, please send us money.',
      'end_action' => '',
      'end_date' => '',
      'end_frequency_interval' => '',
      'end_frequency_unit' => '',
      'entity_status' => 2,
      'group_id' => '',
      'is_active' => 1,
      'is_repeat' => '0',
      'mapping_id' => 6,
      'msg_template_id' => '',
      'recipient' => '',
      'recipient_listing' => '',
      'recipient_manual' => '',
      'record_activity' => 1,
      'repetition_frequency_interval' => '',
      'repetition_frequency_unit' => '',
      'start_action_condition' => 'after',
      'start_action_date' => 'date_field',
      'start_action_offset' => '1',
      'start_action_unit' => 'week',
      'subject' => 'subject sched_contact_grad_anniv',
    );

    $this->fixtures['sched_contact_created_yesterday'] = array(
      'name' => 'sched_contact_created_yesterday',
      'title' => 'sched_contact_created_yesterday',
      'absolute_date' => '',
      'body_html' => '<p>Your contact was created yesterday</p>',
      'body_text' => 'Your contact was created yesterday!',
      'end_action' => '',
      'end_date' => '',
      'end_frequency_interval' => '',
      'end_frequency_unit' => '',
      'entity_status' => 1,
      'entity_value' => 'created_date',
      'group_id' => '',
      'is_active' => 1,
      'is_repeat' => '0',
      'mapping_id' => 6,
      'msg_template_id' => '',
      'recipient' => '',
      'recipient_listing' => '',
      'recipient_manual' => '',
      'record_activity' => 1,
      'repetition_frequency_interval' => '',
      'repetition_frequency_unit' => '',
      'start_action_condition' => 'after',
      'start_action_date' => 'date_field',
      'start_action_offset' => '1',
      'start_action_unit' => 'day',
      'subject' => 'subject sched_contact_created_yesterday',
    );

    $this->fixtures['sched_contact_mod_anniv'] = array(
      'name' => 'sched_contact_mod_anniv',
      'title' => 'sched_contact_mod_anniv',
      'absolute_date' => '',
      'body_html' => '<p>You last updated your data last year</p>',
      'body_text' => 'Go update your stuff!',
      'end_action' => '',
      'end_date' => '',
      'end_frequency_interval' => '',
      'end_frequency_unit' => '',
      'entity_status' => 2,
      'entity_value' => 'modified_date',
      'group_id' => '',
      'is_active' => 1,
      'is_repeat' => '0',
      'mapping_id' => 6,
      'msg_template_id' => '',
      'recipient' => '',
      'recipient_listing' => '',
      'recipient_manual' => '',
      'record_activity' => 1,
      'repetition_frequency_interval' => '',
      'repetition_frequency_unit' => '',
      'start_action_condition' => 'before',
      'start_action_date' => 'date_field',
      'start_action_offset' => '1',
      'start_action_unit' => 'day',
      'subject' => 'subject sched_contact_mod_anniv',
    );

    $this->fixtures['sched_eventtype_start_1week_before'] = array(
      'name' => 'sched_eventtype_start_1week_before',
      'title' => 'sched_eventtype_start_1week_before',
      'absolute_date' => '',
      'body_html' => '<p>body sched_eventtype_start_1week_before ({event.title})</p>',
      'body_text' => 'body sched_eventtype_start_1week_before ({event.title})',
      'end_action' => '',
      'end_date' => '',
      'end_frequency_interval' => '',
      'end_frequency_unit' => '',
      // participant status id
      'entity_status' => '',
      // event type id
      'entity_value' => '',
      'group_id' => '',
      'is_active' => 1,
      'is_repeat' => '0',
      // event type
      'mapping_id' => 2,
      'msg_template_id' => '',
      'recipient' => '',
      'recipient_listing' => '',
      'recipient_manual' => '',
      'record_activity' => 1,
      'repetition_frequency_interval' => '',
      'repetition_frequency_unit' => '',
      'start_action_condition' => 'before',
      'start_action_date' => 'event_start_date',
      'start_action_offset' => '1',
      'start_action_unit' => 'week',
      'subject' => 'subject sched_eventtype_start_1week_before ({event.title})',
    );
    $this->fixtures['sched_eventtype_end_2month_repeat_twice_2_weeks'] = array(
      'name' => 'sched_eventtype_end_2month_repeat_twice_2_weeks',
      'title' => 'sched_eventtype_end_2month_repeat_twice_2_weeks',
      'absolute_date' => '',
      'body_html' => '<p>body sched_eventtype_end_2month_repeat_twice_2_weeks {event.title}</p>',
      'body_text' => 'body sched_eventtype_end_2month_repeat_twice_2_weeks {event.title}',
      'end_action' => 'after',
      'end_date' => 'event_end_date',
      'end_frequency_interval' => '3',
      'end_frequency_unit' => 'month',
      // participant status id
      'entity_status' => '',
      // event type id
      'entity_value' => '',
      'group_id' => '',
      'is_active' => 1,
      'is_repeat' => '1',
      // event type
      'mapping_id' => 2,
      'msg_template_id' => '',
      'recipient' => '',
      'recipient_listing' => '',
      'recipient_manual' => '',
      'record_activity' => 1,
      'repetition_frequency_interval' => '2',
      'repetition_frequency_unit' => 'week',
      'start_action_condition' => 'after',
      'start_action_date' => 'event_end_date',
      'start_action_offset' => '2',
      'start_action_unit' => 'month',
      'subject' => 'subject sched_eventtype_end_2month_repeat_twice_2_weeks {event.title}',
    );

    $this->fixtures['sched_membership_end_2month_repeat_twice_4_weeks'] = array(
      'name' => 'sched_membership_end_2month',
      'title' => 'sched_membership_end_2month',
      'absolute_date' => '',
      'body_html' => '<p>body sched_membership_end_2month</p>',
      'body_text' => 'body sched_membership_end_2month',
      'end_action' => '',
      'end_date' => 'membership_end_date',
      'end_frequency_interval' => '4',
      'end_frequency_unit' => 'month',
      'entity_status' => '',
      'entity_value' => '',
      'group_id' => '',
      'is_active' => 1,
      'is_repeat' => '1',
      'mapping_id' => 4,
      'msg_template_id' => '',
      'recipient' => '',
      'recipient_listing' => '',
      'recipient_manual' => '',
      'record_activity' => 1,
      'repetition_frequency_interval' => '4',
      'repetition_frequency_unit' => 'week',
      'start_action_condition' => 'after',
      'start_action_date' => 'membership_end_date',
      'start_action_offset' => '2',
      'start_action_unit' => 'month',
      'subject' => 'subject sched_membership_end_2month',
    );
    $this->fixtures['sched_membership_end_limit_to_none'] = array(
      'name' => 'limit to none',
      'title' => 'limit to none',
      'absolute_date' => '',
      'body_html' => '<p>body sched_membership_end_2month</p>',
      'body_text' => 'body sched_membership_end_2month',
      'end_action' => '',
      'end_date' => '',
      'end_frequency_interval' => '4',
      'end_frequency_unit' => 'month',
      'entity_status' => '',
      'entity_value' => '',
      'limit_to' => 0,
      'group_id' => '',
      'is_active' => 1,
      'is_repeat' => '1',
      'mapping_id' => 4,
      'msg_template_id' => '',
      'recipient' => '',
      'recipient_listing' => '',
      'recipient_manual' => '',
      'record_activity' => 1,
      'repetition_frequency_interval' => '4',
      'repetition_frequency_unit' => 'week',
      'start_action_condition' => 'after',
      'start_action_date' => 'membership_end_date',
      'start_action_offset' => '2',
      'start_action_unit' => 'month',
      'subject' => 'limit to none',
    );
    $this->fixtures['sched_on_membership_end_date_repeat_interval'] = array(
      'name' => 'sched_on_membership_end_date',
      'title' => 'sched_on_membership_end_date',
      'body_html' => '<p>Your membership expired 1 unit ago</p>',
      'body_text' => 'Your membership expired 1 unit ago',
      'end_frequency_interval' => 10,
      'end_frequency_unit' => 'year',
      'is_active' => 1,
      'is_repeat' => TRUE,
      'mapping_id' => 4,
      'record_activity' => 1,
      'start_action_condition' => 'after',
      'start_action_date' => 'membership_end_date',
      'start_action_offset' => '0',
      'start_action_unit' => 'hour',
      'subject' => 'subject send reminder every unit after membership_end_date',
    );

    $customGroup = $this->callAPISuccess('CustomGroup', 'create', array(
      'title' => ts('Test Contact Custom group'),
      'name' => 'test_contact_cg',
      'extends' => 'Contact',
      'domain_id' => CRM_Core_Config::domainID(),
      'is_active' => 1,
      'collapse_adv_display' => 0,
      'collapse_display' => 0,
    ));
    $customField = $this->callAPISuccess('CustomField', 'create', array(
      'label' => 'Test Text',
      'data_type' => 'String',
      'html_type' => 'Text',
      'custom_group_id' => $customGroup['id'],
    ));
    $this->fixtures['contact_custom_token'] = array(
      'id' => $customField['id'],
      'token' => sprintf('{contact.custom_%s}', $customField['id']),
      'name' => sprintf('custom_%s', $customField['id']),
      'value' => 'text ' . substr(sha1(rand()), 0, 7),
    );

    $this->_setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   *
   * This method is called after a test is executed.
   */
  public function tearDown() {
    parent::tearDown();
    $this->mut->clearMessages();
    $this->mut->stop();
    unset($this->mut);
    $this->quickCleanup(array(
      'civicrm_action_schedule',
      'civicrm_action_log',
      'civicrm_membership',
      'civicrm_participant',
      'civicrm_event',
      'civicrm_email',
    ));
    $this->callAPISuccess('CustomField', 'delete', array('id' => $this->fixtures['contact_custom_token']['id']));
    $this->callAPISuccess('CustomGroup', 'delete', array(
      'id' => CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', 'test_contact_cg', 'id', 'name'),
    ));
    $this->_tearDown();
  }

  public function mailerExamples() {
    $cases = array();

    // Some tokens - short as subject has 128char limit in DB.
    $someTokensTmpl = implode(';;', array(
      // basic contact token
      '{contact.display_name}',
      // funny legacy contact token
      '{contact.gender}',
      // funny legacy contact token
      '{contact.gender_id}',
      // domain token
      '{domain.name}',
      // action-scheduler token
      '{activity.activity_type}',
    ));
    // Further tokens can be tested in the body text/html.
    $manyTokensTmpl = implode(';;', array(
      $someTokensTmpl,
      '{contact.email_greeting}',
      $this->fixture['contact_custom_token']['token'],
    ));
    // Note: The behavior of domain-tokens on a scheduled reminder is undefined. All we
    // can really do is check that it has something.
    $someTokensExpected = 'Churmondleia Ōtākou;;Female;;Female;;[a-zA-Z0-9 ]+;;Phone Call';
    $manyTokensExpected = sprintf('%s;;Dear Churmondleia;;%s', $someTokensExpected, $this->fixture['contact_custom_token']['value']);

    // In this example, we use a lot of tokens cutting across multiple components.
    $cases[0] = array(
      // Schedule definition.
      array(
        'subject' => "subj $someTokensTmpl",
        'body_html' => "html $manyTokensTmpl",
        'body_text' => "text $manyTokensTmpl",
      ),
      // Assertions (regex).
      array(
        'from_name' => "/^FIXME\$/",
        'from_email' => "/^info@EXAMPLE.ORG\$/",
        'subject' => "/^subj $someTokensExpected\$/",
        'body_html' => "/^html $manyTokensExpected\$/",
        'body_text' => "/^text $manyTokensExpected\$/",
      ),
    );

    // In this example, we customize the from address.
    $cases[1] = array(
      // Schedule definition.
      array(
        'from_name' => 'Bob',
        'from_email' => 'bob@example.org',
      ),
      // Assertions (regex).
      array(
        'from_name' => "/^Bob\$/",
        'from_email' => "/^bob@example.org\$/",
      ),
    );

    // In this example, we autoconvert HTML to text
    $cases[2] = array(
      // Schedule definition.
      array(
        'body_html' => '<p>Hello &amp; stuff.</p>',
        'body_text' => '',
      ),
      // Assertions (regex).
      array(
        'body_html' => '/^' . preg_quote('<p>Hello &amp; stuff.</p>', '/') . '/',
        'body_text' => '/^' . preg_quote('Hello & stuff.', '/') . '/',
      ),
    );

    // In this example, we autoconvert HTML to text
    $cases[3] = array(
      // Schedule definition.
      array(
        'body_html' => '',
        'body_text' => 'Hello world',
      ),
      // Assertions (regex).
      array(
        'body_html' => '/^--UNDEFINED--$/',
        'body_text' => '/^Hello world$/',
      ),
    );

    return $cases;
  }

  /**
   * This generates a single mailing through the scheduled-reminder
   * system (using an activity-reminder as a baseline) and
   * checks that the resulting message satisfies various
   * regular expressions.
   *
   * @param array $schedule
   *   Values to set/override in the schedule.
   *   Ex: array('subject' => 'Hello, {contact.first_name}!').
   * @param array $patterns
   *   A list of regexes to compare with the actual email.
   *   Ex: array('subject' => '/^Hello, Alice!/').
   *   Keys: subject, body_text, body_html, from_name, from_email.
   * @dataProvider mailerExamples
   */
  public function testMailer($schedule, $patterns) {
    $actionSchedule = array_merge($this->fixtures['sched_activity_1day'], $schedule);
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));

    $activity = $this->createTestObject('CRM_Activity_DAO_Activity', $this->fixtures['phonecall']);
    $this->assertTrue(is_numeric($activity->id));
    $contact = $this->callAPISuccess('contact', 'create', array_merge(
      $this->fixtures['contact'],
      array(
        $this->fixtures['contact_custom_token']['name'] => $this->fixtures['contact_custom_token']['value'],
      )
    ));
    $activity->save();

    $source['contact_id'] = $contact['id'];
    $source['activity_id'] = $activity->id;
    $source['record_type_id'] = 2;
    $activityContact = $this->createTestObject('CRM_Activity_DAO_ActivityContact', $source);
    $activityContact->save();

    CRM_Utils_Time::setTime('2012-06-14 15:00:00');
    $this->callAPISuccess('job', 'send_reminder', array());
    $this->mut->assertRecipients(array(array('test-member@example.com')));
    foreach ($this->mut->getAllMessages('ezc') as $message) {
      /** @var ezcMail $message */

      $messageArray = array();
      $messageArray['subject'] = $message->subject;
      $messageArray['from_name'] = $message->from->name;
      $messageArray['from_email'] = $message->from->email;
      $messageArray['body_text'] = '--UNDEFINED--';
      $messageArray['body_html'] = '--UNDEFINED--';

      foreach ($message->fetchParts() as $part) {
        /** @var ezcMailText ezcMailText */
        if ($part instanceof ezcMailText && $part->subType == 'html') {
          $messageArray['body_html'] = $part->text;
        }
        if ($part instanceof ezcMailText && $part->subType == 'plain') {
          $messageArray['body_text'] = $part->text;
        }
      }

      foreach ($patterns as $field => $pattern) {
        $this->assertRegExp($pattern, $messageArray[$field],
          "Check that '$field'' matches regex. " . print_r(array('expected' => $patterns, 'actual' => $messageArray), 1));
      }
    }
    $this->mut->clearMessages();
  }

  public function testActivityDateTimeMatchNonRepeatableSchedule() {
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($this->fixtures['sched_activity_1day']);
    $this->assertTrue(is_numeric($actionScheduleDao->id));

    $activity = $this->createTestObject('CRM_Activity_DAO_Activity', $this->fixtures['phonecall']);
    $this->assertTrue(is_numeric($activity->id));
    $contact = $this->callAPISuccess('contact', 'create', $this->fixtures['contact']);
    $activity->subject = "Test subject for Phonecall";
    $activity->save();

    $source['contact_id'] = $contact['id'];
    $source['activity_id'] = $activity->id;
    $source['record_type_id'] = 2;
    $activityContact = $this->createTestObject('CRM_Activity_DAO_ActivityContact', $source);
    $activityContact->save();

    $this->assertCronRuns(array(
      array(
        // Before the 24-hour mark, no email
        'time' => '2012-06-14 04:00:00',
        'recipients' => array(),
        'subjects' => array(),
      ),
      array(
        // After the 24-hour mark, an email
        'time' => '2012-06-14 15:00:00',
        'recipients' => array(array('test-member@example.com')),
        'subjects' => array('1-Day (non-repeating) (about Phone Call)'),
      ),
      array(
        // Run cron again; message already sent
        'time' => '',
        'recipients' => array(),
      ),
    ));
    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'name');
    $activityDAO = new CRM_Activity_DAO_Activity();
    $activityDAO->source_record_id = $activity->id;
    $activityDAO->activity_type_id = array_search('Reminder Sent', $activityTypes);
    $activityDAO->find();
    while ($activityDAO->fetch()) {
      $this->assertContains($activity->subject, $activityDAO->details);
    }
  }

  public function testActivityDateTimeMatchRepeatableSchedule() {
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($this->fixtures['sched_activity_1day_r']);
    $this->assertTrue(is_numeric($actionScheduleDao->id));

    $activity = $this->createTestObject('CRM_Activity_DAO_Activity', $this->fixtures['phonecall']);
    $this->assertTrue(is_numeric($activity->id));
    $contact = $this->callAPISuccess('contact', 'create', $this->fixtures['contact']);
    $activity->save();

    $source['contact_id'] = $contact['id'];
    $source['activity_id'] = $activity->id;
    $source['record_type_id'] = 2;
    $activityContact = $this->createTestObject('CRM_Activity_DAO_ActivityContact', $source);
    $activityContact->save();

    $this->assertCronRuns(array(
      array(
        // Before the 24-hour mark, no email
        'time' => '012-06-14 04:00:00',
        'recipients' => array(),
        'subjects' => array(),
      ),
      array(
        // After the 24-hour mark, an email
        'time' => '2012-06-14 15:00:00',
        'recipients' => array(array('test-member@example.com')),
        'subjects' => array('1-Day (repeating) (about Phone Call)'),
      ),
      array(
        // Run cron 4 hours later; first message already sent
        'time' => '2012-06-14 20:00:00',
        'recipients' => array(),
        'subjects' => array(),
      ),
      array(
        // Run cron 6 hours later; send second message.
        'time' => '2012-06-14 21:00:01',
        'recipients' => array(array('test-member@example.com')),
        'subjects' => array('1-Day (repeating) (about Phone Call)'),
      ),
    ));
  }

  public function testActivityDateTimeMatchRepeatableScheduleOnAbsDate() {
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($this->fixtures['sched_activity_1day_r_on_abs_date']);
    $this->assertTrue(is_numeric($actionScheduleDao->id));

    $activity = $this->createTestObject('CRM_Activity_DAO_Activity', $this->fixtures['phonecall']);
    $this->assertTrue(is_numeric($activity->id));
    $contact = $this->callAPISuccess('contact', 'create', $this->fixtures['contact']);
    $activity->save();

    $source['contact_id'] = $contact['id'];
    $source['activity_id'] = $activity->id;
    $source['record_type_id'] = 2;
    $activityContact = $this->createTestObject('CRM_Activity_DAO_ActivityContact', $source);
    $activityContact->save();

    $this->assertCronRuns(array(
      array(
        // Before the 24-hour mark, no email
        'time' => '2012-06-13 04:00:00',
        'recipients' => array(),
        'subjects' => array(),
      ),
      array(
        // On absolute date set on 2012-06-14
        'time' => '2012-06-14 00:00:00',
        'recipients' => array(array('test-member@example.com')),
        'subjects' => array('1-Day (repeating) (about Phone Call)'),
      ),
      array(
        // Run cron 4 hours later; first message already sent
        'time' => '2012-06-14 04:00:00',
        'recipients' => array(),
        'subjects' => array(),
      ),
      array(
        // Run cron 6 hours later; send second message.
        'time' => '2012-06-14 06:00:01',
        'recipients' => array(array('test-member@example.com')),
        'subjects' => array('1-Day (repeating) (about Phone Call)'),
      ),
    ));
  }

  /**
   * For contacts/activities which don't match the schedule filter,
   * an email should *not* be sent.
   */
  // TODO // function testActivityDateTime_NonMatch() { }

  /**
   * For contacts/members which match schedule based on join/start date,
   * an email should be sent.
   */
  public function testMembershipDateMatch() {
    $membership = $this->createTestObject('CRM_Member_DAO_Membership', array_merge($this->fixtures['rolling_membership'], array('status_id' => 1)));
    $this->assertTrue(is_numeric($membership->id));
    $result = $this->callAPISuccess('Email', 'create', array(
      'contact_id' => $membership->contact_id,
      'email' => 'test-member@example.com',
      'location_type_id' => 1,
      'is_primary' => 1,
    ));

    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], array('contact_id' => $membership->contact_id)));
    $actionSchedule = $this->fixtures['sched_membership_join_2week'];
    $actionSchedule['entity_value'] = $membership->membership_type_id;
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));

    // start_date=2012-03-15 ; schedule is 2 weeks after join_date
    $this->assertCronRuns(array(
      array(
        // Before the 2-week mark, no email.
        'time' => '2012-03-28 01:00:00',
        'recipients' => array(),
        'subjects' => array(),
      ),
      array(
        // After the 2-week mark, send an email.
        'time' => '2012-03-29 01:00:00',
        'recipients' => array(array('test-member@example.com')),
        'subjects' => array('subject sched_membership_join_2week (joined March 15th, 2012)'),
      ),
    ));

    $actionSchedule = $this->fixtures['sched_membership_start_1week'];
    $actionSchedule['entity_value'] = $membership->membership_type_id;
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));

    // start_date=2012-03-15 ; schedule is 1 weeks after start_date
    $this->assertCronRuns(array(
      array(
        // Before the 2-week mark, no email.
        'time' => '2012-03-21 01:00:00',
        'recipients' => array(),
        'subjects' => array(),
      ),
      array(
        // After the 2-week mark, send an email.
        'time' => '2012-03-22 01:00:00',
        'recipients' => array(array('test-member@example.com')),
        'subjects' => array('subject sched_membership_start_1week (joined March 15th, 2012)'),
      ),
    ));
  }

  /**
   * CRM-21675: Support parent and smart group in 'Limit to' field
   */
  public function testScheduleReminderWithParentGroup() {
    // Contact A with birth-date at '07-07-2005' and gender - Male, later got added in smart group
    $contactID1 = $this->individualCreate(array('birth_date' => '20050707', 'gender_id' => 1, 'email' => 'abc@test.com'));
    // Contact B with birth-date at '07-07-2005', later got added in regular group
    $contactID2 = $this->individualCreate(array('birth_date' => '20050707', 'email' => 'def@test.com'), 1);
    // Contact C with birth-date at '07-07-2005', but not included in any group
    $contactID3 = $this->individualCreate(array('birth_date' => '20050707', 'email' => 'ghi@test.com'), 2);

    // create regular group and add Contact B to it
    $groupID = $this->groupCreate();
    $this->callAPISuccess('GroupContact', 'Create', array(
      'group_id' => $groupID,
      'contact_id' => $contactID2,
    ));

    // create smart group which will contain all Male contacts
    $smartGroupParams = array('formValues' => array('gender_id' => 1));
    $smartGroupID = $this->smartGroupCreate(
      $smartGroupParams,
      array(
        'name' => 'new_smart_group',
        'title' => 'New Smart Group',
        'parents' => array($groupID => 1),
      )
    );

    $actionScheduleParams = array(
      'name' => 'sched_contact_bday_yesterday',
      'title' => 'sched_contact_bday_yesterday',
      'absolute_date' => '',
      'body_html' => '<p>you look like you were born yesterday!</p>',
      'body_text' => 'you look like you were born yesterday!',
      'end_action' => '',
      'end_date' => '',
      'end_frequency_interval' => '',
      'end_frequency_unit' => '',
      'entity_status' => 1,
      'entity_value' => 'birth_date',
      'limit_to' => 1,
      'group_id' => $groupID,
      'is_active' => 1,
      'is_repeat' => '0',
      'mapping_id' => 6,
      'msg_template_id' => '',
      'recipient' => '2',
      'recipient_listing' => '',
      'recipient_manual' => '',
      'record_activity' => 1,
      'repetition_frequency_interval' => '',
      'repetition_frequency_unit' => '',
      'start_action_condition' => 'after',
      'start_action_date' => 'date_field',
      'start_action_offset' => '1',
      'start_action_unit' => 'day',
      'subject' => 'subject sched_contact_bday_yesterday',
    );

    // Create schedule reminder where parent group ($groupID) is selectd to limit recipients,
    // which contain a individual contact - $contactID2 and is parent to smart group.
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionScheduleParams);
    $this->assertTrue(is_numeric($actionScheduleDao->id));
    $this->assertCronRuns(array(
      array(
        // On the birthday, no email.
        'time' => '2005-07-07 01:00:00',
        'recipients' => array(),
      ),
      array(
        // The next day, send an email.
        'time' => '2005-07-08 20:00:00',
        'recipients' => array(
          array(
            'def@test.com',
          ),
          array(
            'abc@test.com',
          ),
        ),
      ),
    ));
    $this->groupDelete($smartGroupID);
    $this->groupDelete($groupID);
  }

  /**
   * Test end date email sent.
   *
   * For contacts/members which match schedule based on join date,
   * an email should be sent.
   */
  public function testMembershipJoinDateNonMatch() {
    $membership = $this->createTestObject('CRM_Member_DAO_Membership', $this->fixtures['rolling_membership']);
    $this->assertTrue(is_numeric($membership->id));
    $result = $this->callAPISuccess('Email', 'create', array(
      'contact_id' => $membership->contact_id,
      'location_type_id' => 1,
      'email' => 'test-member@example.com',
    ));

    // Add an alternative membership type, and only send messages for that type
    $extraMembershipType = $this->createTestObject('CRM_Member_DAO_MembershipType', array());
    $this->assertTrue(is_numeric($extraMembershipType->id));
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($this->fixtures['sched_membership_join_2week']);
    $this->assertTrue(is_numeric($actionScheduleDao->id));
    $actionScheduleDao->entity_value = $extraMembershipType->id;
    $actionScheduleDao->save();

    // start_date=2012-03-15 ; schedule is 2 weeks after start_date
    $this->assertCronRuns(array(
      array(
        // After the 2-week mark, don't send email because we have different membership type.
        'time' => '2012-03-29 01:00:00',
        'recipients' => array(),
      ),
    ));
  }

  /**
   * Test that the first and SECOND notifications are sent out.
   */
  public function testMembershipEndDateRepeat() {
    // creates membership with end_date = 20120615
    $membership = $this->createTestObject('CRM_Member_DAO_Membership', array_merge($this->fixtures['rolling_membership'], array('status_id' => 2)));
    $result = $this->callAPISuccess('Email', 'create', array(
      'contact_id' => $membership->contact_id,
      'email' => 'test-member@example.com',
    ));
    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], array('contact_id' => $membership->contact_id)));

    $actionSchedule = $this->fixtures['sched_membership_end_2month_repeat_twice_4_weeks'];
    $actionSchedule['entity_value'] = $membership->membership_type_id;
    $this->callAPISuccess('action_schedule', 'create', $actionSchedule);

    // end_date=2012-06-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns(array(
      array(
        // After the 2-week mark, send an email.
        'time' => '2012-08-15 01:00:00',
        'recipients' => array(array('test-member@example.com')),
      ),
      array(
        // After the 2-week mark, send an email.
        'time' => '2012-09-12 01:00:00',
        'recipients' => array(array('test-member@example.com')),
      ),
    ));
  }

  /**
   * Test behaviour when date changes.
   *
   * Test that the first notification is sent but the second is NOT sent if the end date changes in
   * between
   *  see CRM-15376
   */
  public function testMembershipEndDateRepeatChangedEndDate_CRM_15376() {
    // creates membership with end_date = 20120615
    $membership = $this->createTestObject('CRM_Member_DAO_Membership', array_merge($this->fixtures['rolling_membership'], array('status_id' => 2)));
    $this->callAPISuccess('Email', 'create', array(
      'contact_id' => $membership->contact_id,
      'email' => 'test-member@example.com',
    ));
    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], array('contact_id' => $membership->contact_id)));

    $actionSchedule = $this->fixtures['sched_membership_end_2month_repeat_twice_4_weeks'];
    $actionSchedule['entity_value'] = $membership->membership_type_id;
    $this->callAPISuccess('action_schedule', 'create', $actionSchedule);
    // end_date=2012-06-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns(array(
      array(
        // After the 2-week mark, send an email.
        'time' => '2012-08-15 01:00:00',
        'recipients' => array(array('test-member@example.com')),
      ),
    ));

    // Extend membership - reminder should NOT go out.
    $this->callAPISuccess('membership', 'create', array('id' => $membership->id, 'end_date' => '2014-01-01'));
    $this->assertCronRuns(array(
      array(
        // After the 2-week mark, send an email.
        'time' => '2012-09-12 01:00:00',
        'recipients' => array(),
      ),
    ));
  }

  /**
   * Test membership end date email sends.
   *
   * For contacts/members which match schedule based on end date,
   * an email should be sent.
   */
  public function testMembershipEndDateMatch() {
    // creates membership with end_date = 20120615
    $membership = $this->createTestObject('CRM_Member_DAO_Membership', array_merge($this->fixtures['rolling_membership'], array('status_id' => 2)));
    $this->assertTrue(is_numeric($membership->id));
    $this->callAPISuccess('Email', 'create', array(
      'contact_id' => $membership->contact_id,
      'email' => 'test-member@example.com',
    ));
    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], array('contact_id' => $membership->contact_id)));

    $actionSchedule = $this->fixtures['sched_membership_end_2week'];
    $actionSchedule['entity_value'] = $membership->membership_type_id;
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));

    // end_date=2012-06-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns(array(
      array(
        // Before the 2-week mark, no email.
        'time' => '2012-05-31 01:00:00',
        // 'time' => '2012-06-01 01:00:00',
        // FIXME: Is this the right boundary?
        'recipients' => array(),
      ),
      array(
        // After the 2-week mark, send an email.
        'time' => '2012-06-01 01:00:00',
        'recipients' => array(array('test-member@example.com')),
      ),
    ));

    // Now suppose user has renewed for rolling membership after 3 months, so upcoming assertion is written
    // to ensure that new reminder is sent 2 week before the new end_date i.e. '2012-09-15'
    $membership->end_date = '2012-09-15';
    $membership->save();

    //change the email id of chosen membership contact to assert
    //recipient of not the previously sent mail but the new one
    $result = $this->callAPISuccess('Email', 'create', array(
      'is_primary' => 1,
      'contact_id' => $membership->contact_id,
      'email' => 'member2@example.com',
    ));
    $this->assertAPISuccess($result);

    // end_date=2012-09-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns(array(
      array(
        // Before the 2-week mark, no email
        'time' => '2012-08-31 01:00:00',
        'recipients' => array(),
      ),
      //array( // After the 2-week mark, send an email
      //'time' => '2012-09-01 01:00:00',
      //'recipients' => array(array('member2@example.com')),
      //),
    ));
  }

  /**
   * Test membership end date email.
   *
   * For contacts/members which match schedule based on end date,
   * an email should be sent.
   */
  public function testMembershipEndDateNoMatch() {
    // creates membership with end_date = 20120615
    $membership = $this->createTestObject('CRM_Member_DAO_Membership', array_merge($this->fixtures['rolling_membership'], array('status_id' => 3)));
    $this->assertTrue(is_numeric($membership->id));
    $result = $this->callAPISuccess('Email', 'create', array(
      'contact_id' => $membership->contact_id,
      'email' => 'test-member@example.com',
    ));
    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], array('contact_id' => $membership->contact_id)));

    $actionSchedule = $this->fixtures['sched_membership_end_2month'];
    $actionSchedule['entity_value'] = $membership->membership_type_id;
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));

    // end_date=2012-06-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns(array(
      array(
        // Before the 2-week mark, no email.
        'time' => '2012-05-31 01:00:00',
        // 'time' => '2012-06-01 01:00:00',
        // FIXME: Is this the right boundary?
        'recipients' => array(),
      ),
      array(
        // After the 2-week mark, send an email.
        'time' => '2013-05-01 01:00:00',
        'recipients' => array(),
      ),
    ));
  }

  public function testContactBirthDateNoAnniv() {
    $contact = $this->callAPISuccess('Contact', 'create', $this->fixtures['contact_birthdate']);
    $this->_testObjects['CRM_Contact_DAO_Contact'][] = $contact['id'];
    $actionSchedule = $this->fixtures['sched_contact_bday_yesterday'];
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));
    $this->assertCronRuns(array(
      array(
        // On the birthday, no email.
        'time' => '2005-07-07 01:00:00',
        'recipients' => array(),
      ),
      array(
        // The next day, send an email.
        'time' => '2005-07-08 20:00:00',
        'recipients' => array(array('test-bday@example.com')),
      ),
    ));
  }

  public function testContactBirthDateAnniversary() {
    $contact = $this->callAPISuccess('Contact', 'create', $this->fixtures['contact_birthdate']);
    $this->_testObjects['CRM_Contact_DAO_Contact'][] = $contact['id'];
    $actionSchedule = $this->fixtures['sched_contact_bday_anniv'];
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));
    $this->assertCronRuns(array(
      array(
        // On some random day, no email.
        'time' => '2014-03-07 01:00:00',
        'recipients' => array(),
      ),
      array(
        // On the eve of their 9th birthday, send an email.
        'time' => '2014-07-06 20:00:00',
        'recipients' => array(array('test-bday@example.com')),
      ),
    ));
  }

  public function testContactCustomDateNoAnniv() {
    $group = array(
      'title' => 'Test_Group',
      'name' => 'test_group',
      'extends' => array('Individual'),
      'style' => 'Inline',
      'is_multiple' => FALSE,
      'is_active' => 1,
    );
    $createGroup = $this->callAPISuccess('custom_group', 'create', $group);
    $field = array(
      'label' => 'Graduation',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'custom_group_id' => $createGroup['id'],
    );
    $createField = $this->callAPISuccess('custom_field', 'create', $field);
    $contactParams = $this->fixtures['contact'];
    $contactParams["custom_{$createField['id']}"] = '2013-12-16';
    $contact = $this->callAPISuccess('Contact', 'create', $contactParams);
    $this->_testObjects['CRM_Contact_DAO_Contact'][] = $contact['id'];
    $actionSchedule = $this->fixtures['sched_contact_grad_tomorrow'];
    $actionSchedule['entity_value'] = "custom_{$createField['id']}";
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));
    $this->assertCronRuns(array(
      array(
        // On some random day, no email.
        'time' => '2014-03-07 01:00:00',
        'recipients' => array(),
      ),
      array(
        // On the eve of their graduation, send an email.
        'time' => '2013-12-15 20:00:00',
        'recipients' => array(array('test-member@example.com')),
      ),
    ));
    $this->callAPISuccess('custom_group', 'delete', array('id' => $createGroup['id']));
  }

  public function testContactCreatedNoAnniv() {
    $contact = $this->callAPISuccess('Contact', 'create', $this->fixtures['contact_birthdate']);
    $this->_testObjects['CRM_Contact_DAO_Contact'][] = $contact['id'];
    $actionSchedule = $this->fixtures['sched_contact_created_yesterday'];
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));
    $this->assertCronRuns(array(
      array(
        // On the date created, no email.
        'time' => $contact['values'][$contact['id']]['created_date'],
        'recipients' => array(),
      ),
      array(
        // The next day, send an email.
        'time' => date('Y-m-d H:i:s', strtotime($contact['values'][$contact['id']]['created_date'] . ' +1 day')),
        'recipients' => array(array('test-bday@example.com')),
      ),
    ));
  }

  public function testContactModifiedAnniversary() {
    $contact = $this->callAPISuccess('Contact', 'create', $this->fixtures['contact_birthdate']);
    $this->_testObjects['CRM_Contact_DAO_Contact'][] = $contact['id'];
    $modifiedDate = $this->callAPISuccess('Contact', 'getvalue', array('id' => $contact['id'], 'return' => 'modified_date'));
    $actionSchedule = $this->fixtures['sched_contact_mod_anniv'];
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));
    $this->assertCronRuns(array(
      array(
        // On some random day, no email.
        'time' => date('Y-m-d H:i:s', strtotime($contact['values'][$contact['id']]['modified_date'] . ' -60 days')),
        'recipients' => array(),
      ),
      array(
        // On the eve of 3 years after they were modified, send an email.
        'time' => date('Y-m-d H:i:s', strtotime($modifiedDate . ' +3 years -1 day')),
        'recipients' => array(array('test-bday@example.com')),
      ),
    ));
  }

  /**
   * Check that limit_to + an empty recipients doesn't sent to multiple contacts.
   */
  public function testMembershipLimitToNone() {
    // creates membership with end_date = 20120615
    $membership = $this->createTestObject('CRM_Member_DAO_Membership', array_merge($this->fixtures['rolling_membership'], array('status_id' => 2)));

    $this->assertTrue(is_numeric($membership->id));
    $result = $this->callAPISuccess('Email', 'create', array(
      'contact_id' => $membership->contact_id,
      'email' => 'member@example.com',
    ));
    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], array('contact_id' => $membership->contact_id)));
    $this->callAPISuccess('contact', 'create', array('email' => 'b@c.com', 'contact_type' => 'Individual'));

    $this->assertAPISuccess($result);

    $actionSchedule = $this->fixtures['sched_membership_end_limit_to_none'];
    $actionSchedule['entity_value'] = $membership->membership_type_id;
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));

    // end_date=2012-06-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns(array(
      array(
        // Before the 2-week mark, no email.
        'time' => '2012-05-31 01:00:00',
        // 'time' => '2012-06-01 01:00:00', // FIXME: Is this the right boundary?
        'recipients' => array(),
      ),
    ));
  }

  public function testMembership_referenceDate() {
    $membership = $this->createTestObject('CRM_Member_DAO_Membership', array_merge($this->fixtures['rolling_membership'], array('status_id' => 2)));

    $this->assertTrue(is_numeric($membership->id));
    $result = $this->callAPISuccess('Email', 'create', array(
      'contact_id' => $membership->contact_id,
      'email' => 'member@example.com',
    ));

    $result = $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], array('contact_id' => $membership->contact_id)));
    $this->assertAPISuccess($result);

    $actionSchedule = $this->fixtures['sched_membership_join_2week'];
    $actionSchedule['entity_value'] = $membership->membership_type_id;
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));

    // start_date=2012-03-15 ; schedule is 2 weeks after start_date
    $this->assertCronRuns(array(
      array(
        // After the 2-week mark, send an email
        'time' => '2012-03-29 01:00:00',
        'recipients' => array(array('member@example.com')),
      ),
      array(
        // After the 2-week 1day mark, don't send an email
        'time' => '2012-03-30 01:00:00',
        'recipients' => array(),
      ),
    ));

    //check if reference date is set to membership's join date
    //as per the action_start_date chosen for current schedule reminder
    $this->assertEquals('2012-03-15',
      CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionLog', $membership->contact_id, 'reference_date', 'contact_id')
    );

    //change current membership join date that may signifies as membership renewal activity
    $membership->join_date = '2012-03-29';
    $membership->save();

    $this->assertCronRuns(array(
      array(
        // After the 13 days of the changed join date 2012-03-29, don't send an email
        'time' => '2012-04-11 01:00:00',
        'recipients' => array(),
      ),
      array(
         // After the 2-week of the changed join date 2012-03-29, send an email
        'time' => '2012-04-12 01:00:00',
        'recipients' => array(array('member@example.com')),
      ),
    ));
    $this->assertCronRuns(array(
      array(
        // It should not re-send on the same day
        'time' => '2012-04-12 01:00:00',
        'recipients' => array(),
      ),
    ));
  }

  public function testMembershipOnMultipleReminder() {
    $membership = $this->createTestObject('CRM_Member_DAO_Membership', array_merge($this->fixtures['rolling_membership'], array('status_id' => 2)));

    $this->assertTrue(is_numeric($membership->id));
    $result = $this->callAPISuccess('Email', 'create', array(
      'contact_id' => $membership->contact_id,
      'email' => 'member@example.com',
    ));
    $result = $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], array('contact_id' => $membership->contact_id)));
    $this->assertAPISuccess($result);

    // Send email 2 weeks before end_date
    $actionScheduleBefore = $this->fixtures['sched_membership_end_2week'];
    // Send email on end_date/expiry date
    $actionScheduleOn = $this->fixtures['sched_on_membership_end_date'];
    // Send email 1 day after end_date/grace period
    $actionScheduleAfter = $this->fixtures['sched_after_1day_membership_end_date'];
    $actionScheduleBefore['entity_value'] = $actionScheduleOn['entity_value'] = $actionScheduleAfter['entity_value'] = $membership->membership_type_id;
    foreach (array('actionScheduleBefore', 'actionScheduleOn', 'actionScheduleAfter') as $value) {
      $$value = CRM_Core_BAO_ActionSchedule::add($$value);
      $this->assertTrue(is_numeric($$value->id));
    }

    $this->assertCronRuns(
      array(
        array(
          // 1day 2weeks before membership end date(MED), don't send mail
          'time' => '2012-05-31 01:00:00',
          'recipients' => array(),
        ),
        array(
          // 2 weeks before MED, send an email
          'time' => '2012-06-01 01:00:00',
          'recipients' => array(array('member@example.com')),
        ),
        array(
          // 1day before MED, don't send mail
          'time' => '2012-06-14 01:00:00',
          'recipients' => array(),
        ),
        array(
          // On MED, send an email
          'time' => '2012-06-15 00:00:00',
          'recipients' => array(array('member@example.com')),
        ),
        array(
          // After 1day of MED, send an email
          'time' => '2012-06-16 01:00:00',
          'recipients' => array(array('member@example.com')),
        ),
        array(
          // After 1day 1min of MED, don't send an email
          'time' => '2012-06-17 00:01:00',
          'recipients' => array(),
        ),
      )
    );

    // Assert the timestamp as of when the emails of respective three reminders as configured
    // 2 weeks before, on and 1 day after MED, are sent
    $this->assertApproxEquals(
      strtotime('2012-06-01 01:00:00'),
      strtotime(CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionLog', $actionScheduleBefore->id, 'action_date_time', 'action_schedule_id', TRUE)),
      // Variation in test execution time.
      3
    );
    $this->assertApproxEquals(
      strtotime('2012-06-15 00:00:00'),
      strtotime(CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionLog', $actionScheduleOn->id, 'action_date_time', 'action_schedule_id', TRUE)),
      // Variation in test execution time.
      3
    );
    $this->assertApproxEquals(
      strtotime('2012-06-16 01:00:00'),
      strtotime(CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionLog', $actionScheduleAfter->id, 'action_date_time', 'action_schedule_id', TRUE)),
      // Variation in test execution time.
      3
    );

    //extend MED to 2 weeks after the current MED (that may signifies as membership renewal activity)
    // and lets assert as of when the new set of reminders will be sent against their respective Schedule Reminders(SR)
    $membership->end_date = '2012-06-20';
    $membership->save();

    $result = $this->callAPISuccess('Contact', 'get', array('id' => $membership->contact_id));
    $this->assertCronRuns(
      array(
        array(
          // 1day 2weeks before membership end date(MED), don't send mail
          'time' => '2012-06-05 01:00:00',
          'recipients' => array(),
        ),
        array(
          // 2 weeks before MED, send an email
          'time' => '2012-06-06 01:00:00',
          'recipients' => array(array('member@example.com')),
        ),
        array(
          // 1day before MED, don't send mail
          'time' => '2012-06-19 01:00:00',
          'recipients' => array(),
        ),
        array(
          // On MED, send an email
          'time' => '2012-06-20 00:00:00',
          'recipients' => array(array('member@example.com')),
        ),
        array(
          // After 1day of MED, send an email
          'time' => '2012-06-21 01:00:00',
          'recipients' => array(array('member@example.com')),
        ),
        array(
          // After 1day 1min of MED, don't send an email
          'time' => '2012-07-21 00:01:00',
          'recipients' => array(),
        ),
      ));
  }

  public function testContactCustomDate_Anniv() {
    $group = array(
      'title' => 'Test_Group now',
      'name' => 'test_group_now',
      'extends' => array('Individual'),
      'style' => 'Inline',
      'is_multiple' => FALSE,
      'is_active' => 1,
    );
    $createGroup = $this->callAPISuccess('custom_group', 'create', $group);
    $field = array(
      'label' => 'Graduation',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'custom_group_id' => $createGroup['id'],
    );
    $createField = $this->callAPISuccess('custom_field', 'create', $field);

    $contactParams = $this->fixtures['contact'];
    $contactParams["custom_{$createField['id']}"] = '2013-12-16';
    $contact = $this->callAPISuccess('Contact', 'create', $contactParams);
    $this->_testObjects['CRM_Contact_DAO_Contact'][] = $contact['id'];
    $actionSchedule = $this->fixtures['sched_contact_grad_anniv'];
    $actionSchedule['entity_value'] = "custom_{$createField['id']}";
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));
    $this->assertCronRuns(array(
      array(
        // On some random day, no email.
        'time' => '2014-03-07 01:00:00',
        'recipients' => array(),
      ),
      array(
        // A week after their 5th anniversary of graduation, send an email.
        'time' => '2018-12-23 20:00:00',
        'recipients' => array(array('test-member@example.com')),
      ),
    ));
    $this->callAPISuccess('custom_group', 'delete', array('id' => $createGroup['id']));
  }

  /**
   * Test sched reminder set via registration date.
   */
  public function testEventTypeRegistrationDate() {
    //Create contact
    $contactParams = array(
      'email' => 'test-event@example.com',
    );
    $contact = $this->individualCreate($contactParams);
    //Add it as a participant to an event ending registration - 7 days from now.
    $params = array(
      'start_date' => date('Ymd', strtotime('-5 day')),
      'end_date' => date('Ymd', strtotime('+7 day')),
      'registration_start_date' => date('Ymd', strtotime('-5 day')),
      'registration_end_date' => date('Ymd', strtotime('+7 day')),
    );
    $event = $this->eventCreate($params);
    $this->participantCreate(array('contact_id' => $contact, 'event_id' => $event['id']));

    //Create a scheduled reminder to send email 7 days before registration date.
    $actionSchedule = $this->fixtures['sched_eventtype_start_1week_before'];
    $actionSchedule['start_action_offset'] = 7;
    $actionSchedule['start_action_unit'] = 'day';
    $actionSchedule['start_action_date'] = 'registration_end_date';
    $actionSchedule['entity_value'] = $event['values'][$event['id']]['event_type_id'];
    $actionSchedule['entity_status'] = $this->callAPISuccessGetValue('ParticipantStatusType', array(
      'return' => "id",
      'name' => "Attended",
    ));
    $actionSched = $this->callAPISuccess('action_schedule', 'create', $actionSchedule);
    //Run the cron and verify if an email was sent.
    $this->assertCronRuns(array(
      array(
        'time' => date('Y-m-d'),
        'recipients' => array(array('test-event@example.com')),
      ),
    ));

    //Create contact 2
    $contactParams = array(
      'email' => 'test-event2@example.com',
    );
    $contact2 = $this->individualCreate($contactParams);
    //Create an event with registration end date = 2 week from now.
    $params['end_date'] = date('Ymd', strtotime('+2 week'));
    $params['registration_end_date'] = date('Ymd', strtotime('+2 week'));
    $event2 = $this->eventCreate($params);
    $this->participantCreate(array('contact_id' => $contact2, 'event_id' => $event2['id']));

    //Assert there is no reminder sent to the contact.
    $this->assertCronRuns(array(
      array(
        'time' => date('Y-m-d'),
        'recipients' => array(),
      ),
    ));

    //Modify the sched reminder to be sent 2 week from registration end date.
    $this->callAPISuccess('action_schedule', 'create', array(
      'id' => $actionSched['id'],
      'start_action_offset' => 2,
      'start_action_unit' => 'week',
    ));

    //Contact should receive the reminder now.
    $this->assertCronRuns(array(
      array(
        'time' => date('Y-m-d'),
        'recipients' => array(array('test-event2@example.com')),
      ),
    ));
  }

  /**
   * Test sched reminder set via start date.
   */
  public function testEventTypeStartDate() {
    // Create event+participant with start_date = 20120315, end_date = 20120615.
    $participant = $this->createTestObject('CRM_Event_DAO_Participant', array_merge($this->fixtures['participant'], array('status_id' => 2)));
    $this->callAPISuccess('Email', 'create', array(
      'contact_id' => $participant->contact_id,
      'email' => 'test-event@example.com',
    ));
    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], array('contact_id' => $participant->contact_id)));

    $actionSchedule = $this->fixtures['sched_eventtype_start_1week_before'];
    $actionSchedule['entity_value'] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $participant->event_id, 'event_type_id');
    $this->callAPISuccess('action_schedule', 'create', $actionSchedule);

    //echo "CREATED\n"; ob_flush(); sleep(20);

    // end_date=2012-06-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns(array(
      array(
        // 2 weeks before
        'time' => '2012-03-02 01:00:00',
        'recipients' => array(),
      ),
      array(
        // 1 week before
        'time' => '2012-03-08 01:00:00',
        'recipients' => array(array('test-event@example.com')),
      ),
      array(
        // And then nothing else
        'time' => '2012-03-16 01:00:00',
        'recipients' => array(),
      ),
    ));
  }

  public function testEventTypeEndDateRepeat() {
    // Create event+participant with start_date = 20120315, end_date = 20120615.
    $participant = $this->createTestObject('CRM_Event_DAO_Participant', array_merge($this->fixtures['participant'], array('status_id' => 2)));
    $this->callAPISuccess('Email', 'create', array(
      'contact_id' => $participant->contact_id,
      'email' => 'test-event@example.com',
    ));
    $c = $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], array('contact_id' => $participant->contact_id)));

    $actionSchedule = $this->fixtures['sched_eventtype_end_2month_repeat_twice_2_weeks'];
    $actionSchedule['entity_value'] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $participant->event_id, 'event_type_id');
    $this->callAPISuccess('action_schedule', 'create', $actionSchedule);

    $this->assertCronRuns(array(
      array(
        // Almost 2 months.
        'time' => '2012-08-13 01:00:00',
        'recipients' => array(),
      ),
      array(
        // After the 2-month mark, send an email.
        'time' => '2012-08-16 01:00:00',
        'recipients' => array(array('test-event@example.com')),
      ),
      array(
        // After 2 months and 1 week, don't repeat yet.
        'time' => '2012-08-23 02:00:00',
        'recipients' => array(),
      ),
      array(
        // After 2 months and 2 weeks
        'time' => '2012-08-30 02:00:00',
        'recipients' => array(array('test-event@example.com')),
      ),
      array(
        // After 2 months and 4 week
        'time' => '2012-09-13 02:00:00',
        'recipients' => array(array('test-event@example.com')),
      ),
      array(
        // After 2 months and 6 weeks
        'time' => '2012-09-27 01:00:00',
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
   * Run a series of cron jobs and make an assertion about email deliveries.
   *
   * @param array $cronRuns
   *   array specifying when to run cron and what messages to expect; each item is an array with keys:
   *   - time: string, e.g. '2012-06-15 21:00:01'
   *   - recipients: array(array(string)), list of email addresses which should receive messages
   */
  public function assertCronRuns($cronRuns) {
    foreach ($cronRuns as $cronRun) {
      CRM_Utils_Time::setTime($cronRun['time']);
      $this->callAPISuccess('job', 'send_reminder', array());
      $this->mut->assertRecipients($cronRun['recipients']);
      if (array_key_exists('subjects', $cronRun)) {
        $this->mut->assertSubjects($cronRun['subjects']);
      }
      $this->mut->clearMessages();
    }
  }

  /**
   * @var array(DAO_Name => array(int)) List of items to garbage-collect during tearDown
   */
  private $_testObjects;

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function _setUp() {
    $this->_testObjects = array();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   *
   * This method is called after a test is executed.
   */
  protected function _tearDown() {
    parent::tearDown();
    $this->deleteTestObjects();
  }

  /**
   * This is a wrapper for CRM_Core_DAO::createTestObject which tracks
   * created entities and provides for brainless cleanup.
   *
   * @see CRM_Core_DAO::createTestObject
   *
   * @param $daoName
   * @param array $params
   * @param int $numObjects
   * @param bool $createOnly
   *
   * @return array|NULL|object
   */
  public function createTestObject($daoName, $params = array(), $numObjects = 1, $createOnly = FALSE) {
    $objects = CRM_Core_DAO::createTestObject($daoName, $params, $numObjects, $createOnly);
    if (is_array($objects)) {
      $this->registerTestObjects($objects);
    }
    else {
      $this->registerTestObjects(array($objects));
    }
    return $objects;
  }

  /**
   * @param array $objects
   *   DAO or BAO objects.
   */
  public function registerTestObjects($objects) {
    //if (is_object($objects)) {
    //  $objects = array($objects);
    //}
    foreach ($objects as $object) {
      $daoName = preg_replace('/_BAO_/', '_DAO_', get_class($object));
      $this->_testObjects[$daoName][] = $object->id;
    }
  }

  public function deleteTestObjects() {
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

  /**
   * Test that the various repetition units work correctly.
   * CRM-17028
   */
  public function testRepetitionFrequencyUnit() {
    $membershipTypeParams = array(
      'duration_interval' => '1',
      'duration_unit' => 'year',
      'is_active' => 1,
      'period_type' => 'rolling',
    );
    $membershipType = $this->createTestObject('CRM_Member_DAO_MembershipType', $membershipTypeParams);
    $interval_units = array('hour', 'day', 'week', 'month', 'year');
    foreach ($interval_units as $interval_unit) {
      $membershipEndDate = DateTime::createFromFormat('Y-m-d H:i:s', "2013-03-15 00:00:00");
      $contactParams = array(
        'contact_type' => 'Individual',
        'first_name' => 'Test',
        'last_name' => "Interval $interval_unit",
        'is_deceased' => 0,
      );
      $contact = $this->createTestObject('CRM_Contact_DAO_Contact', $contactParams);
      $this->assertTrue(is_numeric($contact->id));
      $emailParams = array(
        'contact_id' => $contact->id,
        'is_primary' => 1,
        'email' => "test-member-{$interval_unit}@example.com",
        'location_type_id' => 1,
      );
      $email = $this->createTestObject('CRM_Core_DAO_Email', $emailParams);
      $this->assertTrue(is_numeric($email->id));
      $membershipParams = array(
        'membership_type_id' => $membershipType->id,
        'contact_id' => $contact->id,
        'join_date' => '20120315',
        'start_date' => '20120315',
        'end_date' => '20130315',
        'is_override' => 0,
        'status_id' => 2,
      );
      $membershipParams['status-id'] = 1;
      $membership = $this->createTestObject('CRM_Member_DAO_Membership', $membershipParams);
      $actionScheduleParams = $this->fixtures['sched_on_membership_end_date_repeat_interval'];
      $actionScheduleParams['entity_value'] = $membershipType->id;
      $actionScheduleParams['repetition_frequency_unit'] = $interval_unit;
      $actionScheduleParams['repetition_frequency_interval'] = 2;
      $actionSchedule = CRM_Core_BAO_ActionSchedule::add($actionScheduleParams);
      $this->assertTrue(is_numeric($actionSchedule->id));
      $beforeEndDate = $this->createModifiedDateTime($membershipEndDate, '-1 day');
      $beforeFirstUnit = $this->createModifiedDateTime($membershipEndDate, "+1 $interval_unit");
      $afterFirstUnit = $this->createModifiedDateTime($membershipEndDate, "+2 $interval_unit");
      $cronRuns = array(
        array(
          'time' => $beforeEndDate->format('Y-m-d H:i:s'),
          'recipients' => array(),
        ),
        array(
          'time' => $membershipEndDate->format('Y-m-d H:i:s'),
          'recipients' => array(array("test-member-{$interval_unit}@example.com")),
        ),
        array(
          'time' => $beforeFirstUnit->format('Y-m-d H:i:s'),
          'recipients' => array(),
        ),
        array(
          'time' => $afterFirstUnit->format('Y-m-d H:i:s'),
          'recipients' => array(array("test-member-{$interval_unit}@example.com")),
        ),
      );
      $this->assertCronRuns($cronRuns);
      $actionSchedule->delete();
      $membership->delete();
    }
  }

  /**
   * Inherited members without permission to edit the main member contact should
   * not get reminders.
   *
   * However, just because a contact inherits one membership doesn't mean
   * reminders for other memberships should be suppressed.
   *
   * See CRM-14098
   */
  public function testInheritedMembershipPermissions() {
    // Set up common parameters for memberships.
    $membershipParams = $this->fixtures['rolling_membership'];
    $membershipParams['status_id'] = 1;

    $membershipParams['membership_type_id']['relationship_type_id'] = 1;
    $membershipParams['membership_type_id']['relationship_direction'] = 'b_a';
    $membershipType1 = $this->createTestObject('CRM_Member_DAO_MembershipType', $membershipParams['membership_type_id']);

    // We'll create a new membership type that can be held at the same time as
    // the first one.
    $membershipParams['membership_type_id']['relationship_type_id'] = 'NULL';
    $membershipParams['membership_type_id']['relationship_direction'] = 'NULL';
    $membershipType2 = $this->createTestObject('CRM_Member_DAO_MembershipType', $membershipParams['membership_type_id']);

    // Create the parent membership and contact
    $membershipParams['membership_type_id'] = $membershipType1->id;
    $mainMembership = $this->createTestObject('CRM_Member_DAO_Membership', $membershipParams);

    $contactParams = [
      'contact_type' => 'Individual',
      'first_name' => 'Mom',
      'last_name' => 'Rel',
      'is_deceased' => 0,
    ];
    $this->createTestObject('CRM_Contact_DAO_Contact', array_merge($contactParams, ['id' => $mainMembership->contact_id]));

    $emailParams = [
      'contact_id' => $mainMembership->contact_id,
      'email' => 'test-member@example.com',
      'location_type_id' => 1,
      'is_primary' => 1,
    ];
    $email = $this->createTestObject('CRM_Core_DAO_Email', $emailParams);

    // Set up contacts and emails for the two children
    $contactParams['first_name'] = 'Favorite';
    $permChild = $this->createTestObject('CRM_Contact_DAO_Contact', $contactParams);
    $emailParams['email'] = 'favorite@example.com';
    $emailParams['contact_id'] = $permChild->id;
    $this->createTestObject('CRM_Core_DAO_Email', $emailParams);

    $contactParams['first_name'] = 'Black Sheep';
    $nonPermChild = $this->createTestObject('CRM_Contact_DAO_Contact', $contactParams);
    $emailParams['email'] = 'black.sheep@example.com';
    $emailParams['contact_id'] = $nonPermChild->id;
    $this->createTestObject('CRM_Core_DAO_Email', $emailParams);

    // Each child gets a relationship, one with permission to edit the parent.  This
    // will trigger inherited memberships for the first membership type
    $relParams = [
      'relationship_type_id' => 1,
      'contact_id_a' => $nonPermChild->id,
      'contact_id_b' => $mainMembership->contact_id,
      'is_active' => 1,
    ];
    $this->callAPISuccess('relationship', 'create', $relParams);

    $relParams['contact_id_a'] = $permChild->id;
    $relParams['is_permission_a_b'] = CRM_Contact_BAO_Relationship::EDIT;
    $this->callAPISuccess('relationship', 'create', $relParams);

    // Mom and Black Sheep get their own memberships of the second type.
    $membershipParams['membership_type_id'] = $membershipType2->id;
    $membershipParams['owner_membership_id'] = 'NULL';
    $membershipParams['contact_id'] = $mainMembership->contact_id;
    $this->createTestObject('CRM_Member_DAO_Membership', $membershipParams);

    $membershipParams['contact_id'] = $nonPermChild->id;
    $this->createTestObject('CRM_Member_DAO_Membership', $membershipParams);

    // Test a reminder for the first membership type - that should exclude Black
    // Sheep.
    $actionSchedule = $this->fixtures['sched_membership_join_2week'];
    $actionSchedule['entity_value'] = $membershipType1->id;
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));

    $this->assertCronRuns([
      [
        'time' => '2012-03-29 01:00:00',
        'recipients' => [['test-member@example.com'], ['favorite@example.com']],
        'subjects' => [
          'subject sched_membership_join_2week (joined March 15th, 2012)',
          'subject sched_membership_join_2week (joined March 15th, 2012)',
        ],
      ],
    ]);

    // Test a reminder for the second membership type - that should include
    // Black Sheep.
    $actionSchedule = $this->fixtures['sched_membership_start_1week'];
    $actionSchedule['entity_value'] = $membershipType2->id;
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertTrue(is_numeric($actionScheduleDao->id));

    $this->assertCronRuns([
      [
        'time' => '2012-03-22 01:00:00',
        'recipients' => [['test-member@example.com'], ['black.sheep@example.com']],
        'subjects' => [
          'subject sched_membership_start_1week (joined March 15th, 2012)',
          'subject sched_membership_start_1week (joined March 15th, 2012)',
        ],
      ],
    ]);
  }

  public function createModifiedDateTime($origDateTime, $modifyRule) {
    $newDateTime = clone($origDateTime);
    $newDateTime->modify($modifyRule);
    return $newDateTime;
  }

}
