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
          'cart_id' => $this->cart->id,
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
    $empty_seats = CRM_Event_BAO_Participant::eventFull($event_id, TRUE);
    if (is_numeric($empty_seats)) {
      return $empty_seats;
    }
    if (is_string($empty_seats)) {
      return 0;
    }
    return NULL;
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
      'is_deleted' => CRM_Utils_Array::value('is_deleted', $fields, TRUE),
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

}
