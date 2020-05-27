<?php

/**
 * Class CRM_Event_Cart_Page_AddToCart
 */
class CRM_Event_Cart_Page_AddToCart extends CRM_Core_Page {

  use CRM_Core_Page_EntityPageTrait;

  /**
   * This function takes care of all the things common to all pages.
   *
   * This typically involves assigning the appropriate smarty variables :)
   */
  public function run() {
    $this->_id = CRM_Utils_Request::retrieveValue('id', 'Positive', NULL);
    if (empty($this->_id)) {
      CRM_Core_Error::statusBounce(ts('Missing required parameters'), NULL, ts('Add to cart'));
    }
    if (!CRM_Core_Permission::event(CRM_Core_Permission::VIEW, $this->_id, 'register for events')) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to register for this event'));
    }

    $transaction = new CRM_Core_Transaction();
    $cart = CRM_Event_Cart_BAO_Cart::find_or_create_for_current_session();
    $event_in_cart = $cart->add_event($this->_id);
    $transaction->commit();

    $cartUrl = CRM_Utils_System::url('civicrm/event/view_cart');
    CRM_Utils_System::setUFMessage(ts("<b>%1</b> has been added to your <a href='%2'>cart</a>.", [
      1 => $event_in_cart->event->title,
      2 => $cartUrl,
    ]));

    CRM_Utils_System::redirect($_SERVER['HTTP_REFERER']);
  }

}
