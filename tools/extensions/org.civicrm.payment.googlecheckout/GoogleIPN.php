<?php

/**
 * Copyright (C) 2006 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/* This is the response handler code that will be invoked every time
  * a notification or request is sent by the Google Server
  *
  * To allow this code to receive responses, the url for this file
  * must be set on the seller page under Settings->Integration as the
  * "API Callback URL'
  * Order processing commands can be sent automatically by placing these
  * commands appropriately
  *
  * To use this code for merchant-calculated feedback, this url must be
  * set also as the merchant-calculations-url when the cart is posted
  * Depending on your calculations for shipping, taxes, coupons and gift
  * certificates update parts of the code as required
  *
  */



require_once 'CRM/Core/Payment/BaseIPN.php';

define('GOOGLE_DEBUG_PP', 1);
class org_civicrm_payment_googlecheckout_GoogleIPN extends CRM_Core_Payment_BaseIPN {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * mode of operation: live or test
   *
   * @var object
   * @static
   */
  static protected $_mode = NULL;

  static function retrieve($name, $type, $object, $abort = TRUE) {
    $value = CRM_Utils_Array::value($name, $object);
    if ($abort && $value === NULL) {
      CRM_Core_Error::debug_log_message("Could not find an entry for $name");
      echo "Failure: Missing Parameter<p>";
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
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   *
   * @param $paymentProcessor
   *
   * @return \org_civicrm_payment_googlecheckout_GoogleIPN
   */
  function __construct($mode, &$paymentProcessor) {
    parent::__construct();

    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
  }

  /**
   * The function gets called when a new order takes place.
   *
   * @param xml $dataRoot response send by google in xml format
   * @param array $privateData contains the name value pair of <merchant-private-data>
   *
   * @param $component
   * @return void
   */
  function newOrderNotify($dataRoot, $privateData, $component) {
    $ids = $input = $params = array();

    $input['component'] = strtolower($component);

    $ids['contact'] = self::retrieve('contactID', 'Integer', $privateData, TRUE);
    $ids['contribution'] = self::retrieve('contributionID', 'Integer', $privateData, TRUE);

    if ($input['component'] == "event") {
      $ids['event']       = self::retrieve('eventID', 'Integer', $privateData, TRUE);
      $ids['participant'] = self::retrieve('participantID', 'Integer', $privateData, TRUE);
      $ids['membership']  = NULL;
    }
    else {
      $ids['membership'] = self::retrieve('membershipID', 'Integer', $privateData, FALSE);
      $ids['related_contact'] = self::retrieve('relatedContactID', 'Integer', $privateData, FALSE);
      $ids['onbehalf_dupe_alert'] = self::retrieve('onBehalfDupeAlert', 'Integer', $privateData, FALSE);
    }
    $ids['contributionRecur'] = $ids['contributionPage'] = NULL;

    if (!$this->validateData($input, $ids, $objects)) {
      return FALSE;
    }

    // make sure the invoice is valid and matches what we have in the contribution record
    $input['invoice']    = $privateData['invoiceID'];
    $input['newInvoice'] = $dataRoot['google-order-number']['VALUE'];
    $contribution        = &$objects['contribution'];
    if ($contribution->invoice_id != $input['invoice']) {
      CRM_Core_Error::debug_log_message("Invoice values dont match between database and IPN request");
      echo "Failure: Invoice values dont match between database and IPN request<p>";
      return;
    }

    // lets replace invoice-id with google-order-number because thats what is common and unique
    // in subsequent calls or notifications sent by google.
    $contribution->invoice_id = $input['newInvoice'];

    $input['amount'] = $dataRoot['order-total']['VALUE'];

    if ($contribution->total_amount != $input['amount']) {
      CRM_Core_Error::debug_log_message("Amount values dont match between database and IPN request");
      echo "Failure: Amount values dont match between database and IPN request<p>";
      return;
    }

    if (!$this->getInput($input, $ids)) {
      return FALSE;
    }

    require_once 'CRM/Core/Transaction.php';
    $transaction = new CRM_Core_Transaction();

    // check if contribution is already completed, if so we ignore this ipn
    if ($contribution->contribution_status_id == 1) {
      CRM_Core_Error::debug_log_message("returning since contribution has already been handled");
      echo "Success: Contribution has already been handled<p>";
    }
    else {
      /* Since trxn_id hasn't got any use here,
             * lets make use of it by passing the eventID/membershipTypeID to next level.
             * And change trxn_id to google-order-number before finishing db update */


      if ($ids['event']) {
        $contribution->trxn_id = $ids['event'] . CRM_Core_DAO::VALUE_SEPARATOR . $ids['participant'];
      }
      else {
        $contribution->trxn_id = $ids['membership'] . CRM_Core_DAO::VALUE_SEPARATOR . $ids['related_contact'] . CRM_Core_DAO::VALUE_SEPARATOR . $ids['onbehalf_dupe_alert'];
      }
    }

    // CRM_Core_Error::debug_var( 'c', $contribution );
    $contribution->save();
    $transaction->commit();
    return TRUE;
  }

  /**
   * The function gets called when the state(CHARGED, CANCELLED..) changes for an order
   *
   * @param string $status status of the transaction send by google
   * @param $dataRoot
   * @param $component
   * @internal param array $privateData contains the name value pair of <merchant-private-data>
   *
   * @return void
   */
  function orderStateChange($status, $dataRoot, $component) {
    $input = $objects = $ids = array();

    $input['component'] = strtolower($component);

    // CRM_Core_Error::debug_var( "$status, $component", $dataRoot );
    $orderNo = $dataRoot['google-order-number']['VALUE'];

    require_once 'CRM/Contribute/DAO/Contribution.php';
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->invoice_id = $orderNo;
    if (!$contribution->find(TRUE)) {
      CRM_Core_Error::debug_log_message("Could not find contribution record with invoice id: $orderNo");
      echo "Failure: Could not find contribution record with invoice id: $orderNo <p>";
      exit();
    }

    // Google sends the charged notification twice.
    // So to make sure, code is not executed again.
    if ($contribution->contribution_status_id == 1) {
      CRM_Core_Error::debug_log_message("Contribution already handled (ContributionID = $contribution).");
      exit();
    }

    $objects['contribution'] = &$contribution;
    $ids['contribution'] = $contribution->id;
    $ids['contact'] = $contribution->contact_id;

    $ids['event'] = $ids['participant'] = $ids['membership'] = NULL;
    $ids['contributionRecur'] = $ids['contributionPage'] = NULL;

    if ($input['component'] == "event") {
      list($ids['event'], $ids['participant']) = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        $contribution->trxn_id
      );
    }
    else {
      list($ids['membership'], $ids['related_contact'], $ids['onbehalf_dupe_alert']) = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        $contribution->trxn_id
      );

      foreach (array('membership', 'related_contact', 'onbehalf_dupe_alert') as $fld) {
        if (!is_numeric($ids[$fld])) {
          unset($ids[$fld]);
        }
      }
    }

    $this->loadObjects($input, $ids, $objects);

    require_once 'CRM/Core/Transaction.php';
    $transaction = new CRM_Core_Transaction();

    // CRM_Core_Error::debug_var( 'c', $contribution );
    if ($status == 'PAYMENT_DECLINED' ||
      $status == 'CANCELLED_BY_GOOGLE' ||
      $status == 'CANCELLED'
    ) {
      return $this->failed($objects, $transaction);
    }

    $input['amount']     = $contribution->total_amount;
    $input['fee_amount'] = NULL;
    $input['net_amount'] = NULL;
    $input['trxn_id']    = $orderNo;
    $input['is_test']    = $contribution->is_test;

    $this->completeTransaction($input, $ids, $objects, $transaction);
  }

  /**
   * singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   *
   * @param $component
   * @param $paymentProcessor
   * @return object
   * @static
   */
  static
  function &singleton($mode, $component, &$paymentProcessor) {
    if (self::$_singleton === NULL) {
      self::$_singleton = new org_civicrm_payment_googlecheckout_GoogleIPN($mode, $paymentProcessor);
    }
    return self::$_singleton;
  }

  /**
   * The function retrieves the amount the contribution is for, based on the order-no google sends
   *
   * @param int $orderNo <order-total> send by google
   *
   * @return amount
   * @access public
   */
  function getAmount($orderNo) {
    require_once 'CRM/Contribute/DAO/Contribution.php';
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->invoice_id = $orderNo;
    if (!$contribution->find(TRUE)) {
      CRM_Core_Error::debug_log_message("Could not find contribution record with invoice id: $orderNo");
      echo "Failure: Could not find contribution record with invoice id: $orderNo <p>";
      exit();
    }
    return $contribution->total_amount;
  }

  /**
   * The function returns the component(Event/Contribute..), given the google-order-no and merchant-private-data
   *
   * @param xml     $xml_response   response send by google in xml format
   * @param array   $privateData    contains the name value pair of <merchant-private-data>
   * @param int     $orderNo        <order-total> send by google
   * @param string  $root           root of xml-response
   *
   * @return array context of this call (test, module, payment processor id)
   * @static
   */
  static
  function getContext($xml_response, $privateData, $orderNo, $root) {
    require_once 'CRM/Contribute/DAO/Contribution.php';

    $isTest = NULL;
    $module = NULL;
    if ($root == 'new-order-notification') {
      $contributionID   = $privateData['contributionID'];
      $contribution     = new CRM_Contribute_DAO_Contribution();
      $contribution->id = $contributionID;
      if (!$contribution->find(TRUE)) {
        CRM_Core_Error::debug_log_message("Could not find contribution record: $contributionID");
        echo "Failure: Could not find contribution record for $contributionID<p>";
        exit();
      }
      if (stristr($contribution->source, ts('Online Contribution'))) {
        $module = 'Contribute';
      }
      elseif (stristr($contribution->source, ts('Online Event Registration'))) {
        $module = 'Event';
      }
      $isTest = $contribution->is_test;
    }
    else {
      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->invoice_id = $orderNo;
      if (!$contribution->find(TRUE)) {
        CRM_Core_Error::debug_log_message("Could not find contribution record with invoice id: $orderNo");
        echo "Failure: Could not find contribution record with invoice id: $orderNo <p>";
        exit();
      }
      if (stristr($contribution->source, ts('Online Contribution'))) {
        $module = 'Contribute';
      }
      elseif (stristr($contribution->source, ts('Online Event Registration'))) {
        $module = 'Event';
      }
      $isTest = $contribution->is_test;
    }

    if ($contribution->contribution_status_id == 1) {
      //contribution already handled.
      exit();
    }

    if ($module == 'Contribute') {
      if (!$contribution->contribution_page_id) {
        CRM_Core_Error::debug_log_message("Could not find contribution page for contribution record: $contributionID");
        echo "Failure: Could not find contribution page for contribution record: $contributionID<p>";
        exit();
      }

      // get the payment processor id from contribution page
      $paymentProcessorID = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage',
        $contribution->contribution_page_id,
        'payment_processor_id'
      );
    }
    else {
      if ($root == 'new-order-notification') {
        $eventID = $privateData['eventID'];
      }
      else {
        list($eventID, $participantID) = explode(CRM_Core_DAO::VALUE_SEPARATOR,
          $contribution->trxn_id
        );
      }
      if (!$eventID) {
        CRM_Core_Error::debug_log_message("Could not find event ID");
        echo "Failure: Could not find eventID<p>";
        exit();
      }

      // we are in event mode
      // make sure event exists and is valid
      require_once 'CRM/Event/DAO/Event.php';
      $event = new CRM_Event_DAO_Event();
      $event->id = $eventID;
      if (!$event->find(TRUE)) {
        CRM_Core_Error::debug_log_message("Could not find event: $eventID");
        echo "Failure: Could not find event: $eventID<p>";
        exit();
      }

      // get the payment processor id from contribution page
      $paymentProcessorID = $event->payment_processor_id;
    }

    if (!$paymentProcessorID) {
      CRM_Core_Error::debug_log_message("Could not find payment processor for contribution record: $contributionID");
      echo "Failure: Could not find payment processor for contribution record: $contributionID<p>";
      exit();
    }

    return array($isTest, $module, $paymentProcessorID);
  }

  /**
   * This method is handles the response that will be invoked (from extern/googleNotify) every time
   * a notification or request is sent by the Google Server.
   *
   */
  static
  function main($xml_response) {
    require_once ('Google/library/googleresponse.php');
    require_once ('Google/library/googlemerchantcalculations.php');
    require_once ('Google/library/googleresult.php');
    require_once ('Google/library/xml-processing/xmlparser.php');

    $config = CRM_Core_Config::singleton();

    // Retrieve the XML sent in the HTTP POST request to the ResponseHandler
    if (get_magic_quotes_gpc()) {
      $xml_response = stripslashes($xml_response);
    }

    require_once 'CRM/Utils/System.php';
    $headers = CRM_Utils_System::getAllHeaders();

    if (GOOGLE_DEBUG_PP) {
      CRM_Core_Error::debug_var('RESPONSE', $xml_response, TRUE, TRUE, 'Google');
    }

    // Retrieve the root and data from the xml response
    $xmlParser = new XmlParser($xml_response);
    $root      = $xmlParser->GetRoot();
    $data      = $xmlParser->GetData();

    $orderNo = $data[$root]['google-order-number']['VALUE'];

    // lets retrieve the private-data
    $privateData = $data[$root]['shopping-cart']['merchant-private-data']['VALUE'];
    $privateData = $privateData ? self::stringToArray($privateData) : '';

    list($mode, $module, $paymentProcessorID) = self::getContext($xml_response, $privateData, $orderNo, $root);
    $mode = $mode ? 'test' : 'live';

    require_once 'CRM/Financial/BAO/PaymentProcessor.php';
    $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($paymentProcessorID,
      $mode
    );

    $ipn = &self::singleton($mode, $module, $paymentProcessor);

    // Create new response object
    $merchant_id  = $paymentProcessor['user_name'];
    $merchant_key = $paymentProcessor['password'];
    $server_type  = ($mode == 'test') ? "sandbox" : '';

    $response = new GoogleResponse($merchant_id, $merchant_key,
      $xml_response, $server_type
    );
    if (GOOGLE_DEBUG_PP) {
      CRM_Core_Error::debug_var('RESPONSE-ROOT', $response->root, TRUE, TRUE, 'Google');
    }

    //Check status and take appropriate action
    $status = $response->HttpAuthentication($headers);

    switch ($root) {
      case "request-received":
      case "error":
      case "diagnosis":
      case "checkout-redirect":
      case "merchant-calculation-callback":
        break;

      case "new-order-notification": {
          $response->SendAck();
          $ipn->newOrderNotify($data[$root], $privateData, $module);
          break;
        }
      case "order-state-change-notification": {
          $response->SendAck();
          $new_financial_state = $data[$root]['new-financial-order-state']['VALUE'];
          $new_fulfillment_order = $data[$root]['new-fulfillment-order-state']['VALUE'];

          switch ($new_financial_state) {
            case 'CHARGEABLE':
              $amount = $ipn->getAmount($orderNo);
              if ($amount) {
                  $response->SendChargeOrder($data[$root]['google-order-number']['VALUE'],
                    $amount, $message_log
                  );
                  $response->SendProcessOrder($data[$root]['google-order-number']['VALUE'],
                    $message_log
                  );
                }
                break;

              case 'CHARGED':
              case 'PAYMENT_DECLINED':
              case 'CANCELLED':
                $ipn->orderStateChange($new_financial_state, $data[$root], $module);
                break;

              case 'REVIEWING':
              case 'CHARGING':
              case 'CANCELLED_BY_GOOGLE':
                break;

              default:
                break;
            }
          }
        case "charge-amount-notification":
        case "chargeback-amount-notification":
        case "refund-amount-notification":
        case "risk-information-notification":
          $response->SendAck();
          break;

        default:
          break;
      }
    }

    function getInput(&$input, &$ids) {
      if (!$this->getBillingID($ids)) {
        return FALSE;
      }

      $billingID = $ids['billing'];
      $lookup = array("first_name" => 'contact-name',
        // "last-name" not available with google (every thing in contact-name)
        "last_name" => 'last_name',
        "street_address-{$billingID}" => 'address1',
        "city-{$billingID}" => 'city',
        "state-{$billingID}" => 'region',
        "postal_code-{$billingID}" => 'postal-code',
        "country-{$billingID}" => 'country-code',
      );

      foreach ($lookup as $name => $googleName) {
        $value = $dataRoot['buyer-billing-address'][$googleName]['VALUE'];
        $input[$name] = $value ? $value : NULL;
      }
      return TRUE;
    }

    /**
     * Converts the comma separated name-value pairs in <merchant-private-data>
     * to an array of name-value pairs.
     */
    static
    function stringToArray($str) {
      $vars = $labels = array();
      $labels = explode(',', $str);
      foreach ($labels as $label) {
        $terms = explode('=', $label);
        $vars[$terms[0]] = $terms[1];
      }
      return $vars;
    }
  }

