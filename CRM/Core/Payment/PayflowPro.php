<?php
/*
   +----------------------------------------------------------------------------+
   | PayflowPro Core Payment Module for CiviCRM version 4.7                     |
   +----------------------------------------------------------------------------+
   | Licensed to CiviCRM under the Academic Free License version 3.0            |
   |                                                                            |
   | Written & Contributed by Eileen McNaughton - 2009                          |
   +---------------------------------------------------------------------------+
  */

/**
 * Class CRM_Core_Payment_PayflowPro.
 */
class CRM_Core_Payment_PayflowPro extends CRM_Core_Payment {
  // (not used, implicit in the API, might need to convert?)
  const
    CHARSET = 'UFT-8';

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   */
  static private $_singleton = NULL;

  /**
   * Constructor
   *
   * @param string $mode
   *   The mode of operation: live or test.
   * @param $paymentProcessor
   */
  public function __construct($mode, &$paymentProcessor) {
    // live or test
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('Payflow Pro');
  }

  /*
   * This function  sends request and receives response from
   * the processor. It is the main function for processing on-server
   * credit card transactions
   */
  /**
   * This function collects all the information from a web/api form and invokes
   * the relevant payment processor specific functions to perform the transaction
   *
   * @param array $params
   *   Assoc array of input parameters for this transaction.
   *
   * @return array
   *   the result in an nice formatted array (or an error object)
   * @abstract
   */
  public function doDirectPayment(&$params) {
    if (!defined('CURLOPT_SSLCERT')) {
      CRM_Core_Error::fatal(ts('PayFlowPro requires curl with SSL support'));
    }

    /*
     * define variables for connecting with the gateway
     */

    // Are you using the Payflow Fraud Protection Service?
    // Default is YES, change to NO or blank if not.
    //This has not been investigated as part of writing this payment processor
    $fraud = 'NO';
    //if you have not set up a separate user account the vendor name is used as the username
    if (!$this->_paymentProcessor['subject']) {
      $user = $this->_paymentProcessor['user_name'];
    }
    else {
      $user = $this->_paymentProcessor['subject'];
    }

    // ideally this id would be passed through into this class as
    // part of the paymentProcessor
    //object with the other variables. It seems inefficient to re-query to get it.
    //$params['processor_id'] = CRM_Core_DAO::getFieldValue(
    // 'CRM_Contribute_DAO_ContributionP
    //age',$params['contributionPageID'],  'payment_processor_id' );

    /*
     *Create the array of variables to be sent to the processor from the $params array
     * passed into this function
     *
     */

    $payflow_query_array = array(
      'USER' => $user,
      'VENDOR' => $this->_paymentProcessor['user_name'],
      'PARTNER' => $this->_paymentProcessor['signature'],
      'PWD' => $this->_paymentProcessor['password'],
      // C - Direct Payment using credit card
      'TENDER' => 'C',
      // A - Authorization, S - Sale
      'TRXTYPE' => 'S',
      'ACCT' => urlencode($params['credit_card_number']),
      'CVV2' => $params['cvv2'],
      'EXPDATE' => urlencode(sprintf('%02d', (int) $params['month']) . substr($params['year'], 2, 2)),
      'ACCTTYPE' => urlencode($params['credit_card_type']),
      'AMT' => urlencode($params['amount']),
      'CURRENCY' => urlencode($params['currency']),
      'FIRSTNAME' => $params['billing_first_name'],
      //credit card name
      'LASTNAME' => $params['billing_last_name'],
      //credit card name
      'STREET' => $params['street_address'],
      'CITY' => urlencode($params['city']),
      'STATE' => urlencode($params['state_province']),
      'ZIP' => urlencode($params['postal_code']),
      'COUNTRY' => urlencode($params['country']),
      'EMAIL' => $params['email'],
      'CUSTIP' => urlencode($params['ip_address']),
      'COMMENT1' => urlencode($params['contributionType_accounting_code']),
      'COMMENT2' => $mode,
      'INVNUM' => urlencode($params['invoiceID']),
      'ORDERDESC' => urlencode($params['description']),
      'VERBOSITY' => 'MEDIUM',
      'BILLTOCOUNTRY' => urlencode($params['country']),
    );

    if ($params['installments'] == 1) {
      $params['is_recur'] == FALSE;
    }

    if ($params['is_recur'] == TRUE) {

      $payflow_query_array['TRXTYPE'] = 'R';
      $payflow_query_array['OPTIONALTRX'] = 'S';
      $payflow_query_array['OPTIONALTRXAMT'] = $params['amount'];
      //Amount of the initial Transaction. Required
      $payflow_query_array['ACTION'] = 'A';
      //A for add recurring (M-modify,C-cancel,R-reactivate,I-inquiry,P-payment
      $payflow_query_array['PROFILENAME'] = urlencode('RegularContribution');
      //A for add recurring (M-modify,C-cancel,R-reactivate,I-inquiry,P-payment
      if ($params['installments'] > 0) {
        $payflow_query_array['TERM'] = $params['installments'] - 1;
        //ie. in addition to the one happening with this transaction
      }
      // $payflow_query_array['COMPANYNAME']
      // $payflow_query_array['DESC']  =  not set yet  Optional
      // description of the goods or
      //services being purchased.
      //This parameter applies only for ACH_CCD accounts.
      // The
      // $payflow_query_array['MAXFAILPAYMENTS']   = 0;
      // number of payment periods (as s
      //pecified by PAYPERIOD) for which the transaction is allowed
      //to fail before PayPal cancels a profile.  the default
      // value of 0 (zero) specifies no
      //limit. Retry
      //attempts occur until the term is complete.
      // $payflow_query_array['RETRYNUMDAYS'] = (not set as can't assume business rule

      switch ($params['frequency_unit']) {
        case '1 week':
          $params['next_sched_contribution_date'] = mktime(0, 0, 0, date("m"), date("d") + 7,
            date("Y")
          );
          $params['end_date'] = mktime(0, 0, 0, date("m"), date("d") + (7 * $payflow_query_array['TERM']),
            date("Y")
          );
          $payflow_query_array['START'] = date('mdY', $params['next_sched_contribution_date']);
          $payflow_query_array['PAYPERIOD'] = "WEEK";
          $params['frequency_unit'] = "week";
          $params['frequency_interval'] = 1;
          break;

        case '2 weeks':
          $params['next_sched_contribution_date'] = mktime(0, 0, 0, date("m"), date("d") + 14, date("Y"));
          $params['end_date'] = mktime(0, 0, 0, date("m"), date("d") + (14 * $payflow_query_array['TERM']), date("Y ")
          );
          $payflow_query_array['START'] = date('mdY', $params['next_sched_contribution_date']);
          $payflow_query_array['PAYPERIOD'] = "BIWK";
          $params['frequency_unit'] = "week";
          $params['frequency_interval'] = 2;
          break;

        case '4 weeks':
          $params['next_sched_contribution_date'] = mktime(0, 0, 0, date("m"), date("d") + 28, date("Y")
          );
          $params['end_date'] = mktime(0, 0, 0, date("m"), date("d") + (28 * $payflow_query_array['TERM']), date("Y")
          );
          $payflow_query_array['START'] = date('mdY', $params['next_sched_contribution_date']);
          $payflow_query_array['PAYPERIOD'] = "FRWK";
          $params['frequency_unit'] = "week";
          $params['frequency_interval'] = 4;
          break;

        case '1 month':
          $params['next_sched_contribution_date'] = mktime(0, 0, 0, date("m") + 1,
            date("d"), date("Y")
          );
          $params['end_date'] = mktime(0, 0, 0, date("m") +
            (1 * $payflow_query_array['TERM']),
            date("d"), date("Y")
          );
          $payflow_query_array['START'] = date('mdY', $params['next_sched_contribution_date']);
          $payflow_query_array['PAYPERIOD'] = "MONT";
          $params['frequency_unit'] = "month";
          $params['frequency_interval'] = 1;
          break;

        case '3 months':
          $params['next_sched_contribution_date'] = mktime(0, 0, 0, date("m") + 3, date("d"), date("Y")
          );
          $params['end_date'] = mktime(0, 0, 0, date("m") +
            (3 * $payflow_query_array['TERM']),
            date("d"), date("Y")
          );
          $payflow_query_array['START'] = date('mdY', $params['next_sched_contribution_date']);
          $payflow_query_array['PAYPERIOD'] = "QTER";
          $params['frequency_unit'] = "month";
          $params['frequency_interval'] = 3;
          break;

        case '6 months':
          $params['next_sched_contribution_date'] = mktime(0, 0, 0, date("m") + 6, date("d"),
            date("Y")
          );
          $params['end_date'] = mktime(0, 0, 0, date("m") +
            (6 * $payflow_query_array['TERM']),
            date("d"), date("Y")
          );
          $payflow_query_array['START'] = date('mdY', $params['next_sched_contribution_date']
          );
          $payflow_query_array['PAYPERIOD'] = "SMYR";
          $params['frequency_unit'] = "month";
          $params['frequency_interval'] = 6;
          break;

        case '1 year':
          $params['next_sched_contribution_date'] = mktime(0, 0, 0, date("m"), date("d"),
            date("Y") + 1
          );
          $params['end_date'] = mktime(0, 0, 0, date("m"), date("d"),
            date("Y") +
            (1 * $payflow_query_array['TEM'])
          );
          $payflow_query_array['START'] = date('mdY', $params['next_sched_contribution_date']);
          $payflow_query_array['PAYPERIOD'] = "YEAR";
          $params['frequency_unit'] = "year";
          $params['frequency_interval'] = 1;
          break;
      }
    }

    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $payflow_query_array);
    $payflow_query = $this->convert_to_nvp($payflow_query_array);

    /*
     * Check to see if we have a duplicate before we send
     */
    if ($this->checkDupe($params['invoiceID'], CRM_Utils_Array::value('contributionID', $params))) {
      return self::errorExit(9003, 'It appears that this transaction is a duplicate.  Have you already submitted the form once?  If so there may have been a connection problem.  Check your email for a receipt.  If you do not receive a receipt within 2 hours you can try your transaction again.  If you continue to have problems please contact the site administrator.');
    }

    // ie. url at payment processor to submit to.
    $submiturl = $this->_paymentProcessor['url_site'];

    $responseData = self::submit_transaction($submiturl, $payflow_query);

    /*
     * Payment successfully sent to gateway - process the response now
     */
    $result = strstr($responseData, "RESULT");
    $nvpArray = array();
    while (strlen($result)) {
      // name
      $keypos = strpos($result, '=');
      $keyval = substr($result, 0, $keypos);
      // value
      $valuepos = strpos($result, '&') ? strpos($result, '&') : strlen($result);
      $valval = substr($result, $keypos + 1, $valuepos - $keypos - 1);
      // decoding the respose
      $nvpArray[$keyval] = $valval;
      $result = substr($result, $valuepos + 1, strlen($result));
    }
    // get the result code to validate.
    $result_code = $nvpArray['RESULT'];
    /*debug
    echo "<p>Params array</p><br>";
    print_r($params);
    echo "<p></p><br>";
    echo "<p>Values to Payment Processor</p><br>";
    print_r($payflow_query_array);
    echo "<p></p><br>";
    echo "<p>Results from Payment Processor</p><br>";
    print_r($nvpArray);
    echo "<p></p><br>";
     */

    switch ($result_code) {
      case 0:

        /*******************************************************
         * Success !
         * This is a successful transaction. PayFlow Pro does return further information
         * about transactions to help you identify fraud including whether they pass
         * the cvv check, the avs check. This is stored in
         * CiviCRM as part of the transact
         * but not further processing is done. Business rules would need to be defined
         *******************************************************/
        $params['trxn_id'] = $nvpArray['PNREF'] . $nvpArray['TRXPNREF'];
        //'trxn_id' is varchar(255) field. returned value is length 12
        $params['trxn_result_code'] = $nvpArray['AUTHCODE'] . "-Cvv2:" . $nvpArray['CVV2MATCH'] . "-avs:" . $nvpArray['AVSADDR'];

        if ($params['is_recur'] == TRUE) {
          $params['recur_trxn_id'] = $nvpArray['PROFILEID'];
          //'trxn_id' is varchar(255) field. returned value is length 12
        }
        return $params;

      case 1:
        return self::errorExit(9008, "There is a payment processor configuration problem. This is usually due to invalid account information or ip restrictions on the account.  You can verify ip restriction by logging         // into Manager.  See Service Settings >> Allowed IP Addresses.   ");

      case 12:
        // Hard decline from bank.
        return self::errorExit(9009, "Your transaction was declined   ");

      case 13:
        // Voice authorization required.
        return self::errorExit(9010, "Your Transaction is pending. Contact Customer Service to complete your order.");

      case 23:
        // Issue with credit card number or expiration date.
        return self::errorExit(9011, "Invalid credit card information. Please re-enter.");

      case 26:
        return self::errorExit(9012, "You have not configured your payment processor with the correct credentials. Make sure you have provided both the <vendor> and the <user> variables ");

      default:
        return self::errorExit(9013, "Error - from payment processor: [" . $result_code . " " . $nvpArray['RESPMSG'] . "] ");
    }

    return self::errorExit(9014, "Check the code - all transactions should have been headed off before they got here. Something slipped through the net");
  }

  /*
   * Produces error message and returns from class
   */
  /**
   * @param null $errorCode
   * @param null $errorMessage
   *
   * @return object
   */
  public function &errorExit($errorCode = NULL, $errorMessage = NULL) {
    $e = CRM_Core_Error::singleton();
    if ($errorCode) {
      $e->push($errorCode, 0, NULL, $errorMessage);
    }
    else {
      $e->push(9000, 0, NULL, 'Unknown System Error.');
    }
    return $e;
  }


  /*
   * NOTE: 'doTransferCheckout' not implemented
   */
  /**
   * @param array $params
   * @param $component
   *
   * @throws Exception
   */
  public function doTransferCheckout(&$params, $component) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  /*
   * This public function checks to see if we have the right processor config values set
   *
   * NOTE: Called by Events and Contribute to check config params are set prior to trying
   *  register any credit card details
   *
   * @param string $mode
   *   The mode we are operating in (live or test) - not used.
   *
   * returns string $errorMsg if any errors found - null if OK
   */

  //  function checkConfig( $mode )          // CiviCRM V1.9 Declaration

  /**
   * CiviCRM V2.0 Declaration
   * This function checks to see if we have the right config values
   *
   * @internal param string $mode the mode we are operating in (live or test)
   *
   * @return string
   *   the error message if any
   */
  public function checkConfig() {
    $errorMsg = array();
    if (empty($this->_paymentProcessor['user_name'])) {
      $errorMsg[] = ' ' . ts('ssl_merchant_id is not set for this payment processor');
    }

    if (empty($this->_paymentProcessor['url_site'])) {
      $errorMsg[] = ' ' . ts('URL is not set for %1', array(1 => $this->_paymentProcessor['name']));
    }

    if (!empty($errorMsg)) {
      return implode('<p>', $errorMsg);
    }
    else {
      return NULL;
    }
  }
  //end check config

  /*
   * convert to a name/value pair (nvp) string
   */
  /**
   * @param $payflow_query_array
   *
   * @return array|string
   */
  public function convert_to_nvp($payflow_query_array) {
    foreach ($payflow_query_array as $key => $value) {
      $payflow_query[] = $key . '[' . strlen($value) . ']=' . $value;
    }
    $payflow_query = implode('&', $payflow_query);

    return $payflow_query;
  }

  /*
   * Submit transaction using CuRL
   * @submiturl string Url to direct HTTPS GET to
   * @payflow_query value string to be posted
   */
  /**
   * @param $submiturl
   * @param $payflow_query
   *
   * @return mixed|object
   */
  public function submit_transaction($submiturl, $payflow_query) {
    /*
     * Submit transaction using CuRL
     */

    // get data ready for API
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    // Here's your custom headers; adjust appropriately for your setup:
    $headers[] = "Content-Type: text/namevalue";
    //or text/xml if using XMLPay.
    $headers[] = "Content-Length : " . strlen($data);
    // Length of data to be passed
    // Here the server timeout value is set to 45, but notice
    // below in the cURL section, the timeout
    // for cURL is 90 seconds.  You want to make sure the server
    // timeout is less, then the connection.
    $headers[] = "X-VPS-Timeout: 45";
    //random unique number  - the transaction is retried using this transaction ID
    // in this function but if that doesn't work and it is re- submitted
    // it is treated as a new attempt. PayflowPro doesn't allow
    // you to change details (e.g. card no) when you re-submit
    // you can only try the same details
    $headers[] = "X-VPS-Request-ID: " . rand(1, 1000000000);
    // optional header field
    $headers[] = "X-VPS-VIT-Integration-Product: CiviCRM";
    // other Optional Headers.  If used adjust as necessary.
    // Name of your OS
    //$headers[] = "X-VPS-VIT-OS-Name: Linux";
    // OS Version
    //$headers[] = "X-VPS-VIT-OS-Version: RHEL 4";
    // What you are using
    //$headers[] = "X-VPS-VIT-Client-Type: PHP/cURL";
    // For your info
    //$headers[] = "X-VPS-VIT-Client-Version: 0.01";
    // For your info
    //$headers[] = "X-VPS-VIT-Client-Architecture: x86";
    // Application version
    //$headers[] = "X-VPS-VIT-Integration-Version: 0.01";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $submiturl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    // tells curl to include headers in response
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // return into a variable
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);
    // times out after 90 secs
    if (ini_get('open_basedir') == '' && ini_get('safe_mode') == 'Off') {
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, Civi::settings()->get('verifySSL'));
    // this line makes it work under https
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payflow_query);
    //adding POST data
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, Civi::settings()->get('verifySSL') ? 2 : 0);
    //verifies ssl certificate
    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
    //forces closure of connection when done
    curl_setopt($ch, CURLOPT_POST, 1);
    //data sent as POST

    // Try to submit the transaction up to 3 times with 5 second delay.  This can be used
    // in case of network issues.  The idea here is since you are posting via HTTPS there
    // could be general network issues, so try a few times before you tell customer there
    // is an issue.

    $i = 1;
    while ($i++ <= 3) {
      $responseData = curl_exec($ch);
      $responseHeaders = curl_getinfo($ch);
      if ($responseHeaders['http_code'] != 200) {
        // Let's wait 5 seconds to see if its a temporary network issue.
        sleep(5);
      }
      elseif ($responseHeaders['http_code'] == 200) {
        // we got a good response, drop out of loop.
        break;
      }
    }

    /*
     * Transaction submitted -
     * See if we had a curl error - if so tell 'em and bail out
     *
     * NOTE: curl_error does not return a logical value (see its documentation), but
     *       a string, which is empty when there was no error.
     */
    if ((curl_errno($ch) > 0) || (strlen(curl_error($ch)) > 0)) {
      curl_close($ch);
      $errorNum = curl_errno($ch);
      $errorDesc = curl_error($ch);

      //Paranoia - in the unlikley event that 'curl' errno fails
      if ($errorNum == 0) {
        $errorNum = 9005;
      }

      // Paranoia - in the unlikley event that 'curl' error fails
      if (strlen($errorDesc) == 0) {
        $errorDesc = "Connection to payment gateway failed";
      }
      if ($errorNum = 60) {
        return self::errorExit($errorNum, "Curl error - " . $errorDesc .
          " Try this link for more information http://curl.haxx.se/d
                                         ocs/sslcerts.html"
        );
      }

      return self::errorExit($errorNum, "Curl error - " . $errorDesc .
        "  processor response = " . $processorResponse
      );
    }

    /*
     * If null data returned - tell 'em and bail out
     *
     * NOTE: You will not necessarily get a string back, if the request failed for
     *       any reason, the return value will be the boolean false.
     */
    if (($responseData === FALSE) || (strlen($responseData) == 0)) {
      curl_close($ch);
      return self::errorExit(9006, "Error: Connection to payment gateway failed - no data
                                           returned. Gateway url set to $submiturl");
    }

    /*
     * If gateway returned no data - tell 'em and bail out
     */
    if (empty($responseData)) {
      curl_close($ch);
      return self::errorExit(9007, "Error: No data returned from payment gateway.");
    }

    /*
     * Success so far - close the curl and check the data
     */
    curl_close($ch);
    return $responseData;
  }
  //end submit_transaction

  /**
   * @param int $recurringProfileID
   * @param int $processorID
   *
   * @throws Exception
   */
  public function getRecurringTransactionStatus($recurringProfileID, $processorID) {
    if (!defined('CURLOPT_SSLCERT')) {
      CRM_Core_Error::fatal(ts('PayFlowPro requires curl with SSL support'));
    }

    /*
     * define variables for connecting with the gateway
     */

    //if you have not set up a separate user account the vendor name is used as the username
    if (!$this->_paymentProcessor['subject']) {
      $user = $this->_paymentProcessor['user_name'];
    }
    else {
      $user = $this->_paymentProcessor['subject'];
    }
    //$recurringProfileID = "RT0000000001";
    //     c  $trythis =        $this->getRecurringTransactionStatus($recurringProfileID,17);

    /*
     *Create the array of variables to be sent to the processor from the $params array
     * passed into this function
     *
     */

    $payflow_query_array = array(
      'USER' => $user,
      'VENDOR' => $this->_paymentProcessor['user_name'],
      'PARTNER' => $this->_paymentProcessor['signature'],
      'PWD' => $this->_paymentProcessor['password'],
      // C - Direct Payment using credit card
      'TENDER' => 'C',
      // A - Authorization, S - Sale
      'TRXTYPE' => 'R',
      'ACTION' => 'I',
      //A for add recurring
      //(M-modify,C-cancel,R-reactivate,
      //I-inquiry,P-payment
      'ORIGPROFILEID' => $recurringProfileID,
      'PAYMENTHISTORY' => 'Y',
    );

    $payflow_query = $this->convert_to_nvp($payflow_query_array);
    echo $payflow_query;
    $submiturl = $this->_paymentProcessor['url_site'];
    //ie. url at payment processor to submit to.
    $responseData = self::submit_transaction($submiturl, $payflow_query);
    /*
     * Payment successfully sent to gateway - process the response now
     */

    $result = strstr($responseData, "RESULT");
    $nvpArray = array();
    while (strlen($result)) {
      // name
      $keypos = strpos($result, '=');
      $keyval = substr($result, 0, $keypos);
      // value
      $valuepos = strpos($result, '&') ? strpos($result, '&') : strlen($result);
      $valval = substr($result, $keypos + 1, $valuepos - $keypos - 1);
      // decoding the respose
      $nvpArray[$keyval] = $valval;
      $result = substr($result, $valuepos + 1, strlen($result));
    }
    // get the result code to validate.
    $result_code = $nvpArray['RESULT'];
    print_r($responseData);

    //RESPMSG=Invalid Profile ID: Invalid recurring profile ID
    //RT0000000001
  }

}
