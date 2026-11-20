<?php

namespace Civi\Order\Event;

use Civi\Core\Event\GenericHookEvent;

/**
 * Event fired after the default tax rate of a line item has been determined
 * from its financial type, allowing extensions to change it.
 *
 * Listeners can only change the taxes through setTaxRate() and setTaxDetails().
 * The line item itself is read-only.
 */
class AlterTaxRateEvent extends GenericHookEvent {

  private array $lineItem;

  private ?int $contactID;

  /**
   * Rate defined by the financial type, before any listener.
   */
  private float $defaultTaxRate;

  /**
   * Current overall rate, as a percentage.
   */
  private float $taxRate;

  /**
   * Optional breakdown, as a list of ['name' => string, 'rate' => float].
   * Empty unless a listener sets it.
   */
  private array $taxDetails = [];

  public function __construct(array $lineItem, ?int $contactID, float $defaultTaxRate) {
    $this->lineItem = $lineItem;
    $this->contactID = $contactID;
    $this->defaultTaxRate = $defaultTaxRate;
    $this->taxRate = $defaultTaxRate;
  }

  public function getLineItem(): array {
    return $this->lineItem;
  }

  public function getContactID(): ?int {
    return $this->contactID;
  }

  public function getTaxRate(): float {
    return $this->taxRate;
  }

  public function getTaxDetails(): array {
    return $this->taxDetails;
  }

  /**
   * Set a single tax rate, replacing any existing details.
   *
   * Core will generate a single default tax entry from this rate.
   * Use setTaxDetails() instead when several taxes apply.
   *
   * @param float $taxRate
   *   Percentage, e.g. 14.975.
   */
  public function setTaxRate(float $taxRate): void {
    self::assertValidRate($taxRate);
    $this->taxRate = $taxRate;
    $this->taxDetails = [];
  }

  /**
   * Set the list of taxes. The overall tax rate becomes the sum of their rates.
   *
   * @param array $taxDetails
   *   List of ['name' => string, 'rate' => float].
   *
   * @throws \CRM_Core_Exception
   */
  public function setTaxDetails(array $taxDetails): void {
    $clean = [];
    $total = 0.0;
    foreach ($taxDetails as $tax) {
      if (empty($tax['name']) || !isset($tax['rate']) || !is_numeric($tax['rate'])) {
        throw new \CRM_Core_Exception('Each tax requires a name and a numeric rate.');
      }
      $rate = (float) $tax['rate'];
      self::assertValidRate($rate);
      $clean[] = ['name' => (string) $tax['name'], 'rate' => $rate];
      $total += $rate;
    }
    self::assertValidRate($total);
    $this->taxDetails = $clean;
    $this->taxRate = $total;
  }

  private static function assertValidRate(float $rate): void {
    if ($rate < 0 || $rate > 100) {
      throw new \CRM_Core_Exception("Invalid tax rate $rate.");
    }
  }

}