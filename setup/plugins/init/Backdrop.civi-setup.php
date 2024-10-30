<?php
/**
 * @file
 *
 * Determine default settings for Backdrop.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.checkAuthorized', function (\Civi\Setup\Event\CheckAuthorizedEvent $e) {
    $model = $e->getModel();
    if ($model->cms !== 'Backdrop') {
      return;
    }

    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'checkAuthorized'));
    $e->setAuthorized(user_access('administer modules'));
  });


\Civi\Setup::dispatcher()
  ->addListener('civi.setup.init', function (\Civi\Setup\Event\InitEvent $e) {
    $model = $e->getModel();
    if ($model->cms !== 'Backdrop' || !function_exists('user_access')) {
      return;
    }
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'init'));

    $object = new \CRM_Utils_System_Backdrop();
    $cmsPath = $object->cmsRootPath();

    // Compute settingsPath.
    $model->settingsPath = $cmsPath . DIRECTORY_SEPARATOR . 'civicrm.settings.php';

    $model->templateCompilePath = 'FIXME';

    // Compute DSN.
    global $databases;
    $ssl_params = \Civi\Setup\DrupalUtil::guessSslParams($databases['default']['default']);
    // @todo Does Backdrop support unixsocket in config? Set 'server' => 'unix(/path/to/socket.sock)'
    $model->db = $model->cmsDb = array(
      'server' => \Civi\Setup\DbUtil::encodeHostPort($databases['default']['default']['host'], $databases['default']['default']['port'] ?: NULL),
      'username' => $databases['default']['default']['username'],
      'password' => $databases['default']['default']['password'],
      'database' => $databases['default']['default']['database'],
      'ssl_params' => empty($ssl_params) ? NULL : $ssl_params,
    );

    // Compute URLs
    global $base_url, $base_path;
    $model->cmsBaseUrl = $base_url . $base_path;

    // Compute general paths
    // $model->paths['civicrm.files']['url'] = $filePublicPath;
    $model->paths['civicrm.files']['path'] = implode(DIRECTORY_SEPARATOR,
      [_backdrop_civisetup_getPublicFiles(), 'civicrm']);
    $model->paths['civicrm.private']['path'] = implode(DIRECTORY_SEPARATOR,
      [_backdrop_civisetup_getPrivateFiles(), 'civicrm']);

    // Compute templateCompileDir.
    $model->templateCompilePath = implode(DIRECTORY_SEPARATOR,
      [_backdrop_civisetup_getPrivateFiles(), 'civicrm', 'templates_c']);

    // Compute default locale.
    global $language;
    $model->lang = \Civi\Setup\LocaleUtil::pickClosest($language->langcode, $model->getField('lang', 'options'));
  });

function _backdrop_civisetup_getPublicFiles() {
  $filePublicPath = variable_get('file_public_path', conf_path() . '/files');

  if (!CRM_Utils_File::isAbsolute($filePublicPath)) {
    $ufSystem = new CRM_Utils_System_Backdrop();
    $cmsPath = $ufSystem->cmsRootPath();
    $filePublicPath = $cmsPath . DIRECTORY_SEPARATOR . $filePublicPath;
  }

  // We sometimes get `/./` in the middle. That's silly.
  $DS = DIRECTORY_SEPARATOR;
  $filePublicPath = str_replace("$DS.$DS", $DS, $filePublicPath);

  return $filePublicPath;
}

function _backdrop_civisetup_getPrivateFiles() {
  $filePrivatePath = variable_get('file_private_path', '');

  if (!$filePrivatePath) {
    $filePrivatePath = _backdrop_civisetup_getPublicFiles();
  }
  elseif ($filePrivatePath && !CRM_Utils_File::isAbsolute($filePrivatePath)) {
    $ufSystem = new CRM_Utils_System_Backdrop();
    $cmsPath = $ufSystem->cmsRootPath();

    $filePrivatePath = $cmsPath . DIRECTORY_SEPARATOR . $filePrivatePath;
  }

  $DS = DIRECTORY_SEPARATOR;
  $filePrivatePath = str_replace("$DS.$DS", $DS, $filePrivatePath);

  return $filePrivatePath;
}
