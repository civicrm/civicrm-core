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
    if ($e->getModel()->cms !== 'Drupal') {
      return;
    }
    \Civi\Setup::log()->info(sprintf('[%s] Flush CMS metadata', basename(__FILE__)));

    // If the admin activated the module first, and then ran web-based installer,
    // then some hooks (eg hook_menu) may not fire until we fix this flag.
    $initialized = &drupal_static('civicrm_initialize', FALSE);
    $failure = &drupal_static('civicrm_initialize_failure', FALSE);
    $initialized = TRUE;
    $failure = FALSE;

    system_rebuild_module_data();
    module_enable(array('civicrm', 'civicrmtheme'));
    drupal_flush_all_caches();
    civicrm_install_set_drupal_perms();

  }, \Civi\Setup::PRIORITY_LATE - 50);

function civicrm_install_set_drupal_perms() {
  if (!function_exists('db_select')) {
    db_query('UPDATE {permission} SET perm = CONCAT( perm, \', access CiviMail subscribe/unsubscribe pages, access all custom data, access uploaded files, make online contributions, profile listings and forms, register for events, view event info, view event participants\') WHERE rid IN (1, 2)');
  }
  else {
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
    user_role_grant_permissions(DRUPAL_AUTHENTICATED_RID, $perms);
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, $perms);
  }
}
