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
    if ($e->getModel()->lang) {
      \Civi\Setup::log()->info('[SetLanguage.civi-setup.php] Set default language to ' . $e->getModel()->lang);
      \Civi::settings()->set('lcMessages', $e->getModel()->lang);
    }
  }, \Civi\Setup::PRIORITY_LATE + 400);
