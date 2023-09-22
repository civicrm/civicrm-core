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
use Civi\Api4\Utils\CoreUtil;

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
   * @return array[]
   */
  public static function getEntityLinks(string $entity, $addLabel = FALSE): array {
    $paths = CoreUtil::getInfoItem($entity, 'paths') ?? [];
    $links = [];
    // Hack to support links to relationships
    if ($entity === 'RelationshipCache') {
      $entity = 'Relationship';
    }
    if ($addLabel === TRUE) {
      $addLabel = CoreUtil::getInfoItem($entity, 'title');
    }
    // If addLabel is false the placeholder needs to be passed through to javascript
    $label = $addLabel ?: '%1';
    $styles = [
      'delete' => 'danger',
      'add' => 'primary',
    ];
    foreach (array_keys($paths) as $actionName) {
      $actionKey = \CRM_Core_Action::mapItem($actionName);
      $link = [
        'action' => $actionName,
        'entity' => $entity,
        'text' => \CRM_Core_Action::getTitle($actionKey, $label),
        'icon' => \CRM_Core_Action::getIcon($actionKey),
        'weight' => \CRM_Core_Action::getWeight($actionKey),
        'style' => $styles[$actionName] ?? 'default',
        'target' => 'crm-popup',
      ];
      // Contacts and cases are too cumbersome to view in a popup
      if (in_array($entity, ['Contact', 'Case']) && in_array($actionName, ['view', 'update'])) {
        $link['target'] = '_blank';
      }
      $links[$actionName] = $link;
    }
    // Sort by weight, then discard it
    uasort($links, ['CRM_Utils_Sort', 'cmpFunc']);
    foreach ($links as $index => $link) {
      unset($links[$index]['weight']);
    }
    return $links;
  }

}
