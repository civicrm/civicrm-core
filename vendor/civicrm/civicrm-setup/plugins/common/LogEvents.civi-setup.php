<?php
/**
 * @file
 *
 * Record a log message at the start and end of each major business operation.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}
use \Civi\Setup;

$setup = Setup::instance();

$eventNames = array(
  'civi.setup.init',
  'civi.setup.checkAuthorized',
  'civi.setup.checkRequirements',
  'civi.setup.checkInstalled',
  'civi.setup.installFiles',
  'civi.setup.installDatabase',
  'civi.setup.uninstallDatabase',
  'civi.setup.uninstallFiles',
  'civi.setupui.construct',
  'civi.setupui.boot',
);
foreach ($eventNames as $eventName) {
  $setup->getDispatcher()
    ->addListener(
      $eventName,
      function ($event) use ($eventName, $setup) {
        $setup->getLog()->debug("[LogEvents.civi-setup.php] Start $eventName");
      },
      Setup::PRIORITY_START + 1
    );
  $setup->getDispatcher()
    ->addListener(
      $eventName,
      function ($event) use ($eventName, $setup) {
        $setup->getLog()->debug("[LogEvents.civi-setup.php] Finish $eventName");
      },
      Setup::PRIORITY_END - 1
    );
}
