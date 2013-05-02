<?php
class CRM_Event_Cart_Page_ViewCart extends CRM_Core_Page {
  function run() {
    $transaction = new CRM_Core_Transaction();

    $cart = CRM_Event_Cart_BAO_Cart::find_or_create_for_current_session();
    $cart->load_associations();
    $this->assign('events_in_carts', $cart->get_main_events_in_carts());
    $this->assign('events_count', count($cart->get_main_events_in_carts()));

    $transaction->commit();

    return parent::run();
  }
}



