<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use Civi\Payment\Exception\PaymentProcessorException;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class CRM_Core_Payment_PayPalImpl for paypal pro, paypal standard & paypal express.
 */
class CRM_Core_Payment_PayPalImpl extends CRM_Core_Payment {

  /**
   * Types of PayPal experience supported by this class.
   */
  const PAYPAL_PRO = 'PayPal';
  const PAYPAL_STANDARD = 'PayPal_Standard';
  const PAYPAL_EXPRESS = 'PayPal_Express';

  /**
   * Production Postback URL for IPN verification
   */
  const IPN_VERIFY_URI = 'https://ipnpb.paypal.com/cgi-bin/webscr';
  /**
   * Sandbox Postback URL for IPN verification
   */
  const IPN_SANDBOX_VERIFY_URI = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';

  /**
   * Response from PayPal indicating IPN validation was successful
   */
  const IPN_VALID = 'VERIFIED';
  /**
   * Response from PayPal indicating IPN validation failed
   */
  const IPN_INVALID = 'INVALID';

  /**
   * Processor mode: 'live' or 'test'.
   *
   * @var string
   */
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
  }

  /**
   * @var GuzzleHttp\Client
   */
  protected $guzzleClient;

  /**
   * @return \GuzzleHttp\Client
   */
  public function getGuzzleClient(): \GuzzleHttp\Client {
    return $this->guzzleClient ?? new \GuzzleHttp\Client();
  }

  /**
   * @param \GuzzleHttp\Client $guzzleClient
   */
  public function setGuzzleClient(\GuzzleHttp\Client $guzzleClient) {
    $this->guzzleClient = $guzzleClient;
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
   * Checks if payment processor supports not returning to the form processing.
   *
   * The exists to support historical event form logic where emails are sent
   * & the form postProcess hook is called before redirecting the browser where
   * the user is redirected.
   *
   * @return bool
   */
  public function supportsNoReturn(): bool {
    $billingMode = (int) $this->_paymentProcessor['billing_mode'];
    return $billingMode === self::BILLING_MODE_NOTIFY;
  }

  /**
   * Checks if payment processor supports not returning to the form processing on recurring.
   *
   * The exists to support historical event form logic where emails are sent
   * & the form postProcess hook is called before redirecting the browser where
   * the user is redirected.
   *
   * @return bool
   */
  public function supportsNoReturnForRecurring(): bool {
    $billingMode = (int) $this->_paymentProcessor['billing_mode'];
    return $billingMode === self::BILLING_MODE_NOTIFY || $billingMode === self::BILLING_MODE_BUTTON;
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
      $form->add('xbutton', $form->_expressButtonName, ts('Pay using PayPal'), [
        'type' => 'submit',
        'formnovalidate' => 'formnovalidate',
        'class' => 'crm-form-submit',
      ]);
      CRM_Core_Resources::singleton()->addStyle('
        button#' . $form->_expressButtonName . '{
         background-image: url(' . $this->_paymentProcessor['url_button'] . ');
         color: transparent;
         background-repeat: no-repeat;
         background-color: transparent;
         background-position: center;
         min-width: 150px;
         min-height: 50px;
         border: none;
       ');
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
    $args['desc'] = $params['description'] ?? NULL;
    $args['invnum'] = $params['invoiceID'];
    $args['returnURL'] = $this->getReturnSuccessUrl($params['qfKey']);
    $args['cancelURL'] = $this->getCancelUrl($params['qfKey'], $params['participantID'] ?? NULL);
    $args['version'] = '56.0';
    $args['SOLUTIONTYPE'] = 'Sole';

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

    /* Success */
    $fieldMap = [
      'token' => 'token',
      'payer_status' => 'payerstatus',
      'payer_id' => 'payerid',
      'billing_first_name' => 'firstname',
      'billing_middle_name' => 'middlename',
      'billing_last_name' => 'lastname',
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

    /* Success */
    $params['trxn_id'] = $result['transactionid'];
    $params['fee_amount'] = $result['feeamt'];
    $params['net_amount'] = $result['settleamt'] ?? NULL;
    if ($params['net_amount'] == 0 && $params['fee_amount'] != 0) {
      $params['net_amount'] = number_format(($params['gross_amount'] - $params['fee_amount']), 2);
    }
    $params['payment_status'] = $result['paymentstatus'];
    $params['pending_reason'] = $result['pendingreason'];
    if (!empty($params['is_recur'])) {
      // See comment block.
      $params['payment_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');
    }
    else {
      $params['payment_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
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
    $args['totalbillingcycles'] = $params['installments'] ?? NULL;
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
    $args['subject'] = $this->_paymentProcessor['subject'] ?? NULL;
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
    $this->_component = $component;
    if ($this->isPayPalType($this::PAYPAL_EXPRESS) || ($this->isPayPalType($this::PAYPAL_PRO) && !empty($params['token']))) {
      return $this->doExpressCheckout($params);
    }
    $result = $this->setStatusPaymentPending([]);

    // If we have a $0 amount, skip call to processor and set payment_status to Completed.
    // Conceivably a processor might override this - perhaps for setting up a token - but we don't
    // have an example of that at the mome.
    if ($params['amount'] == 0) {
      $result = $this->setStatusPaymentCompleted($result);
      return $result;
    }

    if ($this->_paymentProcessor['billing_mode'] == 4) {
      $this->doPaymentRedirectToPayPal($params);
      // redirect calls CiviExit() so execution is stopped
    }
    else {
      $result = $this->doPaymentPayPalButton($params);
      if (is_array($result) && !isset($result['payment_status_id'])) {
        if (!empty($params['is_recur'])) {
          $result = $this->setStatusPaymentPending($result);
        }
        else {
          $result = $this->setStatusPaymentCompleted($result);
        }
      }
    }
    if (is_a($result, 'CRM_Core_Error')) {
      CRM_Core_Error::deprecatedFunctionWarning('payment processors should throw exceptions rather than return errors');
      throw new PaymentProcessorException(CRM_Core_Error::getMessages($result));
    }
    return $result;
  }

  /**
   * Temporary function to catch transition to doPaymentPayPalButton()
   * @deprecated
   */
  public function doDirectPayment(&$params) {
    CRM_Core_Error::deprecatedFunctionWarning('doPayment');
    return $this->doPaymentPayPalButton($params);
  }

  /**
   * This function collects all the information from a web/api form and invokes
   * the relevant payment processor specific functions to perform the transaction
   *
   * @param array $params
   *   Assoc array of input parameters for this transaction.
   *
   * @return array
   *   the result in an nice formatted array (or an error object)
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function doPaymentPayPalButton(&$params) {
    $args = [];

    $result = [];

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
    $args['email'] = $params['email'] ?? NULL;
    $args['street'] = $params['street_address'];
    $args['city'] = $params['city'];
    $args['state'] = $params['state_province'];
    $args['countryCode'] = $params['country'];
    $args['zip'] = $params['postal_code'];
    $args['desc'] = substr(($params['description'] ?? ''), 0, 127);
    $args['custom'] = $params['accountingCode'] ?? NULL;

    // add CiviCRM BN code
    $args['BUTTONSOURCE'] = 'CiviCRM_SP';

    if (($params['is_recur'] ?? NULL) == 1) {
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
      $args['totalbillingcycles'] = $params['installments'] ?? NULL;
      $args['version'] = 56.0;
      $args['PROFILEREFERENCE'] = "" .
        "i=" . $params['invoiceID'] . "&m=" . $this->_component .
        "&c=" . $params['contactID'] . "&r=" . $params['contributionRecurID'] .
        "&b=" . $params['contributionID'] . "&p=" . $params['contributionPageID'];
    }

    // Allow further manipulation of the arguments via custom hooks ..
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $args);

    $apiResult = $this->invokeAPI($args);

    $params['recurr_profile_id'] = NULL;

    if (($params['is_recur'] ?? NULL) == 1) {
      $params['recurr_profile_id'] = $apiResult['profileid'];
    }

    /* Success */
    $doQueryParams = [
      'gross_amount' => $apiResult['amt'] ?? NULL,
      'trxn_id' => $apiResult['transactionid'] ?? NULL,
      'is_recur' => $params['is_recur'] ?? FALSE,
    ];
    $params = array_merge($params, $this->doQuery($doQueryParams));

    $result['fee_amount'] = $params['fee_amount'] ?? 0;
    $result['trxn_id'] = $apiResult['transactionid'] ?? NULL;
    return $result;
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
    if ($this->isPayPalType($this::PAYPAL_STANDARD)) {
      if (empty($this->_paymentProcessor['url_site'])) {
        $error[] = ts('Site URL is not set (eg. https://www.paypal.com/ - https://www.sandbox.paypal.com/)');
      }
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  /**
   * Get url for users to manage this recurring contribution for this processor.
   *
   * @param int $entityID
   * @param string|null $entity
   * @param string $action
   *
   * @return string|null
   * @throws \CRM_Core_Exception
   */
  public function subscriptionURL($entityID = NULL, $entity = NULL, $action = 'cancel') {
    if ($this->isPayPalType($this::PAYPAL_STANDARD)) {
      if ($action !== 'cancel') {
        return NULL;
      }
      return "{$this->_paymentProcessor['url_site']}cgi-bin/webscr?cmd=_subscr-find&alias=" . urlencode($this->_paymentProcessor['user_name']);
    }
    return parent::subscriptionURL($entityID, $entity, $action);
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
   * @return bool
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function cancelSubscription(&$message = '', $params = []) {
    if ($this->isPayPalType($this::PAYPAL_PRO) || $this->isPayPalType($this::PAYPAL_EXPRESS)) {
      $args = [];
      $this->initialize($args, 'ManageRecurringPaymentsProfileStatus');

      $args['PROFILEID'] = $params['subscriptionId'] ?? NULL;
      $args['ACTION'] = 'Cancel';
      $args['NOTE'] = $params['reason'] ?? NULL;

      $result = $this->invokeAPI($args);

      $message = "{$result['ack']}: profileid={$result['profileid']}";
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Process incoming notification.
   *
   * @throws \CRM_Core_Exception
   */
  public function handlePaymentNotification() {
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

    $paymentProcessorType = $result['values'][0]['api.PaymentProcessorType.getvalue'] ?? NULL;
    switch ($paymentProcessorType) {
      case 'PayPal':
        // "PayPal - Website Payments Pro"
        $paypalIPN = new CRM_Core_Payment_PayPalProIPN($params);
        break;

      case 'PayPal_Express':
        // "PayPal - Express"
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
   * Verify incoming IPN data.
   * Sends the incoming post data back to PayPal using the cURL library.
   *
   * This method is substantially copied from the PayPal example code published here:
   * https://github.com/paypal/ipn-code-samples/blob/master/php/PaypalIPN.php - function verifyIPN()
   *
   * Although there may be cleaner or modern ways of doing some things here, the general principle
   * is to stick to the PayPal reference code as this is known to work, and IPN post-back is both
   * very sensitive and difficult to test.
   *
   * For example, according to https://developer.paypal.com/api/nvp-soap/ipn/IPNImplementation/:
   * "do not change the message fields, the order of the fields, or the character encoding from the original message"
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function verifyIPN() {
    if (CIVICRM_UF === 'UnitTests') {
      // This method won't work in unit tests because data will not be available in raw input stream
      // and (even if it were available) verification would fail.
      Civi::log()->debug('PayPalIPN: Skipping verification in unit test environment.');
      return TRUE;
    }

    if (!count($_POST)) {
      throw new CRM_Core_Exception("Missing POST Data");
    }

    // Reading posted data directly from $_POST causes serialization issues with array data in POST.
    // Reading raw POST data from input stream instead.
    // See here for discussion as to reasons why: https://stackoverflow.com/questions/14008067
    $raw_post_data = file_get_contents('php://input');
    $raw_post_array = explode('&', $raw_post_data);
    $myPost = [];
    foreach ($raw_post_array as $keyval) {
      $keyval = explode('=', $keyval);
      if (count($keyval) == 2) {
        // Since we do not want the plus in the datetime string to be encoded to a space, we manually encode it.
        if ($keyval[0] === 'payment_date') {
          if (substr_count($keyval[1], '+') === 1) {
            $keyval[1] = str_replace('+', '%2B', $keyval[1]);
          }
        }
        $myPost[$keyval[0]] = urldecode($keyval[1]);
      }
    }

    // Build the body of the verification post request, adding the _notify-validate command.
    $req = 'cmd=_notify-validate';
    foreach ($myPost as $key => $value) {
      $value = urlencode($value);
      $req .= "&$key=$value";
    }

    // Post the data back to PayPal, using curl. Throw exceptions if errors occur.
    if (!function_exists('curl_init')) {
      throw new CRM_Core_Exception('curl functions NOT available.');
    }
    $ch = curl_init($this->getPaypalUriForIPN());
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
    curl_setopt($ch, CURLOPT_SSLVERSION, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    // The original PayPal reference code bundles a local certificate file for use if the server does not have one.
    // Instead, we will use the CiviCRM local certificate file if deemed necessary according to built-in logic.
    // This is often required if the server is missing a global cert bundle, or is using an outdated one.
    $caConfig = CA_Config_Curl::probe();
    if (!empty($caConfig->getCaFile())) {
      curl_setopt($ch, CURLOPT_CAINFO, $caConfig->getCaFile());
    }
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'User-Agent: PHP-IPN-Verification-Script',
      'Connection: Close',
    ]);
    $res = curl_exec($ch);
    if (!$res) {
      $errno = curl_errno($ch);
      $errstr = curl_error($ch);
      throw new CRM_Core_Exception("cURL error: [$errno] $errstr");
    }

    $info = curl_getinfo($ch);
    $http_code = $info['http_code'];
    if ($http_code != 200) {
      throw new CRM_Core_Exception("PayPal responded with http code $http_code");
    }

    Civi::log()->debug('PayPalIPN: Verification response from PayPal: "' . $res . '".');

    // Check if PayPal verifies the IPN data, and if so, return true.
    if ($res == self::IPN_VALID) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Determine endpoint to post the IPN verification data to.
   *
   * This method is based on the PayPal example code published here:
   * https://github.com/paypal/ipn-code-samples/blob/master/php/PaypalIPN.php - function getPaypalUri()
   *
   * @return string
   */
  protected function getPaypalUriForIPN() {
    if ($this->_mode == 'test') {
      return self::IPN_SANDBOX_VERIFY_URI;
    }
    else {
      return self::IPN_VERIFY_URI;
    }
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

      $message = "{$result['ack']}: profileid={$result['profileid']}";
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param string $message
   * @param array $params
   *
   * @return bool
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
    $siteUrl = rtrim($this->_paymentProcessor['url_site'], '/');
    return [
      'pre_approval_parameters' => ['token' => $token],
      'redirect_url' => $siteUrl . "/cgi-bin/webscr?cmd=_express-checkout&token=$token",
    ];
  }

  /**
   * Temporary function to catch transition to doPaymentRedirectToPayPal()
   * @deprecated
   */
  public function doTransferCheckout(&$params, $component = 'contribute') {
    CRM_Core_Error::deprecatedFunctionWarning('doPayment');
    $this->doPaymentRedirectToPayPal($params);
  }

  /**
   * @param array $params
   *
   * @throws Exception
   */
  public function doPaymentRedirectToPayPal(&$params) {
    $notifyParameters = ['module' => $this->_component];
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
    $config = CRM_Core_Config::singleton();

    $paypalParams = [
      'business' => $this->_paymentProcessor['user_name'],
      'notify_url' => $this->getNotifyUrl(),
      'item_name' => $this->getPaymentDescription($params, 127),
      'quantity' => 1,
      'undefined_quantity' => 0,
      'cancel_return' => $this->getCancelUrl($params['qfKey'], $params['participantID'] ?? NULL),
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
        throw new CRM_Core_Exception(ts('Recurring contribution, but no database id'));
      }

      // See https://developer.paypal.com/api/nvp-soap/paypal-payments-standard/integration-guide/Appx-websitestandard-htmlvariables/#link-recurringpaymentvariables
      $paypalParams += [
        'cmd' => '_xclick-subscriptions',
        'a3' => $this->getAmount($params),
        'p3' => $params['frequency_interval'] ?? 1,
        't3' => ucfirst(substr($params['frequency_unit'], 0, 1)),
        'src' => 1,
        'sra' => 1,
        'srt' => $params['installments'] ?? NULL,
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

    /*
     * PayPal urlencodes the IPN Notify URL. For sites not using Clean URLs (or
     * using Shortcodes in WordPress) this results in "%2F" becoming "%252F" and
     * therefore incomplete transactions. We need to prevent that.
     * @see https://lab.civicrm.org/dev/core/-/issues/1931
     */
    $paypalParams['notify_url'] = rawurldecode($paypalParams['notify_url']);

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

    // Allow each CMS to do a pre-flight check before redirecting to PayPal.
    CRM_Core_Config::singleton()->userSystem->prePostRedirect();
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
   *
   * @return array|object
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function invokeAPI($args) {

    if (empty($this->_paymentProcessor['url_api'])) {
      throw new PaymentProcessorException(ts('Please set the API URL. Please refer to the documentation for more details'));
    }

    $url = $this->_paymentProcessor['url_api'] . 'nvp';

    $p = [];
    foreach ($args as $n => $v) {
      $p[] = "$n=" . urlencode($v ?? '');
    }

    //NVPRequest for submitting to server
    $nvpreq = implode('&', $p);

    if (!function_exists('curl_init')) {
      throw new PaymentProcessorException('curl functions NOT available.');
    }

    $response = (string) $this->getGuzzleClient()->post($url, [
      'body' => $nvpreq,
      'curl' => [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_SSL_VERIFYPEER => Civi::settings()->get('verifySSL'),
      ],
    ])->getBody();

    $result = self::deformat($response);

    $outcome = strtolower($result['ack'] ?? '');

    if ($outcome !== 'success' && $outcome !== 'successwithwarning') {
      throw new PaymentProcessorException("{$result['l_shortmessage0']} {$result['l_longmessage0']}");
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
      $valPos = strpos($str, '&') ?: strlen($str);

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
      $params[$civicrmField] = $paypalParams[$paypalField] ?? NULL;
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
      // @todo - we think these top 2 are likely not required & it's still here
      // on a precautionary basis.
      // see https://github.com/civicrm/civicrm-core/pull/18680
      '_qf_Register_upload_express_x',
      '_qf_Payment_upload_express_x',
      '_qf_Register_upload_express',
      '_qf_Payment_upload_express',
      '_qf_Main_upload_express',
    ];
    if (array_intersect_key($params, array_fill_keys($possibleExpressFields, 1))) {
      return TRUE;
    }
    return FALSE;
  }

}
