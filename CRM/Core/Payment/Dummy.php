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
  protected $_doDirectPaymentResult = [];

  /**
   * This support variable is used to allow the capabilities supported by the Dummy processor to be set from unit tests
   *   so that we don't need to create a lot of new processors to test combinations of features.
   * Initially these capabilities are set to TRUE, however they can be altered by calling the setSupports function directly from outside the class.
   * @var bool[]
   */
  protected $supports = [
    'MultipleConcurrentPayments' => TRUE,
    'EditRecurringContribution' => TRUE,
    'CancelRecurringNotifyOptional' => TRUE,
    'BackOffice' => TRUE,
    'NoEmailProvided' => TRUE,
    'CancelRecurring' => TRUE,
    'FutureRecurStartDate' => TRUE,
    'Refund' => TRUE,
  ];

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
   * Does this payment processor support refund?
   *
   * @return bool
   */
  public function supportsRefund() {
    return $this->supports['Refund'];
  }

  /**
   * Should the first payment date be configurable when setting up back office recurring payments.
   *
   * We set this to false for historical consistency but in fact most new processors use tokens for recurring and can support this
   *
   * @return bool
   */
  public function supportsFutureRecurStartDate() {
    return $this->supports['FutureRecurStartDate'];
  }

  /**
   * Can more than one transaction be processed at once?
   *
   * In general processors that process payment by server to server communication support this while others do not.
   *
   * In future we are likely to hit an issue where this depends on whether a token already exists.
   *
   * @return bool
   */
  protected function supportsMultipleConcurrentPayments() {
    return $this->supports['MultipleConcurrentPayments'];
  }

  /**
   * Checks if back-office recurring edit is allowed
   *
   * @return bool
   */
  public function supportsEditRecurringContribution() {
    return $this->supports['EditRecurringContribution'];
  }

  /**
   * Are back office payments supported.
   *
   * e.g paypal standard won't permit you to enter a credit card associated
   * with someone else's login.
   * The intention is to support off-site (other than paypal) & direct debit but that is not all working yet so to
   * reach a 'stable' point we disable.
   *
   * @return bool
   */
  protected function supportsBackOffice() {
    return $this->supports['BackOffice'];
  }

  /**
   * Does the processor work without an email address?
   *
   * The historic assumption is that all processors require an email address. This capability
   * allows a processor to state it does not need to be provided with an email address.
   * NB: when this was added (Feb 2020), the Manual processor class overrides this but
   * the only use of the capability is in the webform_civicrm module.  It is not currently
   * used in core but may be in future.
   *
   * @return bool
   */
  protected function supportsNoEmailProvided() {
    return $this->supports['NoEmailProvided'];
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
    return $this->supports['CancelRecurring'];
  }

  /**
   * Does the processor support the user having a choice as to whether to cancel the recurring with the processor?
   *
   * If this returns TRUE then there will be an option to send a cancellation request in the cancellation form.
   *
   * This would normally be false for processors where CiviCRM maintains the schedule.
   *
   * @return bool
   */
  protected function supportsCancelRecurringNotifyOptional() {
    return $this->supports['CancelRecurringNotifyOptional'];
  }

  /**
   * Does this processor support pre-approval.
   *
   * This would generally look like a redirect to enter credentials which can then be used in a later payment call.
   *
   * Currently Paypal express supports this, with a redirect to paypal after the 'Main' form is submitted in the
   * contribution page. This token can then be processed at the confirm phase. Although this flow 'looks' like the
   * 'notify' flow a key difference is that in the notify flow they don't have to return but in this flow they do.
   *
   * @return bool
   */
  protected function supportsPreApproval(): bool {
    return $this->supports['PreApproval'] ?? FALSE;
  }

  /**
   * Set the return value of support functions. By default it is TRUE
   *
   */
  public function setSupports(array $support) {
    $this->supports = array_merge($this->supports, $support);
  }

  /**
   * @param array|PropertyBag $params
   *
   * @param string $component
   *
   * @return array
   *   Result array (containing at least the key payment_status_id)
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function doPayment(&$params, $component = 'contribute') {
    $this->_component = $component;
    $statuses = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'validate');

    $propertyBag = PropertyBag::cast($params);
    if ((float) $propertyBag->getAmount() !== (float) $params['amount']) {
      CRM_Core_Error::deprecatedWarning('amount should be passed through in machine-ready format');
    }
    // If we have a $0 amount, skip call to processor and set payment_status to Completed.
    // Conceivably a processor might override this - perhaps for setting up a token - but we don't
    // have an example of that at the mome.
    if ($propertyBag->getAmount() == 0) {
      $result['payment_status_id'] = array_search('Completed', $statuses);
      $result['payment_status'] = 'Completed';
      return $result;
    }

    // Invoke hook_civicrm_paymentProcessor
    // In Dummy's case, there is no translation of parameters into
    // the back-end's canonical set of parameters.  But if a processor
    // does this, it needs to invoke this hook after it has done translation,
    // but before it actually starts talking to its proprietary back-end.
    if ($propertyBag->getIsRecur()) {
      $throwAnENoticeIfNotSetAsTheseAreRequired = $propertyBag->getRecurFrequencyInterval() . $propertyBag->getRecurFrequencyUnit();
    }
    // no translation in Dummy processor
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $propertyBag);
    // This means we can test failing transactions by setting a past year in expiry. A full expiry check would
    // be more complete.
    if (!empty($params['credit_card_exp_date']['Y']) && CRM_Utils_Time::date('Y') >
      CRM_Core_Payment_Form::getCreditCardExpirationYear($params)) {
      throw new PaymentProcessorException(ts('Invalid expiry date'));
    }

    if (!empty($this->_doDirectPaymentResult)) {
      $result = $this->_doDirectPaymentResult;
      if (empty($result['payment_status_id'])) {
        $result['payment_status_id'] = array_search('Pending', $statuses);
        $result['payment_status'] = 'Pending';
      }
      if ($result['payment_status_id'] === 'failed') {
        throw new PaymentProcessorException($result['message'] ?? 'failed');
      }
      $result['trxn_id'] = array_shift($this->_doDirectPaymentResult['trxn_id']);
      return $result;
    }

    $result['trxn_id'] = $this->getTrxnID();

    // Add a fee_amount so we can make sure fees are handled properly in underlying classes.
    $result['fee_amount'] = 1.50;
    $result['description'] = $this->getPaymentDescription($params);

    if (!isset($result['payment_status_id'])) {
      if (!empty($propertyBag->getIsRecur())) {
        // See comment block.
        $result['payment_status_id'] = array_search('Pending', $statuses);
        $result['payment_status'] = 'Pending';
      }
      else {
        $result['payment_status_id'] = array_search('Completed', $statuses);
        $result['payment_status'] = 'Completed';
      }
    }

    return $result;
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
   * Function to action pre-approval if supported
   *
   * @param array $params
   *   Parameters from the form
   *
   * This function returns an array which should contain
   *   - pre_approval_parameters (this will be stored on the calling form & available later)
   *   - redirect_url (if set the browser will be redirected to this.
   */
  public function doPreApproval(&$params): void {
    // We set this here to allow the test to check what is set.
    \Civi::$statics[__CLASS__][__FUNCTION__] = $params;
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
    $trxn_id = CRM_Core_DAO::singleValueQuery("SELECT MAX(trxn_id) FROM civicrm_contribution WHERE trxn_id LIKE '{$string}_%'") ?? '';
    $trxn_id = str_replace($string, '', $trxn_id);
    $trxn_id = (int) $trxn_id + 1;
    return $string . '_' . $trxn_id . '_' . uniqid();
  }

}
