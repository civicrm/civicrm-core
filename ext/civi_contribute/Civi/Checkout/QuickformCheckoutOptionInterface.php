<?php

namespace Civi\Checkout;

/**
 * FIXME: clarify what functions from CRM_Core_Payment are required for Quickform processing
 */
interface QuickformCheckoutOptionInterface {

  /**
   * Process payment
   *
   * @param array|PropertyBag $params
   *
   * @param string $component
   *
   * @return array
   *   Result array (containing at least the key payment_status_id)
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   *
   * @see \CRM_Core_Payment
   */
  public function doPayment(&$params, $component = 'contribute');

}
