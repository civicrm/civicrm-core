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
      return \Drupal::service('site.path');
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
    $siteDir = $matches[1] ?? 'default';

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

  /**
   * Guess if the CMS is using SSL for MySQL and what the corresponding
   * parameters should be for PEAR::DB.
   *
   * Not all combinations will work. See the install docs for a list of known
   * configurations that do. We don't enforce that here since we're just
   * trying to guess a default based on what they already have.
   *
   * @param array $cmsDatabaseParams
   *   The contents of the section from drupal's settings.php where it defines
   *   the $database array, usually under 'default'.
   * @return array
   *   The corresponding guessed params for PEAR::DB.
   */
  public static function guessSslParams(array $cmsDatabaseParams):array {
    // If the pdo-mysql extension isn't loaded or they have nothing in drupal
    // config for pdo, then we're done. PDO isn't required for Civi, but note
    // the references to PDO constants below would fail and they obviously
    // wouldn't have them in drupal config then.
    if (empty($cmsDatabaseParams['pdo']) || !extension_loaded('pdo_mysql')) {
      return [];
    }

    $pdo = $cmsDatabaseParams['pdo'];

    $pdo_map = [
      \PDO::MYSQL_ATTR_SSL_CA => 'ca',
      \PDO::MYSQL_ATTR_SSL_KEY => 'key',
      \PDO::MYSQL_ATTR_SSL_CERT => 'cert',
      \PDO::MYSQL_ATTR_SSL_CAPATH => 'capath',
      \PDO::MYSQL_ATTR_SSL_CIPHER => 'cipher',
    ];

    $ssl_params = [];

    // If they have one set in drupal config and it's a string, then copy
    // it over verbatim.
    foreach ($pdo_map as $pdo_name => $ssl_name) {
      if (!empty($pdo[$pdo_name]) && is_string($pdo[$pdo_name])) {
        $ssl_params[$ssl_name] = $pdo[$pdo_name];
      }
    }

    // No client certificate or server verification, but want SSL. Return our
    // made-up indicator ssl=1 that isn't a real mysqli option but which we
    // recognize. It's possible they have other params set too which we pass
    // along from above, but that may not be compatible but it's up to them.
    if (($pdo[\PDO::MYSQL_ATTR_SSL_CA] ?? NULL) === TRUE && ($pdo[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] ?? NULL) === FALSE) {
      $ssl_params['ssl'] = 1;
    }

    return $ssl_params;
  }

}
