<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but   |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This form records additional payments needed when event/contribution is partially paid.
 */
class CRM_Contribute_Form_AdditionalPayment extends CRM_Contribute_Form_AbstractEditPayment {
  public $_contributeMode = 'direct';

  /**
   * Related component whose financial payment is being processed.
   *
   * @var string
   */
  protected $_component = NULL;

  /**
   * Id of the component entity
   */
  public $_id = NULL;

  protected $_owed = NULL;

  protected $_refund = NULL;

  protected $_contactId = NULL;

  protected $_contributorDisplayName = NULL;

  protected $_contributorEmail = NULL;

  protected $_toDoNotEmail = NULL;

  protected $_paymentType = NULL;

  protected $_contributionId = NULL;

  protected $fromEmailId = NULL;

  protected $_fromEmails = NULL;

  protected $_view = NULL;

  public $_action = NULL;

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->_component = CRM_Utils_Request::retrieve('component', 'String', $this, TRUE);
    $this->_view = CRM_Utils_Request::retrieve('view', 'String', $this, FALSE);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE);
    $this->assign('component', $this->_component);
    $this->assign('id', $this->_id);
    $this->assign('suppressPaymentFormButtons', $this->isBeingCalledFromSelectorContext());

    if ($this->_view == 'transaction' && ($this->_action & CRM_Core_Action::BROWSE)) {
      $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($this->_id, $this->_component, TRUE);
      $transactionRows = $paymentInfo['transaction'];
      $title = ts('View Payment');
      if ($this->_component == 'event') {
        $info = CRM_Event_BAO_Participant::participantDetails($this->_id);
        $title .= " - {$info['title']}";
      }
      CRM_Utils_System::setTitle($title);
      $this->assign('transaction', TRUE);
      $this->assign('rows', $transactionRows);
      return;
    }
    $this->_fromEmails = CRM_Core_BAO_Email::getFromEmail();
    $this->_formType = CRM_Utils_Array::value('formType', $_GET);

    $enitityType = NULL;
    if ($this->_component == 'event') {
      $enitityType = 'participant';
      $this->_contributionId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $this->_id, 'contribution_id', 'participant_id');
    }
    $eventId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $this->_id, 'event_id', 'id');
    $this->_fromEmails = CRM_Event_BAO_Event::getFromEmailIds($eventId);

    $paymentInfo = CRM_Core_BAO_FinancialTrxn::getPartialPaymentWithType($this->_id, $enitityType);
    $paymentDetails = CRM_Contribute_BAO_Contribution::getPaymentInfo($this->_id, $this->_component, FALSE, TRUE);

    $this->_amtPaid = $paymentDetails['paid'];
    $this->_amtTotal = $paymentDetails['total'];

    if (!empty($paymentInfo['refund_due'])) {
      $paymentAmt = $this->_refund = $paymentInfo['refund_due'];
      $this->_paymentType = 'refund';
    }
    elseif (!empty($paymentInfo['amount_owed'])) {
      $paymentAmt = $this->_owed = $paymentInfo['amount_owed'];
      $this->_paymentType = 'owed';
    }
    else {
      CRM_Core_Error::fatal(ts('No payment information found for this record'));
    }

    //set the payment mode - _mode property is defined in parent class
    $this->_mode = CRM_Utils_Request::retrieve('mode', 'String', $this);

    if (!empty($this->_mode) && $this->_paymentType == 'refund') {
      CRM_Core_Error::fatal(ts('Credit card payment is not for Refund payments use'));
    }

    list($this->_contributorDisplayName, $this->_contributorEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactId);

    $this->assignPaymentRelatedVariables();

    $this->assign('contributionMode', $this->_mode);
    $this->assign('contactId', $this->_contactId);
    $this->assign('paymentType', $this->_paymentType);
    $this->assign('paymentAmt', abs($paymentAmt));

    $this->setPageTitle($this->_refund ? ts('Refund') : ts('Payment'));
  }

  /**
   * Is this function being called from a datatable selector.
   *
   * If so we don't want to show the buttons.
   */
  protected function isBeingCalledFromSelectorContext() {
    return CRM_Utils_Request::retrieve('selector', 'Positive');
  }

  /**
   * This virtual function is used to set the default values of
   * various form elements
   *
   * access        public
   *
   * @return array
   *   reference to the array of default values
   */
  /**
   * @return array
   */
  public function setDefaultValues() {
    if ($this->_view == 'transaction' && ($this->_action & CRM_Core_Action::BROWSE)) {
      return NULL;
    }
    $defaults = array();
    if ($this->_mode) {
      $defaults = $this->_values;

      $config = CRM_Core_Config::singleton();
      // set default country from config if no country set
      if (empty($defaults["billing_country_id-{$this->_bltID}"])) {
        $defaults["billing_country_id-{$this->_bltID}"] = $config->defaultContactCountry;
      }

      if (empty($defaults["billing_state_province_id-{$this->_bltID}"])) {
        $defaults["billing_state_province_id-{$this->_bltID}"] = $config->defaultContactStateProvince;
      }

      $billingDefaults = $this->getProfileDefaults('Billing', $this->_contactId);
      $defaults = array_merge($defaults, $billingDefaults);
    }

    if (empty($defaults['trxn_date']) && empty($defaults['trxn_date_time'])) {
      list($defaults['trxn_date'], $defaults['trxn_date_time'])
        = CRM_Utils_Date::setDateDefaults(
          CRM_Utils_Array::value('register_date', $defaults),
          'activityDateTime'
        );
    }

    if ($this->_refund) {
      $defaults['total_amount'] = abs($this->_refund);
    }

    // Set $newCredit variable in template to control whether link to credit card mode is included
    $this->assign('newCredit', CRM_Core_Config::isEnabledBackOfficeCreditCardPayments());
    return $defaults;
  }

  public function buildQuickForm() {
    if ($this->_view == 'transaction' && ($this->_action & CRM_Core_Action::BROWSE)) {
      $this->addButtons(array(
          array(
            'type' => 'cancel',
            'name' => ts('Done'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ),
        )
      );
      return;
    }
    $ccPane = NULL;
    if ($this->_mode) {
      if (CRM_Utils_Array::value('payment_type', $this->_processors) & CRM_Core_Payment::PAYMENT_TYPE_DIRECT_DEBIT
      ) {
        $ccPane = array(ts('Direct Debit Information') => 'DirectDebit');
      }
      else {
        $ccPane = array(ts('Credit Card Information') => 'CreditCard');
      }
      $defaults = $this->_values;
      $showAdditionalInfo = FALSE;

      foreach ($ccPane as $name => $type) {
        if ($this->_formType == $type || !empty($_POST["hidden_{$type}"]) ||
          CRM_Utils_Array::value("hidden_{$type}", $defaults)
        ) {
          $showAdditionalInfo = TRUE;
          $allPanes[$name]['open'] = 'true';
        }

        $urlParams = "snippet=4&formType={$type}";
        if ($this->_mode) {
          $urlParams .= "&mode={$this->_mode}";
        }
        $open = 'false';
        if ($type == 'CreditCard' ||
          $type == 'DirectDebit'
        ) {
          $open = 'true';
        }

        $allPanes[$name] = array(
          'url' => CRM_Utils_System::url('civicrm/payment/add', $urlParams),
          'open' => $open,
          'id' => $type,
        );

        CRM_Core_Payment_Form::buildPaymentForm($this, $this->_paymentProcessor, FALSE, TRUE);

        $qfKey = $this->controller->_key;
        $this->assign('qfKey', $qfKey);
        $this->assign('allPanes', $allPanes);
        $this->assign('showAdditionalInfo', $showAdditionalInfo);

        if ($this->_formType) {
          $this->assign('formType', $this->_formType);
          return;
        }
      }
    }
    $attributes = CRM_Core_DAO::getAttribute('CRM_Financial_DAO_FinancialTrxn');

    $this->add('select', 'payment_processor_id', ts('Payment Processor'), $this->_processors, NULL);
    $label = ($this->_refund) ? ts('Refund Amount') : ts('Payment Amount');
    $this->addMoney('total_amount',
      $label,
      FALSE,
      $attributes['total_amount'],
      TRUE, 'currency', NULL
    );

    $this->add('select', 'payment_instrument_id',
      ts('Payment Method'),
      array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::paymentInstrument(),
      TRUE, array('onChange' => "return showHideByValue('payment_instrument_id','4','checkNumber','table-row','select',false);")
    );

    $this->add('text', 'check_number', ts('Check Number'), $attributes['financial_trxn_check_number']);
    $this->add('text', 'trxn_id', ts('Transaction ID'), array('class' => 'twelve') + $attributes['trxn_id']);

    //add receipt for offline contribution
    $this->addElement('checkbox', 'is_email_receipt', ts('Send Receipt?'));

    $this->add('select', 'from_email_address', ts('Receipt From'), $this->_fromEmails['from_email_id']);

    $this->add('textarea', 'receipt_text', ts('Confirmation Message'));

    // add various dates
    $dateLabel = ($this->_refund) ? ts('Refund Date') : ts('Date Received');
    $this->addDateTime('trxn_date', $dateLabel, FALSE, array('formatType' => 'activityDateTime'));

    if ($this->_contactId && $this->_id) {
      if ($this->_component == 'event') {
        $eventId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $this->_id, 'event_id', 'id');
        $event = CRM_Event_BAO_Event::getEvents(0, $eventId);
        $this->assign('eventName', $event[$eventId]);
      }
    }

    $this->assign('displayName', $this->_contributorDisplayName);
    $this->assign('component', $this->_component);
    $this->assign('email', $this->_contributorEmail);

    $this->add('text', 'fee_amount', ts('Fee Amount'),
      $attributes['fee_amount']
    );
    $this->addRule('fee_amount', ts('Please enter a valid monetary value for Fee Amount.'), 'money');

    $this->add('text', 'net_amount', ts('Net Amount'),
      $attributes['net_amount']
    );
    $this->addRule('net_amount', ts('Please enter a valid monetary value for Net Amount.'), 'money');

    $js = NULL;
    if (!$this->_mode) {
      $js = array('onclick' => "return verify( );");
    }

    $buttonName = $this->_refund ? 'Record Refund' : 'Record Payment';
    $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('%1', array(1 => $buttonName)),
          'js' => $js,
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
    $mailingInfo = Civi::settings()->get('mailing_backend');
    $this->assign('outBound_option', $mailingInfo['outBound_option']);

    $this->addFormRule(array('CRM_Contribute_Form_AdditionalPayment', 'formRule'), $this);
  }

  /**
   * @param $fields
   * @param $files
   * @param $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = array();
    if ($self->_paymentType == 'owed' && $fields['total_amount'] > $self->_owed) {
      $errors['total_amount'] = ts('Payment amount cannot be greater than owed amount');
    }
    if ($self->_paymentType == 'refund' && $fields['total_amount'] != abs($self->_refund)) {
      $errors['total_amount'] = ts('Refund amount must equal refund due amount.');
    }
    $netAmt = $fields['total_amount'] - $fields['fee_amount'];
    if (!empty($fields['net_amount']) && $netAmt != $fields['net_amount']) {
      $errors['net_amount'] = ts('Net amount should be equal to the difference between payment amount and fee amount.');
    }
    return $errors;
  }

  public function postProcess() {
    $participantId = NULL;
    if ($this->_component == 'event') {
      $participantId = $this->_id;
    }
    $submittedValues = $this->controller->exportValues($this->_name);
    $submittedValues['confirm_email_text'] = CRM_Utils_Array::value('receipt_text', $submittedValues);

    $submittedValues['trxn_date'] = CRM_Utils_Date::processDate($submittedValues['trxn_date'], $submittedValues['trxn_date_time']);
    if ($this->_mode) {
      // process credit card
      $this->assign('contributeMode', 'direct');
      $this->processCreditCard($submittedValues);
    }
    else {
      $defaults = array();
      $contribution = civicrm_api3('Contribution', 'getsingle', array(
        'return' => array("contribution_status_id"),
        'id' => $this->_contributionId,
      ));
      $contributionStatusId = CRM_Utils_Array::value('contribution_status_id', $contribution);
      $result = CRM_Contribute_BAO_Contribution::recordAdditionalPayment($this->_contributionId, $submittedValues, $this->_paymentType, $participantId);
      // Fetch the contribution & do proportional line item assignment
      $params = array('id' => $this->_contributionId);
      $contribution = CRM_Contribute_BAO_Contribution::retrieve($params, $defaults, $params);
      CRM_Contribute_BAO_Contribution::addPayments(array($contribution), $contributionStatusId);

      // email sending
      if (!empty($result) && !empty($submittedValues['is_email_receipt'])) {
        $submittedValues['contact_id'] = $this->_contactId;
        $submittedValues['contribution_id'] = $this->_contributionId;

        // to get 'from email id' for send receipt
        $this->fromEmailId = $submittedValues['from_email_address'];
        $sendReceipt = $this->emailReceipt($submittedValues);
      }

      $statusMsg = ts('The payment record has been processed.');
      if (!empty($submittedValues['is_email_receipt']) && $sendReceipt) {
        $statusMsg .= ' ' . ts('A receipt has been emailed to the contributor.');
      }

      CRM_Core_Session::setStatus($statusMsg, ts('Saved'), 'success');

      $session = CRM_Core_Session::singleton();
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$this->_contactId}&selectedChild=participant"
      ));
    }
  }

  /**
   * @param $submittedValues
   */
  public function processCreditCard($submittedValues) {
    $config = CRM_Core_Config::singleton();
    $session = CRM_Core_Session::singleton();

    $unsetParams = array(
      'trxn_id',
      'payment_instrument_id',
      'contribution_status_id',
    );
    foreach ($unsetParams as $key) {
      if (isset($submittedValues[$key])) {
        unset($submittedValues[$key]);
      }
    }

    // Get the required fields value only.
    $params = $this->_params = $submittedValues;

    //get the payment processor id as per mode.
    //@todo unclear relevance of mode - seems like a lot of duplicated params here!
    $this->_params['payment_processor'] = $params['payment_processor_id']
      = $this->_params['payment_processor_id'] = $submittedValues['payment_processor_id'] = $this->_paymentProcessor['id'];

    $now = date('YmdHis');
    $fields = array();

    // we need to retrieve email address
    if ($this->_context == 'standalone' && !empty($submittedValues['is_email_receipt'])) {
      list($this->userDisplayName,
        $this->userEmail
        ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactId);
      $this->assign('displayName', $this->userDisplayName);
    }

    //set email for primary location.
    $fields['email-Primary'] = 1;
    $params['email-Primary'] = $this->_contributorEmail;

    // now set the values for the billing location.
    foreach ($this->_fields as $name => $dontCare) {
      $fields[$name] = 1;
    }

    // also add location name to the array
    $params["address_name-{$this->_bltID}"] = CRM_Utils_Array::value('billing_first_name', $params) . ' ' . CRM_Utils_Array::value('billing_middle_name', $params) . ' ' . CRM_Utils_Array::value('billing_last_name', $params);
    $params["address_name-{$this->_bltID}"] = trim($params["address_name-{$this->_bltID}"]);
    $fields["address_name-{$this->_bltID}"] = 1;

    $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
      $this->_contactId,
      'contact_type'
    );

    $nameFields = array('first_name', 'middle_name', 'last_name');
    foreach ($nameFields as $name) {
      $fields[$name] = 1;
      if (array_key_exists("billing_$name", $params)) {
        $params[$name] = $params["billing_{$name}"];
        $params['preserveDBName'] = TRUE;
      }
    }

    if (!empty($params['source'])) {
      unset($params['source']);
    }
    $contactID = CRM_Contact_BAO_Contact::createProfileContact($params, $fields,
      $this->_contactId,
      NULL, NULL,
      $ctype
    );

    // Add all the additional payment params we need.
    $this->_params["state_province-{$this->_bltID}"] = $this->_params["billing_state_province-{$this->_bltID}"] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($this->_params["billing_state_province_id-{$this->_bltID}"]);
    $this->_params["country-{$this->_bltID}"] = $this->_params["billing_country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($this->_params["billing_country_id-{$this->_bltID}"]);

    if ($this->_paymentProcessor['payment_type'] & CRM_Core_Payment::PAYMENT_TYPE_CREDIT_CARD) {
      $this->_params['year'] = CRM_Core_Payment_Form::getCreditCardExpirationYear($this->_params);
      $this->_params['month'] = CRM_Core_Payment_Form::getCreditCardExpirationMonth($this->_params);
    }
    $this->_params['ip_address'] = CRM_Utils_System::ipAddress();
    $this->_params['amount'] = $this->_params['total_amount'];
    // @todo - stop setting amount level in this function & call the CRM_Price_BAO_PriceSet::getAmountLevel
    // function to get correct amount level consistently. Remove setting of the amount level in
    // CRM_Price_BAO_PriceSet::processAmount. Extend the unit tests in CRM_Price_BAO_PriceSetTest
    // to cover all variants.
    $this->_params['amount_level'] = 0;
    $this->_params['currencyID'] = CRM_Utils_Array::value('currency',
      $this->_params,
      $config->defaultCurrency
    );

    if (!empty($this->_params['trxn_date'])) {
      $this->_params['receive_date'] = CRM_Utils_Date::processDate($this->_params['trxn_date'], $this->_params['trxn_date_time']);
    }

    if (empty($this->_params['invoice_id'])) {
      $this->_params['invoiceID'] = md5(uniqid(rand(), TRUE));
    }
    else {
      $this->_params['invoiceID'] = $this->_params['invoice_id'];
    }

    $this->assignBillingName($params);
    $this->assign('address', CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters(
      $params,
      $this->_bltID
    ));

    $date = CRM_Utils_Date::format($params['credit_card_exp_date']);
    $date = CRM_Utils_Date::mysqlToIso($date);
    $this->assign('credit_card_type', CRM_Utils_Array::value('credit_card_type', $params));
    $this->assign('credit_card_exp_date', $date);
    $this->assign('credit_card_number',
      CRM_Utils_System::mungeCreditCard($params['credit_card_number'])
    );

    //Add common data to formatted params
    CRM_Contribute_Form_AdditionalInfo::postProcessCommon($params, $this->_params, $this);
    // at this point we've created a contact and stored its address etc
    // all the payment processors expect the name and address to be in the
    // so we copy stuff over to first_name etc.
    $paymentParams = $this->_params;
    $paymentParams['contactID'] = $this->_contactId;
    CRM_Core_Payment_Form::mapParams($this->_bltID, $this->_params, $paymentParams, TRUE);

    // add some financial type details to the params list
    // if folks need to use it
    $paymentParams['contributionType_name'] = $this->_params['contributionType_name'] = $contributionType->name;
    $paymentParams['contributionPageID'] = NULL;
    if (!empty($this->_params['is_email_receipt'])) {
      $paymentParams['email'] = $this->_contributorEmail;
      $paymentParams['is_email_receipt'] = 1;
    }
    else {
      $paymentParams['is_email_receipt'] = 0;
      $this->_params['is_email_receipt'] = 0;
    }
    if (!empty($this->_params['receive_date'])) {
      $paymentParams['receive_date'] = $this->_params['receive_date'];
    }
    if (!empty($this->_params['receive_date'])) {
      $paymentParams['receive_date'] = $this->_params['receive_date'];
    }

    $result = NULL;

    if ($paymentParams['amount'] > 0.0) {
      try {
        // force a reget of the payment processor in case the form changed it, CRM-7179
        $payment = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
        $result = $payment->doPayment($paymentParams);
      }
      catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
        //set the contribution mode.
        $urlParams = "action=add&cid={$this->_contactId}&id={$this->_id}&component={$this->_component}";
        if ($this->_mode) {
          $urlParams .= "&mode={$this->_mode}";
        }
        CRM_Core_Error::displaySessionError($result);
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/payment/add', $urlParams));
      }
    }

    if ($result) {
      $this->_params = array_merge($this->_params, $result);
    }

    if (empty($this->_params['receive_date'])) {
      $this->_params['receive_date'] = $now;
    }

    $this->set('params', $this->_params);

    // set source if not set
    if (empty($this->_params['source'])) {
      $userID = $session->get('userID');
      $userSortName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $userID,
        'sort_name'
      );
      $this->_params['source'] = ts('Submit Credit Card Payment by: %1', array(1 => $userSortName));
    }

    // process the additional payment
    $participantId = NULL;
    if ($this->_component == 'event') {
      $participantId = $this->_id;
    }
    $trxnRecord = CRM_Contribute_BAO_Contribution::recordAdditionalPayment($this->_contributionId, $submittedValues, $this->_paymentType, $participantId);

    if ($trxnRecord->id && !empty($this->_params['is_email_receipt'])) {
      $sendReceipt = $this->emailReceipt($this->_params);
    }

    if ($trxnRecord->id) {
      $statusMsg = ts('The payment record has been processed.');
      if (!empty($this->_params['is_email_receipt']) && $sendReceipt) {
        $statusMsg .= ' ' . ts('A receipt has been emailed to the contributor.');
      }

      CRM_Core_Session::setStatus($statusMsg, ts('Complete'), 'success');
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$this->_contactId}&selectedChild=participant"
      ));
    }
  }

  /**
   * @param array $params
   *
   * @return mixed
   */
  public function emailReceipt(&$params) {
    // email receipt sending
    // send message template
    if ($this->_component == 'event') {
      $eventId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $this->_id, 'event_id', 'id');

      $returnProperties = array('fee_label', 'start_date', 'end_date', 'is_show_location', 'title');
      CRM_Core_DAO::commonRetrieveAll('CRM_Event_DAO_Event', 'id', $eventId, $events, $returnProperties);
      $event = $events[$eventId];
      unset($event['start_date']);
      unset($event['end_date']);

      $this->assign('event', $event);
      $this->assign('isShowLocation', $event['is_show_location']);
      if (CRM_Utils_Array::value('is_show_location', $event) == 1) {
        $locationParams = array(
          'entity_id' => $eventId,
          'entity_table' => 'civicrm_event',
        );
        $location = CRM_Core_BAO_Location::getValues($locationParams, TRUE);
        $this->assign('location', $location);
      }
    }

    // assign payment info here
    $paymentConfig['confirm_email_text'] = CRM_Utils_Array::value('confirm_email_text', $params);
    $this->assign('paymentConfig', $paymentConfig);
    $isRefund = ($this->_paymentType == 'refund') ? TRUE : FALSE;
    $this->assign('isRefund', $isRefund);
    if ($isRefund) {
      $this->assign('totalPaid', $this->_amtPaid);
      $this->assign('totalAmount', $this->_amtTotal);
      $this->assign('refundAmount', $params['total_amount']);
    }
    else {
      $balance = $this->_amtTotal - ($this->_amtPaid + $params['total_amount']);
      $paymentsComplete = ($balance == 0) ? 1 : 0;
      $this->assign('amountOwed', $balance);
      $this->assign('totalAmount', $this->_amtTotal);
      $this->assign('paymentAmount', $params['total_amount']);
      $this->assign('paymentsComplete', $paymentsComplete);
    }
    $this->assign('contactDisplayName', $this->_contributorDisplayName);

    // assign trxn details
    $this->assign('trxn_id', CRM_Utils_Array::value('trxn_id', $params));
    $this->assign('receive_date', CRM_Utils_Array::value('trxn_date', $params));
    $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
    if (array_key_exists('payment_instrument_id', $params)) {
      $this->assign('paidBy',
        CRM_Utils_Array::value($params['payment_instrument_id'],
          $paymentInstrument
        )
      );
    }
    $this->assign('checkNumber', CRM_Utils_Array::value('check_number', $params));

    $sendTemplateParams = array(
      'groupName' => 'msg_tpl_workflow_contribution',
      'valueName' => 'payment_or_refund_notification',
      'contactId' => $this->_contactId,
      'PDFFilename' => ts('notification') . '.pdf',
    );

    // try to send emails only if email id is present
    // and the do-not-email option is not checked for that contact
    if ($this->_contributorEmail && !$this->_toDoNotEmail) {
      if (array_key_exists($params['from_email_address'], $this->_fromEmails['from_email_id'])) {
        $receiptFrom = $params['from_email_address'];
      }

      $sendTemplateParams['from'] = $receiptFrom;
      $sendTemplateParams['toName'] = $this->_contributorDisplayName;
      $sendTemplateParams['toEmail'] = $this->_contributorEmail;
      $sendTemplateParams['cc'] = CRM_Utils_Array::value('cc', $this->_fromEmails);
      $sendTemplateParams['bcc'] = CRM_Utils_Array::value('bcc', $this->_fromEmails);
    }
    list($mailSent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
    return $mailSent;
  }

}
