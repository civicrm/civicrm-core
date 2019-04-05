<?php

/**
 * Class CRM_Event_Cart_Page_AddToCart
 */
class CRM_Event_Cart_Page_AddToCart extends CRM_Core_Page {
  /**
   * This function takes care of all the things common to all pages.
   *
   * This typically involves assigning the appropriate smarty variables :)
   */
  public function run() {
    $transaction = new CRM_Core_Transaction();

    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    if (!CRM_Core_Permission::event(CRM_Core_Permission::VIEW, $this->_id, 'register for events')) {
      CRM_Core_Error::fatal(ts('You do not have permission to register for this event'));
    }

    $cart = CRM_Event_Cart_BAO_Cart::find_or_create_for_current_session();
    $event_in_cart = $cart->add_event($this->_id);

    $url = CRM_Utils_System::url('civicrm/event/view_cart');
    CRM_Utils_System::setUFMessage(ts("<b>%1</b> has been added to your cart. <a href='%2'>View your cart.</a>", [
          1 => $event_in_cart->event->title,
          2 => $url,
        ]));

    $transaction->commit();

    return CRM_Utils_System::redirect($_SERVER['HTTP_REFERER']);
  }

}
