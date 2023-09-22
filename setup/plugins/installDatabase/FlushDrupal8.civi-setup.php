<?php
/**
 * @file
 *
 * Finalize any extra CMS changes in Drupal.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installDatabase', function (\Civi\Setup\Event\InstallDatabaseEvent $e) {
    if ($e->getModel()->cms !== 'Drupal8') {
      return;
    }
    \Civi\Setup::log()->info(sprintf('[%s] Flush CMS metadata', basename(__FILE__)));

    \Drupal::service('extension.list.module')->reset();
    \Drupal::service('module_installer')->install(['civicrm', 'civicrmtheme']);
    drupal_flush_all_caches();
    civicrm_install_set_drupal8_perms();

  }, \Civi\Setup::PRIORITY_LATE - 50);

function civicrm_install_set_drupal8_perms() {
  $perms = array(
    'access all custom data',
    'access uploaded files',
    'make online contributions',
    'profile create',
    'profile edit',
    'profile view',
    'register for events',
    'view event info',
    'view event participants',
    'access CiviMail subscribe/unsubscribe pages',
  );

  // Adding a permission that has not yet been assigned to a module by
  // a hook_permission implementation results in a database error.
  // CRM-9042

  /** @var \Drupal\user\PermissionHandlerInterface $permissionHandler */
  $permissionHandler = \Drupal::service('user.permissions');

  $allPerms = array_keys($permissionHandler->getPermissions());
  foreach (array_diff($perms, $allPerms) as $perm) {
    \Drupal::logger('my_module')->error('Cannot grant the %perm permission because it does not yet exist.', [
      '%perm' => $perm,
    ]);
  }
  $perms = array_intersect($perms, $allPerms);
  user_role_grant_permissions(\Drupal\user\RoleInterface::AUTHENTICATED_ID, $perms);
  user_role_grant_permissions(\Drupal\user\RoleInterface::ANONYMOUS_ID, $perms);
}
