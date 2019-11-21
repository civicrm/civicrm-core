<?php

require_once 'CRM/Core/Payment.php';

class CRM_Core_Payment_FapsACH extends CRM_Core_Payment_Faps {

  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return void
   */
  function __construct( $mode, &$paymentProcessor ) {
    $this->_mode             = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName    = ts('iATS Payments 1st American Payment System Interface, ACH');
    $this->disable_cryptogram    = iats_get_setting('disable_cryptogram');
    $this->is_test = ($this->_mode == 'test' ? 1 : 0); 
  }

  /**
   * Get array of fields that should be displayed on the payment form for credit cards.
   * Use FAPS cryptojs to gather the senstive card information, if enabled.
   *
   * @return array
   */

  protected function getDirectDebitFormFields() {
    $fields =  $this->disable_cryptogram ? parent::getDirectDebitFormFields() : array('cryptogram');
    return $fields;
  }

/**
   * Opportunity for the payment processor to override the entire form build.
   *
   * @param CRM_Core_Form $form
   *
   * @return bool
   *   Should form building stop at this point?
   *
   * return (!empty($form->_paymentFields));
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function buildForm(&$form) {
    /* by default, use the cryptogram, but allow it to be disabled */
    if (iats_get_setting('disable_cryptogram')) {
      return;
    }
    // otherwise, generate some js settings that will allow the included
    // crypto.js to generate the required iframe.
    $iats_domain = parse_url($this->_paymentProcessor['url_site'], PHP_URL_HOST);
    // cryptojs is url of the firstpay script that needs to get loaded after the iframe
    // is generated.
    $cryptojs = 'https://' . $iats_domain . '/secure/PaymentHostedForm/Scripts/firstpay/firstpay.cryptogram.js';
    $currency = $form->getCurrency();
    $iframe_src = 'https://' . $iats_domain . '/secure/PaymentHostedForm/v3/' . (('CAD' == $currency) ? 'CanadianAch' : 'Ach');
    $jsVariables = [
      'paymentProcessorId' => $this->_paymentProcessor['id'],
      'transcenterId' => $this->_paymentProcessor['password'],
      'processorId' => $this->_paymentProcessor['user_name'],
      'currency' => $currency,
      'is_test' => $this->is_test,
      'title' => $form->getTitle(),
      'iframe_src' => $iframe_src,
      'cryptojs' => $cryptojs,
      'paymentInstrumentId' => 2,
    ];
    $resources = CRM_Core_Resources::singleton();
    $cryptoCss = $resources->getUrl('com.iatspayments.civicrm', 'css/crypto.css');
    $markup = '<link type="text/css" rel="stylesheet" href="'.$cryptoCss.'" media="all" /><script type="text/javascript" src="'.$cryptojs.'"></script>';
    CRM_Core_Region::instance('billing-block')->add(array(
      'markup' => $markup,
    ));
    // the cryptojs above is the one on the 1pay server, now I load and invoke the extension's crypto.js
    $myCryptoJs = $resources->getUrl('com.iatspayments.civicrm', 'js/crypto.js');
    // after manually doing what addVars('iats', $jsVariables) would normally do
    $script = 'var iatsSettings = ' . json_encode($jsVariables) . ';';
    $script .= 'var cryptoJs = "'.$myCryptoJs.'";';
    $script .= 'CRM.$(function ($) { $.getScript(cryptoJs); });';
    CRM_Core_Region::instance('billing-block')->add(array(
      'script' => $script,
    ));
    // and now add in a helpful cheque image and description
    switch($currency) {
      case 'USD': 
        CRM_Core_Region::instance('billing-block')->add(array(
          'template' => 'CRM/Iats/BillingBlockFapsACH_USD.tpl',
        ));
      case 'CAD': 
        CRM_Core_Region::instance('billing-block')->add(array(
          'template' => 'CRM/Iats/BillingBlockFapsACH_CAD.tpl',
        ));
    }
    return FALSE;
  }


  /**
   * function doDirectPayment
   *
   * This is the function for taking a payment using a core payment form of any kind.
   *
   */
  public function doDirectPayment(&$params) {
    // CRM_Core_Error::debug_var('doDirectPayment params', $params);

    // Check for valid currency
    $currency = $params['currencyID'];
    if (('USD' != $currency) && ('CAD' != $currency)) {
      return self::error('Invalid currency selection: ' . $currency);
    }
    $isRecur = CRM_Utils_Array::value('is_recur', $params) && $params['contributionRecurID'];
    $ipAddress = (function_exists('ip_address') ? ip_address() : $_SERVER['REMOTE_ADDR']);
    $credentials = array(
      'merchantKey' => $this->_paymentProcessor['signature'],
      'processorId' => $this->_paymentProcessor['user_name']
    );
    // FAPS has a funny thing called a 'category' that needs to be included with any ACH request.
    // The category is auto-generated in the getCategoryText function, using some default settings that can be overridden on the FAPS settings page.
    // Store it in params, will be used by my convert request call(s) later
    $params['ach_category_text'] = self::getCategoryText($credentials, $this->is_test, $ipAddress);

    $vault_key = $vault_id = '';
    if ($isRecur) {
      // Store the params in a vault before attempting payment
      $options = array(
        'action' => 'VaultCreateAchRecord',
        'test' => $this->is_test,
      );
      $vault_request = new CRM_Iats_FapsRequest($options);
      $request = $this->convertParams($params, $options['action']);
      // auto-generate a compliant vault key  
      $vault_key = self::generateVaultKey($request['ownerEmail']);
      $request['vaultKey'] = $vault_key;
      $request['ipAddress'] = $ipAddress;
      // Make the request.
      //CRM_Core_Error::debug_var('vault request', $request);
      $result = $vault_request->request($credentials, $request);
      // unset the cryptogram param, we can't use it again and don't want to return it anyway.
      unset($params['cryptogram']);
      //CRM_Core_Error::debug_var('vault result', $result);
      if (!empty($result['isSuccess'])) {
        $vault_id = $result['data']['id'];
        if ($isRecur) {
          // save my vaule key + vault id as a token
          $token = $vault_key.':'.$vault_id;
          $payment_token_params = [
           'token' => $token,
           'ip_address' => $request['ipAddress'],
           'contact_id' => $params['contactID'],
           'email' => $request['ownerEmail'],
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
          // updateRecurring, incluing updating the next scheduled contribution date, before taking payment.
          $this->updateRecurring($params);
        }
      }
      else {
        return self::error($result);
      }
      // now set the options for taking the money
      $options = array(
        'action' => 'AchDebitUsingVault',
        'test' => $this->is_test,
      );
    }
    else { // set the simple sale option for taking the money
      $options = array(
        'action' => 'AchDebit',
        'test' => $this->is_test,
      );
    }
    // now take the money
    $payment_request = new CRM_Iats_FapsRequest($options);
    $request = $this->convertParams($params, $options['action']);
    $request['ipAddress'] = $ipAddress;
    if ($vault_id) {
      $request['vaultKey'] = $vault_key;
      $request['vaultId'] = $vault_id;
    }
    // Make the request.
    // CRM_Core_Error::debug_var('payment request', $request);
    $result = $payment_request->request($credentials, $request);
    // CRM_Core_Error::debug_var('result', $result);
    $success = (!empty($result['isSuccess']));
    if ($success) {
      $params['payment_status_id'] = 2;
      $params['trxn_id'] = trim($result['data']['referenceNumber']).':'.time();
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
      return self::error($result);
    }
  }

  /**
   * Get the category text. 
   * Before I return it, check that the category text exists, and create it if
   * it doesn't.
   *
   * FAPS has a funny thing called a 'category' that needs to be included with
   * any ACH request. This function will test if a category text string exists
   * and create it if it doesn't.
   *
   * TODO: including the setup of the category on the FAPS system within this
   * function is very funky, it should get done when the account is setup instead.
   *
   * @param string $ach_category_text
   * @param array $credentials
   *
   * @return none
   */
  public static function getCategoryText($credentials, $is_test, $ipAddress = NULL) {
    static $ach_category_text_saved;
    if (!empty($ach_category_text_saved)) {
      return $ach_category_text_saved;
    } 
    $ach_category_text = iats_get_setting('ach_category_text');
    $ach_category_text = empty($ach_category_text) ? FAPS_DEFAULT_ACH_CATEGORY_TEXT : $ach_category_text;
    $ach_category_exists = FALSE;
    // check if it's setup
    $options = array(
      'action' => 'AchGetCategories',
      'test' => $is_test,
    );
    $categories_request = new CRM_Iats_FapsRequest($options);
    $request = empty($ipAddress) ? array() : array('ipAddress' => $ipAddress);
    $result = $categories_request->request($credentials, $request);
    // CRM_Core_Error::debug_var('categories request result', $result);
    if (!empty($result['isSuccess']) && !empty($result['data'])) {
      foreach($result['data'] as $category) {
        if ($category['achCategoryText'] == $ach_category_text) {
          $ach_category_exists = TRUE;
          break;
        }
      }
    }
    if (!$ach_category_exists) { // set it up!
      $options = array(
        'action' => 'AchCreateCategory',
        'test' => $is_test,
      );
      $categories_request = new CRM_Iats_FapsRequest($options);
      // I've got some non-offensive defaults in here.
      $request = array(
        'achCategoryText' => $ach_category_text,
        'achClassCode' => 'WEB',
        'achEntry' => 'CiviCRM',
      );
      if (!empty($ipAddress)) {
        $request['ipAddress'] = $ipAddress;
      }
      $result = $categories_request->request($credentials, $request);
      // I'm being a bit naive and assuming it succeeds.
    }
    return $ach_category_text_saved = $ach_category_text;
  }

  /**
   * Convert the values in the civicrm params to the request array with keys as expected by FAPS
   * ACH has different fields from credit card.
   *
   * @param array $params
   * @param string $action
   *
   * @return array
   */
  protected function convertParams($params, $method) {
    $request = array();
    $convert = array(
      'ownerEmail' => 'email',
      'ownerStreet' => 'street_address',
      'ownerCity' => 'city',
      'ownerState' => 'state_province',
      'ownerZip' => 'postal_code',
      'ownerCountry' => 'country',
      'orderId' => 'invoiceID',
      'achCryptogram' => 'cryptogram',
    );
    foreach ($convert as $r => $p) {
      if (isset($params[$p])) {
        $request[$r] = htmlspecialchars($params[$p]);
      }
    }
    if (empty($params['email'])) {
      if (isset($params['email-5'])) {
        $request['ownerEmail'] = $params['email-5'];
      }
      elseif (isset($params['email-Primary'])) {
        $request['ownerEmail'] = $params['email-Primary'];
      }
    }
    $request['ownerName'] = $params['billing_first_name'].' '.$params['billing_last_name'];
    $request['transactionAmount'] = sprintf('%01.2f', CRM_Utils_Rule::cleanMoney($params['amount']));
    $request['categoryText'] = $params['ach_category_text'];
    return $request;
  }

}
