<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

require_once 'Google/library/googlecart.php';
require_once 'Google/library/googleitem.php';
require_once 'Google/library/googlesubscription.php';
require_once 'Google/library/googlerequest.php';

/**
 * Class CRM_Core_Payment_Google
 */
class CRM_Core_Payment_Google extends CRM_Core_Payment {

  /**
   * mode of operation: live or test
   *
   * @var object
   */
  protected $_mode = NULL;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   *
   * @param $paymentProcessor
   *
   * @return \CRM_Core_Payment_Google
   */
  function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('Google Checkout');
  }

  /**
   * singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   *
   * @param object $paymentProcessor
   *
   * @return object
   * @static
   */
  static function &singleton($mode, &$paymentProcessor) {
    $processorName = $paymentProcessor['name'];
    if (!isset(self::$_singleton[$processorName]) || self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_Google($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig() {
    $config = CRM_Core_Config::singleton();

    $error = array();

    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('User Name is not set in the Administer CiviCRM &raquo; System Settings &raquo; Payment Processors.');
    }

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('Password is not set in the Administer CiviCRM &raquo; System Settings &raquo; Payment Processors.');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  /**
   * This function collects all the information from a web/api form and invokes
   * the relevant payment processor specific functions to perform the transaction
   *
   * @param  array $params assoc array of input parameters for this transaction
   *
   * @return array the result in an nice formatted array (or an error object)
   * @abstract
   */
  function doDirectPayment(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  /**
   * Sets appropriate parameters for checking out to google
   *
   * @param array $params name value pair of contribution datat
   *
   * @param $component
   *
   * @return void
   * @access public
   */
  function doTransferCheckout(&$params, $component) {
    $component = strtolower($component);

    if (!empty($params['is_recur']) &&
      $params['contributionRecurID']
    ) {
      return $this->doRecurCheckout($params, $component);
    }

    //Create a new shopping cart object
    // Merchant ID
    $merchant_id = $this->_paymentProcessor['user_name'];
    // Merchant Key
    $merchant_key = $this->_paymentProcessor['password'];
    $server_type = ($this->_mode == 'test') ? 'sandbox' : '';

    $cart = new GoogleCart($merchant_id, $merchant_key, $server_type, $params['currencyID']);
    $item1 = new GoogleItem($params['item_name'], '', 1, $params['amount']);
    $cart->AddItem($item1);

    $this->submitPostParams($params, $component, $cart);
  }

  /**
   * @param $params
   * @param $component
   */
  function doRecurCheckout(&$params, $component) {
    $intervalUnit = CRM_Utils_Array::value('frequency_unit', $params);
    if ($intervalUnit == 'week') {
      $intervalUnit = 'WEEKLY';
    }
    elseif ($intervalUnit == 'year') {
      $intervalUnit = 'YEARLY';
    }
    elseif ($intervalUnit == 'day') {
      $intervalUnit = 'DAILY';
    }
    elseif ($intervalUnit == 'month') {
      $intervalUnit = 'MONTHLY';
    }

    // Merchant ID
    $merchant_id = $this->_paymentProcessor['user_name'];
    // Merchant Key
    $merchant_key = $this->_paymentProcessor['password'];
    $server_type = ($this->_mode == 'test') ? 'sandbox' : '';

    $itemName     = CRM_Utils_Array::value('item_name', $params);
    $description  = CRM_Utils_Array::value('description', $params);
    $amount       = CRM_Utils_Array::value('amount', $params);
    $installments = CRM_Utils_Array::value('installments', $params);

    $cart              = new GoogleCart($merchant_id, $merchant_key, $server_type, $params['currencyID']);
    $item              = new GoogleItem($itemName, $description, 1, $amount);
    $subscription_item = new GoogleSubscription("merchant", $intervalUnit, $amount, $installments);

    $item->SetSubscription($subscription_item);
    $cart->AddItem($item);

    $this->submitPostParams($params, $component, $cart);
  }

  /**
   * Builds appropriate parameters for checking out to google and submits the post params
   *
   * @param array  $params    name value pair of contribution data
   * @param string $component event/contribution
   * @param object $cart      object of googel cart
   *
   * @return void
   * @access public
   *
   */
  function submitPostParams($params, $component, $cart) {
    $url = rtrim($this->_paymentProcessor['url_site'], '/') . '/cws/v2/Merchant/' . $this->_paymentProcessor['user_name'] . '/checkout';

    if ($component == "event") {
      $privateData = "contactID={$params['contactID']},contributionID={$params['contributionID']},contributionTypeID={$params['contributionTypeID']},eventID={$params['eventID']},participantID={$params['participantID']},invoiceID={$params['invoiceID']}";
    }
    elseif ($component == "contribute") {
      $privateData = "contactID={$params['contactID']},contributionID={$params['contributionID']},contributionTypeID={$params['contributionTypeID']},invoiceID={$params['invoiceID']}";

      $contributionRecurID = CRM_Utils_Array::value('contributionRecurID', $params);
      if ($contributionRecurID) {
        $privateData .= ",contributionRecurID=$contributionRecurID";
      }

      $membershipID = CRM_Utils_Array::value('membershipID', $params);
      if ($membershipID) {
        $privateData .= ",membershipID=$membershipID";
      }

      $relatedContactID = CRM_Utils_Array::value('related_contact', $params);
      if ($relatedContactID) {
        $privateData .= ",relatedContactID=$relatedContactID";

        $onBehalfDupeAlert = CRM_Utils_Array::value('onbehalf_dupe_alert', $params);
        if ($onBehalfDupeAlert) {
          $privateData .= ",onBehalfDupeAlert=$onBehalfDupeAlert";
        }
      }
    }

    // Allow further manipulation of the arguments via custom hooks ..
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $privateData);

    $cart->SetMerchantPrivateData($privateData);

    if ($component == "event") {
      $returnURL = CRM_Utils_System::url('civicrm/event/register',
        "_qf_ThankYou_display=1&qfKey={$params['qfKey']}",
        TRUE, NULL, FALSE
      );
    }
    elseif ($component == "contribute") {
      $returnURL = CRM_Utils_System::url('civicrm/contribute/transact',
        "_qf_ThankYou_display=1&qfKey={$params['qfKey']}",
        TRUE, NULL, FALSE
      );
    }
    $cart->SetContinueShoppingUrl($returnURL);

    $cartVal = base64_encode($cart->GetXML());
    $signatureVal = base64_encode($cart->CalcHmacSha1($cart->GetXML()));

    $googleParams = array(
      'cart' => $cartVal,
      'signature' => $signatureVal,
    );

    require_once 'HTTP/Request.php';
    $params = array(
      'method' => HTTP_REQUEST_METHOD_POST,
      'allowRedirects' => FALSE,
    );
    $request = new HTTP_Request($url, $params);
    foreach ($googleParams as $key => $value) {
      $request->addPostData($key, $value);
    }

    $result = $request->sendRequest();

    if (PEAR::isError($result)) {
      CRM_Core_Error::fatal($result->getMessage());
    }

    if ($request->getResponseCode() != 302) {
      CRM_Core_Error::fatal(ts('Invalid response code received from Google Checkout: %1',
          array(1 => $request->getResponseCode())
        ));
    }
    CRM_Utils_System::redirect($request->getResponseHeader('location'));
    CRM_Utils_System::civiExit();
  }

  /**
   * hash_call: Function to perform the API call to PayPal using API signature
   * @paymentProcessor is the array of payment processor settings value.
   * @searchParamsnvpStr is the array of search params.
   * returns an associtive array containing the response from the server.
   */
  function invokeAPI($paymentProcessor, $searchParams) {
    $merchantID  = $paymentProcessor['user_name'];
    $merchantKey = $paymentProcessor['password'];
    $siteURL     = rtrim(str_replace('https://', '', $paymentProcessor['url_site']), '/');

    $url = "https://{$merchantID}:{$merchantKey}@{$siteURL}/api/checkout/v2/reports/Merchant/{$merchantID}";
    $xml = self::buildXMLQuery($searchParams);

    if (!function_exists('curl_init')) {
      CRM_Core_Error::fatal("curl functions NOT available.");
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);

    //turning off the server and peer verification(TrustManager Concept).
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'verifySSL'));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'verifySSL') ? 2 : 0);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);

    //setting the nvpreq as POST FIELD to curl
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

    //getting response from server
    $xmlResponse = curl_exec($ch);

    // strip slashes if needed
    if (get_magic_quotes_gpc()) {
      $xmlResponse = stripslashes($xmlResponse);
    }

    if (curl_errno($ch)) {
      $e = CRM_Core_Error::singleton();
      $e->push(curl_errno($ch),
        0, NULL,
        curl_error($ch)
      );
      return $e;
    }
    else {
      curl_close($ch);
    }

    return self::getArrayFromXML($xmlResponse);
  }

  /**
   * @param $searchParams
   *
   * @return string
   */
  static function buildXMLQuery($searchParams) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
<notification-history-request xmlns="http://checkout.google.com/schema/2">';

    if (array_key_exists('next-page-token', $searchParams)) {
      $xml .= '
<next-page-token>' . $searchParams['next-page-token'] . '</next-page-token>';
    }
    if (array_key_exists('start', $searchParams)) {
      $xml .= '
<start-time>' . $searchParams['start'] . '</start-time>
<end-time>' . $searchParams['end'] . '</end-time>';
    }
    if (array_key_exists('notification-types', $searchParams)) {
      $xml .= '
<notification-types>
<notification-type>' . implode($searchParams['notification-types'], '</notification-type>
<notification-type>') . '</notification-type>
</notification-types>';
    }
    if (array_key_exists('order-numbers', $searchParams)) {
      $xml .= '
<order-numbers>
<google-order-number>' . implode($searchParams['order-numbers'], '</google-order-number>
<google-order-number>') . '</google-order-number>
</order-numbers>';
    }
    $xml .= '
</notification-history-request>';

    return $xml;
  }

  /**
   * @param $xmlData
   *
   * @return array
   */
  static function getArrayFromXML($xmlData) {
    require_once 'Google/library/xml-processing/gc_xmlparser.php';
    $xmlParser = new gc_XmlParser($xmlData);
    $root      = $xmlParser->GetRoot();
    $data      = $xmlParser->GetData();

    return array($root, $data);
  }

  /**
   * @param null $errorCode
   * @param null $errorMessage
   *
   * @return object
   */
  function &error($errorCode = NULL, $errorMessage = NULL) {
    $e = &CRM_Core_Error::singleton();
    if ($errorCode) {
      $e->push($errorCode, 0, NULL, $errorMessage);
    }
    else {
      $e->push(9001, 0, NULL, 'Unknown System Error.');
    }
    return $e;
  }

  /**
   * @return string
   */
  function accountLoginURL() {
    return ($this->_mode == 'test') ? 'https://sandbox.google.com/checkout/sell' : 'https://checkout.google.com/';
  }

  /**
   * @param string $message
   * @param array $params
   *
   * @return bool|object
   */
  function cancelSubscription(&$message = '', $params = array(
    )) {
    $orderNo = CRM_Utils_Array::value('subscriptionId', $params);

    $merchant_id  = $this->_paymentProcessor['user_name'];
    $merchant_key = $this->_paymentProcessor['password'];
    $server_type  = ($this->_mode == 'test') ? 'sandbox' : '';

    $googleRequest = new GoogleRequest($merchant_id, $merchant_key, $server_type);
    $result        = $googleRequest->SendCancelItems($orderNo, array(), 'Cancelled by admin', '');
    $message       = "{$result[0]}: {$result[1]}";

    if ($result[0] != 200) {
      return self::error($result[0], $result[1]);
    }
    return TRUE;
  }
}

