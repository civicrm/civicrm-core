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
   * One of: 'create'|'edit'|'delete'
   *
   * @var string
   */
  private string $action;

  /**
   * OrderSaveEvent constructor.
   *
   * @param \CRM_Financial_BAO_Order $order
   * @param string $action
   * @param int|null $contributionID
   */
  public function __construct(\CRM_Financial_BAO_Order $order, string $action = 'create', ?int $contributionID = NULL) {
    $this->order = $order;
    if (!in_array($action, ['create', 'edit', 'delete'])) {
      throw new \CRM_Core_Exception('OrderSaveEvent: Action must be one of create|edit|delete');
    }
    $this->action = $action;
    $this->contributionID = $contributionID;
  }

  public function getOrder(): \CRM_Financial_BAO_Order {
    return $this->order;
  }

  public function getAction(): string {
    return $this->action;
  }

  public function getContributionID(): ?int {
    return $this->contributionID;
  }

}
