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
class GrantAutocompleteProvider extends \Civi\Core\Service\AutoService implements HookInterface {

  /**
   * Provide default SavedSearch for Grant autocompletes
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public static function on_civi_search_autocompleteDefault(GenericHookEvent $e) {
    if (!is_array($e->savedSearch) || $e->savedSearch['api_entity'] !== 'Grant') {
      return;
    }
    $e->savedSearch['api_params'] = [
      'version' => 4,
      'select' => [
        'id',
        'contact_id.display_name',
        'grant_type_id:label',
        'financial_type_id:label',
        'status_id:label',
      ],
      'orderBy' => [],
      'where' => [],
      'groupBy' => [],
      'join' => [],
      'having' => [],
    ];
  }

  /**
   * Provide default SearchDisplay for Grant autocompletes
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public static function on_civi_search_defaultDisplay(GenericHookEvent $e) {
    if ($e->display['settings'] || $e->display['type'] !== 'autocomplete' || $e->savedSearch['api_entity'] !== 'Grant') {
      return;
    }
    $e->display['settings'] = [
      'sort' => [
        ['contact_id.sort_name', 'ASC'],
        ['application_received_date', 'DESC'],
      ],
      'columns' => [
        [
          'type' => 'field',
          'key' => 'contact_id.display_name',
          'rewrite' => '[contact_id.display_name] - [grant_type_id:label]',
        ],
        [
          'type' => 'field',
          'key' => 'financial_type_id:label',
          'rewrite' => '#[id] [status_id:label]',
        ],
        [
          'type' => 'field',
          'key' => 'financial_type_id:label',
        ],
      ],
    ];
  }

}
