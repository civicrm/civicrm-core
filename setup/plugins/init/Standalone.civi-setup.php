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

    // error_log('artfulrobot: ' . __FILE__ . ' listener for civi.setup.init');
    // Compute settingsPath.
    // We use this structure: /var/www/standalone/data/{civicrm.settings.php,templates_c}
    // to reduce the number of directories that admins have to chmod

    /**
     * @var string $projectRootPath
     *       refers to the root of the *application*, not the actual webroot as reachable by http.
     *       Typically, this means that $projectRootPath might be like /var/www/example.org/ and
     *       the actual web root would be /var/www/example.org/web/
     */
    if (!empty($model->extras['standaloneRoot'])) {
      $projectRootPath = $model->extras['standaloneRoot'];
    }
    else {
      $candidates = [
        // Ex: Clone ~/src/civicrm-core; use PHP built-in server and standalone.
        $model->srcPath . '/srv',

        // Ex: Make a vhost and clone `civicrm-core` as `HTTP_ROOT/core`
        dirname($model->srcPath, 2),

        // Ex: Clone `civicrm-standalone` which depends on `civicrm-core`. Use Apache/nginx/etc.
        dirname($model->srcPath, 3),
      ];
      foreach ($candidates as $candidate) {
        if (file_exists($candidate . '/civicrm.config.php.standalone')) {
          $projectRootPath = $model->extras['standaloneRoot'] = $candidate;
          break;
        }
      }
    }
    if (empty($projectRootPath)) {
      throw new \RuntimeException("Failed to identify standalone root. (TIP: Set extras.standaloneRoot)");
    }
    $model->paths['civicrm.private']['path'] = implode(DIRECTORY_SEPARATOR, [$projectRootPath, 'data']);
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
      $model->cmsBaseUrl = _standalone_setup_scheme() . '://' . $_SERVER['HTTP_HOST'];
    }

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

    if (\Composer\InstalledVersions::isInstalled('civicrm/civicrm-asset-plugin')) {
      $model->mandatorySettings['userFrameworkResourceURL'] = $model->cmsBaseUrl . '/assets/civicrm/core';
      // civicrm-asset-plugin will fill-in various $paths.
    }
    else {
      $model->mandatorySettings['userFrameworkResourceURL'] = $model->cmsBaseUrl . '/core';
      $model->paths['civicrm.core']['url'] = $model->cmsBaseUrl . '/core';
      $model->paths['civicrm.core']['path'] = $model->srcPath;
      $model->paths['civicrm.vendor']['url'] = $model->cmsBaseUrl . '/core/vendor';
      $model->paths['civicrm.vendor']['path'] = $model->srcPath . '/vendor';
      $model->paths['civicrm.bower']['url'] = $model->cmsBaseUrl . '/core/bower_components';
      $model->paths['civicrm.bower']['path'] = $model->srcPath . '/bower_components';
      $model->paths['civicrm.packages']['url'] = $model->cmsBaseUrl . '/core/packages';
      $model->paths['civicrm.packages']['path'] = file_exists($model->srcPath . '/packages')
          ? $model->srcPath . '/packages'
          : dirname($model->srcPath) . '/civicrm-packages';
    }
  });
