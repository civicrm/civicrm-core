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

    // NOTE: in here, $model->projectRootPath refers to the root of the *application*, not the actual webroot as reachable by http.
    // Typically this means that $model->projectRootPath might be like /var/www/example.org/ and the actual web root would be
    // /var/www/example.org/web/

    // Compute settingsPath.
    // We use this structure: /var/www/standalone/data/{civicrm.settings.php,templates_c}
    // to reduce the number of directories that admins have to chmod
    $model->settingsPath = implode(DIRECTORY_SEPARATOR, [$model->projectRootPath, 'data', 'civicrm.settings.php']);
    $model->templateCompilePath = implode(DIRECTORY_SEPARATOR, [$model->projectRootPath, 'data', 'templates_c']);
    print "\n-------------------------\nSet model values:\n" . json_encode($model->getValues(), JSON_PRETTY_PRINT) . "\n-----------------------------\n";

    // Compute DSN.
    $model->db = $model->cmsDb = [
      'server' => 'mysql',
      'username' => 'loner',
      'password' => 'somepass',
      'database' => 'standalone_civicrm',
    ];

    // Compute URLs (@todo?)
    // $model->cmsBaseUrl = $_SERVER['HTTP_ORIGIN'] ?: $_SERVER['HTTP_REFERER'];
    $model->cmsBaseUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];

    // These mandatorySettings become 'extra settings from installer' and set values in
    // $civicrm_setting['domain'][k] = v;
    $model->mandatorySettings['userFrameworkResourceURL'] = $model->cmsBaseUrl . '/assets/civicrm/core';

    // These paths get set as
    // $civicrm_paths[k]['url'|'path'] = v
    $model->paths['cms.root'] = [
      'path' => $model->projectRootPath . DIRECTORY_SEPARATOR . 'web',
    ];
    $model->paths['civicrm.files'] = [
      'path' => rtrim($model->projectRootPath . DIRECTORY_SEPARATOR . 'web') . DIRECTORY_SEPARATOR . 'upload',
      'url' => $model->cmsBaseUrl . '/upload',
    ];

    // Compute default locale.
    $model->lang = $_REQUEST['lang'] ?? 'en_US';
  });
