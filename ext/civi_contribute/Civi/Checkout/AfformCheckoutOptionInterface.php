<?php

namespace Civi\Checkout;

use Civi\Afform\Event\AfformValidateEvent;

/**
 * AfformCheckoutInterface
 */
interface AfformCheckoutOptionInterface {

  /**
   * Respond to validation event. At this point this will be
   * an AfformValidateEvent - but leaving open for handling other
   * event types in future
   *
   * @param Civi\Afform\Event\AfformValidateEvent $event
   */
  public function validate(AfformValidateEvent $event): void;

  /**
   * Provide settings to afCheckout
   *
   * Typically this will include `template` referencing a .html partial
   *
   * May also include payment processor specific data - e.g. public client key
   *
   * @return mixed[]
   */
  public function getAfformSettings(bool $testMode): array;

  /**
   * @return ?string name of an angular module required to use this CheckoutOption with Afform, if any
   */
  public function getAfformModule(): ?string;

}
