<?php
/**
 * @file
 *
 * Perform the full bootstrap.
 *
 * GOAL: All the standard services (database, DAOs, translations, settings, etc) should be loaded.
 *
 * MECHANICS: This basically calls `CRM_Core_Config::singleton(TRUE,TRUE)`.
 *
 * NOTE: This is technically a *reboot*. `Preboot` started things off, but it
 * booted with `CRM_Core_Config::singleton($loadFromDB==FALSE)`. Now, the DB is
 * populated, so we can teardown the preboot stuff and start again with `$loadFromDB==TRUE`.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installDatabase', function (\Civi\Setup\Event\InstallDatabaseEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Bootstrap CiviCRM', basename(__FILE__)));

    unset($GLOBALS['CIVICRM_SQL_ESCAPER']);
    unset(\Civi\Test::$statics['testPreInstall']);

    CRM_Core_Config::singleton(TRUE, TRUE);

  }, \Civi\Setup::PRIORITY_MAIN - 200);
