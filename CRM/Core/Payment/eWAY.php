<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
 +--------------------------------------------------------------------+
 | eWAY Core Payment Module for CiviCRM version 4.7 & 1.9             |
 +--------------------------------------------------------------------+
 | Licensed to CiviCRM under the Academic Free License version 3.0    |
 |                                                                    |
 | Written & Contributed by Dolphin Software P/L - March 2008         |
 +--------------------------------------------------------------------+
 |                                                                    |
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | This code was initially based on the recent PayJunction module     |
 | contributed by Phase2 Technology, and then plundered bits from     |
 | the AuthorizeNet module contributed by Ideal Solution, and         |
 | referenced the eWAY code in Drupal 5.7's ecommerce-5.x-3.4 and     |
 | ecommerce-5.x-4.x-dev modules.                                     |
 |                                                                    |
 | Plus a bit of our own code of course - Peter Barwell               |
 | contact PB@DolphinSoftware.com.au if required.                     |
 |                                                                    |
 | NOTE: This initial eWAY module does not yet allow for recurring     |
 |       payments - contact Peter Barwell or add yourself (or both)   |
 |                                                                    |
 | NOTE: The eWAY gateway only allows a single currency per account   |
 |       (per eWAY CustomerID) ie you can only have one currency per  |
 |       added Payment Processor.                                     |
 |       The only way to add multi-currency is to code it so that a   |
 |       different CustomerID is used per currency.                   |
 |                                                                    |
 +--------------------------------------------------------------------+
 */

/**
 * -----------------------------------------------------------------------------------------------
 * From the eWAY supplied 'Web.config' dated 25-Sep-2006 - check date and update links if required
 * -----------------------------------------------------------------------------------------------
 *
 * LIVE gateway with CVN
 * https://www.eway.com.au/gateway_cvn/xmlpayment.asp
 *
 * LIVE gateway without CVN
 * https://www.eway.com.au/gateway/xmlpayment.asp
 *
 *
 * Test gateway with CVN
 * https://www.eway.com.au/gateway_cvn/xmltest/TestPage.asp
 *
 * Test gateway without CVN
 * https://www.eway.com.au/gateway/xmltest/TestPage.asp
 *
 *
 * LIVE gateway for Stored Transactions
 * https://www.eway.com.au/gateway/xmlstored.asp
 *
 *
 * -----------------------------------------------------------------------------------------------
 * From the eWAY web-site - http://www.eway.com.au/Support/Developer/PaymentsRealTime.aspx
 * -----------------------------------------------------------------------------------------------
 * The test Customer ID is 87654321 - this is the only ID that will work on the test gateway.
 * The test Credit Card number is 4444333322221111
 * - this is the only credit card number that will work on the test gateway.
 * The test Total Amount should end in 00 or 08 to get a successful response (e.g. $10.00 or $10.08)
 * ie - all other amounts will return a failed response.
 *
 * -----------------------------------------------------------------------------------------------
 */

// require Standard eWAY API libraries
require_once 'eWAY/eWAY_GatewayRequest.php';
require_once 'eWAY/eWAY_GatewayResponse.php';

class CRM_Core_Payment_eWAY extends CRM_Core_Payment {
  # (not used, implicit in the API, might need to convert?)
  const CHARSET = 'UTF-8';

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   */
  static private $_singleton = NULL;

  /**
   * *******************************************************
   * Constructor
   *
   * @param string $mode
   *   The mode of operation: live or test.
   *
   * @param int $paymentProcessor
   *
   * *******************************************************
   */
  public function __construct($mode, &$paymentProcessor) {

    // live or test
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('eWay');
  }

  /**
   * Sends request and receive response from eWAY payment process.
   *
   * @param array $params
   *
   * @return array|object
   * @throws \Exception
   */
  public function doDirectPayment(&$params) {
    if (CRM_Utils_Array::value('is_recur', $params) == TRUE) {
      CRM_Core_Error::fatal(ts('eWAY - recurring payments not implemented'));
    }

    if (!defined('CURLOPT_SSLCERT')) {
      CRM_Core_Error::fatal(ts('eWAY - Gateway requires curl with SSL support'));
    }

    // eWAY Client ID
    $ewayCustomerID = $this->_paymentProcessor['user_name'];
    // eWAY Gateway URL
    $gateway_URL = $this->_paymentProcessor['url_site'];

    //------------------------------------
    // create eWAY gateway objects
    //------------------------------------
    $eWAYRequest = new GatewayRequest();

    if (($eWAYRequest == NULL) || (!($eWAYRequest instanceof GatewayRequest))) {
      return self::errorExit(9001, "Error: Unable to create eWAY Request object.");
    }

    $eWAYResponse = new GatewayResponse();

    if (($eWAYResponse == NULL) || (!($eWAYResponse instanceof GatewayResponse))) {
      return self::errorExit(9002, "Error: Unable to create eWAY Response object.");
    }

    /*
    //-------------------------------------------------------------
    // NOTE: eWAY Doesn't use the following at the moment:
    //-------------------------------------------------------------
    $creditCardType = $params['credit_card_type'];
    $currentcyID    = $params['currencyID'];
    $country        = $params['country'];
     */

    //-------------------------------------------------------------
    // Prepare some composite data from _paymentProcessor fields
    //-------------------------------------------------------------
    $fullAddress = $params['street_address'] . ", " . $params['city'] . ", " . $params['state_province'] . ".";
    $expireYear = substr($params['year'], 2, 2);
    $expireMonth = sprintf('%02d', (int) $params['month']);
    // CiviCRM V1.9 - Picks up reasonable description
    //$description = $params['amount_level'];
    // CiviCRM V2.0 - Picks up description
    $description = $params['description'];
    $txtOptions = "";

    $amountInCents = round(((float) $params['amount']) * 100);

    $credit_card_name = $params['first_name'] . " ";
    if (strlen($params['middle_name']) > 0) {
      $credit_card_name .= $params['middle_name'] . " ";
    }
    $credit_card_name .= $params['last_name'];

    //----------------------------------------------------------------------------------------------------
    // We use CiviCRM's param's 'invoiceID' as the unique transaction token to feed to eWAY
    // Trouble is that eWAY only accepts 16 chars for the token, while CiviCRM's invoiceID is an 32.
    // As its made from a "$invoiceID = md5(uniqid(rand(), true));" then using the fierst 16 chars
    // should be alright
    //----------------------------------------------------------------------------------------------------
    $uniqueTrnxNum = substr($params['invoiceID'], 0, 16);

    //----------------------------------------------------------------------------------------------------
    // OPTIONAL: If TEST Card Number force an Override of URL and CutomerID.
    // During testing CiviCRM once used the LIVE URL.
    // This code can be uncommented to override the LIVE URL that if CiviCRM does that again.
    //----------------------------------------------------------------------------------------------------
    //        if ( ( $gateway_URL == "https://www.eway.com.au/gateway_cvn/xmlpayment.asp")
    //             && ( $params['credit_card_number'] == "4444333322221111" ) ) {
    //            $ewayCustomerID = "87654321";
    //            $gateway_URL    = "https://www.eway.com.au/gateway_cvn/xmltest/testpage.asp";
    //        }

    //----------------------------------------------------------------------------------------------------
    // Now set the payment details - see http://www.eway.com.au/Support/Developer/PaymentsRealTime.aspx
    //----------------------------------------------------------------------------------------------------
    // 8 Chars - ewayCustomerID                 - Required
    $eWAYRequest->EwayCustomerID($ewayCustomerID);
    // 12 Chars - ewayTotalAmount  (in cents)    - Required
    $eWAYRequest->InvoiceAmount($amountInCents);
    // 50 Chars - ewayCustomerFirstName
    $eWAYRequest->PurchaserFirstName($params['first_name']);
    // 50 Chars - ewayCustomerLastName
    $eWAYRequest->PurchaserLastName($params['last_name']);
    // 50 Chars - ewayCustomerEmail
    $eWAYRequest->PurchaserEmailAddress($params['email']);
    // 255 Chars - ewayCustomerAddress
    $eWAYRequest->PurchaserAddress($fullAddress);
    // 6 Chars - ewayCustomerPostcode
    $eWAYRequest->PurchaserPostalCode($params['postal_code']);
    // 1000 Chars - ewayCustomerInvoiceDescription
    $eWAYRequest->InvoiceDescription($description);
    // 50 Chars - ewayCustomerInvoiceRef
    $eWAYRequest->InvoiceReference($params['invoiceID']);
    // 50 Chars - ewayCardHoldersName            - Required
    $eWAYRequest->CardHolderName($credit_card_name);
    // 20 Chars - ewayCardNumber                 - Required
    $eWAYRequest->CardNumber($params['credit_card_number']);
    // 2 Chars - ewayCardExpiryMonth            - Required
    $eWAYRequest->CardExpiryMonth($expireMonth);
    // 2 Chars - ewayCardExpiryYear             - Required
    $eWAYRequest->CardExpiryYear($expireYear);
    // 4 Chars - ewayCVN                        - Required if CVN Gateway used
    $eWAYRequest->CVN($params['cvv2']);
    // 16 Chars - ewayTrxnNumber
    $eWAYRequest->TransactionNumber($uniqueTrnxNum);
    // 255 Chars - ewayOption1
    $eWAYRequest->EwayOption1($txtOptions);
    // 255 Chars - ewayOption2
    $eWAYRequest->EwayOption2($txtOptions);
    // 255 Chars - ewayOption3
    $eWAYRequest->EwayOption3($txtOptions);

    $eWAYRequest->CustomerIPAddress($params['ip_address']);
    $eWAYRequest->CustomerBillingCountry($params['country']);

    // Allow further manipulation of the arguments via custom hooks ..
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $eWAYRequest);

    //----------------------------------------------------------------------------------------------------
    // Check to see if we have a duplicate before we send
    //----------------------------------------------------------------------------------------------------
    if ($this->checkDupe($params['invoiceID'], CRM_Utils_Array::value('contributionID', $params))) {
      return self::errorExit(9003, 'It appears that this transaction is a duplicate.  Have you already submitted the form once?  If so there may have been a connection problem.  Check your email for a receipt from eWAY.  If you do not receive a receipt within 2 hours you can try your transaction again.  If you continue to have problems please contact the site administrator.');
    }

    //----------------------------------------------------------------------------------------------------
    // Convert to XML and send the payment information
    //----------------------------------------------------------------------------------------------------
    $requestxml = $eWAYRequest->ToXML();

    $submit = curl_init($gateway_URL);

    if (!$submit) {
      return self::errorExit(9004, 'Could not initiate connection to payment gateway');
    }

    curl_setopt($submit, CURLOPT_POST, TRUE);
    // return the result on success, FALSE on failure
    curl_setopt($submit, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($submit, CURLOPT_POSTFIELDS, $requestxml);
    curl_setopt($submit, CURLOPT_TIMEOUT, 36000);
    // if open_basedir or safe_mode are enabled in PHP settings CURLOPT_FOLLOWLOCATION won't work so don't apply it
    // it's not really required CRM-5841
    if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
      // ensures any Location headers are followed
      curl_setopt($submit, CURLOPT_FOLLOWLOCATION, 1);
    }

    // Send the data out over the wire
    //--------------------------------
    $responseData = curl_exec($submit);

    //----------------------------------------------------------------------------------------------------
    // See if we had a curl error - if so tell 'em and bail out
    //
    // NOTE: curl_error does not return a logical value (see its documentation), but
    //       a string, which is empty when there was no error.
    //----------------------------------------------------------------------------------------------------
    if ((curl_errno($submit) > 0) || (strlen(curl_error($submit)) > 0)) {
      $errorNum = curl_errno($submit);
      $errorDesc = curl_error($submit);

      // Paranoia - in the unlikley event that 'curl' errno fails
      if ($errorNum == 0) {
        $errorNum = 9005;
      }

      // Paranoia - in the unlikley event that 'curl' error fails
      if (strlen($errorDesc) == 0) {
        $errorDesc = "Connection to eWAY payment gateway failed";
      }

      return self::errorExit($errorNum, $errorDesc);
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
    curl_close($submit);

    //----------------------------------------------------------------------------------------------------
    // Payment successfully sent to gateway - process the response now
    //----------------------------------------------------------------------------------------------------
    $eWAYResponse->ProcessResponse($responseData);

    //----------------------------------------------------------------------------------------------------
    // See if we got an OK result - if not tell 'em and bail out
    //----------------------------------------------------------------------------------------------------
    if (self::isError($eWAYResponse)) {
      $eWayTrxnError = $eWAYResponse->Error();
      CRM_Core_Error::debug_var('eWay Error', $eWayTrxnError, TRUE, TRUE);
      if (substr($eWayTrxnError, 0, 6) == "Error:") {
        return self::errorExit(9008, $eWayTrxnError);
      }
      $eWayErrorCode = substr($eWayTrxnError, 0, 2);
      $eWayErrorDesc = substr($eWayTrxnError, 3);

      return self::errorExit(9008, "Error: [" . $eWayErrorCode . "] - " . $eWayErrorDesc . ".");
    }

    //-----------------------------------------------------------------------------------------------------
    // Cross-Check - the unique 'TrxnReference' we sent out should match the just received 'TrxnReference'
    //
    // PLEASE NOTE: If this occurs (which is highly unlikely) its a serious error as it would mean we have
    //              received an OK status from eWAY, but their Gateway has not returned the correct unique
    //              token - ie something is broken, BUT money has been taken from the client's account,
    //              so we can't very well error-out as CiviCRM will then not process the registration.
    //              There is an error message commented out here but my preferred response to this unlikley
    //              possibility is to email 'support@eWAY.com.au'
    //-----------------------------------------------------------------------------------------------------
    $eWayTrxnReference_OUT = $eWAYRequest->GetTransactionNumber();
    $eWayTrxnReference_IN = $eWAYResponse->InvoiceReference();

    if ($eWayTrxnReference_IN != $eWayTrxnReference_OUT) {
      // return self::errorExit( 9009, "Error: Unique Trxn code was not returned by eWAY Gateway. This is extremely unusual! Please contact the administrator of this site immediately with details of this transaction.");

    }

    /*
    //----------------------------------------------------------------------------------------------------
    // Test mode always returns trxn_id = 0 - so we fix that here
    //
    // NOTE: This code was taken from the AuthorizeNet payment processor, however it now appears
    //       unnecessary for the eWAY gateway - Left here in case it proves useful
    //----------------------------------------------------------------------------------------------------
    if ( $this->_mode == 'test' ) {
    $query             = "SELECT MAX(trxn_id) FROM civicrm_contribution WHERE trxn_id LIKE 'test%'";
    $p                 = array( );
    $trxn_id           = strval( CRM_Core_Dao::singleValueQuery( $query, $p ) );
    $trxn_id           = str_replace( 'test', '', $trxn_id );
    $trxn_id           = intval($trxn_id) + 1;
    $params['trxn_id'] = sprintf('test%08d', $trxn_id);
    }
    else {
    $params['trxn_id'] = $eWAYResponse->TransactionNumber();
    }
     */

    //=============
    // Success !
    //=============
    $beaglestatus = $eWAYResponse->BeagleScore();
    if (!empty($beaglestatus)) {
      $beaglestatus = ": " . $beaglestatus;
    }
    $params['trxn_result_code'] = $eWAYResponse->Status() . $beaglestatus;
    $params['gross_amount'] = $eWAYResponse->Amount();
    $params['trxn_id'] = $eWAYResponse->TransactionNumber();

    return $params;
  }
  // end function doDirectPayment

  /**
   * Checks the eWAY response status - returning a boolean false if status != 'true'.
   *
   * @param object $response
   *
   * @return bool
   */
  public function isError(&$response) {
    $status = $response->Status();

    if ((stripos($status, "true")) === FALSE) {
      return TRUE;
    }
    return FALSE;
  }

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
   * *****************************************************************************************
   * This public function checks to see if we have the right processor config values set
   *
   * NOTE: Called by Events and Contribute to check config params are set prior to trying
   *       register any credit card details
   *
   * @return null|string
   * @internal param string $mode the mode we are operating in (live or test) - not used but could be
   * to check that the 'test' mode CustomerID was equal to '87654321' and that the URL was
   * set to https://www.eway.com.au/gateway_cvn/xmltest/TestPage.asp
   *
   * returns string $errorMsg if any errors found - null if OK
   *
   * *****************************************************************************************
   */
  public function checkConfig() {
    $errorMsg = array();

    if (empty($this->_paymentProcessor['user_name'])) {
      $errorMsg[] = ts('eWAY CustomerID is not set for this payment processor');
    }

    if (empty($this->_paymentProcessor['url_site'])) {
      $errorMsg[] = ts('eWAY Gateway URL is not set for this payment processor');
    }

    if (!empty($errorMsg)) {
      return implode('<p>', $errorMsg);
    }
    else {
      return NULL;
    }
  }

}
// end class CRM_Core_Payment_eWAY
