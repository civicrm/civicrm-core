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

namespace Civi\Api4\Action\Entity;

use Civi\Api4\CustomValue;
use Civi\Api4\Service\Schema\Joinable\CustomGroupJoinable;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\GenericHookEvent;

/**
 * Get the names & docblocks of all APIv4 entities.
 *
 * Scans for api entities in core, enabled components & enabled extensions.
 *
 * Also includes pseudo-entities from multi-record custom groups.
 */
class Get extends \Civi\Api4\Generic\BasicGetAction {

  /**
   * @var bool
   * @deprecated
   */
  protected $includeCustom;

  /**
   * Scan all api directories to discover entities
   */
  protected function getRecords() {
    $cache = \Civi::cache('metadata');
    $entities = $cache->get('api4.entities.info', []);

    if (!$entities) {
      // Load entities declared in API files
      foreach ($this->getAllApiClasses() as $className) {
        $this->loadEntity($className, $entities);
      }
      // Load entities based on custom data
      $entities = array_merge($entities, $this->getCustomEntities());
      // Allow extensions to modify the list of entities
      $event = GenericHookEvent::create(['entities' => &$entities]);
      \Civi::dispatcher()->dispatch('civi.api4.entityTypes', $event);
      ksort($entities);
      $cache->set('api4.entities.info', $entities);
    }

    return $entities;
  }

  /**
   * @param \Civi\Api4\Generic\AbstractEntity $className
   * @param array $entities
   */
  private function loadEntity($className, array &$entities) {
    $info = $className::getInfo();
    $daoName = $info['dao'] ?? NULL;
    // Only include DAO entities from enabled components
    if (!$daoName || !defined("{$daoName}::COMPONENT") || array_key_exists($daoName::COMPONENT, \CRM_Core_Component::getEnabledComponents())) {
      $entities[$info['name']] = $info;
    }
  }

  /**
   * @return \Civi\Api4\Generic\AbstractEntity[]
   */
  private function getAllApiClasses() {
    $classNames = [];
    $locations = array_merge([\Civi::paths()->getPath('[civicrm.root]/Civi.php')],
      array_column(\CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles(), 'filePath')
    );
    foreach ($locations as $location) {
      $dir = \CRM_Utils_File::addTrailingSlash(dirname($location)) . 'Civi/Api4';
      if (is_dir($dir)) {
        foreach (glob("$dir/*.php") as $file) {
          $className = 'Civi\Api4\\' . basename($file, '.php');
          if (is_a($className, 'Civi\Api4\Generic\AbstractEntity', TRUE)) {
            $classNames[] = $className;
          }
        }
      }
    }
    return $classNames;
  }

  /**
   * Get custom-field pseudo-entities
   *
   * @return array[]
   */
  private function getCustomEntities() {
    $entities = [];
    $baseInfo = CustomValue::getInfo();
    $select = \CRM_Utils_SQL_Select::from('civicrm_custom_group')
      ->where('is_multiple = 1')
      ->where('is_active = 1')
      ->toSQL();
    $group = \CRM_Core_DAO::executeQuery($select);
    while ($group->fetch()) {
      $fieldName = 'Custom_' . $group->name;
      $baseEntity = CoreUtil::getApiClass(CustomGroupJoinable::getEntityFromExtends($group->extends));
      $entities[$fieldName] = [
        'name' => $fieldName,
        'title' => $group->title,
        'title_plural' => $group->title,
        'description' => ts('Custom group for %1', [1 => $baseEntity::getInfo()['title_plural']]),
        'paths' => [
          'view' => "civicrm/contact/view/cd?reset=1&gid={$group->id}&recId=[id]&multiRecordDisplay=single",
        ],
      ] + $baseInfo;
      if (!empty($group->icon)) {
        $entities[$fieldName]['icon'] = $group->icon;
      }
      if (!empty($group->help_pre)) {
        $entities[$fieldName]['comment'] = $this->plainTextify($group->help_pre);
      }
      if (!empty($group->help_post)) {
        $pre = empty($entities[$fieldName]['comment']) ? '' : $entities[$fieldName]['comment'] . "\n\n";
        $entities[$fieldName]['comment'] = $pre . $this->plainTextify($group->help_post);
      }
    }
    return $entities;
  }

  /**
   * Convert html to plain text.
   *
   * @param $input
   * @return mixed
   */
  private function plainTextify($input) {
    return html_entity_decode(strip_tags($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }

}
