<?php
/**
 * @file
 *
 * Perform the first system bootstrap.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installDatabase', function (\Civi\Setup\Event\InstallDatabaseEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Bootstrap CiviCRM', basename(__FILE__)));

    if (!defined('CIVICRM_SETTINGS_PATH')) {
      define('CIVICRM_SETTINGS_PATH', $e->getModel()->settingsPath);
    }

    if (CIVICRM_SETTINGS_PATH !== $e->getModel()->settingsPath) {
      throw new \RuntimeException(sprintf("Cannot boot: The civicrm.settings.php path appears inconsistent (%s vs %s)", CIVICRM_SETTINGS_PATH, $e->getModel()->settingsPath));
    }

    include_once CIVICRM_SETTINGS_PATH;

    require_once 'CRM/Core/ClassLoader.php';
    CRM_Core_ClassLoader::singleton()->register();

    CRM_Core_Config::singleton(TRUE);

  }, \Civi\Setup::PRIORITY_MAIN - 200);
