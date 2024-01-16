<?php
/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Api4\Event\AuthorizeRecordEvent;

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
   * @param \Civi\Api4\Event\AuthorizeRecordEvent $e
   * @see \Civi\Api4\Utils\CoreUtil::checkAccessRecord
   */
  public static function self_civi_api4_authorizeRecord(AuthorizeRecordEvent $e): void {
    $record = $e->getRecord();
    $action = $e->getActionName();
    // Prevent users from updating or deleting the admin and everyone roles
    if (in_array($action, ['delete', 'update'], TRUE)) {
      $name = $record['name'] ?? CRM_Core_DAO::getFieldValue(self::class, $record['id']);
      if (in_array($name, ['admin', 'everyone'], TRUE)) {
        $e->setAuthorized(FALSE);
      }
    }
  }

}
