<?php

namespace _CiviVersion_ {

  class Util {

    /**
     * Get the CiviCRM version
     */
    public static function findVersion() {
      $verFile = implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), 'xml', 'version.xml']);
      if (file_exists($verFile)) {
        $str = file_get_contents($verFile);
        $xmlObj = simplexml_load_string($str);
        return (string) $xmlObj->version_no;
      }

      trigger_error("Unknown version", E_USER_ERROR);
      exit();
    }

    /**
     * Get the CMS name
     */
    public static function findCMS() {
      if (defined('CIVICRM_UF')) {
        return CIVICRM_UF;
      }
      elseif (defined('BACKDROP_VERSION')) {
        return 'Backdrop';
      }
      elseif (function_exists('drupal_bootstrap') && version_compare(VERSION, '7.0', '>=') && version_compare(VERSION, '8.0', '<')) {
        return 'Drupal';
      }
      elseif (defined('ABSPATH') && function_exists('get_bloginfo')) {
        return 'WordPress';
      }
      elseif (defined('DRUPAL_ROOT') && class_exists('Drupal') && version_compare(\Drupal::VERSION, '8.0', '>=') && version_compare(\Drupal::VERSION, '9.0', '<')) {
        return 'Drupal8';
      }
      else {
        // guess CMS name from the current path
        list($cmsType,) = self::findCMSRootPath();

        if (!empty($cmsType)) {
          return $cmsType;
        }
      }
    }

    /**
     * Get the CMS root path and CMS name
     */
    public static function findCMSRootPath() {
      $cmsPatterns = array(
        'Wordpress' => array(
          'wp-includes/version.php',
          // Future? 'vendor/civicrm/wordpress/civicrm.php' => 'wp',
        ),
        'Joomla' => array(
          'administrator/components/com_civicrm/civicrm/civicrm-version.php',
        ),
        'Drupal' => array(
          // D7
          'modules/system/system.module',
        ),
        'Drupal8' => array(
          // D8
          'core/core.services.yml',
        ),
        'Backdrop' => array(
          'core/modules/layout/layout.module',
        ),
      );

      $parts = explode('/', str_replace('\\', '/', self::getSearchDir()));
      while (!empty($parts)) {
        $basePath = implode('/', $parts);

        foreach ($cmsPatterns as $cmsType => $relPaths) {
          foreach ($relPaths as $relPath) {
            $matches = glob("$basePath/$relPath");
            if (!empty($matches)) {
              return [$cmsType, $basePath];
            }
          }
        }

        array_pop($parts);
      }
    }

    /**
     * Get the current path
     */
    public static function getSearchDir() {
      if ($_SERVER['SCRIPT_FILENAME']) {
        return dirname($_SERVER['SCRIPT_FILENAME']);
      }
      // getenv('PWD') works better with symlinked source trees, but it's
      // not portable to Windows.
      if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        return getcwd();
      }
      else {
        return getenv('PWD');
      }
    }

  }
}

namespace {

  /**
   * Get the CiviCRM version.
   * TODO : For now this function is not included in \Civi\Version class so not to break any code
   *   which directly call civicrmVersion(). So those call need to replaced with \Civi\Version::civicrmVersion()
   *   when included in the class
   * @deprecated
   */
  function civicrmVersion() {
    return [
      'version' => \_CiviVersion_\Util::findVersion(),
      'cms' => \_CiviVersion_\Util::findCMS(),
    ];
  }

}
