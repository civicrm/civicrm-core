<?php
/**
 * @file
 *
 * Determine default settings for Standalone.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

function _standalone_setup_scheme(): string {
  if ((!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') ||
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ||
    (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')) {
    return 'https';
  }
  else {
    return 'http';
  }
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

    // Compute DSN.
    $model->db = $model->cmsDb = [
      'server' => 'localhost',
      'username' => '',
      'password' => '',
      'database' => '',
    ];

    // Compute default locale.
    $model->lang = $_REQUEST['lang'] ?? 'en_US';

    // Compute paths and urls

    // get globals set in civicrm.standalone.php
    global $appRootPath, $settingsPath;

    // sometimes when using cv these global won't be set
    if (!$appRootPath) {
      $appRootCandidate = $model->srcPath;
      while ($appRootCandidate && $appRootCandidate != '/') {
        $appRootCandidate = dirname($appRootCandidate);

        if (file_exists(implode(DIRECTORY_SEPARATOR, [$appRootCandidate, 'civicrm.standalone.php']))) {
          $appRootPath = $appRootCandidate;
          break;
        }
      }
      if (!$appRootPath) {
        throw new \Exception("Can't locate Standalone root path as source path is not set.");
      }
    }
    if (!$settingsPath) {
      $settingsPath = implode(DIRECTORY_SEPARATOR, [$appRootPath, 'private', 'civicrm.settings.php']);
    }

    // try to determine base url if we dont have already (e.g. from buildkit)
    // TODO:
    // a) this won't work if we are installing in a subdirectory of the webroot
    // b) https detection might be problematic behind a reverse proxy
    if (empty($model->cmsBaseUrl)) {
      $model->cmsBaseUrl = _standalone_setup_scheme() . '://' . $_SERVER['HTTP_HOST'];
    }

    // TODO: at the moment the installer will only work when app root = web root
    $model->paths['cms.root']['path'] = $appRootPath;
    $model->paths['cms.root']['url'] = $baseUrl = $model->cmsBaseUrl;

    // we should already know settings path from civicrm.standalone.php
    $model->settingsPath = $settingsPath;

    // private directories
    $model->paths['civicrm.private']['path'] = $privatePath = $appRootPath . '/private';
    $model->paths['civicrm.compile']['path'] = $model->templateCompilePath = $privatePath . '/cache';
    $model->paths['civicrm.log']['path'] = $privatePath . '/log';
    $model->paths['civicrm.l10n']['path'] = $privatePath . '/l10n';
    $model->mandatorySettings['customFileUploadDir'] = '[cms.root]/private/attachment';
    $model->mandatorySettings['uploadDir'] = '[cms.root]/private/tmp';

    // public directories
    $model->paths['civicrm.files']['path'] = $appRootPath . '/public';
    $model->paths['civicrm.files']['url'] = $baseUrl . '/public';

    $model->mandatorySettings['imageUploadDir'] = '[cms.root]/public/media';
    $model->mandatorySettings['imageUploadURL'] = '[cms.root]/public/media';

    // extensions directory
    $model->mandatorySettings['extensionsDir'] = '[cms.root]/ext';
    $model->mandatorySettings['extensionsURL'] = '[cms.root]/ext';

    if (\Composer\InstalledVersions::isInstalled('civicrm/civicrm-asset-plugin')) {
      // civicrm-asset-plugin loads core asset paths directly into the $civicrm_paths global

      // we need to set the civicrm.root url on the model so it can be used to load assets in the web UI
      $model->paths['civicrm.root']['url'] = $GLOBALS['civicrm_paths']['civicrm.root']['url'];
    }
    else {
      // if not using composer, dependencies will be inside the civicrm core directory
      $model->paths['civicrm.root']['path'] = $corePath = $appRootPath . '/core';
      $model->paths['civicrm.root']['url'] = $coreUrl = $baseUrl . '/core';

      $model->paths['civicrm.vendor']['path'] = $corePath . '/vendor';
      $model->paths['civicrm.vendor']['url'] = $coreUrl . '/vendor';

      $model->paths['civicrm.bower']['path'] = $corePath . '/bower_components';
      $model->paths['civicrm.bower']['url'] = $coreUrl . '/bower_components';

      $model->paths['civicrm.packages']['path'] = $corePath . '/packages';
      $model->paths['civicrm.packages']['url'] = $coreUrl . '/packages';
    }
  });
