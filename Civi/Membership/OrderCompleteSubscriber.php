<?php
namespace Civi\Membership;

use Civi\Core\Service\AutoService;
use Civi\Core\Service\IsActiveTrait;
use Civi\Order\Event\OrderCompleteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class OrderCompleteSubscriber
 * @package Civi\Membership
 * @service civi_membership_order_complete
 *
 * This class provides the default behaviour for updating memberships on completion of contribution (On "Order Complete")
 */
class OrderCompleteSubscriber extends AutoService implements EventSubscriberInterface {

  use IsActiveTrait;

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return [
      'civi.order.complete' => ['onOrderComplete', 0],
    ];
  }

  /**
   * Default handler for Membership on "Order Complete"
   * Note that the "civi.order.complete" will trigger for all "Completed orders"
   *   so you should check if there is actually a membership to update.
   *
   * @param \Civi\Order\Event\OrderCompleteEvent $event
   *
   * @throws \CRM_Core_Exception
   */
  public function onOrderComplete(OrderCompleteEvent $event): void {
    if (!$this->isActive()) {
      return;
    }

    try {
      \CRM_Contribute_BAO_Contribution::updateMembershipBasedOnCompletionOfContribution($event->contributionID, $event->dateTodayForDatesCalculations);
    }
    catch (\Exception $e) {
      \Civi::log()->error('civi_membership_order_complete: Error updating membership for contributionID: ' . $event->contributionID . ': ' . $e->getMessage());
    }
  }

}
