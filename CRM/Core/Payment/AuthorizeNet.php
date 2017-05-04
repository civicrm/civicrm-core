<?php
/*
 * Copyright (C) 2007
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * Written and contributed by Ideal Solution, LLC (http://www.idealso.com)
 *
 */

/**
 *
 * @package CRM
 * @author Marshal Newrock <marshal@idealso.com>
 */

/**
 * NOTE:
 * When looking up response codes in the Authorize.Net API, they
 * begin at one, so always delete one from the "Position in Response"
 */
class CRM_Core_Payment_AuthorizeNet extends CRM_Core_Payment {
  const CHARSET = 'iso-8859-1';
  const AUTH_APPROVED = 1;
  const AUTH_DECLINED = 2;
  const AUTH_ERROR = 3;
  const AUTH_REVIEW = 4;
  const TIMEZONE = 'America/Denver';

  protected $_mode = NULL;

  protected $_params = array();

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   */
  static private $_singleton = NULL;

  /**
   * Constructor.
   *
   * @param string $mode
   *   The mode of operation: live or test.
   *
   * @param $paymentProcessor
   *
   * @return \CRM_Core_Payment_AuthorizeNet
   */
  public function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('Authorize.net');

    $this->_setParam('apiLogin', $paymentProcessor['user_name']);
    $this->_setParam('paymentKey', $paymentProcessor['password']);
    $this->_setParam('paymentType', 'AIM');
    $this->_setParam('md5Hash', CRM_Utils_Array::value('signature', $paymentProcessor));

    $this->_setParam('timestamp', time());
    srand(time());
    $this->_setParam('sequence', rand(1, 1000));
  }

  /**
   * Should the first payment date be configurable when setting up back office recurring payments.
   * In the case of Authorize.net this is an option
   * @return bool
   */
  protected function supportsFutureRecurStartDate() {
    return TRUE;
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
   * Submit a payment using Advanced Integration Method.
   *
   * @param array $params
   *   Assoc array of input parameters for this transaction.
   *
   * @return array
   *   the result in a nice formatted array (or an error object)
   */
  public function doDirectPayment(&$params) {
    if (!defined('CURLOPT_SSLCERT')) {
      return self::error(9001, 'Authorize.Net requires curl with SSL support');
    }

    /*
     * recurpayment function does not compile an array & then process it -
     * - the tpl does the transformation so adding call to hook here
     * & giving it a change to act on the params array
     */
    $newParams = $params;
    if (!empty($params['is_recur']) && !empty($params['contributionRecurID'])) {
      CRM_Utils_Hook::alterPaymentProcessorParams($this,
        $params,
        $newParams
      );
    }
    foreach ($newParams as $field => $value) {
      $this->_setParam($field, $value);
    }

    if (!empty($params['is_recur']) && !empty($params['contributionRecurID'])) {
      $result = $this->doRecurPayment();
      if (is_a($result, 'CRM_Core_Error')) {
        return $result;
      }
      return $params;
    }

    $postFields = array();
    $authorizeNetFields = $this->_getAuthorizeNetFields();

    // Set up our call for hook_civicrm_paymentProcessor,
    // since we now have our parameters as assigned for the AIM back end.
    CRM_Utils_Hook::alterPaymentProcessorParams($this,
      $params,
      $authorizeNetFields
    );

    foreach ($authorizeNetFields as $field => $value) {
      // CRM-7419, since double quote is used as enclosure while doing csv parsing
      $value = ($field == 'x_description') ? str_replace('"', "'", $value) : $value;
      $postFields[] = $field . '=' . urlencode($value);
    }

    // Authorize.Net will not refuse duplicates, so we should check if the user already submitted this transaction
    if ($this->checkDupe($authorizeNetFields['x_invoice_num'], CRM_Utils_Array::value('contributionID', $params))) {
      return self::error(9004, 'It appears that this transaction is a duplicate.  Have you already submitted the form once?  If so there may have been a connection problem.  Check your email for a receipt from Authorize.net.  If you do not receive a receipt within 2 hours you can try your transaction again.  If you continue to have problems please contact the site administrator.');
    }

    $submit = curl_init($this->_paymentProcessor['url_site']);

    if (!$submit) {
      return self::error(9002, 'Could not initiate connection to payment gateway');
    }

    curl_setopt($submit, CURLOPT_POST, TRUE);
    curl_setopt($submit, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($submit, CURLOPT_POSTFIELDS, implode('&', $postFields));
    curl_setopt($submit, CURLOPT_SSL_VERIFYPEER, Civi::settings()->get('verifySSL'));

    $response = curl_exec($submit);

    if (!$response) {
      return self::error(curl_errno($submit), curl_error($submit));
    }

    curl_close($submit);

    $response_fields = $this->explode_csv($response);

    // fetch available contribution statuses
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    // check gateway MD5 response
    if (!$this->checkMD5($response_fields[37], $response_fields[6], $response_fields[9])) {
      $params['payment_status_id'] = array_search('Failed', $contributionStatus);
      return self::error(9003, 'MD5 Verification failed');
    }

    // check for application errors
    // TODO:
    // AVS, CVV2, CAVV, and other verification results
    switch ($response_fields[0]) {
      case self::AUTH_REVIEW:
        $params['payment_status_id'] = array_search('Pending', $contributionStatus);
        break;

      case self::AUTH_ERROR:
        $params['payment_status_id'] = array_search('Failed', $contributionStatus);
        $errormsg = $response_fields[2] . ' ' . $response_fields[3];
        return self::error($response_fields[1], $errormsg);

      case self::AUTH_DECLINED:
        $errormsg = $response_fields[2] . ' ' . $response_fields[3];
        return self::error($response_fields[1], $errormsg);

      default:
        // Success

        // test mode always returns trxn_id = 0
        // also live mode in CiviCRM with test mode set in
        // Authorize.Net return $response_fields[6] = 0
        // hence treat that also as test mode transaction
        // fix for CRM-2566
        if (($this->_mode == 'test') || $response_fields[6] == 0) {
          $query = "SELECT MAX(trxn_id) FROM civicrm_contribution WHERE trxn_id RLIKE 'test[0-9]+'";
          $p = array();
          $trxn_id = strval(CRM_Core_DAO::singleValueQuery($query, $p));
          $trxn_id = str_replace('test', '', $trxn_id);
          $trxn_id = intval($trxn_id) + 1;
          $params['trxn_id'] = sprintf('test%08d', $trxn_id);
        }
        else {
          $params['trxn_id'] = $response_fields[6];
        }
        $params['gross_amount'] = $response_fields[9];
        break;
    }
    // TODO: include authorization code?

    return $params;
  }

  /**
   * Submit an Automated Recurring Billing subscription.
   */
  public function doRecurPayment() {
    $template = CRM_Core_Smarty::singleton();

    $intervalLength = $this->_getParam('frequency_interval');
    $intervalUnit = $this->_getParam('frequency_unit');
    if ($intervalUnit == 'week') {
      $intervalLength *= 7;
      $intervalUnit = 'days';
    }
    elseif ($intervalUnit == 'year') {
      $intervalLength *= 12;
      $intervalUnit = 'months';
    }
    elseif ($intervalUnit == 'day') {
      $intervalUnit = 'days';
    }
    elseif ($intervalUnit == 'month') {
      $intervalUnit = 'months';
    }

    // interval cannot be less than 7 days or more than 1 year
    if ($intervalUnit == 'days') {
      if ($intervalLength < 7) {
        return self::error(9001, 'Payment interval must be at least one week');
      }
      elseif ($intervalLength > 365) {
        return self::error(9001, 'Payment interval may not be longer than one year');
      }
    }
    elseif ($intervalUnit == 'months') {
      if ($intervalLength < 1) {
        return self::error(9001, 'Payment interval must be at least one week');
      }
      elseif ($intervalLength > 12) {
        return self::error(9001, 'Payment interval may not be longer than one year');
      }
    }

    $template->assign('intervalLength', $intervalLength);
    $template->assign('intervalUnit', $intervalUnit);

    $template->assign('apiLogin', $this->_getParam('apiLogin'));
    $template->assign('paymentKey', $this->_getParam('paymentKey'));
    $template->assign('refId', substr($this->_getParam('invoiceID'), 0, 20));

    //for recurring, carry first contribution id
    $template->assign('invoiceNumber', $this->_getParam('contributionID'));
    $firstPaymentDate = $this->_getParam('receive_date');
    if (!empty($firstPaymentDate)) {
      //allow for post dated payment if set in form
      $startDate = date_create($firstPaymentDate);
    }
    else {
      $startDate = date_create();
    }
    /* Format start date in Mountain Time to avoid Authorize.net error E00017
     * we do this only if the day we are setting our start time to is LESS than the current
     * day in mountaintime (ie. the server time of the A-net server). A.net won't accept a date
     * earlier than the current date on it's server so if we are in PST we might need to use mountain
     * time to bring our date forward. But if we are submitting something future dated we want
     * the date we entered to be respected
     */
    $minDate = date_create('now', new DateTimeZone(self::TIMEZONE));
    if (strtotime($startDate->format('Y-m-d')) < strtotime($minDate->format('Y-m-d'))) {
      $startDate->setTimezone(new DateTimeZone(self::TIMEZONE));
    }

    $template->assign('startDate', $startDate->format('Y-m-d'));

    $installments = $this->_getParam('installments');

    // for open ended subscription totalOccurrences has to be 9999
    $installments = empty($installments) ? 9999 : $installments;
    $template->assign('totalOccurrences', $installments);

    $template->assign('amount', $this->_getParam('amount'));

    $template->assign('cardNumber', $this->_getParam('credit_card_number'));
    $exp_month = str_pad($this->_getParam('month'), 2, '0', STR_PAD_LEFT);
    $exp_year = $this->_getParam('year');
    $template->assign('expirationDate', $exp_year . '-' . $exp_month);

    // name rather than description is used in the tpl - see http://www.authorize.net/support/ARB_guide.pdf
    $template->assign('name', $this->_getParam('description', TRUE));

    $template->assign('email', $this->_getParam('email'));
    $template->assign('contactID', $this->_getParam('contactID'));
    $template->assign('billingFirstName', $this->_getParam('billing_first_name'));
    $template->assign('billingLastName', $this->_getParam('billing_last_name'));
    $template->assign('billingAddress', $this->_getParam('street_address', TRUE));
    $template->assign('billingCity', $this->_getParam('city', TRUE));
    $template->assign('billingState', $this->_getParam('state_province'));
    $template->assign('billingZip', $this->_getParam('postal_code', TRUE));
    $template->assign('billingCountry', $this->_getParam('country'));

    $arbXML = $template->fetch('CRM/Contribute/Form/Contribution/AuthorizeNetARB.tpl');
    // submit to authorize.net

    $submit = curl_init($this->_paymentProcessor['url_recur']);
    if (!$submit) {
      return self::error(9002, 'Could not initiate connection to payment gateway');
    }
    curl_setopt($submit, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($submit, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
    curl_setopt($submit, CURLOPT_HEADER, 1);
    curl_setopt($submit, CURLOPT_POSTFIELDS, $arbXML);
    curl_setopt($submit, CURLOPT_POST, 1);
    curl_setopt($submit, CURLOPT_SSL_VERIFYPEER, Civi::settings()->get('verifySSL'));

    $response = curl_exec($submit);

    if (!$response) {
      return self::error(curl_errno($submit), curl_error($submit));
    }

    curl_close($submit);
    $responseFields = $this->_ParseArbReturn($response);

    if ($responseFields['resultCode'] == 'Error') {
      return self::error($responseFields['code'], $responseFields['text']);
    }

    // update recur processor_id with subscriptionId
    CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionRecur', $this->_getParam('contributionRecurID'),
      'processor_id', $responseFields['subscriptionId']
    );
    //only impact of assigning this here is is can be used to cancel the subscription in an automated test
    // if it isn't cancelled a duplicate transaction error occurs
    if (!empty($responseFields['subscriptionId'])) {
      $this->_setParam('subscriptionId', $responseFields['subscriptionId']);
    }
  }

  /**
   * @return array
   */
  public function _getAuthorizeNetFields() {
    $amount = $this->_getParam('total_amount');//Total amount is from the form contribution field
    if (empty($amount)) {//CRM-9894 would this ever be the case??
      $amount = $this->_getParam('amount');
    }
    $fields = array();
    $fields['x_login'] = $this->_getParam('apiLogin');
    $fields['x_tran_key'] = $this->_getParam('paymentKey');
    $fields['x_email_customer'] = $this->_getParam('emailCustomer');
    $fields['x_first_name'] = $this->_getParam('billing_first_name');
    $fields['x_last_name'] = $this->_getParam('billing_last_name');
    $fields['x_address'] = $this->_getParam('street_address');
    $fields['x_city'] = $this->_getParam('city');
    $fields['x_state'] = $this->_getParam('state_province');
    $fields['x_zip'] = $this->_getParam('postal_code');
    $fields['x_country'] = $this->_getParam('country');
    $fields['x_customer_ip'] = $this->_getParam('ip_address');
    $fields['x_email'] = $this->_getParam('email');
    $fields['x_invoice_num'] = $this->_getParam('invoiceID');
    $fields['x_amount'] = $amount;
    $fields['x_currency_code'] = $this->_getParam('currencyID');
    $fields['x_description'] = $this->_getParam('description');
    $fields['x_cust_id'] = $this->_getParam('contactID');
    if ($this->_getParam('paymentType') == 'AIM') {
      $fields['x_relay_response'] = 'FALSE';
      // request response in CSV format
      $fields['x_delim_data'] = 'TRUE';
      $fields['x_delim_char'] = ',';
      $fields['x_encap_char'] = '"';
      // cc info
      $fields['x_card_num'] = $this->_getParam('credit_card_number');
      $fields['x_card_code'] = $this->_getParam('cvv2');
      $exp_month = str_pad($this->_getParam('month'), 2, '0', STR_PAD_LEFT);
      $exp_year = $this->_getParam('year');
      $fields['x_exp_date'] = "$exp_month/$exp_year";
    }

    if ($this->_mode != 'live') {
      $fields['x_test_request'] = 'TRUE';
    }

    return $fields;
  }

  /**
   * Generate HMAC_MD5
   *
   * @param string $key
   * @param string $data
   *
   * @return string
   *   the HMAC_MD5 encoding string
   */
  public function hmac($key, $data) {
    if (function_exists('mhash')) {
      // Use PHP mhash extension
      return (bin2hex(mhash(MHASH_MD5, $data, $key)));
    }
    else {
      // RFC 2104 HMAC implementation for php.
      // Creates an md5 HMAC.
      // Eliminates the need to install mhash to compute a HMAC
      // Hacked by Lance Rushing
      // byte length for md5
      $b = 64;
      if (strlen($key) > $b) {
        $key = pack("H*", md5($key));
      }
      $key = str_pad($key, $b, chr(0x00));
      $ipad = str_pad('', $b, chr(0x36));
      $opad = str_pad('', $b, chr(0x5c));
      $k_ipad = $key ^ $ipad;
      $k_opad = $key ^ $opad;
      return md5($k_opad . pack("H*", md5($k_ipad . $data)));
    }
  }

  /**
   * Check the gateway MD5 response to make sure that this is a proper
   * gateway response
   *
   * @param string $responseMD5
   *   MD5 hash generated by the gateway.
   * @param string $transaction_id
   *   Transaction id generated by the gateway.
   * @param string $amount
   *   Purchase amount.
   *
   * @param bool $ipn
   *
   * @return bool
   */
  public function checkMD5($responseMD5, $transaction_id, $amount, $ipn = FALSE) {
    // cannot check if no MD5 hash
    $md5Hash = $this->_getParam('md5Hash');
    if (empty($md5Hash)) {
      return TRUE;
    }
    $loginid = $this->_getParam('apiLogin');
    $hashString = $ipn ? ($md5Hash . $transaction_id . $amount) : ($md5Hash . $loginid . $transaction_id . $amount);
    $result = strtoupper(md5($hashString));

    if ($result == $responseMD5) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Calculate and return the transaction fingerprint.
   *
   * @return string
   *   fingerprint
   */
  public function CalculateFP() {
    $x_tran_key = $this->_getParam('paymentKey');
    $loginid = $this->_getParam('apiLogin');
    $sequence = $this->_getParam('sequence');
    $timestamp = $this->_getParam('timestamp');
    $amount = $this->_getParam('amount');
    $currency = $this->_getParam('currencyID');
    $transaction = "$loginid^$sequence^$timestamp^$amount^$currency";
    return $this->hmac($x_tran_key, $transaction);
  }

  /**
   * Split a CSV file.  Requires , as delimiter and " as enclosure.
   * Based off notes from http://php.net/fgetcsv
   *
   * @param string $data
   *   A single CSV line.
   *
   * @return array
   *   CSV fields
   */
  public function explode_csv($data) {
    $data = trim($data);
    //make it easier to parse fields with quotes in them
    $data = str_replace('""', "''", $data);
    $fields = array();

    while ($data != '') {
      $matches = array();
      if ($data[0] == '"') {
        // handle quoted fields
        preg_match('/^"(([^"]|\\")*?)",?(.*)$/', $data, $matches);

        $fields[] = str_replace("''", '"', $matches[1]);
        $data = $matches[3];
      }
      else {
        preg_match('/^([^,]*),?(.*)$/', $data, $matches);

        $fields[] = $matches[1];
        $data = $matches[2];
      }
    }
    return $fields;
  }

  /**
   * Extract variables from returned XML.
   *
   * Function is from Authorize.Net sample code, and used
   * to prevent the requirement of XML functions.
   *
   * @param string $content
   *   XML reply from Authorize.Net.
   *
   * @return array
   *   refId, resultCode, code, text, subscriptionId
   */
  public function _parseArbReturn($content) {
    $refId = $this->_substring_between($content, '<refId>', '</refId>');
    $resultCode = $this->_substring_between($content, '<resultCode>', '</resultCode>');
    $code = $this->_substring_between($content, '<code>', '</code>');
    $text = $this->_substring_between($content, '<text>', '</text>');
    $subscriptionId = $this->_substring_between($content, '<subscriptionId>', '</subscriptionId>');
    return array(
      'refId' => $refId,
      'resultCode' => $resultCode,
      'code' => $code,
      'text' => $text,
      'subscriptionId' => $subscriptionId,
    );
  }

  /**
   * Helper function for _parseArbReturn.
   *
   * Function is from Authorize.Net sample code, and used to avoid using
   * PHP5 XML functions
   *
   * @param string $haystack
   * @param string $start
   * @param string $end
   *
   * @return bool|string
   */
  public function _substring_between(&$haystack, $start, $end) {
    if (strpos($haystack, $start) === FALSE || strpos($haystack, $end) === FALSE) {
      return FALSE;
    }
    else {
      $start_position = strpos($haystack, $start) + strlen($start);
      $end_position = strpos($haystack, $end);
      return substr($haystack, $start_position, $end_position - $start_position);
    }
  }

  /**
   * Get the value of a field if set.
   *
   * @param string $field
   *   The field.
   *
   * @param bool $xmlSafe
   * @return mixed
   *   value of the field, or empty string if the field is
   *   not set
   */
  public function _getParam($field, $xmlSafe = FALSE) {
    $value = CRM_Utils_Array::value($field, $this->_params, '');
    if ($xmlSafe) {
      $value = str_replace(array('&', '"', "'", '<', '>'), '', $value);
    }
    return $value;
  }

  /**
   * @param null $errorCode
   * @param null $errorMessage
   *
   * @return object
   */
  public function &error($errorCode = NULL, $errorMessage = NULL) {
    $e = CRM_Core_Error::singleton();
    if ($errorCode) {
      $e->push($errorCode, 0, array(), $errorMessage);
    }
    else {
      $e->push(9001, 0, array(), 'Unknown System Error.');
    }
    return $e;
  }

  /**
   * Set a field to the specified value.  Value must be a scalar (int,
   * float, string, or boolean)
   *
   * @param string $field
   * @param mixed $value
   *
   * @return bool
   *   false if value is not a scalar, true if successful
   */
  public function _setParam($field, $value) {
    if (!is_scalar($value)) {
      return FALSE;
    }
    else {
      $this->_params[$field] = $value;
    }
  }

  /**
   * This function checks to see if we have the right config values.
   *
   * @return string
   *   the error message if any
   */
  public function checkConfig() {
    $error = array();
    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('APILogin is not set for this payment processor');
    }

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('Key is not set for this payment processor');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  /**
   * @return string
   */
  public function accountLoginURL() {
    return ($this->_mode == 'test') ? 'https://test.authorize.net' : 'https://authorize.net';
  }

  /**
   * @param string $message
   * @param array $params
   *
   * @return bool|object
   */
  public function cancelSubscription(&$message = '', $params = array()) {
    $template = CRM_Core_Smarty::singleton();

    $template->assign('subscriptionType', 'cancel');

    $template->assign('apiLogin', $this->_getParam('apiLogin'));
    $template->assign('paymentKey', $this->_getParam('paymentKey'));
    $template->assign('subscriptionId', CRM_Utils_Array::value('subscriptionId', $params));

    $arbXML = $template->fetch('CRM/Contribute/Form/Contribution/AuthorizeNetARB.tpl');

    // submit to authorize.net
    $submit = curl_init($this->_paymentProcessor['url_recur']);
    if (!$submit) {
      return self::error(9002, 'Could not initiate connection to payment gateway');
    }

    curl_setopt($submit, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($submit, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
    curl_setopt($submit, CURLOPT_HEADER, 1);
    curl_setopt($submit, CURLOPT_POSTFIELDS, $arbXML);
    curl_setopt($submit, CURLOPT_POST, 1);
    curl_setopt($submit, CURLOPT_SSL_VERIFYPEER, Civi::settings()->get('verifySSL'));

    $response = curl_exec($submit);

    if (!$response) {
      return self::error(curl_errno($submit), curl_error($submit));
    }

    curl_close($submit);

    $responseFields = $this->_ParseArbReturn($response);
    $message = "{$responseFields['code']}: {$responseFields['text']}";

    if ($responseFields['resultCode'] == 'Error') {
      return self::error($responseFields['code'], $responseFields['text']);
    }
    return TRUE;
  }

  /**
   * @param string $message
   * @param array $params
   *
   * @return bool|object
   */
  public function updateSubscriptionBillingInfo(&$message = '', $params = array()) {
    $template = CRM_Core_Smarty::singleton();
    $template->assign('subscriptionType', 'updateBilling');

    $template->assign('apiLogin', $this->_getParam('apiLogin'));
    $template->assign('paymentKey', $this->_getParam('paymentKey'));
    $template->assign('subscriptionId', $params['subscriptionId']);

    $template->assign('cardNumber', $params['credit_card_number']);
    $exp_month = str_pad($params['month'], 2, '0', STR_PAD_LEFT);
    $exp_year = $params['year'];
    $template->assign('expirationDate', $exp_year . '-' . $exp_month);

    $template->assign('billingFirstName', $params['first_name']);
    $template->assign('billingLastName', $params['last_name']);
    $template->assign('billingAddress', $params['street_address']);
    $template->assign('billingCity', $params['city']);
    $template->assign('billingState', $params['state_province']);
    $template->assign('billingZip', $params['postal_code']);
    $template->assign('billingCountry', $params['country']);

    $arbXML = $template->fetch('CRM/Contribute/Form/Contribution/AuthorizeNetARB.tpl');

    // submit to authorize.net
    $submit = curl_init($this->_paymentProcessor['url_recur']);
    if (!$submit) {
      return self::error(9002, 'Could not initiate connection to payment gateway');
    }

    curl_setopt($submit, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($submit, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
    curl_setopt($submit, CURLOPT_HEADER, 1);
    curl_setopt($submit, CURLOPT_POSTFIELDS, $arbXML);
    curl_setopt($submit, CURLOPT_POST, 1);
    curl_setopt($submit, CURLOPT_SSL_VERIFYPEER, Civi::settings()->get('verifySSL'));

    $response = curl_exec($submit);

    if (!$response) {
      return self::error(curl_errno($submit), curl_error($submit));
    }

    curl_close($submit);

    $responseFields = $this->_ParseArbReturn($response);
    $message = "{$responseFields['code']}: {$responseFields['text']}";

    if ($responseFields['resultCode'] == 'Error') {
      return self::error($responseFields['code'], $responseFields['text']);
    }
    return TRUE;
  }

  /**
   * Process incoming notification.
   */
  static public function handlePaymentNotification() {
    $ipnClass = new CRM_Core_Payment_AuthorizeNetIPN(array_merge($_GET, $_REQUEST));
    $ipnClass->main();
  }

  /**
   * @param string $message
   * @param array $params
   *
   * @return bool|object
   */
  public function changeSubscriptionAmount(&$message = '', $params = array()) {
    $template = CRM_Core_Smarty::singleton();

    $template->assign('subscriptionType', 'update');

    $template->assign('apiLogin', $this->_getParam('apiLogin'));
    $template->assign('paymentKey', $this->_getParam('paymentKey'));

    $template->assign('subscriptionId', $params['subscriptionId']);

    // for open ended subscription totalOccurrences has to be 9999
    $installments = empty($params['installments']) ? 9999 : $params['installments'];
    $template->assign('totalOccurrences', $installments);

    $template->assign('amount', $params['amount']);

    $arbXML = $template->fetch('CRM/Contribute/Form/Contribution/AuthorizeNetARB.tpl');

    // submit to authorize.net
    $submit = curl_init($this->_paymentProcessor['url_recur']);
    if (!$submit) {
      return self::error(9002, 'Could not initiate connection to payment gateway');
    }

    curl_setopt($submit, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($submit, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
    curl_setopt($submit, CURLOPT_HEADER, 1);
    curl_setopt($submit, CURLOPT_POSTFIELDS, $arbXML);
    curl_setopt($submit, CURLOPT_POST, 1);
    curl_setopt($submit, CURLOPT_SSL_VERIFYPEER, Civi::settings()->get('verifySSL'));

    $response = curl_exec($submit);

    if (!$response) {
      return self::error(curl_errno($submit), curl_error($submit));
    }

    curl_close($submit);

    $responseFields = $this->_parseArbReturn($response);
    $message = "{$responseFields['code']}: {$responseFields['text']}";

    if ($responseFields['resultCode'] == 'Error') {
      return self::error($responseFields['code'], $responseFields['text']);
    }
    return TRUE;
  }

}
