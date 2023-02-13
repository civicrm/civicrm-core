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

    error_log('artfulrobot: ' . __FILE__ . ' listener for civi.setup.init');
    // Compute settingsPath.
    // We use this structure: /var/www/standalone/data/{civicrm.settings.php,templates_c}
    // to reduce the number of directories that admins have to chmod

    /**
     * @var string $projectRootPath
     *       refers to the root of the *application*, not the actual webroot as reachable by http.
     *       Typically, this means that $projectRootPath might be like /var/www/example.org/ and
     *       the actual web root would be /var/www/example.org/web/
     */
    $projectRootPath = dirname($model->srcPath, 3);
    $model->settingsPath = implode(DIRECTORY_SEPARATOR, [$projectRootPath, 'data', 'civicrm.settings.php']);
    $model->templateCompilePath = implode(DIRECTORY_SEPARATOR, [$projectRootPath, 'data', 'templates_c']);
    // print "\n-------------------------\nSet model values:\n" . json_encode($model->getValues(), JSON_PRETTY_PRINT) . "\n-----------------------------\n";

    // Compute DSN.
    // print "=======================\n". json_encode(['model' => $model->getValues(), 'server' => $_SERVER], JSON_PRETTY_PRINT) ."\n";
    $model->db = $model->cmsDb = [
      'server' => 'localhost',
      'username' => '',
      'password' => '',
      'database' => '',
    ];

    // Compute URLs (@todo?)
    // original: $model->cmsBaseUrl = $_SERVER['HTTP_ORIGIN'] ?: $_SERVER['HTTP_REFERER'];
    if (empty($model->cmsBaseUrl)) {
      // A buildkit install (which uses cv core:install) sets this correctly. But a standard composer-then-website type install does not.
      $model->cmsBaseUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    }

    // These mandatorySettings become 'Additional settings from installer' (via 'extraSettings') and set values in
    // $civicrm_setting['domain'][k] = v;
    $model->mandatorySettings['userFrameworkResourceURL'] = $model->cmsBaseUrl . '/assets/civicrm/core';

    // These paths get set as
    // $civicrm_paths[k]['url'|'path'] = v
    $model->paths['cms.root'] = [
      'path' => $projectRootPath . DIRECTORY_SEPARATOR . 'web',
    ];
    $model->paths['civicrm.files'] = [
      'path' => rtrim($projectRootPath . DIRECTORY_SEPARATOR . 'web') . DIRECTORY_SEPARATOR . 'upload',
      'url' => $model->cmsBaseUrl . '/upload',
    ];

    // Compute default locale.
    $model->lang = $_REQUEST['lang'] ?? 'en_US';
  });
