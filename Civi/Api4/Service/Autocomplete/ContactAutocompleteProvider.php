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
    if ($e->display['settings'] || $e->display['type'] !== 'autocomplete' || $e->savedSearch['api_entity'] !== 'Contact') {
      return;
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
            ['field' => 'contact_type:icon'],
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
      // If doing a search by a field other than the default
      if (!empty($e->context['filters'])) {
        $filterField = array_keys($e->context['filters'])[0];
      }
      elseif (\Civi::settings()->get('includeEmailInName')) {
        $filterField = 'email_primary.email';
      }
      if ($filterField) {
        $column['key'] = $filterField;
        $column['rewrite'] = "[sort_name] :: [$filterField]";
        $column['empty_value'] = '[sort_name]';
      }
      else {
        $column['key'] = 'sort_name';
      }
      $e->display['settings']['columns'] = [$column];
    }
  }

}
