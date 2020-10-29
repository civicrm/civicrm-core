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
use Civi\Api4\Service\Schema\Joinable\CustomGroupJoinable;

/**
 * Get the names & docblocks of all APIv4 entities.
 *
 * Scans for api entities in core + enabled extensions.
 *
 * Also includes pseudo-entities from multi-record custom groups by default.
 *
 * @method $this setIncludeCustom(bool $value)
 * @method bool getIncludeCustom()
 */
class Get extends \Civi\Api4\Generic\BasicGetAction {

  /**
   * Include custom-field-based pseudo-entities?
   *
   * @var bool
   */
  protected $includeCustom = TRUE;

  /**
   * Scan all api directories to discover entities
   */
  protected function getRecords() {
    $entities = [];
    $toGet = $this->_itemsToGet('name');
    $locations = array_merge([\Civi::paths()->getPath('[civicrm.root]/Civi.php')],
      array_column(\CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles(), 'filePath')
    );
    foreach ($locations as $location) {
      $dir = \CRM_Utils_File::addTrailingSlash(dirname($location)) . 'Civi/Api4';
      if (is_dir($dir)) {
        foreach (glob("$dir/*.php") as $file) {
          $matches = [];
          preg_match('/(\w*)\.php$/', $file, $matches);
          $entity = '\Civi\Api4\\' . $matches[1];
          if (
            (!$toGet || in_array($matches[1], $toGet))
            && is_a($entity, '\Civi\Api4\Generic\AbstractEntity', TRUE)
          ) {
            $info = $entity::getInfo();
            $entities[$info['name']] = $info;
          }
        }
      }
    }

    // Fetch custom entities unless we've already fetched everything requested
    if ($this->includeCustom && (!$toGet || array_diff($toGet, array_keys($entities)))) {
      $this->addCustomEntities($entities);
    }

    ksort($entities);
    return $entities;
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
    foreach ($customEntities as $customEntity) {
      $fieldName = 'Custom_' . $customEntity['name'];
      $baseEntity = '\Civi\Api4\\' . CustomGroupJoinable::getEntityFromExtends($customEntity['extends']);
      $entities[$fieldName] = [
        'name' => $fieldName,
        'title' => $customEntity['title'],
        'title_plural' => $customEntity['title'],
        'description' => ts('Custom group for %1', [1 => $baseEntity::getInfo()['title_plural']]),
        'see' => [
          'https://docs.civicrm.org/user/en/latest/organising-your-data/creating-custom-fields/#multiple-record-fieldsets',
          '\\Civi\\Api4\\CustomGroup',
        ],
        'icon' => $customEntity['icon'],
      ];
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
