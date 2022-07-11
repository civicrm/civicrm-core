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

    // Compute settingsPath.
    // We use this structure: /var/www/standalone/data/{civicrm.settings.php,templates_c}
    // to reduce the number of directories that admins have to chmod
    $model->settingsPath = implode(DIRECTORY_SEPARATOR, [$model->webroot, 'data', 'civicrm.settings.php']);
    $model->templateCompilePath = implode(DIRECTORY_SEPARATOR, [$model->webroot, 'data', 'templates_c']);

    // Compute DSN.
    $model->db = $model->cmsDb = [
      'server' => 'localhost',
      'username' => '',
      'password' => '',
      'database' => '',
    ];

    // Compute URLs (@todo?)
    $model->cmsBaseUrl = $_SERVER['HTTP_ORIGIN'] ?: $_SERVER['HTTP_REFERER'];
    $model->mandatorySettings['userFrameworkResourceURL'] = $model->cmsBaseUrl . '/assets/civicrm/core';

    // Compute default locale.
    $model->lang = $_REQUEST['lang'] ?? 'en_US';
  });
