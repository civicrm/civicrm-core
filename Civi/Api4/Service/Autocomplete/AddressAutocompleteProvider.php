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
class AddressAutocompleteProvider extends \Civi\Core\Service\AutoService {

  /**
   * Provide default SearchDisplay for Address autocompletes
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  #[CiviEventListener(event: 'civi.search.defaultDisplay')]
  public static function on_civi_search_defaultDisplay(GenericHookEvent $e) {
    if ($e->display['settings'] || $e->display['type'] !== 'autocomplete' || $e->savedSearch['api_entity'] !== 'Address') {
      return;
    }
    $e->display['settings'] = [
      'sort' => [
        ['street_address', 'ASC'],
      ],
      'columns' => [
        [
          'type' => 'field',
          'key' => 'street_address',
          'rewrite' => '[street_address], [city]',
          'empty_value' => '[city]',
        ],
        [
          'type' => 'field',
          'key' => 'state_province_id.name',
          'rewrite' => '[state_province_id.name], [country_id.name]',
          'empty_value' => '[country_id.name]',
        ],
        [
          'type' => 'field',
          'key' => 'id',
          'rewrite' => '#[id]',
        ],
      ],
    ];
  }

}
