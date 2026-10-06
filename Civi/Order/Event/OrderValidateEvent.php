<?php

namespace Civi\Order\Event;

/**
 * OrderValidateEvent: Triggered when validating an Order.
 *
 * The Order is "readonly". You have access to all values that would be submitted/calculated
 *   via getContributionValues(), getContributionRecurValues() and getLineItems().
 * To check if other listeners have already added errors call getErrors().
 * To add errors call addError() or setErrors().
 */
class OrderValidateEvent {

  /**
   * @var array
   */
  private array $errors = [];

  /**
   * Array of submitted and calculated Contribution values
   *
   * @var array
   */
  private array $contributionValues;

  /**
   *  Array of submitted and calculated ContributionRecur values
   *
   * @var array
   */
  private array $contributionRecurValues;

  /**
   * Array of submitted and calculated lineItem values
   *
   * @var array
   */
  private array $lineItems;

  /**
   * OrderValidateEvent constructor.
   *
   * @param array $contributionValues
   * @param array $contributionRecurValues
   * @param array $lineItems
   */
  public function __construct(array $contributionValues, array $contributionRecurValues, array $lineItems) {
    $this->contributionValues = $contributionValues;
    $this->contributionRecurValues = $contributionRecurValues;
    $this->lineItems = $lineItems;
  }

  /**
   * @return array
   */
  public function getContributionValues(): array {
    return $this->contributionValues;
  }

  /**
   * @return array
   */
  public function getContributionRecurValues(): array {
    return $this->contributionRecurValues;
  }

  /**
   * @return array
   */
  public function getLineItems(): array {
    return $this->lineItems;
  }

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
