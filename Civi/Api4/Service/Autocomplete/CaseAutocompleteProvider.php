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

use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\HookInterface;

/**
 * @service
 * @internal
 */
class CaseAutocompleteProvider extends \Civi\Core\Service\AutoService implements HookInterface {

  /**
   * Provide default SavedSearch for Case autocompletes
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public static function on_civi_search_autocompleteDefault(GenericHookEvent $e) {
    if (!is_array($e->savedSearch) || $e->savedSearch['api_entity'] !== 'Case') {
      return;
    }
    $e->savedSearch['api_params'] = [
      'version' => 4,
      'select' => [
        'id',
        'subject',
        'Case_CaseContact_Contact_01.sort_name',
        'case_type_id:label',
        'status_id:label',
        'start_date',
      ],
      'orderBy' => [],
      'where' => [],
      'groupBy' => [],
      'join' => [
        [
          'Contact AS Case_CaseContact_Contact_01',
          'LEFT',
          'CaseContact',
          ['id', '=', 'Case_CaseContact_Contact_01.case_id'],
        ],
      ],
      'having' => [],
    ];
  }

  /**
   * Provide default SearchDisplay for Case autocompletes
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public static function on_civi_search_defaultDisplay(GenericHookEvent $e) {
    if ($e->display['settings'] || $e->display['type'] !== 'autocomplete' || $e->savedSearch['api_entity'] !== 'Case') {
      return;
    }
    // Basic settings with no join
    // We won't assume the SavedSearch includes a contact join, because it's possible to override
    // the savedSearch for an autocomplete and still use this default display.
    $e->display['settings'] = [
      'sort' => [
        ['subject', 'ASC'],
      ],
      'columns' => [
        [
          'type' => 'field',
          'key' => 'subject',
          'empty_value' => '(' . ts('no subject') . ')',
        ],
        [
          'type' => 'field',
          'key' => 'id',
          'rewrite' => '#[id]: [case_type_id:label]',
        ],
        [
          'type' => 'field',
          'key' => 'status_id:label',
          'rewrite' => '[status_id:label] (' . ts('Started %1', [1 => '[start_date]']) . ')',
        ],
      ],
    ];
    // If the savedSearch includes a contact join, add it to the output and the sort.
    foreach ($e->savedSearch['api_params']['join'] ?? [] as $join) {
      [$entity, $contactAlias] = explode(' AS ', $join[0]);
      if (CoreUtil::isContact($entity)) {
        array_unshift($e->display['settings']['sort'], ["$contactAlias.sort_name", 'ASC']);
        $e->display['settings']['columns'][0]['rewrite'] = "[$contactAlias.sort_name] - [subject]";
        $e->display['settings']['columns'][0]['empty_value'] = "[$contactAlias.sort_name] (" . ts('no subject') . ')';
        break;
      }
    }
  }

}
