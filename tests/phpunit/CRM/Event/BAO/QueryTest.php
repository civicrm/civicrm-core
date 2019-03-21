<?php

/**
 * @group headless
 */
class CRM_Event_BAO_QueryTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function testParticipantNote() {
    $event = $this->eventCreate();
    $this->individualCreate([
      'api.participant.create' => [
        'event_id' => $event['id'],
        'note'     => 'some_note',
      ]
    ]);
    $this->individualCreate([
      'api.participant.create' => [
        'event_id' => $event['id'],
        'note'     => 'some_other_note',
      ]
    ]);
    $params = [
      [
        0 => 'participant_note',
        1 => '=',
        2 => 'some_note',
        3 => 1,
        4 => 0,
      ]
    ];

    $query = new CRM_Contact_BAO_Query($params, NULL, NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CONTACTS);
    $sql = $query->query(FALSE);
    $result = CRM_Core_DAO::executeQuery(implode(' ', $sql));
    $this->assertEquals(1, $result->N);
  }

}
