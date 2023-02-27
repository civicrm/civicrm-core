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

use Civi\Payment\Exception\PaymentProcessorException;

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

  protected $_params = [];

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

    $this->_setParam('apiLogin', $paymentProcessor['user_name']);
    $this->_setParam('paymentKey', $paymentProcessor['password']);
    $this->_setParam('paymentType', 'AIM');
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
   * @param array|\Civi\Payment\PropertyBag $params
   *
   * @param string $component
   *
   * @return array
   *   Result array (containing at least the key payment_status_id)
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function doPayment(&$params, $component = 'contribute') {
    $propertyBag = \Civi\Payment\PropertyBag::cast($params);
    $this->_component = $component;
    $statuses = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'validate');

    // If we have a $0 amount, skip call to processor and set payment_status to Completed.
    // Conceivably a processor might override this - perhaps for setting up a token - but we don't
    // have an example of that at the moment.
    if ($propertyBag->getAmount() == 0) {
      $result['payment_status_id'] = array_search('Completed', $statuses);
      $result['payment_status'] = 'Completed';
      return $result;
    }

    if (!defined('CURLOPT_SSLCERT')) {
      // Note that guzzle doesn't necessarily require CURL, although it prefers it. But we should leave this error
      // here unless someone suggests it is not required since it's likely helpful.
      throw new PaymentProcessorException('Authorize.Net requires curl with SSL support', 9001);
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
      $this->doRecurPayment();
      $params['payment_status_id'] = array_search('Pending', $statuses);
      $params['payment_status'] = 'Pending';
      return $params;
    }

    $postFields = [];
    $authorizeNetFields = $this->_getAuthorizeNetFields();

    // Set up our call for hook_civicrm_paymentProcessor,
    // since we now have our parameters as assigned for the AIM back end.
    CRM_Utils_Hook::alterPaymentProcessorParams($this,
      $params,
      $authorizeNetFields
    );

    foreach ($authorizeNetFields as $field => $value) {
      // CRM-7419, since double quote is used as enclosure while doing csv parsing
      $value = ($field === 'x_description') ? str_replace('"', "'", $value) : $value;
      $postFields[] = $field . '=' . urlencode($value);
    }

    // Authorize.Net will not refuse duplicates, so we should check if the user already submitted this transaction
    if ($this->checkDupe($authorizeNetFields['x_invoice_num'], $params['contributionID'] ?? NULL)) {
      throw new PaymentProcessorException('It appears that this transaction is a duplicate.  Have you already submitted the form once?  If so there may have been a connection problem.  Check your email for a receipt from Authorize.net.  If you do not receive a receipt within 2 hours you can try your transaction again.  If you continue to have problems please contact the site administrator.', 9004);
    }

    $response = (string) $this->getGuzzleClient()->post($this->_paymentProcessor['url_site'], [
      'body' => implode('&', $postFields),
      'curl' => [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_SSL_VERIFYPEER => Civi::settings()->get('verifySSL'),
      ],
    ])->getBody();

    $response_fields = $this->explode_csv($response);

    $result = [];
    // check for application errors
    // TODO:
    // AVS, CVV2, CAVV, and other verification results
    switch ($response_fields[0]) {
      case self::AUTH_REVIEW:
        $result = $this->setStatusPaymentPending($result);
        break;

      case self::AUTH_ERROR:
        $errormsg = $response_fields[2] . ' ' . $response_fields[3];
        throw new PaymentProcessorException($errormsg, $response_fields[1]);

      case self::AUTH_DECLINED:
        $errormsg = $response_fields[2] . ' ' . $response_fields[3];
        throw new PaymentProcessorException($errormsg, $response_fields[1]);

      default:
        // Success
        $result['trxn_id'] = !empty($response_fields[6]) ? $response_fields[6] : $this->getTestTrxnID();
        $result = $this->setStatusPaymentCompleted($result);
        break;
    }

    return $result;
  }

  /**
   * Submit an Automated Recurring Billing subscription.
   */
  public function doRecurPayment() {
    $template = CRM_Core_Smarty::singleton();

    $intervalLength = $this->_getParam('frequency_interval');
    $intervalUnit = $this->_getParam('frequency_unit');
    if ($intervalUnit === 'week') {
      $intervalLength *= 7;
      $intervalUnit = 'days';
    }
    elseif ($intervalUnit === 'year') {
      $intervalLength *= 12;
      $intervalUnit = 'months';
    }
    elseif ($intervalUnit === 'day') {
      $intervalUnit = 'days';
      // interval cannot be less than 7 days or more than 1 year
      if ($intervalLength < 7) {
        throw new PaymentProcessorException('Payment interval must be at least one week', 9001);
      }
      if ($intervalLength > 365) {
        throw new PaymentProcessorException('Payment interval may not be longer than one year', 9001);
      }
    }
    elseif ($intervalUnit === 'month') {
      $intervalUnit = 'months';
      if ($intervalLength < 1) {
        throw new PaymentProcessorException('Payment interval must be at least one week', 9001);
      }
      if ($intervalLength > 12) {
        throw new PaymentProcessorException('Payment interval may not be longer than one year', 9001);
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
    // Required to be set for s
    $template->ensureVariablesAreAssigned(['subscriptionType']);
    $arbXML = $template->fetch('CRM/Contribute/Form/Contribution/AuthorizeNetARB.tpl');

    // Submit to authorize.net
    $response = $this->getGuzzleClient()->post($this->_paymentProcessor['url_recur'], [
      'headers' => [
        'Content-Type' => 'text/xml; charset=UTF8',
      ],
      'body' => $arbXML,
      'curl' => [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_SSL_VERIFYPEER => Civi::settings()->get('verifySSL'),
      ],
    ]);
    $responseFields = $this->_ParseArbReturn((string) $response->getBody());

    if ($responseFields['resultCode'] === 'Error') {
      throw new PaymentProcessorException($responseFields['text'], $responseFields['code']);
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
    //Total amount is from the form contribution field
    $amount = $this->_getParam('total_amount');
    //CRM-9894 would this ever be the case??
    if (empty($amount)) {
      $amount = $this->_getParam('amount');
    }
    $fields = [];
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
    if ($this->_getParam('paymentType') === 'AIM') {
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

    if ($this->_mode !== 'live') {
      $fields['x_test_request'] = 'TRUE';
    }

    return $fields;
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
    $fields = [];

    while ($data != '') {
      $matches = [];
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
    return [
      'refId' => $refId,
      'resultCode' => $resultCode,
      'code' => $code,
      'text' => $text,
      'subscriptionId' => $subscriptionId,
    ];
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
      $value = str_replace(['&', '"', "'", '<', '>'], '', $value);
    }
    return $value;
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
    $error = [];
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
    return ($this->_mode === 'test') ? 'https://test.authorize.net' : 'https://authorize.net';
  }

  /**
   * @param string $message
   * @param \Civi\Payment\PropertyBag $params
   *
   * @return bool|object
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function cancelSubscription(&$message = '', $params = []) {
    $template = CRM_Core_Smarty::singleton();

    $template->assign('subscriptionType', 'cancel');

    $template->assign('apiLogin', $this->_getParam('apiLogin'));
    $template->assign('paymentKey', $this->_getParam('paymentKey'));
    $template->assign('subscriptionId', $params->getRecurProcessorID());

    $arbXML = $template->fetch('CRM/Contribute/Form/Contribution/AuthorizeNetARB.tpl');

    $response = (string) $this->getGuzzleClient()->post($this->_paymentProcessor['url_recur'], [
      'headers' => [
        'Content-Type' => 'text/xml; charset=UTF8',
      ],
      'body' => $arbXML,
      'curl' => [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_SSL_VERIFYPEER => Civi::settings()->get('verifySSL'),
      ],
    ])->getBody();

    $responseFields = $this->_ParseArbReturn($response);
    $message = "{$responseFields['code']}: {$responseFields['text']}";

    if ($responseFields['resultCode'] === 'Error') {
      throw new PaymentProcessorException($responseFields['text'], $responseFields['code']);
    }
    return TRUE;
  }

  /**
   * Update payment details at Authorize.net.
   *
   * @param string $message
   * @param array $params
   *
   * @return bool|object
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function updateSubscriptionBillingInfo(&$message = '', $params = []) {
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

    $response = (string) $this->getGuzzleClient()->post($this->_paymentProcessor['url_recur'], [
      'headers' => [
        'Content-Type' => 'text/xml; charset=UTF8',
      ],
      'body' => $arbXML,
      'curl' => [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_SSL_VERIFYPEER => Civi::settings()->get('verifySSL'),
      ],
    ])->getBody();

    // submit to authorize.net
    $responseFields = $this->_ParseArbReturn($response);
    $message = "{$responseFields['code']}: {$responseFields['text']}";

    if ($responseFields['resultCode'] === 'Error') {
      throw new PaymentProcessorException($responseFields['text'], $responseFields['code']);
    }
    return TRUE;
  }

  /**
   * Process incoming notification.
   */
  public function handlePaymentNotification() {
    $ipnClass = new CRM_Core_Payment_AuthorizeNetIPN(array_merge($_GET, $_REQUEST));
    $ipnClass->main();
  }

  /**
   * Change the amount of the recurring payment.
   *
   * @param string $message
   * @param array $params
   *
   * @return bool|object
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function changeSubscriptionAmount(&$message = '', $params = []) {
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

    $response = (string) $this->getGuzzleClient()->post($this->_paymentProcessor['url_recur'], [
      'headers' => [
        'Content-Type' => 'text/xml; charset=UTF8',
      ],
      'body' => $arbXML,
      'curl' => [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_SSL_VERIFYPEER => Civi::settings()->get('verifySSL'),
      ],
    ])->getBody();

    $responseFields = $this->_parseArbReturn($response);
    $message = "{$responseFields['code']}: {$responseFields['text']}";

    if ($responseFields['resultCode'] === 'Error') {
      throw new PaymentProcessorException($responseFields['text'], $responseFields['code']);
    }
    return TRUE;
  }

  /**
   * Get an appropriate test trannsaction id.
   *
   * @return string
   */
  protected function getTestTrxnID() {
    // test mode always returns trxn_id = 0
    // also live mode in CiviCRM with test mode set in
    // Authorize.Net return $response_fields[6] = 0
    // hence treat that also as test mode transaction
    // fix for CRM-2566
    $query = "SELECT MAX(trxn_id) FROM civicrm_contribution WHERE trxn_id RLIKE 'test[0-9]+'";
    $trxn_id = (string) (CRM_Core_DAO::singleValueQuery($query));
    $trxn_id = str_replace('test', '', $trxn_id);
    $trxn_id = (int) ($trxn_id) + 1;
    return sprintf('test%08d', $trxn_id);
  }

}
