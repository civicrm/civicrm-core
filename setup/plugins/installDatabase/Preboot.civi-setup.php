<?php
/**
 * @file
 *
 * Perform an initial, partial bootstrap.
 *
 * GOAL: This should provide sufficient services for `InstallSchema` to generate
 * For example, `ts()` needs to be online.
 *
 * MECHANICS: This basically loads `civicrm.settings.php` and calls
 * `CRM_Core_Config::singleton(FALSE,TRUE)`.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.checkRequirements', function (\Civi\Setup\Event\CheckRequirementsEvent $e) {

  });

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installDatabase', function (\Civi\Setup\Event\InstallDatabaseEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Load minimal (non-DB) services', basename(__FILE__)));

    if (!defined('CIVICRM_SETTINGS_PATH')) {
      define('CIVICRM_SETTINGS_PATH', $e->getModel()->settingsPath);
    }

    if (realpath(CIVICRM_SETTINGS_PATH) !== realpath($e->getModel()->settingsPath)) {
      throw new \RuntimeException(sprintf("Cannot boot: The civicrm.settings.php path appears inconsistent (%s vs %s)", CIVICRM_SETTINGS_PATH, $e->getModel()->settingsPath));
    }

    include_once CIVICRM_SETTINGS_PATH;

    require_once 'CRM/Core/ClassLoader.php';
    CRM_Core_ClassLoader::singleton()->register();

    $conn = \Civi\Setup\DbUtil::connect($e->getModel()->db);
    $GLOBALS['CIVICRM_SQL_ESCAPER'] = function($text) use ($conn) {
      return $conn->escape_string($text);
    };

    \Civi\Test::$statics['testPreInstall'] = 1;

    CRM_Core_Config::singleton(FALSE, TRUE);

  }, \Civi\Setup::PRIORITY_PREPARE);
