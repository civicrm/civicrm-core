<?php
/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use CRM_Standaloneusers_ExtensionUtil as E;

class CRM_Standaloneusers_BAO_Role extends CRM_Standaloneusers_DAO_Role implements \Civi\Core\HookInterface {

  /**
   * Event fired after an action is taken on a Role record.
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    // Reset cache
    Civi::cache('metadata')->clear();
  }

  /**
   * Check access permission
   *
   * @param string $entityName
   * @param string $action
   * @param array $record
   * @param integer|null $userID
   * @return boolean
   * @see \Civi\Api4\Utils\CoreUtil::checkAccessRecord
   */
  public static function _checkAccess(string $entityName, string $action, array $record, ?int $userID): bool {
    // Prevent users from updating or deleting the admin and everyone roles
    if (in_array($action, ['delete', 'update'], TRUE)) {
      $name = $record['name'] ?? CRM_Core_DAO::getFieldValue(self::class, $record['id']);
      if (in_array($name, ['admin', 'everyone'], TRUE)) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
