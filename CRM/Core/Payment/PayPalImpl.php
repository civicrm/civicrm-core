<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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

use Civi\Payment\Exception\PaymentProcessorException;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * Class CRM_Core_Payment_PayPalImpl for paypal pro, paypal standard & paypal express.
 */
class CRM_Core_Payment_PayPalImpl extends CRM_Core_Payment {
  const CHARSET = 'iso-8859-1';

  protected $_mode = NULL;

  /**
   * Constructor.
   *
   * @param string $mode
   *   The mode of operation: live or test.
   *
   * @param CRM_Core_Payment $paymentProcessor
   *
   * @return \CRM_Core_Payment_PayPalImpl
   */
  public function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('PayPal Pro');
    $paymentProcessorType = CRM_Core_PseudoConstant::paymentProcessorType(FALSE, NULL, 'name');

    if ($this->_paymentProcessor['payment_processor_type_id'] == CRM_Utils_Array::key('PayPal_Standard', $paymentProcessorType)) {
      $this->_processorName = ts('PayPal Standard');
      return;
    }
    elseif ($this->_paymentProcessor['payment_processor_type_id'] == CRM_Utils_Array::key('PayPal_Express', $paymentProcessorType)) {
      $this->_processorName = ts('PayPal Express');
    }

  }

  /**
   * Are back office payments supported.
   *
   * E.g paypal standard won't permit you to enter a credit card associated
   * with someone else's login.
   *
   * @return bool
   */
  protected function supportsBackOffice() {
    if ($this->_processorName == ts('PayPal Pro')) {
      return TRUE;
    }
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
    if ($this->_processorName == ts('PayPal Express') || $this->_processorName == ts('PayPal Pro')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Opportunity for the payment processor to override the entire form build.
   *
   * @param CRM_Core_Form $form
   *
   * @return bool
   *   Should form building stop at this point?
   */
  public function buildForm(&$form) {
    if ($this->_processorName == 'PayPal Express' || $this->_processorName == 'PayPal Pro') {
      $this->addPaypalExpressCode($form);
      if ($this->_processorName == 'PayPal Express') {
        CRM_Core_Region::instance('billing-block-post')->add(array(
          'template' => 'CRM/Financial/Form/PaypalExpress.tpl',
          'name' => 'paypal_express',
        ));
      }
      if ($this->_processorName == 'PayPal Pro') {
        CRM_Core_Region::instance('billing-block-pre')->add(array(
          'template' => 'CRM/Financial/Form/PaypalPro.tpl',
        ));
      }
    }
    return FALSE;
  }

  /**
   * Billing mode button is basically synonymous with paypal express.
   *
   * This is probably a good example of 'odds & sods' code we
   * need to find a way for the payment processor to assign.
   *
   * A tricky aspect is that the payment processor may need to set the order
   *
   * @param CRM_Core_Form $form
   */
  protected function addPaypalExpressCode(&$form) {
    if (empty($form->isBackOffice)) {
      $form->_expressButtonName = $form->getButtonName('upload', 'express');
      $form->assign('expressButtonName', $form->_expressButtonName);
      $form->add(
        'image',
        $form->_expressButtonName,
        $this->_paymentProcessor['url_button'],
        array('class' => 'crm-form-submit')
      );
    }
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
    return TRUE;
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
    if ($this->_paymentProcessor['payment_processor_type'] == 'PayPal' && !$this->isPaypalExpress($values)) {
      CRM_Core_Payment_Form::validateCreditCard($values, $errors);
      CRM_Core_Form::validateMandatoryFields($this->getMandatoryFields(), $values, $errors);
    }
  }

  /**
   * Express checkout code.
   *
   * Check PayPal documentation for more information
   *
   * @param array $params
   *   Assoc array of input parameters for this transaction.
   *
   * @return array
   *   the result in an nice formatted array (or an error object)
   */
  protected function setExpressCheckOut(&$params) {
    $args = array();

    $this->initialize($args, 'SetExpressCheckout');

    $args['paymentAction'] = 'Sale';
    $args['amt'] = $params['amount'];
    $args['currencyCode'] = $params['currencyID'];
    $args['desc'] = CRM_Utils_Array::value('description', $params);
    $args['invnum'] = $params['invoiceID'];
    $args['returnURL'] = $this->getReturnSuccessUrl($params['qfKey']);
    $args['cancelURL'] = $this->getCancelUrl($params['qfKey'], NULL);
    $args['version'] = '56.0';

    //LCD if recurring, collect additional data and set some values
    if (!empty($params['is_recur'])) {
      $args['L_BILLINGTYPE0'] = 'RecurringPayments';
      //$args['L_BILLINGAGREEMENTDESCRIPTION0'] = 'Recurring Contribution';
      $args['L_BILLINGAGREEMENTDESCRIPTION0'] = $params['amount'] . " Per " . $params['frequency_interval'] . " " . $params['frequency_unit'];
      $args['L_PAYMENTTYPE0'] = 'Any';
    }

    // Allow further manipulation of the arguments via custom hooks ..
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $args);

    $result = $this->invokeAPI($args);

    if (is_a($result, 'CRM_Core_Error')) {
      throw new PaymentProcessorException($result->message);
    }

    /* Success */

    return $result['token'];
  }

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
    return empty($storedDetails['token']) ? array() : $this->getExpressCheckoutDetails($storedDetails['token']);
  }

  /**
   * Get details from paypal.
   *
   * Check PayPal documentation for more information
   *
   * @param string $token
   *   The key associated with this transaction.
   *
   * @return array
   *   the result in an nice formatted array (or an error object)
   */
  public function getExpressCheckoutDetails($token) {
    $args = array();

    $this->initialize($args, 'GetExpressCheckoutDetails');
    $args['token'] = $token;
    // LCD
    $args['method'] = 'GetExpressCheckoutDetails';

    $result = $this->invokeAPI($args);

    if (is_a($result, 'CRM_Core_Error')) {
      throw new PaymentProcessorException(CRM_Core_Error::getMessages($result));
    }

    /* Success */
    $fieldMap = array(
      'token' => 'token',
      'payer_status' => 'payerstatus',
      'payer_id' => 'payerid',
      'first_name' => 'firstname',
      'middle_name' => 'middlename',
      'last_name' => 'lastname',
      'street_address' => 'shiptostreet',
      'supplemental_address_1' => 'shiptostreet2',
      'city' => 'shiptocity',
      'postal_code' => 'shiptozip',
      'state_province' => 'shiptostate',
      'country' => 'shiptocountrycode',
    );
    return $this->mapPaypalParamsToCivicrmParams($fieldMap, $result);
  }

  /**
   * Do the express checkout at paypal.
   *
   * Check PayPal documentation for more information
   *
   * @param array $params
   *
   * @return array
   *   The result in an nice formatted array.
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function doExpressCheckout(&$params) {
    $statuses = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id');
    if (!empty($params['is_recur'])) {
      return $this->createRecurringPayments($params);
    }
    $args = array();

    $this->initialize($args, 'DoExpressCheckoutPayment');
    $args['token'] = $params['token'];
    $args['paymentAction'] = 'Sale';
    $args['amt'] = $params['amount'];
    $args['currencyCode'] = $params['currencyID'];
    $args['payerID'] = $params['payer_id'];
    $args['invnum'] = $params['invoiceID'];
    $args['returnURL'] = $this->getReturnSuccessUrl($params['qfKey']);
    $args['cancelURL'] = $this->getCancelUrl($params['qfKey'], NULL);
    $args['desc'] = $params['description'];

    // add CiviCRM BN code
    $args['BUTTONSOURCE'] = 'CiviCRM_SP';

    $result = $this->invokeAPI($args);

    if (is_a($result, 'CRM_Core_Error')) {
      throw new PaymentProcessorException(CRM_Core_Error::getMessages($result));
    }

    /* Success */

    $params['trxn_id'] = $result['transactionid'];
    $params['gross_amount'] = $result['amt'];
    $params['fee_amount'] = $result['feeamt'];
    $params['net_amount'] = CRM_Utils_Array::value('settleamt', $result);
    if ($params['net_amount'] == 0 && $params['fee_amount'] != 0) {
      $params['net_amount'] = number_format(($params['gross_amount'] - $params['fee_amount']), 2);
    }
    $params['payment_status'] = $result['paymentstatus'];
    $params['pending_reason'] = $result['pendingreason'];
    if (!empty($params['is_recur'])) {
      // See comment block.
      $result['payment_status_id'] = array_search('Pending', $statuses);
    }
    else {
      $result['payment_status_id'] = array_search('Completed', $statuses);
    }
    return $params;
  }

  /**
   * Create recurring payments.
   *
   * @param array $params
   *
   * @return mixed
   */
  public function createRecurringPayments(&$params) {
    $args = array();
    // @todo this function is riddled with enotices - perhaps use $this->mapPaypalParamsToCivicrmParams($fieldMap, $result)
    $this->initialize($args, 'CreateRecurringPaymentsProfile');

    $start_time = strtotime(date('m/d/Y'));
    $start_date = date('Y-m-d\T00:00:00\Z', $start_time);

    $args['token'] = $params['token'];
    $args['paymentAction'] = 'Sale';
    $args['amt'] = $params['amount'];
    $args['currencyCode'] = $params['currencyID'];
    $args['payerID'] = $params['payer_id'];
    $args['invnum'] = $params['invoiceID'];
    $args['returnURL'] = $params['returnURL'];
    $args['cancelURL'] = $params['cancelURL'];
    $args['profilestartdate'] = $start_date;
    $args['method'] = 'CreateRecurringPaymentsProfile';
    $args['billingfrequency'] = $params['frequency_interval'];
    $args['billingperiod'] = ucwords($params['frequency_unit']);
    $args['desc'] = $params['amount'] . " Per " . $params['frequency_interval'] . " " . $params['frequency_unit'];
    //$args['desc']           = 'Recurring Contribution';
    $args['totalbillingcycles'] = $params['installments'];
    $args['version'] = '56.0';
    $args['profilereference'] = "i={$params['invoiceID']}" .
      "&m=" .
      "&c={$params['contactID']}" .
      "&r={$params['contributionRecurID']}" .
      "&b={$params['contributionID']}" .
      "&p={$params['contributionPageID']}";

    // add CiviCRM BN code
    $args['BUTTONSOURCE'] = 'CiviCRM_SP';

    $result = $this->invokeAPI($args);

    if (is_a($result, 'CRM_Core_Error')) {
      return $result;
    }

    /* Success */
    $params['trxn_id'] = $result['transactionid'];
    $params['gross_amount'] = $result['amt'];
    $params['fee_amount'] = $result['feeamt'];
    $params['net_amount'] = $result['settleamt'];
    if ($params['net_amount'] == 0 && $params['fee_amount'] != 0) {
      $params['net_amount'] = number_format(($params['gross_amount'] - $params['fee_amount']), 2);
    }
    $params['payment_status'] = $result['paymentstatus'];
    $params['pending_reason'] = $result['pendingreason'];

    return $params;
  }

  /**
   * Initialise.
   *
   * @param $args
   * @param $method
   */
  public function initialize(&$args, $method) {
    $args['user'] = $this->_paymentProcessor['user_name'];
    $args['pwd'] = $this->_paymentProcessor['password'];
    $args['version'] = 3.0;
    $args['signature'] = $this->_paymentProcessor['signature'];
    $args['subject'] = CRM_Utils_Array::value('subject', $this->_paymentProcessor);
    $args['method'] = $method;
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
    if ($this->_paymentProcessor['payment_processor_type'] == 'PayPal_Express'
      || ($this->_paymentProcessor['payment_processor_type'] == 'PayPal' && !empty($params['token']))
    ) {
      $this->_component = $component;
      return $this->doExpressCheckout($params);

    }
    return parent::doPayment($params, $component);
  }

  /**
   * This function collects all the information from a web/api form and invokes
   * the relevant payment processor specific functions to perform the transaction
   *
   * @param array $params
   *   Assoc array of input parameters for this transaction.
   *
   * @param string $component
   * @return array
   *   the result in an nice formatted array (or an error object)
   */
  public function doDirectPayment(&$params, $component = 'contribute') {
    $args = array();

    $this->initialize($args, 'DoDirectPayment');

    $args['paymentAction'] = 'Sale';
    $args['amt'] = $params['amount'];
    $args['currencyCode'] = $params['currencyID'];
    $args['invnum'] = $params['invoiceID'];
    $args['ipaddress'] = $params['ip_address'];
    $args['creditCardType'] = $params['credit_card_type'];
    $args['acct'] = $params['credit_card_number'];
    $args['expDate'] = sprintf('%02d', $params['month']) . $params['year'];
    $args['cvv2'] = $params['cvv2'];
    $args['firstName'] = $params['first_name'];
    $args['lastName'] = $params['last_name'];
    $args['email'] = CRM_Utils_Array::value('email', $params);
    $args['street'] = $params['street_address'];
    $args['city'] = $params['city'];
    $args['state'] = $params['state_province'];
    $args['countryCode'] = $params['country'];
    $args['zip'] = $params['postal_code'];
    $args['desc'] = substr(CRM_Utils_Array::value('description', $params), 0, 127);
    $args['custom'] = CRM_Utils_Array::value('accountingCode', $params);

    // add CiviCRM BN code
    $args['BUTTONSOURCE'] = 'CiviCRM_SP';

    if (CRM_Utils_Array::value('is_recur', $params) == 1) {
      $start_time = strtotime(date('m/d/Y'));
      $start_date = date('Y-m-d\T00:00:00\Z', $start_time);

      $args['PaymentAction'] = 'Sale';
      $args['billingperiod'] = ucwords($params['frequency_unit']);
      $args['billingfrequency'] = $params['frequency_interval'];
      $args['method'] = "CreateRecurringPaymentsProfile";
      $args['profilestartdate'] = $start_date;
      $args['desc'] = "" .
        $params['description'] . ": " .
        $params['amount'] . " Per " .
        $params['frequency_interval'] . " " .
        $params['frequency_unit'];
      $args['amt'] = $params['amount'];
      $args['totalbillingcycles'] = $params['installments'];
      $args['version'] = 56.0;
      $args['PROFILEREFERENCE'] = "" .
        "i=" . $params['invoiceID'] . "&m=" . $component .
        "&c=" . $params['contactID'] . "&r=" . $params['contributionRecurID'] .
        "&b=" . $params['contributionID'] . "&p=" . $params['contributionPageID'];
    }

    // Allow further manipulation of the arguments via custom hooks ..
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $args);

    $result = $this->invokeAPI($args);

    //WAG
    if (is_a($result, 'CRM_Core_Error')) {
      return $result;
    }

    $params['recurr_profile_id'] = NULL;

    if (CRM_Utils_Array::value('is_recur', $params) == 1) {
      $params['recurr_profile_id'] = $result['profileid'];
    }

    /* Success */

    $params['trxn_id'] = CRM_Utils_Array::value('transactionid', $result);
    $params['gross_amount'] = CRM_Utils_Array::value('amt', $result);
    $params = array_merge($params, $this->doQuery($params));
    return $params;
  }

  /**
   * Query payment processor for details about a transaction.
   *
   * For paypal see : https://developer.paypal.com/webapps/developer/docs/classic/api/merchant/GetTransactionDetails_API_Operation_NVP/
   *
   * @param array $params
   *   Array of parameters containing one of:
   *   - trxn_id Id of an individual transaction.
   *   - processor_id Id of a recurring contribution series as stored in the civicrm_contribution_recur table.
   *
   * @return array
   *   Extra parameters retrieved.
   *   Any parameters retrievable through this should be documented in the function comments at
   *   CRM_Core_Payment::doQuery. Currently
   *   - fee_amount Amount of fee paid
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function doQuery($params) {
    if (empty($params['trxn_id'])) {
      throw new \Civi\Payment\Exception\PaymentProcessorException('transaction id not set');
    }
    $args = array(
      'TRANSACTIONID' => $params['trxn_id'],
    );
    $this->initialize($args, 'GetTransactionDetails');
    $result = $this->invokeAPI($args);
    return array(
      'fee_amount' => $result['feeamt'],
      'net_amount' => $params['gross_amount'] - $result['feeamt'],
    );
  }

  /**
   * This function checks to see if we have the right config values.
   *
   * @return string
   *   the error message if any
   */
  public function checkConfig() {
    $error = array();
    $paymentProcessorType = CRM_Core_PseudoConstant::paymentProcessorType(FALSE, NULL, 'name');

    if ($this->_paymentProcessor['payment_processor_type_id'] != CRM_Utils_Array::key('PayPal_Standard', $paymentProcessorType)) {
      if (empty($this->_paymentProcessor['signature'])) {
        $error[] = ts('Signature is not set in the Administer &raquo; System Settings &raquo; Payment Processors.');
      }

      if (empty($this->_paymentProcessor['password'])) {
        $error[] = ts('Password is not set in the Administer &raquo; System Settings &raquo; Payment Processors.');
      }
    }
    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('User Name is not set in the Administer &raquo; System Settings &raquo; Payment Processors.');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  /**
   * @return null|string
   */
  public function cancelSubscriptionURL() {
    if ($this->_paymentProcessor['payment_processor_type'] == 'PayPal_Standard') {
      return "{$this->_paymentProcessor['url_site']}cgi-bin/webscr?cmd=_subscr-find&alias=" . urlencode($this->_paymentProcessor['user_name']);
    }
    else {
      return NULL;
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
  public function isSupported($method) {
    if ($this->_paymentProcessor['payment_processor_type'] != 'PayPal') {
      // since subscription methods like cancelSubscription or updateBilling is not yet implemented / supported
      // by standard or express.
      return FALSE;
    }
    return parent::isSupported($method);
  }

  /**
   * Paypal express replaces the submit button with it's own.
   *
   * @return bool
   *   Should the form button by suppressed?
   */
  public function isSuppressSubmitButtons() {
    if ($this->_paymentProcessor['payment_processor_type'] == 'PayPal_Express') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param string $message
   * @param array $params
   *
   * @return array|bool|object
   */
  public function cancelSubscription(&$message = '', $params = array()) {
    if ($this->_paymentProcessor['payment_processor_type'] == 'PayPal') {
      $args = array();
      $this->initialize($args, 'ManageRecurringPaymentsProfileStatus');

      $args['PROFILEID'] = CRM_Utils_Array::value('subscriptionId', $params);
      $args['ACTION'] = 'Cancel';
      $args['NOTE'] = CRM_Utils_Array::value('reason', $params);

      $result = $this->invokeAPI($args);
      if (is_a($result, 'CRM_Core_Error')) {
        return $result;
      }
      $message = "{$result['ack']}: profileid={$result['profileid']}";
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Process incoming notification.
   *
   * This is only supported for paypal pro at the moment & no specific plans to add this path to core
   * for paypal standard as the goal must be to separate the 2.
   *
   * We don't need to handle paypal standard using this path as there has never been any historic support
   * for paypal standard to call civicrm/payment/ipn as a path.
   */
  static public function handlePaymentNotification() {
    $paypalIPN = new CRM_Core_Payment_PayPalProIPN($_REQUEST);
    $paypalIPN->main();
  }

  /**
   * @param string $message
   * @param array $params
   *
   * @return array|bool|object
   */
  public function updateSubscriptionBillingInfo(&$message = '', $params = array()) {
    if ($this->_paymentProcessor['payment_processor_type'] == 'PayPal') {
      $config = CRM_Core_Config::singleton();
      $args = array();
      $this->initialize($args, 'UpdateRecurringPaymentsProfile');

      $args['PROFILEID'] = $params['subscriptionId'];
      $args['AMT'] = $params['amount'];
      $args['CURRENCYCODE'] = $config->defaultCurrency;
      $args['CREDITCARDTYPE'] = $params['credit_card_type'];
      $args['ACCT'] = $params['credit_card_number'];
      $args['EXPDATE'] = sprintf('%02d', $params['month']) . $params['year'];
      $args['CVV2'] = $params['cvv2'];

      $args['FIRSTNAME'] = $params['first_name'];
      $args['LASTNAME'] = $params['last_name'];
      $args['STREET'] = $params['street_address'];
      $args['CITY'] = $params['city'];
      $args['STATE'] = $params['state_province'];
      $args['COUNTRYCODE'] = $params['postal_code'];
      $args['ZIP'] = $params['country'];

      $result = $this->invokeAPI($args);
      if (is_a($result, 'CRM_Core_Error')) {
        return $result;
      }
      $message = "{$result['ack']}: profileid={$result['profileid']}";
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param string $message
   * @param array $params
   *
   * @return array|bool|object
   */
  public function changeSubscriptionAmount(&$message = '', $params = array()) {
    if ($this->_paymentProcessor['payment_processor_type'] == 'PayPal') {
      $config = CRM_Core_Config::singleton();
      $args = array();
      $this->initialize($args, 'UpdateRecurringPaymentsProfile');

      $args['PROFILEID'] = $params['subscriptionId'];
      $args['AMT'] = $params['amount'];
      $args['CURRENCYCODE'] = $config->defaultCurrency;
      $args['BILLINGFREQUENCY'] = $params['installments'];

      $result = $this->invokeAPI($args);
      CRM_Core_Error::debug_var('$result', $result);
      if (is_a($result, 'CRM_Core_Error')) {
        return $result;
      }
      $message = "{$result['ack']}: profileid={$result['profileid']}";
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Function to action pre-approval if supported
   *
   * @param array $params
   *   Parameters from the form
   *
   * @return array
   *   - pre_approval_parameters (this will be stored on the calling form & available later)
   *   - redirect_url (if set the browser will be redirected to this.
   */
  public function doPreApproval(&$params) {
    if (!$this->isPaypalExpress($params)) {
      return array();
    }
    $this->_component = $params['component'];
    $token = $this->setExpressCheckOut($params);
    return array(
      'pre_approval_parameters' => array('token' => $token),
      'redirect_url' => $this->_paymentProcessor['url_site'] . "/cgi-bin/webscr?cmd=_express-checkout&token=$token",
    );
  }

  /**
   * @param array $params
   * @param string $component
   *
   * @throws Exception
   */
  public function doTransferCheckout(&$params, $component = 'contribute') {
    $config = CRM_Core_Config::singleton();

    if ($component != 'contribute' && $component != 'event') {
      CRM_Core_Error::fatal(ts('Component is invalid'));
    }

    $notifyURL = $config->userFrameworkResourceURL . "extern/ipn.php?reset=1&contactID={$params['contactID']}" . "&contributionID={$params['contributionID']}" . "&module={$component}";

    if ($component == 'event') {
      $notifyURL .= "&eventID={$params['eventID']}&participantID={$params['participantID']}";
    }
    else {
      $membershipID = CRM_Utils_Array::value('membershipID', $params);
      if ($membershipID) {
        $notifyURL .= "&membershipID=$membershipID";
      }
      $relatedContactID = CRM_Utils_Array::value('related_contact', $params);
      if ($relatedContactID) {
        $notifyURL .= "&relatedContactID=$relatedContactID";

        $onBehalfDupeAlert = CRM_Utils_Array::value('onbehalf_dupe_alert', $params);
        if ($onBehalfDupeAlert) {
          $notifyURL .= "&onBehalfDupeAlert=$onBehalfDupeAlert";
        }
      }
    }

    $url = ($component == 'event') ? 'civicrm/event/register' : 'civicrm/contribute/transact';
    $cancel = ($component == 'event') ? '_qf_Register_display' : '_qf_Main_display';
    $returnURL = CRM_Utils_System::url($url,
      "_qf_ThankYou_display=1&qfKey={$params['qfKey']}",
      TRUE, NULL, FALSE
    );

    $cancelUrlString = "$cancel=1&cancel=1&qfKey={$params['qfKey']}";
    if (!empty($params['is_recur'])) {
      $cancelUrlString .= "&isRecur=1&recurId={$params['contributionRecurID']}&contribId={$params['contributionID']}";
    }

    $cancelURL = CRM_Utils_System::url(
      $url,
      $cancelUrlString,
      TRUE, NULL, FALSE
    );

    // ensure that the returnURL is absolute.
    if (substr($returnURL, 0, 4) != 'http') {
      $fixUrl = CRM_Utils_System::url("civicrm/admin/setting/url", '&reset=1');
      CRM_Core_Error::fatal(ts('Sending a relative URL to PayPalIPN is erroneous. Please make your resource URL (in <a href="%1">Administer &raquo; System Settings &raquo; Resource URLs</a> ) complete.', array(1 => $fixUrl)));
    }

    $paypalParams = array(
      'business' => $this->_paymentProcessor['user_name'],
      'notify_url' => $notifyURL,
      'item_name' => $this->getPaymentDescription($params),
      'quantity' => 1,
      'undefined_quantity' => 0,
      'cancel_return' => $cancelURL,
      'no_note' => 1,
      'no_shipping' => 1,
      'return' => $returnURL,
      'rm' => 2,
      'currency_code' => $params['currencyID'],
      'invoice' => $params['invoiceID'],
      'lc' => substr($config->lcMessages, -2),
      'charset' => function_exists('mb_internal_encoding') ? mb_internal_encoding() : 'UTF-8',
      'custom' => CRM_Utils_Array::value('accountingCode', $params),
      'bn' => 'CiviCRM_SP',
    );

    // add name and address if available, CRM-3130
    $otherVars = array(
      'first_name' => 'first_name',
      'last_name' => 'last_name',
      'street_address' => 'address1',
      'country' => 'country',
      'preferred_language' => 'lc',
      'city' => 'city',
      'state_province' => 'state',
      'postal_code' => 'zip',
      'email' => 'email',
    );

    foreach (array_keys($params) as $p) {
      // get the base name without the location type suffixed to it
      $parts = explode('-', $p);
      $name = count($parts) > 1 ? $parts[0] : $p;
      if (isset($otherVars[$name])) {
        $value = $params[$p];
        if ($value) {
          if ($name == 'state_province') {
            $stateName = CRM_Core_PseudoConstant::stateProvinceAbbreviation($value);
            $value = $stateName;
          }
          if ($name == 'country') {
            $countryName = CRM_Core_PseudoConstant::countryIsoCode($value);
            $value = $countryName;
          }
          // ensure value is not an array
          // CRM-4174
          if (!is_array($value)) {
            $paypalParams[$otherVars[$name]] = $value;
          }
        }
      }
    }

    // if recurring donations, add a few more items
    if (!empty($params['is_recur'])) {
      if ($params['contributionRecurID']) {
        $notifyURL .= "&contributionRecurID={$params['contributionRecurID']}&contributionPageID={$params['contributionPageID']}";
        $paypalParams['notify_url'] = $notifyURL;
      }
      else {
        CRM_Core_Error::fatal(ts('Recurring contribution, but no database id'));
      }

      $paypalParams += array(
        'cmd' => '_xclick-subscriptions',
        'a3' => $params['amount'],
        'p3' => $params['frequency_interval'],
        't3' => ucfirst(substr($params['frequency_unit'], 0, 1)),
        'src' => 1,
        'sra' => 1,
        'srt' => CRM_Utils_Array::value('installments', $params),
        'no_note' => 1,
        'modify' => 0,
      );
    }
    else {
      $paypalParams += array(
        'cmd' => '_xclick',
        'amount' => $params['amount'],
      );
    }

    // Allow further manipulation of the arguments via custom hooks ..
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $paypalParams);

    $uri = '';
    foreach ($paypalParams as $key => $value) {
      if ($value === NULL) {
        continue;
      }

      $value = urlencode($value);
      if ($key == 'return' ||
        $key == 'cancel_return' ||
        $key == 'notify_url'
      ) {
        $value = str_replace('%2F', '/', $value);
      }
      $uri .= "&{$key}={$value}";
    }

    $uri = substr($uri, 1);
    $url = $this->_paymentProcessor['url_site'];
    $sub = empty($params['is_recur']) ? 'cgi-bin/webscr' : 'subscriptions';
    $paypalURL = "{$url}{$sub}?$uri";

    CRM_Utils_System::redirect($paypalURL);
  }

  /**
   * Hash_call: Function to perform the API call to PayPal using API signature.
   *
   * @methodName is name of API  method.
   * @nvpStr is nvp string.
   * returns an associative array containing the response from the server.
   *
   * @param array $args
   * @param null $url
   *
   * @return array|object
   * @throws \Exception
   */
  public function invokeAPI($args, $url = NULL) {

    if ($url === NULL) {
      if (empty($this->_paymentProcessor['url_api'])) {
        CRM_Core_Error::fatal(ts('Please set the API URL. Please refer to the documentation for more details'));
      }

      $url = $this->_paymentProcessor['url_api'] . 'nvp';
    }

    if (!function_exists('curl_init')) {
      CRM_Core_Error::fatal("curl functions NOT available.");
    }

    //setting the curl parameters.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);

    //turning off the server and peer verification(TrustManager Concept).
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, Civi::settings()->get('verifySSL'));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, Civi::settings()->get('verifySSL') ? 2 : 0);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);

    $p = array();
    foreach ($args as $n => $v) {
      $p[] = "$n=" . urlencode($v);
    }

    //NVPRequest for submitting to server
    $nvpreq = implode('&', $p);

    //setting the nvpreq as POST FIELD to curl
    curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

    //getting response from server
    $response = curl_exec($ch);

    //converting NVPResponse to an Associative Array
    $result = self::deformat($response);

    if (curl_errno($ch)) {
      $e = CRM_Core_Error::singleton();
      $e->push(curl_errno($ch),
        0, NULL,
        curl_error($ch)
      );
      return $e;
    }
    else {
      curl_close($ch);
    }

    if (strtolower($result['ack']) != 'success' &&
      strtolower($result['ack']) != 'successwithwarning'
    ) {
      throw new PaymentProcessorException("{$result['l_shortmessage0']} {$result['l_longmessage0']}");
      $e = CRM_Core_Error::singleton();
      $e->push($result['l_errorcode0'],
        0, NULL,
        "{$result['l_shortmessage0']} {$result['l_longmessage0']}"
      );
      return $e;
    }

    return $result;
  }

  /**
   * This function will take NVPString and convert it to an Associative Array.
   *
   * It will decode the response. It is useful to search for a particular key and displaying arrays.
   *
   * @param string $str
   *
   * @return array
   */
  public static function deformat($str) {
    $result = array();

    while (strlen($str)) {
      // position of key
      $keyPos = strpos($str, '=');

      // position of value
      $valPos = strpos($str, '&') ? strpos($str, '&') : strlen($str);

      /*getting the Key and Value values and storing in a Associative Array*/

      $key = substr($str, 0, $keyPos);
      $val = substr($str, $keyPos + 1, $valPos - $keyPos - 1);

      //decoding the respose
      $result[strtolower(urldecode($key))] = urldecode($val);
      $str = substr($str, $valPos + 1, strlen($str));
    }

    return $result;
  }

  /**
   * Get array of fields that should be displayed on the payment form.
   *
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  public function getPaymentFormFields() {
    if ($this->_processorName == ts('PayPal Pro')) {
      return $this->getCreditCardFormFields();
    }
    else {
      return array();
    }
  }

  /**
   * Map the paypal params to CiviCRM params using a field map.
   *
   * @param array $fieldMap
   * @param array $paypalParams
   *
   * @return array
   */
  protected function mapPaypalParamsToCivicrmParams($fieldMap, $paypalParams) {
    $params = array();
    foreach ($fieldMap as $civicrmField => $paypalField) {
      $params[$civicrmField] = isset($paypalParams[$paypalField]) ? $paypalParams[$paypalField] : NULL;
    }
    return $params;
  }

  /**
   * Is this being processed by payment express.
   *
   * Either because it is payment express or because is pro with paypal express in use.
   *
   * @param array $params
   *
   * @return bool
   */
  protected function isPaypalExpress($params) {
    if ($this->_processorName == ts('PayPal Express')) {
      return TRUE;
    }

    // This would occur postProcess.
    if (!empty($params['token'])) {
      return TRUE;
    }
    if (isset($params['button']) && stristr($params['button'], 'express')) {
      return TRUE;
    }

    // The contribution form passes a 'button' but the event form might still set one of these fields.
    // @todo more standardisation & get paypal fully out of the form layer.
    $possibleExpressFields = array(
      '_qf_Register_upload_express_x',
      '_qf_Payment_upload_express_x',
    );
    if (array_intersect_key($params, array_fill_keys($possibleExpressFields, 1))) {
      return TRUE;
    }
    return FALSE;
  }

}
