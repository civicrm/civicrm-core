<?php

/**
 * Class CRM_Event_Cart_Form_Cart
 */
class CRM_Event_Cart_Form_Cart extends CRM_Core_Form {
  public $cart;

  public $_action;
  public $contact;
  public $event_cart_id = NULL;
  public $_mode;
  public $participants;

  public function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE);
    $this->_mode = 'live';
    $this->loadCart();

    $this->checkWaitingList();

    $this->assignBillingType();

    $event_titles = array();
    foreach ($this->cart->get_main_events_in_carts() as $event_in_cart) {
      $event_titles[] = $event_in_cart->event->title;
    }
    $this->description = ts("Online Registration for %1", array(1 => implode(", ", $event_titles)));
    if (!isset($this->discounts)) {
      $this->discounts = array();
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
        $participant_params = array(
          'cart_id' => $this->cart->id,
          'event_id' => $event_in_cart->event_id,
          'contact_id' => self::find_or_create_contact($this->getContactID()),
        );
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
    $empty_seats = CRM_Event_BAO_Participant::eventFull($event_id, TRUE);
    if (is_numeric($empty_seats)) {
      return $empty_seats;
    }
    if (is_string($empty_seats)) {
      return 0;
    }
    else {
      return NULL;
    }
  }

  /**
   * @return bool
   */
  public static function is_administrator() {
    global $user;
    return CRM_Core_Permission::check('administer CiviCRM');
  }

  /**
   * @return mixed
   */
  public function getContactID() {
    //XXX when do we query 'cid' ?
    $tempID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    //check if this is a checksum authentication
    $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this);
    if ($userChecksum) {
      //check for anonymous user.
      $validUser = CRM_Contact_BAO_Contact_Utils::validChecksum($tempID, $userChecksum);
      if ($validUser) {
        return $tempID;
      }
    }

    // check if the user is registered and we have a contact ID
    $session = CRM_Core_Session::singleton();
    return $session->get('userID');
  }

  /**
   * @param $fields
   *
   * @return mixed|null
   */
  public static function find_contact($fields) {
    return CRM_Contact_BAO_Contact::getFirstDuplicateContact($fields, 'Individual', 'Unsupervised', array(), FALSE);
  }

  /**
   * @param int $registeringContactID
   * @param array $fields
   *
   * @return int|mixed|null
   */
  public static function find_or_create_contact($registeringContactID = NULL, $fields = array()) {
    $contact_id = self::find_contact($fields);

    if ($contact_id) {
      return $contact_id;
    }
    $contact_params = array(
      'email-Primary' => CRM_Utils_Array::value('email', $fields, NULL),
      'first_name' => CRM_Utils_Array::value('first_name', $fields, NULL),
      'last_name' => CRM_Utils_Array::value('last_name', $fields, NULL),
      'is_deleted' => CRM_Utils_Array::value('is_deleted', $fields, TRUE),
    );
    $no_fields = array();
    $contact_id = CRM_Contact_BAO_Contact::createProfileContact($contact_params, $no_fields, NULL);
    if (!$contact_id) {
      CRM_Core_Error::displaySessionError("Could not create or match a contact with that email address.  Please contact the webmaster.");
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

}
