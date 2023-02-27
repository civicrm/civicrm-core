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

namespace Civi\Api4\Event\Subscriber;

use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Preprocess api autocomplete requests
 * @service
 * @internal
 */
class AutocompleteFieldSubscriber extends AutoService implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api.prepare' => ['onApiPrepare', -50],
    ];
  }

  /**
   * Apply any filters set in the schema for autocomplete fields
   *
   * In order for this to work, the `$fieldName` param needs to be in
   * the format `EntityName.field_name`. Anything not in that format
   * will be ignored, with the expectation that any extension making up
   * its own notation for identifying fields (e.g. Afform) can implement
   * its own `PrepareEvent` handler to do filtering. If their callback
   * runs earlier than this one, it can optionally `setFieldName` to the
   * standard recognized here to get the benefit of both custom filters
   * and the ones from the schema.
   * @see \Civi\Api4\Subscriber\AfformAutocompleteSubscriber::processAfformAutocomplete
   *
   * @param \Civi\API\Event\PrepareEvent $event
   */
  public function onApiPrepare(\Civi\API\Event\PrepareEvent $event): void {
    $apiRequest = $event->getApiRequest();
    if (is_object($apiRequest) && is_a($apiRequest, 'Civi\Api4\Generic\AutocompleteAction')) {
      [$entityName, $fieldName] = array_pad(explode('.', (string) $apiRequest->getFieldName(), 2), 2, '');

      if (!$fieldName) {
        return;
      }
      try {
        $fieldSpec = civicrm_api4($entityName, 'getFields', [
          'checkPermissions' => FALSE,
          'where' => [['name', '=', $fieldName]],
        ])->single();

        // Auto-add filters defined in schema
        foreach ($fieldSpec['input_attrs']['filter'] ?? [] as $key => $value) {
          $apiRequest->addFilter($key, $value);
        }

      }
      catch (\Exception $e) {
        // Ignore anything else. Extension using their own $fieldName notation can do their own handling.
      }
    }
  }

}
