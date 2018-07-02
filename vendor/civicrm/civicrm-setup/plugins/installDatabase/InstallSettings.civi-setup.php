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
    foreach ($e->getModel()->settings as $settingKey => $settingValue) {
      \Civi\Setup::log()->info(sprintf('[%s] Set value of %s', basename(__FILE__), $settingKey));

      \Civi::settings()->set($settingKey, $settingValue);
    }
  }, \Civi\Setup::PRIORITY_LATE + 100);
