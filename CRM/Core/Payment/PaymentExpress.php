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
 * PxPay Functionality Copyright (C) 2008 Lucas Baker, Logistic Information Systems Limited (Logis)
 * PxAccess Functionality Copyright (C) 2008 Eileen McNaughton
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * Grateful acknowledgements go to Donald Lobo for invaluable assistance
 * in creating this payment processor module
 */

/**
 * Class CRM_Core_Payment_PaymentExpress
 */
class CRM_Core_Payment_PaymentExpress extends CRM_Core_Payment {
  const CHARSET = 'iso-8859-1';

  protected $_mode = NULL;

  /**
   * Constructor.
   *
   * @param string $mode
   *   The mode of operation: live or test.
   *
   * @param $paymentProcessor
   *
   * @return \CRM_Core_Payment_PaymentExpress
   */
  public function __construct($mode, &$paymentProcessor) {

    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
  }

  /**
   * This function checks to see if we have the right config values.
   *
   * @internal param string $mode the mode we are operating in (live or test)
   *
   * @return string
   *   the error message if any
   */
  public function checkConfig() {
    $config = CRM_Core_Config::singleton();

    $error = [];

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

  /**
   * This function collects all the information from a web/api form and invokes
   * the relevant payment processor specific functions to perform the transaction
   *
   * @param array $params
   *   Assoc array of input parameters for this transaction.
   */
  public function doDirectPayment(&$params) {
    throw new CRM_Core_Exception(ts('This function is not implemented'));
  }

  /**
   * Main transaction function.
   *
   * @param array $params
   *   Name value pair of contribution data.
   *
   * @param $component
   */
  public function doTransferCheckout(&$params, $component) {
    // This is broken - in 2015 this commit broke it... https://github.com/civicrm/civicrm-core/commit/204c86d59f0cfc4c4d917cc245fb41633d36916e#diff-b00e65c9829c27da8b34e35f2e64d9b6L114
    $component = strtolower($component);
    $config = CRM_Core_Config::singleton();
    if ($component != 'contribute' && $component != 'event') {
      throw new CRM_Core_Exception(ts('Component is invalid'));
    }

    $url = CRM_Utils_System::externUrl('extern/pxIPN');

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
      $membershipID = $params['membershipID'] ?? NULL;
      if ($membershipID) {
        $privateData .= ",e=$membershipID";
      }
      $merchantRef = substr($params['contactID'] . "-" . $params['contributionID'] . " " . substr($params['description'], 20, 20), 0, 24);

    }

    $dpsParams = [
      'AmountInput' => str_replace(",", "", number_format($params['amount'], 2)),
      'CurrencyInput' => $params['currencyID'],
      'MerchantReference' => $merchantRef,
      'TxnData1' => $params['qfKey'],
      'TxnData2' => $privateData,
      'TxnData3' => $component . "," . $this->_paymentProcessor['id'],
      'TxnType' => 'Purchase',
      // Leave this empty for now, causes an error with DPS if we populate it
      'TxnId' => '',
      'UrlFail' => $url,
      'UrlSuccess' => $url,
    ];
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
        throw new CRM_Core_Exception(ts('Unable to establish connection to the payment gateway.'));
      }
    }
    else {
      $processortype = "pxaccess";
      require_once 'PaymentExpress/pxaccess.inc.php';
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
