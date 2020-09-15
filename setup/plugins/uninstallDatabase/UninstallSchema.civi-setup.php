<?php
/**
 * @file
 *
 * Populate the database schema.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.uninstallDatabase', function (\Civi\Setup\Event\UninstallDatabaseEvent $e) {
    \Civi\Setup::log()->info('[UninstallSchema.civi-setup.php] Remove all tables and views (civicrm_* and log_civicrm_*)');
    $model = $e->getModel();

    $conn = \Civi\Setup\DbUtil::connect($model->db);
    \Civi\Setup\DbUtil::execute($conn, 'SET FOREIGN_KEY_CHECKS=0;');

    foreach (\Civi\Setup\DbUtil::findViews($conn, $model->db['database']) as $view) {
      if (preg_match('/^(civicrm_|log_civicrm_)/', $view)) {
        \Civi\Setup\DbUtil::execute($conn, sprintf('DROP VIEW `%s`', $conn->escape_string($view)));
      }
    }

    foreach (\Civi\Setup\DbUtil::findTables($conn, $model->db['database']) as $table) {
      if (preg_match('/^(civicrm_|log_civicrm_)/', $table)) {
        \Civi\Setup\DbUtil::execute($conn, sprintf('DROP TABLE `%s`', $conn->escape_string($table)));
      }
    }

    // TODO Perhaps we should also remove stored-procedures/functions?

    $conn->close();
  });
