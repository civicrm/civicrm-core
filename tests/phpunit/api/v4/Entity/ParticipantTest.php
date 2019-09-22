<?php

namespace api\v4\Entity;

use Civi\Api4\Participant;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class ParticipantTest extends UnitTestCase {

  public function setUp() {
    parent::setUp();
    $cleanup_params = [
      'tablesToTruncate' => [
        'civicrm_event',
        'civicrm_participant',
      ],
    ];
    $this->cleanup($cleanup_params);
  }

  public function testGetActions() {
    $result = Participant::getActions()
      ->setCheckPermissions(FALSE)
      ->execute()
      ->indexBy('name');

    $getParams = $result['get']['params'];
    $whereDescription = 'Criteria for selecting items.';

    $this->assertEquals(TRUE, $getParams['checkPermissions']['default']);
    $this->assertEquals($whereDescription, $getParams['where']['description']);
  }

  public function testGet() {
    $rows = $this->getRowCount('civicrm_participant');
    if ($rows > 0) {
      $this->markTestSkipped('Participant table must be empty');
    }

    // With no records:
    $result = Participant::get()->setCheckPermissions(FALSE)->execute();
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
      'contacts' => $this->createEntity([
        'type' => 'Individual',
        'count' => $contactCount,
        'seq' => 1,
      ]),
      'events' => $this->createEntity([
        'type' => 'Event',
        'count' => $eventCount,
        'seq' => 1,
      ]),
      'sources' => ['Paddington', 'Springfield', 'Central'],
    ];

    // - create dummy participants record
    for ($i = 0; $i < $participantCount; $i++) {
      $dummy['participants'][$i] = $this->sample([
        'type' => 'Participant',
        'overrides' => [
          'event_id' => $dummy['events'][$i % $eventCount]['id'],
          'contact_id' => $dummy['contacts'][$i % $contactCount]['id'],
          // 3 = number of sources
          'source' => $dummy['sources'][$i % 3],
        ],
      ])['sample_params'];

      Participant::create()
        ->setValues($dummy['participants'][$i])
        ->setCheckPermissions(FALSE)
        ->execute();
    }
    $sqlCount = $this->getRowCount('civicrm_participant');
    $this->assertEquals($participantCount, $sqlCount, "Unexpected count");

    $firstEventId = $dummy['events'][0]['id'];
    $secondEventId = $dummy['events'][1]['id'];
    $firstContactId = $dummy['contacts'][0]['id'];

    $firstOnlyResult = Participant::get()
      ->setCheckPermissions(FALSE)
      ->addClause('AND', ['event_id', '=', $firstEventId])
      ->execute();

    $this->assertEquals($expectedFirstEventCount, count($firstOnlyResult),
      "count of first event is not $expectedFirstEventCount");

    // get first two events using different methods
    $firstTwo = Participant::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('event_id', 'IN', [$firstEventId, $secondEventId])
      ->execute();

    $firstResult = $result->first();

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

    $firstParticipantResult = Participant::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('event_id', '=', $firstEventId)
      ->addWhere('contact_id', '=', $firstContactId)
      ->execute();

    $this->assertEquals(1, count($firstParticipantResult), "more than one registration");

    $firstParticipantId = $firstParticipantResult->first()['id'];

    // get a result which excludes $first_participant
    $otherParticipantResult = Participant::get()
      ->setCheckPermissions(FALSE)
      ->setSelect(['id'])
      ->addClause('NOT', [
        ['event_id', '=', $firstEventId],
        ['contact_id', '=', $firstContactId],
      ])
      ->execute()
      ->indexBy('id');

    // check alternate syntax for NOT
    $otherParticipantResult2 = Participant::get()
      ->setCheckPermissions(FALSE)
      ->setSelect(['id'])
      ->addClause('NOT', 'AND', [
        ['event_id', '=', $firstEventId],
        ['contact_id', '=', $firstContactId],
      ])
      ->execute()
      ->indexBy('id');

    $this->assertEquals($otherParticipantResult, $otherParticipantResult2);

    $this->assertEquals($participantCount - 1,
      count($otherParticipantResult),
      "failed to exclude a single record on complex criteria");
    // check the record we have excluded is the right one:

    $this->assertFalse(
      $otherParticipantResult->offsetExists($firstParticipantId),
      'excluded wrong record');

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

}
