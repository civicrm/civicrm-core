<?php

/**
 * @group headless
 */
class CRM_Event_BAO_QueryTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Test searching for participant note.
   *
   * @throws \CRM_Core_Exception
   */
  public function testParticipantNote() {
    $event = $this->eventCreate();
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
  public function testEventType() {
    $event = $this->eventCreate();
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
  public function getEventSearchParameters() {
    return [
      [
        [['participant_status_id', '=', 1, 0, 0]],
        [
          'where' => '( civicrm_participant.status_id = 1 )',
          'qill' => 'Participant Status (ID) = Registered',
        ],
      ],
      [
        [['participant_status_id', 'IN', [1, 2], 0, 0]],
        [
          'where' => '( civicrm_participant.status_id IN ("1", "2") )',
          'qill' => 'Participant Status (ID) In Registered, Attended',
        ],
      ],
    ];
  }

}
