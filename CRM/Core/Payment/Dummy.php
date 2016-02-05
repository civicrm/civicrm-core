<?php
/*
 * Copyright (C) 2007
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * Written and contributed by Ideal Solution, LLC (http://www.idealso.com)
 *
 */

/**
 *
 * @package CRM
 * @author Marshal Newrock <marshal@idealso.com>
 * $Id: Dummy.php 45429 2013-02-06 22:11:18Z lobo $
 */

/**
 * Dummy payment processor
 */
class CRM_Core_Payment_Dummy extends CRM_Core_Payment {
  const CHARSET = 'iso-8859-1';

  protected $_mode = NULL;

  protected $_params = array();
  protected $_doDirectPaymentResult = array();

  /**
   * Set result from do Direct Payment for test purposes.
   *
   * @param array $doDirectPaymentResult
   *  Result to be returned from test.
   */
  public function setDoDirectPaymentResult($doDirectPaymentResult) {
    $this->_doDirectPaymentResult = $doDirectPaymentResult;
    if (empty($this->_doDirectPaymentResult['trxn_id'])) {
      $this->_doDirectPaymentResult['trxn_id'] = array();
    }
    else {
      $this->_doDirectPaymentResult['trxn_id'] = (array) $doDirectPaymentResult['trxn_id'];
    }
  }

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   */
  static private $_singleton = NULL;

  /**
   * Constructor.
   *
   * @param string $mode
   *   The mode of operation: live or test.
   *
   * @param $paymentProcessor
   *
   * @return \CRM_Core_Payment_Dummy
   */
  public function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('Dummy Processor');
  }

  /**
   * Submit a payment using Advanced Integration Method.
   *
   * @param array $params
   *   Assoc array of input parameters for this transaction.
   *
   * @return array
   *   the result in a nice formatted array (or an error object)
   */
  public function doDirectPayment(&$params) {
    // Invoke hook_civicrm_paymentProcessor
    // In Dummy's case, there is no translation of parameters into
    // the back-end's canonical set of parameters.  But if a processor
    // does this, it needs to invoke this hook after it has done translation,
    // but before it actually starts talking to its proprietary back-end.

    // no translation in Dummy processor
    $cookedParams = $params;
    CRM_Utils_Hook::alterPaymentProcessorParams($this,
      $params,
      $cookedParams
    );
    //end of hook invocation
    if (!empty($this->_doDirectPaymentResult)) {
      $result = $this->_doDirectPaymentResult;
      $result['trxn_id'] = array_shift($this->_doDirectPaymentResult['trxn_id']);
      return $result;
    }
    if ($this->_mode == 'test') {
      $query = "SELECT MAX(trxn_id) FROM civicrm_contribution WHERE trxn_id LIKE 'test\\_%'";
      $p = array();
      $trxn_id = strval(CRM_Core_Dao::singleValueQuery($query, $p));
      $trxn_id = str_replace('test_', '', $trxn_id);
      $trxn_id = intval($trxn_id) + 1;
      $params['trxn_id'] = sprintf('test_%08d', $trxn_id);
    }
    else {
      $query = "SELECT MAX(trxn_id) FROM civicrm_contribution WHERE trxn_id LIKE 'live_%'";
      $p = array();
      $trxn_id = strval(CRM_Core_Dao::singleValueQuery($query, $p));
      $trxn_id = str_replace('live_', '', $trxn_id);
      $trxn_id = intval($trxn_id) + 1;
      $params['trxn_id'] = sprintf('live_%08d', $trxn_id);
    }
    $params['gross_amount'] = $params['amount'];
    // Add a fee_amount so we can make sure fees are handled properly in underlying classes.
    $params['fee_amount'] = 1.50;
    $params['net_amount'] = $params['gross_amount'] - $params['fee_amount'];

    return $params;
  }

  /**
   * Are back office payments supported - e.g paypal standard won't permit you to enter a credit card associated with someone else's login
   * @return bool
   */
  protected function supportsLiveMode() {
    return TRUE;
  }

  /**
   * Generate error object.
   *
   * Throwing exceptions is preferred over this.
   *
   * @param string $errorCode
   * @param string $errorMessage
   *
   * @return CRM_Core_Error
   *   Error object.
   */
  public function &error($errorCode = NULL, $errorMessage = NULL) {
    $e = CRM_Core_Error::singleton();
    if ($errorCode) {
      $e->push($errorCode, 0, NULL, $errorMessage);
    }
    else {
      $e->push(9001, 0, NULL, 'Unknown System Error.');
    }
    return $e;
  }

  /**
   * This function checks to see if we have the right config values.
   *
   * @return string
   *   the error message if any
   */
  public function checkConfig() {
    return NULL;
  }

}
