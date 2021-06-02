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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace Civi\Api4\Action\Entity;

use Civi\Api4\CustomGroup;
use Civi\Api4\CustomValue;
use Civi\Api4\Service\Schema\Joinable\CustomGroupJoinable;
use Civi\Api4\Utils\CoreUtil;

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
    $entities = [];
    $namesRequested = $this->_itemsToGet('name');

    if ($namesRequested) {
      foreach ($namesRequested as $entityName) {
        if (strpos($entityName, 'Custom_') !== 0) {
          $className = CoreUtil::getApiClass($entityName);
          if ($className) {
            $this->loadEntity($className, $entities);
          }
        }
      }
    }
    else {
      foreach ($this->getAllApiClasses() as $className) {
        $this->loadEntity($className, $entities);
      }
    }

    // Fetch custom entities unless we've already fetched everything requested
    if (!$namesRequested || array_diff($namesRequested, array_keys($entities))) {
      $this->addCustomEntities($entities);
    }

    ksort($entities);
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
   * Add custom-field pseudo-entities
   *
   * @param $entities
   * @throws \API_Exception
   */
  private function addCustomEntities(&$entities) {
    $customEntities = CustomGroup::get()
      ->addWhere('is_multiple', '=', 1)
      ->addWhere('is_active', '=', 1)
      ->setSelect(['name', 'title', 'help_pre', 'help_post', 'extends', 'icon'])
      ->setCheckPermissions(FALSE)
      ->execute();
    $baseInfo = CustomValue::getInfo();
    foreach ($customEntities as $customEntity) {
      $fieldName = 'Custom_' . $customEntity['name'];
      $baseEntity = CoreUtil::getApiClass(CustomGroupJoinable::getEntityFromExtends($customEntity['extends']));
      $entities[$fieldName] = [
        'name' => $fieldName,
        'title' => $customEntity['title'],
        'title_plural' => $customEntity['title'],
        'description' => ts('Custom group for %1', [1 => $baseEntity::getInfo()['title_plural']]),
        'paths' => [
          'view' => "civicrm/contact/view/cd?reset=1&gid={$customEntity['id']}&recId=[id]&multiRecordDisplay=single",
        ],
        'icon' => $customEntity['icon'] ?: NULL,
      ] + $baseInfo;
      if (!empty($customEntity['help_pre'])) {
        $entities[$fieldName]['comment'] = $this->plainTextify($customEntity['help_pre']);
      }
      if (!empty($customEntity['help_post'])) {
        $pre = empty($entities[$fieldName]['comment']) ? '' : $entities[$fieldName]['comment'] . "\n\n";
        $entities[$fieldName]['comment'] = $pre . $this->plainTextify($customEntity['help_post']);
      }
    }
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
