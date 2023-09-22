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

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\HookInterface;

/**
 * @service
 * @internal
 */
class StateProvinceAutocompleteProvider extends \Civi\Core\Service\AutoService implements HookInterface {

  /**
   * Provide default SearchDisplay for StateProvince autocompletes
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public static function on_civi_search_defaultDisplay(GenericHookEvent $e) {
    if ($e->display['settings'] || $e->display['type'] !== 'autocomplete' || $e->savedSearch['api_entity'] !== 'StateProvince') {
      return;
    }
    $e->display['settings'] = [
      'sort' => [
        ['name', 'ASC'],
      ],
      'columns' => [
        [
          'type' => 'field',
          'key' => 'name',
        ],
        [
          'type' => 'field',
          'key' => 'country_id.name',
        ],
      ],
    ];
  }

}
