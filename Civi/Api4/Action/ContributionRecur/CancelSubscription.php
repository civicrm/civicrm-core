<?php
namespace Civi\Api4\Action\ContributionRecur;

use Civi\Api4\ContributionRecur;
use Civi\Api4\Generic\BasicBatchAction;

/**
 * This API Action cancels the contributionRecur and payment processor subscription.
 *
 */
class CancelSubscription extends BasicBatchAction {

  /**
   * If the payment processor supports optional notification (cancelRecurringNotifyOptional)
   *   then setting this to FALSE will update the recurring contribution in CiviCRM but NOT
   *   notify the payment provider.
   *
   * @var bool
   */
  protected bool $notifyPaymentProcessor = TRUE;

  /**
   * Optional cancellation reason
   *
   * @var string
   */
  protected string $cancelReason = '';

  /**
   * @inheritDoc
   */
  public function doTask($item) {
    try {
      if (empty($item['payment_processor_id'])) {
        $message = ts('No payment processor for recurring contribution ID: %1', [1 => $item['id']]);
      }
      else {
        $paymentProcessor = \Civi\Payment\System::singleton()->getById($item['payment_processor_id']);
        if (!$paymentProcessor->supports('cancelRecurring')) {
          $message = ts('Payment processor does not support cancelling recurring contribution');
        }
        else {
          $propertyBag = new \Civi\Payment\PropertyBag();
          $propertyBag->setContributionRecurID($item['id']);
          $propertyBag->setRecurProcessorID($item['processor_id']);
          if ($paymentProcessor->supports('cancelRecurringNotifyOptional') && !$this->notifyPaymentProcessor) {
            $propertyBag->setIsNotifyProcessorOnCancelRecur(FALSE);
          }
          else {
            $propertyBag->setIsNotifyProcessorOnCancelRecur(TRUE);
          }
          $message = $paymentProcessor->doCancelRecurring($propertyBag)['message'] ?? '';
        }
      }
    }
    catch (\Throwable $t) {
      \Civi::log()->error(ts('Error cancelling recurring contribution ID: %1. Error: %2', [1 => $item['id'], 2 => $t->getMessage()]));
    }

    // Now update the actual recurring Contribution
    ContributionRecur::update(FALSE)
      ->addWhere('id', '=', $item['id'])
      ->setValues([
        'contribution_status_id:name' => 'Cancelled',
        'processor_message' => $message ?? NULL,
        'cancel_reason' => $this->cancelReason,
        'cancel_date' => 'now',
      ])->execute();
    return [
      'id' => $item['id'],
      'message' => $message ?? NULL,
    ];
  }

  protected function getSelect() {
    return [
      'id',
      'payment_processor_id',
      'processor_id',
    ];
  }

}
