<?php

/**
 * Class Participant
 */
class Participant extends PHPUnit_Framework_Testcase {
  /**
   * Helper function to create a Participant.
   *
   * @param int $contactId
   * @param int $eventId
   *
   * @return int
   *   id of created Participant
   */
  public static function create($contactId, $eventId) {
    $params = array(
      'send_receipt' => 1,
      'is_test' => 0,
      'is_pay_later' => 0,
      'event_id' => $eventId,
      'register_date' => date('Y-m-d') . " 00:00:00",
      'role_id' => 1,
      'status_id' => 1,
      'source' => 'Event_' . $eventId,
      'contact_id' => $contactId,
    );

    require_once 'CRM/Event/BAO/Participant.php';
    $participant = CRM_Event_BAO_Participant::add($params);
    return $participant->id;
  }

  /**
   * Helper function to delete a participant.
   *
   * @param int $participantId
   * @return bool
   *   true if participant deleted, false otherwise
   */
  public static function delete($participantId) {
    require_once 'CRM/Event/BAO/Participant.php';
    return CRM_Event_BAO_Participant::deleteParticipant($participantId);
  }

}
