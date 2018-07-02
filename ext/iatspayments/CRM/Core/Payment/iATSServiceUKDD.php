<?php

/**
 * @file Copyright iATS Payments (c) 2014.
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
 * for UK Direct Debit Recurring contributions ONLY
 */

/**
 *
 */
class CRM_Core_Payment_iATSServiceUKDD extends CRM_Core_Payment {

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
   *
   * @return void
   */
  public function __construct($mode, &$paymentProcessor) {
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('iATS Payments UK Direct Debit');

    // Get merchant data from config.
    $config = CRM_Core_Config::singleton();
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
      self::$_singleton[$processorName] = new CRM_Core_Payment_iATSServiceUKDD($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * Function checkParams.
   */
  public function checkParams($params) {
    if (!$this->_profile) {
      return self::error('Unexpected error, missing profile');
    }
    $isRecur = CRM_Utils_Array::value('is_recur', $params) && $params['contributionRecurID'];
    if (!$isRecur) {
      return self::error('Not a recurring contribution: you can only use UK Direct Debit with a recurring contribution.');
    }
    if ('GBP' != $params['currencyID']) {
      return self::error(ts('Invalid currency %1, must by GBP', array(1 => $params['currencyID'])));
    }
    if (empty($params['installments'])) {
      return self::error(ts('You must specify the number of installments, open-ended contributions are not allowed.'));
    }
    elseif (1 >= $params['installments']) {
      return self::error(ts('You must specify a number of installments greater than 1.'));
    }
  }

  /**
   *
   */
  public function getSchedule($params) {
    // Convert params recurring information into iATS equivalents.
    $scheduleType = NULL;
    $paymentsRecur = $params['installments'] - 1;
    // IATS requires begin and end date, calculated here
    // to be converted to date format later
    // begin date has to be more than 12 days from now, not checked here.
    $beginTime = strtotime($beginDate = $params['payer_validate_start_date']);
    $date = getdate($beginTime);
    $interval = $params['frequency_interval'] ? $params['frequency_interval'] : 1;
    switch ($params['frequency_unit']) {
      case 'week':
        if (1 != $interval) {
          return self::error(ts('You can only choose each week on a weekly schedule.'));
        }
        $scheduleType = 'Weekly';
        $scheduleDate = $date['wday'] + 1;
        $endTime      = $beginTime + ($paymentsRecur * 7 * 24 * 60 * 60);
        break;

      case 'month':
        $scheduleType = 'Monthly';
        $scheduleDate = $date['mday'];
        if (3 == $interval) {
          $scheduleType = 'Quarterly';
          $scheduleDate = '';
        }
        elseif (1 != $interval) {
          return self::error(ts('You can only choose monthly or every three months (quarterly) for a monthly schedule.'));
        }
        $date['mon'] += ($interval * $paymentsRecur);
        while ($date['mon'] > 12) {
          $date['mon'] -= 12;
          $date['year'] += 1;
        }
        $endTime = mktime($date['hours'], $date['minutes'], $date['seconds'], $date['mon'], $date['mday'], $date['year']);
        break;

      case 'year':
        if (1 != $interval) {
          return self::error(ts('You can only choose each year for a yearly schedule.'));
        }
        $scheduleType = 'Yearly';
        $scheduleDate = '';
        $date['year'] += $paymentsRecur;
        $endTime = mktime($date['hours'], $date['minutes'], $date['seconds'], $date['mon'], $date['mday'], $date['year']);
        break;

      default:
        return self::error(ts('Invalid frequency unit: %1', array(1 => $params['frequency_unit'])));
      break;

    }
    $endDate = date('c', $endTime);
    $beginDate = date('c', $beginTime);
    return array('scheduleType' => $scheduleType, 'scheduleDate' => $scheduleDate, 'endDate' => $endDate, 'beginDate' => $beginDate);
  }

  /**
   *
   */
  public function doDirectPayment(&$params) {
    $error = $this->checkParams($params);
    if (!empty($error)) {
      return $error;
    }
    // $params['start_date'] = $params['receive_date'];
    // use the iATSService object for interacting with iATS.
    require_once "CRM/iATS/iATSService.php";
    $iats = new iATS_Service_Request(array('type' => 'customer', 'method' => 'direct_debit_create_acheft_customer_code', 'iats_domain' => $this->_profile['iats_domain'], 'currencyID' => $params['currencyID']));
    $schedule = $this->getSchedule($params);
    // Assume an error object to return.
    if (!is_array($schedule)) {
      return $schedule;
    }
    $request = array_merge($this->convertParamsCreateCustomerCode($params), $schedule);
    $request['customerIPAddress'] = (function_exists('ip_address') ? ip_address() : $_SERVER['REMOTE_ADDR']);
    $request['customerCode'] = '';
    $request['accountType'] = 'CHECKING';
    $credentials = array(
      'agentCode' => $this->_paymentProcessor['user_name'],
      'password'  => $this->_paymentProcessor['password'],
    );
    // Get the API endpoint URL for the method's transaction mode.
    // TODO: enable override of the default url in the request object
    // $url = $this->_paymentProcessor['url_site'];.
    // Make the soap request.
    $response = $iats->request($credentials, $request);
    // Process the soap response into a readable result.
    $result = $iats->result($response);
    // drupal_set_message('<pre>'.print_r($result,TRUE).'</pre>');.
    if ($result['status']) {
      // Always pending.
      $params['contribution_status_id'] = 2;
      // For future versions, the proper key.
      $params['payment_status_id'] = 2;
      $params['trxn_id'] = trim($result['remote_id']) . ':' . time();
      $params['gross_amount'] = $params['amount'];
      // Save the client info in my custom table
      // Allow further manipulation of the arguments via custom hooks,.
      $customer_code = $result['CUSTOMERCODE'];
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
        3 => array('', 'String'),
        4 => array($params['contactID'], 'Integer'),
        5 => array($email, 'String'),
        6 => array($params['contributionRecurID'], 'Integer'),
      );
      // drupal_set_message('<pre>'.print_r($query_params,TRUE).'</pre>');.
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_iats_customer_codes
        (customer_code, ip, expiry, cid, email, recur_id) VALUES (%1, %2, %3, %4, %5, %6)", $query_params);
      // Save their payer validation data in civicrm_iats_ukdd_validate.
      $query_params = array(
        1 => array($customer_code, 'String'),
        2 => array($params['payer_validate_reference'], 'String'),
        3 => array($params['contactID'], 'Integer'),
        4 => array($params['contributionRecurID'], 'Integer'),
        5 => array($params['payer_validate_declaration'], 'Integer'),
        6 => array(date('c'), 'String'),
      );
      // drupal_set_message('<pre>'.print_r($query_params,TRUE).'</pre>');.
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_iats_ukdd_validate
        (customer_code, acheft_reference_num, cid, recur_id, validated, validated_datetime) VALUES (%1, %2, %3, %4, %5, %6)", $query_params);
      // Set the status of the initial contribution to pending (currently is redundant), and the date to what I'm asking iATS for.
      $params['contribution_status_id'] = 2;
      $params['start_date'] = $params['payer_validate_start_date'];
      // Optimistically set this date, even though CiviCRM will likely not do anything with it yet - I'll change it with my pre hook in the meanwhile
      // $params['receive_date'] = strtotime($params['payer_validate_start_date']);
      // also set next_sched_contribution, though it won't be used.
      $params['next_sched_contribution'] = strtotime($params['payer_validate_start_date'] . ' + ' . $params['frequency_interval'] . ' ' . $params['frequency_unit']);
      return $params;
    }
    else {
      return self::error($result['reasonMessage']);
    }
  }

  /**
   * TODO: requires custom link
   * function changeSubscriptionAmount(&$message = '', $params = array()) {
   * $userAlert = ts('You have updated the amount of this recurring contribution.');
   * CRM_Core_Session::setStatus($userAlert, ts('Warning'), 'alert');
   * return TRUE;
   * } .
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
    if (empty($this->_paymentProcessor['signature'])) {
      $error[] = ts('Service User Number (SUN) is not set in the Administer CiviCRM &raquo; System Settings &raquo; Payment Processors.');
    }
    $iats_domain = parse_url($this->_paymentProcessor['url_site'], PHP_URL_HOST);
    if ('www.uk.iatspayments.com' != $iats_domain) {
      $error[] = ts('You can only use this payment processor with a UK iATS account');
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
  public function convertParamsCreateCustomerCode($params) {
    $request = array();
    $convert = array(
      'firstName' => 'billing_first_name',
      'lastName' => 'billing_last_name',
      'address' => 'street_address',
      'city' => 'city',
      'state' => 'state_province',
      'zipCode' => 'postal_code',
      'country' => 'country',
      'ACHEFTReferenceNum' => 'payer_validate_reference',
      'accountCustomerName' => 'account_holder',
      'email' => 'email',
      'recurring' => 'is_recur',
      'amount' => 'amount',
    );

    foreach ($convert as $r => $p) {
      if (isset($params[$p])) {
        $request[$r] = $params[$p];
      }
    }
    // Account custom name is first name + last name, truncated to a maximum of 30 chars.
    $request['accountNum'] = trim($params['bank_identification_number']) . trim($params['bank_account_number']);
    return $request;
  }

}
