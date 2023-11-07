<?php

/**
 * Trait implements functions to retrieve payment processor related values.
 *
 * Note that any functions on this class that are supported to be used from
 * outside of core are specifically tagged.
 */
trait CRM_Financial_Form_PaymentProcessorFormTrait {

  /**
   * Get the payment processors that are available on the form.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getAvailablePaymentProcessors(): array {
    return CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors([ucfirst($this->getPaymentProcessorMode()) . 'Mode'], $this->getAvailablePaymentProcessorIDS());
  }

  /**
   * Get the payment processor IDs available on the form.
   *
   * @return false|array
   */
  protected function getAvailablePaymentProcessorIDS() {
    return FALSE;
  }

  /**
   * Get the mode (test or live) of the payment processor.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @return string|null
   *   test or live
   * @throws \CRM_Core_Exception
   */
  public function getPaymentProcessorMode(): ?string {
    return CRM_Utils_Request::retrieve('mode', 'Alphanumeric', $this);
  }

  /**
   * Get the payment processor object for the submission, returning the manual one for offline payments.
   *
   * @return CRM_Core_Payment
   */
  protected function getPaymentProcessorObject() {
    if (!empty($this->_paymentProcessor)) {
      return $this->_paymentProcessor['object'];
    }
    return new CRM_Core_Payment_Manual();
  }

}
