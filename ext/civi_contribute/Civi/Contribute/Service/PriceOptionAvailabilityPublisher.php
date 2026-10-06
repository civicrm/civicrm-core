<?php

namespace Civi\Contribute\Service;

use Civi\Afform\Event\AfformPrefillEvent;
use Civi\Contribute\Utils\PriceFieldUtils;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Publishes `has_all_price_options` onto each price-bearing entity in the
 * Afform.prefill response so the client-side af-if can decide whether to show
 * admin-visibility (non-public) price options.
 *
 * The flag is purely permission-derived: TRUE when the current user holds
 * 'edit contributions' (the same gate core QuickForm uses in
 * CRM_Contribute_Form_Contribution_Main::buildPriceSet), FALSE otherwise. It
 * is computed server-side, so the browser cannot assert it - and even if it
 * were tampered with, the option is only revealed client-side; the authoritative
 * check is CreateContribution::getLineItemsForRecord, which re-checks the
 * permission and rejects a restricted value regardless.
 *
 * Synthetic facts don't survive Submit::preprocessSubmittedValues; the
 * server-side enforcement re-checks the permission directly.
 *
 * @service civi.contribute.price_option_availability_publisher
 */
class PriceOptionAvailabilityPublisher extends AutoService implements EventSubscriberInterface {

  private const FLAG = PriceFieldUtils::RESTRICTED_OPTIONS_FLAG;

  public static function getSubscribedEvents(): array {
    return [
      'civi.afform.prefill' => ['onAfformPrefill', -10],
    ];
  }

  public function onAfformPrefill(AfformPrefillEvent $event): void {
    // Nothing to reveal if there are no admin-visibility options anywhere.
    if (!PriceFieldUtils::getRestrictedPriceFieldValueIds()) {
      return;
    }
    // Only price-bearing entities carry these options.
    if (!in_array($event->getEntityType(), PriceFieldUtils::getEnabledEntities(), TRUE)) {
      return;
    }

    $event->setValue(self::FLAG, \CRM_Core_Permission::check('edit contributions'));
  }

}
