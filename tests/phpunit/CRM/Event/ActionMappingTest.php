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

/**
 * Class CRM_Event_ActionMappingTest
 * @group ActionSchedule
 *
 * This class tests various configurations of event scheduled-reminders. It follows a design/pattern described in
 * AbstractMappingTest.
 *
 * @see \Civi\ActionSchedule\AbstractMappingTest
 * @group headless
 */
class CRM_Event_ActionMappingTest extends \Civi\ActionSchedule\AbstractMappingTest {

  public function createTestCases() {
  }

  public function testLimitByRoleId() {
    $participantId = $this->participantCreate(['role_id' => [1, 2]]);
    $participant = $this->callAPISuccess('participant', 'getsingle', ['id' => $participantId]);
    $eventId = $participant['event_id'];
    $this->schedule->mapping_id = CRM_Event_ActionMapping::EVENT_NAME_MAPPING_ID;
    $this->schedule->start_action_date = 'start_date';
    $this->schedule->entity_value = $eventId;
    $this->schedule->limit_to = 1;
    $this->schedule->recipient_listing = 1;
    $this->startWeekBefore();
    $this->useHelloFirstName();
    $this->schedule->save();
    $this->callAPISuccess('job', 'send_reminder', []);
  }

}
