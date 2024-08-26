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
   * Set filters for the menubar quicksearch.
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
      $allowedFilters = \Civi::settings()->get('quicksearch_options');
      foreach ($apiRequest->getFilters() as $fieldName => $val) {
        if (in_array($fieldName, $allowedFilters)) {
          $apiRequest->addFilter($fieldName, $val);
        }
      }
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
    // Adjust display for quicksearch input - the display only needs one column
    // as the menubar autocomplete does not support descriptions
    if (($e->context['formName'] ?? NULL) === 'crmMenubar' && ($e->context['fieldName'] ?? NULL) === 'crm-qsearch-input') {
      $column = ['type' => 'field'];
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
      $filterFields = [];
      // If doing a search by a field other than the default,
      // add that field to the main column
      if (!empty($e->context['filters'])) {
        $filterFields[] = array_keys($e->context['filters'])[0];
      }
      else {
        if (\Civi::settings()->get('includeEmailInName')) {
          $filterFields[] = 'email_primary.email';
        }
        if (\Civi::settings()->get('includeNickNameInName')) {
          $filterFields[] = 'nick_name';
        }
      }
      if (!empty($filterFields)) {
        // Take the first one as the key.
        $column['key'] = $filterFields[0];
        $column['empty_value'] = '[sort_name]';
        $column['rewrite'] = "[sort_name]";
        foreach ($filterFields as $filterField) {
          $column['rewrite'] .= " :: [$filterField]";
          $autocompleteOptionsMap = array_diff($autocompleteOptionsMap, [$filterField]);
        }
      }
      // No filter & email search disabled: search on name only
      else {
        $column['key'] = 'sort_name';
      }
      $e->display['settings']['columns'] = [$column];
      // Add exta columns based on search preferences
      $autocompleteOptions = Setting::get(FALSE)
        ->addSelect('contact_autocomplete_options')->execute()
        ->first();
      foreach ($autocompleteOptions['value'] ?? [] as $option) {
        if (isset($autocompleteOptionsMap[$option])) {
          $e->display['settings']['columns'][] = [
            'type' => 'field',
            'key' => $autocompleteOptionsMap[$option],
          ];
        }
      }
    }
  }

}
