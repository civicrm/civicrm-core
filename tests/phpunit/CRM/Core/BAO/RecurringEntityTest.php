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
 * Class CRM_Core_BAO_RecurringEntityTest
 * @group headless
 */
class CRM_Core_BAO_RecurringEntityTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  protected function tearDown() {
  }

  /**
   * Testing Activity Generation through Entity Recursion.
   */
  public function testActivityGeneration() {
    //Activity set initial params
    $daoActivity = new CRM_Activity_DAO_Activity();
    $daoActivity->activity_type_id = 1;
    $daoActivity->subject = "Initial Activity";
    $daoActivity->activity_date_time = '20141002103000';
    $daoActivity->save();

    $recursion = new CRM_Core_BAO_RecurringEntity();
    $recursion->entity_id = $daoActivity->id;
    $recursion->entity_table = 'civicrm_activity';
    $recursion->dateColumns = ['activity_date_time'];
    $recursion->schedule = [
      'entity_value' => $daoActivity->id,
      'start_action_date' => $daoActivity->activity_date_time,
      'entity_status' => 'fourth saturday',
      'repetition_frequency_unit' => 'month',
      'repetition_frequency_interval' => 3,
      'start_action_offset' => 5,
      'used_for' => 'activity',
    ];

    $generatedEntities = $recursion->generate();
    $this->assertEquals(5, count($generatedEntities['civicrm_activity']), "Cehck if number of iterations are 5");
    $expectedDates = [
      '20141025103000',
      '20150124103000',
      '20150425103000',
      '20150725103000',
      '20151024103000',
    ];
    foreach ($generatedEntities['civicrm_activity'] as $entityID) {
      $this->assertDBNotNull('CRM_Activity_DAO_Activity', $entityID, 'id',
        'id', 'Check DB if repeating activities were created'
      );
    }

    // set mode to ALL, i.e any change to changing activity affects all related recurring activities
    $recursion->mode(3);

    // lets change subject of initial activity that we created in beginning
    $daoActivity->find(TRUE);
    $daoActivity->subject = 'Changed Activity';
    $daoActivity->save();

    // check if other activities were affected
    $actualDates = [];
    foreach ($generatedEntities['civicrm_activity'] as $entityID) {
      $this->assertDBCompareValue('CRM_Activity_DAO_Activity', $entityID, 'subject', 'id', 'Changed Activity', 'Check if subject was updated');
      $actualDates[] = date('YmdHis', strtotime(CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $entityID, 'activity_date_time', 'id')));
    }
    $resultDates = array_diff($actualDates, $expectedDates);
    $this->assertEquals(0, count($resultDates), "Check if all the value in expected array matches actual array");

  }

  /**
   * Creating action schedule
   */
  private function createActionSchedule($entity_id, $entity_table) {
    $params = [
      "used_for" => $entity_table,
      "entity_value" => $entity_id,
      "start_action_date" => date("YmdHis"),
      "repetition_frequency_unit" => "week",
      "repetition_frequency_interval" => "3",
      "start_action_condition" => "monday,tuesday,wednesday,thursday,friday,saturday",
      "start_action_offset" => "2",
    ];
    $actionScheduleObj = CRM_Core_BAO_ActionSchedule::add($params);
    return $actionScheduleObj;
  }

  /**
   * Creating recurring entities
   */
  private function createRecurringEntities($actionScheduleObj, $entity_id, $entity_table) {
    $recursion = new CRM_Core_BAO_RecurringEntity();
    $recursion->dateColumns = [
      "start_date",
    ];
    $recursion->scheduleId = $actionScheduleObj->id;
    $recursion->entity_id = $entity_id;
    $recursion->entity_table = $entity_table;
    $recursion->linkedEntities = [
      [
        "table"          => "civicrm_price_set_entity",
        "findCriteria"   => [
          "entity_id"    => $entity_id,
          "entity_table" => $entity_table,
        ],
        "linkedColumns"  => [
          "entity_id",
        ],
        "isRecurringEntityRecord" => FALSE,
      ],
    ];
    return $recursion->generate();
  }

  /**
   * Testing Event Generation through Entity Recursion.
   */
  public function testRepeatEventCreation() {
    $event = $this->eventCreate();
    $entity_table = "civicrm_event";
    $entity_id = $event["id"];
    CRM_Price_BAO_PriceSet::addTo($entity_table, $entity_id, 1);
    $actionScheduleObj = $this->createActionSchedule($entity_id, $entity_table);
    $recurringEntities = $this->createRecurringEntities($actionScheduleObj, $entity_id, $entity_table);
    $finalResult = CRM_Core_BAO_RecurringEntity::updateModeAndPriceSet($entity_id, $entity_table, CRM_Core_BAO_RecurringEntity::MODE_ALL_ENTITY_IN_SERIES, [], 2);
    $this->assertEquals(2, count($recurringEntities["civicrm_event"]), "Recurring events not created.");
    $this->assertEquals(2, count($recurringEntities["civicrm_price_set_entity"]), "Recurring price sets not created.");
    $priceSetOne = CRM_Price_BAO_PriceSet::getFor($entity_table, $recurringEntities["civicrm_price_set_entity"][0]);
    $priceSetTwo = CRM_Price_BAO_PriceSet::getFor($entity_table, $recurringEntities["civicrm_price_set_entity"][1]);
    $this->assertEquals(2, $priceSetOne, "Price set id of the recurring event is not updated.");
    $this->assertEquals(2, $priceSetTwo, "Price set id of the recurring event is not updated.");
  }

  /**
   * Testing Event Generation through Entity Recursion.
   */
  public function testEventGeneration() {
    //Event set initial params
    $daoEvent = new CRM_Event_DAO_Event();
    $daoEvent->title = 'Test event for Recurring Entity';
    $daoEvent->event_type_id = 3;
    $daoEvent->is_public = 1;
    $daoEvent->start_date = date('YmdHis', strtotime('2014-10-26 10:30:00'));
    $daoEvent->end_date = date('YmdHis', strtotime('2014-10-28 10:30:00'));
    $daoEvent->created_date = date('YmdHis');
    $daoEvent->is_active = 1;
    $daoEvent->save();
    $this->assertDBNotNull('CRM_Event_DAO_Event', $daoEvent->id, 'id', 'id', 'Check DB if event was created');

    //Create tell a friend for event
    $daoTellAFriend = new CRM_Friend_DAO_Friend();
    $daoTellAFriend->entity_table = 'civicrm_event';
    // join with event
    $daoTellAFriend->entity_id = $daoEvent->id;
    $daoTellAFriend->title = 'Testing tell a friend';
    $daoTellAFriend->is_active = 1;
    $daoTellAFriend->save();
    $this->assertDBNotNull('CRM_Friend_DAO_Friend', $daoTellAFriend->id, 'id', 'id', 'Check DB if tell a friend was created');

    // time to use recursion
    $recursion = new CRM_Core_BAO_RecurringEntity();
    $recursion->entity_id = $daoEvent->id;
    $recursion->entity_table = 'civicrm_event';
    $recursion->dateColumns = ['start_date'];
    $recursion->schedule = [
      'entity_value' => $daoEvent->id,
      'start_action_date' => $daoEvent->start_date,
      'start_action_condition' => 'monday',
      'repetition_frequency_unit' => 'week',
      'repetition_frequency_interval' => 1,
      'start_action_offset' => 4,
      'used_for' => 'event',
    ];

    $recursion->linkedEntities = [
      [
        'table' => 'civicrm_tell_friend',
        'findCriteria' => [
          'entity_id' => $recursion->entity_id,
          'entity_table' => 'civicrm_event',
        ],
        'linkedColumns' => ['entity_id'],
        'isRecurringEntityRecord' => TRUE,
      ],
    ];

    $interval = $recursion->getInterval($daoEvent->start_date, $daoEvent->end_date);
    $recursion->intervalDateColumns = ['end_date' => $interval];
    $generatedEntities = $recursion->generate();
    $this->assertArrayHasKey('civicrm_event', $generatedEntities, 'Check if generatedEntities has civicrm_event as required key');
    $expectedDates = [
      '20141027103000' => '20141029103000',
      '20141103103000' => '20141105103000',
      '20141110103000' => '20141112103000',
      '20141117103000' => '20141119103000',
    ];

    $this->assertCount($recursion->schedule['start_action_offset'], $generatedEntities['civicrm_event'], 'Check if the number of events created are right');
    $actualDates = [];
    foreach ($generatedEntities['civicrm_event'] as $key => $val) {
      $this->assertDBNotNull('CRM_Event_DAO_Event', $val, 'id', 'id', 'Check if repeating events were created.');
      $startDate = date('YmdHis', strtotime(CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $val, 'start_date', 'id')));
      $endDate = date('YmdHis', strtotime(CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $val, 'end_date', 'id')));
      $actualDates[$startDate] = $endDate;
    }

    $resultDates = array_diff($actualDates, $expectedDates);
    $this->assertEquals(0, count($resultDates), "Check if all the value in expected array matches actual array");

    foreach ($generatedEntities['civicrm_tell_friend'] as $key => $val) {
      $this->assertDBNotNull('CRM_Friend_DAO_Friend', $val, 'id', 'id', 'Check if friends were created in loop');
      $this->assertDBCompareValue('CRM_Friend_DAO_Friend', $val, 'entity_id', 'id', $generatedEntities['civicrm_event'][$key], 'Check DB if correct FK was maintained with event for Friend');
    }
    $this->assertCount($recursion->schedule['start_action_offset'], $generatedEntities['civicrm_tell_friend'], 'Check if the number of tell a friend records are right');

    // set mode to ALL, i.e any change to changing event affects all related recurring activities
    $recursion->mode(3);

    $daoEvent->find(TRUE);
    $daoEvent->title = 'Event Changed';
    $daoEvent->save();

    // check if other events were affected
    foreach ($generatedEntities['civicrm_event'] as $entityID) {
      $this->assertDBCompareValue('CRM_Event_DAO_Event', $entityID, 'title', 'id', 'Event Changed', 'Check if title was updated');
    }

    end($generatedEntities['civicrm_event']);
    $key = key($generatedEntities['civicrm_event']);

    end($generatedEntities['civicrm_tell_friend']);
    $actKey = key($generatedEntities['civicrm_tell_friend']);

    //Check if both(event/tell a friend) keys are same
    $this->assertEquals($key, $actKey, "Check if both the keys are same");

    //Cross check event exists before we test deletion
    $searchParamsEventBeforeDelete = [
      'entity_id' => $generatedEntities['civicrm_event'][$key],
      'entity_table' => 'civicrm_event',
    ];
    $expectedValuesEventBeforeDelete = [
      'entity_id' => $generatedEntities['civicrm_event'][$key],
      'entity_table' => 'civicrm_event',
    ];
    $this->assertDBCompareValues('CRM_Core_DAO_RecurringEntity', $searchParamsEventBeforeDelete, $expectedValuesEventBeforeDelete);

    //Cross check event exists before we test deletion
    $searchParamsTellAFriendBeforeDelete = [
      'entity_id' => $generatedEntities['civicrm_tell_friend'][$actKey],
      'entity_table' => 'civicrm_tell_friend',
    ];
    $expectedValuesTellAFriendBeforeDelete = [
      'entity_id' => $generatedEntities['civicrm_tell_friend'][$actKey],
      'entity_table' => 'civicrm_tell_friend',
    ];
    $this->assertDBCompareValues('CRM_Core_DAO_RecurringEntity', $searchParamsTellAFriendBeforeDelete, $expectedValuesTellAFriendBeforeDelete);

    //Delete an event from recurring set and respective linked entity should be deleted from civicrm_recurring_entity_table
    $daoRecurEvent = new CRM_Event_DAO_Event();
    $daoRecurEvent->id = $generatedEntities['civicrm_event'][$key];
    if ($daoRecurEvent->find(TRUE)) {
      $daoRecurEvent->delete();
    }

    //Check if this event_id was deleted
    $this->assertDBNull('CRM_Event_DAO_Event', $generatedEntities['civicrm_event'][$key], 'id', 'id', 'Check if event was deleted');
    $searchParams = [
      'entity_id' => $generatedEntities['civicrm_event'][$key],
      'entity_table' => 'civicrm_event',
    ];
    $compareParams = [];
    $this->assertDBCompareValues('CRM_Core_DAO_RecurringEntity', $searchParams, $compareParams);

    //Find tell_a_friend id if that was deleted from civicrm
    $searchActParams = [
      'entity_id' => $generatedEntities['civicrm_tell_friend'][$actKey],
      'entity_table' => 'civicrm_tell_friend',
    ];
    $compareActParams = [];
    $this->assertDBCompareValues('CRM_Friend_DAO_Friend', $searchActParams, $compareActParams);
  }

  /**
   * Testing Activity Generation through Entity Recursion with Custom Data and Tags.
   */
  public function testRecurringEntityGenerationWithCustomDataAndTags() {

    // Create custom group and field
    $customGroup = $this->customGroupCreate([
      'extends' => 'Activity',
    ]);
    $customField = $this->customFieldCreate([
      'custom_group_id' => $customGroup['id'],
      'default_value' => '',
    ]);

    // Create activity Tag
    $tag = $this->tagCreate([
      'used_for' => 'Activities',
    ]);

    // Create original activity
    $customFieldValue = 'Custom Value';
    $activityDateTime = date('YmdHis');
    $activityId = $this->activityCreate([
      'activity_date_time' => $activityDateTime,
      'custom_' . $customField['id'] => $customFieldValue,
    ]);

    $activityId = $activityId['id'];

    // Assign tag to a activity.
    $this->callAPISuccess('EntityTag', 'create', [
      'entity_table' => 'civicrm_activity',
      'entity_id' => $activityId,
      'tag_id' => $tag['id'],
    ]);

    // Create recurring activities.
    $recursion = new CRM_Core_BAO_RecurringEntity();
    $recursion->entity_id = $activityId;
    $recursion->entity_table = 'civicrm_activity';
    $recursion->dateColumns = ['activity_date_time'];
    $recursion->schedule = [
      'entity_value' => $activityId,
      'start_action_date' => $activityDateTime,
      'entity_status' => 'fourth saturday',
      'repetition_frequency_unit' => 'month',
      'repetition_frequency_interval' => 3,
      'start_action_offset' => 3,
      'used_for' => 'activity',
    ];

    $generatedEntities = $recursion->generate();
    $generatedActivities = $generatedEntities['civicrm_activity'];

    $this->assertEquals(3, count($generatedActivities), "Check if number of iterations are 3");

    foreach ($generatedActivities as $generatedActivityId) {

      /* Validate tag in recurring activity
      // @todo - refer https://github.com/civicrm/civicrm-core/pull/13470
      $this->callAPISuccess('EntityTag', 'getsingle', [
      'entity_table' => 'civicrm_activity',
      'entity_id' => $generatedActivityId,
      ]);
       */

      // Validate custom data in recurring activity
      $activity = $this->callAPISuccess('activity', 'getsingle', [
        'return' => [
          'custom_' . $customField['id'],
        ],
        'id' => $generatedActivityId,
      ]);

      $this->assertEquals($customFieldValue, $activity['custom_' . $customField['id']], 'Custom field value should be ' . $customFieldValue);

    }
  }

}
