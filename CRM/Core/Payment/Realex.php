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

/*
 * Copyright (C) 2009
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * Written and contributed by Kirkdesigns (http://www.kirkdesigns.co.uk)
 */

use Civi\Payment\Exception\PaymentProcessorException;

/**
 *
 * @package CRM
 * @author Tom Kirkpatrick <tkp@kirkdesigns.co.uk>
 */
class CRM_Core_Payment_Realex extends CRM_Core_Payment {
  const AUTH_APPROVED = '00';

  protected $_mode;

  protected $_params = [];

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

    $this->_setParam('merchant_ref', $paymentProcessor['user_name']);
    $this->_setParam('secret', $paymentProcessor['password']);
    $this->_setParam('account', $paymentProcessor['subject']);

    $this->_setParam('emailCustomer', 'TRUE');
    srand(time());
    $this->_setParam('sequence', rand(1, 1000));
  }

  /**
   * Submit a payment using Advanced Integration Method.
   *
   * @param array|PropertyBag $params
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
    $result = $this->setStatusPaymentPending([]);

    // If we have a $0 amount, skip call to processor and set payment_status to Completed.
    // Conceivably a processor might override this - perhaps for setting up a token - but we don't
    // have an example of that at the moment.
    if ($propertyBag->getAmount() == 0) {
      $result = $this->setStatusPaymentCompleted($result);
      return $result;
    }

    if (!defined('CURLOPT_SSLCERT')) {
      throw new PaymentProcessorException(ts('RealAuth requires curl with SSL support'), 9001);
    }

    $this->setRealexFields($params);

    /**********************************************************
     * Check to see if we have a duplicate before we send
     **********************************************************/
    if ($this->checkDupe($params['invoiceID'], $params['contributionID'] ?? NULL)) {
      throw new PaymentProcessorException(ts('It appears that this transaction is a duplicate.  Have you already submitted the form once?  If so there may have been a connection problem.  Check your email for a receipt from Authorize.net.  If you do not receive a receipt within 2 hours you can try your transaction again.  If you continue to have problems please contact the site administrator.'), 9004);
    }

    // Create sha1 hash for request
    $hashme = "{$this->_getParam('timestamp')}.{$this->_getParam('merchant_ref')}.{$this->_getParam('order_id')}.{$this->_getParam('amount')}.{$this->_getParam('currency')}.{$this->_getParam('card_number')}";
    $sha1hash = sha1($hashme);
    $hashme = "$sha1hash.{$this->_getParam('secret')}";
    $sha1hash = sha1($hashme);

    // Generate the request xml that is send to Realex Payments.
    $request_xml = "<request type='auth' timestamp='{$this->_getParam('timestamp')}'>
          <merchantid>{$this->_getParam('merchant_ref')}</merchantid>
          <account>{$this->_getParam('account')}</account>
          <orderid>{$this->_getParam('order_id')}</orderid>
          <amount currency='{$this->_getParam('currency')}'>{$this->_getParam('amount')}</amount>
          <card>
        <number>{$this->_getParam('card_number')}</number>
        <expdate>{$this->_getParam('exp_date')}</expdate>
        <type>{$this->_getParam('card_type')}</type>
        <chname>{$this->_getParam('card_name')}</chname>
        <issueno>{$this->_getParam('issue_number')}</issueno>
        <cvn>
            <number>{$this->_getParam('cvn')}</number>
            <presind>1</presind>
        </cvn>
          </card>
          <autosettle flag='1'/>
          <sha1hash>$sha1hash</sha1hash>
          <comments>
            <comment id='1'>{$this->_getParam('comments')}</comment>
          </comments>
          <tssinfo>
            <varref>{$this->_getParam('varref')}</varref>
          </tssinfo>
      </request>";

    /**********************************************************
     * Send to the payment processor using cURL
     **********************************************************/

    $submit = curl_init($this->_paymentProcessor['url_site']);

    if (!$submit) {
      throw new PaymentProcessorException(ts('Could not initiate connection to payment gateway'), 9002);
    }

    curl_setopt($submit, CURLOPT_HTTPHEADER, ['SOAPAction: ""']);
    curl_setopt($submit, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($submit, CURLOPT_TIMEOUT, 60);
    curl_setopt($submit, CURLOPT_SSL_VERIFYPEER, Civi::settings()->get('verifySSL'));
    curl_setopt($submit, CURLOPT_HEADER, 0);

    // take caching out of the picture
    curl_setopt($submit, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($submit, CURLOPT_FRESH_CONNECT, 1);

    // Apply the XML to our curl call
    curl_setopt($submit, CURLOPT_POST, 1);
    curl_setopt($submit, CURLOPT_POSTFIELDS, $request_xml);

    $response_xml = curl_exec($submit);

    if (!$response_xml) {
      throw new PaymentProcessorException(curl_error($submit), curl_errno($submit));
    }

    // Tidy up the response xml
    $response_xml = preg_replace("/[\s\t]/", " ", $response_xml);
    $response_xml = preg_replace("/[\n\r]/", "", $response_xml);

    // Parse the response xml
    $xml_parser = xml_parser_create();
    if (!xml_parse($xml_parser, $response_xml)) {
      throw new PaymentProcessorException('XML Error', 9003);
    }

    $response = $this->xml_parse_into_assoc($response_xml);
    $response = $response['#return']['RESPONSE'];

    // Log the Realex response for debugging
    // CRM_Core_Error::debug_var('REALEX --------- Response from Realex: ', $response, TRUE);

    // Return an error if authentication was not successful
    if ($response['RESULT'] !== self::AUTH_APPROVED) {
      throw new PaymentProcessorException($this->getDisplayError($response['RESULT'], ' ' . $response['MESSAGE']));
    }

    // FIXME: We are using the trxn_result_code column to store all these extra details since there
    // seems to be nowhere else to put them. This is THE WRONG THING TO DO!
    $extras = [
      'authcode' => $response['AUTHCODE'],
      'batch_id' => $response['BATCHID'],
      'message' => $response['MESSAGE'],
      'trxn_result_code' => $response['RESULT'],
    ];

    $params['trxn_result_code'] = serialize($extras);
    $params['currencyID'] = $this->_getParam('currency');
    $result['trxn_id'] = $response['PASREF'];
    $result['fee_amount'] = 0;
    $result = $this->setStatusPaymentCompleted($result);

    return $result;
  }

  /**
   * Helper function to convert XML string to multi-dimension array.
   *
   * @param string $xml
   *   an XML string.
   *
   * @return array
   *   An array of the result with following keys:
   */
  public function xml_parse_into_assoc($xml) {
    $input = [];
    $result = [];

    $result['#error'] = FALSE;
    $result['#return'] = NULL;

    $xmlparser = xml_parser_create();
    $ret = xml_parse_into_struct($xmlparser, $xml, $input);

    xml_parser_free($xmlparser);

    if (empty($input)) {
      $result['#return'] = $xml;
    }
    else {
      if ($ret > 0) {
        $result['#return'] = $this->_xml_parse($input);
      }
      else {
        $result['#error'] = ts('Error parsing XML result - error code = %1 at line %2 char %3',
          [
            1 => xml_get_error_code($xmlparser),
            2 => xml_get_current_line_number($xmlparser),
            3 => xml_get_current_column_number($xmlparser),
          ]
        );
      }
    }
    return $result;
  }

  /**
   * Private helper for  xml_parse_into_assoc, to recursively parsing the result.
   *
   * @param $input
   * @param int $depth
   *
   * @return array
   */
  public function _xml_parse($input, $depth = 1) {
    $output = [];
    $children = [];

    foreach ($input as $data) {
      if ($data['level'] == $depth) {
        switch ($data['type']) {
          case 'complete':
            $output[$data['tag']] = $data['value'] ?? '';
            break;

          case 'open':
            $children = [];
            break;

          case 'close':
            $output[$data['tag']] = $this->_xml_parse($children, $depth + 1);
            break;
        }
      }
      else {
        $children[] = $data;
      }
    }
    return $output;
  }

  /**
   * Format the params from the form ready for sending to Realex.
   *
   * Also perform some validation
   *
   * @param array $params
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function setRealexFields(&$params) {
    if ((int) $params['amount'] <= 0) {
      throw new PaymentProcessorException(ts('Amount must be positive'), 9001);
    }

    // format amount to be in smallest possible units
    //list($bills, $pennies) = explode('.', $params['amount']);
    $this->_setParam('amount', 100 * $params['amount']);

    switch (strtolower($params['credit_card_type'])) {
      case 'mastercard':
        $this->_setParam('card_type', 'MC');
        $this->_setParam('requiresIssueNumber', FALSE);
        break;

      case 'visa':
        $this->_setParam('card_type', 'VISA');
        $this->_setParam('requiresIssueNumber', FALSE);
        break;

      case 'amex':
        $this->_setParam('card_type', 'AMEX');
        $this->_setParam('requiresIssueNumber', FALSE);
        break;

      case 'laser':
        $this->_setParam('card_type', 'LASER');
        $this->_setParam('requiresIssueNumber', FALSE);
        break;

      case 'maestro':
      case 'switch':
      case 'maestro/switch':
      case 'solo':
        $this->_setParam('card_type', 'SWITCH');
        $this->_setParam('requiresIssueNumber', TRUE);
        break;

      default:
        throw new PaymentProcessorException(ts('Credit card type not supported by Realex:') . ' ' . $params['credit_card_type'], 9001);
    }

    // get the card holder name - cater cor customized billing forms
    if (isset($params['cardholder_name'])) {
      $credit_card_name = $params['cardholder_name'];
    }
    else {
      $credit_card_name = $params['first_name'] . " ";
      if (!empty($params['middle_name'])) {
        $credit_card_name .= $params['middle_name'] . " ";
      }
      $credit_card_name .= $params['last_name'];
    }

    $this->_setParam('card_name', $credit_card_name);
    $this->_setParam('card_number', str_replace(' ', '', $params['credit_card_number']));
    $this->_setParam('cvn', $params['cvv2']);
    $this->_setParam('country', $params['country']);
    $this->_setParam('post_code', $params['postal_code']);
    $this->_setParam('order_id', $params['invoiceID']);
    $params['issue_number'] = ($params['issue_number'] ?? '');
    $this->_setParam('issue_number', $params['issue_number']);
    $comment = $params['description'] . ' (page id:' . $params['contributionPageID'] . ')';
    $this->_setParam('comments', $comment);

    // set the currency to the default which can be overrided.
    $this->_setParam('currency', CRM_Core_Config::singleton()->defaultCurrency);

    // Format the expiry date to MMYY
    $expmonth = (string) $params['month'];
    $expmonth = (strlen($expmonth) === 1) ? '0' . $expmonth : $expmonth;
    $expyear = substr((string) $params['year'], 2, 2);
    $this->_setParam('exp_date', $expmonth . $expyear);

    if (isset($params['credit_card_start_date']) && (strlen($params['credit_card_start_date']['M']) !== 0) &&
      (strlen($params['credit_card_start_date']['Y']) !== 0)
    ) {
      $startmonth = (string) $params['credit_card_start_date']['M'];
      $startmonth = (strlen($startmonth) === 1) ? '0' . $startmonth : $startmonth;
      $startyear = substr((string) $params['credit_card_start_date']['Y'], 2, 2);
      $this->_setParam('start_date', $startmonth . $startyear);
    }

    // Create timestamp
    $timestamp = date('YmdHis');
    $this->_setParam('timestamp', $timestamp);
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
   */
  public function _setParam($field, $value) {
    if (!is_scalar($value)) {
      return;
    }
    $this->_params[$field] = $value;
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
      $error[] = ts('Merchant ID is not set for this payment processor');
    }

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('Secret is not set for this payment processor');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    return NULL;
  }

  /**
   * Get the error to display.
   *
   * @param string $errorCode
   * @param string $errorMessage
   *
   * @return string
   */
  protected function getDisplayError($errorCode, $errorMessage): string {
    if ($errorCode === '101' || $errorCode === '102') {
      $display_error = ts('Card declined by bank. Please try with a different card.');
    }
    elseif ($errorCode === '103') {
      $display_error = ts('Card reported lost or stolen. This incident will be reported.');
    }
    elseif ($errorCode === '501') {
      $display_error = ts('It appears that this transaction is a duplicate. Have you already submitted the form once? If so there may have been a connection problem. Check your email for a receipt for this transaction.  If you do not receive a receipt within 2 hours you can try your transaction again.  If you continue to have problems please contact the site administrator.');
    }
    elseif ($errorCode === '509') {
      $display_error = $errorMessage;
    }
    else {
      $display_error = ts('We were unable to process your payment at this time. Please try again later.');
    }
    return $display_error;
  }

}
