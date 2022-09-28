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
   * @return array[]|null
   */
  public static function getEntityLinks(string $entity, $addLabel = FALSE) {
    $paths = CoreUtil::getInfoItem($entity, 'paths');
    // Hack to support links to relationships
    if ($entity === 'RelationshipCache') {
      $entity = 'Relationship';
    }
    if ($addLabel === TRUE) {
      $addLabel = CoreUtil::getInfoItem($entity, 'title');
    }
    $label = $addLabel ? [1 => $addLabel] : [];
    if ($paths) {
      $links = [
        'view' => [
          'action' => 'view',
          'entity' => $entity,
          'text' => E::ts('View %1', $label),
          'icon' => 'fa-external-link',
          'style' => 'default',
          // Contacts and cases are too cumbersome to view in a popup
          'target' => in_array($entity, ['Contact', 'Case']) ? '_blank' : 'crm-popup',
        ],
        'preview' => [
          'action' => 'preview',
          'entity' => $entity,
          'text' => E::ts('Preview %1', $label),
          'icon' => 'fa-eye',
          'style' => 'default',
          'target' => 'crm-popup',
        ],
        'update' => [
          'action' => 'update',
          'entity' => $entity,
          'text' => E::ts('Edit %1', $label),
          'icon' => 'fa-pencil',
          'style' => 'default',
          // Contacts and cases are too cumbersome to edit in a popup
          'target' => in_array($entity, ['Contact', 'Case']) ? '_blank' : 'crm-popup',
        ],
        'move' => [
          'action' => 'move',
          'entity' => $entity,
          'text' => E::ts('Move %1', $label),
          'icon' => 'fa-random',
          'style' => 'default',
          'target' => 'crm-popup',
        ],
        'delete' => [
          'action' => 'delete',
          'entity' => $entity,
          'text' => E::ts('Delete %1', $label),
          'icon' => 'fa-trash',
          'style' => 'danger',
          'target' => 'crm-popup',
        ],
      ];
      return array_intersect_key($links, $paths) ?: NULL;
    }
    return NULL;
  }

}
