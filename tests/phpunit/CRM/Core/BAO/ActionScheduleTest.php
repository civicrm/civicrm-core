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

use Civi\Api4\Activity;
use Civi\Api4\ActivityContact;
use Civi\Api4\MembershipType;

/**
 * Class CRM_Core_BAO_ActionScheduleTest.
 *
 * @group ActionSchedule
 * @group headless
 *
 * There are additional tests for some specific entities in other classes:
 * @see CRM_Activity_ActionMappingTest
 * @see CRM_Contribute_ActionMapping_ByTypeTest
 */
class CRM_Core_BAO_ActionScheduleTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  /**
   * @var CiviMailUtils
   */
  public $mut;

  /**
   * Entities set up for the test.
   *
   * @var array
   */
  private $fixtures = [];

  /**
   * Generic usable membership type id.
   *
   * These should pre-exist but something is deleting them.
   *
   * @var int
   */
  protected $membershipTypeID;

  /**
   * Setup for tests.
   *
   * @throws CRM_Core_Exception
   */
  public function setUp(): void {
    parent::setUp();

    $this->mut = new CiviMailUtils($this, TRUE);

    $this->fixtures['rolling_membership_type'] = [
      'period_type' => 'rolling',
      'duration_unit' => 'month',
      'duration_interval' => '3',
      'is_active' => 1,
      'domain_id' => 1,
      'financial_type_id' => 2,
    ];

    $this->fixtures['rolling_membership'] = [
      'membership_type_id' => [
        'period_type' => 'rolling',
        'duration_unit' => 'month',
        'duration_interval' => '3',
        'is_active' => 1,
      ],
      'join_date' => '20120315',
      'start_date' => '20120315',
      'end_date' => '20120615',
      'is_override' => 0,
    ];

    $this->fixtures['rolling_membership_past'] = [
      'membership_type_id' => [
        'period_type' => 'rolling',
        'duration_unit' => 'month',
        'duration_interval' => '3',
        'is_active' => 1,
      ],
      'join_date' => '20100310',
      'start_date' => '20100310',
      'end_date' => '20100610',
      'is_override' => 'NULL',
    ];
    $this->fixtures['participant'] = [
      'event_id' => [
        'is_active' => 1,
        'is_template' => 0,
        'title' => 'Example Event',
        'start_date' => '20120315',
        'end_date' => '20120615',
      ],
      // Attendee.
      'role_id' => '1',
      // No-show.
      'status_id' => '8',
    ];

    $this->fixtures['phone_call'] = [
      'status_id' => 1,
      'activity_type_id' => 2,
      'activity_date_time' => '20120615100000',
      'is_current_revision' => 1,
      'is_deleted' => 0,
      'subject' => 'Phone call',
      'details' => 'A phone call about a bear',
    ];
    $this->fixtures['contact'] = [
      'is_deceased' => 0,
      'contact_type' => 'Individual',
      'email' => 'test-member@example.com',
      'gender_id' => 'Female',
      'first_name' => 'Churmondleia',
      'last_name' => 'Ōtākou',
    ];
    $this->fixtures['contact_2'] = [
      'is_deceased' => 0,
      'contact_type' => 'Individual',
      'email' => 'test-contact-2@example.com',
      'gender_id' => 'Male',
      'first_name' => 'Fabio',
      'last_name' => 'Fi',
    ];
    $this->fixtures['contact_birthdate'] = [
      'is_deceased' => 0,
      'contact_type' => 'Individual',
      'email' => 'test-birth_day@example.com',
      'birth_date' => '20050707',
    ];
    $this->fixtures['sched_activity_1day'] = [
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
      'effective_start_date' => '2012-06-14 00:00:00',
      'effective_end_date' => '2012-06-15 00:00:00',
    ];
    $this->fixtures['sched_activity_1day_r'] = [
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
      'effective_end_date' => '2012-06-14 16:00:00',
    ];
    $this->fixtures['sched_activity_1day_r_on_abs_date'] = [
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
    ];
    $this->fixtures['sched_event_name_1day_on_abs_date'] = [
      'name' => 'sched_event_name_1day_on_abs_date',
      'title' => 'sched_event_name_1day_on_abs_date',
      'limit_to' => 1,
      'absolute_date' => CRM_Utils_Date::processDate('20120614100000'),
      'body_html' => '<p>sched_event_name_1day_on_abs_date</p>',
      'body_text' => 'sched_event_name_1day_on_abs_date',
      'entity_status' => '1',
      'entity_value' => '2',
      'group_id' => NULL,
      'is_active' => '1',
      'is_repeat' => '0',
      'mapping_id' => '3',
      'msg_template_id' => NULL,
      'recipient' => '2',
      'recipient_listing' => NULL,
      'recipient_manual' => NULL,
      'record_activity' => NULL,
      'repetition_frequency_interval' => NULL,
      'repetition_frequency_unit' => NULL,
      'end_action' => NULL,
      'end_date' => NULL,
      'end_frequency_interval' => NULL,
      'end_frequency_unit' => NULL,
      'start_action_condition' => NULL,
      'start_action_date' => NULL,
      'start_action_offset' => NULL,
      'start_action_unit' => NULL,
      'subject' => 'sched_event_name_1day_on_abs_date',
    ];
    $this->fixtures['sched_membership_join_2week'] = [
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
    ];
    $this->fixtures['sched_membership_start_1week'] = [
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
    ];
    $this->fixtures['sched_membership_end_2week'] = [
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
      'effective_start_date' => '2012-05-01 01:00:00',
    ];
    $this->fixtures['sched_on_membership_end_date'] = [
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
    ];
    $this->fixtures['sched_after_1day_membership_end_date'] = [
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
    ];

    $this->fixtures['sched_membership_end_2month'] = [
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
    ];

    $this->fixtures['sched_membership_absolute_date'] = [
      'name' => 'sched_membership_absolute_date',
      'title' => 'sched_membership_absolute_date',
      'absolute_date' => CRM_Utils_Date::processDate('20120614100000'),
      'body_html' => '<p>body sched_membership_absolute_date</p>',
      'body_text' => 'body sched_membership_absolute_date',
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
      'start_action_condition' => '',
      'start_action_date' => '',
      'start_action_offset' => '',
      'start_action_unit' => '',
      'subject' => 'subject sched_membership_absolute_date',
    ];

    $this->fixtures['sched_contact_birth_day_yesterday'] = [
      'name' => 'sched_contact_birth_day_yesterday',
      'title' => 'sched_contact_birth_day_yesterday',
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
      'subject' => 'subject sched_contact_birth_day_yesterday',
    ];

    $this->fixtures['sched_contact_birth_day_anniversary'] = [
      'name' => 'sched_contact_birth_day_anniversary',
      'title' => 'sched_contact_birth_day_anniversary',
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
      'subject' => 'subject sched_contact_birth_day_anniversary',
    ];

    $this->fixtures['sched_contact_grad_tomorrow'] = [
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
      'effective_start_date' => '2013-10-15 20:00:00',
    ];

    $this->fixtures['sched_contact_grad_anniversary'] = [
      'name' => 'sched_contact_grad_anniversary',
      'title' => 'sched_contact_grad_anniversary',
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
      'subject' => 'subject sched_contact_grad_anniversary',
    ];

    $this->fixtures['sched_contact_created_yesterday'] = [
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
    ];

    $this->fixtures['sched_contact_mod_anniversary'] = [
      'name' => 'sched_contact_mod_anniversary',
      'title' => 'sched_contact_mod_anniversary',
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
      'subject' => 'subject sched_contact_mod_anniversary',
    ];

    $this->fixtures['sched_event_type_start_1week_before'] = [
      'name' => 'sched_event_type_start_1week_before',
      'title' => 'sched_event_type_start_1week_before',
      'absolute_date' => '',
      'body_html' => '<p>body sched_event_type_start_1week_before ({event.title})</p>',
      'body_text' => 'body sched_event_type_start_1week_before ({event.title})',
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
      'subject' => 'subject sched_event_type_start_1week_before ({event.title})',
    ];
    $this->fixtures['sched_event_type_end_2month_repeat_twice_2_weeks'] = [
      'name' => 'sched_event_type_end_2month_repeat_twice_2_weeks',
      'title' => 'sched_event_type_end_2month_repeat_twice_2_weeks',
      'absolute_date' => '',
      'body_html' => '<p>body sched_event_type_end_2month_repeat_twice_2_weeks {event.title}</p>',
      'body_text' => 'body sched_event_type_end_2month_repeat_twice_2_weeks {event.title}',
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
      'subject' => 'subject sched_event_type_end_2month_repeat_twice_2_weeks {event.title}',
    ];

    $this->fixtures['sched_membership_end_2month_repeat_twice_4_weeks'] = [
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
    ];
    $this->fixtures['sched_membership_end_limit_to_none'] = [
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
    ];
    $this->fixtures['sched_on_membership_end_date_repeat_interval'] = [
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
    ];

    $customGroup = $this->callAPISuccess('CustomGroup', 'create', [
      'title' => ts('Test Contact Custom group'),
      'name' => 'test_contact_cg',
      'extends' => 'Contact',
      'domain_id' => CRM_Core_Config::domainID(),
      'is_active' => 1,
      'collapse_adv_display' => 0,
      'collapse_display' => 0,
    ]);
    $customField = $this->callAPISuccess('CustomField', 'create', [
      'label' => 'Test Text',
      'data_type' => 'String',
      'html_type' => 'Text',
      'custom_group_id' => $customGroup['id'],
    ]);
    $customDateField = $this->callAPISuccess('CustomField', 'create', [
      'label' => 'Test Date Field',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'date_format' => 'mm/dd/yy',
      'custom_group_id' => $customGroup['id'],
    ]);

    $this->fixtures['contact_custom_token'] = [
      'id' => $customField['id'],
      'token' => sprintf('{contact.custom_%s}', $customField['id']),
      'name' => sprintf('custom_%s', $customField['id']),
      'value' => 'text ' . substr(sha1(mt_rand()), 0, 7),
    ];

    $this->fixtures['sched_on_custom_date'] = [
      'name' => 'sched_on_custom_date',
      'title' => 'sched_on_custom_date',
      'body_html' => '<p>Send reminder before 1 hour of custom date field</p>',
      'body_text' => 'Send reminder on custom date field',
      'subject' => 'Send reminder on custom date field',
      'mapping_id' => 6,
      'entity_value' => 'custom_' . $customDateField['id'],
      'entity_status' => 2,
      'entity' => [
        6,
        ['custom_' . $customDateField['id']],
        [1],
      ],
      'start_action_offset' => 1,
      'start_action_unit' => 'hour',
      'start_action_condition' => 'before',
      'start_action_date' => 'date_field',
      'record_activity' => 1,
      'repetition_frequency_unit' => 'hour',
      'end_frequency_unit' => 'hour',
      'end_action' => 'before',
      'end_date' => 'date_field',
      'custom_field_name' => 'custom_' . $customDateField['id'],
    ];
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   *
   * This method is called after a test is executed.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \API_Exception
   */
  public function tearDown(): void {
    $this->deleteTestObjects();
    MembershipType::delete()->addWhere('name', 'NOT IN', ['General', 'Student', 'Lifetime'])->execute();
    $this->quickCleanup([
      'civicrm_action_schedule',
      'civicrm_action_log',
      'civicrm_membership',
      'civicrm_line_item',
      'civicrm_participant',
      'civicrm_event',
      'civicrm_email',
    ], TRUE);
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Get a usable membership type id - creating one if none exists.
   *
   * It should exist but this class over-deletes in not-fully-diagnosed places.
   *
   * @throws \API_Exception
   */
  protected function getMembershipTypeID(): int {
    $generalTypeID = CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'membership_type_id', 'General');
    if ($generalTypeID) {
      $this->membershipTypeID = $generalTypeID;
    }
    else {
      $this->membershipTypeID = (int) MembershipType::create()
        ->setValues([
          'name' => 'General',
          'period_type' => 'rolling',
          'member_of_contact_id' => 1,
          'financial_type_id:name' => 'Member Dues',
          'duration_unit' => 1,
        ]
      )->execute()->first()['id'];
    }
    return $this->membershipTypeID;
  }

  /**
   * Get mailer examples.
   *
   * @return array
   */
  public function mailerExamples(): array {
    $cases = [];

    // Some tokens - short as subject has 128char limit in DB.
    $someTokensTmpl = implode(';;', [
      // basic contact token
      '{contact.display_name}',
      // funny legacy contact token
      '{contact.gender}',
      // domain token
      '{domain.name}',
      // action-scheduler token
      '{activity.activity_type}',
    ]);
    // Further tokens can be tested in the body text/html.
    // We use a dummy string to represent the custom token as this is done in setUp which is run after this function is called.
    $manyTokensTmpl = implode(';;', [
      $someTokensTmpl,
      '{contact.email_greeting}',
      '{contactCustomToken}',
    ]);
    // Note: The behavior of domain-tokens on a scheduled reminder is undefined. All we
    // can really do is check that it has something.
    $someTokensExpected = 'Churmondleia Ōtākou;;Female;;[a-zA-Z0-9 ]+;;Phone Call';
    $manyTokensExpected = sprintf('%s;;Dear Churmondleia;;%s', $someTokensExpected, '{contactCustomTokenValue}');

    // In this example, we use a lot of tokens cutting across multiple components.
    $cases[0] = [
      // Schedule definition.
      [
        'subject' => "subj $someTokensTmpl",
        'body_html' => "html $manyTokensTmpl",
        'body_text' => "text $manyTokensTmpl",
      ],
      // Assertions (regex).
      [
        'from_name' => '/^FIXME$/',
        'from_email' => '/^info@EXAMPLE.ORG$/',
        'subject' => "/^subj $someTokensExpected\$/",
        'body_html' => "/^html $manyTokensExpected\$/",
        'body_text' => "/^text $manyTokensExpected\$/",
      ],
    ];

    // In this example, we customize the from address.
    $cases[1] = [
      // Schedule definition.
      [
        'from_name' => 'Bob',
        'from_email' => 'bob@example.org',
      ],
      // Assertions (regex).
      [
        'from_name' => '/^Bob$/',
        'from_email' => '/^bob@example.org$/',
      ],
    ];

    // In this example, we auto-convert HTML to text
    $cases[2] = [
      // Schedule definition.
      [
        'body_html' => '<p>Hello &amp; stuff.</p>',
        'body_text' => '',
      ],
      // Assertions (regex).
      [
        'body_html' => '/^' . preg_quote('<p>Hello &amp; stuff.</p>', '/') . '/',
        'body_text' => '/^' . preg_quote('Hello & stuff.', '/') . '/',
      ],
    ];

    // In this example, we autoconvert HTML to text
    $cases[3] = [
      // Schedule definition.
      [
        'body_html' => '',
        'body_text' => 'Hello world',
      ],
      // Assertions (regex).
      [
        'body_html' => '/^--UNDEFINED--$/',
        'body_text' => '/^Hello world$/',
      ],
    ];

    // In this example, we test activity tokens
    $activityTokens = '{activity.subject};;{activity.details};;{activity.activity_date_time}';
    $activity = [
      'status_id' => 1,
      'activity_type_id' => 2,
      'activity_date_time' => '20120615100000',
      'is_current_revision' => 1,
      'is_deleted' => 0,
      'subject' => 'Phone call',
      'details' => 'A phone call about a bear',
    ];
    $activityTokensExpected = "Phone call;;A phone call about a bear;;June 15th, 2012 10:00 AM";
    $cases[4] = [
      // Schedule definition.
      [
        'subject' => "subj $someTokensTmpl",
        'body_html' => "html {$activityTokens}",
        'body_text' => "text {$activityTokens}",
      ],
      // Assertions (regex).
      [
        'from_name' => '/^FIXME$/',
        'from_email' => '/^info@EXAMPLE.ORG$/',
        'subject' => "/^subj $someTokensExpected\$/",
        'body_html' => "/^html $activityTokensExpected\$/",
        'body_text' => "/^text $activityTokensExpected\$/",
      ],
    ];

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
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @dataProvider mailerExamples
   */
  public function testMailer(array $schedule, array $patterns): void {
    // Replace the dummy custom contact token referecnes in schedule and patterns that we had to insert because phpunit
    // evaluates dataProviders before running setUp
    foreach ($schedule as $type => $content) {
      $schedule[$type] = str_replace('{contactCustomToken}', $this->fixtures['contact_custom_token']['token'], $content);
    }
    foreach ($patterns as $type => $content) {
      $patterns[$type] = str_replace('{contactCustomTokenValue}', $this->fixtures['contact_custom_token']['value'], $content);
    }
    $this->createScheduleFromFixtures('sched_activity_1day', $schedule);
    $activity = $this->createTestObject('CRM_Activity_DAO_Activity', $this->fixtures['phone_call']);
    $contact = $this->callAPISuccess('contact', 'create', array_merge(
      $this->fixtures['contact'],
      [
        $this->fixtures['contact_custom_token']['name'] => $this->fixtures['contact_custom_token']['value'],
      ]
    ));
    $activity->save();

    ActivityContact::create(FALSE)->setValues([
      'contact_id' => $contact['id'],
      'activity_id' => $activity->id,
      'record_type_id:name' => 'Activity Source',
    ])->execute();

    CRM_Utils_Time::setTime('2012-06-14 15:00:00');
    $this->callAPISuccess('job', 'send_reminder');
    $this->mut->assertRecipients([['test-member@example.com']]);
    foreach ($this->mut->getAllMessages('ezc') as $message) {
      /** @var ezcMail $message */

      $messageArray = [];
      $messageArray['subject'] = $message->subject;
      $messageArray['from_name'] = $message->from->name;
      $messageArray['from_email'] = $message->from->email;
      $messageArray['body_text'] = '--UNDEFINED--';
      $messageArray['body_html'] = '--UNDEFINED--';

      foreach ($message->fetchParts() as $part) {
        /** @var ezcMailText ezcMailText */
        if ($part instanceof ezcMailText && $part->subType === 'html') {
          $messageArray['body_html'] = $part->text;
        }
        if ($part instanceof ezcMailText && $part->subType === 'plain') {
          $messageArray['body_text'] = $part->text;
        }
      }

      foreach ($patterns as $field => $pattern) {
        $this->assertRegExp($pattern, $messageArray[$field],
          "Check that '$field'' matches regex. " . print_r(['expected' => $patterns, 'actual' => $messageArray], 1));
      }
    }
    $this->mut->clearMessages();
  }

  /**
   * Send reminder 1 hour before custom date field
   *
   * @throws \CRM_Core_Exception
   */
  public function testReminderWithCustomDateField(): void {
    $this->createScheduleFromFixtures('sched_on_custom_date');
    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], [$this->fixtures['sched_on_custom_date']['custom_field_name'] => '04/06/2021']));
    $this->assertCronRuns([
      [
        // Before the 24-hour mark, no email
        'time' => '2021-04-02 04:00:00',
        'recipients' => [],
        'subjects' => [],
      ],
      [
        // After the 24-hour mark, an email
        'time' => '2021-04-05 23:00:00',
        'recipients' => [['test-member@example.com']],
        'subjects' => ['Send reminder on custom date field'],
      ],
      [
        // Run cron again; message already sent
        'time' => '',
        'recipients' => [],
      ],
    ]);
  }

  /**
   * Test calculated activity schedule.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function testActivityDateTimeMatchNonRepeatableSchedule(): void {
    $this->createScheduleFromFixtures('sched_activity_1day');

    $activity = $this->createTestObject('CRM_Activity_DAO_Activity', $this->fixtures['phone_call']);
    $contact = $this->callAPISuccess('contact', 'create', $this->fixtures['contact']);
    $activity->subject = 'Test subject for phone_call';
    $activity->save();

    $source['contact_id'] = $contact['id'];
    $source['activity_id'] = $activity->id;
    $source['record_type_id'] = 2;
    $activityContact = $this->createTestObject('CRM_Activity_DAO_ActivityContact', $source);
    $activityContact->save();

    $this->assertCronRuns([
      [
        // Before the 24-hour mark, no email
        'time' => '2012-06-14 04:00:00',
        'recipients' => [],
        'subjects' => [],
      ],
      [
        // After the 24-hour mark, an email
        'time' => '2012-06-14 15:00:00',
        'recipients' => [['test-member@example.com']],
        'subjects' => ['1-Day (non-repeating) (about Phone Call)'],
      ],
      [
        // Run cron again; message already sent
        'time' => '',
        'recipients' => [],
      ],
    ]);
    $activities = Activity::get(FALSE)
      ->setSelect(['details'])
      ->addWhere('activity_type_id:name', '=', 'Reminder Sent')
      ->addWhere('source_record_id', '=', $activity->id)
      ->execute();
    foreach ($activities as $activityDetails) {
      $this->assertStringContainsString($activity->subject, $activityDetails['details']);
    }
  }

  /**
   * Test schedule creation on repeatable schedule.
   *
   * @throws \CRM_Core_Exception
   */
  public function testActivityDateTimeMatchRepeatableSchedule(): void {
    $this->createScheduleFromFixtures('sched_activity_1day_r');
    $this->createActivityAndContactFromFixtures();

    $this->assertCronRuns([
      [
        // Before the 24-hour mark, no email
        'time' => '2012-06-14 04:00:00',
        'recipients' => [],
        'subjects' => [],
      ],
      [
        // After the 24-hour mark, an email
        'time' => '2012-06-14 15:00:00',
        'recipients' => [['test-member@example.com']],
        'subjects' => ['1-Day (repeating) (about Phone Call)'],
      ],
      [
        // Run cron 4 hours later; first message already sent
        'time' => '2012-06-14 20:00:00',
        'recipients' => [],
        'subjects' => [],
      ],
      [
        // Run cron 6 hours later; send second message.
        'time' => '2012-06-14 21:00:01',
        'recipients' => [['test-member@example.com']],
        'subjects' => ['1-Day (repeating) (about Phone Call)'],
      ],
    ]);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testActivityDateTimeMatchRepeatableScheduleOnAbsDate(): void {
    $this->createScheduleFromFixtures('sched_activity_1day_r_on_abs_date');
    $this->createActivityAndContactFromFixtures();

    $this->assertCronRuns([
      [
        // Before the 24-hour mark, no email
        'time' => '2012-06-13 04:00:00',
        'recipients' => [],
        'subjects' => [],
      ],
      [
        // On absolute date set on 2012-06-14
        'time' => '2012-06-14 00:00:00',
        'recipients' => [['test-member@example.com']],
        'subjects' => ['1-Day (repeating) (about Phone Call)'],
      ],
      [
        // Run cron 4 hours later; first message already sent
        'time' => '2012-06-14 04:00:00',
        'recipients' => [],
        'subjects' => [],
      ],
      [
        // Run cron 6 hours later; send second message.
        'time' => '2012-06-14 06:00:01',
        'recipients' => [['test-member@example.com']],
        'subjects' => ['1-Day (repeating) (about Phone Call)'],
      ],
    ]);
  }

  /**
   * Test event with only an absolute date.
   *
   * @throws \CRM_Core_Exception
   */
  public function testEventNameWithAbsoluteDateAndNothingElse(): void {
    $participant = $this->createTestObject('CRM_Event_DAO_Participant', array_merge($this->fixtures['participant'], ['status_id' => 1]));
    $this->callAPISuccess('Email', 'create', [
      'contact_id' => $participant->contact_id,
      'email' => 'test-event@example.com',
    ]);
    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], ['contact_id' => $participant->contact_id]));

    $actionSchedule = $this->fixtures['sched_event_name_1day_on_abs_date'];
    $actionSchedule['entity_value'] = $participant->event_id;
    $this->callAPISuccess('action_schedule', 'create', $actionSchedule);

    $this->assertCronRuns([
      [
        // Before the 24-hour mark, no email
        'time' => '2012-06-13 04:00:00',
        'recipients' => [],
        'subjects' => [],
      ],
      [
        // On absolute date set on 2012-06-14
        'time' => '2012-06-14 00:00:00',
        'recipients' => [['test-event@example.com']],
        'subjects' => ['sched_event_name_1day_on_abs_date'],
      ],
      [
        // Run cron 4 hours later; first message already sent
        'time' => '2012-06-14 04:00:00',
        'recipients' => [],
        'subjects' => [],
      ],
    ]);
  }

  /**
   * For contacts/members which match schedule based on join/start date,
   * an email should be sent.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMembershipDateMatch(): void {
    $contactID = $this->individualCreate(array_merge($this->fixtures['contact'], ['email' => 'test-member@example.com']));
    $membershipTypeID = $this->getMembershipTypeID();
    $membership = (array) $this->callAPISuccess('Membership', 'create', array_merge($this->fixtures['rolling_membership'], ['status_id' => 1, 'contact_id' => $contactID, 'sequential' => 1, 'membership_type_id' => $membershipTypeID]))['values'][0];
    $this->createScheduleFromFixtures('sched_membership_join_2week', ['entity_value' => $membershipTypeID]);

    // start_date=2012-03-15 ; schedule is 2 weeks after join_date
    $this->assertCronRuns([
      [
        // Before the 2-week mark, no email.
        'time' => '2012-03-28 01:00:00',
        'recipients' => [],
        'subjects' => [],
      ],
      [
        // After the 2-week mark, send an email.
        'time' => '2012-03-29 01:00:00',
        'recipients' => [['test-member@example.com']],
        'subjects' => ['subject sched_membership_join_2week (joined March 15th, 2012)'],
      ],
    ]);

    $this->createScheduleFromFixtures('sched_membership_start_1week', ['entity_value' => $membership['membership_type_id']]);

    // start_date=2012-03-15 ; schedule is 1 weeks after start_date
    $this->assertCronRuns([
      [
        // Before the 2-week mark, no email.
        'time' => '2012-03-21 01:00:00',
        'recipients' => [],
        'subjects' => [],
      ],
      [
        // After the 2-week mark, send an email.
        'time' => '2012-03-22 01:00:00',
        'recipients' => [['test-member@example.com']],
        'subjects' => ['subject sched_membership_start_1week (joined March 15th, 2012)'],
      ],
    ]);
  }

  /**
   * CRM-21675: Support parent and smart group in 'Limit to' field
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testScheduleReminderWithParentGroup(): void {
    // Contact A with birth-date at '07-07-2005' and gender - Male, later got added in smart group
    $this->individualCreate(['birth_date' => '20050707', 'gender_id' => 1, 'email' => 'abc@test.com']);
    // Contact B with birth-date at '07-07-2005', later got added in regular group
    $contactID2 = $this->individualCreate(['birth_date' => '20050707', 'email' => 'def@test.com'], 1);
    // Contact C with birth-date at '07-07-2005', but not included in any group
    $this->individualCreate(['birth_date' => '20050707', 'email' => 'ghi@test.com'], 2);

    // create regular group and add Contact B to it
    $groupID = $this->groupCreate();
    $this->callAPISuccess('GroupContact', 'Create', [
      'group_id' => $groupID,
      'contact_id' => $contactID2,
    ]);

    // create smart group which will contain all Male contacts
    $smartGroupParams = ['form_values' => ['gender_id' => 1]];
    $smartGroupID = $this->smartGroupCreate(
      $smartGroupParams,
      [
        'name' => 'new_smart_group',
        'title' => 'New Smart Group',
        'parents' => [$groupID => 1],
      ]
    );

    $actionScheduleParams = [
      'name' => 'sched_contact_birth_day_yesterday',
      'title' => 'sched_contact_birth_day_yesterday',
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
      'subject' => 'subject sched_contact_birth_day_yesterday',
    ];

    // Create schedule reminder where parent group ($groupID) is selected to limit recipients,
    // which contain a individual contact - $contactID2 and is parent to smart group.
    $this->callAPISuccess('ActionSchedule', 'create', $actionScheduleParams);
    $this->assertCronRuns([
      [
        // On the birthday, no email.
        'time' => '2005-07-07 01:00:00',
        'recipients' => [],
      ],
      [
        // The next day, send an email.
        'time' => '2005-07-08 20:00:00',
        'recipients' => [
          [
            'def@test.com',
          ],
          [
            'abc@test.com',
          ],
        ],
      ],
    ]);
    $this->groupDelete($smartGroupID);
    $this->groupDelete($groupID);
  }

  /**
   * Test end date email sent.
   *
   * For contacts/members which match schedule based on join date,
   * an email should be sent.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMembershipJoinDateNonMatch(): void {
    $this->createMembershipFromFixture('rolling_membership', '', ['email' => 'test-member@example.com']);
    // Add an alternative membership type, and only send messages for that type
    $extraMembershipType = $this->createTestObject('CRM_Member_DAO_MembershipType', []);
    $this->createScheduleFromFixtures('sched_membership_join_2week', ['entity_value' => $extraMembershipType->id]);

    // start_date=2012-03-15 ; schedule is 2 weeks after start_date
    $this->assertCronRuns([
      [
        // After the 2-week mark, don't send email because we have different membership type.
        'time' => '2012-03-29 01:00:00',
        'recipients' => [],
      ],
    ]);
  }

  /**
   * Test that the first and SECOND notifications are sent out.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMembershipEndDateRepeat(): void {
    // creates membership with end_date = 20120615
    $membership = $this->createMembershipFromFixture('rolling_membership', 'Current');
    $this->callAPISuccess('Email', 'create', [
      'contact_id' => $membership['contact_id'],
      'email' => 'test-member@example.com',
    ]);
    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], ['contact_id' => $membership['contact_id']]));

    $this->createScheduleFromFixtures('sched_membership_end_2month_repeat_twice_4_weeks', ['entity_value' => $membership['membership_type_id']]);

    // end_date=2012-06-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns([
      [
        // After the 1-month mark, no email
        'time' => '2012-07-15 01:00:00',
        'recipients' => [],
      ],
      [
        // After the 2-month mark, send an email.
        'time' => '2012-08-15 01:00:00',
        'recipients' => [['test-member@example.com']],
      ],
      [
        // 4 weeks after first email send first repeat
        'time' => '2012-09-12 01:00:00',
        'recipients' => [['test-member@example.com']],
      ],
      [
        // 1 week after first repeat send nothing
        // There was a bug where the first repeat went out and then
        // it would keep going out every cron run. This is to check that's
        // not happening.
        'time' => '2012-09-19 01:00:00',
        'recipients' => [],
      ],
      [
        // 4 weeks after first repeat send second repeat
        'time' => '2012-10-10 01:00:00',
        'recipients' => [['test-member@example.com']],
      ],
      [
        // 4 months after membership end, send nothing
        'time' => '2012-10-15 01:00:00',
        'recipients' => [],
      ],
      [
        // 5 months after membership end, send nothing
        'time' => '2012-11-15 01:00:00',
        'recipients' => [],
      ],
    ]);
  }

  /**
   * Test behaviour when date changes.
   *
   * Test that the first notification is sent but the second is NOT sent if the end date changes in
   * between
   *  see CRM-15376
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMembershipEndDateRepeatChangedEndDate_CRM_15376(): void {
    // creates membership with end_date = 20120615
    $membership = $this->createMembershipFromFixture('rolling_membership', 'Current');
    $this->callAPISuccess('Email', 'create', [
      'contact_id' => $membership['contact_id'],
      'email' => 'test-member@example.com',
    ]);
    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], ['contact_id' => $membership['contact_id']]));

    $this->createScheduleFromFixtures('sched_membership_end_2month_repeat_twice_4_weeks', ['entity_value' => $membership['membership_type_id']]);
    // end_date=2012-06-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns([
      [
        // After the 2-week mark, send an email.
        'time' => '2012-08-15 01:00:00',
        'recipients' => [['test-member@example.com']],
      ],
    ]);

    // Extend membership - reminder should NOT go out.
    $this->callAPISuccess('membership', 'create', ['id' => $membership['id'], 'end_date' => '2014-01-01']);
    $this->assertCronRuns([
      [
        // After the 2-week mark, send an email.
        'time' => '2012-09-12 01:00:00',
        'recipients' => [],
      ],
    ]);
  }

  /**
   * Test membership end date email sends.
   *
   * For contacts/members which match schedule based on end date,
   * an email should be sent.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMembershipEndDateMatch(): void {
    // creates membership with end_date = 20120615
    $membership = $this->createMembershipFromFixture('rolling_membership', 'Current');
    $this->callAPISuccess('Email', 'create', [
      'contact_id' => $membership['contact_id'],
      'email' => 'test-member@example.com',
    ]);
    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], ['contact_id' => $membership['contact_id']]));

    $this->createScheduleFromFixtures('sched_membership_end_2week', [
      'entity_value' => $membership['membership_type_id'],
      'effective_start_date' => '2012-06-01 00:00:00',
    ]);

    // end_date=2012-06-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns([
      [
        // Before the 2-week mark, no email.
        'time' => '2012-05-31 01:00:00',
        'recipients' => [],
      ],
      [
        // After the 2-week mark, send an email.
        'time' => '2012-06-01 01:00:00',
        'recipients' => [['test-member@example.com']],
      ],
      [
        // After the email is sent, another one is not sent
        'time' => '2012-06-01 02:00:00',
        'recipients' => [],
      ],
    ]);

    // Now suppose user has renewed for rolling membership after 3 months, so upcoming assertion is written
    // to ensure that new reminder is sent 2 week before the new end_date i.e. '2012-09-15'
    $membershipBAO = new CRM_Member_BAO_Membership();
    $membershipBAO->id = $membership['id'];
    $membershipBAO->end_date = '2012-09-15';
    $membershipBAO->save();

    //change the email id of chosen membership contact to assert
    //recipient of not the previously sent mail but the new one
    $result = $this->callAPISuccess('Email', 'create', [
      'is_primary' => 1,
      'contact_id' => $membership['contact_id'],
      'email' => 'member2@example.com',
    ]);
    $this->assertAPISuccess($result);

    // end_date=2012-09-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns([
      [
        // Before the 2-week mark, no email
        'time' => '2012-08-31 01:00:00',
        'recipients' => [],
      ],
      [
        // After the 2-week mark, send an email
        'time' => '2012-09-01 01:00:00',
        'recipients' => [['member2@example.com']],
      ],
      [
        // After the email is sent, another one is not sent
        'time' => '2012-09-01 02:00:00',
        'recipients' => [],
      ],
    ]);
    $membershipBAO = new CRM_Member_BAO_Membership();
    $membershipBAO->id = $membership['id'];
    $membershipBAO->end_date = '2012-12-15';
    $membershipBAO->save();
    // end_date=2012-12-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns([
      [
        // Before the 2-week mark, no email
        'time' => '2012-11-30 01:00:00',
        'recipients' => [],
      ],
      [
        // After the 2-week mark, send an email
        'time' => '2012-12-01 01:00:00',
        'recipients' => [['member2@example.com']],
      ],
      [
        // After the email is sent, another one is not sent
        'time' => '2012-12-01 02:00:00',
        'recipients' => [],
      ],
    ]);

  }

  /**
   * This test is very similar to testMembershipEndDateMatch, but it adds
   * another contact because there was a bug in
   * RecipientBuilder::buildRelFirstPass where it was only sending the
   * reminder for the first contact returned in a query for renewed
   * memberships. Other contacts wouldn't get the mail.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMultipleMembershipEndDateMatch(): void {
    $contactID = $this->callAPISuccess('Contact', 'create', array_merge($this->fixtures['contact'], ['email' => 'test-member@example.com']))['id'];
    $contactID2 = $this->callAPISuccess('Contact', 'create', $this->fixtures['contact_2'])['id'];
    $membershipOne = $this->createMembershipFromFixture('rolling_membership', 2, [], ['contact_id' => $contactID]);
    $membershipTypeId = $membershipOne['membership_type_id'];
    $membershipTwo = $this->createMembershipFromFixture('rolling_membership', 2, [], ['contact_id' => $contactID2, 'membership_type_id' => $membershipTypeId]);
    // We are using dates that 'should' be expired but the test expects them not to be
    CRM_Core_DAO::executeQuery('UPDATE civicrm_membership SET status_id = 2 WHERE 1');
    $this->createScheduleFromFixtures('sched_membership_end_2week', ['entity_value' => $membershipTypeId]);

    // end_date=2012-06-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns([
      [
        // Before the 2-week mark, no email.
        'time' => '2012-05-31 01:00:00',
        'recipients' => [],
      ],
      [
        // After the 2-week mark, send emails.
        'time' => '2012-06-01 01:00:00',
        'recipients' => [
          ['test-member@example.com'],
          ['test-contact-2@example.com'],
        ],
      ],
      [
        // After the email is sent, another one is not sent
        'time' => '2012-06-01 02:00:00',
        'recipients' => [],
      ],
    ]);

    // Now suppose user has renewed for rolling membership after 3 months, so upcoming assertion is written
    // to ensure that new reminder is sent 2 week before the new end_date i.e. '2012-09-15'
    $membershipOneBAO = new CRM_Member_BAO_Membership();
    $membershipOneBAO->id = $membershipOne['id'];
    $membershipOneBAO->end_date = '2012-09-15';
    $membershipOneBAO->save();
    $membershipTwoBAO = new CRM_Member_BAO_Membership();
    $membershipTwoBAO->id = $membershipTwo['id'];
    $membershipTwoBAO->end_date = '2012-09-15';
    $membershipTwoBAO->save();

    // end_date=2012-09-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns([
      [
        // Before the 2-week mark, no email
        'time' => '2012-08-31 01:00:00',
        'recipients' => [],
      ],
      [
        // After the 2-week mark, send an email
        'time' => '2012-09-01 01:00:00',
        'recipients' => [
          ['test-member@example.com'],
          ['test-contact-2@example.com'],
        ],
      ],
      [
        // After the email is sent, another one is not sent
        'time' => '2012-06-01 02:00:00',
        'recipients' => [],
      ],
    ]);
  }

  /**
   * Test membership end date email.
   *
   * For contacts/members which match schedule based on end date,
   * an email should be sent.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMembershipEndDateNoMatch(): void {
    // creates membership with end_date = 20120615
    $membership = $this->createMembershipFromFixture('rolling_membership', 'Grace');
    $this->callAPISuccess('Email', 'create', [
      'contact_id' => $membership['contact_id'],
      'email' => 'test-member@example.com',
    ]);
    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], ['contact_id' => $membership['contact_id']]));
    $this->createScheduleFromFixtures('sched_membership_end_2month', ['entity_value' => $membership['membership_type_id']]);

    // end_date=2012-06-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns([
      [
        // Before the 2-week mark, no email.
        'time' => '2012-05-31 01:00:00',
        'recipients' => [],
      ],
      [
        // After the 2-week mark, no email
        'time' => '2013-05-01 01:00:00',
        'recipients' => [],
      ],
    ]);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testContactBirthDateNoAnniversary(): void {
    $contact = $this->callAPISuccess('Contact', 'create', $this->fixtures['contact_birthdate']);
    $this->_testObjects['CRM_Contact_DAO_Contact'][] = $contact['id'];
    $this->createScheduleFromFixtures('sched_contact_birth_day_yesterday');
    $this->assertCronRuns([
      [
        // On the birthday, no email.
        'time' => '2005-07-07 01:00:00',
        'recipients' => [],
      ],
      [
        // The next day, send an email.
        'time' => '2005-07-08 20:00:00',
        'recipients' => [['test-birth_day@example.com']],
      ],
    ]);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testContactBirthDateAnniversary(): void {
    $contact = $this->callAPISuccess('Contact', 'create', $this->fixtures['contact_birthdate']);
    $this->_testObjects['CRM_Contact_DAO_Contact'][] = $contact['id'];
    $this->createScheduleFromFixtures('sched_contact_birth_day_anniversary');
    $this->assertCronRuns([
      [
        // On some random day, no email.
        'time' => '2014-03-07 01:00:00',
        'recipients' => [],
      ],
      [
        // On the eve of their 9th birthday, send an email.
        'time' => '2014-07-06 20:00:00',
        'recipients' => [['test-birth_day@example.com']],
      ],
    ]);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testContactCustomDateNoAnniversary(): void {
    $group = [
      'title' => 'Test_Group',
      'name' => 'test_group',
      'extends' => ['Individual'],
      'style' => 'Inline',
      'is_multiple' => FALSE,
      'is_active' => 1,
    ];
    $createGroup = $this->callAPISuccess('custom_group', 'create', $group);
    $field = [
      'label' => 'Graduation',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'custom_group_id' => $createGroup['id'],
    ];
    $createField = $this->callAPISuccess('custom_field', 'create', $field);
    $contactParams = $this->fixtures['contact'];
    $contactParams["custom_{$createField['id']}"] = '2013-12-16';
    $contact = $this->callAPISuccess('Contact', 'create', $contactParams);
    $this->_testObjects['CRM_Contact_DAO_Contact'][] = $contact['id'];
    $this->createScheduleFromFixtures('sched_contact_grad_tomorrow', ['entity_value' => "custom_{$createField['id']}"]);
    $this->assertCronRuns([
      [
        // On some random day, no email.
        'time' => '2014-03-07 01:00:00',
        'recipients' => [],
      ],
      [
        // On the eve of their graduation, send an email.
        'time' => '2013-12-15 20:00:00',
        'recipients' => [['test-member@example.com']],
      ],
    ]);
    $this->callAPISuccess('custom_group', 'delete', ['id' => $createGroup['id']]);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testContactCreatedNoAnniversary(): void {
    $contact = $this->callAPISuccess('Contact', 'create', $this->fixtures['contact_birthdate']);
    $this->createScheduleFromFixtures('sched_contact_created_yesterday');
    $this->assertCronRuns([
      [
        // On the date created, no email.
        'time' => $contact['values'][$contact['id']]['created_date'],
        'recipients' => [],
      ],
      [
        // The next day, send an email.
        'time' => date('Y-m-d H:i:s', strtotime($contact['values'][$contact['id']]['created_date'] . ' +1 day')),
        'recipients' => [['test-birth_day@example.com'], ['fixme.domainemail@example.org'], ['domainemail2@example.org']],
      ],
    ]);
  }

  /**
   * Test the impact of changing the anniversary.
   *
   * @throws \CRM_Core_Exception
   */
  public function testContactModifiedAnniversary(): void {
    $contact = $this->callAPISuccess('Contact', 'create', $this->fixtures['contact_birthdate']);
    $this->_testObjects['CRM_Contact_DAO_Contact'][] = $contact['id'];
    $modifiedDate = $this->callAPISuccess('Contact', 'getvalue', ['id' => $contact['id'], 'return' => 'modified_date']);
    $actionSchedule = $this->createScheduleFromFixtures('sched_contact_mod_anniversary');
    $actionSchedule['effective_start_date'] = date('Y-m-d H:i:s', strtotime($contact['values'][$contact['id']]['modified_date']));
    $actionScheduleDao = CRM_Core_BAO_ActionSchedule::add($actionSchedule);
    $this->assertCronRuns([
      [
        // On some random day, no email.
        'time' => date('Y-m-d H:i:s', strtotime($contact['values'][$contact['id']]['modified_date'] . ' -60 days')),
        'recipients' => [],
      ],
      [
        // On the eve of 3 years after they were modified, send an email.
        'time' => date('Y-m-d H:i:s', strtotime($modifiedDate . ' +3 years -1 day')),
        'recipients' => [['test-birth_day@example.com'], ['fixme.domainemail@example.org'], ['domainemail2@example.org']],
      ],
    ]);
  }

  /**
   * Check that limit_to + an empty recipients doesn't sent to multiple
   * contacts.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMembershipLimitToNone(): void {
    // creates membership with end_date = 20120615
    $membership = $this->createMembershipFromFixture('rolling_membership', 'Current');
    $result = $this->callAPISuccess('Email', 'create', [
      'contact_id' => $membership['contact_id'],
      'email' => 'member@example.com',
    ]);
    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], ['contact_id' => $membership['contact_id']]));
    $this->callAPISuccess('contact', 'create', ['email' => 'b@c.com', 'contact_type' => 'Individual']);

    $this->assertAPISuccess($result);

    $this->createScheduleFromFixtures('sched_membership_end_limit_to_none', ['entity_value' => $membership['membership_type_id']]);

    // end_date=2012-06-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns([
      [
        // Before the 2-week mark, no email.
        'time' => '2012-05-31 01:00:00',
        'recipients' => [],
      ],
    ]);
  }

  /**
   * Test handling of reference date for memberships.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMembershipWithReferenceDate(): void {
    $membership = $this->createMembershipFromFixture('rolling_membership', 'Current', ['email' => 'member@example.com']);
    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], ['contact_id' => $membership['contact_id']]));

    $this->createScheduleFromFixtures('sched_membership_join_2week', ['entity_value' => $membership['membership_type_id']]);

    // start_date=2012-03-15 ; schedule is 2 weeks after start_date
    $this->assertCronRuns([
      [
        // After the 2-week mark, send an email
        'time' => '2012-03-29 01:00:00',
        'recipients' => [['member@example.com']],
      ],
      [
        // After the 2-week 1day mark, don't send an email
        'time' => '2012-03-30 01:00:00',
        'recipients' => [],
      ],
    ]);

    //check if reference date is set to membership's join date
    //as per the action_start_date chosen for current schedule reminder
    $this->assertEquals('2012-03-15 00:00:00',
      CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionLog', $membership['contact_id'], 'reference_date', 'contact_id')
    );

    //change current membership join date that may signifies as membership renewal activity
    $membershipBAO = new CRM_Member_BAO_Membership();
    $membershipBAO->id = $membership['id'];
    $membershipBAO->join_date = '2012-03-29';
    $membershipBAO->save();

    $this->assertCronRuns([
      [
        // After the 13 days of the changed join date 2012-03-29, don't send an email
        'time' => '2012-04-11 01:00:00',
        'recipients' => [],
      ],
      [
         // After the 2-week of the changed join date 2012-03-29, send an email
        'time' => '2012-04-12 01:00:00',
        'recipients' => [['member@example.com']],
      ],
    ]);
    $this->assertCronRuns([
      [
        // It should not re-send on the same day
        'time' => '2012-04-12 01:00:00',
        'recipients' => [],
      ],
    ]);
  }

  /**
   * Test multiple membership reminder.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMembershipOnMultipleReminder(): void {
    $membership = $this->createMembershipFromFixture('rolling_membership', 'Current', ['email' => 'member@example.com']);
    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], ['contact_id' => $membership['contact_id']]));

    // Send email 2 weeks before end_date
    $actionScheduleBefore = $this->fixtures['sched_membership_end_2week'];
    // Send email on end_date/expiry date
    $actionScheduleOn = $this->fixtures['sched_on_membership_end_date'];
    $actionScheduleOn['effective_start_date'] = '2012-06-14 00:00:00';
    $actionScheduleAfter['effective_end_date'] = '2012-06-15 01:00:00';
    // Send email 1 day after end_date/grace period
    $actionScheduleAfter = $this->fixtures['sched_after_1day_membership_end_date'];
    $actionScheduleAfter['effective_start_date'] = '2012-06-15 01:00:00';
    $actionScheduleAfter['effective_end_date'] = '2012-06-16 02:00:00';
    $actionScheduleBefore['entity_value'] = $actionScheduleOn['entity_value'] = $actionScheduleAfter['entity_value'] = $membership['membership_type_id'];
    foreach (['actionScheduleBefore', 'actionScheduleOn', 'actionScheduleAfter'] as $value) {
      $$value = CRM_Core_BAO_ActionSchedule::add($$value);
    }

    $this->assertCronRuns(
      [
        [
          // 1day 2weeks before membership end date(MED), don't send mail
          'time' => '2012-05-31 01:00:00',
          'recipients' => [],
        ],
        [
          // 2 weeks before MED, send an email
          'time' => '2012-06-01 01:00:00',
          'recipients' => [['member@example.com']],
        ],
        [
          // 1day before MED, don't send mail
          'time' => '2012-06-14 01:00:00',
          'recipients' => [],
        ],
        [
          // On MED, send an email
          'time' => '2012-06-15 00:00:00',
          'recipients' => [['member@example.com']],
        ],
        [
          // After 1day of MED, send an email
          'time' => '2012-06-16 01:00:00',
          'recipients' => [['member@example.com']],
        ],
        [
          // After 1day 1min of MED, don't send an email
          'time' => '2012-06-17 00:01:00',
          'recipients' => [],
        ],
      ]
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
    $membershipBAO = new CRM_Member_BAO_Membership();
    $membershipBAO->id = $membership['id'];
    $membershipBAO->end_date = '2012-06-20';
    $membershipBAO->save();

    // increase the effective end date to future
    $actionScheduleAfter->effective_end_date = '2012-07-22 00:00:00';
    $actionScheduleAfter->save();

    $this->callAPISuccess('Contact', 'get', ['id' => $membership['contact_id']]);
    $this->assertCronRuns(
      [
        [
          // 1day 2weeks before membership end date(MED), don't send mail
          'time' => '2012-06-05 01:00:00',
          'recipients' => [],
        ],
        [
          // 2 weeks before MED, send an email
          'time' => '2012-06-06 01:00:00',
          'recipients' => [['member@example.com']],
        ],
        [
          // 1day before MED, don't send mail
          'time' => '2012-06-19 01:00:00',
          'recipients' => [],
        ],
        [
          // On MED, send an email
          'time' => '2012-06-20 00:00:00',
          'recipients' => [['member@example.com']],
        ],
        [
          // After 1day of MED, send an email
          'time' => '2012-06-21 01:00:00',
          'recipients' => [['member@example.com']],
        ],
        [
          // After 1day 1min of MED, don't send an email
          'time' => '2012-07-21 00:01:00',
          'recipients' => [],
        ],
      ]);
  }

  /**
   * Test reminders sent on custom data anniversary.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function testContactCustomDate_Anniversary(): void {
    $this->createCustomGroupWithFieldOfType([], 'date');
    $contactParams = $this->fixtures['contact'];
    $contactParams[$this->getCustomFieldName('date')] = '2013-12-16';
    $contact = $this->callAPISuccess('Contact', 'create', $contactParams);
    $this->_testObjects['CRM_Contact_DAO_Contact'][] = $contact['id'];
    $this->fixtures['sched_contact_grad_anniversary']['entity_value'] = $this->getCustomFieldName('date');
    $this->createScheduleFromFixtures('sched_contact_grad_anniversary');

    $this->assertCronRuns([
      [
        // On some random day, no email.
        'time' => '2014-03-07 01:00:00',
        'recipients' => [],
      ],
      [
        // A week after their 5th anniversary of graduation, send an email.
        'time' => '2018-12-23 20:00:00',
        'recipients' => [['test-member@example.com']],
      ],
    ]);
  }

  /**
   * Test sched reminder set via registration date.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testEventTypeRegistrationDate(): void {
    $contact = $this->individualCreate(['email' => 'test-event@example.com']);
    //Add it as a participant to an event ending registration - 7 days from now.
    $params = [
      'start_date' => date('Ymd', strtotime('-5 day')),
      'end_date' => date('Ymd', strtotime('+7 day')),
      'registration_start_date' => date('Ymd', strtotime('-5 day')),
      'registration_end_date' => date('Ymd', strtotime('+7 day')),
    ];
    $event = $this->eventCreate($params);
    $this->participantCreate(['contact_id' => $contact, 'event_id' => $event['id']]);

    //Create a scheduled reminder to send email 7 days before registration date.
    $actionSchedule = $this->fixtures['sched_event_type_start_1week_before'];
    $actionSchedule['start_action_offset'] = 7;
    $actionSchedule['start_action_unit'] = 'day';
    $actionSchedule['start_action_date'] = 'registration_end_date';
    $actionSchedule['entity_value'] = $event['values'][$event['id']]['event_type_id'];
    $actionSchedule['entity_status'] = $this->callAPISuccessGetValue('ParticipantStatusType', [
      'return' => 'id',
      'name' => 'Attended',
    ]);
    $actionSched = $this->callAPISuccess('action_schedule', 'create', $actionSchedule);
    //Run the cron and verify if an email was sent.
    $this->assertCronRuns([
      [
        'time' => date('Y-m-d'),
        'recipients' => [['test-event@example.com']],
      ],
    ]);

    //Create contact 2
    $contactParams = [
      'email' => 'test-event2@example.com',
    ];
    $contact2 = $this->individualCreate($contactParams);
    //Create an event with registration end date = 2 week from now.
    $params['end_date'] = date('Ymd', strtotime('+2 week'));
    $params['registration_end_date'] = date('Ymd', strtotime('+2 week'));
    $event2 = $this->eventCreate($params);
    $this->participantCreate(['contact_id' => $contact2, 'event_id' => $event2['id']]);

    //Assert there is no reminder sent to the contact.
    $this->assertCronRuns([
      [
        'time' => date('Y-m-d'),
        'recipients' => [],
      ],
    ]);

    //Modify the sched reminder to be sent 2 week from registration end date.
    $this->callAPISuccess('action_schedule', 'create', [
      'id' => $actionSched['id'],
      'start_action_offset' => 2,
      'start_action_unit' => 'week',
    ]);

    //Contact should receive the reminder now.
    $this->assertCronRuns([
      [
        'time' => date('Y-m-d'),
        'recipients' => [['test-event2@example.com']],
      ],
    ]);
  }

  /**
   * Test sched reminder set via start date.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testEventTypeStartDate(): void {
    // Create event+participant with start_date = 20120315, end_date = 20120615.
    $params = $this->fixtures['participant'];
    $params['event_id'] = $this->callAPISuccess('Event', 'create', array_merge($this->fixtures['participant']['event_id'], ['event_type_id' => 1]))['id'];
    $params['status_id'] = 2;
    $params['contact_id'] = $this->individualCreate(array_merge($this->fixtures['contact'], ['email' => 'test-event@example.com']));
    $this->callAPISuccess('Participant', 'create', $params);

    $actionSchedule = $this->fixtures['sched_event_type_start_1week_before'];
    $actionSchedule['entity_value'] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $params['event_id'], 'event_type_id');
    $this->callAPISuccess('action_schedule', 'create', $actionSchedule);

    // end_date=2012-06-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns([
      [
        // 2 weeks before
        'time' => '2012-03-02 01:00:00',
        'recipients' => [],
      ],
      [
        // 1 week before
        'time' => '2012-03-08 01:00:00',
        'recipients' => [['test-event@example.com']],
      ],
      [
        // And then nothing else
        'time' => '2012-03-16 01:00:00',
        'recipients' => [],
      ],
    ]);

    // CASE 2: Create a schedule reminder which was created 1 day after the schdule day,
    // so it shouldn't deliver reminders schedule to send 1 week before the event start date
    $actionSchedule = $this->fixtures['sched_event_type_start_1week_before'];
    $actionSchedule['entity_value'] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $params['event_id'], 'event_type_id');
    $actionSchedule['effective_start_date'] = '20120309000000';
    $this->callAPISuccess('action_schedule', 'create', $actionSchedule);
    // end_date=2012-06-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns([
      [
        // 2 weeks before
        'time' => '2012-03-02 01:00:00',
        'recipients' => [],
      ],
      [
        // 1 week before
        'time' => '2012-03-08 01:00:00',
        'recipients' => [],
      ],
      [
        // And then nothing else
        'time' => '2012-03-16 01:00:00',
        'recipients' => [],
      ],
    ]);

    // CASE 3: Create a schedule reminder which is created less then a week before the event start date,
    // so it should deliver reminders schedule to send 1 week before the event start date, set the effective end date just an hour later the reminder delivery date
    $actionSchedule = $this->fixtures['sched_event_type_start_1week_before'];
    $actionSchedule['entity_value'] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $params['event_id'], 'event_type_id');
    $actionSchedule['effective_end_date'] = '20120309010000';
    $this->callAPISuccess('action_schedule', 'create', $actionSchedule);
    // end_date=2012-06-15 ; schedule is 2 weeks before end_date
    $this->assertCronRuns([
      [
        // 2 weeks before
        'time' => '2012-03-02 01:00:00',
        'recipients' => [],
      ],
      [
        // 1 week before
        'time' => '2012-03-08 01:00:00',
        'recipients' => [['test-event@example.com']],
      ],
      [
        // And then nothing else
        'time' => '2012-03-16 01:00:00',
        'recipients' => [],
      ],
    ]);
  }

  /**
   * Test schedule on event end date.
   *
   * @throws \CRM_Core_Exception
   */
  public function testEventTypeEndDateRepeat(): void {
    // Create event+participant with start_date = 20120315, end_date = 20120615.
    $participant = $this->createTestObject('CRM_Event_DAO_Participant', array_merge($this->fixtures['participant'], ['status_id' => 2]));
    $this->callAPISuccess('Email', 'create', [
      'contact_id' => $participant->contact_id,
      'email' => 'test-event@example.com',
    ]);
    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], ['contact_id' => $participant->contact_id]));

    $actionSchedule = $this->fixtures['sched_event_type_end_2month_repeat_twice_2_weeks'];
    $actionSchedule['entity_value'] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $participant->event_id, 'event_type_id');
    $this->callAPISuccess('action_schedule', 'create', $actionSchedule);

    $this->assertCronRuns([
      [
        // Almost 2 months.
        'time' => '2012-08-13 01:00:00',
        'recipients' => [],
      ],
      [
        // After the 2-month mark, send an email.
        'time' => '2012-08-16 01:00:00',
        'recipients' => [['test-event@example.com']],
      ],
      [
        // After 2 months and 1 week, don't repeat yet.
        'time' => '2012-08-23 02:00:00',
        'recipients' => [],
      ],
      [
        // After 2 months and 2 weeks
        'time' => '2012-08-30 02:00:00',
        'recipients' => [['test-event@example.com']],
      ],
      [
        // After 2 months and 4 week
        'time' => '2012-09-13 02:00:00',
        'recipients' => [['test-event@example.com']],
      ],
      [
        // After 2 months and 6 weeks
        'time' => '2012-09-27 01:00:00',
        'recipients' => [],
      ],
    ]);
  }

  /**
   * Run a series of cron jobs and make an assertion about email deliveries.
   *
   * @param array $cronRuns
   *   array specifying when to run cron and what messages to expect; each item is an array with keys:
   *   - time: string, e.g. '2012-06-15 21:00:01'
   *   - recipients: array(array(string)), list of email addresses which should receive messages
   *
   * @throws \CRM_Core_Exception
   * @noinspection DisconnectedForeachInstructionInspection
   */
  public function assertCronRuns(array $cronRuns): void {
    foreach ($cronRuns as $cronRun) {
      CRM_Utils_Time::setTime($cronRun['time']);
      $this->callAPISuccess('job', 'send_reminder', []);
      $this->mut->assertRecipients($cronRun['recipients']);
      if (array_key_exists('subjects', $cronRun)) {
        $this->mut->assertSubjects($cronRun['subjects']);
      }
      $this->mut->clearMessages();
    }
  }

  /**
   * @var array
   *
   * (DAO_Name => array(int)) List of items to garbage-collect during tearDown
   */
  private $_testObjects = [];

  /**
   * This is a wrapper for CRM_Core_DAO::createTestObject which tracks
   * created entities and provides for brainless cleanup.
   *
   * However, it is only really brainless when initially writing the code.
   * It 'steals and deletes entities that are part of the 'stock build'.
   *
   * In general this causes weird stuff.
   *
   * @param $daoName
   * @param array $params
   * @param int $numObjects
   * @param bool $createOnly
   *
   * @return array|NULL|object
   * @see CRM_Core_DAO::createTestObject
   */
  public function createTestObject($daoName, array $params = [], int $numObjects = 1, bool $createOnly = FALSE) {
    $objects = CRM_Core_DAO::createTestObject($daoName, $params, $numObjects, $createOnly);
    if (is_array($objects)) {
      $this->registerTestObjects($objects);
    }
    else {
      $this->registerTestObjects([$objects]);
    }
    return $objects;
  }

  /**
   * @param array $objects
   *   DAO or BAO objects.
   */
  public function registerTestObjects(array $objects): void {
    //if (is_object($objects)) {
    //  $objects = array($objects);
    //}
    foreach ($objects as $object) {
      $daoName = str_replace('_BAO_', '_DAO_', get_class($object));
      $this->_testObjects[$daoName][] = $object->id;
    }
  }

  public function deleteTestObjects(): void {
    // Note: You might argue that the FK relations between test
    // objects could make this problematic; however, it should
    // behave intuitively as long as we mentally split our
    // test-objects between the "manual/primary records"
    // and the "automatic/secondary records"
    foreach ($this->_testObjects as $daoName => $daoIds) {
      foreach ($daoIds as $daoId) {
        CRM_Core_DAO::deleteTestObjects($daoName, ['id' => $daoId]);
      }
    }
    $this->_testObjects = [];
  }

  /**
   * Test that the various repetition units work correctly.
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-17028
   * @throws \CRM_Core_Exception
   */
  public function testRepetitionFrequencyUnit(): void {
    $membershipTypeParams = [
      'duration_interval' => '1',
      'duration_unit' => 'year',
      'is_active' => 1,
      'period_type' => 'rolling',
    ];
    $membershipType = $this->createTestObject('CRM_Member_DAO_MembershipType', $membershipTypeParams);
    $interval_units = ['hour', 'day', 'week', 'month', 'year'];
    foreach ($interval_units as $interval_unit) {
      $membershipEndDate = DateTime::createFromFormat('Y-m-d H:i:s', '2013-03-15 00:00:00');
      $contactParams = [
        'contact_type' => 'Individual',
        'first_name' => 'Test',
        'last_name' => "Interval $interval_unit",
        'is_deceased' => 0,
      ];
      $contact = $this->createTestObject('CRM_Contact_DAO_Contact', $contactParams);
      $emailParams = [
        'contact_id' => $contact->id,
        'is_primary' => 1,
        'email' => "test-member-$interval_unit@example.com",
        'location_type_id' => 1,
      ];
      $this->createTestObject('CRM_Core_DAO_Email', $emailParams);
      $membershipParams = [
        'membership_type_id' => $membershipType->id,
        'contact_id' => $contact->id,
        'join_date' => '20120315',
        'start_date' => '20120315',
        'end_date' => '20130315',
        'is_override' => 0,
        'status_id' => 2,
      ];
      $membershipParams['status-id'] = 1;
      $membership = $this->createTestObject('CRM_Member_DAO_Membership', $membershipParams);
      $actionScheduleParams = $this->fixtures['sched_on_membership_end_date_repeat_interval'];
      $actionScheduleParams['entity_value'] = $membershipType->id;
      $actionScheduleParams['repetition_frequency_unit'] = $interval_unit;
      $actionScheduleParams['repetition_frequency_interval'] = 2;
      $actionSchedule = CRM_Core_BAO_ActionSchedule::add($actionScheduleParams);
      $beforeEndDate = $this->createModifiedDateTime($membershipEndDate, '-1 day');
      $beforeFirstUnit = $this->createModifiedDateTime($membershipEndDate, "+1 $interval_unit");
      $afterFirstUnit = $this->createModifiedDateTime($membershipEndDate, "+2 $interval_unit");
      $cronRuns = [
        [
          'time' => $beforeEndDate->format('Y-m-d H:i:s'),
          'recipients' => [],
        ],
        [
          'time' => $membershipEndDate->format('Y-m-d H:i:s'),
          'recipients' => [["test-member-$interval_unit@example.com"]],
        ],
        [
          'time' => $beforeFirstUnit->format('Y-m-d H:i:s'),
          'recipients' => [],
        ],
        [
          'time' => $afterFirstUnit->format('Y-m-d H:i:s'),
          'recipients' => [["test-member-$interval_unit@example.com"]],
        ],
      ];
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testInheritedMembershipPermissions(): void {
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
    $this->createTestObject('CRM_Core_DAO_Email', $emailParams);

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
    $this->fixtures['sched_membership_join_2week']['entity_value'] = $membershipType1->id;
    $this->createScheduleFromFixtures('sched_membership_join_2week');

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
    $this->fixtures['sched_membership_start_1week']['entity_value'] = $membershipType2->id;
    $this->createScheduleFromFixtures('sched_membership_start_1week');

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

  /**
   * Modify the date time by the modify rule.
   *
   * @param DateTime $origDateTime
   * @param string $modifyRule
   *
   * @return DateTime
   */
  public function createModifiedDateTime(DateTime $origDateTime, string $modifyRule): DateTime {
    $newDateTime = clone($origDateTime);
    $newDateTime->modify($modifyRule);
    return $newDateTime;
  }

  /**
   * Test absolute date handling for membership.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testMembershipScheduleWithAbsoluteDate(): void {
    $membership = $this->createMembershipFromFixture('rolling_membership', 'New', [
      'email' => 'test-member@example.com',
      'location_type_id' => 1,
    ]);

    $this->callAPISuccess('contact', 'create', array_merge($this->fixtures['contact'], ['contact_id' => $membership['contact_id']]));
    $this->fixtures['sched_membership_absolute_date']['entity_value'] = $membership['membership_type_id'];
    $this->createScheduleFromFixtures('sched_membership_absolute_date');

    $this->assertCronRuns([
      [
        // Before the 24-hour mark, no email
        'time' => '2012-06-13 04:00:00',
        'recipients' => [],
        'subjects' => [],
      ],
      [
        // On absolute date set on 2012-06-14
        'time' => '2012-06-14 00:00:00',
        'recipients' => [['test-member@example.com']],
        'subjects' => ['subject sched_membership_absolute_date'],
      ],
      [
        // Run cron 4 hours later; first message already sent
        'time' => '2012-06-14 04:00:00',
        'recipients' => [],
        'subjects' => [],
      ],
    ]);
  }

  /**
   * @param string $fixture
   *   Key from $this->fixtures
   * @param string $status
   *   Membership status
   * @param array $emailParams
   * @param array $membershipOverrides
   *
   * @return array
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function createMembershipFromFixture(string $fixture, string $status, array $emailParams = [], array $membershipOverrides = []): array {
    $membershipTypeID = $membershipOverrides['membership_type_id'] ?? $this->fixtures[$fixture]['membership_type_id'];
    if (is_array($membershipTypeID)) {
      $membershipTypeID = MembershipType::create()->setValues(array_merge([
        'member_of_contact_id' => 1,
        'financial_type_id:name' => 'Member Dues',
        'name' => 'fixture-created-type',
      ], $this->fixtures[$fixture]['membership_type_id']))->execute()->first()['id'];
    }
    $params = array_merge($this->fixtures[$fixture], [
      'sequential' => 1,
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', $status),
      'membership_type_id' => $membershipTypeID,
    ], $membershipOverrides);
    if (empty($params['contact_id'])) {
      $params['contact_id'] = $this->individualCreate(['email' => '']);
    }
    $membership = (array) $this->callAPISuccess('Membership', 'create', $params)['values'][0];
    if ($emailParams) {
      Civi\Api4\Email::create(FALSE)->setValues(array_merge([
        'contact_id' => $membership['contact_id'],
        'location_type_id' => 1,
      ], $emailParams))->execute();
    }
    return $membership;
  }

  /**
   * Create action schedule from defined fixtures.
   *
   * @param string $fixture
   * @param array $extraParams
   *
   * @throws \CRM_Core_Exception
   */
  protected function createScheduleFromFixtures(string $fixture, array $extraParams = []): void {
    $id = $this->callAPISuccess('ActionSchedule', 'create', array_merge($this->fixtures[$fixture], $extraParams))['id'];
    $this->fixtures[$fixture]['action_schedule_id'] = (int) $id;
  }

  /**
   * @param string $activityKey
   * @param string $contactKey
   *
   * @throws \CRM_Core_Exception
   */
  protected function createActivityAndContactFromFixtures(string $activityKey = 'phone_call', string $contactKey = 'contact'): void {
    $activity = $this->createTestObject('CRM_Activity_DAO_Activity', $this->fixtures[$activityKey]);
    $contact = $this->callAPISuccess('contact', 'create', $this->fixtures[$contactKey]);
    $activity->save();

    $source = [];
    $source['contact_id'] = $contact['id'];
    $source['activity_id'] = $activity->id;
    $source['record_type_id'] = 2;
    $activityContact = $this->createTestObject('CRM_Activity_DAO_ActivityContact', $source);
    $activityContact->save();
  }

}
