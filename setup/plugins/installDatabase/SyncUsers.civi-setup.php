<?php
/**
 * @file
 *
 * When CiviCRM is colocated with a CMS, we may synchronize users<=>contacts.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installDatabase', function (\Civi\Setup\Event\InstallDatabaseEvent $e) {
    if ($e->getModel()->cms === 'Standalone' || !$e->getModel()->syncUsers) {
      return;
    }

    \Civi\Setup::log()->info(sprintf('[%s] Synchronize CMS users', basename(__FILE__)));
    CRM_Utils_System::synchronizeUsers();
  }, \Civi\Setup::PRIORITY_LATE - 60);
