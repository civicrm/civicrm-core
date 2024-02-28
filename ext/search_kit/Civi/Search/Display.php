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

use CRM_Search_ExtensionUtil as E;

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

  /**
   * Returns all links for a given entity
   *
   * @param string $entity
   * @param string|bool $addLabel
   *   Pass a string to supply a custom label, TRUE to use the default,
   *   or FALSE to keep the %1 placeholders in the text (used for the admin UI)
   * @param array|null $excludeActions
   * @return array[]
   */
  public static function getEntityLinks(string $entity, $addLabel = FALSE, array $excludeActions = NULL): array {
    $apiParams = [
      'checkPermissions' => FALSE,
      'entityTitle' => $addLabel,
      'select' => ['ui_action', 'entity', 'text', 'icon', 'target'],
    ];
    if ($excludeActions) {
      $apiParams['where'][] = ['ui_action', 'NOT IN', $excludeActions];
    }
    $links = (array) civicrm_api4($entity, 'getLinks', $apiParams);
    $styles = [
      'delete' => 'danger',
      'add' => 'primary',
    ];
    foreach ($links as &$link) {
      $link['action'] = $link['ui_action'];
      $link['style'] = $styles[$link['ui_action']] ?? 'default';
      unset($link['ui_action']);
    }
    return $links;
  }

}
