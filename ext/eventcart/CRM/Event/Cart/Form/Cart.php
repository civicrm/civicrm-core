<?php

/**
 * Class CRM_Event_Cart_Form_Cart
 */
class CRM_Event_Cart_Form_Cart extends CRM_Core_Form {

  /**
   * @var \CRM_Event_Cart_BAO_Cart
   */
  public $cart;

  public $_action;
  public $contact;
  public $event_cart_id = NULL;
  public $_mode;
  public $participants;

  /**
   * Provides way for extensions to add discounts to the event_registration_receipt emails.
   *
   * Todo: Do any extensions actually use this,
   * or can it be removed, and the email templates cleaned up?
   *
   * @var array
   * @deprecated Not recommended for new extensions.
   */
  public $discounts = [];

  public function preProcess() {
    $this->_action = CRM_Utils_Request::retrieveValue('action', 'String');
    $this->_mode = 'live';
    $this->loadCart();

    $this->checkWaitingList();

    $event_titles = [];
    foreach ($this->cart->get_main_events_in_carts() as $event_in_cart) {
      $event_titles[] = $event_in_cart->event->title;
    }
  }

  public function loadCart() {
    if ($this->event_cart_id == NULL) {
      $this->cart = CRM_Event_Cart_BAO_Cart::find_or_create_for_current_session();
    }
    else {
      $this->cart = CRM_Event_Cart_BAO_Cart::find_by_id($this->event_cart_id);
    }
    $this->cart->load_associations();
    $this->stub_out_and_inherit();
  }

  public function stub_out_and_inherit() {
    $transaction = new CRM_Core_Transaction();

    foreach ($this->cart->get_main_events_in_carts() as $event_in_cart) {
      if (empty($event_in_cart->participants)) {
        $participant_params = [
          'event_id' => $event_in_cart->event_id,
          'contact_id' => self::find_or_create_contact(),
        ];
        $participant = CRM_Event_Cart_BAO_MerParticipant::create($participant_params);
        $participant->save();
        $event_in_cart->add_participant($participant);
      }
      $event_in_cart->save();
    }
    $transaction->commit();
  }

  public function checkWaitingList() {
    foreach ($this->cart->events_in_carts as $event_in_cart) {
      $empty_seats = $this->checkEventCapacity($event_in_cart->event_id);
      if ($empty_seats === NULL) {
        continue;
      }
      foreach ($event_in_cart->participants as $participant) {
        $participant->must_wait = ($empty_seats <= 0);
        $empty_seats--;
      }
    }
  }

  /**
   * @param int $event_id
   *
   * @return bool|int|null|string
   */
  public function checkEventCapacity($event_id) {
    $empty_seats = self::eventFull($event_id, TRUE);
    if (is_numeric($empty_seats)) {
      return $empty_seats;
    }
    if (is_string($empty_seats)) {
      return 0;
    }
    return NULL;
  }

  /**
   * Check whether the event is full for participation and return as.
   * per requirements.
   *
   * @param int $eventId
   *   Event id.
   * @param bool $returnEmptySeats
   *   Are we require number if empty seats.
   * @param bool $includeWaitingList
   *   Consider waiting list in event full.
   *                 calculation or not. (it is for cron job  purpose)
   *
   * @param bool $returnWaitingCount
   * @param bool $considerTestParticipant deprecated, unused
   * @param bool $onlyPositiveStatuses
   *   When FALSE, count all participant statuses where is_counted = 1.  This includes
   *   both "Positive" participants (Registered, Attended, etc.) and waitlisted
   *   (and some pending) participants.
   *   When TRUE, count only participants with statuses of "Positive".
   *
   * @return bool|int|null|string
   *   1. false                 => If event having some empty spaces.
   * @throws \CRM_Core_Exception
   */
  private static function eventFull(
    $eventId,
    $returnEmptySeats = FALSE,
    $includeWaitingList = TRUE,
    $returnWaitingCount = FALSE,
    $considerTestParticipant = FALSE,
    $onlyPositiveStatuses = FALSE
  ) {
    $result = NULL;
    if (!$eventId) {
      return $result;
    }

    // consider event is full when.
    // 1. (count(is_counted) >= event_size) or
    // 2. (count(participants-with-status-on-waitlist) > 0)
    // It might be case there are some empty spaces and still event
    // is full, as waitlist might represent group require spaces > empty.

    $countedStatuses = \CRM_Event_BAO_Participant::buildOptions('status_id', NULL, ['is_counted' => 1]);;
    $positiveStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Positive'");
    $waitingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Waiting'");
    $onWaitlistStatusId = array_search('On waitlist', $waitingStatuses);

    $where = [' event.id = %1 ', ' participant.is_test = 0 '];
    $participantRoleClause = self::getParticipantRoleClause();
    if ($participantRoleClause) {
      $where[] = " participant.role_id " . $participantRoleClause;
    }
    $eventParams = [1 => [$eventId, 'Positive']];

    //in case any waiting, straight forward event is full.
    if ($includeWaitingList && $onWaitlistStatusId) {

      //build the where clause.
      $whereClause = ' WHERE ' . implode(' AND ', $where);
      $whereClause .= " AND participant.status_id = $onWaitlistStatusId ";
      $eventSeatsWhere = implode(' AND ', $where) . " AND ( participant.status_id = $onWaitlistStatusId )";

      $query = "
    SELECT  participant.id id
      FROM  civicrm_participant participant
INNER JOIN  civicrm_event event ON ( event.id = participant.event_id )
            {$whereClause}";

      $hasWaitlistedParticipants = CRM_Core_DAO::singleValueQuery($query, $eventParams);
      if ($hasWaitlistedParticipants) {
        //oops here event is full and we don't want waiting count.
        if ($returnWaitingCount) {
          return CRM_Event_BAO_Event::eventTotalSeats($eventId, $eventSeatsWhere);
        }
        return CRM_Core_DAO::singleValueQuery('SELECT event_full_text FROM civicrm_event WHERE id = ' . (int) $eventId) ?: ts('This event is full.');
      }
    }

    //Consider only counted participants, or alternatively only registered (not on waitlist) participants.
    if ($onlyPositiveStatuses) {
      $where[] = ' participant.status_id IN ( ' . implode(', ', array_keys($positiveStatuses)) . ' ) ';
    }
    else {
      $where[] = ' participant.status_id IN ( ' . implode(', ', array_keys($countedStatuses)) . ' ) ';
    }
    $whereClause = ' WHERE ' . implode(' AND ', $where);
    $eventSeatsWhere = implode(' AND ', $where);

    $query = "
    SELECT  participant.id id,
            event.event_full_text as event_full_text,
            event.max_participants as max_participants
      FROM  civicrm_participant participant
INNER JOIN  civicrm_event event ON ( event.id = participant.event_id )
            {$whereClause}";

    $eventMaxSeats = NULL;
    $eventFullText = ts('This event is full.');
    $participants = CRM_Core_DAO::executeQuery($query, $eventParams);
    while ($participants->fetch()) {
      if ($participants->event_full_text) {
        $eventFullText = $participants->event_full_text;
      }
      $eventMaxSeats = $participants->max_participants;
      //don't have limit for event seats.
      if ($participants->max_participants == NULL) {
        return $result;
      }
    }

    //get the total event seats occupied by these participants.
    $eventRegisteredSeats = CRM_Event_BAO_Event::eventTotalSeats($eventId, $eventSeatsWhere);

    if ($eventRegisteredSeats) {
      if ($eventRegisteredSeats >= $eventMaxSeats) {
        $result = $eventFullText;
      }
      elseif ($returnEmptySeats) {
        $result = $eventMaxSeats - $eventRegisteredSeats;
      }
      return $result;
    }
    else {
      $query = '
SELECT  event.event_full_text,
        event.max_participants
  FROM  civicrm_event event
 WHERE  event.id = %1';
      $event = CRM_Core_DAO::executeQuery($query, $eventParams);
      while ($event->fetch()) {
        $eventFullText = $event->event_full_text;
        $eventMaxSeats = $event->max_participants;
      }
    }

    // no limit for registration.
    if ($eventMaxSeats == NULL) {
      return $result;
    }
    if ($eventMaxSeats) {
      return ($returnEmptySeats) ? (int) $eventMaxSeats : FALSE;
    }

    return $eventFullText;
  }

  /**
   * Get the clause to exclude uncounted participant roles.
   *
   * @internal do not call from outside core code.
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  private static function getParticipantRoleClause(): string {
    // Only count Participant Roles with the "Counted?" flag.
    $participantRoles = CRM_Event_BAO_Participant::buildOptions('role_id', NULL, ['filter' => TRUE]);
    $allRoles = CRM_Event_BAO_Participant::buildOptions('role_id');
    if ($participantRoles === $allRoles) {
      // Don't complicate the query if no roles are excluded.
      return '';
    }
    if (!empty($participantRoles)) {
      $escapedRoles = [];
      foreach (array_keys($participantRoles) as $participantRole) {
        $escapedRoles[] = CRM_Utils_Type::escape($participantRole, 'String');
      }

      $regexp = "([[:cntrl:]]|^)" . implode('([[:cntrl:]]|$)|([[:cntrl:]]|^)', $escapedRoles) . "([[:cntrl:]]|$)";
      $participantRoleClause = "REGEXP '{$regexp}'";
    }
    return $participantRoleClause ?? '';
  }

  /**
   * @return int
   * @throws \CRM_Core_Exception
   */
  public function getContactID() {
    $tempID = CRM_Utils_Request::retrieveValue('cid', 'Positive');

    //check if this is a checksum authentication
    $userChecksum = CRM_Utils_Request::retrieveValue('cs', 'String');
    if ($userChecksum) {
      //check for anonymous user.
      $validUser = CRM_Contact_BAO_Contact_Utils::validChecksum($tempID, $userChecksum);
      if ($validUser) {
        return $tempID;
      }
    }

    // check if the user is registered and we have a contact ID
    return CRM_Core_Session::getLoggedInContactID();
  }

  /**
   * @param $fields
   *
   * @return mixed|null
   */
  public static function find_contact($fields) {
    return CRM_Contact_BAO_Contact::getFirstDuplicateContact($fields, 'Individual', 'Unsupervised', [], FALSE);
  }

  /**
   * @param array $fields
   *
   * @return int|mixed|null
   */
  public static function find_or_create_contact($fields = []) {
    $contact_id = self::find_contact($fields);

    if ($contact_id) {
      return $contact_id;
    }
    $contact_params = [
      'email-Primary' => $fields['email'] ?? NULL,
      'first_name' => $fields['first_name'] ?? NULL,
      'last_name' => $fields['last_name'] ?? NULL,
      'is_deleted' => $fields['is_deleted'] ?? TRUE,
    ];
    $no_fields = [];
    $contact_id = CRM_Contact_BAO_Contact::createProfileContact($contact_params, $no_fields, NULL);
    if (!$contact_id) {
      CRM_Core_Session::setStatus(ts("Could not create or match a contact with that email address. Please contact the webmaster."), '', 'error');
    }
    return $contact_id;
  }

  /**
   * @param string $page_name
   *
   * @return mixed
   */
  public function getValuesForPage($page_name) {
    $container = $this->controller->container();
    return $container['values'][$page_name];
  }

  /**
   *
   * @deprecated copy of previously shared code.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the name / value pairs.
   *                        in a hierarchical manner
   *
   * @return CRM_Contact_BAO_Contact
   */
  protected function retrieveContact(&$params, &$defaults = []) {
    if (array_key_exists('contact_id', $params)) {
      $params['id'] = $params['contact_id'];
    }
    elseif (array_key_exists('id', $params)) {
      $params['contact_id'] = $params['id'];
    }

    $contact = new CRM_Contact_BAO_Contact();

    $contact->copyValues($params);

    if ($contact->find(TRUE)) {

      CRM_Core_DAO::storeValues($contact, $values);
      $contact->contact_id = $contact->id;
    }

    unset($params['id']);
    $contact->email = $defaults['email'] = CRM_Core_BAO_Email::getValues(['contact_id' => $params['contact_id']]);
    $contact->address = $defaults['address'] = CRM_Core_BAO_Address::getValues(['contact_id' => $params['contact_id']]);
    return $contact;
  }

}
