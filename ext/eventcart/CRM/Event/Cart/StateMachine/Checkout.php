<?php

/**
 * Class CRM_Event_Cart_StateMachine_Checkout
 */
class CRM_Event_Cart_StateMachine_Checkout extends CRM_Core_StateMachine {

  /**
   * @param object $controller
   * @param const|int $action
   */
  public function __construct($controller, $action = CRM_Core_Action::NONE) {
    parent::__construct($controller, $action);

    $cart = CRM_Event_Cart_BAO_Cart::find_or_create_for_current_session();
    $cart->load_associations();
    if ($cart->is_empty()) {
      CRM_Core_Error::statusBounce(ts("You don't have any events in you cart. Please add some events."), CRM_Utils_System::url('civicrm/event'));
    }

    $pages = [];
    $pages['CRM_Event_Cart_Form_Checkout_ParticipantsAndPrices'] = NULL;
    foreach ($cart->events_in_carts as $event_in_cart) {
      /* @var \CRM_Event_Cart_BAO_EventInCart $event_in_cart */
      if ($event_in_cart->is_parent_event()) {
        foreach ($event_in_cart->participants as $participant) {
          $pages["CRM_Event_Cart_Form_Checkout_ConferenceEvents_{$event_in_cart->event_id}_{$participant->id}"] = [
            'className' => 'CRM_Event_Cart_Form_Checkout_ConferenceEvents',
            'title' => "Select {$event_in_cart->event->title} Events For {$participant->email}",
          ];
        }
      }
    }
    $pages["CRM_Event_Cart_Form_Checkout_Payment"] = NULL;
    $pages["CRM_Event_Cart_Form_Checkout_ThankYou"] = NULL;
    $this->addSequentialPages($pages);
  }

}
