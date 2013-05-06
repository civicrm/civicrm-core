<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.3                                                |
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
class CRM_Core_Payment_PaymentExpress extends CRM_Core_Payment {
  CONST CHARSET = 'iso-8859-1';

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
   * @return void
   */
  function __construct($mode, &$paymentProcessor) {

    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('DPS Payment Express');
  }

  /**
   * singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return object
   * @static
   *
   */
  static function &singleton($mode, &$paymentProcessor) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_PaymentExpress($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  function checkConfig() {
    $config = CRM_Core_Config::singleton();

    $error = array();

    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('UserID is not set in the Administer &raquo; System Settings &raquo; Payment Processors');
    }

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('pxAccess / pxPay Key is not set in the Administer &raquo; System Settings &raquo; Payment Processors');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  function setExpressCheckOut(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function getExpressCheckoutDetails($token) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function doExpressCheckout(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function doDirectPayment(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  /**
   * Main transaction function
   *
   * @param array $params  name value pair of contribution data
   *
   * @return void
   * @access public
   *
   */
  function doTransferCheckout(&$params, $component) {
    $component = strtolower($component);
    $config = CRM_Core_Config::singleton();
    if ($component != 'contribute' && $component != 'event') {
      CRM_Core_Error::fatal(ts('Component is invalid'));
    }

    $url = $config->userFrameworkResourceURL . "extern/pxIPN.php";

    if ($component == 'event') {
      $cancelURL = CRM_Utils_System::url('civicrm/event/register',
        "_qf_Confirm_display=true&qfKey={$params['qfKey']}",
        FALSE, NULL, FALSE
      );
    }
    elseif ($component == 'contribute') {
      $cancelURL = CRM_Utils_System::url('civicrm/contribute/transact',
        "_qf_Confirm_display=true&qfKey={$params['qfKey']}",
        FALSE, NULL, FALSE
      );
    }


    /*
         * Build the private data string to pass to DPS, which they will give back to us with the
         *
         * transaction result.  We are building this as a comma-separated list so as to avoid long URLs.
         *
         * Parameters passed: a=contactID, b=contributionID,c=contributionTypeID,d=invoiceID,e=membershipID,f=participantID,g=eventID
         */

    $privateData = "a={$params['contactID']},b={$params['contributionID']},c={$params['contributionTypeID']},d={$params['invoiceID']}";

    if ($component == 'event') {
    $merchantRef = substr($params['contactID'] . "-" . $params['contributionID'] . " " . substr($params['description'], 27, 20), 0, 24);
      $privateData .= ",f={$params['participantID']},g={$params['eventID']}";
    }
    elseif ($component == 'contribute') {
      $membershipID = CRM_Utils_Array::value('membershipID', $params);
      if ($membershipID) {
        $privateData .= ",e=$membershipID";
      }
      $merchantRef = substr($params['contactID'] . "-" . $params['contributionID'] . " " . substr($params['description'], 20, 20), 0, 24);

    }

    $dpsParams = array(
      'AmountInput' => str_replace(",", "", number_format($params['amount'], 2)),
      'CurrencyInput' => $params['currencyID'],
      'MerchantReference' => $merchantRef,
      'TxnData1' => $params['qfKey'],
      'TxnData2' => $privateData,
      'TxnData3' => $component . ",".$this->_paymentProcessor['id'],
      'TxnType' => 'Purchase',
      // Leave this empty for now, causes an error with DPS if we populate it
      'TxnId' => '',
      'UrlFail' => $url,
      'UrlSuccess' => $url,
    );
    // Allow further manipulation of params via custom hooks
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $dpsParams);

    /*
         *  determine whether method is pxaccess or pxpay by whether signature (mac key) is defined
         */


    if (empty($this->_paymentProcessor['signature'])) {
      /*
       * Processor is pxpay
       *
       * This contains the XML/Curl functions we'll need to generate the XML request
       */

      $dpsParams['PxPayUserId'] = $this->_paymentProcessor['user_name'];
      $dpsParams['PxPayKey'] = $this->_paymentProcessor['password'];
      // Build a valid XML string to pass to DPS
      $generateRequest = CRM_Core_Payment_PaymentExpressUtils::_valueXml($dpsParams);

      $generateRequest = CRM_Core_Payment_PaymentExpressUtils::_valueXml('GenerateRequest', $generateRequest);
      // Get the special validated URL back from DPS by sending them the XML we've generated
      $curl = CRM_Core_Payment_PaymentExpressUtils::_initCURL($generateRequest, $this->_paymentProcessor['url_site']);
      $success = FALSE;

      if ($response = curl_exec($curl)) {
        curl_close($curl);
        $valid = CRM_Core_Payment_PaymentExpressUtils::_xmlAttribute($response, 'valid');
        if (1 == $valid) {
          // the request was validated, so we'll get the URL and redirect to it
          $uri = CRM_Core_Payment_PaymentExpressUtils::_xmlElement($response, 'URI');
          CRM_Utils_System::redirect($uri);
        }
        else {
          // redisplay confirmation page
          CRM_Utils_System::redirect($cancelURL);
        }
      }
      else {
        // calling DPS failed
        CRM_Core_Error::fatal(ts('Unable to establish connection to the payment gateway.'));
      }
    }
    else {
      $processortype = "pxaccess";
      require_once ('PaymentExpress/pxaccess.inc.php');
      // URL
      $PxAccess_Url = $this->_paymentProcessor['url_site'];
      // User ID
      $PxAccess_Userid = $this->_paymentProcessor['user_name'];
      // Your DES Key from DPS
      $PxAccess_Key = $this->_paymentProcessor['password'];
      // Your MAC key from DPS
      $Mac_Key = $this->_paymentProcessor['signature'];

      $pxaccess = new PxAccess($PxAccess_Url, $PxAccess_Userid, $PxAccess_Key, $Mac_Key);
      $request = new PxPayRequest();
      $request->setAmountInput($dpsParams['AmountInput']);
      $request->setTxnData1($dpsParams['TxnData1']);
      $request->setTxnData2($dpsParams['TxnData2']);
      $request->setTxnData3($dpsParams['TxnData3']);
      $request->setTxnType($dpsParams['TxnType']);
      $request->setInputCurrency($dpsParams['InputCurrency']);
      $request->setMerchantReference($dpsParams['MerchantReference']);
      $request->setUrlFail($dpsParams['UrlFail']);
      $request->setUrlSuccess($dpsParams['UrlSuccess']);
      $request_string = $pxaccess->makeRequest($request);
      CRM_Utils_System::redirect($request_string);
    }
  }
}

