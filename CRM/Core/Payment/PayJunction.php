<?php

/**
 * Copyright (C) 2007
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * Written and contributed by Phase2 Technology, LLC (http://www.phase2technology.com)
 *
 */

use Civi\Payment\Exception\PaymentProcessorException;

/**
 *
 * @package CRM
 * @author Michael Morris and Gene Chi @ Phase2 Technology <mmorris@phase2technology.com>
 */
require_once 'PayJunction/pjClasses.php';

/**
 * Class CRM_Core_Payment_PayJunction.
 */
class CRM_Core_Payment_PayJunction extends CRM_Core_Payment {
  // (not used, implicit in the API, might need to convert?)
  const CHARSET = 'UFT-8';

  /**
   * Constructor.
   *
   * @param string $mode
   *   The mode of operation: live or test.
   *
   * @param array $paymentProcessor
   */
  public function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
  }

  /**
   * This function sends request and receives response from
   * PayJunction payment process
   *
   * @param array $params
   *   Assoc array of input parameters for this transaction.
   *
   * @return array
   *   the result in an nice formatted array (or an error object)
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function doDirectPayment(&$params) {
    $logon = $this->_paymentProcessor['user_name'];
    $password = $this->_paymentProcessor['password'];
    $url_site = $this->_paymentProcessor['url_site'];

    // create pjpgCustInfo object
    $pjpgCustInfo = new pjpgCustInfo();

    $pjpgCustInfo->setEmail($params['email']);

    $billing = [
      'logon' => $logon,
      'password' => $password,
      'url_site' => $url_site,
      'first_name' => $params['first_name'],
      'last_name' => $params['last_name'],
      'address' => $params['street_address'],
      'city' => $params['city'],
      'province' => $params['state_province'],
      'postal_code' => $params['postal_code'],
      'country' => $params['country'],
    ];
    $pjpgCustInfo->setBilling($billing);

    // create pjpgTransaction object
    $my_orderid = $params['invoiceID'];

    $expiry_string = sprintf('%04d%02d', $params['year'], $params['month']);

    $txnArray = [
      'type' => 'purchase',
      'order_id' => $my_orderid,
      'amount' => sprintf('%01.2f', $params['amount']),
      'pan' => $params['credit_card_number'],
      'expdate' => $expiry_string,
      'crypt_type' => '7',
      'cavv' => $params['cvv2'],
      'cust_id' => $params['contact_id'],
    ];

    // Allow further manipulation of params via custom hooks
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $txnArray);

    $pjpgTxn = new pjpgTransaction($txnArray);

    // set customer info (level 3 data) for the transaction
    $pjpgTxn->setCustInfo($pjpgCustInfo);

    // empty installments convert to 999 because PayJunction do not allow open end donation
    if ($params['installments'] === '') {
      $params['installments'] = '999';
    }

    // create recurring object
    if ($params['is_recur'] == TRUE && $params['installments'] > 1) {
      // schedule start date as today
      // format: YYYY-MM-DD
      $params['dc_schedule_start'] = date("Y-m-d");

      // Recur Variables
      $dc_schedule_create = $params['is_recur'];
      $recurUnit = $params['frequency_unit'];
      $recurInterval = $params['frequency_interval'];
      $dc_schedule_start = $params['dc_schedule_start'];

      // next payment in moneris required format
      $startDate = date("Y/m/d", $next);

      $numRecurs = $params['installments'];

      $recurArray = [
        'dc_schedule_create' => $dc_schedule_create,
        // (day | week | month)
        'recur_unit' => $recurUnit,
        // yyyy/mm/dd
        'start_date' => $startDate,
        'num_recurs' => $numRecurs,
        'start_now' => 'false',
        'period' => $recurInterval,
        'dc_schedule_start' => $dc_schedule_start,
        'amount' => sprintf('%01.2f', $params['amount']),
      ];

      $pjpgRecur = new pjpgRecur($recurArray);

      $pjpgTxn->setRecur($pjpgRecur);
    }

    // create a pjpgRequest object passing the transaction object
    $pjpgRequest = new pjpgRequest($pjpgTxn);

    $pjpgHttpPost = new pjpgHttpsPost($pjpgRequest);

    // get an pjpgResponse object
    $pjpgResponse = $pjpgHttpPost->getPJpgResponse();

    if (self::isError($pjpgResponse)) {
      throw new PaymentProcessorException($pjpgResponse);
    }

    // Success
    $params['trxn_result_code'] = $pjpgResponse['dc_response_code'];
    $params['trxn_id'] = $pjpgResponse['dc_transaction_id'];

    return $params;
  }

  // end function doDirectPayment

  /**
   * This function checks the PayJunction response code.
   *
   * @param array $response
   *
   * @return bool
   */
  public function isError(&$response) {
    $responseCode = $response['dc_response_code'];

    if ($responseCode === "00" || $responseCode === "85") {
      return FALSE;
    }
    return TRUE;

  }

  /**
   * Get the value of a field if set.
   *
   * @param string $field
   *   The field.
   *
   * @return mixed
   *   value of the field, or empty string if the field is
   *   not set
   */
  public function _getParam($field) {
    if (isset($this->_params[$field])) {
      return $this->_params[$field];
    }
    return '';
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
      $error[] = ts('Username is not set for this payment processor');
    }

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('Password is not set for this payment processor');
    }

    if (empty($this->_paymentProcessor['url_site'])) {
      $error[] = ts('Site URL is not set for this payment processor');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    return NULL;
  }

}
// end class CRM_Core_Payment_PayJunction
