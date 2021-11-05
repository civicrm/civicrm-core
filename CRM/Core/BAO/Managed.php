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
class CRM_Core_BAO_Managed extends CRM_Core_DAO_Managed implements Civi\Test\HookInterface {

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
