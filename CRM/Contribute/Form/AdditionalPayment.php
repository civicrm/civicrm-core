<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This form records additional payments needed when event/contribution is partially paid.
 */
class CRM_Contribute_Form_AdditionalPayment extends CRM_Contribute_Form_AbstractEditPayment {
  public $_contributeMode = 'direct';

  /**
   * Id of the component entity
   */
  public $_id = NULL;

  protected $entity = 'Contribution';

  protected $_owed = NULL;

  protected $_refund = NULL;

  /**
   * @deprecated - use parent $this->contactID
   *
   * @var int
   */
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
    parent::preProcess();
    $this->_contactId = $this->_contactID;
    $this->_component = CRM_Utils_Request::retrieve('component', 'String', $this, FALSE, 'contribution');
    $this->_view = CRM_Utils_Request::retrieve('view', 'String', $this, FALSE);
    $this->assign('component', $this->_component);
    $this->assign('id', $this->_id);
    $this->assign('suppressPaymentFormButtons', $this->isBeingCalledFromSelectorContext());

    if ($this->_view == 'transaction' && ($this->_action & CRM_Core_Action::BROWSE)) {
      $title = $this->assignPaymentInfoBlock();
      CRM_Utils_System::setTitle($title);
      return;
    }
    $this->_fromEmails = CRM_Core_BAO_Email::getFromEmail();

    $entityType = 'contribution';
    if ($this->_component == 'event') {
      $entityType = 'participant';
      $this->_contributionId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $this->_id, 'contribution_id', 'participant_id');
      $eventId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $this->_id, 'event_id', 'id');
      $this->_fromEmails = CRM_Event_BAO_Event::getFromEmailIds($eventId);
    }
    else {
      $this->_contributionId = $this->_id;
      $this->_fromEmails['from_email_id'] = CRM_Core_BAO_Email::getFromEmail();
    }

    $paymentInfo = CRM_Core_BAO_FinancialTrxn::getPartialPaymentWithType($this->_id, $entityType);
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

    if (!empty($this->_mode) && $this->_paymentType == 'refund') {
      CRM_Core_Error::fatal(ts('Credit card payment is not for Refund payments use'));
    }

    list($this->_contributorDisplayName, $this->_contributorEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);

    $this->assign('contributionMode', $this->_mode);
    $this->assign('contactId', $this->_contactID);
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
      CRM_Core_Payment_Form::setDefaultValues($this, $this->_contactId);
      $defaults = array_merge($defaults, $this->_defaults);
    }

    if (empty($defaults['trxn_date'])) {
      $defaults['trxn_date'] = date('Y-m-d H:i:s');
    }

    if ($this->_refund) {
      $defaults['total_amount'] = CRM_Utils_Money::format(abs($this->_refund), NULL, NULL, TRUE);
    }
    elseif ($this->_owed) {
      $defaults['total_amount'] = number_format($this->_owed, 2);
    }

    // Set $newCredit variable in template to control whether link to credit card mode is included
    $this->assign('newCredit', CRM_Core_Config::isEnabledBackOfficeCreditCardPayments());
    return $defaults;
  }

  /**
   * Build the form object.
   */
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

    CRM_Core_Payment_Form::buildPaymentForm($this, $this->_paymentProcessor, FALSE, TRUE, CRM_Utils_Request::retrieve('payment_instrument_id', 'Integer'));
    $this->add('select', 'payment_processor_id', ts('Payment Processor'), $this->_processors, NULL);

    $attributes = CRM_Core_DAO::getAttribute('CRM_Financial_DAO_FinancialTrxn');

    $label = ($this->_refund) ? ts('Refund Amount') : ts('Payment Amount');
    $this->addMoney('total_amount',
      $label,
      TRUE,
      $attributes['total_amount'],
      TRUE, 'currency', NULL
    );

    //add receipt for offline contribution
    $this->addElement('checkbox', 'is_email_receipt', ts('Send Receipt?'));

    $this->add('select', 'from_email_address', ts('Receipt From'), $this->_fromEmails['from_email_id']);

    $this->add('textarea', 'receipt_text', ts('Confirmation Message'));

    $dateLabel = ($this->_refund) ? ts('Refund Date') : ts('Date Received');
    $this->addField('trxn_date', array('entity' => 'FinancialTrxn', 'label' => $dateLabel, 'context' => 'Contribution'), FALSE, FALSE);

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

    $js = NULL;
    // render backoffice payment fields only on offline mode
    if (!$this->_mode) {
      $js = array('onclick' => "return verify( );");

      $this->add('select', 'payment_instrument_id',
        ts('Payment Method'),
        array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::paymentInstrument(),
        TRUE,
        array('onChange' => "return showHideByValue('payment_instrument_id','4','checkNumber','table-row','select',false);")
      );

      $this->add('text', 'check_number', ts('Check Number'), $attributes['financial_trxn_check_number']);
      $this->add('text', 'trxn_id', ts('Transaction ID'), array('class' => 'twelve') + $attributes['trxn_id']);

      $this->add('text', 'fee_amount', ts('Fee Amount'),
        $attributes['fee_amount']
      );
      $this->addRule('fee_amount', ts('Please enter a valid monetary value for Fee Amount.'), 'money');

      $this->add('text', 'net_amount', ts('Net Amount'),
        $attributes['net_amount']
      );
      $this->addRule('net_amount', ts('Please enter a valid monetary value for Net Amount.'), 'money');
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
    $netAmt = $fields['total_amount'] - CRM_Utils_Array::value('fee_amount', $fields, 0);
    if (!empty($fields['net_amount']) && $netAmt != $fields['net_amount']) {
      $errors['net_amount'] = ts('Net amount should be equal to the difference between payment amount and fee amount.');
    }
    if ($self->_paymentProcessor['id'] === 0 && empty($fields['payment_instrument_id'])) {
      $errors['payment_instrument_id'] = ts('Payment method is a required field');
    }

    return $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $submittedValues = $this->controller->exportValues($this->_name);
    $this->submit($submittedValues);
    $childTab = 'contribute';
    if ($this->_component == 'event') {
      $childTab = 'participant';
    }
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
      "reset=1&cid={$this->_contactId}&selectedChild={$childTab}"
    ));
  }

  /**
   * Process Payments.
   * @param array $submittedValues
   *
   */
  public function submit($submittedValues) {
    $this->_params = $submittedValues;
    $this->beginPostProcess();
    $this->_contributorContactID = $this->_contactID;
    $this->processBillingAddress();
    $participantId = NULL;
    if ($this->_component == 'event') {
      $participantId = $this->_id;
    }
    $contributionStatuses = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution',
      'contribution_status_id',
      array('labelColumn' => 'name')
    );
    $contributionStatusID = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $this->_contributionId, 'contribution_status_id');
    if ($contributionStatuses[$contributionStatusID] == 'Pending') {
      civicrm_api3('Contribution', 'create',
        array(
          'id' => $this->_contributionId,
          'contribution_status_id' => array_search('Partially paid', $contributionStatuses),
          'is_pay_later' => 0,
        )
      );
    }

    if ($this->_mode) {
      // process credit card
      $this->assign('contributeMode', 'direct');
      $this->processCreditCard();
    }

    $defaults = array();
    $contribution = civicrm_api3('Contribution', 'getsingle', array(
      'return' => array("contribution_status_id"),
      'id' => $this->_contributionId,
    ));
    $contributionStatusId = CRM_Utils_Array::value('contribution_status_id', $contribution);
    $result = CRM_Contribute_BAO_Contribution::recordAdditionalPayment($this->_contributionId, $this->_params, $this->_paymentType, $participantId);
    // Fetch the contribution & do proportional line item assignment
    $params = array('id' => $this->_contributionId);
    $contribution = CRM_Contribute_BAO_Contribution::retrieve($params, $defaults, $params);
    CRM_Contribute_BAO_Contribution::addPayments(array($contribution), $contributionStatusId);
    if ($this->_contributionId && CRM_Core_Permission::access('CiviMember')) {
      $membershipPaymentCount = civicrm_api3('MembershipPayment', 'getCount', array('contribution_id' => $this->_contributionId));
      if ($membershipPaymentCount) {
        $this->ajaxResponse['updateTabs']['#tab_member'] = CRM_Contact_BAO_Contact::getCountComponent('membership', $this->_contactID);
      }
    }
    if ($this->_contributionId && CRM_Core_Permission::access('CiviEvent')) {
      $participantPaymentCount = civicrm_api3('ParticipantPayment', 'getCount', array('contribution_id' => $this->_contributionId));
      if ($participantPaymentCount) {
        $this->ajaxResponse['updateTabs']['#tab_participant'] = CRM_Contact_BAO_Contact::getCountComponent('participant', $this->_contactID);
      }
    }

    $statusMsg = ts('The payment record has been processed.');
    // send email
    if (!empty($result) && !empty($this->_params['is_email_receipt'])) {
      $this->_params['contact_id'] = $this->_contactId;
      $this->_params['contribution_id'] = $this->_contributionId;
      // to get 'from email id' for send receipt
      $this->fromEmailId = $this->_params['from_email_address'];
      $sendReceipt = $this->emailReceipt($this->_params);
      if ($sendReceipt) {
        $statusMsg .= ' ' . ts('A receipt has been emailed to the contributor.');
      }
    }

    CRM_Core_Session::setStatus($statusMsg, ts('Saved'), 'success');
  }

  public function processCreditCard() {
    $config = CRM_Core_Config::singleton();
    $session = CRM_Core_Session::singleton();

    $now = date('YmdHis');
    $fields = array();

    // we need to retrieve email address
    if ($this->_context == 'standalone' && !empty($this->_params['is_email_receipt'])) {
      list($this->userDisplayName,
        $this->userEmail
        ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactId);
      $this->assign('displayName', $this->userDisplayName);
    }

    $this->formatParamsForPaymentProcessor($this->_params);

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
      $this->_params['receive_date'] = $this->_params['trxn_date'];
    }

    if (empty($this->_params['receive_date'])) {
      $this->_params['receive_date'] = date('YmdHis');
    }

    if (empty($this->_params['invoice_id'])) {
      $this->_params['invoiceID'] = md5(uniqid(rand(), TRUE));
    }
    else {
      $this->_params['invoiceID'] = $this->_params['invoice_id'];
    }

    $this->assign('address', CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters(
      $this->_params,
      $this->_bltID
    ));

    //Add common data to formatted params
    $params = $this->_params;
    CRM_Contribute_Form_AdditionalInfo::postProcessCommon($params, $this->_params, $this);
    // at this point we've created a contact and stored its address etc
    // all the payment processors expect the name and address to be in the
    // so we copy stuff over to first_name etc.
    $paymentParams = $this->_params;
    $paymentParams['contactID'] = $this->_contactId;
    CRM_Core_Payment_Form::mapParams($this->_bltID, $this->_params, $paymentParams, TRUE);

    $paymentParams['contributionPageID'] = NULL;
    if (!empty($this->_params['is_email_receipt'])) {
      $paymentParams['email'] = $this->_contributorEmail;
      $paymentParams['is_email_receipt'] = TRUE;
    }
    else {
      $paymentParams['is_email_receipt'] = $this->_params['is_email_receipt'] = FALSE;
    }

    $result = NULL;

    if ($paymentParams['amount'] > 0.0) {
      try {
        // force a reget of the payment processor in case the form changed it, CRM-7179
        $payment = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
        $result = $payment->doPayment($paymentParams);
      }
      catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
        Civi::log()->error('Payment processor exception: ' . $e->getMessage());
        $urlParams = "action=add&cid={$this->_contactId}&id={$this->_contributionId}&component={$this->_component}&mode={$this->_mode}";
        CRM_Core_Error::statusBounce(CRM_Utils_System::url($e->getMessage(), 'civicrm/payment/add', $urlParams));
      }
    }

    if (!empty($result)) {
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
  }

  /**
   * Function to send email receipt.
   *
   * @param array $params
   *
   * @return bool
   */
  public function emailReceipt(&$params) {
    // email receipt sending
    // send message template
    if ($this->_component == 'event') {

      // fetch event information from participant ID using API
      $eventId = civicrm_api3('Participant', 'getvalue', array(
        'return' => "event_id",
        'id' => $this->_id,
      ));
      $event = civicrm_api3('Event', 'getsingle', array('id' => $eventId));

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

    $this->assign('totalAmount', $this->_amtTotal);

    $isRefund = ($this->_paymentType == 'refund') ? TRUE : FALSE;
    $this->assign('isRefund', $isRefund);
    if ($isRefund) {
      $this->assign('totalPaid', $this->_amtPaid);
      $this->assign('refundAmount', $params['total_amount']);
    }
    else {
      $balance = $this->_amtTotal - ($this->_amtPaid + $params['total_amount']);
      $paymentsComplete = ($balance == 0) ? 1 : 0;
      $this->assign('amountOwed', $balance);
      $this->assign('paymentAmount', $params['total_amount']);
      $this->assign('paymentsComplete', $paymentsComplete);
    }
    $this->assign('contactDisplayName', $this->_contributorDisplayName);

    // assign trxn details
    $this->assign('trxn_id', CRM_Utils_Array::value('trxn_id', $params));
    $this->assign('receive_date', CRM_Utils_Array::value('trxn_date', $params));
    $this->assign('paidBy', CRM_Core_PseudoConstant::getLabel(
      'CRM_Contribute_BAO_Contribution',
      'payment_instrument_id',
      $params['payment_instrument_id']
    ));
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
    }
    list($mailSent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
    return $mailSent;
  }

  /**
   * Wrapper for unit testing the post process submit function.
   *
   * @param array $params
   * @param string|null $creditCardMode
   * @param string $entityType
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmit($params, $creditCardMode = NULL, $entityType = 'contribute') {
    $this->_bltID = 5;
    // Required because processCreditCard calls set method on this.
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $this->controller = new CRM_Core_Controller();

    $this->assignPaymentRelatedVariables();

    if (!empty($params['contribution_id'])) {
      $this->_contributionId = $params['contribution_id'];

      $paymentInfo = CRM_Core_BAO_FinancialTrxn::getPartialPaymentWithType($this->_contributionId, $entityType);
      $paymentDetails = CRM_Contribute_BAO_Contribution::getPaymentInfo($this->_contributionId, $entityType, FALSE, TRUE);

      $this->_amtPaid = $paymentDetails['paid'];
      $this->_amtTotal = $paymentDetails['total'];

      if (!empty($paymentInfo['refund_due'])) {
        $this->_refund = $paymentInfo['refund_due'];
        $this->_paymentType = 'refund';
      }
      elseif (!empty($paymentInfo['amount_owed'])) {
        $this->_owed = $paymentInfo['amount_owed'];
        $this->_paymentType = 'owed';
      }
    }

    if (!empty($params['contact_id'])) {
      $this->_contactId = $params['contact_id'];
    }

    if ($creditCardMode) {
      $this->_mode = $creditCardMode;
    }

    $this->_fields = array();
    $this->set('cid', $this->_contactId);
    parent::preProcess();
    $this->submit($params);
  }

}
