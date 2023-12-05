<?php
// phpcs:disable
use CRM_Standaloneusers_ExtensionUtil as E;
// phpcs:enable

class CRM_Standaloneusers_BAO_Role extends CRM_Standaloneusers_DAO_Role implements \Civi\Core\HookInterface {

  /**
   * Event fired after an action is taken on a Role record.
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    // Remove role from users on deletion
    if ($event->action === 'delete') {
      $users = \Civi\Api4\User::get(FALSE)
        ->addSelect('id', 'roles')
        ->addWhere('roles', 'CONTAINS', $event->id)
        ->execute();
      foreach ($users as $user) {
        $roles = array_diff($user['roles'], [$event->id]);
        \Civi\Api4\User::update(FALSE)
          ->addValue('roles', $roles)
          ->addWhere('id', '=', $user['id'])
          ->execute();
      }
    }

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
   * @see CRM_Core_DAO::checkAccess
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
