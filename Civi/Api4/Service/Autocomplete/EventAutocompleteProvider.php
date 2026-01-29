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

namespace Civi\Api4\Service\Autocomplete;

use Civi\API\Events;
use Civi\Core\Event\GenericHookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service
 * @internal
 */
class EventAutocompleteProvider extends \Civi\Core\Service\AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents(): array {
    return [
      'civi.api.prepare' => ['onApiPrepare', 140],
      'civi.search.defaultDisplay' => ['alterDefaultDisplay', Events::W_LATE],
    ];
  }

  /**
   * Add is_template filter to event template autocompletes
   * @param \Civi\API\Event\PrepareEvent $event
   */
  public function onApiPrepare(\Civi\API\Event\PrepareEvent $event): void {
    $apiRequest = $event->getApiRequest();
    if (is_object($apiRequest) && is_a($apiRequest, 'Civi\Api4\Generic\AutocompleteAction')) {
      [$entityName, $fieldName] = array_pad(explode('.', (string) $apiRequest->getFieldName(), 2), 2, '');
      $entityName = !empty($entityName) ? $entityName : $apiRequest->getEntityName();
      if (($entityName === 'Event' && $fieldName === 'id') || $fieldName === 'event_id') {
        $showTemplates = $fieldName === 'template_id';
        $apiRequest->addFilter('is_template', $showTemplates);
      }
    }
  }

  /**
   * Alter default display of events based on the is_template filter.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public static function alterDefaultDisplay(GenericHookEvent $e) {
    if ($e->display['type'] !== 'autocomplete' || $e->savedSearch['api_entity'] !== 'Event') {
      return;
    }
    $filters = $e->context['filters'] ?? [];
    if (!empty($filters['is_template'])) {
      $e->display['settings']['columns'][0]['key'] = 'template_title';
    }
  }

}
