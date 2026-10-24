<?php

namespace Civi\Order\Event;

use Symfony\Contracts\EventDispatcher\Event;

abstract class OrderEvent extends Event {

  /**
   * @var \CRM_Financial_BAO_Order
   */
  private \CRM_Financial_BAO_Order $order;

  /**
   * One of create|edit|delete
   * May allow custom actions (eg. place|dispatch) in the future
   *
   * @var string
   */
  private string $action;

  /**
   * OrderValidateEvent constructor.
   *
   * @param \CRM_Financial_BAO_Order $order
   * @param string $action
   */
  public function __construct(\CRM_Financial_BAO_Order $order, string $action = 'create') {
    $this->order = $order;
    $this->action = $action;
  }

  /**
   * Get the Order object
   *
   * @return \CRM_Financial_BAO_Order
   */
  public function getOrder(): \CRM_Financial_BAO_Order {
    return $this->order;
  }

  /**
   * Get the Order action
   * One of create|edit|delete
   * May allow custom actions (eg. place|dispatch) in the future
   *
   * @return string
   */
  public function getAction(): string {
    return $this->action;
  }

}
