<?php

namespace Civi\Contribute\Service;

use Civi\Afform\Event\AfformPrefillEvent;
use Civi\Api4\Action\Afform\Prefill;
use Civi\Api4\Generic\Result;
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
 * Two events:
 *   civi.afform.prefill (priority -10):
 *     Per price-bearing entity - stash the flag keyed by entity name.
 *   civi.api.respond (priority 0):
 *     Per API call - inject stashed facts into Afform.prefill responses.
 *
 * Synthetic facts don't survive Submit::preprocessSubmittedValues; the
 * server-side enforcement re-checks the permission directly.
 *
 * @service civi.contribute.price_option_availability_publisher
 */
class PriceOptionAvailabilityPublisher extends AutoService implements EventSubscriberInterface {

  private const FLAG = PriceFieldUtils::RESTRICTED_OPTIONS_FLAG;

  private array $factsByRequest = [];

  public static function getSubscribedEvents(): array {
    return [
      'civi.afform.prefill' => ['onAfformPrefill', -10],
      'civi.api.respond' => ['onApiRespond', 0],
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

    $requestId = spl_object_id($event->getApiRequest());
    $this->factsByRequest[$requestId][$event->getEntityName()] = [
      self::FLAG => \CRM_Core_Permission::check('edit contributions'),
    ];
  }

  public function onApiRespond($event): void {
    $apiRequest = $event->getApiRequest();
    if (!($apiRequest instanceof Prefill)) {
      return;
    }
    $requestId = spl_object_id($apiRequest);
    $facts = $this->factsByRequest[$requestId] ?? NULL;
    unset($this->factsByRequest[$requestId]);

    if (!$facts) {
      return;
    }
    $response = $event->getResponse();
    if (!$response instanceof Result) {
      return;
    }

    // The Result is an ArrayObject - getArrayCopy + exchangeArray is the
    // supported mutation pattern.
    $values = $response->getArrayCopy();

    // Index existing response entries by entity name for quick lookup.
    // Entries are missing from the response when no record was loaded for
    // that entity (e.g. a fresh "create" form has no autofill).
    $indexByName = [];
    foreach ($values as $i => $entry) {
      if (isset($entry['name'])) {
        $indexByName[$entry['name']] = $i;
      }
    }

    foreach ($facts as $entityName => $entityFacts) {
      if (!isset($indexByName[$entityName])) {
        // Entity has no response entry - append one so the flag is present
        // on the first (created-from-scratch) record.
        $values[] = [
          'name' => $entityName,
          'values' => [['fields' => $entityFacts, 'joins' => []]],
        ];
        continue;
      }

      $i = $indexByName[$entityName];
      if (empty($values[$i]['values'])) {
        $values[$i]['values'] = [['fields' => [], 'joins' => []]];
      }
      foreach ($values[$i]['values'] as $idx => $record) {
        if (!isset($values[$i]['values'][$idx]['fields'])) {
          $values[$i]['values'][$idx]['fields'] = [];
        }
        foreach ($entityFacts as $field => $value) {
          $values[$i]['values'][$idx]['fields'][$field] = $value;
        }
      }
    }
    $response->exchangeArray($values);
  }

}
