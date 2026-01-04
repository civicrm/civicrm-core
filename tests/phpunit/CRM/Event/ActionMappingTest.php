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

use Civi\ActionSchedule\AbstractMappingTestCase;

/**
 * Class CRM_Event_ActionMappingTest
 * @group ActionSchedule
 *
 * This class tests various configurations of event scheduled-reminders. It follows a design/pattern described in
 * AbstractMappingTest.
 *
 * @see \Civi\ActionSchedule\AbstractMappingTestCase
 * @group headless
 */
class CRM_Event_ActionMappingTest extends AbstractMappingTestCase {

  public static function createTestCases(): array {
    $cs = [];

    $cs[] = [
      '2015-02-01 00:00:00',
      'createReunion registerAliceDualRole scheduleForEventStart startWeekBefore targetByRole useHelloFirstName',
      [
        [
          'time' => '2015-01-25 00:00:00',
          'to' => ['alice@example.org'],
          'subject' => '/Hello, Alice/',
        ],
      ],
    ];

    return $cs;
  }

  public function createReunion(array $params = [], string $identifier = 'event') {
    $this->eventCreateUnpaid([
      'title' => 'Roadie Reunion',
      'start_date' => '2015-02-01 00:00:00',
      'end_date' => '2015-02-03 05:00:00',
      'registration_start_date' => '2014-08-01 09:00:00',
      'registration_end_date' => '2015-01-15 23:59:59',
    ]);
  }

  public function registerAliceDualRole() {
    $this->participantCreate([
      'role_id' => [1, 2],
      'contact_id' => $this->contacts['alice']['id'],
      'event_id' => $this->getEventID(),
    ]);
  }

  public function scheduleForEventStart(): void {
    $this->schedule->mapping_id = CRM_Event_ActionMapping::EVENT_NAME_MAPPING_ID;
    $this->schedule->start_action_date = 'start_date';
    $this->schedule->entity_value = $this->getEventID();
  }

  public function targetByRole(): void {
    $this->schedule->limit_to = 1;
    $this->schedule->recipient_listing = 1;
  }

}
