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
      // Override searchDisplay
      $display = [
        'settings' => [
          'sort' => [
            ['sort_name', 'ASC'],
          ],
        ],
      ];

      // get user inputs and clear from the original api request
      $filterInputs = $apiRequest->getFilters();
      $apiRequest->setFilters([]);
      // the name/email value is supplied through getInput
      // for historical reasons - TODO: rationalise
      $input = $apiRequest->getInput();
      $apiRequest->setInput('');

      // now lets interpret the filters
      $duplicateOptions = [];
      $filterValues = [];
      $selectFields = ['sort_name'];
      $columns = [['type' => 'field', 'key' => 'sort_name']];

      // relevant settings
      $ftsIndices = array_keys(\Civi::service('civi.schema.fts')->getIndicesForEntity('Contact'));
      $allFilterFields = array_column(\CRM_Core_SelectValues::getQuicksearchOptions(), NULL, 'key');
      $enabledFilterFields = \array_intersect_key($allFilterFields, array_flip(\Civi::settings()->get('quicksearch_options')));

      if (!$filterInputs) {
        // this is the default option: Name (and Email)
        $useFtsForSortName = \in_array('contact_names', $ftsIndices);
        $includeNickName = $useFtsForSortName || \Civi::settings()->get('includeNickNameInName');
        $includeEmailWithName = \Civi::settings()->get('includeEmailInName');
        $wildcardAtStart = \Civi::settings()->get('includeWildCardInName') ? '%' : '';

        if ($useFtsForSortName) {
          // strip FULLTEXT BOOLEAN special characters to avoid invalid expression
          // (assumes they are not valid in names)
          $ftsNameInput = \preg_replace('/[%@+-<>~]/', '', $input);
          // add trailing wildcards in between each word
          // NOTE trailing wildcard will be added to the very end
          // when the apiParams are composed below
          $ftsNameInput = \str_replace(' ', '% ', $ftsNameInput);
          $filterValues['contact_names'] = $ftsNameInput;
        }
        else {
          $filterValues['sort_name'] = $wildcardAtStart . $input;
        }

        if ($includeNickName) {
          $selectFields[] = 'nick_name';
          $columns[0] = [
            'type' => 'field',
            'key' => 'nick_name',
            'rewrite' => '[sort_name] "[nick_name]"',
            'empty_value' => '[sort_name]',
          ];
        }

        if ($includeEmailWithName) {
          $filterValues['email_primary.email'] = $wildcardAtStart . $input;
          $selectFields[] = 'email_primary.email';
          $columns[] = ['type' => 'field', 'key' => 'email_primary.email'];
        }
      }
      else {
        foreach ($filterInputs as $filterField => $val) {
          $filterFieldMeta = $enabledFilterFields[$filterField] ?? NULL;
          if (!$filterFieldMeta) {
            // unrecogised field
            continue;
          }
          if ($filterField === 'Phone.phone_numeric') {
            $val = preg_replace('/\D/', '', $val);
          }
          // Add trusted filter
          $filterValues[$filterField] = $val;

          // so long as not a fts index, add to select
          if (!in_array($filterField, $ftsIndices)) {
            $selectFields[] = $filterField;
          }
          // If the filter includes a join, add it to the savedSearch query
          if (!empty($filterFieldMeta['join'])) {
            $apiParams['join'][] = $filterFieldMeta['join'];
            // Prevent this field from displaying twice e.g. both Email.email and email_primary.email
            [$entity, $field] = explode('.', $filterField);
            $duplicateOptions[] = strtolower($entity) . "_primary.$field";
          }
        }
        // display additional selected fields using rewrite
        if (count($selectFields) > 1) {
          $columns[0]['rewrite'] = \implode(' :: ', array_map(fn ($f) => "[{$f}]", $selectFields));
        }
      }

      if (!$filterValues) {
        throw new \CRM_Core_Exception('No search values found for quicksearch');
      }

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
      $autocompleteOptionsMap = array_diff($autocompleteOptionsMap, $selectFields, $duplicateOptions);

      // Add extra columns based on search preferences
      $extraFields = [];
      $autocompleteOptions = Setting::get(FALSE)
        ->addSelect('contact_autocomplete_options')->execute()
        ->first();
      foreach ($autocompleteOptions['value'] ?? [] as $option) {
        if (isset($autocompleteOptionsMap[$option])) {
          $selectFields[] = $autocompleteOptionsMap[$option];
          $columns[] = [
            'type' => 'field',
            'key' => $autocompleteOptionsMap[$option],
          ];
        }
      }

      $apiParams['select'] = array_unique(array_merge(['id'], $selectFields, $extraFields));
      $apiParams['orderBy'] = ['sort_name' => 'ASC'];
      $apiParams['limit'] = \Civi::settings()->get('search_autocomplete_count') ?: 15;

      // We use UNION to improve performance of multiple filters
      // If there is only one filter field then this is a trivial union
      // with one subquery - but simpler to use the same codepath
      $savedSearch['api_entity'] = 'EntitySet';
      $savedSearch['api_params'] = [
        'select' => $apiParams['select'],
        'sets' => [],
      ];

      // Add a UNION per search field
      foreach ($filterValues as $field => $value) {
        $params = $apiParams;
        $operator = $enabledFilterFields[$field]['op'] ?? 'LIKE';
        if ($operator === 'LIKE') {
          $value = $value . '%';
        }
        $params['where'][] = [$field, $operator, $value];
        // Strip all suffixes from inner select array (pseudoconstants will be evaluated by the outer query)
        $params['select'] = array_map(fn ($field) => explode(':', $field)[0], $params['select']);
        $savedSearch['api_params']['sets'][] = ['UNION DISTINCT', 'Contact', 'get', $params];
      }

      $display['settings']['columns'] = $columns;
      // Search terms are already embedded in the UNION sets, so there is nothing for
      // AutocompleteAction to retry against other search fields. If we don't set this
      // here, we may end up doing the same query twice when there are no results
      // because we can have two searchFields. 'sort_name' here is meaningless,
      // could be any TRUE string, but this is a logical value to return to the client.
      $display['settings']['searchFields'] = ['sort_name'];

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
