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
 * $Id$
 *
 */


namespace Civi\Api4\Action\Entity;

use Civi\Api4\CustomGroup;
use Civi\Api4\Utils\ReflectionUtils;

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
    $getDocs = $this->_isFieldSelected('description', 'comment', 'see');
    $locations = array_merge([\Civi::paths()->getPath('[civicrm.root]/Civi.php')],
      array_column(\CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles(), 'filePath')
    );
    foreach ($locations as $location) {
      $dir = \CRM_Utils_File::addTrailingSlash(dirname($location)) . 'Civi/Api4';
      if (is_dir($dir)) {
        foreach (glob("$dir/*.php") as $file) {
          $matches = [];
          preg_match('/(\w*)\.php$/', $file, $matches);
          if (
            (!$toGet || in_array($matches[1], $toGet))
            && is_a('\Civi\Api4\\' . $matches[1], '\Civi\Api4\Generic\AbstractEntity', TRUE)
          ) {
            $entity = ['name' => $matches[1]];
            if ($getDocs) {
              $this->addDocs($entity);
            }
            $entities[$matches[1]] = $entity;
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
      ->setSelect(['name', 'title', 'help_pre', 'help_post', 'extends'])
      ->setCheckPermissions(FALSE)
      ->execute();
    foreach ($customEntities as $customEntity) {
      $fieldName = 'Custom_' . $customEntity['name'];
      $entities[$fieldName] = [
        'name' => $fieldName,
        'description' => $customEntity['title'] . ' custom group - extends ' . $customEntity['extends'],
        'see' => [
          'https://docs.civicrm.org/user/en/latest/organising-your-data/creating-custom-fields/#multiple-record-fieldsets',
          '\\Civi\\Api4\\CustomGroup',
        ],
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

  /**
   * Add info from code docblock.
   *
   * @param $entity
   */
  private function addDocs(&$entity) {
    $reflection = new \ReflectionClass("\\Civi\\Api4\\" . $entity['name']);
    $entity += ReflectionUtils::getCodeDocs($reflection, NULL, ['entity' => $entity['name']]);
    unset($entity['package'], $entity['method']);
  }

}
