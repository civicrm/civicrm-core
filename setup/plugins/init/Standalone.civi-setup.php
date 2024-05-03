<?php
/**
 * @file
 *
 * Determine default settings for Standalone.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}



\Civi\Setup::dispatcher()
  ->addListener('civi.setup.checkAuthorized', function (\Civi\Setup\Event\CheckAuthorizedEvent $e) {
    $model = $e->getModel();
    if ($model->cms !== 'Standalone') {
      return;
    }

    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'checkAuthorized'));
    $e->setAuthorized(TRUE);
  });


\Civi\Setup::dispatcher()
  ->addListener('civi.setup.init', function (\Civi\Setup\Event\InitEvent $e) {
    $model = $e->getModel();
    if ($model->cms !== 'Standalone') {
      return;
    }
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'init'));

    // @todo why is this set here as well as database.civi-setup.php?
    // (in order to set cms db as well?)
    // @todo parse if the DSN is set directly
    $dbHost  = \Civi\Standalone\AppSettings::get('CIVICRM_DB_HOST');
    $dbPort  = \Civi\Standalone\AppSettings::get('CIVICRM_DB_PORT');
    $model->db = $model->cmsDb = [
      'server' => $dbHost . ':' . $dbPort,
      'username' => \Civi\Standalone\AppSettings::get('CIVICRM_DB_USER'),
      'password' => \Civi\Standalone\AppSettings::get('CIVICRM_DB_PASS'),
      'database' => \Civi\Standalone\AppSettings::get('CIVICRM_DB_NAME'),
    ];

    /**
    * load the minimum paths needed for the installer model from AppSettings
    *
    * this ensures we respect any paths provided in settings files / env vars / AppSettings default
    * and resolve relative paths
    */
    $model->paths = [
      'civicrm.files' => [
        'path' => \Civi\Standalone\AppSettings::getPath('public_uploads'),
        'url' => \Civi\Standalone\AppSettings::getUrl('public_uploads'),
      ],
    ];

    $model->cmsBaseUrl = \Civi\Standalone\AppSettings::getUrl('web_root');
    $model->templateCompilePath = \Civi\Standalone\AppSettings::getPath('compile');

    $settingsPath = \Civi\Standalone\AppSettings::getPath('settings');

    if (is_dir($settingsPath)) {
      $model->settingsPath = $settingsPath . DIRECTORY_SEPARATOR . '000_installtime.settings.php';
    }
    else {
      $model->settingsPath = $settingsPath;
    }

    // Compute default locale.
    $model->lang = $_REQUEST['lang'] ?? 'en_US';
  });
