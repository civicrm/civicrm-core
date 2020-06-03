<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
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


/*
 * PxPay Functionality Copyright (C) 2008 Lucas Baker, Logistic Information Systems Limited (Logis)
 * PxAccess Functionality Copyright (C) 2008 Eileen McNaughton
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * Grateful acknowledgements go to Donald Lobo for invaluable assistance
 * in creating this payment processor module
 */

/**
 * Class CRM_Core_Payment_PaymentExpressIPN
 */
class CRM_Core_Payment_PaymentExpressIPN extends CRM_Core_Payment_BaseIPN {

  /**
   * Mode of operation: live or test
   *
   * @var object
   */
  protected $_mode = NULL;

  /**
   * @param string $name
   * @param $type
   * @param $object
   * @param bool $abort
   *
   * @return mixed
   */
  public static function retrieve($name, $type, $object, $abort = TRUE) {
    $value = $object[$name] ?? NULL;
    if ($abort && $value === NULL) {
      CRM_Core_Error::debug_log_message("Could not find an entry for $name");
      echo "Failure: Missing Parameter - " . $name . "<p>";
      exit();
    }

    if ($value) {
      if (!CRM_Utils_Type::validate($value, $type)) {
        CRM_Core_Error::debug_log_message("Could not find a valid entry for $name");
        echo "Failure: Invalid Parameter<p>";
        exit();
      }
    }

    return $value;
  }

  /**
   * Constructor.
   *
   * @param string $mode
   *   The mode of operation: live or test.
   *
   * @param $paymentProcessor
   *
   * @return \CRM_Core_Payment_PaymentExpressIPN
   */
  public function __construct($mode, &$paymentProcessor) {
    parent::__construct();

    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
  }

  /**
   * The function gets called when a new order takes place.
   *
   * @param $success
   * @param array $privateData
   *   Contains the name value pair of <merchant-private-data>.
   *
   * @param $component
   * @param $amount
   * @param $transactionReference
   *
   * @return bool
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function newOrderNotify($success, $privateData, $component, $amount, $transactionReference) {
    $ids = $input = $params = [];

    $input['component'] = strtolower($component);

    $ids['contact'] = self::retrieve('contactID', 'Integer', $privateData, TRUE);
    $ids['contribution'] = self::retrieve('contributionID', 'Integer', $privateData, TRUE);

    if ($input['component'] == "event") {
      $ids['event'] = self::retrieve('eventID', 'Integer', $privateData, TRUE);
      $ids['participant'] = self::retrieve('participantID', 'Integer', $privateData, TRUE);
      $ids['membership'] = NULL;
    }
    else {
      $ids['membership'] = self::retrieve('membershipID', 'Integer', $privateData, FALSE);
    }
    $ids['contributionRecur'] = $ids['contributionPage'] = NULL;

    $paymentProcessorID = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessorType',
      'PayPal_Express', 'id', 'name'
    );

    if (!$this->validateData($input, $ids, $objects, TRUE, $paymentProcessorID)) {
      return FALSE;
    }

    // make sure the invoice is valid and matches what we have in the contribution record
    $input['invoice'] = $privateData['invoiceID'];
    $input['newInvoice'] = $transactionReference;
    $contribution = &$objects['contribution'];
    $input['trxn_id'] = $transactionReference;

    if ($contribution->invoice_id != $input['invoice']) {
      CRM_Core_Error::debug_log_message("Invoice values dont match between database and IPN request");
      echo "Failure: Invoice values dont match between database and IPN request<p>";
      return FALSE;
    }

    // lets replace invoice-id with Payment Processor -number because thats what is common and unique
    // in subsequent calls or notifications sent by google.
    $contribution->invoice_id = $input['newInvoice'];

    $input['amount'] = $amount;

    if ($contribution->total_amount != $input['amount']) {
      CRM_Core_Error::debug_log_message("Amount values dont match between database and IPN request");
      echo "Failure: Amount values dont match between database and IPN request. " . $contribution->total_amount . "/" . $input['amount'] . "<p>";
      return FALSE;
    }

    // check if contribution is already completed, if so we ignore this ipn

    if ($contribution->contribution_status_id == 1) {
      CRM_Core_Error::debug_log_message("returning since contribution has already been handled");
      echo "Success: Contribution has already been handled<p>";
      return TRUE;
    }
    else {
      /* Since trxn_id hasn't got any use here,
       * lets make use of it by passing the eventID/membershipTypeID to next level.
       * And change trxn_id to the payment processor reference before finishing db update */

      if ($ids['event']) {
        $contribution->trxn_id = $ids['event'] . CRM_Core_DAO::VALUE_SEPARATOR . $ids['participant'];
      }
      else {
        $contribution->trxn_id = $ids['membership'];
      }
    }
    CRM_Contribute_BAO_Contribution::completeOrder($input, $ids, $objects);
    return TRUE;
  }

  /**
   *
   * /**
   * The function returns the component(Event/Contribute..)and whether it is Test or not
   *
   * @param array $privateData
   *   Contains the name-value pairs of transaction related data.
   * @param int $orderNo
   *   <order-total> send by google.
   *
   * @return array
   *   context of this call (test, component, payment processor id)
   */
  public static function getContext($privateData, $orderNo) {

    $component = NULL;
    $isTest = NULL;

    $contributionID = $privateData['contributionID'];
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $contributionID;

    if (!$contribution->find(TRUE)) {
      CRM_Core_Error::debug_log_message("Could not find contribution record: $contributionID");
      echo "Failure: Could not find contribution record for $contributionID<p>";
      exit();
    }

    if (stristr($contribution->source, 'Online Contribution')) {
      $component = 'contribute';
    }
    elseif (stristr($contribution->source, 'Online Event Registration')) {
      $component = 'event';
    }
    $isTest = $contribution->is_test;

    $duplicateTransaction = 0;
    if ($contribution->contribution_status_id == 1) {
      //contribution already handled. (some processors do two notifications so this could be valid)
      $duplicateTransaction = 1;
    }

    if ($component == 'contribute') {
      if (!$contribution->contribution_page_id) {
        CRM_Core_Error::debug_log_message("Could not find contribution page for contribution record: $contributionID");
        echo "Failure: Could not find contribution page for contribution record: $contributionID<p>";
        exit();
      }
    }
    else {

      $eventID = $privateData['eventID'];

      if (!$eventID) {
        CRM_Core_Error::debug_log_message("Could not find event ID");
        echo "Failure: Could not find eventID<p>";
        exit();
      }

      // we are in event mode
      // make sure event exists and is valid
      $event = new CRM_Event_DAO_Event();
      $event->id = $eventID;
      if (!$event->find(TRUE)) {
        CRM_Core_Error::debug_log_message("Could not find event: $eventID");
        echo "Failure: Could not find event: $eventID<p>";
        exit();
      }
    }

    return [$isTest, $component, $duplicateTransaction];
  }

  /**
   * Main notification processing method.
   *
   * hex string from paymentexpress is passed to this function as hex string. Code based on googleIPN
   * mac_key is only passed if the processor is pxaccess as it is used for decryption
   * $dps_method is either pxaccess or pxpay
   *
   * @param string $dps_method
   * @param array $rawPostData
   * @param string $dps_url
   * @param string $dps_user
   * @param string $dps_key
   * @param string $mac_key
   *
   * @throws \Exception
   */
  public static function main($dps_method, $rawPostData, $dps_url, $dps_user, $dps_key, $mac_key) {

    $config = CRM_Core_Config::singleton();
    define('RESPONSE_HANDLER_LOG_FILE', $config->uploadDir . 'CiviCRM.PaymentExpress.log');

    //Setup the log file
    if (!$message_log = fopen(RESPONSE_HANDLER_LOG_FILE, "a")) {
      error_func("Cannot open " . RESPONSE_HANDLER_LOG_FILE . " file.\n", 0);
      exit(1);
    }

    if ($dps_method == "pxpay") {
      $processResponse = CRM_Core_Payment_PaymentExpressUtils::_valueXml([
        'PxPayUserId' => $dps_user,
        'PxPayKey' => $dps_key,
        'Response' => $_GET['result'],
      ]);
      $processResponse = CRM_Core_Payment_PaymentExpressUtils::_valueXml('ProcessResponse', $processResponse);

      fwrite($message_log, sprintf("\n\r%s:- %s\n", date("D M j G:i:s T Y"),
        $processResponse
      ));

      // Send the XML-formatted validation request to DPS so that we can receive a decrypted XML response which contains the transaction results
      $curl = CRM_Core_Payment_PaymentExpressUtils::_initCURL($processResponse, $dps_url);

      fwrite($message_log, sprintf("\n\r%s:- %s\n", date("D M j G:i:s T Y"),
        $curl
      ));
      $success = FALSE;
      if ($response = curl_exec($curl)) {
        $info = curl_getinfo($curl);
        if ($info['http_code'] < 200 || $info['http_code'] > 299) {
          $log_message = "DPS error: HTTP {$info['http_code']} retrieving {$info['url']}.";
          throw new CRM_Core_Exception($log_message);
        }
        else {
          fwrite($message_log, sprintf("\n\r%s:- %s\n", date("D M j G:i:s T Y"), $response));
          curl_close($curl);

          // Assign the returned XML values to variables
          $valid = CRM_Core_Payment_PaymentExpressUtils::_xmlAttribute($response, 'valid');
          // CRM_Core_Payment_PaymentExpressUtils::_xmlAttribute() returns NULL if preg fails.
          if (is_null($valid)) {
            throw new CRM_Core_Exception(ts("DPS error: Unable to parse XML response from DPS.", [1 => $valid]));
          }
          $success = CRM_Core_Payment_PaymentExpressUtils::_xmlElement($response, 'Success');
          $txnId = CRM_Core_Payment_PaymentExpressUtils::_xmlElement($response, 'TxnId');
          $responseText = CRM_Core_Payment_PaymentExpressUtils::_xmlElement($response, 'ResponseText');
          $authCode = CRM_Core_Payment_PaymentExpressUtils::_xmlElement($response, 'AuthCode');
          $DPStxnRef = CRM_Core_Payment_PaymentExpressUtils::_xmlElement($response, 'DpsTxnRef');
          $qfKey = CRM_Core_Payment_PaymentExpressUtils::_xmlElement($response, "TxnData1");
          $privateData = CRM_Core_Payment_PaymentExpressUtils::_xmlElement($response, "TxnData2");
          list($component, $paymentProcessorID,) = explode(',', CRM_Core_Payment_PaymentExpressUtils::_xmlElement($response, "TxnData3"));
          $amount = CRM_Core_Payment_PaymentExpressUtils::_xmlElement($response, "AmountSettlement");
          $merchantReference = CRM_Core_Payment_PaymentExpressUtils::_xmlElement($response, "MerchantReference");
        }
      }
      else {
        // calling DPS failed
        throw new CRM_Core_Exception(ts('Unable to establish connection to the payment gateway to verify transaction response.'));
        exit;
      }
    }
    elseif ($dps_method == "pxaccess") {

      require_once 'PaymentExpress/pxaccess.inc.php';
      global $pxaccess;
      $pxaccess = new PxAccess($dps_url, $dps_user, $dps_key, $mac_key);
      // GetResponse method in PxAccess object returns PxPayResponse object
      // which encapsulates all the response data
      $rsp = $pxaccess->getResponse($rawPostData);

      $qfKey = $rsp->getTxnData1();
      $privateData = $rsp->getTxnData2();
      list($component, $paymentProcessorID) = explode(',', $rsp->getTxnData3());
      $success = $rsp->getSuccess();
      $authCode = $rsp->getAuthCode();
      $DPStxnRef = $rsp->getDpsTxnRef();
      $amount = $rsp->getAmountSettlement();
      $MerchantReference = $rsp->getMerchantReference();
    }

    $privateData = $privateData ? self::stringToArray($privateData) : '';

    // Record the current count in array, before we start adding things (for later checks)
    $countPrivateData = count($privateData);

    // Private Data consists of : a=contactID, b=contributionID,c=contributionTypeID,d=invoiceID,e=membershipID,f=participantID,g=eventID
    $privateData['contactID'] = $privateData['a'];
    $privateData['contributionID'] = $privateData['b'];
    $privateData['contributionTypeID'] = $privateData['c'];
    $privateData['invoiceID'] = $privateData['d'];

    if ($component == "event") {
      $privateData['participantID'] = $privateData['f'];
      $privateData['eventID'] = $privateData['g'];
    }
    elseif ($component == "contribute") {

      if ($countPrivateData == 5) {
        $privateData["membershipID"] = $privateData['e'];
      }
    }

    $transactionReference = $authCode . "-" . $DPStxnRef;

    list($mode, $component, $duplicateTransaction) = self::getContext($privateData, $transactionReference);
    $mode = $mode ? 'test' : 'live';

    $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($paymentProcessorID,
      $mode
    );

    $ipn = self::singleton($mode, $component, $paymentProcessor);

    //Check status and take appropriate action

    if ($success == 1) {
      if ($duplicateTransaction == 0) {
        $ipn->newOrderNotify($success, $privateData, $component, $amount, $transactionReference);
      }

      if ($component == "event") {
        $finalURL = CRM_Utils_System::url('civicrm/event/register',
          "_qf_ThankYou_display=1&qfKey=$qfKey",
          FALSE, NULL, FALSE
        );
      }
      elseif ($component == "contribute") {
        $finalURL = CRM_Utils_System::url('civicrm/contribute/transact',
          "_qf_ThankYou_display=1&qfKey=$qfKey",
          FALSE, NULL, FALSE
        );
      }

      CRM_Utils_System::redirect($finalURL);
    }
    else {

      if ($component == "event") {
        $finalURL = CRM_Utils_System::url('civicrm/event/confirm',
          "reset=1&cc=fail&participantId=$privateData[participantID]",
          FALSE, NULL, FALSE
        );
      }
      elseif ($component == "contribute") {
        $finalURL = CRM_Utils_System::url('civicrm/contribute/transact',
          "_qf_Main_display=1&cancel=1&qfKey=$qfKey",
          FALSE, NULL, FALSE
        );
      }

      CRM_Utils_System::redirect($finalURL);
    }
  }

  /**
   * Converts the comma separated name-value pairs in <TxnData2> to an array of values.
   *
   * @param string $str
   *
   * @return array
   */
  public static function stringToArray($str) {
    $vars = $labels = [];
    $labels = explode(',', $str);
    foreach ($labels as $label) {
      $terms = explode('=', $label);
      $vars[$terms[0]] = $terms[1];
    }
    return $vars;
  }

}
