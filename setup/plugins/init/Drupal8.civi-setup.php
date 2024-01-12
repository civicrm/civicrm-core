<?php
/**
 * @file
 *
 * Determine default settings for Drupal 8.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.checkAuthorized', function (\Civi\Setup\Event\CheckAuthorizedEvent $e) {
    $model = $e->getModel();
    if ($model->cms !== 'Drupal8' || !is_callable(['Drupal', 'currentUser'])) {
      return;
    }

    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'checkAuthorized'));
    $e->setAuthorized(\Civi\Setup\DrupalUtil::isDrush() || \Drupal::currentUser()->hasPermission('administer modules'));
  });

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.init', function (\Civi\Setup\Event\InitEvent $e) {
    $model = $e->getModel();
    if ($model->cms !== 'Drupal8' || !is_callable(['Drupal', 'currentUser'])) {
      return;
    }
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'init'));

    $cmsPath = \Drupal::root();

    // Compute settingsPath.
    $siteDir = \Civi\Setup\DrupalUtil::getDrupalSiteDir($cmsPath);
    $model->settingsPath = implode(DIRECTORY_SEPARATOR, [$cmsPath, $siteDir, 'civicrm.settings.php']);

    if (($loadGenerated = \Drupal\Core\Site\Settings::get('civicrm_load_generated', NULL)) !== NULL) {
      $model->loadGenerated = $loadGenerated;
    }

    // Compute DSN.
    $connectionOptions = \Drupal::database()->getConnectionOptions();
    $ssl_params = \Civi\Setup\DrupalUtil::guessSslParams($connectionOptions);
    // @todo Does Drupal support unixsocket in config? Set 'server' => 'unix(/path/to/socket.sock)'
    $model->db = $model->cmsDb = array(
      'server' => \Civi\Setup\DbUtil::encodeHostPort($connectionOptions['host'], $connectionOptions['port'] ?? NULL),
      'username' => $connectionOptions['username'],
      'password' => $connectionOptions['password'],
      'database' => $connectionOptions['database'],
      'ssl_params' => empty($ssl_params) ? NULL : $ssl_params,
    );

    // Compute cmsBaseUrl.
    if (empty($model->cmsBaseUrl)) {
      global $base_url, $base_path;
      $model->cmsBaseUrl = $base_url . $base_path;
    }

    // Compute general paths
    $model->paths['civicrm.files']['url'] = implode('/', [$model->cmsBaseUrl, \Drupal\Core\StreamWrapper\PublicStream::basePath(), 'civicrm']);
    $model->paths['civicrm.files']['path'] = implode(DIRECTORY_SEPARATOR, [_drupal8_civisetup_getPublicFiles(), 'civicrm']);
    $model->paths['civicrm.private']['path'] = implode(DIRECTORY_SEPARATOR, [_drupal8_civisetup_getPrivateFiles(), 'civicrm']);

    // Compute templateCompileDir.
    $model->templateCompilePath = implode(DIRECTORY_SEPARATOR, [_drupal8_civisetup_getPrivateFiles(), 'civicrm', 'templates_c']);

    // Compute default locale.
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $model->lang = \Civi\Setup\LocaleUtil::pickClosest($langcode, $model->getField('lang', 'options'));
  });

function _drupal8_civisetup_getPublicFiles() {
  $filePublicPath = \Drupal\Core\StreamWrapper\PublicStream::basePath();

  if (!$filePublicPath) {
    throw new \Civi\Setup\Exception\InitException("Failed to identify public files path");
  }
  elseif (!CRM_Utils_File::isAbsolute($filePublicPath)) {
    $filePublicPath = \Drupal::root() . DIRECTORY_SEPARATOR . $filePublicPath;
  }

  return $filePublicPath;
}

function _drupal8_civisetup_getPrivateFiles() {
  $filePrivatePath = \Drupal\Core\StreamWrapper\PrivateStream::basePath();

  if (!$filePrivatePath) {
    $filePrivatePath = _drupal8_civisetup_getPublicFiles();
  }
  elseif ($filePrivatePath && !CRM_Utils_File::isAbsolute($filePrivatePath)) {
    $filePrivatePath = \Drupal::root() . DIRECTORY_SEPARATOR . $filePrivatePath;
  }

  return $filePrivatePath;
}
