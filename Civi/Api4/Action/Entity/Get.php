<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */


namespace Civi\Api4\Action\Entity;

use Civi\Api4\CustomGroup;
use Civi\Api4\Utils\ReflectionUtils;

/**
 * Get entities
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
    $locations = array_merge([\Civi::paths()->getPath('[civicrm.root]/Civi.php')],
      array_column(\CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles(), 'filePath')
    );
    foreach ($locations as $location) {
      $dir = \CRM_Utils_File::addTrailingSlash(dirname($location)) . 'Civi/Api4';
      if (is_dir($dir)) {
        foreach (glob("$dir/*.php") as $file) {
          $matches = [];
          preg_match('/(\w*).php/', $file, $matches);
          $entity = ['name' => $matches[1]];
          if ($this->_isFieldSelected('description') || $this->_isFieldSelected('comment')) {
            $this->addDocs($entity);
          }
          $entities[$matches[1]] = $entity;
        }
      }
    }
    unset($entities['CustomValue']);

    if ($this->includeCustom) {
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
    $entity += ReflectionUtils::getCodeDocs($reflection);
    unset($entity['package'], $entity['method']);
  }

}
