<?php

/**
 * Class CRM_Event_Cart_Form_Checkout_Payment
 */
class CRM_Event_Cart_Form_Checkout_Payment extends CRM_Event_Cart_Form_Cart {
  public $all_participants;
  public $financial_type_id;
  public $description;
  public $line_items;
  public $_fields = array();
  public $_paymentProcessor;
  public $total;
  public $sub_total;
  public $payment_required = TRUE;
  public $payer_contact_id;
  public $is_pay_later = FALSE;
  public $pay_later_receipt;

  /**
   * Register a participant.
   *
   * @param array $params
   * @param CRM_Event_BAO_Participant $participant
   * @param CRM_Event_BAO_Event $event
   *
   * @return mixed
   */
  public function registerParticipant($params, &$participant, $event) {
    $transaction = new CRM_Core_Transaction();

    // handle register date CRM-4320
    $registerDate = date('YmdHis');
    $participantParams = array(
      'id' => $participant->id,
      'event_id' => $event->id,
      'register_date' => $registerDate,
      'source' => CRM_Utils_Array::value('participant_source', $params, $this->description),
      //'fee_level'     => $participant->fee_level,
      'is_pay_later' => $this->is_pay_later,
      'fee_amount' => CRM_Utils_Array::value('amount', $params, 0),
      //XXX why is this a ref to participant and not contact?:
      //'registered_by_id' => $this->payer_contact_id,
      'fee_currency' => CRM_Utils_Array::value('currencyID', $params),
    );

    if ($participant->must_wait) {
      $participant_status = 'On waitlist';
    }
    elseif (CRM_Utils_Array::value('is_pay_later', $params, FALSE)) {
      $participant_status = 'Pending from pay later';
    }
    else {
      $participant_status = 'Registered';
    }
    $participant_statuses = CRM_Event_PseudoConstant::participantStatus();
    $participantParams['status_id'] = array_search($participant_status, $participant_statuses);
    $participant_status_label = CRM_Utils_Array::value($participantParams['status_id'], CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label'));
    $participantParams['participant_status'] = $participant_status_label;

    $this->assign('isOnWaitlist', $participant->must_wait);

    if ($this->_action & CRM_Core_Action::PREVIEW || CRM_Utils_Array::value('mode', $params) == 'test') {
      $participantParams['is_test'] = 1;
    }
    else {
      $participantParams['is_test'] = 0;
    }

    if (self::is_administrator()) {
      if (!empty($params['note'])) {
        $note_params = array(
          'participant_id' => $participant->id,
          'contact_id' => self::getContactID(),
          'note' => $params['note'],
        );
        CRM_Event_BAO_Participant::update_note($note_params);
      }
    }

    $participant->copyValues($participantParams);
    $participant->save();

    if (!empty($params['contributionID'])) {
      $payment_params = array(
        'participant_id' => $participant->id,
        'contribution_id' => $params['contributionID'],
      );
      CRM_Event_BAO_ParticipantPayment::create($payment_params);
    }

    $transaction->commit();

    $event_values = array();
    CRM_Core_DAO::storeValues($event, $event_values);

    $location = array();
    if (CRM_Utils_Array::value('is_show_location', $event_values) == 1) {
      $locationParams = array(
        'entity_id' => $participant->event_id,
        'entity_table' => 'civicrm_event',
      );
      $location = CRM_Core_BAO_Location::getValues($locationParams, TRUE);
      CRM_Core_BAO_Address::fixAddress($location['address'][1]);
    }

    list($pre_id, $post_id) = CRM_Event_Cart_Form_MerParticipant::get_profile_groups($participant->event_id);
    $payer_values = array(
      'email' => '',
      'name' => '',
    );
    if ($this->payer_contact_id) {
      $payer_contact_details = CRM_Contact_BAO_Contact::getContactDetails($this->payer_contact_id);
      $payer_values = array(
        'email' => $payer_contact_details[1],
        'name' => $payer_contact_details[0],
      );
    }
    $values = array(
      'params' => array($participant->id => $participantParams),
      'event' => $event_values,
      'location' => $location,
      'custom_pre_id' => $pre_id,
      'custom_post_id' => $post_id,
      'payer' => $payer_values,
    );
    CRM_Event_BAO_Event::sendMail($participant->contact_id, $values, $participant->id);

    return $participant;
  }

  /**
   * Build payment fields.
   */
  public function buildPaymentFields() {
    $payment_processor_id = NULL;
    $can_pay_later = TRUE;
    $pay_later_text = "";
    $this->pay_later_receipt = "";
    foreach ($this->cart->get_main_events_in_carts() as $event_in_cart) {
      if ($payment_processor_id == NULL && $event_in_cart->event->payment_processor != NULL) {
        $payment_processor_id = $event_in_cart->event->payment_processor;
        $this->financial_type_id = $event_in_cart->event->financial_type_id;
      }
      else {
        if ($event_in_cart->event->payment_processor != NULL && $event_in_cart->event->payment_processor != $payment_processor_id) {
          CRM_Core_Error::statusBounce(ts('When registering for multiple events all events must use the same payment processor. '));
        }
      }
      if (!$event_in_cart->event->is_pay_later) {
        $can_pay_later = FALSE;
      }
      else {
        //XXX
        $pay_later_text = $event_in_cart->event->pay_later_text;
        $this->pay_later_receipt = $event_in_cart->event->pay_later_receipt;
      }
    }

    if ($payment_processor_id == NULL) {
      CRM_Core_Error::statusBounce(ts('A payment processor must be selected for this event registration page, or the event must be configured to give users the option to pay later (contact the site administrator for assistance).'));
    }

    $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($payment_processor_id, $this->_mode);
    $this->assign('paymentProcessor', $this->_paymentProcessor);

    CRM_Core_Payment_Form::buildPaymentForm($this, $this->_paymentProcessor, FALSE, FALSE);

    if ($can_pay_later || self::is_administrator()) {
      $this->addElement('checkbox', 'is_pay_later',
        $pay_later_text
      );
      $this->addElement('checkbox', 'payment_completed',
        ts('Payment Completed')
      );
      $this->assign('pay_later_instructions', $this->pay_later_receipt);
    }
  }

  /**
   * Build QuickForm.
   */
  public function buildQuickForm() {

    $this->line_items = array();
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
    $this->payment_required = ($this->total > 0);
    $this->assign('payment_required', $this->payment_required);
    $this->assign('line_items', $this->line_items);
    $this->assign('sub_total', $this->sub_total);
    $this->assign('total', $this->total);
    $buttons = array();
    $buttons[] = array(
      'name' => ts('Go Back'),
      'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp',
      'type' => 'back',
    );
    $buttons[] = array(
      'isDefault' => TRUE,
      'name' => ts('Complete Transaction'),
      'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
      'type' => 'next',
    );

    if ($this->total) {
      $this->add('text', 'billing_contact_email', 'Billing Email', '', TRUE);
      $this->assign('collect_billing_email', TRUE);
    }
    if (self::is_administrator()) {
      $this->add('textarea', 'note', 'Note');
      $this->add('text', 'source', 'Source', array('size' => 80));
      $instruments = array();
      CRM_Core_OptionGroup::getAssoc('payment_instrument', $instruments, TRUE);
      $options = array();
      foreach ($instruments as $type) {
        $options[] = $this->createElement('radio', NULL, '', $type['label'], $type['value']);
      }
      $this->addGroup($options, 'payment_type', ts("Alternative Payment Type"));
      $this->add('text', 'check_number', ts('Check No.'), array('size' => 20));
      $this->addElement('checkbox', 'is_pending', ts('Create a pending registration'));

      $this->assign('administrator', TRUE);
    }
    $this->addButtons($buttons);

    $this->addFormRule(array('CRM_Event_Cart_Form_Checkout_Payment', 'formRule'), $this);

    if ($this->payment_required) {
      $this->buildPaymentFields();
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
      $event_price_values = array();
      foreach ($this->_price_values as $key => $value) {
        if (preg_match("/event_{$event_in_cart->event_id}_(price.*)/", $key, $matches)) {
          $event_price_values[$matches[1]] = $value;
        }
      }
      $price_sets = CRM_Price_BAO_PriceSet::getSetDetail($price_set_id, TRUE);
      $price_set = $price_sets[$price_set_id];
      $price_set_amount = array();
      CRM_Price_BAO_PriceSet::processAmount($price_set['fields'], $event_price_values, $price_set_amount);
      $discountCode = $this->_price_values['discountcode'];
      if (!empty($discountCode)) {
        $ret = $this->apply_discount($discountCode, $price_set_amount, $cost, $event_in_cart->event_id);
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
    $not_waiting_participants = array();
    foreach ($event_in_cart->not_waiting_participants() as $participant) {
      $amount += $participant->cost;
      $cost = max($cost, $participant->cost);
      $not_waiting_participants[] = array(
        'display_name' => CRM_Contact_BAO_Contact::displayName($participant->contact_id),
      );
    }
    $waiting_participants = array();
    foreach ($event_in_cart->waiting_participants() as $participant) {
      $waiting_participants[] = array(
        'display_name' => CRM_Contact_BAO_Contact::displayName($participant->contact_id),
      );
    }
    $this->line_items[] = array(
      'amount' => $amount,
      'cost' => $cost,
      'event' => $event_in_cart->event,
      'participants' => $not_waiting_participants,
      'num_participants' => count($not_waiting_participants),
      'num_waiting_participants' => count($waiting_participants),
      'waiting_participants' => $waiting_participants,
      'class' => $class,
    );

    $this->sub_total += $amount;
  }

  /**
   * Send email receipt.
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
      $location_params = array('entity_id' => $line_item['event']->id, 'entity_table' => 'civicrm_event');
      $line_item['location'] = CRM_Core_BAO_Location::getValues($location_params, TRUE);
      CRM_Core_BAO_Address::fixAddress($line_item['location']['address'][1]);
    }
    $send_template_params = array(
      'table' => 'civicrm_msg_template',
      'contactId' => $this->payer_contact_id,
      'from' => CRM_Core_BAO_Domain::getNameAndEmail(TRUE, TRUE),
      'groupName' => 'msg_tpl_workflow_event',
      'isTest' => FALSE,
      'toEmail' => $contact_details[1],
      'toName' => $contact_details[0],
      'tplParams' => array(
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
        'is_pay_later' => $this->is_pay_later,
        'pay_later_receipt' => $this->pay_later_receipt,
      ),
      'valueName' => 'event_registration_receipt',
      'PDFFilename' => ts('confirmation') . '.pdf',
    );
    $template_params_to_copy = array(
      'billing_name',
      'billing_city',
      'billing_country',
      'billing_postal_code',
      'billing_state',
      'billing_street_address',
      'credit_card_exp_date',
      'credit_card_type',
      'credit_card_number',
    );
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
   * @param CRM_Core_Form $self
   *
   * @return array|bool
   */
  public static function formRule($fields, $files, $self) {
    $errors = array();

    if ($self->payment_required && empty($self->_submitValues['is_pay_later'])) {
      CRM_Core_Form::validateMandatoryFields($self->_fields, $fields, $errors);

      // validate payment instrument values (e.g. credit card number)
      CRM_Core_Payment_Form::validatePaymentInstrument($self->_paymentProcessor['id'], $fields, $errors, NULL);
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Validate form.
   *
   * @todo this should surely go! Test & remove.
   * @return bool
   */
  public function validate() {
    if ($this->is_pay_later) {
      $this->_fields['credit_card_number']['is_required'] = FALSE;
      $this->_fields['cvv2']['is_required'] = FALSE;
      $this->_fields['credit_card_exp_date']['is_required'] = FALSE;
      $this->_fields['credit_card_type']['is_required'] = FALSE;
    }
    return parent::validate();
  }

  /**
   * Pre-process form.
   */
  public function preProcess() {
    $params = $this->_submitValues;
    $this->is_pay_later = CRM_Utils_Array::value('is_pay_later', $params, FALSE) && !CRM_Utils_Array::value('payment_completed', $params);

    parent::preProcess();
  }

  /**
   * Post process form.
   */
  public function postProcess() {

    $transaction = new CRM_Core_Transaction();
    $trxnDetails = NULL;
    $params = $this->_submitValues;

    $main_participants = $this->cart->get_main_event_participants();
    foreach ($main_participants as $participant) {
      $defaults = array();
      $ids = array('contact_id' => $participant->contact_id);
      $contact = CRM_Contact_BAO_Contact::retrieve($ids, $defaults);
      $contact->is_deleted = 0;
      $contact->save();
    }

    $trxn_prefix = 'VR';
    if (array_key_exists('billing_contact_email', $params)) {
      $this->payer_contact_id = self::find_or_create_contact($this->getContactID(), array(
        'email' => $params['billing_contact_email'],
        'first_name' => $params['billing_first_name'],
        'last_name' => $params['billing_last_name'],
        'is_deleted' => FALSE,
      ));

      $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $this->payer_contact_id,
        'contact_type'
      );
      $billing_fields = array(
        "billing_first_name" => 1,
        "billing_middle_name" => 1,
        "billing_last_name" => 1,
        "billing_street_address-{$this->_bltID}" => 1,
        "billing_city-{$this->_bltID}" => 1,
        "billing_state_province_id-{$this->_bltID}" => 1,
        "billing_postal_code-{$this->_bltID}" => 1,
        "billing_country_id-{$this->_bltID}" => 1,
        "address_name-{$this->_bltID}" => 1,
        "email-{$this->_bltID}" => 1,
      );

      $params["address_name-{$this->_bltID}"] = CRM_Utils_Array::value('billing_first_name', $params) . ' ' . CRM_Utils_Array::value('billing_middle_name', $params) . ' ' . CRM_Utils_Array::value('billing_last_name', $params);

      $params["email-{$this->_bltID}"] = $params['billing_contact_email'];
      CRM_Contact_BAO_Contact::createProfileContact(
        $params,
        $billing_fields,
        $this->payer_contact_id,
        NULL,
        NULL,
        $ctype,
        TRUE
      );
    }

    $params['now'] = date('YmdHis');
    $params['invoiceID'] = md5(uniqid(rand(), TRUE));
    $params['amount'] = $this->total;
    $params['financial_type_id'] = $this->financial_type_id;
    if ($this->payment_required && empty($params['is_pay_later'])) {
      $trxnDetails = $this->make_payment($params);
      $params['trxn_id'] = $trxnDetails['trxn_id'];
      $params['trxn_date'] = $trxnDetails['trxn_date'];
      $params['currencyID'] = $trxnDetails['currency'];
    }
    $this->cart->completed = TRUE;
    $this->cart->save();
    $this->set('last_event_cart_id', $this->cart->id);

    $contribution_statuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $params['payment_instrument_id'] = NULL;
    if (!empty($params['is_pay_later'])) {
      $params['payment_instrument_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check');
      $trxn_prefix = 'CK';
    }
    else {
      $params['payment_instrument_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Credit Card');
    }
    if ($this->is_pay_later && empty($params['payment_completed'])) {
      $params['contribution_status_id'] = array_search('Pending', $contribution_statuses);
    }
    else {
      $params['contribution_status_id'] = array_search('Completed', $contribution_statuses);
      $params['participant_status'] = 'Registered';
      $params['is_pay_later'] = 0;
    }
    if ($trxnDetails == NULL) {
      $params['trxn_id'] = $trxn_prefix . strftime("%Y%m%d%H%M%S");
      $params['trxn_date'] = $params['now'];
    }

    if ($this->payment_required) {
      $this->emailReceipt($this->cart->events_in_carts, $params);
    }

    // n.b. we need to process the subparticipants before main event
    // participants so that session attendance can be included in the email
    $main_participants = $this->cart->get_main_event_participants();
    $this->all_participants = array();
    foreach ($main_participants as $main_participant) {
      $this->all_participants = array_merge($this->all_participants, $this->cart->get_subparticipants($main_participant));
    }
    $this->all_participants = array_merge($this->all_participants, $main_participants);

    $this->sub_trxn_index = 0;
    foreach ($this->all_participants as $mer_participant) {
      $event_in_cart = $this->cart->get_event_in_cart_by_event_id($mer_participant->event_id);

      $this->sub_trxn_index += 1;

      unset($params['contributionID']);
      if ($mer_participant->must_wait) {
        $this->registerParticipant($params, $mer_participant, $event_in_cart->event);
      }
      else {
        $params['amount'] = $mer_participant->cost - $mer_participant->discount_amount;

        if ($event_in_cart->event->financial_type_id && $mer_participant->cost) {
          $params['financial_type_id'] = $event_in_cart->event->financial_type_id;
          $params['participant_contact_id'] = $mer_participant->contact_id;
          $contribution = $this->record_contribution($mer_participant, $params, $event_in_cart->event);
          // Record civicrm_line_item
          CRM_Price_BAO_LineItem::processPriceSet($mer_participant->id, $mer_participant->price_details, $contribution, $entity_table = 'civicrm_participant');
        }
        $this->registerParticipant($params, $mer_participant, $event_in_cart->event);
      }
    }
    $this->trxn_id = $params['trxn_id'];
    $this->trxn_date = $params['trxn_date'];
    $this->saveDataToSession();
    $transaction->commit();
  }

  /**
   * Make payment.
   *
   * @param array $params
   *
   * @return array
   * @throws Exception
   */
  public function make_payment(&$params) {
    $config = CRM_Core_Config::singleton();
    if (isset($params["billing_state_province_id-{$this->_bltID}"]) && $params["billing_state_province_id-{$this->_bltID}"]) {
      $params["billing_state_province-{$this->_bltID}"] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($params["billing_state_province_id-{$this->_bltID}"]);
    }

    if (isset($params["billing_country_id-{$this->_bltID}"]) && $params["billing_country_id-{$this->_bltID}"]) {
      $params["billing_country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($params["billing_country_id-{$this->_bltID}"]);
    }
    $params['ip_address'] = CRM_Utils_System::ipAddress();
    $params['currencyID'] = $config->defaultCurrency;

    $payment = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    CRM_Core_Payment_Form::mapParams($this->_bltID, $params, $params, TRUE);
    $params['month'] = $params['credit_card_exp_date']['M'];
    $params['year'] = $params['credit_card_exp_date']['Y'];
    try {
      $result = $payment->doPayment($params);
    }
    catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
      CRM_Core_Error::displaySessionError($result);
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/event/cart_checkout', "_qf_Payment_display=1&qfKey={$this->controller->_key}", TRUE, NULL, FALSE));
    }

    $trxnDetails = array(
      'trxn_id' => $result['trxn_id'],
      'trxn_date' => $result['now'],
      'currency' => CRM_Utils_Array::value('currencyID', $result),
    );
    return $trxnDetails;
  }

  /**
   * Record contribution.
   *
   * @param CRM_Event_BAO_Participant $mer_participant
   * @param array $params
   * @param CRM_Event_BAO_Event $event
   *
   * @return object
   * @throws Exception
   */
  public function record_contribution(&$mer_participant, &$params, $event) {
    if (self::is_administrator() && !empty($params['payment_type'])) {
      $params['payment_instrument_id'] = $params['payment_type'];
    }

    if ($this->payer_contact_id) {
      $payer = $this->payer_contact_id;
    }
    elseif (self::getContactID()) {
      $payer = self::getContactID();
    }
    else {
      $payer = $params['participant_contact_id'];
    }

    $contribParams = array(
      'contact_id' => $payer,
      'financial_type_id' => $params['financial_type_id'],
      'receive_date' => $params['now'],
      'total_amount' => $params['amount'],
      'amount_level' => $mer_participant->fee_level,
      'net_amount' => $params['amount'],
      'invoice_id' => "{$params['invoiceID']}-{$this->sub_trxn_index}",
      'trxn_id' => "{$params['trxn_id']}-{$this->sub_trxn_index}",
      'currency' => CRM_Utils_Array::value('currencyID', $params),
      'source' => $event->title,
      'is_pay_later' => CRM_Utils_Array::value('is_pay_later', $params, 0),
      'contribution_status_id' => $params['contribution_status_id'],
      'payment_instrument_id' => $params['payment_instrument_id'],
      'check_number' => CRM_Utils_Array::value('check_number', $params),
      'skipLineItem' => 1,
    );

    if (is_array($this->_paymentProcessor)) {
      $contribParams['payment_processor'] = $this->_paymentProcessor['id'];
    }

    $contribution = CRM_Contribute_BAO_Contribution::add($contribParams);
    $mer_participant->contribution_id = $contribution->id;
    $params['contributionID'] = $contribution->id;

    return $contribution;
  }

  /**
   * Save data to session.
   */
  public function saveDataToSession() {
    $session_line_items = array();
    foreach ($this->line_items as $line_item) {
      $session_line_item = array();
      $session_line_item['amount'] = $line_item['amount'];
      $session_line_item['cost'] = $line_item['cost'];
      $session_line_item['event_id'] = $line_item['event']->id;
      $session_line_items[] = $session_line_item;
    }
    $this->set('line_items', $session_line_items);
    $this->set('payment_required', $this->payment_required);
    $this->set('is_pay_later', $this->is_pay_later);
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

    $defaults = parent::setDefaultValues();

    $config = CRM_Core_Config::singleton();
    $default_country = new CRM_Core_DAO_Country();
    $default_country->iso_code = $config->defaultContactCountry();
    $default_country->find(TRUE);
    $defaults["billing_country_id-{$this->_bltID}"] = $default_country->id;

    if (self::getContactID() && !self::is_administrator()) {
      $params = array('id' => self::getContactID());
      $contact = CRM_Contact_BAO_Contact::retrieve($params, $defaults);

      foreach ($contact->email as $email) {
        if ($email['is_billing']) {
          $defaults["billing_contact_email"] = $email['email'];
        }
      }
      if (empty($defaults['billing_contact_email'])) {
        foreach ($contact->email as $email) {
          if ($email['is_primary']) {
            $defaults["billing_contact_email"] = $email['email'];
          }
        }
      }

      $defaults["billing_first_name"] = $contact->first_name;
      $defaults["billing_middle_name"] = $contact->middle_name;
      $defaults["billing_last_name"] = $contact->last_name;

      $billing_address = CRM_Event_Cart_BAO_MerParticipant::billing_address_from_contact($contact);

      if ($billing_address != NULL) {
        $defaults["billing_street_address-{$this->_bltID}"] = $billing_address['street_address'];
        $defaults["billing_city-{$this->_bltID}"] = $billing_address['city'];
        $defaults["billing_postal_code-{$this->_bltID}"] = $billing_address['postal_code'];
        $defaults["billing_state_province_id-{$this->_bltID}"] = $billing_address['state_province_id'];
        $defaults["billing_country_id-{$this->_bltID}"] = $billing_address['country_id'];
      }
    }

    $defaults["source"] = $this->description;

    return $defaults;
  }

  /**
   * Apply discount.
   *
   * @param string $discountCode
   * @param array $price_set_amount
   * @param float $cost
   * @param int $event_id
   *
   * @return bool
   */
  protected function apply_discount($discountCode, &$price_set_amount, &$cost, $event_id) {
    //need better way to determine if cividiscount installed
    $autoDiscount = array();
    $sql = "select is_active from civicrm_extension where name like 'CiviDiscount%'";
    $dao = CRM_Core_DAO::executeQuery($sql, '');
    while ($dao->fetch()) {
      if ($dao->is_active != '1') {
        return FALSE;
      }
    }
    $discounted_priceset_ids = _cividiscount_get_discounted_priceset_ids();
    $discounts = _cividiscount_get_discounts();

    $stat = FALSE;
    foreach ($discounts as $key => $discountValue) {
      if ($key == $discountCode) {
        $events = CRM_Utils_Array::value('events', $discountValue);
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
            $discounted = _cividiscount_calc_discount($price['line_total'], $price['label'], $discountValue, $autoDiscount, "USD");
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

}
