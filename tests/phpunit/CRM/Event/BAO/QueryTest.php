<?php

/**
 * @group headless
 */
class CRM_Event_BAO_QueryTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
  }

  /**
   * Test searching for participant note.
   *
   * @throws \CRM_Core_Exception
   */
  public function testParticipantNote(): void {
    $event = $this->eventCreateUnpaid();
    $this->individualCreate([
      'api.participant.create' => [
        'event_id' => $event['id'],
        'note'     => 'some_note',
      ],
    ]);
    $this->individualCreate([
      'api.participant.create' => [
        'event_id' => $event['id'],
        'note'     => 'some_other_note',
      ],
    ]);
    $params = [
      [
        0 => 'participant_note',
        1 => '=',
        2 => 'some_note',
        3 => 1,
        4 => 0,
      ],
    ];

    $query = new CRM_Contact_BAO_Query($params, NULL, NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CONTACTS);
    $sql = $query->query(FALSE);
    $result = CRM_Core_DAO::executeQuery(implode(' ', $sql));
    $this->assertEquals(1, $result->N);
  }

  /**
   * Unit test to check if participant search retrieves correct event type id.
   *
   * @throws \CRM_Core_Exception
   */
  public function testEventType(): void {
    $event = $this->eventCreateUnpaid();
    $contactId = $this->individualCreate([
      'api.participant.create' => [
        'event_id' => $event['id'],
      ],
    ]);
    $params = [
      [
        0 => 'event_id',
        1 => '=',
        2 => $event['id'],
        3 => 1,
        4 => 0,
      ],
    ];

    $returnProperties = [
      'event_type_id' => 1,
      'contact_id' => 1,
      'event_id' => 1,
    ];

    $query = new CRM_Contact_BAO_Query(
      $params, $returnProperties, NULL,
      FALSE, FALSE, CRM_Contact_BAO_Query::MODE_EVENT
    );
    $sql = $query->query(FALSE);
    $result = CRM_Core_DAO::executeQuery(implode(' ', $sql));

    $this->assertEquals(1, $result->N);
    $result->fetch();

    $this->assertEquals($contactId, $result->contact_id);
    $this->assertEquals($event['id'], $result->event_id);
    $eventTypeId = $this->callAPISuccessGetValue('Event', [
      'id' => $event['id'],
      'return' => 'event_type_id',
    ]);
    $this->assertEquals($eventTypeId, $result->event_type_id);
  }

  /**
   * The "Event Active On" filter should match every event that is running at
   * some point during the search range not only events that start within it.
   *
   * @dataProvider getEventActiveOnSearches
   *
   * @throws \CRM_Core_Exception
   */
  public function testEventActiveOn(?string $low, ?string $high, array $expected): void {
    $this->useTransaction();
    $events = $this->createEventsForActiveOnSearch();

    $params = [];
    if ($low !== NULL) {
      $params[] = ['event_low', '=', $low, 0, 0];
    }
    if ($high !== NULL) {
      $params[] = ['event_high', '=', $high, 0, 0];
    }

    $query = new CRM_Contact_BAO_Query(
      $params, ['event_id' => 1], NULL,
      FALSE, FALSE, CRM_Contact_BAO_Query::MODE_EVENT
    );
    $dao = CRM_Core_DAO::executeQuery(implode(' ', $query->query(FALSE)));

    $found = [];
    while ($dao->fetch()) {
      // Ignore any events left behind by other tests in this class.
      $identifier = array_search((int) $dao->event_id, $events, TRUE);
      if ($identifier !== FALSE) {
        $found[] = $identifier;
      }
    }
    sort($found);
    sort($expected);
    $this->assertEquals($expected, $found);
  }

  /**
   * Search ranges for "Event Active On" and the events they should return.
   *
   * The events being searched are set up in createEventsForActiveOnSearch().
   *
   * @return array
   */
  public static function getEventActiveOnSearches(): array {
    return [
      // CASE A: https://lab.civicrm.org/dev/core/-/issues/6386 : the range
      // falls wholly inside the long running event, which used to be excluded
      // because it started before the range.
      'range inside a long running event' => [
        '2026-01-15', '2026-01-20',
        ['longer_period', 'within'],
      ],
      // CASE B: The range opens on the day the long running event closes, so
      // they still overlap by a few hours.
      'range starting on the event end date' => [
        '2026-03-31', '2026-04-05',
        ['longer_period'],
      ],
      // CASE C: The range closes on the day the long running event opens.
      'range ending on the event start date' => [
        '2025-12-20', '2026-01-01',
        ['longer_period'],
      ],
      // CASE D: An event with no end date runs on its start date, so it is
      // matched by a range covering that day.
      'range covering an event with no end date' => [
        '2026-01-10', '2026-01-10',
        ['longer_period', 'no_end_date'],
      ],
      // CASE E:
      'range starting the day after an event with no end date' => [
        '2026-01-11', '2026-01-14',
        ['longer_period'],
      ],
      // CASE F: A gap between the events, so nothing is running.
      'range matching no events' => [
        '2025-11-10', '2025-11-20',
        [],
      ],
      // CASE G: Only a "from" date anything that had not finished by then.
      'from date only' => [
        '2026-01-15', NULL,
        ['longer_period', 'within', 'no_end_date_future', 'future'],
      ],
      // CASE H: Only a "to" date anything that had started by then.
      'to date only' => [
        NULL, '2026-01-20',
        ['longer_period', 'within', 'no_end_date', 'past'],
      ],
    ];
  }

  /**
   * Create one event per scenario, each with a single participant.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  private function createEventsForActiveOnSearch(): array {
    $events = [];
    foreach ([
      'longer_period' => ['2026-01-01 09:00:00', '2026-03-31 17:00:00'],
      'within' => ['2026-01-16 09:00:00', '2026-01-18 17:00:00'],
      'no_end_date' => ['2026-01-10 09:00:00', NULL],
      'no_end_date_future' => ['2026-09-01 09:00:00', NULL],
      'past' => ['2025-11-01 09:00:00', '2025-11-05 17:00:00'],
      'future' => ['2026-06-01 09:00:00', '2026-06-05 17:00:00'],
    ] as $identifier => [$startDate, $endDate]) {
      $event = $this->eventCreateUnpaid([
        'title' => 'Event Active On - ' . $identifier,
        'start_date' => $startDate,
        'end_date' => $endDate,
      ], $identifier);
      $this->individualCreate([
        'api.participant.create' => ['event_id' => $event['id']],
      ], $identifier);
      $events[$identifier] = (int) $event['id'];
    }
    return $events;
  }

  /**
   * Test provided event search parameters.
   *
   * @dataProvider getEventSearchParameters
   *
   * @throws \CRM_Core_Exception
   */
  public function testParameters($parameters, $expected) {
    $query = new CRM_Contact_BAO_Query(
      $parameters, NULL, NULL,
      FALSE, FALSE, CRM_Contact_BAO_Query::MODE_EVENT
    );
    $query->query(FALSE);
    $this->assertEquals($expected['where'], trim($query->_whereClause));
    $this->assertEquals($expected['qill'], trim($query->_qill[0][0]));
  }

  /**
   * @return array
   */
  public static function getEventSearchParameters() {
    return [
      [
        [['participant_status_id', '=', 1, 0, 0]],
        [
          'where' => '( civicrm_participant.status_id = 1 )',
          'qill' => 'Status ID = Registered',
        ],
      ],
      [
        [['participant_status_id', 'IN', [1, 2], 0, 0]],
        [
          'where' => '( civicrm_participant.status_id IN ("1", "2") )',
          'qill' => 'Status ID In Registered, Attended',
        ],
      ],
    ];
  }

}
