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

/**
 * This class contains functions for managed entities.
 */
class CRM_Core_BAO_Managed extends CRM_Core_DAO_Managed implements Civi\Core\HookInterface {

  /**
   * Callback for hook_civicrm_post().
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function on_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    // When an entity is deleted, delete the corresponding Managed record
    if ($event->action === 'delete' && $event->id && self::isApi4ManagedType($event->entity)) {
      \Civi\Api4\Managed::delete(FALSE)
        ->addWhere('entity_type', '=', $event->entity)
        ->addWhere('entity_id', '=', $event->id)
        ->execute();
    }
    // When an entity is updated, update the timestamp in corresponding Managed record
    elseif ($event->action === 'edit' && $event->id && self::isApi4ManagedType($event->entity)) {
      if (!array_key_exists('entity_modified_date', self::getSupportedFields())) {
        // During upgrades this column may not exist yet
        return;
      }
      CRM_Core_DAO::executeQuery('UPDATE civicrm_managed SET entity_modified_date = CURRENT_TIMESTAMP WHERE entity_type = %1 AND entity_id = %2', [
        1 => [$event->entity, 'String'],
        2 => [$event->id, 'Integer'],
      ]);
    }
  }

  /**
   * @param string $entityName
   * @return bool
   */
  public static function isApi4ManagedType(string $entityName) {
    $type = \Civi\Api4\Utils\CoreUtil::getInfoItem($entityName, 'type');
    return $type && in_array('ManagedEntity', $type, TRUE);
  }

}
