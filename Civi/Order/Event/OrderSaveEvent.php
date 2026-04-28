<?php

namespace Civi\Order\Event;

use Symfony\Contracts\EventDispatcher\Event;

class OrderSaveEvent extends Event {

  /**
   * @var \CRM_Financial_BAO_Order
   */
  private \CRM_Financial_BAO_Order $order;

  /**
   * The Contribution ID created for the Order. Will be NULL for preSave.
   *
   * @var int|null
   */
  private ?int $contributionID = NULL;

  /**
   * OrderSaveEvent constructor.
   *
   * @param \CRM_Financial_BAO_Order $order
   * @param int|null $contributionID
   */
  public function __construct(\CRM_Financial_BAO_Order $order, ?int $contributionID = NULL) {
    $this->order = $order;
    $this->contributionID = $contributionID;
  }

  public function getOrder(): \CRM_Financial_BAO_Order {
    return $this->order;
  }

  public function getContributionID(): ?int {
    return $this->contributionID;
  }

}
