<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use Civi\Api4\EventCartParticipant;

/**
 * Class CRM_Event_Cart_BAO_MerParticipant
 */
class CRM_Event_Cart_BAO_MerParticipant extends CRM_Event_BAO_Participant {

  /**
   * @var string
   */
  public $email = NULL;

  /**
   * @var int
   */
  public $contribution_id = NULL;

  /**
   * @var \CRM_Event_Cart_BAO_Cart
   */
  public $cart = NULL;

  private $cart_id;

  /**
   * @param array $participant
   * @param int|null $cart_id
   */
  public function __construct($participant, $cart_id) {
    parent::__construct();
    $a = (array) $participant;
    $this->copyValues($a);
    $this->cart_id = $cart_id;
    $this->email = $a['email'] ?? NULL;
  }

  /**
   * @param array $params
   *
   * @return CRM_Event_Cart_BAO_MerParticipant
   * @throws Exception
   */
  public static function create(&$params) {
    $participantParams = [
      'id' => $params['id'] ?? NULL,
      'role_id' => self::get_attendee_role_id(),
      'status_id' => self::get_pending_in_cart_status_id(),
      'contact_id' => $params['contact_id'],
      'event_id' => $params['event_id'],
    ];
    $participant = CRM_Event_BAO_Participant::create($participantParams);
    if (!empty($params['cart_id'])) {
      EventCartParticipant::save(FALSE)
        ->addRecord(['cart_id' => $params['cart_id'], 'participant_id' => $participant->id])
        ->setMatch(['cart_id', 'participant_id'])
        ->execute();
    }
    $mer_participant = new CRM_Event_Cart_BAO_MerParticipant($participant, $params['cart_id'] ?? NULL);
    return $mer_participant;
  }

  /**
   * @return mixed
   */
  public static function get_attendee_role_id() {
    $roles = CRM_Event_PseudoConstant::participantRole(NULL, "v.label='Attendee'");
    $role_names = array_keys($roles);
    return end($role_names);
  }

  /**
   * @return mixed
   */
  public static function get_pending_in_cart_status_id() {
    $status_types = CRM_Event_PseudoConstant::participantStatus(NULL, "name='Pending in cart'");
    $status_names = array_keys($status_types);
    return end($status_names);
  }

  /**
   * @param int $event_id
   * @param int $event_cart_id
   *
   * @return array|null
   */
  public static function find_all_by_event_and_cart_id($event_id, $event_cart_id) {
    if ($event_cart_id == NULL) {
      return NULL;
    }
    $participants = EventCartParticipant::get(FALSE)
      ->addWhere('cart_id', '=', $event_cart_id)
      ->addWhere('participant_id.event_id', '=', $event_id)
      ->execute();
    $result = [];
    foreach ($participants as $participant) {
      $dao = new CRM_Event_BAO_Participant();
      $dao->id = $participant['participant_id'];
      $dao->find(TRUE);
      $result[] = new CRM_Event_Cart_BAO_MerParticipant($dao, $event_cart_id);
    }
    return $result;
  }

  /**
   * @param array $params
   *
   * @return array
   */
  public static function find_all_by_params($params) {
    if (!empty($params['cart_id'])) {
      $participants = EventCartParticipant::get(FALSE)
        ->addWhere('cart_id', '=', $params['cart_id'])
        ->execute();
      foreach ($participants as $participant) {
        $dao = new CRM_Event_BAO_Participant();
        $dao->id = $participant['participant_id'];
        $dao->fetch();
        $result[] = new CRM_Event_Cart_BAO_MerParticipant((array) $dao, $params['cart_id']);
      }
    }
    else {
      $participant = new CRM_Event_BAO_Participant();
      $participant->copyValues($params);
      $result = [];
      if ($participant->find()) {
        while ($participant->fetch()) {
          // hopefully unreachable.
          $result[] = new CRM_Event_Cart_BAO_MerParticipant(clone($participant), NULL);
        }
      }
    }
    return $result;
  }

  /**
   * @param int $id
   *
   * @return mixed
   */
  public static function get_by_id($id) {
    $results = self::find_all_by_params(['id' => $id]);
    return array_pop($results);
  }

  public function load_associations() {
    $contact_details = CRM_Contact_BAO_Contact::getContactDetails($this->contact_id);
    $this->email = $contact_details[1];
  }

  /**
   * @return int
   */
  public function get_participant_index() {
    if (!$this->cart) {
      $this->cart = CRM_Event_Cart_BAO_Cart::find_by_id($this->cart_id);
      $this->cart->load_associations();
    }
    $index = $this->cart->get_participant_index_from_id($this->id);
    return $index + 1;
  }

  /**
   * @param $contact
   *
   * @return null
   */
  public static function billing_address_from_contact($contact) {
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

  /**
   * @return CRM_Event_Cart_Form_MerParticipant
   */
  public function get_form() {
    return new CRM_Event_Cart_Form_MerParticipant($this);
  }

}
