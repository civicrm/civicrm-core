<?php
/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Api4\Event\AuthorizeRecordEvent;
use CRM_Standaloneusers_ExtensionUtil as E;

class CRM_Standaloneusers_BAO_Role extends CRM_Standaloneusers_DAO_Role implements \Civi\Core\HookInterface {

  /**
   * Event fired after an action is taken on a Role record.
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    // Reset cache
    Civi::cache('metadata')->clear();
    // Rebuild role-based search displays if they will be affected by this action
    if ($event->action === 'delete' || $event->action === 'create' ||
      ($event->action === 'edit' && (isset($event->params['label']) || isset($event->params['is_active'])))
    ) {
      \Civi\Api4\Managed::reconcile(FALSE)
        ->addModule(E::LONG_NAME)
        ->execute();
    }
  }

  /**
   * Check access permission
   *
   * Note that $e->getRecord() returns the data passed INTO the API, e.g. if it
   * has a 'name' key, then the value is that passed into the API (e.g. for an
   * update action), not the current value.
   *
   * @param \Civi\Api4\Event\AuthorizeRecordEvent $e
   * @see \Civi\Api4\Utils\CoreUtil::checkAccessRecord
   */
  public static function self_civi_api4_authorizeRecord(AuthorizeRecordEvent $e): void {
    $record = $e->getRecord();
    $action = $e->getActionName();
    if (!in_array($action, ['delete', 'update'], TRUE)) {
      // We only care about these actions.
      return;
    }

    // Load the role name from the record that is to be updated/deleted.
    $storedRoleName = CRM_Core_DAO::getFieldValue(self::class, $record['id'], 'name');

    // Protect the admin role: it must have access to everything.
    if ($storedRoleName === 'admin') {
      $e->setAuthorized(FALSE);
      return;
    }

    // Protect the everyone role
    if ($storedRoleName === 'everyone') {
      if ($action === 'delete') {
        // Do not allow deletion of the everyone role.
        $e->setAuthorized(FALSE);
      }
      // Updates: Disallow changing name and is_active
      if (array_intersect(['name', 'is_active'], array_keys($record))) {
        $e->setAuthorized(FALSE);
      }
    }
  }

}
