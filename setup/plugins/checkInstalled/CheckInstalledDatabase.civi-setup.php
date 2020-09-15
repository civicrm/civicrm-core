<?php
/**
 * @file
 *
 * Determine whether Civi has been installed.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.checkInstalled', function (\Civi\Setup\Event\CheckInstalledEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'checkInstalled'));
    $model = $e->getModel();

    if ($model->db) {
      try {
        $conn = \Civi\Setup\DbUtil::connect($model->db);
      }
      catch (\Civi\Setup\Exception\SqlException $exception) {
        $e->setDatabaseInstalled(FALSE);
        return;
      }
      $found = FALSE;
      foreach ($conn->query('SHOW TABLES LIKE "civicrm_%"') as $result) {
        $found = TRUE;
      }
      $conn->close();
      $e->setDatabaseInstalled($found);
    }
    else {
      throw new \Exception("The \"db\" is unspecified. Cannot determine whether the database schema file exists.");
    }
  });
