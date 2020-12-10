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
    if ($e->getModel()->cms !== 'WordPress') {
      return;
    }
    \Civi\Setup::log()->info(sprintf('[%s] Flush CMS metadata', basename(__FILE__)));

    // Should we set the default permissions -- like in Drupal?
  }, \Civi\Setup::PRIORITY_LATE + 50);
