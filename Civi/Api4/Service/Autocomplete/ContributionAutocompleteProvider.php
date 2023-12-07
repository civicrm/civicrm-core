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
class ContributionAutocompleteProvider extends \Civi\Core\Service\AutoService implements HookInterface {

  /**
   * Provide default SavedSearch for Contribution autocompletes
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public static function on_civi_search_autocompleteDefault(GenericHookEvent $e) {
    if (!is_array($e->savedSearch) || $e->savedSearch['api_entity'] !== 'Contribution') {
      return;
    }
    $e->savedSearch['api_params'] = [
      'version' => 4,
      'select' => [
        'id',
        'contact_id.sort_name',
        'total_amount',
        'receive_date',
        'financial_type_id:label',
        'contribution_status_id:label',
      ],
      'orderBy' => [],
      'where' => [],
      'groupBy' => [],
      'join' => [],
      'having' => [],
    ];
  }

  /**
   * Provide default SearchDisplay for Contribution autocompletes
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public static function on_civi_search_defaultDisplay(GenericHookEvent $e) {
    if ($e->display['settings'] || $e->display['type'] !== 'autocomplete' || $e->savedSearch['api_entity'] !== 'Contribution') {
      return;
    }
    $e->display['settings'] = [
      'sort' => [
        ['contact_id.sort_name', 'ASC'],
        ['total_amount', 'ASC'],
        ['receive_date', 'DESC'],
      ],
      'columns' => [
        [
          'type' => 'field',
          'key' => 'contact_id.sort_name',
          'rewrite' => '[contact_id.sort_name] - [total_amount]',
        ],
        [
          'type' => 'field',
          'key' => 'financial_type_id:label',
          'rewrite' => '#[id] [financial_type_id:label]',
        ],
        [
          'type' => 'field',
          'key' => 'receive_date',
          'rewrite' => '[contribution_status_id:label] [receive_date]',
        ],
      ],
    ];
  }

}
