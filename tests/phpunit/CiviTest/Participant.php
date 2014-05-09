<?php
class Participant extends PHPUnit_Framework_Testcase {
  /**
   * Helper function to create a Participant
   *
   * @param $contactId
   * @param $eventId
   *
   * @return mixed $participant id of created Participant
   */
  static function create($contactId, $eventId) {
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
   * Helper function to delete a participant
   *
   * @param $participantId
   * @internal param int $participantID id of the participant to delete
   * @return boolean true if participant deleted, false otherwise
   */
  static function delete($participantId) {
    require_once 'CRM/Event/BAO/Participant.php';
    return CRM_Event_BAO_Participant::deleteParticipant($participantId);
  }
}
