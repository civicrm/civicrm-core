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
 * Provide autocomplete searches for Personal Campaign Pages (PCP).
 *
 * @service
 * @internal
 */
class PCPAutocompleteProvider extends \Civi\Core\Service\AutoService {

  /**
   * Provide default SavedSearch for PCP autocompletes.
   *
   * PCP entities link to either a ContributionPage or an Event depending on page_type.
   * We use the EntitySet API to UNION ALL across both.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  #[CiviEventListener(event: 'civi.search.autocompleteDefault')]
  public static function on_civi_search_autocompleteDefault(GenericHookEvent $e) {
    if (!is_array($e->savedSearch) || $e->savedSearch['api_entity'] !== 'PCP') {
      return;
    }
    $sets = [];
    if (\CRM_Core_Component::isEnabled('CiviContribute')) {
      $sets[] = [
        'UNION ALL', 'PCP', 'get', [
          'select' => [
            'id',
            'title',
            'contact_id.sort_name',
            'contact_id.email_primary.email',
            'page.title',
            'page_type',
            'page_id',
          ],
          'join' => [
            ['ContributionPage AS page', 'INNER', ['page_id', '=', 'page.id']],
          ],
          'where' => [
            ['page_type', '=', 'contribute'],
            ['status_id:name', '=', 'Approved'],
            ['is_active', '=', TRUE],
          ],
        ],
      ];
    }
    if (\CRM_Core_Component::isEnabled('CiviEvent')) {
      $sets[] = [
        'UNION ALL', 'PCP', 'get', [
          'select' => [
            'id',
            'title',
            'contact_id.sort_name',
            'contact_id.email_primary.email',
            'page.title',
            'page_type',
            'page_id',
          ],
          'join' => [
            ['Event AS page', 'INNER', ['page_id', '=', 'page.id']],
          ],
          'where' => [
            ['page_type', '=', 'event'],
            ['status_id:name', '=', 'Approved'],
            ['is_active', '=', TRUE],
          ],
        ],
      ];
    }

    $e->savedSearch['api_entity'] = 'EntitySet';
    $e->savedSearch['api_params'] = [
      'version' => 4,
      'select' => [
        'id',
        'title',
        'contact_id.sort_name',
        'contact_id.email_primary.email',
        'page.title',
        'page_type',
        'page_id',
      ],
      'sets' => $sets,
    ];
  }

  /**
   * Provide default SearchDisplay for PCP autocompletes.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  #[CiviEventListener(event: 'civi.search.defaultDisplay')]
  public static function on_civi_search_defaultDisplay(GenericHookEvent $e) {
    if (
      $e->display['settings'] ||
      $e->display['type'] !== 'autocomplete' ||
      ($e->savedSearch['api_entity'] !== 'EntitySet' || ($e->savedSearch['api_params']['sets'][0][1] ?? NULL) !== 'PCP')
    ) {
      return;
    }
    $e->display['settings'] = [
      'keyField' => 'id',
      'searchFields' => [
        'contact_id.sort_name',
        'title',
        'page.title',
      ],
      'sort' => [
        ['contact_id.sort_name', 'ASC'],
      ],
      'columns' => [
        [
          'type' => 'field',
          'key' => 'contact_id.sort_name',
          'rewrite' => '[contact_id.sort_name]: [title]',
        ],
        [
          'type' => 'field',
          'key' => 'page.title',
        ],
      ],
    ];
  }

}
