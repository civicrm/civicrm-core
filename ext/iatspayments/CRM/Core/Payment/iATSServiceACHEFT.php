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
 * This code provides glue between CiviCRM payment model and the iATS Payment model encapsulated in the CRM_Iats_iATSServiceRequest object
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
   * Get array of fields that should be displayed on the payment form for ACH/EFT (badly named as debit cards).
   *
   * @return array
   */

  protected function getDirectDebitFormFields() {
    $fields = parent::getDirectDebitFormFields();
    $fields[] = 'bank_account_type';
    // print_r($fields); die();
    return $fields;
  }

  /**
   * Return an array of all the details about the fields potentially required for payment fields.
   *
   * Only those determined by getPaymentFormFields will actually be assigned to the form
   *
   * @return array
   *   field metadata
   */
  public function getPaymentFormFieldsMetadata() {
    $metadata = parent::getPaymentFormFieldsMetadata();
    $metadata['bank_account_type'] = [
      'htmlType' => 'Select',
      'name' => 'bank_account_type',
      'title' => ts('Account type'),
      'is_required' => TRUE,
      'attributes' => ['CHECKING' => 'Chequing', 'SAVING' => 'Savings'],
    ];
    return $metadata;
  }


  /**
   * Opportunity for the payment processor to override the entire form build.
   *
   * @param CRM_Core_Form $form
   *
   * @return bool
   *   Should form building stop at this point?
   *
   * Add ACH/EFT per currency instructions, also do parent (cc) form building to allow future
   * recurring on public pages.
   *
   * return (!empty($form->_paymentFields));
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function buildForm(&$form) {
    // If a form allows ACH/EFT and enables recurring, set recurring to the default. 
    if (isset($form->_elementIndex['is_recur'])) {
      // Make recurring contrib default to true.
      $form->setDefaults(array('is_recur' => 1));
    }
    $currency = iats_getCurrency($form);
    // my javascript will (should, not yet) use the currency to rewrite some labels
    $jsVariables = [
      'currency' => $currency,
    ];
    CRM_Core_Resources::singleton()->addVars('iats', $jsVariables);
    CRM_Core_Resources::singleton()->addScriptFile('com.iatspayments.civicrm', 'js/dd_acheft.js', 10);
    // add in a billing block template in a currency dependent way.
    $fname = 'buildForm_' . $currency;
    if ($currency && method_exists($this,$fname)) {
      // add in the common fields and rules first to allow modifications
      //$this->addCommonFields($form, $form->_paymentFields);
      //$this->addRules($form, $form->_paymentFields);
      $this->$fname($form);
    }
    // Else, I'm handling an unexpected currency.
    elseif ($currency) {
      CRM_Core_Region::instance('billing-block')->add(array(
        'template' => 'CRM/Iats/BillingBlockDirectDebitExtra_Other.tpl',
      ));
    }
    return parent::buildForm($form);
  }


  /**
   * Customization for USD ACH-EFT billing block.
   */
  protected function buildForm_USD(&$form) {
    /*
    $element = $form->getElement('account_holder');
    $element->setLabel(ts('Name of Account Holder'));
    $element = $form->getElement('bank_account_number');
    $element->setLabel(ts('Bank Account Number'));
    $element = $form->getElement('bank_identification_number');
    $element->setLabel(ts('Bank Routing Number')); */
    /* if (empty($form->billingFieldSets['direct_debit']['fields']['bank_identification_number']['is_required'])) {
      $form->addRule('bank_identification_number', ts('%1 is a required field.', array(1 => ts('Bank Routing Number'))), 'required');
  } */
    CRM_Core_Region::instance('billing-block')->add(array(
      'template' => 'CRM/Iats/BillingBlockDirectDebitExtra_USD.tpl',
    ));
  }
  
  /**
   * Customization for CAD ACH-EFT billing block.
   *
   * Add some elements (bank number and transit number) that are used to
   * generate the bank identification number, which is hidden.
   * I can't do this in the usual way because it's currency-specific.
   * Note that this is really just an interface convenience, the ACH/EFT
   * North American interbank system is consistent across US and Canada.
   */
  protected function buildForm_CAD(&$form) {
    $form->addElement('text', 'cad_bank_number', ts('Bank Number (3 digits)'));
    $form->addRule('cad_bank_number', ts('%1 is a required field.', array(1 => ts('Bank Number'))), 'required');
    $form->addRule('cad_bank_number', ts('%1 must contain only digits.', array(1 => ts('Bank Number'))), 'numeric');
    $form->addRule('cad_bank_number', ts('%1 must be of length 3.', array(1 => ts('Bank Number'))), 'rangelength', array(3, 3));
    $form->addElement('text', 'cad_transit_number', ts('Transit Number (5 digits)'));
    $form->addRule('cad_transit_number', ts('%1 is a required field.', array(1 => ts('Transit Number'))), 'required');
    $form->addRule('cad_transit_number', ts('%1 must contain only digits.', array(1 => ts('Transit Number'))), 'numeric');
    $form->addRule('cad_transit_number', ts('%1 must be of length 5.', array(1 => ts('Transit Number'))), 'rangelength', array(5, 5));
    /* minor customization of labels + make them required  */
    /* $element = $form->getElement('account_holder');
    $element->setLabel(ts('Name of Account Holder'));
    $element = $form->getElement('bank_account_number');
    $element->setLabel(ts('Account Number'));
    $form->addRule('bank_account_number', ts('%1 must contain only digits.', array(1 => ts('Bank Account Number'))), 'numeric'); */
    /* the bank_identification_number is hidden and then populated using jquery, in the custom template  */
    /* $element = $form->getElement('bank_identification_number');
    $element->setLabel(ts('Bank Number + Transit Number')); */
    // print_r($form); die();
    CRM_Core_Resources::singleton()->addScriptFile('com.iatspayments.civicrm', 'js/dd_cad.js', 10);
    CRM_Core_Region::instance('billing-block')->add(array(
      'template' => 'CRM/Iats/BillingBlockDirectDebitExtra_CAD.tpl',
    ));
  }


  /**
   *
   */
  public function doDirectPayment(&$params) {

    if (!$this->_profile) {
      return self::error('Unexpected error, missing profile');
    }
    // Use the iATSService object for interacting with iATS, mostly the same for recurring contributions.
    // We handle both one-time and recurring ACH/EFT
    $isRecur = CRM_Utils_Array::value('is_recur', $params) && $params['contributionRecurID'];
    $methodType = $isRecur ? 'customer' : 'process';
    $method = $isRecur ? 'create_acheft_customer_code' : 'acheft';
    $iats = new CRM_Iats_iATSServiceRequest(array('type' => $methodType, 'method' => $method, 'iats_domain' => $this->_profile['iats_domain'], 'currencyID' => $params['currencyID']));
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
      // Save the customer info in to the CiviCRM core payment_token table
      $customer = $iats->result($response);
      if (!$customer['status']) {
        return self::error($customer['reasonMessage']);
      }
      else {
        $processresult = $response->PROCESSRESULT;
        $customer_code = (string) $processresult->CUSTOMERCODE;
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
        $payment_token_params = [
          'token' => $customer_code,
          'ip_address' => $request['customerIPAddress'],
          'contact_id' => $params['contactID'],
          'email' => $email,
          'payment_processor_id' => $this->_paymentProcessor['id'],
        ];
        $token_result = civicrm_api3('PaymentToken', 'create', $payment_token_params);
        // Upon success, save the token table's id back in the recurring record.
        if (!empty($token_result['id'])) {
          civicrm_api3('ContributionRecur', 'create', [
            'id' => $params['contributionRecurID'],
            'payment_token_id' => $token_result['id'],
          ]);
        }
        // Test for admin setting that limits allowable transaction days
        $allow_days = $this->getSettings('days');
        // Test for a specific receive date request and convert to a timestamp, default now
        $receive_date = CRM_Utils_Array::value('receive_date', $params);
        // my front-end addition to will get stripped out of the params, do a
        // work-around
        if (empty($receive_date)) {
          $receive_date = CRM_Utils_Array::value('receive_date', $_POST);
        }
        $receive_ts = empty($receive_date) ? time() : strtotime($receive_date);
        // If the admin setting is in force, ensure it's compatible.
        if (max($allow_days) > 0) {
          $receive_ts = CRM_Iats_Transaction::contributionrecur_next($receive_ts, $allow_days);
        }
        // convert to a reliable format
        $receive_date = date('Ymd', $receive_ts);
        $today = date('Ymd');
        // If the receive_date is NOT today, then
        // create a pending contribution and adjust the next scheduled date.
        if ($receive_date !== $today) {
          // I've got a schedule to adhere to!
          // set the receieve time to 3:00 am for a better admin experience
          $update = array(
            'payment_status_id' => 2,
            'receive_date' => date('Ymd', $receive_ts) . '030000',
          );
          // update the recurring and contribution records with the receive date,
          // i.e. make up for what core doesn't do
          $this->updateRecurring($params, $update);
          $this->updateContribution($params, $update);
          // and now return the updates to core via the params
          $params = array_merge($params, $update);
          return $params;
        }
        else {
          $iats = new CRM_Iats_iATSServiceRequest(array('type' => 'process', 'method' => 'acheft_with_customer_code', 'iats_domain' => $this->_profile['iats_domain'], 'currencyID' => $params['currencyID']));
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
              'payment_status_id' => 2,
            );
            // Setting the next_sched_contribution_date param doesn't do anything, 
            // work around in updateRecurring
            $this->updateRecurring($params, $update);
            $params = array_merge($params, $update);
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
    // The "&" character is badly handled by the processor,
    // so we sanitize it to "and"
    $request['firstName'] = str_replace('&', ts('and'), $request['firstName']);
    $request['lastName'] = str_replace('&', ts('and'), $request['lastName']);
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
