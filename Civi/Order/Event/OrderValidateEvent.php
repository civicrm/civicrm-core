<?php

namespace Civi\Order\Event;

use Symfony\Contracts\EventDispatcher\Event;

class OrderValidateEvent extends Event {

  /**
   * @var array
   */
  private array $errors = [];

  /**
   * @var \CRM_Financial_BAO_Order
   */
  private \CRM_Financial_BAO_Order $order;

  /**
   * OrderValidateEvent constructor.
   *
   * @param \CRM_Financial_BAO_Order $order
   */
  public function __construct(\CRM_Financial_BAO_Order $order) {
    $this->order = $order;
  }

  /**
   * @param string $errorMsg
   */
  public function setError(string $errorMsg) {
    $this->errors[] = $errorMsg;
  }

  /**
   * @return array
   */
  public function getErrors(): array {
    return $this->errors;
  }

  public function getOrder(): \CRM_Financial_BAO_Order {
    return $this->order;
  }

}
