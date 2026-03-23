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
 * Ideally as much handling as possible is independent of the frontend form layer (quickform / afform / ...)
 * and works through CheckoutSession and/or data stored on the Contribution record (and joined entities)
 *
 * Additional optional interface AfformCheckoutOptionInterface provides functions to integrate with Afform
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
  public function getPaymentProcessorId(): ?int;

  /**
   * Initiate a new checkout
   */
  public function startCheckout(CheckoutSession $session): void;

  /**
   * Take user to the next step. May redirect or update the status of
   * the contribution.
   */
  public function continueCheckout(CheckoutSession $session): void;

}
