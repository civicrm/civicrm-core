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

use Civi\API\Event\PrepareEvent;
use Civi\Api4\Setting;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\HookInterface;

/**
 * @service
 * @internal
 */
class ContactAutocompleteProvider extends \Civi\Core\Service\AutoService implements HookInterface {

  /**
   * Custom autocomplete for the menubar quicksearch.
   *
   * @param \Civi\API\Event\PrepareEvent $event
   */
  public static function on_civi_api_prepare(PrepareEvent $event) {
    $apiRequest = $event->getApiRequest();
    if (is_object($apiRequest) &&
      is_a($apiRequest, 'Civi\Api4\Generic\AutocompleteAction') &&
      $apiRequest->getFormName() === 'crmMenubar' &&
      $apiRequest->getFieldName() === 'crm-qsearch-input'
    ) {

      // Override savedSearch
      $savedSearch = [
        'api_entity' => 'Contact',
      ];
      $apiParams = [
        'where' => [
          // Api4 automatically adds this to most Contact queries, but not within Unions.
          ['is_deleted', '=', FALSE],
        ],
      ];

      $allowedFilters = \Civi::settings()->get('quicksearch_options');
      foreach ($apiRequest->getFilters() as $filterField => $val) {
        if (in_array($filterField, $allowedFilters)) {
          // Add trusted filter
          $apiRequest->addFilter($filterField, $val);
          $apiRequest->setInput($val);

          // If the filter is from a multi-record custom field set, add necessary join to the savedSearch query
          if (str_contains($filterField, '.')) {
            [$customGroupName, $customFieldName] = explode('.', $filterField);
            $customGroup = \CRM_Core_BAO_CustomGroup::getGroup(['name' => $customGroupName]);
            if (!empty($customGroup['is_multiple'])) {
              $apiParams['join'][] = [
                "Custom_$customGroupName AS $customGroupName",
                'INNER',
                ['id', '=', "$customGroupName.entity_id"],
              ];
            }
          }
        }
      }

      // Override searchDisplay
      $display = [
        'settings' => [
          'sort' => [
            ['sort_name', 'ASC'],
          ],
        ],
      ];

      $columns = [
        ['type' => 'field', 'key' => 'sort_name'],
      ];

      // Map contact_autocomplete_options settings to v4 format
      $autocompleteOptionsMap = [
        2 => 'email_primary.email',
        3 => 'phone_primary.phone',
        4 => 'address_primary.street_address',
        5 => 'address_primary.city',
        6 => 'address_primary.state_province_id:abbr',
        7 => 'address_primary.country_id:label',
        8 => 'address_primary.postal_code',
      ];
      // If doing a search by a field other than the default,
      // add that field as the column
      if ($apiRequest->getFilters()) {
        $filterFields = array_keys($apiRequest->getFilters());
        $columns[0]['rewrite'] = "[sort_name] :: [" . implode('] :: [', $filterFields) . "]";
      }
      else {
        $filterFields = ['sort_name'];
        if (\Civi::settings()->get('includeEmailInName')) {
          $filterFields[] = 'email_primary.email';
          $columns[] = ['type' => 'field', 'key' => 'email_primary.email'];
        }
        if (\Civi::settings()->get('includeNickNameInName')) {
          $filterFields[] = 'nick_name';
          $columns[0] = [
            'type' => 'field',
            'key' => 'nick_name',
            'rewrite' => '[sort_name] "[nick_name]"',
            'empty_value' => '[sort_name]',
          ];
        }
      }
      $autocompleteOptionsMap = array_diff($autocompleteOptionsMap, $filterFields);

      // Add extra columns based on search preferences
      $extraFields = [];
      $autocompleteOptions = Setting::get(FALSE)
        ->addSelect('contact_autocomplete_options')->execute()
        ->first();
      foreach ($autocompleteOptions['value'] ?? [] as $option) {
        if (isset($autocompleteOptionsMap[$option])) {
          $extraFields[] = $autocompleteOptionsMap[$option];
          $columns[] = [
            'type' => 'field',
            'key' => $autocompleteOptionsMap[$option],
          ];
        }
      }

      $apiParams['select'] = array_unique(array_merge(['id'], $filterFields, $extraFields));
      $display['settings']['columns'] = $columns;

      // Single filter
      if (count($filterFields) === 1) {
        $display['settings']['searchFields'] = $filterFields;
        $savedSearch['api_params'] = $apiParams;
      }
      // With multiple filters, a UNION is more performant
      else {
        $savedSearch['api_entity'] = 'EntitySet';
        $savedSearch['api_params'] = [
          'select' => $apiParams['select'],
          'sets' => [],
        ];
        // Add limit to each subset for max efficiency
        $apiParams['orderBy'] = ['sort_name' => 'ASC'];
        $apiParams['limit'] = \Civi::settings()->get('search_autocomplete_count') ?: 15;
        // Add a UNION per search field
        $prefix = \Civi::settings()->get('includeWildCardInName') ? '%' : '';
        foreach ($filterFields as $field) {
          $params = $apiParams;
          $params['where'][] = [$field, 'LIKE', $prefix . $apiRequest->getInput() . '%'];
          // Strip all suffixes from inner select array (pseudoconstants will be evaluated by the outer query)
          $params['select'] = array_map(function ($field) {
            return explode(':', $field)[0];
          }, $params['select']);
          $savedSearch['api_params']['sets'][] = ['UNION DISTINCT', 'Contact', 'get', $params];
        }
        // Remove filter as we've already embedded it in the WHERE clauses of each UNION
        $apiRequest->setInput('');
      }

      $apiRequest->overrideSavedSearch($savedSearch);
      $apiRequest->overrideDisplay($display);
    }
  }

  /**
   * Provide default SearchDisplay for Contact autocompletes
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public static function on_civi_search_defaultDisplay(GenericHookEvent $e) {
    if ($e->display['settings'] || $e->display['type'] !== 'autocomplete' || !CoreUtil::isContact($e->savedSearch['api_entity'])) {
      return;
    }
    if ($e->savedSearch['api_entity'] === 'Contact') {
      $contactTypeIcon = ['field' => 'contact_type:icon'];
    }
    else {
      $contactTypeIcon = ['icon' => CoreUtil::getInfoItem($e->savedSearch['api_entity'], 'icon')];
    }
    $e->display['settings'] = [
      'sort' => [
        ['sort_name', 'ASC'],
      ],
      'columns' => [
        [
          'type' => 'field',
          'key' => 'sort_name',
          'icons' => [
            ['field' => 'contact_sub_type:icon'],
            $contactTypeIcon,
          ],
        ],
        [
          'type' => 'field',
          'key' => 'contact_sub_type:label',
          'rewrite' => '#[id] [contact_sub_type:label]',
          'empty_value' => '#[id] [contact_type:label]',
        ],
      ],
    ];
  }

}
