<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
function civicrm_setup($filesDirectory) {
  global $crmPath, $sqlPath, $pkgPath, $tplPath;
  global $compileDir;

  $pkgPath = $crmPath . DIRECTORY_SEPARATOR . 'packages';
  set_include_path($crmPath . PATH_SEPARATOR .
    $pkgPath . PATH_SEPARATOR .
    get_include_path()
  );

  $sqlPath = $crmPath . DIRECTORY_SEPARATOR . 'sql';
  $tplPath = $crmPath . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'CRM' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR;

  if (!is_dir($filesDirectory)) {
    mkdir($filesDirectory, 0777);
    chmod($filesDirectory, 0777);
  }

  $scratchDir = $filesDirectory . DIRECTORY_SEPARATOR . 'civicrm';
  if (!is_dir($scratchDir)) {
    mkdir($scratchDir, 0777);
  }

  $compileDir = $scratchDir . DIRECTORY_SEPARATOR . 'templates_c' . DIRECTORY_SEPARATOR;
  if (!is_dir($compileDir)) {
    mkdir($compileDir, 0777);
  }
  $compileDir = addslashes($compileDir);
}

/**
 * @param $name
 * @param $buffer
 */
function civicrm_write_file($name, &$buffer) {
  $fd = fopen($name, "w");
  if (!$fd) {
    die("Cannot open $name");
  }
  fputs($fd, $buffer);
  fclose($fd);
}

/**
 * @param $config
 */
function civicrm_main(&$config) {
  global $sqlPath, $crmPath, $cmsPath, $installType;

  if ($installType == 'drupal') {
    $siteDir = isset($config['site_dir']) ? $config['site_dir'] : getSiteDir($cmsPath, $_SERVER['SCRIPT_FILENAME']);
    civicrm_setup($cmsPath . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR .
      $siteDir . DIRECTORY_SEPARATOR . 'files'
    );
  }
  elseif ($installType == 'wordpress') {
    civicrm_setup(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'files');
  }

  $dsn = "mysql://{$config['mysql']['username']}:{$config['mysql']['password']}@{$config['mysql']['server']}/{$config['mysql']['database']}?new_link=true";

  civicrm_source($dsn, $sqlPath . DIRECTORY_SEPARATOR . 'civicrm.mysql');

  if (!empty($config['loadGenerated'])) {
    civicrm_source($dsn, $sqlPath . DIRECTORY_SEPARATOR . 'civicrm_generated.mysql', TRUE);
  }
  else {
    if (isset($config['seedLanguage'])
      and preg_match('/^[a-z][a-z]_[A-Z][A-Z]$/', $config['seedLanguage'])
      and file_exists($sqlPath . DIRECTORY_SEPARATOR . "civicrm_data.{$config['seedLanguage']}.mysql")
      and file_exists($sqlPath . DIRECTORY_SEPARATOR . "civicrm_acl.{$config['seedLanguage']}.mysql")
    ) {
      civicrm_source($dsn, $sqlPath . DIRECTORY_SEPARATOR . "civicrm_data.{$config['seedLanguage']}.mysql");
      civicrm_source($dsn, $sqlPath . DIRECTORY_SEPARATOR . "civicrm_acl.{$config['seedLanguage']}.mysql");
    }
    else {
      civicrm_source($dsn, $sqlPath . DIRECTORY_SEPARATOR . 'civicrm_data.mysql');
      civicrm_source($dsn, $sqlPath . DIRECTORY_SEPARATOR . 'civicrm_acl.mysql');
    }
  }

  // generate backend settings file
  if ($installType == 'drupal') {
    $configFile = $cmsPath . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . $siteDir . DIRECTORY_SEPARATOR . 'civicrm.settings.php';
  }
  elseif ($installType == 'wordpress') {
    $configFile = $cmsPath . DIRECTORY_SEPARATOR . 'civicrm.settings.php';
  }

  $string = civicrm_config($config);
  civicrm_write_file($configFile,
    $string
  );

}

/**
 * @param $dsn
 * @param $fileName
 * @param bool $lineMode
 */
function civicrm_source($dsn, $fileName, $lineMode = FALSE) {
  global $crmPath;

  require_once "$crmPath/packages/DB.php";

  $db = DB::connect($dsn);
  if (PEAR::isError($db)) {
    die("Cannot open $dsn: " . $db->getMessage());
  }
  $db->query("SET NAMES utf8");

  $db->query("SET NAMES utf8");

  if (!$lineMode) {
    $string = file_get_contents($fileName);

    // change \r\n to fix windows issues
    $string = str_replace("\r\n", "\n", $string);

    //get rid of comments starting with # and --

    $string = preg_replace("/^#[^\n]*$/m", "\n", $string);
    $string = preg_replace("/^(--[^-]).*/m", "\n", $string);

    $queries = preg_split('/;\s*$/m', $string);
    foreach ($queries as $query) {
      $query = trim($query);
      if (!empty($query)) {
        $res = &$db->query($query);
        if (PEAR::isError($res)) {
          print_r($res);
          die("Cannot execute $query: " . $res->getMessage());
        }
      }
    }
  }
  else {
    $fd = fopen($fileName, "r");
    while ($string = fgets($fd)) {
      $string = preg_replace("/^#[^\n]*$/m", "\n", $string);
      $string = preg_replace("/^(--[^-]).*/m", "\n", $string);

      $string = trim($string);
      if (!empty($string)) {
        $res = &$db->query($string);
        if (PEAR::isError($res)) {
          die("Cannot execute $string: " . $res->getMessage());
        }
      }
    }
  }
}

/**
 * @param $config
 *
 * @return string
 */
function civicrm_config(&$config) {
  global $crmPath, $comPath;
  global $compileDir;
  global $tplPath, $installType;

  $params = array(
    'crmRoot' => $crmPath,
    'templateCompileDir' => $compileDir,
    'frontEnd' => 0,
    'dbUser' => addslashes($config['mysql']['username']),
    'dbPass' => addslashes($config['mysql']['password']),
    'dbHost' => $config['mysql']['server'],
    'dbName' => addslashes($config['mysql']['database']),
  );

  $params['baseURL'] = isset($config['base_url']) ? $config['base_url'] : civicrm_cms_base();
  if ($installType == 'drupal') {
    if (version_compare(VERSION, '7.0-rc1') >= 0) {
      $params['cms']       = 'Drupal';
      $params['CMSdbUser'] = addslashes($config['drupal']['username']);
      $params['CMSdbPass'] = addslashes($config['drupal']['password']);
      $params['CMSdbHost'] = $config['drupal']['server'];
      $params['CMSdbName'] = addslashes($config['drupal']['database']);
    }
    elseif (version_compare(VERSION, '6.0') >= 0) {
      $params['cms']       = 'Drupal6';
      $params['CMSdbUser'] = addslashes($config['drupal']['username']);
      $params['CMSdbPass'] = addslashes($config['drupal']['password']);
      $params['CMSdbHost'] = $config['drupal']['server'];
      $params['CMSdbName'] = addslashes($config['drupal']['database']);
    }
  }
  else {
    $params['cms']       = 'WordPress';
    $params['CMSdbUser'] = addslashes(DB_USER);
    $params['CMSdbPass'] = addslashes(DB_PASSWORD);
    $params['CMSdbHost'] = DB_HOST;
    $params['CMSdbName'] = addslashes(DB_NAME);

    // CRM-12386
    $params['crmRoot'] = addslashes($params['crmRoot']);
  }

  $params['siteKey'] = md5(uniqid('', TRUE) . $params['baseURL']);

  $str = file_get_contents($tplPath . 'civicrm.settings.php.template');
  foreach ($params as $key => $value) {
    $str = str_replace('%%' . $key . '%%', $value, $str);
  }
  return trim($str);
}

/**
 * @return string
 */
function civicrm_cms_base() {
  global $installType;

  // for drupal
  $numPrevious = 6;

  if (isset($_SERVER['HTTPS']) &&
    !empty($_SERVER['HTTPS']) &&
    strtolower($_SERVER['HTTPS']) != 'off'
  ) {
    $url = 'https://' . $_SERVER['HTTP_HOST'];
  }
  else {
    $url = 'http://' . $_SERVER['HTTP_HOST'];
  }

  $baseURL = $_SERVER['SCRIPT_NAME'];

  if ($installType == 'drupal') {
    //don't assume 6 dir levels, as civicrm
    //may or may not be in sites/all/modules/
    //lets allow to install in custom dir. CRM-6840
    global $cmsPath;
    $crmDirLevels = str_replace($cmsPath, '', str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']));
    $baseURL = str_replace($crmDirLevels, '', str_replace('\\', '/', $baseURL));
  }
  elseif ($installType == 'wordpress') {
    $baseURL = str_replace($url, '', site_url());
  }
  else {
    for ($i = 1; $i <= $numPrevious; $i++) {
      $baseURL = dirname($baseURL);
    }
  }

  // remove the last directory separator string from the directory
  if (substr($baseURL, -1, 1) == DIRECTORY_SEPARATOR) {
    $baseURL = substr($baseURL, 0, -1);
  }

  // also convert all DIRECTORY_SEPARATOR to the forward slash for windoze
  $baseURL = str_replace(DIRECTORY_SEPARATOR, '/', $baseURL);

  if ($baseURL != '/') {
    $baseURL .= '/';
  }

  return $url . $baseURL;
}

/**
 * @return string
 */
function civicrm_home_url() {
  $drupalURL = civicrm_cms_base();
  return $drupalURL . 'index.php?q=civicrm';
}

