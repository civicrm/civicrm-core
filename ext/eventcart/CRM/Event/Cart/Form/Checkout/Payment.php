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
    // @todo we should replace this with an optional profile
    $this->add('text', 'first_name', 'First Name', '', TRUE);
    $this->add('text', 'last_name', 'Last Name', '', TRUE);
    $this->add('text', 'email', 'Billing Email', '', TRUE);

    $this->addFormRule(['CRM_Event_Cart_Form_Checkout_Payment', 'formRule'], $this);
    $this->addButtons([
      [
        'name' => ts('Go Back'),
        'type' => 'back',
      ],
      [
        'isDefault' => TRUE,
        'name' => ts('Complete Transaction'),
        'type' => 'next',
      ],
    ]);

    if ($this->isPaymentRequired()) {
      CRM_Core_Payment_ProcessorForm::buildQuickForm($this);
      $this->addPaymentProcessorFieldsToForm();
    }

    // Add reCAPTCHA
    if (!CRM_Core_Session::getLoggedInContactID()) {
      CRM_Utils_ReCAPTCHA::enableCaptchaOnForm($this);
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
    $price_details = [];
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
    CRM_Core_Session::singleton()->replaceUserContext(
      CRM_Utils_System::url('civicrm/event/cart_checkout', [
        'reset' => 1,
        'qf_Payment_display' => 1,
      ])
    );
    parent::preProcess();

    $this->paymentPropertyBag = new \Civi\Payment\PropertyBag();
    $this->setPaymentMode();

    // Setup payment processors if required
    if ($this->isPaymentRequired()) {
      $this->_paymentProcessorIDs = \Civi::settings()->get('eventcart_payment_processors');
      $this->setIsPayLater(\Civi::settings()->get('eventcart_paylater'));
      if ($this->isPayLater()) {
        $this->setPayLaterLabel(\Civi::settings()->get('eventcart_paylater_text'));
        // Add the pay later processor to the list of available processor IDs
        $this->_paymentProcessorIDs[] = 0;
      }

      $this->assignPaymentProcessor($this->isPayLater());
      // This is required to set the "default" payment processor for the form.
      // Otherwise the first (default) processor will not load correctly on the page.
      if (isset($this->_submitValues['payment_processor_id'])) {
        $this->_paymentProcessor = $this->_paymentProcessors[$this->_submitValues['payment_processor_id']];
      }
      else {
        $this->_paymentProcessor = reset($this->_paymentProcessors);
      }
      $this->_defaults['payment_processor_id'] = $this->_paymentProcessor['id'];
    }
  }

  /**
   * Post process form.
   */
  public function postProcess() {
    $params = $this->_submitValues;
    $transaction = new CRM_Core_Transaction();

    $contactID = $this->getContactID();
    $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'contact_type');

    $fields = [];

    $fields['email-Primary'] = 1;
    $this->_params = $params;
    // now set the values for the billing location.
    foreach (array_keys($this->_fields) as $name) {
      $fields[$name] = 1;
    }

    $fields["address_name-{$this->_bltID}"] = 1;

    list($hasBillingField, $addressParams) = CRM_Contribute_BAO_Contribution::getPaymentProcessorReadyAddressParams($this->_params, $this->_bltID);
    $fields = $this->formatParamsForPaymentProcessor($fields);

    if ($hasBillingField) {
      $addressParams = array_merge($this->_params, $addressParams);
      // CRM-18277 don't let this get passed in because we don't want contribution source to override contact source.
      // Ideally we wouldn't just randomly merge everything into addressParams but just pass in a relevant array.
      // Note this source field is covered by a unit test.
      if (isset($addressParams['source'])) {
        unset($addressParams['source']);
      }
      //here we are setting up the billing contact - if different from the member they are already created
      // but they will get billing details assigned
      CRM_Contact_BAO_Contact::createProfileContact(
        $params,
        $fields,
        $contactID,
        NULL,
        NULL,
        $ctype
      );
    }

    $params['contact_id'] = $contactID;
    $params['now'] = date('YmdHis');
    $params['currency'] = $this->paymentPropertyBag->getCurrency();

    if ($this->isPaymentRequired() && empty($params['is_pay_later'])) {
      $contributionID = $this->createOrder($params)['id'];
      $this->paymentPropertyBag->setContactID($contactID);
      $this->paymentPropertyBag->setContributionID($contributionID);
      $this->paymentPropertyBag->mergeLegacyInputParams($params);

      $payment = Civi\Payment\System::singleton()->getById($params['payment_processor_id']);
      try {
        $result = $payment->doPayment($this->paymentPropertyBag);
        $completedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
        if ($result['payment_status_id'] == $completedStatusId) {
          civicrm_api3('Payment', 'create', [
            'contribution_id' => $this->paymentPropertyBag->getContributionID(),
            'total_amount' => $this->paymentPropertyBag->getAmount(),
            'trxn_date' => $result['now'],
            'is_send_contribution_notification' => 1,
            'fee_amount' => $result['fee_amount'] ?? 0,
            'payment_processor_id' => $this->_paymentProcessor['id'],
          ]);
        }
      }
      catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
        Civi::log()->error('Payment processor exception: ' . $e->getMessage());
        CRM_Core_Session::singleton()->setStatus($e->getMessage());
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/event/cart_checkout', "_qf_Payment_display=1&qfKey={$this->controller->_key}", TRUE, NULL, FALSE));
      }
    }

    $this->cart->completed = TRUE;
    $this->cart->save();
    $transaction->commit();

    $session = CRM_Core_Session::singleton();
    $session->set('last_event_cart_id', $this->cart->id);
    $session->set('contributionID', $contributionID ?? NULL);
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

      $lineItem = [];
      $lineItem['params'] = [
        'event_id' => $event_in_cart->event_id,
        'contact_id' => $mer_participant->contact_id,
        'role_id' => $mer_participant->role_id,
        'participant_id' => $mer_participant->id,
        'participant_status_id' => 'Pending in cart',
      ];

      // @todo maybe handle waitList: $mer_participant->must_wait
      foreach ($mer_participant->price_details as $priceSetID => $priceSetFieldValues) {
        foreach ($priceSetFieldValues as $priceSetFieldValueID => $priceSetFieldValueDetail) {
          $priceSetFieldValueDetail['entity_table'] = 'civicrm_participant';
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
      'currency' => $params['currency'],
      'line_items' => $lineItems,
    ];
    $result = civicrm_api3('Order', 'create', $orderParams);
    return reset($result['values']);
  }

  /**
   * Save data to session.
   */
  public function saveDataToSession() {

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
    $contact = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('first_name', 'last_name')
      ->addWhere('id', '=', $contactID)
      ->execute()
      ->first();
    $this->_defaults['first_name'] = $contact['first_name'] ?? '';
    $this->_defaults['last_name'] = $contact['last_name'] ?? '';
    $email = \Civi\Api4\Email::get(FALSE)
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
    $currency = $event_in_cart->event->currency ?? CRM_Core_Config::singleton()->defaultCurrency;
    $this->assign('currency', $currency);
    $this->paymentPropertyBag->setCurrency($currency);
    $this->paymentPropertyBag->setAmount($this->total);
  }

}
