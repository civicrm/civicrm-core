<?php
class CRM_Event_Cart_BAO_MerParticipant extends CRM_Event_BAO_Participant {
  public $email = NULL;
  public $contribution_id = NULL;
  public $cart = NULL;

  //XXX 
  function __construct($participant = NULL) {
    parent::__construct();
    $a = (array)$participant;
    $this->copyValues($a);

    $this->email = CRM_Utils_Array::value('email', $participant);
  }

  public static function &create(&$params) {
    $participantParams = array(
      'id' => CRM_Utils_Array::value('id', $params),
      'role_id' => self::get_attendee_role_id(),
      'status_id' => self::get_pending_in_cart_status_id(),
      'contact_id' => $params['contact_id'],
      'event_id' => $params['event_id'],
      'cart_id' => $params['cart_id'],
      //XXX
      //'registered_by_id'  =>
      //'discount_amount'   =>
      //'fee_level'         => $params['fee_level'],
    );
    $participant = CRM_Event_BAO_Participant::create($participantParams);

    if (is_a($participant, 'CRM_Core_Error')) {
      CRM_Core_Error::fatal(ts('There was an error creating a cart participant'));
    }

    $mer_participant = new CRM_Event_Cart_BAO_MerParticipant($participant);

    return $mer_participant;
  }

  static function get_attendee_role_id() {
    $roles = CRM_Event_PseudoConstant::participantRole(NULL, "v.label='Attendee'");
    $role_names = array_keys($roles);
    return end($role_names);
  }

  static function get_pending_in_cart_status_id() {
    $status_types = CRM_Event_PseudoConstant::participantStatus(NULL, "name='Pending in cart'");
    $status_names = array_keys($status_types);
    return end($status_names);
  }

  public static function find_all_by_cart_id($event_cart_id) {
    if ($event_cart_id == NULL) {
      return NULL;
    }
    return self::find_all_by_params(array('cart_id' => $event_cart_id));
  }

  public static function find_all_by_event_and_cart_id($event_id, $event_cart_id) {
    if ($event_cart_id == NULL) {
      return NULL;
    }
    return self::find_all_by_params(array('event_id' => $event_id, 'cart_id' => $event_cart_id));
  }

  public static function find_all_by_params($params) {
    $participant = new CRM_Event_BAO_Participant();
    $participant->copyValues($params);
    $result = array();
    if ($participant->find()) {
      while ($participant->fetch()) {
        $result[] = new CRM_Event_Cart_BAO_MerParticipant(clone($participant));
      }
    }
    return $result;
  }

  public static function get_by_id($id) {
    $results = self::find_all_by_params(array('id' => $id));
    return array_pop($results);
  }

  function load_associations() {
    $contact_details = CRM_Contact_BAO_Contact::getContactDetails($this->contact_id);
    $this->email = $contact_details[1];
  }

  function get_participant_index() {
    if (!$this->cart) {
      $this->cart = CRM_Event_Cart_BAO_Cart::find_by_id($this->cart_id);
      $this->cart->load_associations();
    }
    $index = $this->cart->get_participant_index_from_id($this->id);
    return $index + 1;
  }

  static function billing_address_from_contact($contact) {
    foreach ($contact->address as $loc) {
      if ($loc['is_billing']) {
        return $loc;
      }
    }
    foreach ($contact->address as $loc) {
      if ($loc['is_primary']) {
        return $loc;
      }
    }
    return NULL;
  }

  function get_form() {
    return new CRM_Event_Cart_Form_MerParticipant($this);
  }
}

