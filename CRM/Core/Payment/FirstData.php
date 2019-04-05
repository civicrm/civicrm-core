<?php
/*
 +--------------------------------------------------------------------+
 | FirstData Core Payment Module for CiviCRM version 5                |
 +--------------------------------------------------------------------+
 | Licensed to CiviCRM under the Academic Free License version 3.0    |
 |                                                                    |
 | Written & Contributed by Eileen McNaughton - Nov March 2008        |
 +--------------------------------------------------------------------+
 |  This processor is based heavily on the Eway processor by Peter    |
 |Barwell                                                             |
 |                                                                    |
 |                                                                    |
 +--------------------------------------------------------------------+
 */

/**
 * Note that in order to use FirstData / LinkPoint you need a certificate (.pem) file issued by them
 * and a store number. You can configure the path to the certificate and the store number
 * through the front end of civiCRM. The path is as seen by the server not the url
 * -----------------------------------------------------------------------------------------------
 * The basic functionality of this processor is that variables from the $params object are transformed
 * into xml using a function provided by the processor. The xml is submitted to the processor's https site
 * using curl and the response is translated back into an array using the processor's function.
 *
 * If an array ($params) is returned to the calling function it is treated as a success and the values from
 * the array are merged into the calling functions array.
 *
 * If an result of class error is returned it is treated as a failure
 *
 * -----------------------------------------------------------------------------------------------
 */

/**
 * From Payment processor documentation
 * For testing purposes, you can use any of the card numbers listed below. The test card numbers
 * will not result in any charges to the card. Use these card numbers with any expiration date in the
 * future.
 *      Visa Level 2 - 4275330012345675 (replies with a referral message)
 *      JCB - 3566007770003510
 *      Discover - 6011000993010978
 *      MasterCard - 5424180279791765
 *      Visa - 4005550000000019 or 4111111111111111
 *      MasterCard Level 2 - 5404980000008386
 *      Diners - 36555565010005
 *      Amex - 372700997251009
 *
 * **************************
 * Lines starting with CRM_Core_Error::debug_log_message output messages to files/upload/civicrm.log - you may with to comment them out once it is working satisfactorily
 *
 * For live testing uncomment the result field below and set the value to the response you wish to get from the payment processor
 * **************************
 */
class CRM_Core_Payment_FirstData extends CRM_Core_Payment {
  # (not used, implicit in the API, might need to convert?)
  const CHARSET = 'UFT-8';

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
   * @return \CRM_Core_Payment_FirstData *******************************************************
   */
  public function __construct($mode, &$paymentProcessor) {
    // live or test
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
  }

  /**
   * Map fields from params array.
   *
   * This function is set up and put here to make the mapping of fields
   * as visually clear as possible for easy editing
   *
   *  Comment out irrelevant fields
   *
   * @param array $params
   *
   * @return array
   */
  public function mapProcessorFieldstoParams($params) {
    /*concatenate full customer name first  - code from EWAY gateway
     */

    $credit_card_name = $params['first_name'] . " ";
    if (strlen($params['middle_name']) > 0) {
      $credit_card_name .= $params['middle_name'] . " ";
    }
    $credit_card_name .= $params['last_name'];

    //compile array

    /**********************************************************
     *    Payment Processor field name         **fields from $params array   ***
     *******************************************************************/

    $requestFields['cardnumber'] = $params['credit_card_number'];
    $requestFields['chargetotal'] = $params['amount'];
    $requestFields['cardexpmonth'] = sprintf('%02d', (int) $params['month']);
    $requestFields['cardexpyear'] = substr($params['year'], 2, 2);
    $requestFields['cvmvalue'] = $params['cvv2'];
    $requestFields['cvmindicator'] = "provided";
    $requestFields['name'] = $credit_card_name;
    $requestFields['address1'] = $params['street_address'];
    $requestFields['city'] = $params['city'];
    $requestFields['state'] = $params['state_province'];
    $requestFields['zip'] = $params['postal_code'];
    $requestFields['country'] = $params['country'];
    $requestFields['email'] = $params['email'];
    $requestFields['ip'] = $params['ip_address'];
    $requestFields['transactionorigin'] = "Eci";
    #32 character string
    $requestFields['invoice_number'] = $params['invoiceID'];
    $requestFields['ordertype'] = 'Sale';
    $requestFields['comments'] = $params['description'];
    //**********************set 'result' for live testing **************************
    //  $requestFields[       'result'  ]      =    "";  #set to "Good", "Decline" or "Duplicate"
    //  $requestFields[       ''  ]          =  $params[ 'qfKey'        ];
    //  $requestFields[       ''  ]          =  $params[ 'amount_other'      ];
    //  $requestFields[       ''  ]          =  $params[ 'billing_first_name'    ];
    //  $requestFields[       ''  ]          =  $params[ 'billing_middle_name'    ];
    //  $requestFields[       ''  ]          =  $params[ 'billing_last_name'  ];

    //  $requestFields[       ''  ]          =  $params[ 'contributionType_name'  ];
    //  $requestFields[       ''  ]          =  $params[ 'contributionPageID'  ];
    //  $requestFields[       ''  ]          =  $params[ 'contributionType_accounting_code'  ];
    //  $requestFields[       ''  ]          =  $params['amount_level'  ];
    //  $requestFields[       ''  ]          =  $params['credit_card_type'  ];
    //  $requestFields[       'addrnum'  ]    =  numeric portion of street address - not yet implemented
    //  $requestFields[       'taxexempt'  ]   taxexempt status (Y or N) - not implemented

    return $requestFields;
  }

  /**
   * This function sends request and receives response from
   * the processor
   *
   * @param array $params
   *
   * @return array|object
   * @throws \Exception
   */
  public function doDirectPayment(&$params) {
    if ($params['is_recur'] == TRUE) {
      CRM_Core_Error::fatal(ts('First Data - recurring payments not implemented'));
    }

    if (!defined('CURLOPT_SSLCERT')) {
      CRM_Core_Error::fatal(ts('%1 - Gateway requires curl with SSL support', [1 => $paymentProcessor]));
    }

    /**********************************************************
     * Create the array of variables to be sent to the processor from the $params array
     * passed into this function
     **********************************************************/
    $requestFields = self::mapProcessorFieldstoParams($params);

    /**********************************************************
     * create FirstData request object
     **********************************************************/
    require_once 'FirstData/lphp.php';
    //  $mylphp=new lphp;

    /**********************************************************
     * define variables for connecting with the gateway
     **********************************************************/

    # Name and location of certificate file
    $key = $this->_paymentProcessor['password'];
    # Your store number
    $requestFields["configfile"] = $this->_paymentProcessor['user_name'];
    $port = "1129";
    $host = $this->_paymentProcessor['url_site'] . ":" . $port . "/LSGSXML";

    //----------------------------------------------------------------------------------------------------
    // Check to see if we have a duplicate before we send
    //----------------------------------------------------------------------------------------------------
    if ($this->checkDupe($params['invoiceID'], CRM_Utils_Array::value('contributionID', $params))) {
      return self::errorExit(9003, 'It appears that this transaction is a duplicate.  Have you already submitted the form once?  If so there may have been a connection problem.  Check your email for a receipt from eWAY.  If you do not receive a receipt within 2 hours you can try your transaction again.  If you continue to have problems please contact the site administrator.');
    }
    //----------------------------------------------------------------------------------------------------
    // Convert to XML using function provided by payment processor
    //----------------------------------------------------------------------------------------------------
    $requestxml = lphp::buildXML($requestFields);

    /*----------------------------------------------------------------------------------------------------
    // Send to the payment information using cURL
    /----------------------------------------------------------------------------------------------------
     */

    $ch = curl_init($host);
    if (!$ch) {
      return self::errorExit(9004, 'Could not initiate connection to payment gateway');
    }

    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestxml);
    curl_setopt($ch, CURLOPT_SSLCERT, $key);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, Civi::settings()->get('verifySSL') ? 2 : 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, Civi::settings()->get('verifySSL'));
    // return the result on success, FALSE on failure
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 36000);
    // ensures any Location headers are followed
    if (ini_get('open_basedir') == '' && ini_get('safe_mode') == 'Off') {
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    }

    // Send the data out over the wire
    //--------------------------------
    $responseData = curl_exec($ch);

    //----------------------------------------------------------------------------------------------------
    // See if we had a curl error - if so tell 'em and bail out
    //
    // NOTE: curl_error does not return a logical value (see its documentation), but
    //       a string, which is empty when there was no error.
    //----------------------------------------------------------------------------------------------------
    if ((curl_errno($ch) > 0) || (strlen(curl_error($ch)) > 0)) {
      $errorNum = curl_errno($ch);
      $errorDesc = curl_error($ch);

      // Paranoia - in the unlikley event that 'curl' errno fails
      if ($errorNum == 0) {
        $errorNum = 9005;
      }

      // Paranoia - in the unlikley event that 'curl' error fails
      if (strlen($errorDesc) == 0) {
        $errorDesc = "Connection to payment gateway failed";
      }
      if ($errorNum == 60) {
        return self::errorExit($errorNum, "Curl error - " . $errorDesc . " Try this link for more information http://curl.haxx.se/docs/sslcerts.html");
      }

      return self::errorExit($errorNum, "Curl error - " . $errorDesc . " your key is located at " . $key . " the url is " . $host . " xml is " . $requestxml . " processor response = " . $processorResponse);
    }

    //----------------------------------------------------------------------------------------------------
    // If null data returned - tell 'em and bail out
    //
    // NOTE: You will not necessarily get a string back, if the request failed for
    //       any reason, the return value will be the boolean false.
    //----------------------------------------------------------------------------------------------------
    if (($responseData === FALSE) || (strlen($responseData) == 0)) {
      return self::errorExit(9006, "Error: Connection to payment gateway failed - no data returned.");
    }

    //----------------------------------------------------------------------------------------------------
    // If gateway returned no data - tell 'em and bail out
    //----------------------------------------------------------------------------------------------------
    if (empty($responseData)) {
      return self::errorExit(9007, "Error: No data returned from payment gateway.");
    }

    //----------------------------------------------------------------------------------------------------
    // Success so far - close the curl and check the data
    //----------------------------------------------------------------------------------------------------
    curl_close($ch);

    //----------------------------------------------------------------------------------------------------
    // Payment successfully sent to gateway - process the response now
    //----------------------------------------------------------------------------------------------------
    //
    $processorResponse = lphp::decodeXML($responseData);

    // transaction failed, print the reason
    if ($processorResponse["r_approved"] != "APPROVED") {
      return self::errorExit(9009, "Error: [" . $processorResponse['r_error'] . "] - from payment processor");
    }
    else {

      //-----------------------------------------------------------------------------------------------------
      // Cross-Check - the unique 'TrxnReference' we sent out should match the just received 'TrxnReference'
      //
      // this section not used as the processor doesn't appear to pass back our invoice no. Code in eWay model if
      // used later
      //-----------------------------------------------------------------------------------------------------

      //=============
      // Success !
      //=============
      $params['trxn_result_code'] = $processorResponse['r_message'];
      $params['trxn_id'] = $processorResponse['r_ref'];
      CRM_Core_Error::debug_log_message("r_authresponse " . $processorResponse['r_authresponse']);
      CRM_Core_Error::debug_log_message("r_code " . $processorResponse['r_code']);
      CRM_Core_Error::debug_log_message("r_tdate " . $processorResponse['r_tdate']);
      CRM_Core_Error::debug_log_message("r_avs " . $processorResponse['r_avs']);
      CRM_Core_Error::debug_log_message("r_ordernum " . $processorResponse['r_ordernum']);
      CRM_Core_Error::debug_log_message("r_error " . $processorResponse['r_error']);
      CRM_Core_Error::debug_log_message("csp " . $processorResponse['r_csp']);
      CRM_Core_Error::debug_log_message("r_message " . $processorResponse['r_message']);
      CRM_Core_Error::debug_log_message("r_ref " . $processorResponse['r_ref']);
      CRM_Core_Error::debug_log_message("r_time " . $processorResponse['r_time']);
      return $params;
    }
  }
  // end function doDirectPayment

  /**
   * Produces error message and returns from class.
   *
   * @param int $errorCode
   * @param string $errorMessage
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

  /**
   * This public function checks to see if we have the right processor config values set.
   *
   * NOTE: Called by Events and Contribute to check config params are set prior to trying
   *       register any credit card details
   *
   * @return null|string
   * @internal param string $mode the mode we are operating in (live or test) - not used
   *
   * returns string $errorMsg if any errors found - null if OK
   *
   *  function checkConfig( $mode )           CiviCRM V1.9 Declaration
   * CiviCRM V2.0 Declaration
   */
  public function checkConfig() {
    $errorMsg = [];

    if (empty($this->_paymentProcessor['user_name'])) {
      $errorMsg[] = ts(' Store Name is not set for this payment processor');
    }

    if (empty($this->_paymentProcessor['url_site'])) {
      $errorMsg[] = ts(' URL is not set for this payment processor');
    }

    if (!empty($errorMsg)) {
      return implode('<p>', $errorMsg);
    }
    else {
      return NULL;
    }
  }

}
// end class CRM_Core_Payment_FirstData
