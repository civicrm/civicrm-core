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

use Civi\Core\Attribute\CiviEventListener;
use Civi\Core\Event\GenericHookEvent;

class OptionValueAutocompleteProvider {

  #[CiviEventListener(event: 'civi.api.prepare', priority: 200)]
  public function onApiPrepare(\Civi\API\Event\PrepareEvent $event): void {
    $apiRequest = $event->getApiRequest();
    if (is_object($apiRequest) && is_a($apiRequest, 'Civi\Api4\Generic\AutocompleteAction')) {
      if ($apiRequest->getEntityName() === 'OptionValue') {
        $optionGroup = $apiRequest->getFilters()['option_group_id'] ?? NULL;
        // Always accept this filter if the field doesn't already specify it (if the field does specify it, e.g. custom fields always do,
        // then it will override this in AutocompleteFieldSubscriber::onApiPrepare)
        if ($optionGroup) {
          $apiRequest->addFilter('option_group_id', $optionGroup);
        }
      }
    }
  }

  /**
   * Provide default SearchDisplay for Country autocompletes
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  #[CiviEventListener(event: 'civi.search.defaultDisplay')]
  public static function on_civi_search_defaultDisplay(GenericHookEvent $e) {
    if ($e->display['settings'] || $e->display['type'] !== 'autocomplete' || $e->savedSearch['api_entity'] !== 'OptionValue') {
      return;
    }
    $e->display['settings'] = [
      'sort' => [
        ['weight', 'ASC'],
        ['label', 'ASC'],
      ],
      'extra' => ['color' => 'color'],
      'columns' => [
        [
          'type' => 'field',
          'key' => 'label',
          'icons' => [
            ['field' => 'icon'],
          ],
        ],
        [
          'type' => 'field',
          'key' => 'description',
        ],
      ],
    ];
  }

}
