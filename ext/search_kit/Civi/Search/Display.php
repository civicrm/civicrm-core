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
  public static function getDisplayTypes(array $props, bool $onlyViewable = FALSE): array {
    try {
      if ($onlyViewable && !in_array('grouping', $props)) {
        $props[] = 'grouping';
      }
      $options = \Civi\Api4\SearchDisplay::getFields(FALSE)
        ->setLoadOptions(array_diff($props, ['tag']))
        ->addWhere('name', '=', 'type')
        ->execute()
        ->first()['options'];
      if ($onlyViewable) {
        return array_filter($options, fn($type) => $type['grouping'] !== 'non-viewable');
      }
      return $options;
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
  public static function getEntityLinks(string $entity, $addLabel = FALSE, ?array $excludeActions = NULL): array {
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

  /**
   * Return settings for the crmSearchDisplay angular module.
   * @return array
   */
  public static function getModuleSettings(): array {
    $viewableTypes = self::getDisplayTypes(['id', 'name'], TRUE);

    return [
      'viewableDisplayTypes' => array_column($viewableTypes, 'name', 'id'),
    ];
  }

}
