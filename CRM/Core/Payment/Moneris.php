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
 * @author Alan Dixon
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Core_Payment_Moneris extends CRM_Core_Payment {
  # (not used, implicit in the API, might need to convert?)
  CONST CHARSET = 'UFT-8';

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
   * @return \CRM_Core_Payment_Moneris
   */
  function __construct($mode, &$paymentProcessor, &$paymentForm = NULL, $force = FALSE) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('Moneris');

    // require moneris supplied api library
    if ((include_once 'Services/mpgClasses.php') === FALSE) {
      CRM_Core_Error::fatal(ts('Please download and put the Moneris mpgClasses.php file in packages/Services directory to enable Moneris Support.'));
    }

    // get merchant data from config
    $config = CRM_Core_Config::singleton();
    // live or test
    $this->_profile['mode'] = $mode;
    $this->_profile['storeid'] = $this->_paymentProcessor['signature'];
    $this->_profile['apitoken'] = $this->_paymentProcessor['password'];
    $currencyID = $config->defaultCurrency;
    if ('CAD' != $currencyID) {
      return self::error('Invalid configuration:' . $currencyID . ', you must use currency $CAD with Moneris');
      // Configuration error: default currency must be CAD
    }
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
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_Moneris($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
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
    //make sure i've been called correctly ...
    if (!$this->_profile) {
      return self::error('Unexpected error, missing profile');
    }
    if ($params['currencyID'] != 'CAD') {
      return self::error('Invalid currency selection, must be $CAD');
    }
    /* unused params: cvv not yet implemented, payment action ingored (should test for 'Sale' value?)
        [cvv2] => 000
        [ip_address] => 192.168.0.103
        [payment_action] => Sale
        [contact_type] => Individual
        [geo_coord_id] => 1 */

    //this code based on Moneris example code #
    //create an mpgCustInfo object
    $mpgCustInfo = new mpgCustInfo();
    //call set methods of the mpgCustinfo object
    $mpgCustInfo->setEmail($params['email']);
    //get text representations of province/country to send to moneris for billing info

    $billing = array(
      'first_name' => $params['first_name'],
      'last_name' => $params['last_name'],
      'address' => $params['street_address'],
      'city' => $params['city'],
      'province' => $params['state_province'],
      'postal_code' => $params['postal_code'],
      'country' => $params['country'],
    );
    $mpgCustInfo->setBilling($billing);
    // set orderid as invoiceID to help match things up with Moneris later
    $my_orderid = $params['invoiceID'];
    $expiry_string = sprintf('%04d%02d', $params['year'], $params['month']);

    $txnArray = array(
      'type' => 'purchase',
      'order_id' => $my_orderid,
      'amount' => sprintf('%01.2f', $params['amount']),
      'pan' => $params['credit_card_number'],
      'expdate' => substr($expiry_string, 2, 4),
      'crypt_type' => '7',
      'cust_id' => $params['contactID'],
    );

    // Allow further manipulation of params via custom hooks
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $txnArray);

    //create a transaction object passing the hash created above
    $mpgTxn = new mpgTransaction($txnArray);

    //use the setCustInfo method of mpgTransaction object to
    //set the customer info (level 3 data) for this transaction
    $mpgTxn->setCustInfo($mpgCustInfo);
    // add a recurring payment if requested
    if ($params['is_recur'] && $params['installments'] > 1) {
      //Recur Variables
      $recurUnit     = $params['frequency_unit'];
      $recurInterval = $params['frequency_interval'];
      $next          = time();
      $day           = 60 * 60 * 24;
      switch ($recurUnit) {
        case 'day':
          $next += $recurInterval * $day;
          break;

        case 'week':
          $next += $recurInterval * $day * 7;
          break;

        case 'month':
          $date = getdate();
          $date['mon'] += $recurInterval;
          while ($date['mon'] > 12) {
            $date['mon'] -= 12;
            $date['year'] += 1;
          }
          $next = mktime($date['hours'], $date['minutes'], $date['seconds'], $date['mon'], $date['mday'], $date['year']);
          break;

        case 'year':
          $date = getdate();
          $date['year'] += 1;
          $next = mktime($date['hours'], $date['minutes'], $date['seconds'], $date['mon'], $date['mday'], $date['year']);
          break;

        default:
          die('Unexpected error!');
      }
      // next payment in moneris required format
      $startDate = date("Y/m/d", $next);
      $numRecurs = $params['installments'] - 1;
      //$startNow = 'true'; -- setting start now to false will mean the main transaction doesn't happen!
      $recurAmount = sprintf('%01.2f', $params['amount']);
      //Create an array with the recur variables
      // (day | week | month)
      $recurArray = array(
        'recur_unit' => $recurUnit,
        // yyyy/mm/dd
        'start_date' => $startDate,
        'num_recurs' => $numRecurs,
        'start_now' => 'true',
        'period' => $recurInterval,
        'recur_amount' => $recurAmount,
      );
      $mpgRecur = new mpgRecur($recurArray);
      // set the Recur Object to mpgRecur
      $mpgTxn->setRecur($mpgRecur);
    }
    //create a mpgRequest object passing the transaction object
    $mpgRequest = new mpgRequest($mpgTxn);

    // create mpgHttpsPost object which does an https post ##
    // [extra parameter added to library by AD]
    $isProduction = ($this->_profile['mode'] == 'live');
    $mpgHttpPost = new mpgHttpsPost($this->_profile['storeid'], $this->_profile['apitoken'], $mpgRequest, $isProduction);
    // get an mpgResponse object
    $mpgResponse = $mpgHttpPost->getMpgResponse();
    $params['trxn_result_code'] = $mpgResponse->getResponseCode();
    if (self::isError($mpgResponse)) {
      if ($params['trxn_result_code']) {
        return self::error($mpgResponse);
      }
      else {
        return self::error('No reply from server - check your settings &/or try again');
      }
    }
    /* Check for application errors */

    $result = self::checkResult($mpgResponse);
    if (is_a($result, 'CRM_Core_Error')) {
      return $result;
    }

    /* Success */

    $params['trxn_result_code'] = (integer) $mpgResponse->getResponseCode();
    // todo: above assignment seems to be ignored, not getting stored in the civicrm_financial_trxn table
    $params['trxn_id'] = $mpgResponse->getTxnNumber();
    $params['gross_amount'] = $mpgResponse->getTransAmount();
    return $params;
  }

  /**
   * @param $response
   *
   * @return bool
   */
  function isError(&$response) {
    $responseCode = $response->getResponseCode();
    if (is_null($responseCode)) {
      return TRUE;
    }
    if ('null' == $responseCode) {
      return TRUE;
    }
    if (($responseCode >= 0) && ($responseCode < 50)) {
      return FALSE;
    }
    return TRUE;
  }

  // ignore for now, more elaborate error handling later.
  /**
   * @param $response
   *
   * @return object
   */
  function &checkResult(&$response) {
    return $response;

    $errors = $response->getErrors();
    if (empty($errors)) {
      return $result;
    }

    $e = CRM_Core_Error::singleton();
    if (is_a($errors, 'ErrorType')) {
      $e->push($errors->getErrorCode(),
        0, NULL,
        $errors->getShortMessage() . ' ' . $errors->getLongMessage()
      );
    }
    else {
      foreach ($errors as $error) {
        $e->push($error->getErrorCode(),
          0, NULL,
          $error->getShortMessage() . ' ' . $error->getLongMessage()
        );
      }
    }
    return $e;
  }

  /**
   * @param null $error
   *
   * @return object
   */
  function &error($error = NULL) {
    $e = CRM_Core_Error::singleton();
    if (is_object($error)) {
      $e->push($error->getResponseCode(),
        0, NULL,
        $error->getMessage()
      );
    }
    elseif (is_string($error)) {
      $e->push(9002,
        0, NULL,
        $error
      );
    }
    else {
      $e->push(9001, 0, NULL, "Unknown System Error.");
    }
    return $e;
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig() {
    $error = array();

    if (empty($this->_paymentProcessor['signature'])) {
      $error[] = ts('Store ID is not set in the Administer CiviCRM &raquo; System Settings &raquo; Payment Processors.');
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
}

