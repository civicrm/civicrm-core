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

}
