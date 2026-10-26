<?php

namespace Civi\Order\Event;

class OrderSaveEvent extends OrderEvent {

  /**
   * The Contribution ID created for the Order. Will be NULL for preSave.
   *
   * @var int|null
   */
  private ?int $contributionID;

  /**
   * OrderSaveEvent constructor.
   *
   * @param \CRM_Financial_BAO_Order $order
   * @param string $action
   * @param int|null $contributionID
   */
  public function __construct(\CRM_Financial_BAO_Order $order, string $action, ?int $contributionID = NULL) {
    parent::__construct($order, $action);
    $this->contributionID = $contributionID;
  }

  public function getContributionID(): ?int {
    return $this->contributionID;
  }

}
