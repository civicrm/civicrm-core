<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Core_Payment_Manual extends CRM_Core_Payment {

  protected $result;

  /**
   * This function checks to see if we have the right config values.
   */
  public function checkConfig() {}

  /**
   * Get billing fields required for this processor.
   *
   * We apply the existing default of returning fields only for payment processor type 1. Processors can override to
   * alter.
   *
   * @param int $billingLocationID
   *
   * @return array
   */
  public function getBillingAddressFields($billingLocationID = NULL) {
    if (!$billingLocationID) {
      // Note that although the billing id is passed around the forms the idea that it would be anything other than
      // the result of the function below doesn't seem to have eventuated.
      // So taking this as a param is possibly something to be removed in favour of the standard default.
      $billingLocationID = CRM_Core_BAO_LocationType::getBilling();
    }

    // Only handle pseudo-profile billing for now.
    if ($this->billingProfile == 'billing') {
      // @todo - use profile api to retrieve this - either as pseudo-profile or (better) set up billing
      // as a reserved profile in the DB and (even better) allow the profile to be selected
      // on the form instead of just 'billing for pay=later bool'
      return array(
        'first_name' => 'billing_first_name',
        'middle_name' => 'billing_middle_name',
        'last_name' => 'billing_last_name',
        'street_address' => "billing_street_address-{$billingLocationID}",
        'city' => "billing_city-{$billingLocationID}",
        'country' => "billing_country_id-{$billingLocationID}",
        'state_province' => "billing_state_province_id-{$billingLocationID}",
        'postal_code' => "billing_postal_code-{$billingLocationID}",
      );
    }
    else {
      return array();
    }
  }

  /**
   * Get array of fields that should be displayed on the payment form.
   *
   * @return array
   */
  public function getPaymentFormFields() {
    if (!$this->isBackOffice()) {
      return array();
    }

    $paymentInstrument = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', $this->getPaymentInstrumentID());
    if ($paymentInstrument === 'Credit Card') {
      return array('credit_card_type', 'pan_truncation');
    }
    elseif ($paymentInstrument === 'Check') {
      return array('check_number');
    }
    return array();
  }

  /**
   * Process payment.
   *
   * The function ensures an exception is thrown & moves some of this logic out of the form layer and makes the forms
   * more agnostic.
   *
   * @param array $params
   *
   * @param string $component
   *
   * @return array
   *   Result array
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function doPayment(&$params, $component = 'contribute') {
    $params['payment_status_id'] = $this->getResult();
    return $params;
  }

  /**
   * Get the result of the payment.
   *
   * Usually this will be pending but the calling layer has a chance to set the result.
   *
   * This would apply in particular when the form accepts status id.
   *
   * Note that currently this payment class is only being used to manage the 'billing block' aspect
   * of pay later. However, a longer term idea is that by treating 'pay-later' as 'just another processor'
   * will allow code simplification.
   *
   * @return int
   */
  protected function getResult() {
    if (!$this->result) {
      $this->setResult(CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'status_id', 'Pending'));
    }
    return $this->result;
  }

  /**
   * Set the result to be returned.
   *
   * This would be set from outside the function where we want to pass on the status from the form.
   *
   * @param int $result
   */
  public function setResult($result) {
    $this->result = $result;
  }

  /**
   * Set payment instrument id.
   *
   * @param int $paymentInstrumentID
   */
  public function setPaymentInstrumentID($paymentInstrumentID) {
    $this->paymentInstrumentID = $paymentInstrumentID;
  }

  /**
   * Get the name of the payment type.
   *
   * @return string
   */
  public function getPaymentTypeName() {
    return 'pay-later';
  }

  /**
   * Get the name of the payment type.
   *
   * @return string
   */
  public function getPaymentTypeLabel() {
    return CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', $this->getPaymentInstrumentID());
  }

  /**
   * Declare that more than one payment can be processed at once.
   *
   * @return bool
   */
  protected function supportsMultipleConcurrentPayments() {
    return TRUE;
  }

  /**
   * Checks if backoffice recurring edit is allowed
   *
   * @return bool
   */
  public function supportsEditRecurringContribution() {
    return TRUE;
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
   * Submit a manual payment.
   *
   * @param array $params
   *   Assoc array of input parameters for this transaction.
   *
   * @return array
   */
  public function doDirectPayment(&$params) {
    $statuses = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id');
    if ($params['is_pay_later']) {
      $result['payment_status_id'] = array_search('Pending', $statuses);
    }
    else {
      $result['payment_status_id'] = array_search('Completed', $statuses);
    }
    return $result;
  }

  /**
   * Should a receipt be sent out for a pending payment.
   *
   * e.g for traditional pay later & ones with a delayed settlement a pending receipt makes sense.
   */
  public function isSendReceiptForPending() {
    return TRUE;
  }

  /**
   * Get help text information (help, description, etc.) about this payment,
   * to display to the user.
   *
   * @param string $context
   *   Context of the text.
   *   Only explicitly supported contexts are handled without error.
   *   Currently supported:
   *   - contributionPageRecurringHelp (params: is_recur_installments, is_email_receipt)
   *
   * @param array $params
   *   Parameters for the field, context specific.
   *
   * @return string
   */
  public function getText($context, $params) {
    switch ($context) {
      case 'contributionPageContinueText':
        if ($params['amount'] <= 0) {
          return ts('To complete this transaction, click the <strong>Continue</strong> button below.');
        }
        return ts('To complete your contribution, click the <strong>Continue</strong> button below.');

    }
  }

}
