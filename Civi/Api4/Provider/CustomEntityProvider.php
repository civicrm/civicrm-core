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

namespace Civi\Api4\Provider;

use Civi\Api4\CustomValue;
use Civi\Api4\Service\Schema\Joinable\CustomGroupJoinable;
use Civi\Core\Event\GenericHookEvent;

class CustomEntityProvider {

  /**
   * Get custom-field pseudo-entities
   */
  public static function addCustomEntities(GenericHookEvent $e) {
    $baseInfo = CustomValue::getInfo();
    $select = \CRM_Utils_SQL_Select::from('civicrm_custom_group')
      ->where('is_multiple = 1')
      ->where('is_active = 1')
      ->toSQL();
    $group = \CRM_Core_DAO::executeQuery($select);
    while ($group->fetch()) {
      $entityName = 'Custom_' . $group->name;
      $baseEntity = CustomGroupJoinable::getEntityFromExtends($group->extends);
      // Lookup base entity info using DAO methods not CoreUtil to avoid early-bootstrap issues
      $baseEntityDao = \CRM_Core_DAO_AllCoreTables::getFullName($baseEntity);
      $baseEntityTitle = $baseEntityDao ? $baseEntityDao::getEntityTitle(TRUE) : $baseEntity;
      $e->entities[$entityName] = [
        'name' => $entityName,
        'title' => $group->title,
        'title_plural' => $group->title,
        'table_name' => $group->table_name,
        'class_args' => [$group->name],
        'description' => ts('Custom group for %1', [1 => $baseEntityTitle]),
        'paths' => [
          'view' => "civicrm/contact/view/cd?reset=1&gid={$group->id}&recId=[id]&multiRecordDisplay=single",
        ],
      ] + $baseInfo;
      if (!empty($group->icon)) {
        $e->entities[$entityName]['icon'] = $group->icon;
      }
      if (!empty($group->help_pre)) {
        $e->entities[$entityName]['comment'] = self::plainTextify($group->help_pre);
      }
      if (!empty($group->help_post)) {
        $pre = empty($e->entities[$entityName]['comment']) ? '' : $e->entities[$entityName]['comment'] . "\n\n";
        $e->entities[$entityName]['comment'] = $pre . self::plainTextify($group->help_post);
      }
    }
  }

  /**
   * Convert html to plain text.
   *
   * @param $input
   * @return mixed
   */
  private static function plainTextify($input) {
    return html_entity_decode(strip_tags($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }

}
