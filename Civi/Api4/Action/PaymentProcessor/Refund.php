<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Action\PaymentProcessor;

/**
 * PaymentProcessor refund action
 */
class Refund extends \Civi\Api4\Generic\AbstractAction {

  /**
   * The Payment Processor ID
   *
   * @var int
   * @required
   */
  protected int $paymentProcessorID;

  /**
   * The amount to refund
   *
   * @var float
   * @required
   */
  protected float $amountToRefund;

  /**
   * The currency of the amount to refund (Optional)
   *
   * @var string
   */
  protected string $currency = '';

  /**
   * The payment processor transaction ID
   * Required by most payment processors
   *
   * @var string
   * @required
   */
  protected string $transactionID;

  /**
   * @see \Civi\Api4\Generic\AbstractEntity::permissions()
   * @return array
   */
  public static function permissions() {
    return [
      'refund' => ['refund contributions'],
    ];
  }

  public function _run(\Civi\Api4\Generic\Result $result) {
    /** @var \CRM_Core_Payment $processor */
    $processor = \Civi\Payment\System::singleton()->getById($this->paymentProcessorID);
    if (!$processor->supportsRefund()) {
      throw new \CRM_Core_Exception('Payment Processor does not support refund');
    }
    $refundParams['amount'] = $this->amountToRefund;
    $refundParams['trxn_id'] = $this->transactionID;
    // With a trxn_id we often don't need currency at all. But some payment processors use it for formatting the requested amount etc.
    if (!empty($this->currency)) {
      $refundParams['currency'] = $this->currency;
    }

    $result->exchangeArray($processor->doRefund($refundParams) ?? []);
    return $result;
  }

}
