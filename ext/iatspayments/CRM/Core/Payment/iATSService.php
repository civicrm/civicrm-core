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
 */

/**
 *
 */
class CRM_Core_Payment_iATSService extends CRM_Core_Payment {

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
   * @param array $paymentProcessor
   */
  public function __construct($mode, &$paymentProcessor, &$paymentForm = NULL, $force = FALSE) {
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('iATS Payments');

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
      self::$_singleton[$processorName] = new CRM_Core_Payment_iATSService($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * Get the iATS configuration values or value.
   *
   * Mangle the days settings to make it easier to test if it is set.
   */
  protected function getSettings($key = '') {
    static $settings = array();
    if (empty($settings)) {
      try {
        $settings = civicrm_api3('Setting', 'getvalue', array('name' => 'iats_settings'));
        if (empty($settings['days'])) {
          $settings['days'] = array('-1');
        }
      }
      catch (CiviCRM_API3_Exception $e) {
        // Assume no settings exist, use safest fallback.
        $settings = array('days' => array('-1'));
      }
    }
    return (empty($key) ? $settings : (empty($settings[$key]) ? '' : $settings[$key]));
  }

  /**
   * Override the default way of testing if a method is supported to enable admin configuration of certain
   * functions.
   * Where certain functions currently only means updateSubscriptionBillingInfo, which we'll allow for credit cards.
   *
   * Core says this method is now deprecated, so I might need to change this in the future, but this is how it is used now.
   */
  public function isSupported($method) {
    switch($method) {
      case 'updateSubscriptionBillingInfo':
        if ('CRM_Core_Payment_iATSServiceACHEFT' == CRM_Utils_System::getClassName($this)) {
          return FALSE;
        }
        elseif (!CRM_Core_Permission::check('access CiviContribution')) {
          // disable self-service update of billing info if the admin has not allowed it
          if (FALSE == $this->getSettings('enable_update_subscription_billing_info')) {
            return FALSE;
          }
        }
        break;
    }
    // this is the default method
    return method_exists(CRM_Utils_System::getClassName($this), $method);
  }

  /**
   * The first payment date is configurable when setting up back office recurring payments.
   * For iATSPayments, this is also true for front-end recurring payments.
   *
   * @return bool
   */
  public function supportsFutureRecurStartDate() {
    return TRUE;
  } 

  /**
   *
   */
  public function doDirectPayment(&$params) {

    if (!$this->_profile) {
      return self::error('Unexpected error, missing profile');
    }
    // Use the iATSService object for interacting with iATS. Recurring contributions go through a more complex process.
    require_once "CRM/iATS/iATSService.php";
    $isRecur = CRM_Utils_Array::value('is_recur', $params) && $params['contributionRecurID'];
    $methodType = $isRecur ? 'customer' : 'process';
    $method = $isRecur ? 'create_credit_card_customer' : 'cc';
    $iats = new iATS_Service_Request(array('type' => $methodType, 'method' => $method, 'iats_domain' => $this->_profile['iats_domain'], 'currencyID' => $params['currencyID']));
    $request = $this->convertParams($params, $method);
    $request['customerIPAddress'] = (function_exists('ip_address') ? ip_address() : $_SERVER['REMOTE_ADDR']);
    $credentials = array(
      'agentCode' => $this->_paymentProcessor['user_name'],
      'password'  => $this->_paymentProcessor['password'],
    );
    // Get the API endpoint URL for the method's transaction mode.
    // TODO: enable override of the default url in the request object
    // $url = $this->_paymentProcessor['url_site'];.
    // Make the soap request.
    $response = $iats->request($credentials, $request);
    if (!$isRecur) {
      // Process the soap response into a readable result, logging any credit card transactions.
      $result = $iats->result($response);
      if ($result['status']) {
        // Success.
        $params['contribution_status_id'] = 1;
        // For versions >= 4.6.6, the proper key.
        $params['payment_status_id'] = 1;
        $params['trxn_id'] = trim($result['remote_id']) . ':' . time();
        $params['gross_amount'] = $params['amount'];
        return $params;
      }
      else {
        return self::error($result['reasonMessage']);
      }
    }
    else {
      // Save the client info in my custom table, then (maybe) run the transaction.
      $customer = $iats->result($response, FALSE);
      // print_r($customer);
      if ($customer['status']) {
        $processresult = $response->PROCESSRESULT;
        $customer_code = (string) $processresult->CUSTOMERCODE;
        $exp = sprintf('%02d%02d', ($params['year'] % 100), $params['month']);
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
        // Test for admin setting that limits allowable transaction days
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
          $iats = new iATS_Service_Request(array('type' => 'process', 'method' => 'cc_with_customer_code', 'iats_domain' => $this->_profile['iats_domain'], 'currencyID' => $params['currencyID']));
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
              'payment_status_id' => '1',
            );
            // Setting the next_sched_contribution_date param doesn't do anything, commented out, work around in setRecurReturnParams
            $params = $this->setRecurReturnParams($params, $update);
            return $params;
          }
          else {
            return self::error($result['reasonMessage']);
          }
        }
        // I've got a schedule to adhere to!
        else {
          // Note that the admin general setting restricting allowable days will overwrite any specific request.
          $next_sched_contribution_timestamp = (max($allow_days) > 0) ? _iats_contributionrecur_next(time(), $allow_days) 
            : strtotime($params['receive_date']);
          // set the receieve time to 3:00 am for a better admin experience
          $update = array(
            'payment_status_id' => 'Pending',
            'receive_date' => date('Ymd', $next_sched_contribution_timestamp) . '030000',
          );
          $params = $this->setRecurReturnParams($params, $update);
          return $params;
        }
        return self::error('Unexpected error');
      }
      else {
        return self::error($customer['reasonMessage']);
      }
    }
  }

  /**
   * support corresponding CiviCRM method
   */
  public function changeSubscriptionAmount(&$message = '', $params = array()) {
    // $userAlert = ts('You have modified this recurring contribution.');
    // CRM_Core_Session::setStatus($userAlert, ts('Warning'), 'alert');
    return TRUE;
  }

  /**
   * support corresponding CiviCRM method
   */
  public function cancelSubscription(&$message = '', $params = array()) {
    $userAlert = ts('You have cancelled this recurring contribution.');
    CRM_Core_Session::setStatus($userAlert, ts('Warning'), 'alert');
    return TRUE;
  }

  /**
   * Set additional fields when editing the schedule.
   *
   * Note: this doesn't completely replace the form hook, which is still 
   * in use for additional changes, and to support 4.6.
   * e.g. the commented out fields below don't work properly here.
   */
  public function getEditableRecurringScheduleFields() {
    return array('amount',
         'installments',
         'next_sched_contribution_date',
//         'contribution_status_id',
//         'start_date',
//         'is_email_receipt',
       );
  }

  /*
   * Set a useful message at the top of the schedule editing form
   */
  public function getRecurringScheduleUpdateHelpText() {
    return 'Use this form to change the amount or number of installments for this recurring contribution. You can not change the contribution frequency.<br />You can also modify the next scheduled contribution date, and whether or not the recipient will get email receipts for each contribution.<br />You have an option to notify the donor of these changes.';
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
   * This function checks to see if we have the right config values.
   *
   * @return string
   *   The error message if any
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
   *
   * @param array $params
   * @param string $method
   *
   * @return array
   */
  protected function convertParams($params, $method) {
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
      'creditCardNum' => 'credit_card_number',
      'cvv2' => 'cvv2',
    );

    foreach ($convert as $r => $p) {
      if (isset($params[$p])) {
        $request[$r] = htmlspecialchars($params[$p]);
      }
    }
    $request['creditCardExpiry'] = sprintf('%02d/%02d', $params['month'], ($params['year'] % 100));
    $request['total'] = sprintf('%01.2f', CRM_Utils_Rule::cleanMoney($params['amount']));
    // Place for ugly hacks.
    switch ($method) {
      case 'cc_create_customer_code':
        $request['ccNum'] = $request['creditCardNum'];
        unset($request['creditCardNum']);
        $request['ccExp'] = $request['creditCardExpiry'];
        unset($request['creditCardExpiry']);
        break;

      case 'cc_with_customer_code':
        foreach (array('creditCardNum', 'creditCardExpiry', 'mop') as $key) {
          if (isset($request[$key])) {
            unset($request[$key]);
          }
        }
        break;
    }
    if (!empty($params['credit_card_type'])) {
      $mop = array(
        'Visa' => 'VISA',
        'MasterCard' => 'MC',
        'Amex' => 'AMX',
        'Discover' => 'DSC',
      );
      $request['mop'] = $mop[$params['credit_card_type']];
    }
    // print_r($request); print_r($params); die();
    return $request;
  }

  /*
   * Implement the ability to update the billing info for recurring contributions,
   * This functionality will apply to back-end and front-end,
   * so it's only enabled when configured as on via the iATS admin settings.
   * The default isSupported method is overridden above to achieve this.
   */
  public function updateSubscriptionBillingInfo(&$message = '', $params = array()) {
    require_once('CRM/iATS/Form/IATSCustomerUpdateBillingInfo.php');

    $fakeForm = new IATSCustomerUpdateBillingInfo();
    $fakeForm->updatedBillingInfo = $params;
    try {
      $fakeForm->postProcess();
    }
    catch (Exception $error) { // what could go wrong? 
      $message = $error->getMessage();
      CRM_Core_Session::setStatus($message, ts('Warning'), 'alert');
      $e = CRM_Core_Error::singleton();
      return $e; 
    }
    if ('OK' == $fakeForm->getAuthorizationResult()) {
      return TRUE;
    }
    $message = $fakeForm->getResultMessage();
    CRM_Core_Session::setStatus($message, ts('Warning'), 'alert');
    $e = CRM_Core_Error::singleton();
    return $e;
  }
  
  /*
   * Set the return params for recurring contributions.
   *
   * Implemented as a function so I can do some cleanup and implement
   * the ability to set a future start date for recurring contributions.
   * This functionality will apply to back-end and front-end,
   * As enabled when configured via the iATS admin settings.
   *
   * This function will alter the recurring schedule as an intended side effect.
   * and return the modified the params.
   */
  protected function setRecurReturnParams($params, $update) {
    // Merge in the updates
    $params = array_merge($params, $update);
    // If the recurring record already exists, let's fix the next contribution and start dates,
    // in case core isn't paying attention.
    // We also set the schedule to 'in-progress' (even for ACH/EFT when the first one hasn't been verified), 
    // because we want the recurring job to run for this schedule.
    if (!empty($params['contributionRecurID'])) {
      $recur_id = $params['contributionRecurID'];
      $recur_update = array(
        'id' => $recur_id,
        'contribution_status_id' => 'In Progress',
      );
      // use the receive date to set the next sched contribution date.
      // By default, it's empty, unless we've got a future start date.
      if (empty($update['receive_date'])) {
        $next = strtotime('+' . $params['frequency_interval'] . ' ' . $params['frequency_unit']);
        $recur_update['next_sched_contribution_date'] = date('Ymd', $next) . '030000';
      }
      else {
        $recur_update['start_date'] = $recur_update['next_sched_contribution_date'] = $update['receive_date'];
        // If I've got a monthly schedule, let's set the cycle_day for niceness
        if ('month' == $params['frequency_interval']) {
          $recur_update['cycle_day'] = date('j', strtotime($recur_update['start_date']));
        }
      }
      try {
        $result = civicrm_api3('ContributionRecur', 'create', $recur_update);
      }
      catch (CiviCRM_API3_Exception $e) {
        // Not a critical error, just log and continue.
        $error = $e->getMessage();
        Civi::log()->info('Unexpected error updating the next scheduled contribution date for id {id}: {error}', array('id' => $recur_id, 'error' => $error));
      }
    }
    else {
      Civi::log()->info('Unexpectedly unable to update the next scheduled contribution date, missing id.');
    }
    return $params;
  }
  
}
