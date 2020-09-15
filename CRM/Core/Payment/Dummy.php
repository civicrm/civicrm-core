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
 */

use Civi\Payment\Exception\PaymentProcessorException;
use Civi\Payment\PropertyBag;

/**
 * Dummy payment processor
 */
class CRM_Core_Payment_Dummy extends CRM_Core_Payment {
  protected $_mode;

  protected $_params = [];
  protected $_doDirectPaymentResult = [];

  /**
   * Set result from do Direct Payment for test purposes.
   *
   * @param array $doDirectPaymentResult
   *  Result to be returned from test.
   */
  public function setDoDirectPaymentResult($doDirectPaymentResult) {
    $this->_doDirectPaymentResult = $doDirectPaymentResult;
    if (empty($this->_doDirectPaymentResult['trxn_id'])) {
      $this->_doDirectPaymentResult['trxn_id'] = [];
    }
    else {
      $this->_doDirectPaymentResult['trxn_id'] = (array) $doDirectPaymentResult['trxn_id'];
    }
  }

  /**
   * Constructor.
   *
   * @param string $mode
   *   The mode of operation: live or test.
   *
   * @param array $paymentProcessor
   */
  public function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
  }

  /**
   * Submit a payment using Advanced Integration Method.
   *
   * @param array $params
   *   Assoc array of input parameters for this transaction.
   *
   * @return array
   *   the result in a nice formatted array (or an error object)
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function doDirectPayment(&$params) {
    $propertyBag = PropertyBag::cast($params);
    // Invoke hook_civicrm_paymentProcessor
    // In Dummy's case, there is no translation of parameters into
    // the back-end's canonical set of parameters.  But if a processor
    // does this, it needs to invoke this hook after it has done translation,
    // but before it actually starts talking to its proprietary back-end.
    if ($propertyBag->getIsRecur()) {
      $throwAnENoticeIfNotSetAsTheseAreRequired = $propertyBag->getRecurFrequencyInterval() . $propertyBag->getRecurFrequencyUnit();
    }
    // no translation in Dummy processor
    CRM_Utils_Hook::alterPaymentProcessorParams($this,
      $params,
      $propertyBag
    );
    // This means we can test failing transactions by setting a past year in expiry. A full expiry check would
    // be more complete.
    if (!empty($params['credit_card_exp_date']['Y']) && date('Y') >
      CRM_Core_Payment_Form::getCreditCardExpirationYear($params)) {
      throw new PaymentProcessorException(ts('Invalid expiry date'));
    }
    //end of hook invocation
    if (!empty($this->_doDirectPaymentResult)) {
      $result = $this->_doDirectPaymentResult;
      if (CRM_Utils_Array::value('payment_status_id', $result) === 'failed') {
        throw new PaymentProcessorException($result['message'] ?? 'failed');
      }
      $result['trxn_id'] = array_shift($this->_doDirectPaymentResult['trxn_id']);
      return $result;
    }

    $params['trxn_id'] = $this->getTrxnID();;

    // Add a fee_amount so we can make sure fees are handled properly in underlying classes.
    $params['fee_amount'] = 1.50;
    $params['description'] = $this->getPaymentDescription($params);

    return $params;
  }

  /**
   * Does this payment processor support refund?
   *
   * @return bool
   */
  public function supportsRefund() {
    return TRUE;
  }

  /**
   * Supports altering future start dates.
   *
   * @return bool
   */
  public function supportsFutureRecurStartDate() {
    return TRUE;
  }

  /**
   * Submit a refund payment
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   *
   * @param array $params
   *   Assoc array of input parameters for this transaction.
   */
  public function doRefund(&$params) {}

  /**
   * This function checks to see if we have the right config values.
   *
   * @return string
   *   the error message if any
   */
  public function checkConfig() {
    return NULL;
  }

  /**
   * Get an array of the fields that can be edited on the recurring contribution.
   *
   * Some payment processors support editing the amount and other scheduling details of recurring payments, especially
   * those which use tokens. Others are fixed. This function allows the processor to return an array of the fields that
   * can be updated from the contribution recur edit screen.
   *
   * The fields are likely to be a subset of these
   *  - 'amount',
   *  - 'installments',
   *  - 'frequency_interval',
   *  - 'frequency_unit',
   *  - 'cycle_day',
   *  - 'next_sched_contribution_date',
   *  - 'end_date',
   *  - 'failure_retry_day',
   *
   * The form does not restrict which fields from the contribution_recur table can be added (although if the html_type
   * metadata is not defined in the xml for the field it will cause an error.
   *
   * Open question - would it make sense to return membership_id in this - which is sometimes editable and is on that
   * form (UpdateSubscription).
   *
   * @return array
   */
  public function getEditableRecurringScheduleFields() {
    return ['amount', 'next_sched_contribution_date'];
  }

  /**
   * Does this processor support cancelling recurring contributions through code.
   *
   * If the processor returns true it must be possible to take action from within CiviCRM
   * that will result in no further payments being processed. In the case of token processors (e.g
   * IATS, eWay) updating the contribution_recur table is probably sufficient.
   *
   * @return bool
   */
  protected function supportsCancelRecurring() {
    return TRUE;
  }

  /**
   * Cancel a recurring subscription.
   *
   * Payment processor classes should override this rather than implementing cancelSubscription.
   *
   * A PaymentProcessorException should be thrown if the update of the contribution_recur
   * record should not proceed (in many cases this function does nothing
   * as the payment processor does not need to take any action & this should silently
   * proceed. Note the form layer will only call this after calling
   * $processor->supports('cancelRecurring');
   *
   * @param \Civi\Payment\PropertyBag $propertyBag
   *
   * @return array
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function doCancelRecurring(PropertyBag $propertyBag) {
    return ['message' => ts('Recurring contribution cancelled')];
  }

  /**
   * Get a value for the transaction ID.
   *
   * Value is made up of the max existing value + a random string.
   *
   * Note the random string is likely a historical workaround.
   *
   * @return string
   */
  protected function getTrxnID() {
    $string = $this->_mode;
    $trxn_id = CRM_Core_DAO::singleValueQuery("SELECT MAX(trxn_id) FROM civicrm_contribution WHERE trxn_id LIKE '{$string}_%'");
    $trxn_id = str_replace($string, '', $trxn_id);
    $trxn_id = (int) $trxn_id + 1;
    return $string . '_' . $trxn_id . '_' . uniqid();
  }

}
