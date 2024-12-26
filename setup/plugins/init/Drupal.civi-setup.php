<?php
/**
 * @file
 *
 * Determine default settings for Drupal 7.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.checkAuthorized', function (\Civi\Setup\Event\CheckAuthorizedEvent $e) {
    $model = $e->getModel();
    if ($model->cms !== 'Drupal' || !function_exists('user_access')) {
      return;
    }

    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'checkAuthorized'));
    $e->setAuthorized(\Civi\Setup\DrupalUtil::isDrush() || user_access('administer modules'));
  });

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.init', function (\Civi\Setup\Event\InitEvent $e) {
    $model = $e->getModel();
    if ($model->cms !== 'Drupal' || !function_exists('user_access')) {
      return;
    }
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'init'));

    // Compute settingsPath.
    $drupalSystem = new CRM_Utils_System_Drupal();
    $cmsPath = $drupalSystem->cmsRootPath();
    $siteDir = \Civi\Setup\DrupalUtil::getDrupalSiteDir($cmsPath);
    $model->settingsPath = implode(DIRECTORY_SEPARATOR,
      [$cmsPath, 'sites', $siteDir, 'civicrm.settings.php']);

    // Compute DSN.
    global $databases;
    $ssl_params = \Civi\Setup\DrupalUtil::guessSslParams($databases['default']['default']);
    // @todo Does Drupal support unixsocket in config? Set 'server' => 'unix(/path/to/socket.sock)'
    $model->db = $model->cmsDb = array(
      'server' => \Civi\Setup\DbUtil::encodeHostPort($databases['default']['default']['host'], $databases['default']['default']['port'] ?: NULL),
      'username' => $databases['default']['default']['username'],
      'password' => $databases['default']['default']['password'],
      'database' => $databases['default']['default']['database'],
      'ssl_params' => empty($ssl_params) ? NULL : $ssl_params,
    );

    // Compute cmsBaseUrl.
    global $base_url, $base_path;
    $model->cmsBaseUrl = $base_url . $base_path;

    // Compute general paths
    // $model->paths['civicrm.files']['url'] = $filePublicPath;
    $model->paths['civicrm.files']['path'] = implode(DIRECTORY_SEPARATOR,
      [_drupal_civisetup_getPublicFiles(), 'civicrm']);
    $model->paths['civicrm.private']['path'] = implode(DIRECTORY_SEPARATOR,
      [_drupal_civisetup_getPrivateFiles(), 'civicrm']);

    // Compute templateCompileDir.
    $model->templateCompilePath = implode(DIRECTORY_SEPARATOR,
      [_drupal_civisetup_getPrivateFiles(), 'civicrm', 'templates_c']);

    // Compute default locale.
    global $language;
    $model->lang = \Civi\Setup\LocaleUtil::pickClosest($language->langcode ?? NULL, $model->getField('lang', 'options'));
  });

function _drupal_civisetup_getPublicFiles() {
  $filePublicPath = variable_get('file_public_path', conf_path() . '/files');

  if (!CRM_Utils_File::isAbsolute($filePublicPath)) {
    $drupalSystem = new CRM_Utils_System_Drupal();
    $cmsPath = $drupalSystem->cmsRootPath();
    $filePublicPath = $cmsPath . DIRECTORY_SEPARATOR . $filePublicPath;
  }

  return $filePublicPath;
}

function _drupal_civisetup_getPrivateFiles() {
  $filePrivatePath = variable_get('file_private_path', '');

  if (!$filePrivatePath) {
    $filePrivatePath = _drupal_civisetup_getPublicFiles();
  }
  elseif ($filePrivatePath && !CRM_Utils_File::isAbsolute($filePrivatePath)) {
    $drupalSystem = new CRM_Utils_System_Drupal();
    $cmsPath = $drupalSystem->cmsRootPath();

    $filePrivatePath = $cmsPath . DIRECTORY_SEPARATOR . $filePrivatePath;
  }

  return $filePrivatePath;
}
