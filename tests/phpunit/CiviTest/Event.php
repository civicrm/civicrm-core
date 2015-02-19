<?php

/**
 * Class Event
 */
class Event extends PHPUnit_Framework_Testcase {
  /**
   * Helper function to create
   * an Event
   *
   * @param int $contactId
   *
   * @return int
   *   id of created Event
   */
  public static function create($contactId) {
    require_once "CRM/Event/BAO/Event.php";
    $params = array(
      'title' => 'Test Event',
      'event_type_id' => 1,
      'default_role_id' => 1,
      'participant_listing_id' => 1,
      'summary' => 'Created for Test Coverage BAO',
      'description' => 'Test Coverage BAO',
      'is_public' => 1,
      'start_date' => '20080526200000',
      'end_date' => '20080530200000',
      'is_active' => 1,
      'contact_id' => $contactId,
    );

    $event = CRM_Event_BAO_Event::create($params);
    return $event->id;
  }

  /**
   * Helper function to delete an Event.
   *
   * @param int $eventId
   * @return bool
   *   true if event deleted, false otherwise
   */
  public static function delete($eventId) {
    return CRM_Event_BAO_Event::del($eventId);
  }

}
