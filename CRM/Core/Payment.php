<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
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

use Civi\Payment\System;
use Civi\Payment\Exception\PaymentProcessorException;

/**
 * Class CRM_Core_Payment.
 *
 * This class is the main class for the payment processor subsystem.
 *
 * It is the parent class for payment processors. It also holds some IPN related functions
 * that need to be moved. In particular handlePaymentMethod should be moved to a factory class.
 */
abstract class CRM_Core_Payment {

  /**
   * Component - ie. event or contribute.
   *
   * This is used for setting return urls.
   *
   * @var string
   */
  protected $_component;

  /**
   * How are we getting billing information.
   *
   * We are trying to completely deprecate these parameters.
   *
   * FORM   - we collect it on the same page
   * BUTTON - the processor collects it and sends it back to us via some protocol
   */
  const
    BILLING_MODE_FORM = 1,
    BILLING_MODE_BUTTON = 2,
    BILLING_MODE_NOTIFY = 4;

  /**
   * Which payment type(s) are we using?
   *
   * credit card
   * direct debit
   * or both
   * @todo create option group - nb omnipay uses a 3rd type - transparent redirect cc
   */
  const
    PAYMENT_TYPE_CREDIT_CARD = 1,
    PAYMENT_TYPE_DIRECT_DEBIT = 2;

  /**
   * Subscription / Recurring payment Status
   * START, END
   */
  const
    RECURRING_PAYMENT_START = 'START',
    RECURRING_PAYMENT_END = 'END';

  protected $_paymentProcessor;

  /**
   * Opportunity for the payment processor to override the entire form build.
   *
   * @param CRM_Core_Form $form
   *
   * @return bool
   *   Should form building stop at this point?
   */
  public function buildForm(&$form) {
    return FALSE;
  }

  /**
   * Log payment notification message to forensic system log.
   *
   * @todo move to factory class \Civi\Payment\System (or similar)
   *
   * @param array $params
   *
   * @return mixed
   */
  public static function logPaymentNotification($params) {
    $message = 'payment_notification ';
    if (!empty($params['processor_name'])) {
      $message .= 'processor_name=' . $params['processor_name'];
    }
    if (!empty($params['processor_id'])) {
      $message .= 'processor_id=' . $params['processor_id'];
    }

    $log = new CRM_Utils_SystemLogger();
    $log->alert($message, $_REQUEST);
  }

  /**
   * Check if capability is supported.
   *
   * Capabilities have a one to one relationship with capability-related functions on this class.
   *
   * Payment processor classes should over-ride the capability-specific function rather than this one.
   *
   * @param string $capability
   *   E.g BackOffice, LiveMode, FutureRecurStartDate.
   *
   * @return bool
   */
  public function supports($capability) {
    $function = 'supports' . ucfirst($capability);
    if (method_exists($this, $function)) {
      return $this->$function();
    }
    return FALSE;
  }

  /**
   * Are back office payments supported.
   *
   * e.g paypal standard won't permit you to enter a credit card associated
   * with someone else's login.
   * The intention is to support off-site (other than paypal) & direct debit but that is not all working yet so to
   * reach a 'stable' point we disable.
   *
   * @return bool
   */
  protected function supportsBackOffice() {
    if ($this->_paymentProcessor['billing_mode'] == 4 || $this->_paymentProcessor['payment_type'] != 1) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Can more than one transaction be processed at once?
   *
   * In general processors that process payment by server to server communication support this while others do not.
   *
   * In future we are likely to hit an issue where this depends on whether a token already exists.
   *
   * @return bool
   */
  protected function supportsMultipleConcurrentPayments() {
    if ($this->_paymentProcessor['billing_mode'] == 4 || $this->_paymentProcessor['payment_type'] != 1) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Are live payments supported - e.g dummy doesn't support this.
   *
   * @return bool
   */
  protected function supportsLiveMode() {
    return TRUE;
  }

  /**
   * Are test payments supported.
   *
   * @return bool
   */
  protected function supportsTestMode() {
    return TRUE;
  }

  /**
   * Should the first payment date be configurable when setting up back office recurring payments.
   *
   * We set this to false for historical consistency but in fact most new processors use tokens for recurring and can support this
   *
   * @return bool
   */
  protected function supportsFutureRecurStartDate() {
    return FALSE;
  }

  /**
   * Does this processor support pre-approval.
   *
   * This would generally look like a redirect to enter credentials which can then be used in a later payment call.
   *
   * Currently Paypal express supports this, with a redirect to paypal after the 'Main' form is submitted in the
   * contribution page. This token can then be processed at the confirm phase. Although this flow 'looks' like the
   * 'notify' flow a key difference is that in the notify flow they don't have to return but in this flow they do.
   *
   * @return bool
   */
  protected function supportsPreApproval() {
    return FALSE;
  }

  /**
   * Can recurring contributions be set against pledges.
   *
   * In practice all processors that use the baseIPN function to finish transactions or
   * call the completetransaction api support this by looking up previous contributions in the
   * series and, if there is a prior contribution against a pledge, and the pledge is not complete,
   * adding the new payment to the pledge.
   *
   * However, only enabling for processors it has been tested against.
   *
   * @return bool
   */
  protected function supportsRecurContributionsForPledges() {
    return FALSE;
  }

  /**
   * Function to action pre-approval if supported
   *
   * @param array $params
   *   Parameters from the form
   *
   * This function returns an array which should contain
   *   - pre_approval_parameters (this will be stored on the calling form & available later)
   *   - redirect_url (if set the browser will be redirected to this.
   */
  public function doPreApproval(&$params) {}

  /**
   * Get any details that may be available to the payment processor due to an approval process having happened.
   *
   * In some cases the browser is redirected to enter details on a processor site. Some details may be available as a
   * result.
   *
   * @param array $storedDetails
   *
   * @return array
   */
  public function getPreApprovalDetails($storedDetails) {
    return array();
  }

  /**
   * Default payment instrument validation.
   *
   * Implement the usual Luhn algorithm via a static function in the CRM_Core_Payment_Form if it's a credit card
   * Not a static function, because I need to check for payment_type.
   *
   * @param array $values
   * @param array $errors
   */
  public function validatePaymentInstrument($values, &$errors) {
    if ($this->_paymentProcessor['payment_type'] == 1) {
      CRM_Core_Payment_Form::validateCreditCard($values, $errors);
    }
  }

  /**
   * Getter for the payment processor.
   *
   * The payment processor array is based on the civicrm_payment_processor table entry.
   *
   * @return array
   *   Payment processor array.
   */
  public function getPaymentProcessor() {
    return $this->_paymentProcessor;
  }

  /**
   * Setter for the payment processor.
   *
   * @param array $processor
   */
  public function setPaymentProcessor($processor) {
    $this->_paymentProcessor = $processor;
  }

  /**
   * Setter for the payment form that wants to use the processor.
   *
   * @deprecated
   *
   * @param CRM_Core_Form $paymentForm
   */
  public function setForm(&$paymentForm) {
    $this->_paymentForm = $paymentForm;
  }

  /**
   * Getter for payment form that is using the processor.
   * @deprecated
   * @return CRM_Core_Form
   *   A form object
   */
  public function getForm() {
    return $this->_paymentForm;
  }

  /**
   * Getter for accessing member vars.
   *
   * @todo believe this is unused
   *
   * @param string $name
   *
   * @return null
   */
  public function getVar($name) {
    return isset($this->$name) ? $this->$name : NULL;
  }

  /**
   * Get name for the payment information type.
   * @todo - use option group + name field (like Omnipay does)
   * @return string
   */
  public function getPaymentTypeName() {
    return $this->_paymentProcessor['payment_type'] == 1 ? 'credit_card' : 'direct_debit';
  }

  /**
   * Get label for the payment information type.
   * @todo - use option group + labels (like Omnipay does)
   * @return string
   */
  public function getPaymentTypeLabel() {
    return $this->_paymentProcessor['payment_type'] == 1 ? 'Credit Card' : 'Direct Debit';
  }

  /**
   * Get array of fields that should be displayed on the payment form.
   * @todo make payment type an option value & use it in the function name - currently on debit & credit card work
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  public function getPaymentFormFields() {
    if ($this->_paymentProcessor['billing_mode'] == 4) {
      return array();
    }
    return $this->_paymentProcessor['payment_type'] == 1 ? $this->getCreditCardFormFields() : $this->getDirectDebitFormFields();
  }

  /**
   * Get array of fields that should be displayed on the payment form for credit cards.
   *
   * @return array
   */
  protected function getCreditCardFormFields() {
    return array(
      'credit_card_type',
      'credit_card_number',
      'cvv2',
      'credit_card_exp_date',
    );
  }

  /**
   * Get array of fields that should be displayed on the payment form for direct debits.
   *
   * @return array
   */
  protected function getDirectDebitFormFields() {
    return array(
      'account_holder',
      'bank_account_number',
      'bank_identification_number',
      'bank_name',
    );
  }

  /**
   * Return an array of all the details about the fields potentially required for payment fields.
   *
   * Only those determined by getPaymentFormFields will actually be assigned to the form
   *
   * @return array
   *   field metadata
   */
  public function getPaymentFormFieldsMetadata() {
    //@todo convert credit card type into an option value
    $creditCardType = array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::creditCard();
    return array(
      'credit_card_number' => array(
        'htmlType' => 'text',
        'name' => 'credit_card_number',
        'title' => ts('Card Number'),
        'cc_field' => TRUE,
        'attributes' => array(
          'size' => 20,
          'maxlength' => 20,
          'autocomplete' => 'off',
          'class' => 'creditcard',
        ),
        'is_required' => TRUE,
      ),
      'cvv2' => array(
        'htmlType' => 'text',
        'name' => 'cvv2',
        'title' => ts('Security Code'),
        'cc_field' => TRUE,
        'attributes' => array(
          'size' => 5,
          'maxlength' => 10,
          'autocomplete' => 'off',
        ),
        'is_required' => CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,
          'cvv_backoffice_required',
          NULL,
          1
        ),
        'rules' => array(
          array(
            'rule_message' => ts('Please enter a valid value for your card security code. This is usually the last 3-4 digits on the card\'s signature panel.'),
            'rule_name' => 'integer',
            'rule_parameters' => NULL,
          ),
        ),
      ),
      'credit_card_exp_date' => array(
        'htmlType' => 'date',
        'name' => 'credit_card_exp_date',
        'title' => ts('Expiration Date'),
        'cc_field' => TRUE,
        'attributes' => CRM_Core_SelectValues::date('creditCard'),
        'is_required' => TRUE,
        'rules' => array(
          array(
            'rule_message' => ts('Card expiration date cannot be a past date.'),
            'rule_name' => 'currentDate',
            'rule_parameters' => TRUE,
          ),
        ),
      ),
      'credit_card_type' => array(
        'htmlType' => 'select',
        'name' => 'credit_card_type',
        'title' => ts('Card Type'),
        'cc_field' => TRUE,
        'attributes' => $creditCardType,
        'is_required' => FALSE,
      ),
      'account_holder' => array(
        'htmlType' => 'text',
        'name' => 'account_holder',
        'title' => ts('Account Holder'),
        'cc_field' => TRUE,
        'attributes' => array(
          'size' => 20,
          'maxlength' => 34,
          'autocomplete' => 'on',
        ),
        'is_required' => TRUE,
      ),
      //e.g. IBAN can have maxlength of 34 digits
      'bank_account_number' => array(
        'htmlType' => 'text',
        'name' => 'bank_account_number',
        'title' => ts('Bank Account Number'),
        'cc_field' => TRUE,
        'attributes' => array(
          'size' => 20,
          'maxlength' => 34,
          'autocomplete' => 'off',
        ),
        'rules' => array(
          array(
            'rule_message' => ts('Please enter a valid Bank Identification Number (value must not contain punctuation characters).'),
            'rule_name' => 'nopunctuation',
            'rule_parameters' => NULL,
          ),
        ),
        'is_required' => TRUE,
      ),
      //e.g. SWIFT-BIC can have maxlength of 11 digits
      'bank_identification_number' => array(
        'htmlType' => 'text',
        'name' => 'bank_identification_number',
        'title' => ts('Bank Identification Number'),
        'cc_field' => TRUE,
        'attributes' => array(
          'size' => 20,
          'maxlength' => 11,
          'autocomplete' => 'off',
        ),
        'is_required' => TRUE,
        'rules' => array(
          array(
            'rule_message' => ts('Please enter a valid Bank Identification Number (value must not contain punctuation characters).'),
            'rule_name' => 'nopunctuation',
            'rule_parameters' => NULL,
          ),
        ),
      ),
      'bank_name' => array(
        'htmlType' => 'text',
        'name' => 'bank_name',
        'title' => ts('Bank Name'),
        'cc_field' => TRUE,
        'attributes' => array(
          'size' => 20,
          'maxlength' => 64,
          'autocomplete' => 'off',
        ),
        'is_required' => TRUE,

      ),
    );
  }

  /**
   * Get base url dependent on component.
   *
   * @return string|void
   */
  protected function getBaseReturnUrl() {
    if ($this->_component == 'event') {
      $baseURL = 'civicrm/event/register';
    }
    else {
      $baseURL = 'civicrm/contribute/transact';
    }
    return $baseURL;
  }

  /**
   * Get url to return to after cancelled or failed transaction
   *
   * @param $qfKey
   * @param $participantID
   *
   * @return string cancel url
   */
  protected function getCancelUrl($qfKey, $participantID) {
    if ($this->_component == 'event') {
      return CRM_Utils_System::url($this->getBaseReturnUrl(), array(
        'reset' => 1,
        'cc' => 'fail',
        'participantId' => $participantID,
      ),
        TRUE, NULL, FALSE
      );
    }

    return CRM_Utils_System::url($this->getBaseReturnUrl(), array(
      '_qf_Main_display' => 1,
      'qfKey' => $qfKey,
      'cancel' => 1,
    ),
      TRUE, NULL, FALSE
    );
  }

  /**
   * Get URL to return the browser to on success.
   *
   * @param $qfKey
   *
   * @return string
   */
  protected function getReturnSuccessUrl($qfKey) {
    return CRM_Utils_System::url($this->getBaseReturnUrl(), array(
      '_qf_ThankYou_display' => 1,
      'qfKey' => $qfKey,
    ),
      TRUE, NULL, FALSE
    );
  }

  /**
   * Get URL to return the browser to on failure.
   *
   * @param string $key
   * @param int $participantID
   * @param int $eventID
   *
   * @return string
   *   URL for a failing transactor to be redirected to.
   */
  protected function getReturnFailUrl($key, $participantID = NULL, $eventID = NULL) {
    $test = $this->_is_test ? '&action=preview' : '';
    if ($this->_component == "event") {
      return CRM_Utils_System::url('civicrm/event/register',
        "reset=1&cc=fail&participantId={$participantID}&id={$eventID}{$test}&qfKey={$key}",
        FALSE, NULL, FALSE
      );
    }
    else {
      return CRM_Utils_System::url('civicrm/contribute/transact',
        "_qf_Main_display=1&cancel=1&qfKey={$key}{$test}",
        FALSE, NULL, FALSE
      );
    }
  }

  /**
   * Get URl for when the back button is pressed.
   *
   * @param $qfKey
   *
   * @return string url
   */
  protected function getGoBackUrl($qfKey) {
    return CRM_Utils_System::url($this->getBaseReturnUrl(), array(
      '_qf_Confirm_display' => 'true',
      'qfKey' => $qfKey,
    ),
      TRUE, NULL, FALSE
    );
  }

  /**
   * Get the notify (aka ipn, web hook or silent post) url.
   *
   * If there is no '.' in it we assume that we are dealing with localhost or
   * similar and it is unreachable from the web & hence invalid.
   *
   * @return string
   *   URL to notify outcome of transaction.
   */
  protected function getNotifyUrl() {
    $url = CRM_Utils_System::url(
      'civicrm/payment/ipn/' . $this->_paymentProcessor['id'],
      array(),
      TRUE
    );
    return (stristr($url, '.')) ? $url : '';
  }

  /**
   * Calling this from outside the payment subsystem is deprecated - use doPayment.
   *
   * Does a server to server payment transaction.
   *
   * @param array $params
   *   Assoc array of input parameters for this transaction.
   *
   * @return array
   *   the result in an nice formatted array (or an error object - but throwing exceptions is preferred)
   */
  protected function doDirectPayment(&$params) {
    return $params;
  }

  /**
   * Process payment - this function wraps around both doTransferPayment and doDirectPayment.
   *
   * The function ensures an exception is thrown & moves some of this logic out of the form layer and makes the forms
   * more agnostic.
   *
   * Payment processors should set payment_status_id. This function adds some historical defaults ie. the
   * assumption that if a 'doDirectPayment' processors comes back it completed the transaction & in fact
   * doTransferCheckout would not traditionally come back.
   *
   * doDirectPayment does not do an immediate payment for Authorize.net or Paypal so the default is assumed
   * to be Pending.
   *
   * Once this function is fully rolled out then it will be preferred for processors to throw exceptions than to
   * return Error objects
   *
   * @param array $params
   *
   * @param string $component
   *
   * @return array
   *   Result array
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function doPayment(&$params, $component = 'contribute') {
    $this->_component = $component;
    $statuses = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id');
    if ($this->_paymentProcessor['billing_mode'] == 4) {
      $result = $this->doTransferCheckout($params, $component);
      if (is_array($result) && !isset($result['payment_status_id'])) {
        $result['payment_status_id'] = array_search('Pending', $statuses);
      }
    }
    else {
      if ($this->_paymentProcessor['billing_mode'] == 1) {
        $result = $this->doDirectPayment($params, $component);
      }
      else {
        $result = $this->doExpressCheckout($params);
      }
      if (is_array($result) && !isset($result['payment_status_id'])) {
        if (!empty($params['is_recur'])) {
          // See comment block.
          $result['payment_status_id'] = array_search('Pending', $statuses);
        }
        else {
          $result['payment_status_id'] = array_search('Completed', $statuses);
        }
      }
    }
    if (is_a($result, 'CRM_Core_Error')) {
      throw new PaymentProcessorException(CRM_Core_Error::getMessages($result));
    }
    return $result;
  }

  /**
   * This function checks to see if we have the right config values.
   *
   * @return string
   *   the error message if any
   */
  abstract protected function checkConfig();

  /**
   * Redirect for paypal.
   *
   * @todo move to paypal class or remove
   *
   * @param $paymentProcessor
   *
   * @return bool
   */
  public static function paypalRedirect(&$paymentProcessor) {
    if (!$paymentProcessor) {
      return FALSE;
    }

    if (isset($_GET['payment_date']) &&
      isset($_GET['merchant_return_link']) &&
      CRM_Utils_Array::value('payment_status', $_GET) == 'Completed' &&
      $paymentProcessor['payment_processor_type'] == "PayPal_Standard"
    ) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Handle incoming payment notification.
   *
   * IPNs, also called silent posts are notifications of payment outcomes or activity on an external site.
   *
   * @todo move to0 \Civi\Payment\System factory method
   * Page callback for civicrm/payment/ipn
   */
  public static function handleIPN() {
    self::handlePaymentMethod(
      'PaymentNotification',
      array(
        'processor_name' => @$_GET['processor_name'],
        'processor_id' => @$_GET['processor_id'],
        'mode' => @$_GET['mode'],
        'q' => @$_GET['q'],
      )
    );
    CRM_Utils_System::civiExit();
  }

  /**
   * Payment callback handler.
   *
   * The processor_name or processor_id is passed in.
   * Note that processor_id is more reliable as one site may have more than one instance of a
   * processor & ideally the processor will be validating the results
   * Load requested payment processor and call that processor's handle<$method> method
   *
   * @todo move to \Civi\Payment\System factory method
   *
   * @param string $method
   *   'PaymentNotification' or 'PaymentCron'
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public static function handlePaymentMethod($method, $params = array()) {
    if (!isset($params['processor_id']) && !isset($params['processor_name'])) {
      $q = explode('/', CRM_Utils_Array::value('q', $params, ''));
      $lastParam = array_pop($q);
      if (is_numeric($lastParam)) {
        $params['processor_id'] = $_GET['processor_id'] = $lastParam;
      }
      else {
        throw new CRM_Core_Exception("Either 'processor_id' (recommended) or 'processor_name' (deprecated) is required for payment callback.");
      }
    }

    self::logPaymentNotification($params);

    $sql = "SELECT ppt.class_name, ppt.name as processor_name, pp.id AS processor_id
              FROM civicrm_payment_processor_type ppt
        INNER JOIN civicrm_payment_processor pp
                ON pp.payment_processor_type_id = ppt.id
               AND pp.is_active";

    if (isset($params['processor_id'])) {
      $sql .= " WHERE pp.id = %2";
      $args[2] = array($params['processor_id'], 'Integer');
      $notFound = ts("No active instances of payment processor %1 were found.", array(1 => $params['processor_id']));
    }
    else {
      // This is called when processor_name is passed - passing processor_id instead is recommended.
      $sql .= " WHERE ppt.name = %2 AND pp.is_test = %1";
      $args[1] = array(
        (CRM_Utils_Array::value('mode', $params) == 'test') ? 1 : 0,
        'Integer',
      );
      $args[2] = array($params['processor_name'], 'String');
      $notFound = ts("No active instances of payment processor '%1' were found.", array(1 => $params['processor_name']));
    }

    $dao = CRM_Core_DAO::executeQuery($sql, $args);

    // Check whether we found anything at all.
    if (!$dao->N) {
      CRM_Core_Error::fatal($notFound);
    }

    $method = 'handle' . $method;
    $extension_instance_found = FALSE;

    // In all likelihood, we'll just end up with the one instance returned here. But it's
    // possible we may get more. Hence, iterate through all instances ..

    while ($dao->fetch()) {
      // Check pp is extension - is this still required - surely the singleton below handles it.
      $ext = CRM_Extension_System::singleton()->getMapper();
      if ($ext->isExtensionKey($dao->class_name)) {
        $paymentClass = $ext->keyToClass($dao->class_name, 'payment');
        require_once $ext->classToPath($paymentClass);
      }

      $processorInstance = Civi\Payment\System::singleton()->getById($dao->processor_id);

      // Should never be empty - we already established this processor_id exists and is active.
      if (empty($processorInstance)) {
        continue;
      }

      // Does PP implement this method, and can we call it?
      if (!method_exists($processorInstance, $method) ||
        !is_callable(array($processorInstance, $method))
      ) {
        // on the off chance there is a double implementation of this processor we should keep looking for another
        // note that passing processor_id is more reliable & we should work to deprecate processor_name
        continue;
      }

      // Everything, it seems, is ok - execute pp callback handler
      $processorInstance->$method();
      $extension_instance_found = TRUE;
    }

    if (!$extension_instance_found) {
      $message = "No extension instances of the '%1' payment processor were found.<br />" .
        "%2 method is unsupported in legacy payment processors.";
      CRM_Core_Error::fatal(ts($message, array(1 => $params['processor_name'], 2 => $method)));
    }
  }

  /**
   * Check whether a method is present ( & supported ) by the payment processor object.
   *
   * @param string $method
   *   Method to check for.
   *
   * @return bool
   */
  public function isSupported($method = 'cancelSubscription') {
    return method_exists(CRM_Utils_System::getClassName($this), $method);
  }

  /**
   * Some processors replace the form submit button with their own.
   *
   * Returning false here will leave the button off front end forms.
   *
   * At this stage there is zero cross-over between back-office processors and processors that suppress the submit.
   */
  public function isSuppressSubmitButtons() {
    return FALSE;
  }

  /**
   * Checks to see if invoice_id already exists in db.
   *
   * It's arguable if this belongs in the payment subsystem at all but since several processors implement it
   * it is better to standardise to being here.
   *
   * @param int $invoiceId The ID to check.
   *
   * @param null $contributionID
   *   If a contribution exists pass in the contribution ID.
   *
   * @return bool
   *   True if invoice ID otherwise exists, else false
   */
  protected function checkDupe($invoiceId, $contributionID = NULL) {
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->invoice_id = $invoiceId;
    if ($contributionID) {
      $contribution->whereAdd("id <> $contributionID");
    }
    return $contribution->find();
  }

  /**
   * Get url for users to manage this recurring contribution for this processor.
   *
   * @param int $entityID
   * @param null $entity
   * @param string $action
   *
   * @return string
   */
  public function subscriptionURL($entityID = NULL, $entity = NULL, $action = 'cancel') {
    // Set URL
    switch ($action) {
      case 'cancel':
        $url = 'civicrm/contribute/unsubscribe';
        break;

      case 'billing':
        //in notify mode don't return the update billing url
        if (!$this->isSupported('updateSubscriptionBillingInfo')) {
          return NULL;
        }
        $url = 'civicrm/contribute/updatebilling';
        break;

      case 'update':
        $url = 'civicrm/contribute/updaterecur';
        break;
    }

    $session = CRM_Core_Session::singleton();
    $userId = $session->get('userID');
    $contactID = 0;
    $checksumValue = '';
    $entityArg = '';

    // Find related Contact
    if ($entityID) {
      switch ($entity) {
        case 'membership':
          $contactID = CRM_Core_DAO::getFieldValue("CRM_Member_DAO_Membership", $entityID, "contact_id");
          $entityArg = 'mid';
          break;

        case 'contribution':
          $contactID = CRM_Core_DAO::getFieldValue("CRM_Contribute_DAO_Contribution", $entityID, "contact_id");
          $entityArg = 'coid';
          break;

        case 'recur':
          $sql = "
    SELECT con.contact_id
      FROM civicrm_contribution_recur rec
INNER JOIN civicrm_contribution con ON ( con.contribution_recur_id = rec.id )
     WHERE rec.id = %1
  GROUP BY rec.id";
          $contactID = CRM_Core_DAO::singleValueQuery($sql, array(1 => array($entityID, 'Integer')));
          $entityArg = 'crid';
          break;
      }
    }

    // Add entity arguments
    if ($entityArg != '') {
      // Add checksum argument
      if ($contactID != 0 && $userId != $contactID) {
        $checksumValue = '&cs=' . CRM_Contact_BAO_Contact_Utils::generateChecksum($contactID, NULL, 'inf');
      }
      return CRM_Utils_System::url($url, "reset=1&{$entityArg}={$entityID}{$checksumValue}", TRUE, NULL, FALSE, TRUE);
    }

    // Else login URL
    if ($this->isSupported('accountLoginURL')) {
      return $this->accountLoginURL();
    }

    // Else default
    return isset($this->_paymentProcessor['url_recur']) ? $this->_paymentProcessor['url_recur'] : '';
  }

}
