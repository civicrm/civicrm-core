<?php
/**
 * @file
 *
 * Activate Civi components on the newly populated database.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installDatabase', function (\Civi\Setup\Event\InstallDatabaseEvent $e) {
    \Civi\Setup::log()->info('[InstallComponents.civi-setup.php] Activate components: ' . implode(" ", $e->getModel()->components));

    if (empty($e->getModel()->components)) {
      throw new \Exception("System must have at least one active component.");
    }

    \Civi::settings()->set('enable_components', $e->getModel()->components);
  }, \Civi\Setup::PRIORITY_LATE + 300);
