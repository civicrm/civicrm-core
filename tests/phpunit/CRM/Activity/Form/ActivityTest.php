<?php

/**
 *  Include dataProvider for tests
 * @group headless
 */
class CRM_Activity_Form_ActivityTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->assignee1 = $this->individualCreate([
      'first_name' => 'testassignee1',
      'last_name' => 'testassignee1',
      'email' => 'testassignee1@gmail.com',
    ]);
    $this->assignee2 = $this->individualCreate([
      'first_name' => 'testassignee2',
      'last_name' => 'testassignee2',
      'email' => 'testassignee2@gmail.com',
    ]);
    $this->target = $this->individualCreate();
    $this->source = $this->individualCreate();
  }

  public function testActivityCreate() {
    Civi::settings()->set('activity_assignee_notification', TRUE);
    //Reset filter to none.
    Civi::settings()->set('do_not_notify_assignees_for', []);
    $mut = new CiviMailUtils($this, TRUE);
    $mut->clearMessages();

    $form = new CRM_Activity_Form_Activity();
    $activityTypeId = CRM_Core_PseudoConstant::getKey('CRM_Activity_DAO_Activity', 'activity_type_id', 'Meeting');
    $params = [
      'source_contact_id' => $this->source,
      'assignee_contact_id' => [$this->assignee1],
      'target_contact_id' => [$this->target],
      'followup_assignee_contact_id' => [],
      'activity_type_id' => $activityTypeId,
    ];

    $activityRef = new ReflectionClass('CRM_Activity_Form_Activity');
    $method = $activityRef->getMethod('processActivity');
    $method->setAccessible(TRUE);
    $method->invokeArgs($form, [&$params]);

    $msg = $mut->getMostRecentEmail();
    $this->assertNotEmpty($msg);
    $mut->clearMessages();

    //Block Meeting notification.
    Civi::settings()->set('do_not_notify_assignees_for', [$activityTypeId]);
    $params['assignee_contact_id'] = [$this->assignee2];
    $method->invokeArgs($form, [&$params]);
    $msg = $mut->getMostRecentEmail();
    $this->assertEmpty($msg);
  }

  public function testActivityDelete() {
    // Set the parameters of the test.
    $numberOfSingleActivitiesToCreate = 3;
    $numberOfRepeatingActivitiesToCreate = 6;
    $singleActivityToDeleteOffset = 1;
    $mode1ActivityToDeleteOffset = 1;
    $mode2ActivityToDeleteOffset = 3;
    $mode3ActivityToDeleteOffset = 2;

    // Track the target contact's activities.
    $expectedActivityIds = array_keys(CRM_Activity_BAO_Activity::getActivities(['contact_id' => $this->target]));

    // Create non-repeating activities.
    $meetingActivityTypeId = CRM_Core_PseudoConstant::getKey('CRM_Activity_DAO_Activity', 'activity_type_id', 'Meeting');
    $singleActivityIds = [];
    for ($activityCount = 0; $activityCount < $numberOfSingleActivitiesToCreate; $activityCount++) {
      $activityParams = [
        'source_contact_id' => $this->source,
        'target_contact_id' => $this->target,
        'activity_type_id' => $meetingActivityTypeId,
        'activity_date_time' => date_create('+' . $activityCount . ' weeks')->format('YmdHis'),
      ];
      $singleActivityBao = CRM_Activity_BAO_Activity::create($activityParams);
      $singleActivityIds[] = $singleActivityBao->id;
    }
    $expectedActivityIds = array_merge($expectedActivityIds, $singleActivityIds);

    // Create an activity to be repeated.
    $activityParams = [
      'source_contact_id' => $this->source,
      'target_contact_id' => $this->target,
      'activity_type_id' => $meetingActivityTypeId,
      'activity_date_time' => date('YmdHis'),
    ];
    $repeatingActivityBao = CRM_Activity_BAO_Activity::create($activityParams);

    // Create the repeating activity's schedule.
    $actionScheduleParams = [
      'used_for' => 'civicrm_activity',
      'entity_value' => $repeatingActivityBao->id,
      'start_action_date' => $repeatingActivityBao->activity_date_time,
      'repetition_frequency_unit' => 'week',
      'repetition_frequency_interval' => 1,
      'start_action_offset' => $numberOfRepeatingActivitiesToCreate - 1,
    ];
    $actionScheduleBao = CRM_Core_BAO_ActionSchedule::add($actionScheduleParams);

    // Create the activity's repeats.
    $recurringEntityBao = new CRM_Core_BAO_RecurringEntity();
    $recurringEntityBao->entity_table = 'civicrm_activity';
    $recurringEntityBao->entity_id = $repeatingActivityBao->id;
    $recurringEntityBao->dateColumns = ['activity_date_time'];
    $recurringEntityBao->linkedEntities = [
      [
        'table' => 'civicrm_activity_contact',
        'findCriteria' => ['activity_id' => $repeatingActivityBao->id],
        'linkedColumns' => ['activity_id'],
        'isRecurringEntityRecord' => FALSE,
      ],
    ];
    $recurringEntityBao->scheduleId = $actionScheduleBao->id;
    $newEntities = $recurringEntityBao->generate();
    $repeatingActivityIds = array_merge([$repeatingActivityBao->id], $newEntities['civicrm_activity']);
    $expectedActivityIds = array_merge($expectedActivityIds, $repeatingActivityIds);

    // Assert that the expected activities exist.
    $this->assertTargetActivityIds($expectedActivityIds);

    // Delete a non-repeating activity.
    $activityId = $singleActivityIds[$singleActivityToDeleteOffset];
    $this->deleteActivity($activityId);
    $expectedActivityIds = array_diff($expectedActivityIds, [$activityId]);
    $this->assertTargetActivityIds($expectedActivityIds);

    // Delete one activity from series (mode 1).
    $activityId = $repeatingActivityIds[$mode1ActivityToDeleteOffset];
    $this->deleteActivity($activityId, 1);
    $expectedActivityIds = array_diff($expectedActivityIds, [$activityId]);
    $this->assertTargetActivityIds($expectedActivityIds);

    // Delete from one activity until end of series (mode 2).
    $activityId = $repeatingActivityIds[$mode2ActivityToDeleteOffset];
    $this->deleteActivity($activityId, 2);
    $expectedActivityIds = array_diff($expectedActivityIds, array_slice($repeatingActivityIds, $mode2ActivityToDeleteOffset));
    $this->assertTargetActivityIds($expectedActivityIds);

    // Delete all activities in series (mode 3).
    $activityId = $repeatingActivityIds[$mode3ActivityToDeleteOffset];
    $this->deleteActivity($activityId, 3);
    $expectedActivityIds = array_diff($expectedActivityIds, $repeatingActivityIds);
    $this->assertTargetActivityIds($expectedActivityIds);
  }

  /**
   * Asserts that the target contact has the expected activity IDs
   *
   * @param array $expectedActivityIds
   *   An array of the activity IDs that are expected to exist for the target contact
   */
  private function assertTargetActivityIds($expectedActivityIds) {
    $actualActivityIds = array_keys(CRM_Activity_BAO_Activity::getActivities(['contact_id' => $this->target]));
    $this->assertEquals(array_fill_keys($expectedActivityIds, NULL), array_fill_keys($actualActivityIds, NULL));
  }

  /**
   * Tests the form's deletion of activities, with optional mode for repeating activities
   *
   * @param int $activityId
   *   The ID of the activity to delete
   * @param int $mode
   *   1 - delete the specified activity
   *   2 - delete the specified activity and all following activities in the series
   *   3 - delete all activities in the series
   */
  private function deleteActivity($activityId, $mode = NULL) {
    // For repeating activities, set the recurring entity mode.
    if (!is_null($mode)) {
      $recurringEntityBao = new CRM_Core_BAO_RecurringEntity();
      $recurringEntityBao->entity_table = 'civicrm_activity';
      $recurringEntityBao->entity_id = $activityId;
      $recurringEntityBao->mode($mode);
    }

    // Use a form to delete the activity.
    $form = new CRM_Activity_Form_Activity();
    $form->_action = CRM_Core_Action::DELETE;
    $form->_activityId = $activityId;
    $form->postProcess();
  }

  /**
   * This is a bit messed up having a variable called name that means label but we don't want to fix it because it's a form member variable _activityTypeName that might be used in form hooks, so just make sure it doesn't flip between name and label. dev/core#1116
   */
  public function testActivityTypeNameIsReallyLabel() {
    $form = new CRM_Activity_Form_Activity();

    // the actual value is irrelevant we just need something for the tested function to act on
    $form->_currentlyViewedContactId = $this->source;

    // Let's make a new activity type that has a different name from its label just to be sure.
    $actParams = [
      'option_group_id' => 'activity_type',
      'name' => 'wp1234',
      'label' => 'Water Plants',
      'is_active' => 1,
      'is_default' => 0,
    ];
    $result = $this->callAPISuccess('option_value', 'create', $actParams);

    $form->_activityTypeId = $result['values'][$result['id']]['value'];
    $this->assertNotEmpty($form->_activityTypeId);

    // Do the thing we want to test
    $form->assignActivityType();

    $this->assertEquals('Water Plants', $form->_activityTypeName);

    // cleanup
    $this->callAPISuccess('option_value', 'delete', ['id' => $result['id']]);
  }

  /**
   * Test that the machineName and displayLabel are assigned correctly to the
   * smarty template.
   *
   * See also testActivityTypeNameIsReallyLabel()
   */
  public function testActivityTypeAssignment() {
    $form = new CRM_Activity_Form_Activity();

    $form->_currentlyViewedContactId = $this->source;

    // Let's make a new activity type that has a different name from its label just to be sure.
    $actParams = [
      'option_group_id' => 'activity_type',
      'name' => '47395hc',
      'label' => 'Hide Cookies',
      'is_active' => 1,
      'is_default' => 0,
    ];
    $result = $this->callAPISuccess('option_value', 'create', $actParams);

    $form->_activityTypeId = $result['values'][$result['id']]['value'];

    // Do the thing we want to test
    $form->assignActivityType();

    // Check the smarty template has the correct values assigned.
    $keyValuePair = $form->getTemplate()->get_template_vars('activityType');
    $this->assertEquals('47395hc', $keyValuePair['machineName']);
    $this->assertEquals('Hide Cookies', $keyValuePair['displayLabel']);

    // cleanup
    $this->callAPISuccess('option_value', 'delete', ['id' => $result['id']]);
  }

}
