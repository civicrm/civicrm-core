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

  /**
   * @param array $participant
   */
  public function __construct($participant = []) {
    parent::__construct();
    $this->copyValues($participant);

    $this->email = $participant['email'] ?? NULL;
  }

  /**
   * @param array $participantParams
   *   You MUST pass in event_id and cart_id
   *
   * @return CRM_Event_Cart_BAO_MerParticipant
   * @throws Exception
   */
  public static function create(&$participantParams) {
    if (empty($participantParams['event_id'] || empty($participantParams['cart_id']))) {
      throw new CRM_Core_Exception('MerParticipant create: Missing required params: event_id/cart_id');
    }
    $participantParams['contact_id'] = $participantParams['contact_id'] ?? CRM_Event_Cart_Form_Cart::find_or_create_contact();
    $participantParams['role_id'] = $participantParams['role_id'] ?? CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'role_id', 'Attendee');
    $participantParams['status_id'] = $participantParams['status_id'] ?? CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'status_id', 'Pending in cart');

    $participant = reset(civicrm_api3('Participant', 'create', $participantParams)['values']);
    return new CRM_Event_Cart_BAO_MerParticipant($participant);
  }

  /**
   * @param int $event_cart_id
   *
   * @return array|null
   */
  public static function find_all_by_cart_id($event_cart_id) {
    if ($event_cart_id == NULL) {
      return NULL;
    }
    $participants = \Civi\Api4\Participant::get(FALSE)
      ->addWhere('cart_id', '=', $event_cart_id)
      ->execute();
    $result = [];
    foreach ($participants as $participant) {
      $result[] = new CRM_Event_Cart_BAO_MerParticipant($participant);
    }
    return $result;
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
    $participants = \Civi\Api4\Participant::get(FALSE)
      ->addWhere('event_id', '=', $event_id)
      ->addWhere('cart_id', '=', $event_cart_id)
      ->execute();
    $result = [];
    foreach ($participants as $participant) {
      $result[$participant['id']] = new CRM_Event_Cart_BAO_MerParticipant($participant);
    }
    return $result;
  }

  /**
   * @param int $id
   *
   * @return \CRM_Event_Cart_BAO_MerParticipant
   */
  public static function get_by_id($id) {
    $participant = \Civi\Api4\Participant::get(FALSE)
      ->addWhere('id', '=', $id)
      ->execute()
      ->first();
    return new CRM_Event_Cart_BAO_MerParticipant($participant);
  }

  public function load_associations() {
    $email = \Civi\Api4\Email::get(FALSE)
      ->addWhere('contact_id', '=', $this->contact_id)
      ->addOrderBy('is_primary', 'DESC')
      ->execute()
      ->first();
    $this->email = $email['email'] ?? NULL;
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
   * @return CRM_Event_Cart_Form_MerParticipant
   */
  public function get_form() {
    return new CRM_Event_Cart_Form_MerParticipant($this);
  }

}
