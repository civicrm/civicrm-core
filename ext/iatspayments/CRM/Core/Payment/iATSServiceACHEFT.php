<?php

/**
 * @file
 * Copyright iATS Payments (c) 2014.
 * @author Alan Dixon
 *
 * This file is a part of CiviCRM published extension.
 *
 * This extension is free software; you can copy, modify, and distribute it
 * under the terms of the GNU Affero General Public License
 * Version 3, 19 November 2007.
 *
 * It is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License with this program; if not, see http://www.gnu.org/licenses/
 *
 * This code provides glue between CiviCRM payment model and the iATS Payment model encapsulated in the iATS_Service_Request object
 */

/**
 *
 */
class CRM_Core_Payment_iATSServiceACHEFT extends CRM_Core_Payment_iATSService {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable.
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * Constructor.
   *
   * @param string $mode
   *   the mode of operation: live or test.
   * @param array $paymentProcessor
   */
  public function __construct($mode, &$paymentProcessor) {
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('iATS Payments ACHEFT');

    // Live or test.
    $this->_profile['mode'] = $mode;
    // We only use the domain of the configured url, which is different for NA vs. UK.
    $this->_profile['iats_domain'] = parse_url($this->_paymentProcessor['url_site'], PHP_URL_HOST);
  }

  /**
   *
   */
  static public function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL, $force = FALSE) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_iATSServiceACHEFT($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   *
   */
  public function doDirectPayment(&$params) {

    if (!$this->_profile) {
      return self::error('Unexpected error, missing profile');
    }
    // Use the iATSService object for interacting with iATS, mostly the same for recurring contributions.
    require_once "CRM/iATS/iATSService.php";
    // We handle both one-time and recurring ACH/EFT
    $isRecur = CRM_Utils_Array::value('is_recur', $params) && $params['contributionRecurID'];
    $methodType = $isRecur ? 'customer' : 'process';
    $method = $isRecur ? 'create_acheft_customer_code' : 'acheft';
    $iats = new iATS_Service_Request(array('type' => $methodType, 'method' => $method, 'iats_domain' => $this->_profile['iats_domain'], 'currencyID' => $params['currencyID']));
    $request = $this->convertParams($params, $method);
    $request['customerIPAddress'] = (function_exists('ip_address') ? ip_address() : $_SERVER['REMOTE_ADDR']);
    $credentials = array(
      'agentCode' => $this->_paymentProcessor['user_name'],
      'password'  => $this->_paymentProcessor['password'],
    );
    // Make the soap request.
    $response = $iats->request($credentials, $request);
    if (!$isRecur) {
      // Process the soap response into a readable result, logging any transaction.
      $result = $iats->result($response);
      if ($result['status']) {
        // Always set pending status.
        $params['contribution_status_id'] = 2;
        // For future versions, the proper key.
        $params['payment_status_id'] = 2;
        $params['trxn_id'] = trim($result['remote_id']) . ':' . time();
        $params['gross_amount'] = $params['amount'];
        // Core assumes that a pending result will have no transaction id, but we have a useful one.
        if (!empty($params['contributionID'])) {
          $contribution_update = array('id' => $params['contributionID'], 'trxn_id' => $params['trxn_id']);
          try {
            $result = civicrm_api3('Contribution', 'create', $contribution_update);
          }
          catch (CiviCRM_API3_Exception $e) {
            // Not a critical error, just log and continue.
            $error = $e->getMessage();
            Civi::log()->info('Unexpected error adding the trxn_id for contribution id {id}: {error}', array('id' => $recur_id, 'error' => $error));
          }
        }
        return $params;
      }
      else {
        return self::error($result['reasonMessage']);
      }
    }
    else {
      // Save the client info in my custom table
      $customer = $iats->result($response);
      if (!$customer['status']) {
        return self::error($customer['reasonMessage']);
      }
      else {
        $processresult = $response->PROCESSRESULT;
        $customer_code = (string) $processresult->CUSTOMERCODE;
        // $exp = sprintf('%02d%02d', ($params['year'] % 100), $params['month']);.
        $exp = '0000';
        $email = '';
        if (isset($params['email'])) {
          $email = $params['email'];
        }
        elseif (isset($params['email-5'])) {
          $email = $params['email-5'];
        }
        elseif (isset($params['email-Primary'])) {
          $email = $params['email-Primary'];
        }
        $query_params = array(
          1 => array($customer_code, 'String'),
          2 => array($request['customerIPAddress'], 'String'),
          3 => array($exp, 'String'),
          4 => array($params['contactID'], 'Integer'),
          5 => array($email, 'String'),
          6 => array($params['contributionRecurID'], 'Integer'),
        );
        CRM_Core_DAO::executeQuery("INSERT INTO civicrm_iats_customer_codes
          (customer_code, ip, expiry, cid, email, recur_id) VALUES (%1, %2, %3, %4, %5, %6)", $query_params);
        $allow_days = $this->getSettings('days');
        // Also test for a specific recieve date request that is not today.
        $receive_date_request = CRM_Utils_Array::value('receive_date', $params);
        $today = date('Ymd');
        // If the receive_date is set to sometime today, unset it.
        if (!empty($receive_date_request) && 0 === strpos($receive_date_request, $today)) {
          unset($receive_date_request);
        }
        // Normally, run the (first) transaction immediately, unless the admin setting is in force or a specific request is being made.
        if (max($allow_days) <= 0 && empty($receive_date_request)) {
          $iats = new iATS_Service_Request(array('type' => 'process', 'method' => 'acheft_with_customer_code', 'iats_domain' => $this->_profile['iats_domain'], 'currencyID' => $params['currencyID']));
          $request = array('invoiceNum' => $params['invoiceID']);
          $request['total'] = sprintf('%01.2f', CRM_Utils_Rule::cleanMoney($params['amount']));
          $request['customerCode'] = $customer_code;
          $request['customerIPAddress'] = (function_exists('ip_address') ? ip_address() : $_SERVER['REMOTE_ADDR']);
          $response = $iats->request($credentials, $request);
          $result = $iats->result($response);
          if ($result['status']) {
            // Add a time string to iATS short authentication string to ensure uniqueness and provide helpful referencing.
            $update = array(
              'trxn_id' => trim($result['remote_id']) . ':' . time(),
              'gross_amount' => $params['amount'],
              'payment_status_id' => 2
            );
            // Setting the next_sched_contribution_date param setting is not doing anything, commented out.
            $this->setRecurReturnParams($params, $update);
            // Core assumes that a pending result will have no transaction id, but we have a useful one.
            if (!empty($params['contributionID'])) {
              $contribution_update = array('id' => $params['contributionID'], 'trxn_id' => $update['trxn_id']);
              try {
                $result = civicrm_api3('Contribution', 'create', $contribution_update);
              }
              catch (CiviCRM_API3_Exception $e) {
                // Not a critical error, just log and continue.
                $error = $e->getMessage();
                Civi::log()->info('Unexpected error adding the trxn_id for contribution id {id}: {error}', array('id' => $recur_id, 'error' => $error));
              }
            }
            return $params;
          }
          else {
            return self::error($result['reasonMessage']);
          }
        }
        // Otherwise, I have a schedule to adhere to.
        else {    
          // Note that the admin general setting restricting allowable days may update a specific request.
          $receive_timestamp = empty($receive_date_request) ? time() : strtotime($receive_date_request);
          $next_sched_contribution_timestamp = (max($allow_days) > 0) ? _iats_contributionrecur_next($receive_timestamp, $allow_days) 
            : $receive_timestamp;
          // set the receieve time to 3:00 am for a better admin experience
          $update = array(
            'payment_status_id' => 2,
            'receive_date' => date('Ymd', $next_sched_contribution_timestamp) . '030000',
          );
          $this->setRecurReturnParams($params, $update);
          return $params;
        }
      }
      return $params;
    }
  }

  /**
   *
   */
  public function changeSubscriptionAmount(&$message = '', $params = array()) {
    $userAlert = ts('You have updated the amount of this recurring contribution.');
    CRM_Core_Session::setStatus($userAlert, ts('Warning'), 'alert');
    return TRUE;
  }

  /**
   *
   */
  public function cancelSubscription(&$message = '', $params = array()) {
    $userAlert = ts('You have cancelled this recurring contribution.');
    CRM_Core_Session::setStatus($userAlert, ts('Warning'), 'alert');
    return TRUE;
  }

  /**
   *
   */
  public function &error($error = NULL) {
    $e = CRM_Core_Error::singleton();
    if (is_object($error)) {
      $e->push($error->getResponseCode(),
        0, NULL,
        $error->getMessage()
      );
    }
    elseif ($error && is_numeric($error)) {
      $e->push($error,
        0, NULL,
        $this->errorString($error)
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
   * Are back office payments supported.
   *
   * @return bool
   */
  protected function supportsBackOffice() {
    return TRUE;
  }
    
 /**
   * This function checks to see if we have the right config values.
   *
   * @param string $mode
   *   the mode we are operating in (live or test)
   *
   * @return string the error message if any
   *
   * @public
   */
  public function checkConfig() {
    $error = array();

    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('Agent Code is not set in the Administer CiviCRM &raquo; System Settings &raquo; Payment Processors.');
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
   * Convert the values in the civicrm params to the request array with keys as expected by iATS.
   */
  public function convertParams($params, $method) {
    $request = array();
    $convert = array(
      'firstName' => 'billing_first_name',
      'lastName' => 'billing_last_name',
      'address' => 'street_address',
      'city' => 'city',
      'state' => 'state_province',
      'zipCode' => 'postal_code',
      'country' => 'country',
      'invoiceNum' => 'invoiceID',
    /*  'accountNum' => 'bank_account_number', */
      'accountType' => 'bank_account_type',
    );

    foreach ($convert as $r => $p) {
      if (isset($params[$p])) {
        $request[$r] = $params[$p];
      }
    }
    $request['total'] = sprintf('%01.2f', CRM_Utils_Rule::cleanMoney($params['amount']));
    // Place for ugly hacks.
    switch ($method) {
      case 'acheft':
      case 'create_acheft_customer_code':
      case 'acheft_create_customer_code':
        // Add bank number + transit to account number
        // TODO: verification?
        $request['accountNum'] = preg_replace('/^0-9]/', '', $params['bank_identification_number'] . $params['bank_account_number']);
        break;
    }
    return $request;
  }

}
