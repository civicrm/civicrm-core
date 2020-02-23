<?php
namespace Civi\Setup;

class DrupalUtil {

  /**
   * @return bool
   */
  public static function isDrush() {
    return PHP_SAPI === 'cli' && function_exists('drush_main');
  }

  /**
   * @param $cmsPath
   *
   * @return string
   */
  public static function getDrupalSiteDir($cmsPath) {
    if (function_exists('conf_path')) {
      return basename(conf_path());
    }
    elseif (class_exists('Drupal')) {
      return basename(\Drupal::service('site.path'));
    }
    else {
      throw new \Exception('Cannot detect path under Drupal "sites/".');
      // The old 'install/index.php' system duplicated the conf_path() logic so that it could work pre-boot.
      // With civicrm-setup, the CMS should always be booted first, so we should never go down this path.
      // For the moment, the code is kept below in case it turns out we do need this for some reason.
    }

    /*
    static $siteDir = '';

    if ($siteDir) {
    return $siteDir;
    }

    // The SCRIPT_FILENAME check was copied over from the 'install/index.php' system.
    // It probably doesn't make sense in the context of civicrm-setup b/c we don't know what the SCRIPT will be
    // and instead rely on $model inputs.

    $sites = DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR;
    $modules = DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR;
    preg_match("/" . preg_quote($sites, DIRECTORY_SEPARATOR) .
    "([\-a-zA-Z0-9_.]+)" .
    preg_quote($modules, DIRECTORY_SEPARATOR) . "/",
    $_SERVER['SCRIPT_FILENAME'], $matches
    );
    $siteDir = isset($matches[1]) ? $matches[1] : 'default';

    if (strtolower($siteDir) == 'all') {
    // For this case - use drupal's way of finding out multi-site directory
    $uri = explode(DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']);
    $server = explode('.', implode('.', array_reverse(explode(':', rtrim($_SERVER['HTTP_HOST'], '.')))));
    for ($i = count($uri) - 1; $i > 0; $i--) {
    for ($j = count($server); $j > 0; $j--) {
    $dir = implode('.', array_slice($server, -$j)) . implode('.', array_slice($uri, 0, $i));
    if (file_exists($cmsPath . DIRECTORY_SEPARATOR .
    'sites' . DIRECTORY_SEPARATOR . $dir
    )) {
    $siteDir = $dir;
    return $siteDir;
    }
    }
    }
    $siteDir = 'default';
    }

    return $siteDir;
     */
  }

}
