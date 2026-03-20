<?php

namespace Civi\Checkout;

use Civi\Core\Event\GenericHookEvent;

/**
 * Optional base class for implementing CheckoutOptionInterface
 */
abstract class CheckoutOption implements CheckoutOptionInterface {

  abstract public function getLabel(): string;

  public function getFrontendLabel(): string {
    return $this->getLabel();
  }

  public function getPaymentMethod(): ?string {
    return NULL;
  }

  public function getPaymentProcessorId(bool $testMode = FALSE): ?int {
    return NULL;
  }

  public function validate(GenericHookEvent $e): void {
    // no default validation rules
  }

  /**
   * NOTE: this is a very naive implementation as a starting point
   * for adapting CRM_Core_Payment implementations.
   */
  public function startCheckout(CheckoutSession $session): void {
    $contributionId = $session->getContributionId();
    $processor = $this->getQuickformProcessor($session->isTestMode());
    $processor->doPayment(['contributionID' => $contributionId]);
  }

  public function continueCheckout(CheckoutSession $session): void {
    // some payment processor happen in one shot, in which case
    // this can be empty
  }

  /**
   * By default fetch using getPaymentProcessorId. This will work well for
   * legacy payment processors where 1 PaymentProcessor = 1 Checkout Option
   *
   * A new integration may offer multiple CheckoutOptions for a single
   * PaymentProcessor record, which may return different classes here
   */
  public function getQuickformProcessor(bool $testMode = FALSE): ?\CRM_Core_Payment {
    $id = $this->getPaymentProcessorId($testMode);
    return $id ? \Civi\Payment\System::singleton()->getById($id) : NULL;
  }

  public function getAfformSettings(bool $testMode): ?array {
    return NULL;
  }

  public function getAfformModule(): ?string {
    return NULL;
  }

}
