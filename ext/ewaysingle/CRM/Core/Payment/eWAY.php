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
 +--------------------------------------------------------------------+
 | eWAY Core Payment Module for CiviCRM version 5 & 1.9               |
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

use Civi\Payment\Exception\PaymentProcessorException;
use CRM_Ewaysingle_ExtensionUtil as E;

// require Standard eWAY API libraries
require_once E::path('lib/eWAY/eWAY_GatewayRequest.php');
require_once E::path('lib/eWAY/eWAY_GatewayResponse.php');

/**
 * Class CRM_Core_Payment_eWAY.
 */
class CRM_Core_Payment_eWAY extends CRM_Core_Payment {

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

  /**
   * Sends request and receive response from eWAY payment process.
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

    if (!empty($params['is_recur'])) {
      throw new CRM_Core_Exception(ts('eWAY - recurring payments not implemented'));
    }

    if (!defined('CURLOPT_SSLCERT')) {
      throw new CRM_Core_Exception(ts('eWAY - Gateway requires curl with SSL support'));
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
      throw new PaymentProcessorException('Error: Unable to create eWAY Request object.', 9001);
    }

    $eWAYResponse = new GatewayResponse();

    if (($eWAYResponse == NULL) || (!($eWAYResponse instanceof GatewayResponse))) {
      throw new PaymentProcessorException(9002, 'Error: Unable to create eWAY Response object.', 9002);
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
    // As its made from a "$invoiceID = bin2hex(random_bytes(16))" then using the first 16 chars
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
    if ($this->checkDupe($params['invoiceID'], $params['contributionID'] ?? NULL)) {
      throw new PaymentProcessorException('It appears that this transaction is a duplicate.  Have you already submitted the form once?  If so there may have been a connection problem.  Check your email for a receipt from eWAY.  If you do not receive a receipt within 2 hours you can try your transaction again.  If you continue to have problems please contact the site administrator.', 9003);
    }

    //----------------------------------------------------------------------------------------------------
    // Convert to XML and send the payment information
    //----------------------------------------------------------------------------------------------------
    $requestxml = $eWAYRequest->ToXML();

    $responseData = (string) $this->getGuzzleClient()->post($this->_paymentProcessor['url_site'], [
      'body' => $requestxml,
      'curl' => [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_SSL_VERIFYPEER => Civi::settings()->get('verifySSL'),
      ],
    ])->getBody();

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
    // Payment successfully sent to gateway - process the response now
    //----------------------------------------------------------------------------------------------------
    $eWAYResponse->ProcessResponse($responseData);

    //----------------------------------------------------------------------------------------------------
    // See if we got an OK result - if not tell 'em and bail out
    //----------------------------------------------------------------------------------------------------
    if (self::isError($eWAYResponse)) {
      $eWayTrxnError = $eWAYResponse->Error();
      CRM_Core_Error::debug_var('eWay Error', $eWayTrxnError, TRUE, TRUE);
      if (substr($eWayTrxnError, 0, 6) === 'Error:') {
        throw new PaymentProcessorException($eWayTrxnError, 9008);
      }
      $eWayErrorCode = substr($eWayTrxnError, 0, 2);
      $eWayErrorDesc = substr($eWayTrxnError, 3);

      throw new PaymentProcessorException('Error: [' . $eWayErrorCode . "] - " . $eWayErrorDesc . '.', 9008);
    }

    //=============
    // Success !
    //=============
    $beaglestatus = $eWAYResponse->BeagleScore();
    if (!empty($beaglestatus)) {
      $beaglestatus = ': ' . $beaglestatus;
    }
    $params['trxn_result_code'] = $eWAYResponse->Status() . $beaglestatus;
    $result['trxn_id'] = $eWAYResponse->TransactionNumber();
    $result = $this->setStatusPaymentCompleted($result);
    return $result;
  }

  /**
   * Checks the eWAY response status - returning a boolean false if status != 'true'.
   *
   * @param object $response
   *
   * @return bool
   */
  public function isError(&$response) {
    $status = $response->Status();

    if ((stripos($status, 'true')) === FALSE) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * This public function checks to see if we have the right processor config values set
   *
   * NOTE: Called by Events and Contribute to check config params are set prior to trying
   *       register any credit card details
   *
   * @return null|string
   *   returns string $errorMsg if any errors found - null if OK
   */
  public function checkConfig() {
    $errorMsg = [];

    if (empty($this->_paymentProcessor['user_name'])) {
      $errorMsg[] = ts('eWAY CustomerID is not set for this payment processor');
    }

    if (empty($this->_paymentProcessor['url_site'])) {
      $errorMsg[] = ts('eWAY Gateway URL is not set for this payment processor');
    }

    if (!empty($errorMsg)) {
      return implode('<p>', $errorMsg);
    }
    return NULL;
  }

}
