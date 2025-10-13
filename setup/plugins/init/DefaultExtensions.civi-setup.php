<?php
/**
 * @file
 *
 * Choose some extensions to auto-install.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.init', function (\Civi\Setup\Event\InitEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'init'));

    // NOTE: There are still a handful of other default-extensions enabled
    // in the SQL-template-layer. The mechanism here is more robust.
    // See comments in `civicrm_extension.sqldata.php`.

    $e->getModel()->extensions[] = 'org.civicrm.search_kit';
    $e->getModel()->extensions[] = 'org.civicrm.afform';
    $e->getModel()->extensions[] = 'org.civicrm.afform_admin';
    $e->getModel()->extensions[] = 'authx';
    $e->getModel()->extensions[] = 'civiimport';
    $e->getModel()->extensions[] = 'message_admin';
    $e->getModel()->extensions[] = 'riverlea';

    $e->getModel()->settings['theme_backend'] = 'minetta';
    $e->getModel()->settings['theme_frontend'] = 'minetta';
  });
