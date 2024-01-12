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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4\Entity;

use Civi\Api4\Event;
use Civi\Api4\Participant;
use api\v4\Api4TestBase;

/**
 * @group headless
 */
class ParticipantTest extends Api4TestBase {

  /**
   * @throws \CRM_Core_Exception
   */
  public function testGetActions(): void {
    $result = Participant::getActions(FALSE)
      ->execute()
      ->indexBy('name');

    $getParams = $result['get']['params'];
    $whereDescription = 'Criteria for selecting Participants';

    $this->assertEquals(TRUE, $getParams['checkPermissions']['default']);
    $this->assertStringContainsString($whereDescription, $getParams['where']['description']);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testGet(): void {
    $rows = $this->getRowCount('civicrm_participant');
    if ($rows > 0) {
      $this->fail('Participant table must be empty');
    }

    // With no records:
    $result = Participant::get(FALSE)->execute();
    $this->assertEquals(0, $result->count(), "count of empty get is not 0");

    // Check that the $result knows what the inputs were
    $this->assertEquals('Participant', $result->entity);
    $this->assertEquals('get', $result->action);
    $this->assertEquals(4, $result->version);

    // Create some test related records before proceeding
    $participantCount = 20;
    $contactCount = 7;
    $eventCount = 5;

    // All events will either have this number or one less because of the
    // rotating participation creation method.
    $expectedFirstEventCount = ceil($participantCount / $eventCount);

    $dummy = [
      'contacts' => $this->saveTestRecords('Contact', [
        'records' => array_fill(0, $contactCount, []),
      ]),
      'events' => $this->saveTestRecords('Event', [
        'records' => array_fill(0, $eventCount, []),
      ]),
      'sources' => ['Paddington', 'Springfield', 'Central'],
    ];

    // - create dummy participants record
    $records = [];
    for ($i = 0; $i < $participantCount; $i++) {
      $records[] = [
        'event_id' => $dummy['events'][$i % $eventCount]['id'],
        'contact_id' => $dummy['contacts'][$i % $contactCount]['id'],
        // 3 = number of sources
        'source' => $dummy['sources'][$i % 3],
      ];
    }
    $this->saveTestRecords('Participant', [
      'records' => $records,
      'defaults' => [
        'status_id' => 2,
        'role_id' => 1,
        'register_date' => 20070219,
        'event_level' => 'Payment',
      ],
    ]);
    $sqlCount = $this->getRowCount('civicrm_participant');
    $this->assertEquals($participantCount, $sqlCount, "Unexpected count");

    $firstEventId = $dummy['events'][0]['id'];
    $secondEventId = $dummy['events'][1]['id'];
    $firstContactId = $dummy['contacts'][0]['id'];

    $firstOnlyResult = Participant::get(FALSE)
      ->addClause('AND', ['event_id', '=', $firstEventId])
      ->execute();

    $this->assertCount($expectedFirstEventCount, $firstOnlyResult, "count of first event is not $expectedFirstEventCount");

    // get first two events using different methods
    $firstTwo = Participant::get(FALSE)
      ->addWhere('event_id', 'IN', [$firstEventId, $secondEventId])
      ->execute();

    $firstResult = $firstTwo->first();

    // verify counts
    // count should either twice the first event count or one less
    $this->assertLessThanOrEqual(
      $expectedFirstEventCount * 2,
      count($firstTwo),
      "count is too high"
    );

    $this->assertGreaterThanOrEqual(
      $expectedFirstEventCount * 2 - 1,
      count($firstTwo),
      "count is too low"
    );

    $firstParticipantResult = Participant::get(FALSE)
      ->addWhere('event_id', '=', $firstEventId)
      ->addWhere('contact_id', '=', $firstContactId)
      ->execute();

    $this->assertCount(1, $firstParticipantResult, 'more than one registration');

    $firstParticipantId = $firstParticipantResult->first()['id'];

    // get a result which excludes $first_participant
    $otherParticipantResult = Participant::get(FALSE)
      ->setSelect(['id'])
      ->addClause('NOT', [
        ['event_id', '=', $firstEventId],
        ['contact_id', '=', $firstContactId],
      ])
      ->execute()
      ->indexBy('id');

    // check alternate syntax for NOT
    $otherParticipantResult2 = Participant::get(FALSE)
      ->setSelect(['id'])
      ->addClause('NOT', 'AND', [
        ['event_id', '=', $firstEventId],
        ['contact_id', '=', $firstContactId],
      ])
      ->execute()
      ->indexBy('id');

    $this->assertEquals($otherParticipantResult, $otherParticipantResult2);

    $this->assertCount($participantCount - 1,
      $otherParticipantResult,
      'failed to exclude a single record on complex criteria');
    // check the record we have excluded is the right one:

    $this->assertFalse(
      $otherParticipantResult->offsetExists($firstParticipantId),
      'excluded wrong record');

    // check syntax for date-range

    $getParticipantsById = function($wheres = []) {
      return Participant::get(FALSE)
        ->setWhere($wheres)
        ->execute()
        ->indexBy('id');
    };

    $thisYearParticipants = $getParticipantsById([['register_date', '=', 'this.year']]);
    $this->assertFalse(isset($thisYearParticipants[$firstParticipantId]));

    $otherYearParticipants = $getParticipantsById([['register_date', '!=', 'this.year']]);
    $this->assertTrue(isset($otherYearParticipants[$firstParticipantId]));

    Participant::update()->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $firstParticipantId)
      ->addValue('register_date', 'now')
      ->execute();

    $thisYearParticipants = $getParticipantsById([['register_date', '=', 'this.year']]);
    $this->assertTrue(isset($thisYearParticipants[$firstParticipantId]));

    // retrieve a participant record and update some records
    $patchRecord = [
      'source' => "not " . $firstResult['source'],
    ];

    Participant::update()
      ->addWhere('event_id', '=', $firstEventId)
      ->setCheckPermissions(FALSE)
      ->setLimit(20)
      ->setValues($patchRecord)
      ->setCheckPermissions(FALSE)
      ->execute();

    // - delete some records
    $secondEventId = $dummy['events'][1]['id'];
    $deleteResult = Participant::delete()
      ->addWhere('event_id', '=', $secondEventId)
      ->setCheckPermissions(FALSE)
      ->execute();
    $expectedDeletes = [2, 7, 12, 17];
    $this->assertEquals($expectedDeletes, array_column((array) $deleteResult, 'id'),
      "didn't delete every second record as expected");

    $sqlCount = $this->getRowCount('civicrm_participant');
    $this->assertEquals(
      $participantCount - count($expectedDeletes),
      $sqlCount,
      "records not gone from database after delete");

    // Try creating is_test participants
    foreach ($dummy['contacts'] as $contact) {
      Participant::create()
        ->addValue('is_test', 1)
        ->addValue('contact_id', $contact['id'])
        ->addValue('event_id', $secondEventId)
        ->execute();
    }

    // By default is_test participants are hidden
    $this->assertCount(0, Participant::get()->selectRowCount()->addWhere('event_id', '=', $secondEventId)->execute());

    // Test records show up if you add is_test to the query
    $testParticipants = Participant::get()->addWhere('event_id', '=', $secondEventId)->addWhere('is_test', '=', 1)->addSelect('id')->execute();
    $this->assertCount($contactCount, $testParticipants);

    // Or if you search by id
    $this->assertCount(1, Participant::get()->selectRowCount()->addWhere('id', '=', $testParticipants->first()['id'])->execute());
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testGetRemainingParticipants(): void {
    $eventWithMax = $this->createTestRecord('Event', ['max_participants' => 3])['id'];
    $eventUnlimited = $this->createTestRecord('Event', ['max_participants' => 0])['id'];

    $events = Event::get(FALSE)
      ->addSelect('remaining_participants')
      ->addWhere('id', 'IN', [$eventWithMax, $eventUnlimited])
      ->addOrderBy('id')
      ->execute();
    $this->assertEquals(3, $events[0]['remaining_participants']);
    // `remaining_participants` is always NULL for unlimited events
    $this->assertNull($events[1]['remaining_participants']);

    $deleted = $this->createTestRecord('Contact', ['is_deleted' => TRUE])['id'];
    $this->createTestRecord('OptionValue', [
      'option_group_id:name' => 'participant_role',
      'filter' => 0,
      'label' => 'invisible man',
      'name' => 'spy',
    ]);

    $this->saveTestRecords('Participant', [
      'records' => [
        // 2 legit registrations for $eventWithMax
        ['event_id' => $eventWithMax, 'status_id:name' => 'Registered'],
        ['event_id' => $eventWithMax, 'status_id:name' => 'Attended'],
        // None of these should count toward $eventWithMax participant limit
        ['event_id' => $eventWithMax, 'status_id:name' => 'Registered', 'contact_id' => $deleted],
        ['event_id' => $eventWithMax, 'status_id:name' => 'Registered', 'role_id:name' => 'spy'],
        ['event_id' => $eventWithMax, 'status_id:name' => 'Cancelled'],
        ['event_id' => $eventUnlimited, 'status_id:name' => 'Registered'],
      ],
    ]);

    $events = Event::get(FALSE)
      ->addSelect('remaining_participants')
      ->addWhere('id', 'IN', [$eventWithMax, $eventUnlimited])
      ->addOrderBy('id')
      ->execute();
    // 1 Spot remaining
    $this->assertEquals(1, $events[0]['remaining_participants']);
    // `remaining_participants` is always NULL for unlimited events
    $this->assertNull($events[1]['remaining_participants']);

    $this->saveTestRecords('Participant', [
      'records' => [
        // 2 legit registrations for $eventWithMax
        ['event_id' => $eventWithMax, 'status_id:name' => 'Registered'],
        ['event_id' => $eventWithMax, 'status_id:name' => 'Attended'],
        // None of these should count toward $eventWithMax participant limit
        ['event_id' => $eventWithMax, 'status_id:name' => 'Registered', 'contact_id' => $deleted],
        ['event_id' => $eventUnlimited, 'status_id:name' => 'Registered'],
      ],
    ]);

    $events = Event::get(FALSE)
      ->addSelect('remaining_participants')
      ->addWhere('id', 'IN', [$eventWithMax, $eventUnlimited])
      ->addOrderBy('id')
      ->execute();
    // -1 spot remaining
    $this->assertEquals(-1, $events[0]['remaining_participants']);
    // `remaining_participants` is always NULL for unlimited events
    $this->assertNull($events[1]['remaining_participants']);
  }

  /**
   * Quick record counter
   *
   * @param string $table_name
   * @returns int record count
   */
  private function getRowCount($table_name) {
    $sql = "SELECT count(id) FROM $table_name";
    return (int) \CRM_Core_DAO::singleValueQuery($sql);
  }

}
