<?php
namespace Civi\Membership;

use Civi\Core\Service\AutoSubscriber;
use Civi\Order\Event\OrderCompleteEvent;

/**
 * Class OrderCompleteSubscriber
 * @package Civi\Membership
 *
 * This class handles the smarty processing of tokens.
 */
class OrderCompleteSubscriber extends AutoSubscriber {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return [
      'civi.order.complete' => ['onOrderComplete', 0],
    ];
  }

  /**
   * Apply the various CRM_Utils_Token helpers.
   *
   * @param \Civi\Order\Event\OrderCompleteEvent $event
   */
  public function onOrderComplete(OrderCompleteEvent $event): void {
    \CRM_Contribute_BAO_Contribution::updateMembershipBasedOnCompletionOfContribution($event->contributionID, $event->dateTodayForDatesCalculations);
  }

}
