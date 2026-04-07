<?php
/*
   +----------------------------------------------------------------------------+
   | Payflow Pro Core Payment Module for CiviCRM version 5                      |
   +----------------------------------------------------------------------------+
   | Licensed to CiviCRM under the Academic Free License version 3.0            |
   |                                                                            |
   | Written & Contributed by Eileen McNaughton - 2009                          |
   +---------------------------------------------------------------------------+
  */

use Civi\Payment\Exception\PaymentProcessorException;

/**
 * Class CRM_Core_Payment_PayflowPro.
 */
class CRM_Core_Payment_PayflowPro extends CRM_Core_Payment {

  /**
   * @var GuzzleHttp\Client
   */
  protected $guzzleClient;

  /**
   * Payment Processor Mode
   *   either test or live
   * @var string
   */
  protected $_mode;

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
  }

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

  /*
   * This function  sends request and receives response from
   * the processor. It is the main function for processing on-server
   * credit card transactions
   */

  /**
   * This function collects all the information from a web/api form and invokes
   * the relevant payment processor specific functions to perform the transaction
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
      throw new PaymentProcessorException(ts('Payflow Pro requires curl with SSL support'));
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
     * NB: PayFlowPro does not accept URL Encoded parameters.
     * Particularly problematic when amount contains grouping character: e.g 1,234.56 will return [4 - Invalid Amount]
     */

    $payflow_query_array = [
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
      'AMT' => $this->getAmount($params),
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
      'COMMENT2' => $this->_mode,
      'INVNUM' => urlencode($params['invoiceID']),
      'ORDERDESC' => urlencode($params['description']),
      'VERBOSITY' => 'MEDIUM',
      'BILLTOCOUNTRY' => urlencode($params['country']),
    ];

    if ($params['installments'] == 1) {
      $params['is_recur'] = FALSE;
    }

    if ($params['is_recur'] == TRUE) {

      $payflow_query_array['TRXTYPE'] = 'R';
      $payflow_query_array['OPTIONALTRX'] = 'S';
      $payflow_query_array['OPTIONALTRXAMT'] = $this->getAmount($params);
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

      $interval = $params['frequency_interval'] . " " . $params['frequency_unit'];
      switch ($interval) {
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
    if ($this->checkDupe($params['invoiceID'], $params['contributionID'] ?? NULL)) {
      throw new PaymentProcessorException('It appears that this transaction is a duplicate.  Have you already submitted the form once?  If so there may have been a connection problem.  Check your email for a receipt.  If you do not receive a receipt within 2 hours you can try your transaction again.  If you continue to have problems please contact the site administrator.', 9003);
    }

    // ie. url at payment processor to submit to.
    $submiturl = $this->_paymentProcessor['url_site'];

    $responseData = self::submit_transaction($submiturl, $payflow_query);

    /*
     * Payment successfully sent to gateway - process the response now
     */
    $responseResult = strstr($responseData, 'RESULT');
    if (empty($responseResult)) {
      throw new PaymentProcessorException('No RESULT code from PayPal.', 9016);
    }

    $nvpArray = [];
    while (strlen($responseResult)) {
      // name
      $keypos = strpos($responseResult, '=');
      $keyval = substr($responseResult, 0, $keypos);
      // value
      $valuepos = strpos($responseResult, '&') ? strpos($responseResult, '&') : strlen($responseResult);
      $valval = substr($responseResult, $keypos + 1, $valuepos - $keypos - 1);
      // decoding the respose
      $nvpArray[$keyval] = $valval;
      $responseResult = substr($responseResult, $valuepos + 1, strlen($responseResult));
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
         * This is a successful transaction. Payflow Pro does return further information
         * about transactions to help you identify fraud including whether they pass
         * the cvv check, the avs check. This is stored in
         * CiviCRM as part of the transact
         * but not further processing is done. Business rules would need to be defined
         *******************************************************/
        $result['trxn_id'] = ($nvpArray['PNREF'] ?? '') . ($nvpArray['TRXPNREF'] ?? '');
        //'trxn_id' is varchar(255) field. returned value is length 12
        $params['trxn_result_code'] = $nvpArray['AUTHCODE'] . "-Cvv2:" . $nvpArray['CVV2MATCH'] . "-avs:" . $nvpArray['AVSADDR'];

        if ($params['is_recur'] == TRUE) {
          $params['recur_trxn_id'] = $nvpArray['PROFILEID'];
          //'trxn_id' is varchar(255) field. returned value is length 12
        }
        $result = $this->setStatusPaymentCompleted($result);
        return $result;

      case 1:
        throw new PaymentProcessorException('There is a payment processor configuration problem. This is usually due to invalid account information or ip restrictions on the account.  You can verify ip restriction by logging         // into Manager.  See Service Settings >> Allowed IP Addresses.   ', 9003);

      case 12:
        // Hard decline from bank.
        throw new PaymentProcessorException('Your transaction was declined   ', 9009);

      case 13:
        // Voice authorization required.
        throw new PaymentProcessorException('Your Transaction is pending. Contact Customer Service to complete your order.', 9010);

      case 23:
        // Issue with credit card number or expiration date.
        throw new PaymentProcessorException('Invalid credit card information. Please re-enter.', 9011);

      case 26:
        throw new PaymentProcessorException('You have not configured your payment processor with the correct credentials. Make sure you have provided both the <vendor> and the <user> variables ', 9012);

      default:
        throw new PaymentProcessorException('Error - from payment processor: [' . $result_code . " " . $nvpArray['RESPMSG'] . "] ", 9013);
    }
  }

  /**
   * This public function checks to see if we have the right processor config values set
   *
   * NOTE: Called by Events and Contribute to check config params are set prior to trying
   *  register any credit card details
   *
   * @return string|null
   *   the error message if any, null if OK
   */
  public function checkConfig() {
    $errorMsg = [];
    if (empty($this->_paymentProcessor['user_name'])) {
      $errorMsg[] = ' ' . ts('ssl_merchant_id is not set for this payment processor');
    }

    if (empty($this->_paymentProcessor['url_site'])) {
      $errorMsg[] = ' ' . ts('URL is not set for %1', [1 => $this->_paymentProcessor['name']]);
    }

    if (!empty($errorMsg)) {
      return implode('<p>', $errorMsg);
    }
    return NULL;
  }

  /**
   * convert to a name/value pair (nvp) string
   *
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

  /**
   * Submit transaction using cURL
   *
   * @param string $submiturl Url to direct HTTPS GET to
   * @param string $payflow_query value string to be posted
   *
   * @return mixed|object
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function submit_transaction($submiturl, $payflow_query) {
    // get data ready for API
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Guzzle';
    // Here's your custom headers; adjust appropriately for your setup:
    $headers[] = "Content-Type: text/namevalue";
    //or text/xml if using XMLPay.
    $headers[] = "Content-Length : " . strlen($payflow_query);
    // Length of data to be passed
    // Here the server timeout value is set to 45, but notice
    // below in the cURL section, the timeout
    // for cURL is 90 seconds.  You want to make sure the server
    // timeout is less, then the connection.
    $headers[] = "X-VPS-Timeout: 45";
    //random unique number  - the transaction is retried using this transaction ID
    // in this function but if that doesn't work and it is re- submitted
    // it is treated as a new attempt. Payflow Pro doesn't allow
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
    $response = $this->getGuzzleClient()->post($submiturl, [
      'body' => $payflow_query,
      'headers' => $headers,
      'curl' => [
        CURLOPT_SSL_VERIFYPEER => Civi::settings()->get('verifySSL'),
        CURLOPT_USERAGENT => $user_agent,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_SSL_VERIFYHOST => Civi::settings()->get('verifySSL') ? 2 : 0,
        CURLOPT_POST => TRUE,
      ],
    ]);

    // Try to submit the transaction up to 3 times with 5 second delay.  This can be used
    // in case of network issues.  The idea here is since you are posting via HTTPS there
    // could be general network issues, so try a few times before you tell customer there
    // is an issue.

    $i = 1;
    while ($i++ <= 3) {
      $responseData = $response->getBody();
      $http_code = $response->getStatusCode();
      if ($http_code != 200) {
        // Let's wait 5 seconds to see if its a temporary network issue.
        sleep(5);
      }
      elseif ($http_code == 200) {
        // we got a good response, drop out of loop.
        break;
      }
    }
    if ($http_code != 200) {
      throw new PaymentProcessorException('Error connecting to the Payflow Pro API server.', 9015);
    }

    if (($responseData === FALSE) || (strlen($responseData) == 0)) {
      throw new PaymentProcessorException("Error: Connection to payment gateway failed - no data
                                           returned. Gateway url set to $submiturl", 9006);
    }

    /*
     * If gateway returned no data - tell 'em and bail out
     */
    if (empty($responseData)) {
      throw new PaymentProcessorException('Error: No data returned from payment gateway.', 9007);
    }

    /*
     * Success so far - close the curl and check the data
     */
    return $responseData;
  }

}
