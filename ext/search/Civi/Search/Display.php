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

namespace Civi\Search;

/**
 * Class Display
 * @package Civi\Search
 */
class Display {

  /**
   * @return array
   */
  public static function getPartials($moduleName, $module) {
    $partials = [];
    foreach (self::getDisplayTypes(['id', 'name']) as $type) {
      $partials["~/$moduleName/displayType/{$type['id']}.html"] =
        '<' . $type['name'] . ' api-entity="{{:: $ctrl.apiEntity }}" search="$ctrl.searchName" display="$ctrl.display.name" settings="$ctrl.display.settings" filters="$ctrl.filters"></' . $type['name'] . '>';
    }
    return $partials;
  }

  /**
   * @return array
   */
  public static function getDisplayTypes(array $props):array {
    try {
      return \Civi\Api4\SearchDisplay::getFields(FALSE)
        ->setLoadOptions(array_diff($props, ['tag']))
        ->addWhere('name', '=', 'type')
        ->execute()
        ->first()['options'];
    }
    catch (\Exception $e) {
      return [];
    }
  }

}
