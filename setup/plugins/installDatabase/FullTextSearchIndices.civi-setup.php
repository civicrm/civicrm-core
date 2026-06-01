<?php
/**
 * @file
 *
 * Add Full Text Search indices
 *
 * These cannot be created during the initial database install because they depend on
 * userspace settings
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installDatabase', function (\Civi\Setup\Event\InstallDatabaseEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Bootstrap CiviCRM', basename(__FILE__)));

    // NOTE: if search_mysql_fts is turned off, this is a no-op
    \Civi::service('civi.schema.fts')->createIndices();

  }, \Civi\Setup::PRIORITY_LATE);
