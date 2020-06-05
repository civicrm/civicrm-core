<?php

/**
 * Class CRM_Event_Cart_Form_Checkout_Payment
 */
class CRM_Event_Cart_Form_Checkout_Payment extends CRM_Event_Cart_Form_Cart {
  use CRM_Financial_Form_FrontEndPaymentFormTrait;

  public $all_participants;
  public $financial_type_id;
  public $description;
  public $line_items;
  public $_fields = [];
  public $_paymentProcessor;
  public $total;
  public $sub_total;
  public $payer_contact_id;
  public $pay_later_receipt;

  /**
   * @var \Civi\Payment\PropertyBag
   */
  protected $paymentPropertyBag;

  /**
   * @var array
   */
  protected $_values = [];

  /**
   * Build QuickForm.
   */
  public function buildQuickForm() {
    $buttons = [
      [
        'name' => ts('Go Back'),
        'type' => 'back',
      ],
      [
        'isDefault' => TRUE,
        'name' => ts('Complete Transaction'),
        'type' => 'next',
      ]
    ];

    // @todo we should replace this with an optional profile
    $this->add('text', 'first_name', 'First Name', '', TRUE);
    $this->add('text', 'last_name', 'Last Name', '', TRUE);
    $this->add('text', 'email', 'Billing Email', '', TRUE);

    $this->addButtons($buttons);
    $this->addFormRule(['CRM_Event_Cart_Form_Checkout_Payment', 'formRule'], $this);

    if ($this->isPaymentRequired()) {
      CRM_Core_Payment_ProcessorForm::buildQuickForm($this);
      $this->addPaymentProcessorFieldsToForm();
    }

    // Add reCAPTCHA
    if (count($this->_paymentProcessors) >= 1) {
      if (!$this->getLoggedInUserContactID()) {
        CRM_Utils_ReCAPTCHA::enableCaptchaOnForm($this);
      }
    }
  }

  /**
   * Process line item for event.
   *
   * @param bool $event_in_cart
   * @param string $class
   */
  public function process_event_line_item(&$event_in_cart, $class = NULL) {
    $cost = 0;
    $price_set_id = CRM_Price_BAO_PriceSet::getFor("civicrm_event", $event_in_cart->event_id);
    $amount_level = NULL;
    if ($price_set_id) {
      $event_price_values = [];
      foreach ($this->_price_values as $key => $value) {
        if (preg_match("/event_{$event_in_cart->event_id}_(price.*)/", $key, $matches)) {
          $event_price_values[$matches[1]] = $value;
        }
      }
      $price_sets = CRM_Price_BAO_PriceSet::getSetDetail($price_set_id, TRUE);
      $price_set = $price_sets[$price_set_id];
      $price_set_amount = [];
      CRM_Price_BAO_PriceSet::processAmount($price_set['fields'], $event_price_values, $price_set_amount);
      if (!empty($this->_price_values['discountcode'])) {
        $ret = $this->apply_discount($this->_price_values['discountcode'], $price_set_amount, $cost, $event_in_cart->event_id);
        if ($ret == FALSE) {
          $cost = $event_price_values['amount'];
        }
      }
      else {
        $cost = $event_price_values['amount'];
      }
      // @todo - stop setting amount level in this function & call the CRM_Price_BAO_PriceSet::getAmountLevel
      // function to get correct amount level consistently. Remove setting of the amount level in
      // CRM_Price_BAO_PriceSet::processAmount. Extend the unit tests in CRM_Price_BAO_PriceSetTest
      // to cover all variants.
      $amount_level = $event_price_values['amount_level'];
      $price_details[$price_set_id] = $price_set_amount;
    }

    // iterate over each participant in event
    foreach ($event_in_cart->participants as & $participant) {
      $participant->cost = $cost;
      $participant->fee_level = $amount_level;
      $participant->price_details = $price_details;
    }

    $this->add_line_item($event_in_cart, $class);
  }

  /**
   * Add line item.
   *
   * @param CRM_Event_BAO_Event $event_in_cart
   * @param string $class
   */
  public function add_line_item($event_in_cart, $class = NULL) {
    $amount = 0;
    $cost = 0;
    $not_waiting_participants = [];
    foreach ($event_in_cart->not_waiting_participants() as $participant) {
      $amount += $participant->cost;
      $cost = max($cost, $participant->cost);
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
    $this->line_items[] = [
      'amount' => $amount,
      'cost' => $cost,
      'event' => $event_in_cart->event,
      'participants' => $not_waiting_participants,
      'num_participants' => count($not_waiting_participants),
      'num_waiting_participants' => count($waiting_participants),
      'waiting_participants' => $waiting_participants,
      'class' => $class,
    ];

    $this->sub_total += $amount;
  }

  /**
   * Send email receipt.
   * @fixme: Check if this works and does what we want
   *
   * @param array $events_in_cart
   * @param array $params
   */
  public function emailReceipt($events_in_cart, $params) {
    $contact_details = CRM_Contact_BAO_Contact::getContactDetails($this->payer_contact_id);
    $state_province = new CRM_Core_DAO_StateProvince();
    $state_province->id = $params["billing_state_province_id-{$this->_bltID}"];
    $state_province->find();
    $state_province->fetch();
    $country = new CRM_Core_DAO_Country();
    $country->id = $params["billing_country_id-{$this->_bltID}"];
    $country->find();
    $country->fetch();
    foreach ($this->line_items as & $line_item) {
      $location_params = ['entity_id' => $line_item['event']->id, 'entity_table' => 'civicrm_event'];
      $line_item['location'] = CRM_Core_BAO_Location::getValues($location_params, TRUE);
      CRM_Core_BAO_Address::fixAddress($line_item['location']['address'][1]);
    }
    $send_template_params = [
      'table' => 'civicrm_msg_template',
      'contactId' => $this->payer_contact_id,
      'from' => current(CRM_Core_BAO_Domain::getNameAndEmail(TRUE, TRUE)),
      'groupName' => 'msg_tpl_workflow_event',
      'isTest' => FALSE,
      'toEmail' => $contact_details[1],
      'toName' => $contact_details[0],
      'tplParams' => [
        'billing_name' => "{$params['billing_first_name']} {$params['billing_last_name']}",
        'billing_city' => $params["billing_city-{$this->_bltID}"],
        'billing_country' => $country->name,
        'billing_postal_code' => $params["billing_postal_code-{$this->_bltID}"],
        'billing_state' => $state_province->abbreviation,
        'billing_street_address' => $params["billing_street_address-{$this->_bltID}"],
        'credit_card_exp_date' => $params['credit_card_exp_date'],
        'credit_card_type' => $params['credit_card_type'],
        'credit_card_number' => "************" . substr($params['credit_card_number'], -4, 4),
        // XXX cart->get_discounts
        'discounts' => $this->discounts,
        'email' => $contact_details[1],
        'events_in_cart' => $events_in_cart,
        'line_items' => $this->line_items,
        'name' => $contact_details[0],
        'transaction_id' => $params['trxn_id'],
        'transaction_date' => $params['trxn_date'],
        'is_pay_later' => $this->isPayLater(),
        'pay_later_receipt' => $this->pay_later_receipt,
      ],
      'valueName' => 'event_registration_receipt',
      'PDFFilename' => ts('confirmation') . '.pdf',
    ];
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
      $this->set($template_param_to_copy, $send_template_params['tplParams'][$template_param_to_copy]);
    }

    CRM_Core_BAO_MessageTemplate::sendTemplate($send_template_params);
  }

  /**
   * Apply form rules.
   *
   * @param array $fields
   * @param array $files
   * @param CRM_Core_Form $form
   *
   * @return array|bool
   */
  public static function formRule($fields, $files, $form) {
    $errors = [];

    if ($form->isPaymentRequired() && empty($form->_submitValues['is_pay_later'])) {
      CRM_Core_Form::validateMandatoryFields($form->_fields, $fields, $errors);

      // validate payment instrument values (e.g. credit card number)
      CRM_Core_Payment_Form::validatePaymentInstrument($form->_paymentProcessor['id'], $fields, $errors, NULL);
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Pre-process form.
   */
  public function preProcess() {
    parent::preProcess();

    $this->paymentPropertyBag = new \Civi\Payment\PropertyBag();
    $this->setPaymentMode();

    // Setup payment processors if required
    if ($this->isPaymentRequired()) {
      $this->_paymentProcessorIDs = \Civi::settings()->get('eventcart_payment_processors');
      $this->setIsPayLater(\Civi::settings()->get('eventcart_paylater'));
      if ($this->isPayLater()) {
        $this->setPayLaterLabel(\Civi::settings()->get('eventcart_paylater_text'));
      }
      $this->assignPaymentProcessor($this->isPayLater());
    }
  }

  /**
   * Post process form.
   */
  public function postProcess() {
    $trxnDetails = NULL;
    $params = $this->_submitValues;
    //$transaction = new CRM_Core_Transaction();

    $contactID = $this->getContactID();
    $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'contact_type');
    $params = $this->prepareParamsForPaymentProcessor($params);
    foreach($params as $key => $value) {
      $fields[$key] = 1;
    }
    CRM_Contact_BAO_Contact::createProfileContact(
      $params,
      $fields,
      $this->getContactID(),
      NULL,
      NULL,
      $ctype,
      TRUE
    );
    $params['contact_id'] = $contactID;

    $params['now'] = date('YmdHis');

    $params['financial_type_id'] = $this->financial_type_id;
    if ($this->isPaymentRequired() && empty($params['is_pay_later'])) {
      $order = $this->createOrder($params)['id'];
      $this->paymentPropertyBag->setContactID($contactID);
      $this->paymentPropertyBag->setContributionID($order['id']);
      $this->paymentPropertyBag->mergeLegacyInputParams($params);

      $payment = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
      try {
        $result = $payment->doPayment($this->paymentPropertyBag);
      }
      catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
        Civi::log()->error('Payment processor exception: ' . $e->getMessage());
        CRM_Core_Session::singleton()->setStatus($e->getMessage());
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/event/cart_checkout', "_qf_Payment_display=1&qfKey={$this->controller->_key}", TRUE, NULL, FALSE));
      }

      $params['trxn_id'] = $result['trxn_id'];
      $params['trxn_date'] = $result['now'];
    }

    $this->cart->completed = TRUE;
    $this->cart->save();
    $this->set('last_event_cart_id', $this->cart->id);

    $contribution_statuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    if ($this->isPayLater() && empty($params['payment_completed'])) {
      $params['contribution_status_id'] = array_search('Pending', $contribution_statuses);
    }
    else {
      $params['contribution_status_id'] = array_search('Completed', $contribution_statuses);
      $params['participant_status'] = 'Registered';
      $params['is_pay_later'] = 0;
    }

    if ($this->isPaymentRequired()) {
      // @fixme This is probably not getting the right params, and who is it sending to?
      $this->emailReceipt($this->cart->events_in_carts, $params);
    }

    $this->trxn_id = $params['trxn_id'];
    $this->trxn_date = $params['trxn_date'];
    $this->saveDataToSession();
    $transaction->commit();
  }

  private function createOrder($params) {
    $params['invoiceID'] = md5(uniqid(rand(), TRUE));

    // n.b. we need to process the subparticipants before main event
    // participants so that session attendance can be included in the email
    $main_participants = $this->cart->get_main_event_participants();
    $this->all_participants = [];
    foreach ($main_participants as $main_participant) {
      $this->all_participants = array_merge($this->all_participants, $this->cart->get_subparticipants($main_participant));
    }
    $this->all_participants = array_merge($this->all_participants, $main_participants);

    foreach ($this->all_participants as $mer_participant) {
      $event_in_cart = $this->cart->get_event_in_cart_by_event_id($mer_participant->event_id);

      $lineItem['params'] = [
        'event_id' => $event_in_cart->event_id,
        'contact_id' => $mer_participant->contact_id,
        'role_id' => $mer_participant->role_id,
      ];

      // @todo maybe handle waitList: $mer_participant->must_wait
      foreach ($mer_participant->price_details as $priceSetID => $priceSetFieldValues) {
        foreach ($priceSetFieldValues as $priceSetFieldValueID => $priceSetFieldValueDetail) {
          $lineItem['line_item'][] = $priceSetFieldValueDetail;
        }
      }
      $lineItems[] = $lineItem;
    }

    $orderParams = [
      'contact_id' => $this->getContactID(),
      'total_amount' => $this->total,
      'financial_type_id' => 'Event fee',
      'contribution_status_id' => 'Pending',
      'line_items' => $lineItems,
    ];
    $result = civicrm_api3('Order', 'create', $orderParams);
    return reset($result['values']);
  }

  /**
   * Save data to session.
   */
  public function saveDataToSession() {
    $session_line_items = [];
    foreach ($this->line_items as $line_item) {
      $session_line_item = [];
      $session_line_item['amount'] = $line_item['amount'];
      $session_line_item['cost'] = $line_item['cost'];
      $session_line_item['event_id'] = $line_item['event']->id;
      $session_line_items[] = $session_line_item;
    }
    $this->set('line_items', $session_line_items);
    $this->set('payment_required', $this->isPaymentRequired());
    $this->set('is_pay_later', $this->isPayLater());
    $this->set('pay_later_receipt', $this->pay_later_receipt);
    $this->set('trxn_id', $this->trxn_id);
    $this->set('trxn_date', $this->trxn_date);
    $this->set('total', $this->total);
  }

  /**
   * Set form default values.
   *
   * @return array
   */
  public function setDefaultValues() {
    $contactID = $this->getContactID();
    CRM_Core_Payment_Form::setDefaultValues($this, $contactID);

    // @todo replace this with a profile
    $contact = \Civi\Api4\Contact::get()
      ->addSelect('first_name', 'last_name')
      ->addWhere('id', '=', $contactID)
      ->execute()
      ->first();
    $this->_defaults['first_name'] = $contact['first_name'] ?? '';
    $this->_defaults['last_name'] = $contact['last_name'] ?? '';
    $email = \Civi\Api4\Email::get()
      ->addSelect('email')
      ->addWhere('contact_id', '=', $contactID)
      ->addOrderBy('is_billing', 'DESC')
      ->addOrderBy('is_primary', 'DESC')
      ->execute()
      ->first();
    $this->_defaults['email'] = $email['email'] ?? '';

    return $this->_defaults;
  }

  /**
   * Apply discount.
   * @fixme Check this still works!
   *
   * @param string $discountCode
   * @param array $price_set_amount
   * @param float $cost
   * @param int $event_id
   *
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  protected function apply_discount($discountCode, &$price_set_amount, &$cost, $event_id) {
    $extensions = civicrm_api3('Extension', 'get', [
      'full_name' => 'org.civicrm.module.cividiscount',
    ]);
    if (empty($extensions['id']) || ($extensions['values'][$extensions['id']]['status'] !== 'installed')) {
      return FALSE;
    }

    $autoDiscount = [];

    $discounted_priceset_ids = _cividiscount_get_discounted_priceset_ids();
    $discounts = _cividiscount_get_discounts();

    $stat = FALSE;
    foreach ($discounts as $key => $discountValue) {
      if ($key == $discountCode) {
        $events = $discountValue['events'] ?? NULL;
        $evt_ids = implode(",", $events);
        if ($evt_ids == "0" || strpos($evt_ids, $event_id)) {
          $event_match = TRUE;
        }
        //check priceset is_active
        if ($discountValue['active_on'] != NULL) {
          $today = date('Y-m-d');
          $diff1 = date_diff(date_create($today), date_create($discountValue['active_on']));
          if ($diff1->days > 0) {
            $active1 = TRUE;
          }
        }
        else {
          $active1 = TRUE;
        }
        if ($discountValue['expire_on'] != NULL) {
          $diff2 = date_diff(date_create($today), date_create($discountValue['expire_on']));
          if ($diff2->days > 0) {
            $active2 = TRUE;
          }
        }
        else {
          $active2 = TRUE;
        }
      }
      if ($discountValue['is_active'] == TRUE && ($discountValue['count_max'] == 0 || ($discountValue['count_max'] > $discountValue['count_use'])) && $active1 == TRUE && $active2 == TRUE && $event_match == TRUE) {
        foreach ($price_set_amount as $key => $price) {
          if (array_search($price['price_field_value_id'], $discounted_priceset_ids) != NULL) {
            $discounted = _cividiscount_calc_discount($price['line_total'], $price['label'], $discountValue, $autoDiscount, $this->getCurrency());
            $price_set_amount[$key]['line_total'] = $discounted[0];
            $cost += $discounted[0];
            $price_set_amount[$key]['label'] = $discounted[1];
          }
          else {
            $cost += $price['line_total'];
          }
        }
        $stat = TRUE;
      }
    }
    return $stat;
  }

  public function isPaymentRequired() {
    if (!isset(\Civi::$statics[__CLASS__]['is_payment_required'])) {
      $this->setPaymentParameters();
      \Civi::$statics[__CLASS__]['is_payment_required'] = ($this->total > 0 ? TRUE : FALSE);
      $this->assign('payment_required', \Civi::$statics[__CLASS__]['is_payment_required']);
    }
    return \Civi::$statics[__CLASS__]['is_payment_required'];
  }

  private function setPaymentParameters() {
    $this->line_items = [];
    $this->sub_total = 0;
    $this->_price_values = $this->getValuesForPage('ParticipantsAndPrices');

    // iterate over each event in cart
    foreach ($this->cart->get_main_events_in_carts() as $event_in_cart) {
      $this->process_event_line_item($event_in_cart);
      foreach ($this->cart->get_events_in_carts_by_main_event_id($event_in_cart->event_id) as $subevent) {
        $this->process_event_line_item($subevent, 'subevent');
      }
    }

    $this->total = $this->sub_total;
    $this->assign('line_items', $this->line_items);
    $this->assign('sub_total', $this->sub_total);
    $this->assign('total', $this->total);

    // @fixme: This will always use the currency of the "last" event.
    //   That's probably ok because they should all use the same currency.
    $this->assign('currency', $event_in_cart->event->currency);
    $this->paymentPropertyBag->setCurrency($event_in_cart->event->currency);
    $this->paymentPropertyBag->setAmount($this->total);
  }

}
