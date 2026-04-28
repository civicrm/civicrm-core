<?php

namespace Civi\Order\Event;

class OrderValidateEvent extends OrderEvent {

  /**
   * @var array
   */
  private array $errors = [];

  /**
   * Add an error
   *
   * @param string $errorMsg
   *
   * @return void
   */
  public function addError(string $errorMsg): void {
    $this->errors[] = $errorMsg;
  }

  /**
   * Replace all existing errors with the specified array
   *
   * @param array $errors
   *
   * @return void
   */
  public function setErrors(array $errors): void {
    $this->errors = $errors;
  }

  /**
   * Get all errors that have been set by other callers
   *
   * @return array
   */
  public function getErrors(): array {
    return $this->errors;
  }

}
