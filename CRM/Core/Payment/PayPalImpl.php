<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Class CRM_Core_Payment_PayPalImpl for paypal pro, paypal standard & paypal express.
 */
class CRM_Core_Payment_PayPalImpl extends CRM_Core_Payment {
  const CHARSET = 'iso-8859-1';

  const PAYPAL_PRO = 'PayPal';
  const PAYPAL_STANDARD = 'PayPal_Standard';
  const PAYPAL_EXPRESS = 'PayPal_Express';

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
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;

    if ($this->isPayPalType($this::PAYPAL_STANDARD)) {
      $this->_processorName = ts('PayPal Standard');
    }
    elseif ($this->isPayPalType($this::PAYPAL_EXPRESS)) {
      $this->_processorName = ts('PayPal Express');
    }
    elseif ($this->isPayPalType($this::PAYPAL_PRO)) {
      $this->_processorName = ts('PayPal Pro');
    }
    else {
      throw new PaymentProcessorException('CRM_Core_Payment_PayPalImpl: Payment processor type is not defined!');
    }
  }

  /**
   * Helper function to check which payment processor type is being used.
   *
   * @param $typeName
   *
   * @return bool
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function isPayPalType($typeName) {
    // Historically payment_processor_type may have been set to the name of the processor but newer versions of CiviCRM use the id set in payment_processor_type_id
    if (empty($this->_paymentProcessor['payment_processor_type_id']) && empty($this->_paymentProcessor['payment_processor_type'])) {
      // We need one of them to be set!
      throw new PaymentProcessorException('CRM_Core_Payment_PayPalImpl: Payment processor type is not defined!');
    }
    if (empty($this->_paymentProcessor['payment_processor_type_id']) && !empty($this->_paymentProcessor['payment_processor_type'])) {
      // Handle legacy case where payment_processor_type was set, but payment_processor_type_id was not.
      $this->_paymentProcessor['payment_processor_type_id']
        = CRM_Core_PseudoConstant::getKey('CRM_Financial_BAO_PaymentProcessor', 'payment_processor_type_id', $this->_paymentProcessor['payment_processor_type']);
    }
    if ((int) $this->_paymentProcessor['payment_processor_type_id'] ===
      CRM_Core_PseudoConstant::getKey('CRM_Financial_BAO_PaymentProcessor', 'payment_processor_type_id', $typeName)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Are back office payments supported.
   *
   * E.g paypal standard won't permit you to enter a credit card associated
   * with someone else's login.
   *
   * @return bool
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  protected function supportsBackOffice() {
    if ($this->isPayPalType($this::PAYPAL_PRO)) {
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
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  protected function supportsPreApproval() {
    if ($this->isPayPalType($this::PAYPAL_EXPRESS) || $this->isPayPalType($this::PAYPAL_PRO)) {
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
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function buildForm(&$form) {
    if ($this->supportsPreApproval()) {
      $this->addPaypalExpressCode($form);
      if ($this->isPayPalType($this::PAYPAL_EXPRESS)) {
        CRM_Core_Region::instance('billing-block-post')->add([
          'template' => 'CRM/Financial/Form/PaypalExpress.tpl',
          'name' => 'paypal_express',
        ]);
      }
      if ($this->isPayPalType($this::PAYPAL_PRO)) {
        CRM_Core_Region::instance('billing-block-pre')->add([
          'template' => 'CRM/Financial/Form/PaypalPro.tpl',
        ]);
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
    // @todo use $this->isBackOffice() instead, test.
    if (empty($form->isBackOffice)) {

      /**
       * if payment method selected using ajax call then form object is of 'CRM_Financial_Form_Payment',
       * instead of 'CRM_Contribute_Form_Contribution_Main' so it generate wrong button name
       * and then clicking on express button it redirect to confirm screen rather than PayPal Express form
       */

      if ('CRM_Financial_Form_Payment' == get_class($form) && $form->_formName) {
        $form->_expressButtonName = '_qf_' . $form->_formName . '_upload_express';
      }
      else {
        $form->_expressButtonName = $form->getButtonName('upload', 'express');
      }
      $form->assign('expressButtonName', $form->_expressButtonName);
      $form->add(
        'image',
        $form->_expressButtonName,
        $this->_paymentProcessor['url_button'],
        ['class' => 'crm-form-submit']
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
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function validatePaymentInstrument($values, &$errors) {
    if ($this->isPayPalType($this::PAYPAL_PRO) && !$this->isPaypalExpress($values)) {
      CRM_Core_Payment_Form::validateCreditCard($values, $errors, $this->_paymentProcessor['id']);
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
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  protected function setExpressCheckOut(&$params) {
    $args = [];

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
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function getPreApprovalDetails($storedDetails) {
    return empty($storedDetails['token']) ? [] : $this->getExpressCheckoutDetails($storedDetails['token']);
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
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function getExpressCheckoutDetails($token) {
    $args = [];

    $this->initialize($args, 'GetExpressCheckoutDetails');
    $args['token'] = $token;
    // LCD
    $args['method'] = 'GetExpressCheckoutDetails';

    $result = $this->invokeAPI($args);

    if (is_a($result, 'CRM_Core_Error')) {
      throw new PaymentProcessorException(CRM_Core_Error::getMessages($result));
    }

    /* Success */
    $fieldMap = [
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
    ];
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
    $args = [];

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
      $params['payment_status_id'] = array_search('Pending', $statuses);
    }
    else {
      $params['payment_status_id'] = array_search('Completed', $statuses);
    }
    return $params;
  }

  /**
   * Create recurring payments.
   *
   * Use a pre-authorisation token to activate a recurring payment profile
   * https://developer.paypal.com/docs/classic/api/merchant/CreateRecurringPaymentsProfile_API_Operation_NVP/
   *
   * @param array $params
   *
   * @return mixed
   * @throws \Exception
   */
  public function createRecurringPayments(&$params) {
    $args = [];
    $this->initialize($args, 'CreateRecurringPaymentsProfile');

    $start_time = strtotime(date('m/d/Y'));
    $start_date = date('Y-m-d\T00:00:00\Z', $start_time);

    $args['token'] = $params['token'];
    $args['paymentAction'] = 'Sale';
    $args['amt'] = $params['amount'];
    $args['currencyCode'] = $params['currencyID'];
    $args['payerID'] = $params['payer_id'];
    $args['invnum'] = $params['invoiceID'];
    $args['profilestartdate'] = $start_date;
    $args['method'] = 'CreateRecurringPaymentsProfile';
    $args['billingfrequency'] = $params['frequency_interval'];
    $args['billingperiod'] = ucwords($params['frequency_unit']);
    $args['desc'] = $params['amount'] . " Per " . $params['frequency_interval'] . " " . $params['frequency_unit'];
    $args['totalbillingcycles'] = CRM_Utils_Array::value('installments', $params);
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

    /* Success - result looks like"
     * array (
     * 'profileid' => 'I-CP1U0PLG91R2',
     * 'profilestatus' => 'ActiveProfile',
     * 'timestamp' => '2018-05-07T03:55:52Z',
     * 'correlationid' => 'e717999e9bf62',
     * 'ack' => 'Success',
     * 'version' => '56.0',
     * 'build' => '39949200',)
     */
    $params['trxn_id'] = $result['profileid'];
    $params['payment_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');

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
   * Process payment - this function wraps around both doTransferCheckout and doDirectPayment.
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
    if ($this->isPayPalType($this::PAYPAL_EXPRESS) || ($this->isPayPalType($this::PAYPAL_PRO) && !empty($params['token']))) {
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
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function doDirectPayment(&$params, $component = 'contribute') {
    $args = [];

    $this->initialize($args, 'DoDirectPayment');

    $args['paymentAction'] = 'Sale';
    $args['amt'] = $this->getAmount($params);
    $args['currencyCode'] = $this->getCurrency($params);
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
      $args['amt'] = $this->getAmount($params);
      $args['totalbillingcycles'] = CRM_Utils_Array::value('installments', $params);
      $args['version'] = 56.0;
      $args['PROFILEREFERENCE'] = "" .
        "i=" . $params['invoiceID'] . "&m=" . $component .
        "&c=" . $params['contactID'] . "&r=" . $params['contributionRecurID'] .
        "&b=" . $params['contributionID'] . "&p=" . $params['contributionPageID'];
    }

    // Allow further manipulation of the arguments via custom hooks ..
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $args);

    $result = $this->invokeAPI($args);

    // WAG
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
    //CRM-18140 - trxn_id not returned for recurring paypal transaction
    if (!empty($params['is_recur'])) {
      return [];
    }
    elseif (empty($params['trxn_id'])) {
      throw new \Civi\Payment\Exception\PaymentProcessorException('transaction id not set');
    }
    $args = [
      'TRANSACTIONID' => $params['trxn_id'],
    ];
    $this->initialize($args, 'GetTransactionDetails');
    $result = $this->invokeAPI($args);
    return [
      'fee_amount' => $result['feeamt'],
      'net_amount' => $params['gross_amount'] - $result['feeamt'],
    ];
  }

  /**
   * This function checks to see if we have the right config values.
   *
   * @return null|string
   *   the error message if any
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function checkConfig() {
    $error = [];

    if (!$this->isPayPalType($this::PAYPAL_STANDARD)) {
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
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function cancelSubscriptionURL() {
    if ($this->isPayPalType($this::PAYPAL_STANDARD)) {
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
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function isSupported($method) {
    if (!$this->isPayPalType($this::PAYPAL_PRO)) {
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
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function isSuppressSubmitButtons() {
    if ($this->isPayPalType($this::PAYPAL_EXPRESS)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param string $message
   * @param array $params
   *
   * @return array|bool|object
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function cancelSubscription(&$message = '', $params = []) {
    if ($this->isPayPalType($this::PAYPAL_PRO) || $this->isPayPalType($this::PAYPAL_EXPRESS)) {
      $args = [];
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
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  static public function handlePaymentNotification() {
    $params = array_merge($_GET, $_REQUEST);
    $q = explode('/', CRM_Utils_Array::value('q', $params, ''));
    $lastParam = array_pop($q);
    if (is_numeric($lastParam)) {
      $params['processor_id'] = $lastParam;
    }
    $result = civicrm_api3('PaymentProcessor', 'get', [
      'sequential' => 1,
      'id' => $params['processor_id'],
      'api.PaymentProcessorType.getvalue' => ['return' => "name"],
    ]);
    if (!$result['count']) {
      throw new CRM_Core_Exception("Could not find a processor with the given processor_id value '{$params['processor_id']}'.");
    }

    $paymentProcessorType = CRM_Utils_Array::value('api.PaymentProcessorType.getvalue', $result['values'][0]);
    switch ($paymentProcessorType) {
      case 'PayPal':
        // "PayPal - Website Payments Pro"
        $paypalIPN = new CRM_Core_Payment_PayPalProIPN($params);
        break;

      case 'PayPal_Standard':
        // "PayPal - Website Payments Standard"
        $paypalIPN = new CRM_Core_Payment_PayPalIPN($params);
        break;

      default:
        // If we don't have PayPal Standard or PayPal Pro, something's wrong.
        // Log an error and exit.
        throw new CRM_Core_Exception("The processor_id value '{$params['processor_id']}' is for a processor of type '{$paymentProcessorType}', which is invalid in this context.");
    }

    $paypalIPN->main();
  }

  /**
   * @param string $message
   * @param array $params
   *
   * @return array|bool|object
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function updateSubscriptionBillingInfo(&$message = '', $params = []) {
    if ($this->isPayPalType($this::PAYPAL_PRO)) {
      $config = CRM_Core_Config::singleton();
      $args = [];
      $this->initialize($args, 'UpdateRecurringPaymentsProfile');

      $args['PROFILEID'] = $params['subscriptionId'];
      $args['AMT'] = $this->getAmount($params);
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
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function changeSubscriptionAmount(&$message = '', $params = []) {
    if ($this->isPayPalType($this::PAYPAL_PRO)) {
      $config = CRM_Core_Config::singleton();
      $args = [];
      $this->initialize($args, 'UpdateRecurringPaymentsProfile');

      $args['PROFILEID'] = $params['subscriptionId'];
      $args['AMT'] = $this->getAmount($params);
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
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function doPreApproval(&$params) {
    if (!$this->isPaypalExpress($params)) {
      return [];
    }
    $this->_component = $params['component'];
    $token = $this->setExpressCheckOut($params);
    return [
      'pre_approval_parameters' => ['token' => $token],
      'redirect_url' => $this->_paymentProcessor['url_site'] . "/cgi-bin/webscr?cmd=_express-checkout&token=$token",
    ];
  }

  /**
   * @param array $params
   * @param string $component
   *
   * @throws Exception
   */
  public function doTransferCheckout(&$params, $component = 'contribute') {

    $notifyParameters = ['module' => $component];
    $notifyParameterMap = [
      'contactID' => 'contactID',
      'contributionID' => 'contributionID',
      'eventID' => 'eventID',
      'participantID' => 'participantID',
      'membershipID' => 'membershipID',
      'related_contact' => 'relatedContactID',
      'onbehalf_dupe_alert' => 'onBehalfDupeAlert',
      'accountingCode' => 'accountingCode',
      'contributionRecurID' => 'contributionRecurID',
      'contributionPageID' => 'contributionPageID',
    ];
    foreach ($notifyParameterMap as $paramsName => $notifyName) {
      if (!empty($params[$paramsName])) {
        $notifyParameters[$notifyName] = $params[$paramsName];
      }
    }
    $notifyURL = $this->getNotifyUrl();

    $config = CRM_Core_Config::singleton();
    $url = ($component == 'event') ? 'civicrm/event/register' : 'civicrm/contribute/transact';
    $cancel = ($component == 'event') ? '_qf_Register_display' : '_qf_Main_display';

    $cancelUrlString = "$cancel=1&cancel=1&qfKey={$params['qfKey']}";
    if (!empty($params['is_recur'])) {
      $cancelUrlString .= "&isRecur=1&recurId={$params['contributionRecurID']}&contribId={$params['contributionID']}";
    }

    $cancelURL = CRM_Utils_System::url(
      $url,
      $cancelUrlString,
      TRUE, NULL, FALSE
    );

    $paypalParams = [
      'business' => $this->_paymentProcessor['user_name'],
      'notify_url' => $notifyURL,
      'item_name' => $this->getPaymentDescription($params, 127),
      'quantity' => 1,
      'undefined_quantity' => 0,
      'cancel_return' => $cancelURL,
      'no_note' => 1,
      'no_shipping' => 1,
      'return' => $this->getReturnSuccessUrl($params['qfKey']),
      'rm' => 2,
      'currency_code' => $params['currencyID'],
      'invoice' => $params['invoiceID'],
      'lc' => substr($config->lcMessages, -2),
      'charset' => function_exists('mb_internal_encoding') ? mb_internal_encoding() : 'UTF-8',
      'custom' => json_encode($notifyParameters),
      'bn' => 'CiviCRM_SP',
    ];

    // add name and address if available, CRM-3130
    $otherVars = [
      'first_name' => 'first_name',
      'last_name' => 'last_name',
      'street_address' => 'address1',
      'country' => 'country',
      'preferred_language' => 'lc',
      'city' => 'city',
      'state_province' => 'state',
      'postal_code' => 'zip',
      'email' => 'email',
    ];

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
      if (!$params['contributionRecurID']) {
        CRM_Core_Error::fatal(ts('Recurring contribution, but no database id'));
      }

      $paypalParams += [
        'cmd' => '_xclick-subscriptions',
        'a3' => $this->getAmount($params),
        'p3' => $params['frequency_interval'],
        't3' => ucfirst(substr($params['frequency_unit'], 0, 1)),
        'src' => 1,
        'sra' => 1,
        'srt' => CRM_Utils_Array::value('installments', $params),
        'no_note' => 1,
        'modify' => 0,
      ];
    }
    else {
      $paypalParams += [
        'cmd' => '_xclick',
        'amount' => $params['amount'],
      ];
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

    $p = [];
    foreach ($args as $n => $v) {
      $p[] = "$n=" . urlencode($v);
    }

    //NVPRequest for submitting to server
    $nvpreq = implode('&', $p);

    if (!function_exists('curl_init')) {
      CRM_Core_Error::fatal("curl functions NOT available.");
    }

    //setting the curl parameters.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);

    //turning off the server and peer verification(TrustManager Concept).
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, Civi::settings()->get('verifySSL'));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, Civi::settings()->get('verifySSL') ? 2 : 0);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);

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

    $outcome = strtolower(CRM_Utils_Array::value('ack', $result));

    if ($outcome != 'success' && $outcome != 'successwithwarning') {
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
    $result = [];

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
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function getPaymentFormFields() {
    if ($this->isPayPalType($this::PAYPAL_PRO)) {
      return $this->getCreditCardFormFields();
    }
    else {
      return [];
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
    $params = [];
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
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  protected function isPaypalExpress($params) {
    if ($this->isPayPalType($this::PAYPAL_EXPRESS)) {
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
    $possibleExpressFields = [
      '_qf_Register_upload_express_x',
      '_qf_Payment_upload_express_x',
    ];
    if (array_intersect_key($params, array_fill_keys($possibleExpressFields, 1))) {
      return TRUE;
    }
    return FALSE;
  }

}
