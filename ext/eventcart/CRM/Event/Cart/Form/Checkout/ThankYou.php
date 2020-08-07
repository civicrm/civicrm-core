<?php

use Civi\Api4\Contribution;

/**
 * Class CRM_Event_Cart_Form_Checkout_ThankYou
 */
class CRM_Event_Cart_Form_Checkout_ThankYou extends CRM_Event_Cart_Form_Cart {
  public $line_items = NULL;
  public $sub_total = 0;

  /**
   * @var int
   */
  private $contributionID;

  public function buildLineItems() {
    $lineItems = [];
    if ($this->contributionID) {
      $lineItems = \Civi\Api4\LineItem::get()
        ->addWhere('contribution_id', '=', $this->contributionID)
        ->execute()
        ->indexBy('entity_id');
    }
    foreach ($this->cart->events_in_carts as $event_in_cart) {
      $event_in_cart->load_location();

      $not_waiting_participants = [];
      foreach ($event_in_cart->not_waiting_participants() as $participant) {
        $not_waiting_participants[$participant->id] = [
          'display_name' => CRM_Contact_BAO_Contact::displayName($participant->contact_id),
        ];
      }
      $waiting_participants = [];
      foreach ($event_in_cart->waiting_participants() as $participant) {
        $waiting_participants[$participant->id] = [
          'display_name' => CRM_Contact_BAO_Contact::displayName($participant->contact_id),
        ];
      }

      $lineItemForDisplay['event'] = $event_in_cart->event;
      $lineItemForDisplay['num_participants'] = count($not_waiting_participants);
      $lineItemForDisplay['participants'] = $not_waiting_participants;
      $lineItemForDisplay['num_waiting_participants'] = count($waiting_participants);
      $lineItemForDisplay['waiting_participants'] = $waiting_participants;
      $lineItemForDisplay['location'] = $event_in_cart->location;
      $lineItemForDisplay['class'] = $event_in_cart->event->parent_event_id ? 'subevent' : NULL;
      $lineItemForDisplay['line_total'] = 0;
      foreach ($not_waiting_participants as $participantID => $_) {
        $lineItemForDisplay['line_total'] += $lineItems[$participantID]['line_total'] ?? 0;
        $lineItemForDisplay['unit_price'] = $lineItems[$participantID]['unit_price'] ?? 0;
      }
      $this->sub_total += $lineItemForDisplay['line_total'];
      $this->line_items[] = $lineItemForDisplay;
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
      $currency = Contribution::get(FALSE)
        ->addWhere('id', '=', $this->contributionID)
        ->execute()
        ->first()['currency'];
    }
    $this->assign('currency', $currency ?? CRM_Core_Config::singleton()->defaultCurrency);
    $this->assign('sub_total', $this->sub_total);
    $this->assign('total', $this->get('total') ?? $this->sub_total);
  }

  public function preProcess() {
    $session = CRM_Core_Session::singleton();
    $this->event_cart_id = $session->get('last_event_cart_id');
    $this->contributionID = $session->get('contributionID');
    $this->loadCart();
  }

}
