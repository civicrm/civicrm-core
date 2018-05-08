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

    if ($model->settingsPath) {
      $e->setSettingInstalled(file_exists($model->settingsPath));
    }
    else {
      throw new \Exception("The \"settingsPath\" is unspecified. Cannot determine whether the settings file exists.");
    }
  });
