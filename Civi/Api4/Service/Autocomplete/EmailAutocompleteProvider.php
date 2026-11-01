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

class EmailAutocompleteProvider {

  /**
   * Provide default SearchDisplay for Email autocompletes
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  #[CiviEventListener(event: 'civi.search.defaultDisplay')]
  public static function on_civi_search_defaultDisplay(GenericHookEvent $e) {
    if ($e->display['settings'] || $e->display['type'] !== 'autocomplete' || $e->savedSearch['api_entity'] !== 'Email') {
      return;
    }
    $e->display['settings'] = [
      'sort' => [
        ['contact_id.sort_name', 'ASC'],
        ['email', 'ASC'],
      ],
      'columns' => [
        [
          'type' => 'field',
          'key' => 'contact_id.sort_name',
          'rewrite' => '[contact_id.sort_name] <[email]>',
          'empty_value' => '[email]',
        ],
        [
          'type' => 'field',
          'key' => 'is_primary',
          'rewrite' => '[location_type_id:label]{if $is_primary} ({ts}primary{/ts}){/if}',
        ],
      ],
    ];
  }

}
