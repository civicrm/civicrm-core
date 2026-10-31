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

/**
 * @service
 * @internal
 */
class EntityAutocompleteProvider extends \Civi\Core\Service\AutoService {

  /**
   * Provide default SearchDisplay for autocompletes of API Entity names
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  #[CiviEventListener(event: 'civi.search.defaultDisplay')]
  public static function on_civi_search_defaultDisplay(GenericHookEvent $e) {
    if ($e->display['settings'] || $e->display['type'] !== 'autocomplete' || $e->savedSearch['api_entity'] !== 'Entity') {
      return;
    }
    $e->display['settings'] = [
      'sort' => [
        ['title_plural', 'ASC'],
      ],
      'columns' => [
        [
          'type' => 'field',
          'key' => 'title_plural',
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
