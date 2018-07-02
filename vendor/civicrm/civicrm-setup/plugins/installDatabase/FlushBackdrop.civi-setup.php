<?php
/**
 * @file
 *
 * Configure settings on the newly populated database.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installDatabase', function (\Civi\Setup\Event\InstallDatabaseEvent $e) {
    if ($e->getModel()->cms !== 'Backdrop') {
      return;
    }
    \Civi\Setup::log()->info(sprintf('[%s] Flush CMS metadata', basename(__FILE__)));

    system_rebuild_module_data();
    module_enable(array('civicrm', 'civicrmtheme'));
    backdrop_flush_all_caches();
    civicrm_install_set_backdrop_perms();
  }, \Civi\Setup::PRIORITY_LATE + 50);

function civicrm_install_set_backdrop_perms() {
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
  $allPerms = array_keys(module_invoke_all('permission'));
  foreach (array_diff($perms, $allPerms) as $perm) {
    watchdog('civicrm',
      'Cannot grant the %perm permission because it does not yet exist.',
      array('%perm' => $perm),
      WATCHDOG_ERROR
    );
  }
  $perms = array_intersect($perms, $allPerms);
  user_role_grant_permissions(BACKDROP_AUTHENTICATED_ROLE, $perms);
  user_role_grant_permissions(BACKDROP_ANONYMOUS_ROLE, $perms);
}
