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
class CustomValueAutocompleteProvider extends \Civi\Core\Service\AutoService implements HookInterface {

  /**
   * Provide default SearchDisplay for autocompletes of multi-record custom values
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public static function on_civi_search_defaultDisplay(GenericHookEvent $e) {
    if ($e->display['settings'] || $e->display['type'] !== 'autocomplete' || !str_starts_with($e->savedSearch['api_entity'], 'Custom_')) {
      return;
    }
    $customGroupName = explode('_', $e->savedSearch['api_entity'], 2)[1];

    // Custom groups could contain any fields & we have no idea what's in them
    // but this is just the default display and can be overridden.
    // Our best guess for a "title" is the first text field in the group
    $titleField = \Civi\Api4\CustomValue::getFields('Multi_Stuff', FALSE)
      ->addWhere('data_type', '=', 'String')
      ->addWhere('input_type', '=', 'Text')
      ->execute()->first()['name'] ?? NULL;

    // Include search label of parent entity (e.g. `entity_id.sort_name` for Contact custom groups)
    $customGroupExtends = \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupName, 'extends', 'name');
    $extendsLabelField = CoreUtil::getInfoItem($customGroupExtends, 'search_fields')[0] ?? NULL;

    if (!$extendsLabelField && !$titleField) {
      // Got nothing, fall back to the default which displays id only.
      return;
    }

    $mainColumn = [
      'type' => 'field',
      'key' => $titleField ?? "entity_id.$extendsLabelField",
    ];
    if ($titleField && $extendsLabelField) {
      $mainColumn['rewrite'] = "[entity_id.$extendsLabelField] - [$titleField]";
    }

    $e->display['settings'] = [
      'sort' => [
        [$mainColumn['key'], 'ASC'],
      ],
      'columns' => [
        $mainColumn,
        [
          'type' => 'field',
          'key' => 'id',
          'rewrite' => '#[id]',
        ],
      ],
    ];
  }

}
