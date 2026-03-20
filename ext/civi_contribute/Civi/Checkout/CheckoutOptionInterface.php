<?php

namespace Civi\Checkout;

/**
 * Checkout Options represent the user-facing side of a PaymentProcessor
 *
 * The Stripe integration provides a good example:
 * - the user sets up a connection to Stripe with credentials => this is saved in
 *   PaymentProcessor record
 * - this single Stripe PaymentProcessor record can provide multiple CheckoutOptions: for Stripe, Stripe Checkout, Stripe Embedded Checkout etc
 *
 * Alternative the new Paypal extension provides a CheckoutOption without having
 * a PaymentProcessor record at all (connection details are stored in custom JSON object
 * - though this is only a transitional step and may change in future)
 *
 * Finally Pay Later is a checkout option which doesn't require a PaymentProcessor record
 * at all
 *
 * Methods on the Checkout Option handle the interaction between user and payment processor
 * during a CheckoutSession.
 *
 * Ideally this handling is independent of the frontend form layer (quickform / afform / ...)
 * and works off of data stored on the Contribution record (and joined entities) and
 * whatever transient "checkout_params" the Option/PaymentProcessor needs
 */
interface CheckoutOptionInterface {

  public function getLabel(): string;

  public function getFrontendLabel(): string;

  /**
   * @return ?string name of associated Payment Method/Instrument, if any
   */
  public function getPaymentMethod(): ?string;

  /**
   * @return ?int id of associated PaymentProcessor record, if any
   */
  public function getPaymentProcessorId(bool $testMode = FALSE): ?int;

  /**
   * Respond to validation event. At this point this will be
   * an AfformValidateEvent - but leaving open for handling other
   * event types in future
   *
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  public function validate(GenericHookEvent $event): void;

  /**
   * Initiate a new checkout
   */
  public function startCheckout(CheckoutSession $session): void;

  /**
   * Take user to the next step. May redirect or update the status of
   * the contribution.
   */
  public function continueCheckout(CheckoutSession $session): void;

  /**
   * @return ?\CRM_Core_Payment Quickform Processor class, if any
   */
  public function getQuickformProcessor(bool $testMode = FALSE): ?\CRM_Core_Payment;

  /**
   * For CheckoutOptions that support Afform, provide the necessary details
   *
   * Typically this will include `template` referencing a .html partial
   *
   * May also include payment processor specific keys - e.g. public client key
   *
   * @return ?mixed[] configuration for afCheckout integration, if any
   */
  public function getAfformSettings(bool $testMode): ?array;

  /**
   * @return ?string name of an angular module required to use this CheckoutOption with Afform, if any
   */
  public function getAfformModule(): ?string;

}
