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
    if ($model->cms !== 'Drupal') {
      return;
    }

    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'checkAuthorized'));
    $e->setAuthorized(user_access('administer modules'));
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
    $siteDir = _drupal_civisetup_getSiteDir($cmsPath, $_SERVER['SCRIPT_FILENAME']);
    $model->settingsPath = implode(DIRECTORY_SEPARATOR,
      [$cmsPath, 'sites', $siteDir, 'civicrm.settings.php']);

    // Compute DSN.
    global $databases;
    $model->db = $model->cmsDb = array(
      'server' => \Civi\Setup\DbUtil::encodeHostPort($databases['default']['default']['host'], $databases['default']['default']['port'] ?: NULL),
      'username' => $databases['default']['default']['username'],
      'password' => $databases['default']['default']['password'],
      'database' => $databases['default']['default']['database'],
    );

    // Compute cmsBaseUrl.
    global $base_url, $base_path;
    $model->cmsBaseUrl = $base_url . $base_path;

    // Compute general paths
    // $model->paths['civicrm.files']['url'] = $filePublicPath;
    $model->paths['civicrm.files']['path'] = implode(DIRECTORY_SEPARATOR,
      [_drupal_civisetup_getPublicFiles(), 'civicrm']);

    // Compute templateCompileDir.
    $model->templateCompilePath = implode(DIRECTORY_SEPARATOR,
      [_drupal_civisetup_getPrivateFiles(), 'civicrm', 'templates_c']);

    // Compute default locale.
    global $language;
    $model->lang = \Civi\Setup\LocaleUtil::pickClosest($language->langcode, $model->getField('lang', 'options'));
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

/**
 * @param $cmsPath
 * @param $str
 *
 * @return string
 */
function _drupal_civisetup_getSiteDir($cmsPath, $str) {
  static $siteDir = '';

  if ($siteDir) {
    return $siteDir;
  }

  $sites = CIVICRM_DIRECTORY_SEPARATOR . 'sites' . CIVICRM_DIRECTORY_SEPARATOR;
  $modules = CIVICRM_DIRECTORY_SEPARATOR . 'modules' . CIVICRM_DIRECTORY_SEPARATOR;
  preg_match("/" . preg_quote($sites, CIVICRM_DIRECTORY_SEPARATOR) .
    "([\-a-zA-Z0-9_.]+)" .
    preg_quote($modules, CIVICRM_DIRECTORY_SEPARATOR) . "/",
    $_SERVER['SCRIPT_FILENAME'], $matches
  );
  $siteDir = isset($matches[1]) ? $matches[1] : 'default';

  if (strtolower($siteDir) == 'all') {
    // For this case - use drupal's way of finding out multi-site directory
    $uri = explode(CIVICRM_DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']);
    $server = explode('.', implode('.', array_reverse(explode(':', rtrim($_SERVER['HTTP_HOST'], '.')))));
    for ($i = count($uri) - 1; $i > 0; $i--) {
      for ($j = count($server); $j > 0; $j--) {
        $dir = implode('.', array_slice($server, -$j)) . implode('.', array_slice($uri, 0, $i));
        if (file_exists($cmsPath . CIVICRM_DIRECTORY_SEPARATOR .
          'sites' . CIVICRM_DIRECTORY_SEPARATOR . $dir
        )) {
          $siteDir = $dir;
          return $siteDir;
        }
      }
    }
    $siteDir = 'default';
  }

  return $siteDir;
}
