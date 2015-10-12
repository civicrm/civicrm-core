<?php

/**
 * Class CRM_Event_Cart_BAO_Cart
 */
class CRM_Event_Cart_BAO_Cart extends CRM_Event_Cart_DAO_Cart {
  public $associations_loaded = FALSE;
  /* event_in_cart_id => $event_in_cart */
  public $events_in_carts = array();

  /**
   * @param array $params
   *
   * @return $this
   */
  public static function add(&$params) {
    $cart = new CRM_Event_Cart_BAO_Cart();
    $cart->copyValues($params);
    $result = $cart->save();
    return $result;
  }

  /**
   * @param int $event_id
   *
   * @return mixed
   */
  public function add_event($event_id) {
    $this->load_associations();
    $event_in_cart = $this->get_event_in_cart_by_event_id($event_id);
    if ($event_in_cart) {
      return $event_in_cart;
    }

    $params = array(
      'event_id' => $event_id,
      'event_cart_id' => $this->id,
    );
    $event_in_cart = CRM_Event_Cart_BAO_EventInCart::create($params);
    $event_in_cart->load_associations($this);
    $this->events_in_carts[$event_in_cart->event_id] = $event_in_cart;
    return $this->events_in_carts[$event_in_cart->event_id];
  }

  /**
   * @param $participant
   */
  public function add_participant_to_cart($participant) {
    $event_in_cart = $this->get_event_in_cart_by_event_id($participant->event_id);
    if (!$event_in_cart) {
      $event_in_cart = $this->add_event($participant->event_id);
    }
    $event_in_cart->add_participant($participant);
    $event_in_cart->save();
  }

  /**
   * @param array $params
   *
   * @return $this
   * @throws Exception
   */
  public static function create($params) {
    $transaction = new CRM_Core_Transaction();

    $cart = self::add($params);

    if (is_a($cart, 'CRM_Core_Error')) {
      $transaction->rollback();
      CRM_Core_Error::fatal(ts('There was an error creating an event cart'));
    }

    $transaction->commit();

    return $cart;
  }

  /**
   * @param int $id
   *
   * @return bool|CRM_Event_Cart_BAO_Cart
   */
  public static function find_by_id($id) {
    return self::find_by_params(array('id' => $id));
  }

  /**
   * @param array $params
   *
   * @return bool|CRM_Event_Cart_BAO_Cart
   */
  public static function find_by_params($params) {
    $cart = new CRM_Event_Cart_BAO_Cart();
    $cart->copyValues($params);
    if ($cart->find(TRUE)) {
      return $cart;
    }
    else {
      return FALSE;
    }
  }

  /**
   * @return self|bool|CRM_Event_Cart_BAO_Cart
   */
  public static function find_or_create_for_current_session() {
    $session = CRM_Core_Session::singleton();
    $event_cart_id = $session->get('event_cart_id');
    $userID = $session->get('userID');
    $cart = FALSE;
    if (!is_null($event_cart_id)) {
      $cart = self::find_uncompleted_by_id($event_cart_id);
      if ($cart && $userID) {
        if (!$cart->user_id) {
          $saved_cart = self::find_uncompleted_by_user_id($userID);
          if ($saved_cart) {
            $cart->adopt_participants($saved_cart->id);
            $saved_cart->delete();
            $cart->load_associations();
          }
          else {
            $cart->user_id = $userID;
            $cart->save();
          }
        }
      }
    }
    if ($cart === FALSE) {
      if (is_null($userID)) {
        $cart = self::create(array());
      }
      else {
        $cart = self::find_uncompleted_by_user_id($userID);
        if ($cart === FALSE) {
          $cart = self::create(array('user_id' => $userID));
        }
      }
      $session->set('event_cart_id', $cart->id);
    }
    return $cart;
  }

  /**
   * @param int $id
   *
   * @return bool|CRM_Event_Cart_BAO_Cart
   */
  public static function find_uncompleted_by_id($id) {
    return self::find_by_params(array('id' => $id, 'completed' => 0));
  }

  /**
   * @param int $user_id
   *
   * @return bool|CRM_Event_Cart_BAO_Cart
   */
  public static function find_uncompleted_by_user_id($user_id) {
    return self::find_by_params(array('user_id' => $user_id, 'completed' => 0));
  }

  /**
   * @return array
   */
  public function get_main_events_in_carts() {
    //return CRM_Event_Cart_BAO_EventInCart::find_all_by_params( array('main_conference_event_id'
    $all = array();
    foreach ($this->events_in_carts as $event_in_cart) {
      if (!$event_in_cart->is_child_event()) {
        $all[] = $event_in_cart;
      }
    }
    return $all;
  }

  /**
   * @param int $main_conference_event_id
   *
   * @return array
   */
  public function get_events_in_carts_by_main_event_id($main_conference_event_id) {
    $all = array();
    if (!$main_conference_event_id) {
      return $all;
    }
    foreach ($this->events_in_carts as $event_in_cart) {
      if ($event_in_cart->event->parent_event_id == $main_conference_event_id) {
        $all[] = $event_in_cart;
      }
    }
    usort($all, "CRM_Event_Cart_BAO_Cart::compare_event_dates");
    return $all;
  }

  /**
   * @param $event_in_cart_1
   * @param $event_in_cart_2
   *
   * @return int
   */
  public static function compare_event_dates($event_in_cart_1, $event_in_cart_2) {
    $date_1 = CRM_Utils_Date::unixTime($event_in_cart_1->event->start_date);
    $date_2 = CRM_Utils_Date::unixTime($event_in_cart_2->event->start_date);

    if ($date_1 == $date_2) {

      return 0;

    }
    return ($date_1 < $date_2) ? -1 : 1;
  }

  /**
   * @param $main_participant
   *
   * @return array
   */
  public function get_subparticipants($main_participant) {
    $subparticipants = array();
    foreach ($this->events_in_carts as $event_in_cart) {
      if ($event_in_cart->is_child_event($main_participant->event_id)) {
        foreach ($event_in_cart->participants as $participant) {
          if ($participant->contact_id == $main_participant->contact_id) {
            $subparticipants[] = $participant;
            continue;
          }
        }
      }
    }
    return $subparticipants;
  }

  /**
   * @param int $event_id
   *
   * @return mixed
   */
  public function get_event_in_cart_by_event_id($event_id) {
    return CRM_Utils_Array::value($event_id, $this->events_in_carts);
  }

  /**
   * @param int $event_in_cart_id
   *
   * @return null
   */
  public function &get_event_in_cart_by_id($event_in_cart_id) {
    foreach ($this->events_in_carts as $event_in_cart) {
      if ($event_in_cart->id == $event_in_cart_id) {
        return $event_in_cart;
      }
    }
    return NULL;
  }

  /**
   * @return array
   */
  public function get_main_event_participants() {
    $participants = array();
    foreach ($this->get_main_events_in_carts() as $event_in_cart) {
      $participants = array_merge($participants, $event_in_cart->participants);
    }
    return $participants;
  }

  public function load_associations() {
    if ($this->associations_loaded) {
      return;
    }
    $this->associations_loaded = TRUE;
    $this->events_in_carts = CRM_Event_Cart_BAO_EventInCart::find_all_by_event_cart_id($this->id);
    foreach ($this->events_in_carts as $event_in_cart) {
      $event_in_cart->load_associations($this);
    }
    $this->save();
  }

  /**
   * @param int $event_in_cart_id
   *
   * @return bool|CRM_Event_Cart_BAO_EventInCart
   */
  public function remove_event_in_cart($event_in_cart_id) {
    $event_in_cart = CRM_Event_Cart_BAO_EventInCart::find_by_id($event_in_cart_id);
    if ($event_in_cart) {
      $sessions_to_remove = $this->get_events_in_carts_by_main_event_id($event_in_cart->event_id);
      foreach ($sessions_to_remove as $session) {
        $this->remove_event_in_cart($session->id);
      }
      unset($this->events_in_carts[$event_in_cart->event_id]);
      $event_in_cart->delete();
    }
    return $event_in_cart;
  }

  /**
   * @param int $participant_id
   *
   * @return int
   */
  public function get_participant_index_from_id($participant_id) {
    foreach ($this->events_in_carts as $event_in_cart) {
      $index = 0;
      foreach ($event_in_cart->participants as $participant) {
        if ($participant->id == $participant_id) {
          return $index;
        }
        $index++;
      }
    }
    return -1;
  }

  /**
   * @param array $params
   * @param $values
   *
   * @return mixed
   * @throws Exception
   */
  public static function retrieve(&$params, &$values) {
    $cart = self::find_by_params($params);
    if ($cart === FALSE) {
      CRM_Core_Error::fatal(ts('Could not find cart matching %1', array(1 => var_export($params, TRUE))));
    }
    CRM_Core_DAO::storeValues($cart, $values);
    return $values;
  }


  /**
   * @param int $from_cart_id
   */
  public function adopt_participants($from_cart_id) {
    $params = array(
      1 => array($this->id, 'Integer'),
      2 => array($from_cart_id, 'Integer'),
    );
    $sql = "UPDATE civicrm_participant SET cart_id='%1' WHERE cart_id='%2'";

    CRM_Core_DAO::executeQuery($sql, $params);
  }

}
