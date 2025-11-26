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

use Civi\Payment\Exception\PaymentProcessorException;

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

    $credit_card_name = $params['first_name'] . ' ';
    if (strlen($params['middle_name']) > 0) {
      $credit_card_name .= $params['middle_name'] . ' ';
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
    // 32 character string
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
    $result = $this->setStatusPaymentPending([]);

    // If we have a $0 amount, skip call to processor and set payment_status to Completed.
    // Conceivably a processor might override this - perhaps for setting up a token - but we don't
    // have an example of that at the moment.
    if ($propertyBag->getAmount() == 0) {
      $result = $this->setStatusPaymentCompleted($result);
      return $result;
    }

    if ($params['is_recur'] == TRUE) {
      throw new CRM_Core_Exception(ts('First Data - recurring payments not implemented'));
    }

    if (!defined('CURLOPT_SSLCERT')) {
      throw new CRM_Core_Exception(ts('%1 - Gateway requires curl with SSL support', [1 => $paymentProcessor]));
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

    // Name and location of certificate file
    $key = $this->_paymentProcessor['password'];
    // Your store number
    $requestFields["configfile"] = $this->_paymentProcessor['user_name'];
    $port = "1129";
    $host = $this->_paymentProcessor['url_site'] . ":" . $port . "/LSGSXML";

    //----------------------------------------------------------------------------------------------------
    // Check to see if we have a duplicate before we send
    //----------------------------------------------------------------------------------------------------
    if ($this->checkDupe($params['invoiceID'], $params['contributionID'] ?? NULL)) {
      throw new PaymentProcessorException('It appears that this transaction is a duplicate.  Have you already submitted the form once?  If so there may have been a connection problem.  Check your email for a receipt from eWAY.  If you do not receive a receipt within 2 hours you can try your transaction again.  If you continue to have problems please contact the site administrator.', 9003);
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
      throw new PaymentProcessorException('Could not initiate connection to payment gateway', 9004);
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
    if (ini_get('open_basedir') == '') {
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
        throw new PaymentProcessorException("Curl error - " . $errorDesc . ' Try this link for more information http://curl.haxx.se/docs/sslcerts.html', $errorNum);
      }

      throw new PaymentProcessorException('Curl error - ' . $errorDesc . ' your key is located at ' . $key . ' the url is ' . $host . ' xml is ' . $requestxml . ' processor response = ' . $processorResponse, $errorNum);
    }

    //----------------------------------------------------------------------------------------------------
    // If null data returned - tell 'em and bail out
    //
    // NOTE: You will not necessarily get a string back, if the request failed for
    //       any reason, the return value will be the boolean false.
    //----------------------------------------------------------------------------------------------------
    if (($responseData === FALSE) || (strlen($responseData) == 0)) {
      throw new PaymentProcessorException('Error: Connection to payment gateway failed - no data returned.', 9006);
    }

    //----------------------------------------------------------------------------------------------------
    // If gateway returned no data - tell 'em and bail out
    //----------------------------------------------------------------------------------------------------
    if (empty($responseData)) {
      throw new PaymentProcessorException('Error: No data returned from payment gateway.', 9007);
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
    if ($processorResponse['r_approved'] !== "APPROVED") {
      throw new PaymentProcessorException('Error: [' . $processorResponse['r_error'] . '] - from payment processor', 9009);
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
      $result['trxn_id'] = $processorResponse['r_ref'];
      $result = $this->setStatusPaymentCompleted($result);
      Civi::log('first_data')->debug("r_authresponse " . $processorResponse['r_authresponse']);
      Civi::log('first_data')->debug("r_code " . $processorResponse['r_code']);
      Civi::log('first_data')->debug("r_tdate " . $processorResponse['r_tdate']);
      Civi::log('first_data')->debug("r_avs " . $processorResponse['r_avs']);
      Civi::log('first_data')->debug("r_ordernum " . $processorResponse['r_ordernum']);
      Civi::log('first_data')->debug("r_error " . $processorResponse['r_error']);
      Civi::log('first_data')->debug("csp " . $processorResponse['r_csp']);
      Civi::log('first_data')->debug("r_message " . $processorResponse['r_message']);
      Civi::log('first_data')->debug("r_ref " . $processorResponse['r_ref']);
      Civi::log('first_data')->debug("r_time " . $processorResponse['r_time']);
      return $result;
    }
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
      $errorMsg[] = ts('Store Name is not set for this payment processor');
    }

    if (empty($this->_paymentProcessor['url_site'])) {
      $errorMsg[] = ts('URL is not set for this payment processor');
    }

    if (!empty($errorMsg)) {
      return implode('<p>', $errorMsg);
    }
    return NULL;
  }

}
// end class CRM_Core_Payment_FirstData
