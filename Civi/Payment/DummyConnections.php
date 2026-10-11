<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Payment;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoService;
use Civi\Payment\CheckoutOption\DummyCheckoutOption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Registers CheckoutOptions for all Dummy payment processor instances.
 *
 * @service civi.payment.dummy_connections
 */
class DummyConnections extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents(): array {
    return [
      'civi.checkout.options' => 'getCheckoutOptions',
    ];
  }

  public function getCheckoutOptions(GenericHookEvent $e): void {
    foreach ($this->getPaymentProcessorPairs() as $name => $pair) {
      $e->options['dummy_' . $name] = new DummyCheckoutOption($pair['live'], $pair['test']);
    }
  }

  private function getPaymentProcessorPairs(): array {
    // The "is_test" clause isn't a no-op, API4 default is to ignore test processors.
    $all = \Civi\Api4\PaymentProcessor::get(FALSE)
      ->addWhere('payment_processor_type_id:name', '=', 'Dummy')
      ->addWhere('is_test', 'IN', [TRUE, FALSE])
      ->execute();

    $pairs = [];
    foreach ($all as $processor) {
      $pairs[$processor['name']][$processor['is_test'] ? 'test' : 'live'] = $processor;
    }
    return $pairs;
  }

}
