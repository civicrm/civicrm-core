<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * This class glues together the various parts of the extension
 * system.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Extension_Browser {

  /**
   * An URL for public extensions repository.
   *
   * Note: This default is now handled through setting/*.php.
   *
   * @deprecated
   */
  const DEFAULT_EXTENSIONS_REPOSITORY = 'https://civicrm.org/extdir/ver={ver}|cms={uf}';

  /**
   * Relative path below remote repository URL for single extensions file.
   */
  const SINGLE_FILE_PATH = '/single';

  /**
   * The name of the single JSON extension cache file.
   */
  const CACHE_JSON_FILE = 'extensions.json';

  // timeout for when the connection or the server is slow
  const CHECK_TIMEOUT = 5;

  /**
   * @param string $repoUrl
   *   URL of the remote repository.
   * @param string $indexPath
   *   Relative path of the 'index' file within the repository.
   * @param string $cacheDir
   *   Local path in which to cache files.
   */
  public function __construct($repoUrl, $indexPath, $cacheDir) {
    $this->repoUrl = $repoUrl;
    $this->cacheDir = $cacheDir;
    $this->indexPath = empty($indexPath) ? self::SINGLE_FILE_PATH : $indexPath;
    if ($cacheDir && !file_exists($cacheDir) && is_dir(dirname($cacheDir)) && is_writable(dirname($cacheDir))) {
      CRM_Utils_File::createDir($cacheDir, FALSE);
    }
  }

  /**
   * Determine whether the system policy allows downloading new extensions.
   *
   * This is reflection of *policy* and *intent*; it does not indicate whether
   * the browser will actually *work*. For that, see checkRequirements().
   *
   * @return bool
   */
  public function isEnabled() {
    return (FALSE !== $this->getRepositoryUrl());
  }

  /**
   * @return string
   */
  public function getRepositoryUrl() {
    return $this->repoUrl;
  }

  /**
   * Refresh the cache of remotely-available extensions.
   */
  public function refresh() {
    $file = $this->getTsPath();
    if (file_exists($file)) {
      unlink($file);
    }
  }

  /**
   * Determine whether downloading is supported.
   *
   * @return array
   *   List of error messages; empty if OK.
   */
  public function checkRequirements() {
    if (!$this->isEnabled()) {
      return array();
    }

    $errors = array();

    if (!$this->cacheDir || !is_dir($this->cacheDir) || !is_writable($this->cacheDir)) {
      $civicrmDestination = urlencode(CRM_Utils_System::url('civicrm/admin/extensions', 'reset=1'));
      $url = CRM_Utils_System::url('civicrm/admin/setting/path', "reset=1&civicrmDestination=${civicrmDestination}");
      $errors[] = array(
        'title' => ts('Directory Unwritable'),
        'message' => ts('Your extensions cache directory (%1) is not web server writable. Please go to the <a href="%2">path setting page</a> and correct it.<br/>',
          array(
            1 => $this->cacheDir,
            2 => $url,
          )
        ),
      );
    }

    return $errors;
  }

  /**
   * Get a list of all available extensions.
   *
   * @return array
   *   ($key => CRM_Extension_Info)
   */
  public function getExtensions() {
    if (!$this->isEnabled() || count($this->checkRequirements())) {
      return array();
    }

    $exts = array();

    $remote = $this->_discoverRemote();
    if (is_array($remote)) {
      foreach ($remote as $dc => $e) {
        $exts[$e->key] = $e;
      }
    }

    return $exts;
  }

  /**
   * Get a description of a particular extension.
   *
   * @param string $key
   *   Fully-qualified extension name.
   *
   * @return CRM_Extension_Info|NULL
   */
  public function getExtension($key) {
    // TODO optimize performance -- we don't need to fetch/cache the entire repo
    $exts = $this->getExtensions();
    if (array_key_exists($key, $exts)) {
      return $exts[$key];
    }
    else {
      return NULL;
    }
  }

  /**
   * @return array
   * @throws CRM_Extension_Exception_ParseException
   */
  private function _discoverRemote() {
    $tsPath = $this->getTsPath();
    $timestamp = FALSE;

    if (file_exists($tsPath)) {
      $timestamp = file_get_contents($tsPath);
    }

    // 3 minutes ago for now
    $outdated = (int) $timestamp < (time() - 180) ? TRUE : FALSE;

    if (!$timestamp || $outdated) {
      $remotes = json_decode($this->grabRemoteJson(), TRUE);
    }
    else {
      $remotes = json_decode($this->grabCachedJson(), TRUE);
    }

    $this->_remotesDiscovered = array();
    foreach ((array) $remotes as $id => $xml) {
      $ext = CRM_Extension_Info::loadFromString($xml);
      $this->_remotesDiscovered[] = $ext;
    }

    if (file_exists(dirname($tsPath))) {
      file_put_contents($tsPath, (string) time());
    }

    return $this->_remotesDiscovered;
  }

  /**
   * Loads the extensions data from the cache file. If it is empty
   * or doesn't exist, try fetching from remote instead.
   *
   * @return string
   */
  private function grabCachedJson() {
    $filename = $this->cacheDir . DIRECTORY_SEPARATOR . self::CACHE_JSON_FILE;
    $json = file_get_contents($filename);
    if (empty($json)) {
      $json = $this->grabRemoteJson();
    }
    return $json;
  }

  /**
   * Connects to public server and grabs the list of publicly available
   * extensions.
   *
   * @return string
   * @throws \CRM_Extension_Exception
   */
  private function grabRemoteJson() {

    ini_set('default_socket_timeout', self::CHECK_TIMEOUT);
    set_error_handler(array('CRM_Extension_Browser', 'downloadError'));

    if (!ini_get('allow_url_fopen')) {
      ini_set('allow_url_fopen', 1);
    }

    if (FALSE === $this->getRepositoryUrl()) {
      // don't check if the user has configured civi not to check an external
      // url for extensions. See CRM-10575.
      return array();
    }

    $filename = $this->cacheDir . DIRECTORY_SEPARATOR . self::CACHE_JSON_FILE;
    $url = $this->getRepositoryUrl() . $this->indexPath;
    $status = CRM_Utils_HttpClient::singleton()->fetch($url, $filename);

    ini_restore('allow_url_fopen');
    ini_restore('default_socket_timeout');

    restore_error_handler();

    if ($status !== CRM_Utils_HttpClient::STATUS_OK) {
      throw new CRM_Extension_Exception(ts('The CiviCRM public extensions directory at %1 could not be contacted - please check your webserver can make external HTTP requests or contact CiviCRM team on <a href="http://forum.civicrm.org/">CiviCRM forum</a>.', array(1 => $this->getRepositoryUrl())), 'connection_error');
    }

    // Don't call grabCachedJson here, that would risk infinite recursion
    return file_get_contents($filename);
  }

  /**
   * @return string
   */
  private function getTsPath() {
    return $this->cacheDir . DIRECTORY_SEPARATOR . 'timestamp.txt';
  }

  /**
   * A dummy function required for suppressing download errors.
   *
   * @param $errorNumber
   * @param $errorString
   */
  public static function downloadError($errorNumber, $errorString) {
  }

}
