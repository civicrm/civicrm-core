<?php

/**
 * Class CRM_Event_Cart_Form_Checkout_ThankYou
 */
class CRM_Event_Cart_Form_Checkout_ThankYou extends CRM_Event_Cart_Form_Cart {
  public $line_items = NULL;
  public $sub_total = 0;

  public function buildLineItems() {
    if (empty($this->contributionID)) {
      return;
    }

    $line_items = civicrm_api3('LineItem', 'get', [
      'contribution_id' => $this->contributionID,
    ])['values'];
    foreach ($this->cart->events_in_carts as $event_in_cart) {
      $event_in_cart->load_location();

      $not_waiting_participants = [];
      foreach ($event_in_cart->not_waiting_participants() as $participant) {
        $not_waiting_participants[] = [
          'display_name' => CRM_Contact_BAO_Contact::displayName($participant->contact_id),
        ];
      }
      $waiting_participants = [];
      foreach ($event_in_cart->waiting_participants() as $participant) {
        $waiting_participants[] = [
          'display_name' => CRM_Contact_BAO_Contact::displayName($participant->contact_id),
        ];
      }

      $found = FALSE;
      foreach ($line_items as $lineItemID => $line_item) {
        if ($line_item['entity_id'] == $participant->id) {
          $found = TRUE;
          break;
        }
      }
      if (!$found) {
        continue;
      }

      $line_item['event'] = $event_in_cart->event;
      $line_item['num_participants'] = count($not_waiting_participants);
      $line_item['participants'] = $not_waiting_participants;
      $line_item['num_waiting_participants'] = count($waiting_participants);
      $line_item['waiting_participants'] = $waiting_participants;
      $line_item['location'] = $event_in_cart->location;
      $line_item['class'] = $event_in_cart->event->parent_event_id ? 'subevent' : NULL;

      $this->sub_total += $line_item['line_total'];
      $this->line_items[] = $line_item;
    }
    $this->assign('line_items', $this->line_items);
  }

  public function buildQuickForm() {
    $template_params_to_copy = [
      'billing_name',
      'billing_city',
      'billing_country',
      'billing_postal_code',
      'billing_state',
      'billing_street_address',
      'credit_card_exp_date',
      'credit_card_type',
      'credit_card_number',
    ];
    foreach ($template_params_to_copy as $template_param_to_copy) {
      $this->assign($template_param_to_copy, $this->get($template_param_to_copy));
    }
    $this->buildLineItems();
    $this->assign('discounts', $this->get('discounts'));
    $this->assign('events_in_carts', $this->cart->events_in_carts);
    $this->assign('transaction_id', $this->get('trxn_id'));
    $this->assign('transaction_date', $this->get('trxn_date'));
    $this->assign('payment_required', $this->get('payment_required'));
    $this->assign('is_pay_later', $this->get('is_pay_later'));
    $this->assign('pay_later_receipt', $this->get('pay_later_receipt'));
    if (!empty($this->contributionID)) {
      $this->assign('currency', civicrm_api3('Contribution', 'getvalue', ['id' => $this->contributionID, 'return' => 'currency']));
      $this->assign('sub_total', $this->sub_total);
      $this->assign('total', $this->get('total') ?? $this->sub_total);
    }
  }

  public function preProcess() {
    $session = CRM_Core_Session::singleton();
    $this->event_cart_id = $session->get('last_event_cart_id');
    $this->contributionID = $session->get('contributionID');
    $this->loadCart();
  }

}
