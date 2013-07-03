<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Extension_Browser {

  /**
   * An URL for public extensions repository
   */
  const DEFAULT_EXTENSIONS_REPOSITORY = 'https://civicrm.org/extdir/ver={ver}|cms={uf}';

  /**
   * @param string $repoUrl URL of the remote repository
   * @param string $indexPath relative path of the 'index' file within the repository
   * @param string $cacheDir local path in which to cache files
   */
  public function __construct($repoUrl, $indexPath, $cacheDir) {
    $this->repoUrl = $repoUrl;
    $this->cacheDir = $cacheDir;
    $this->indexPath = $indexPath;
    if ($cacheDir && !file_exists($cacheDir) && is_dir(dirname($cacheDir)) && is_writeable(dirname($cacheDir))) {
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

  public function getRepositoryUrl() {
    return $this->repoUrl;
  }

  public function refresh() {
    $file = $this->getTsPath();
    if (file_exists($file)) {
      unlink($file);
    }
  }

  /**
   * Determine whether downloading is supported
   *
   * @return array list of error messages; empty if OK
   */
  public function checkRequirements() {
    if (!$this->isEnabled()) {
      return array();
    }

    $errors = array();

    if (!$this->cacheDir || !is_dir($this->cacheDir) || !is_writeable($this->cacheDir)) {
      $civicrmDestination = urlencode(CRM_Utils_System::url('civicrm/admin/extensions', 'reset=1'));
      $url = CRM_Utils_System::url('civicrm/admin/setting/path', "reset=1&civicrmDestination=${civicrmDestination}");
      $errors[] = array(
        'title' => ts('Directory Unwritable'),
        'message' => ts('Your extensions cache directory (%1) is not web server writable. Please go to the <a href="%2">path setting page</a> and correct it.<br/>',
          array(
            1 => $this->cacheDir,
            2 => $url,
          )
        )
      );
    }

    return $errors;
  }

  /**
   * Get a list of all available extensions
   *
   * @return array ($key => CRM_Extension_Info)
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
   * Get a description of a particular extension
   *
   * @return CRM_Extension_Info|NULL
   */
  public function getExtension($key) {
    // TODO optimize performance -- we don't need to fetch/cache the entire repo
    $exts = $this->getExtensions();
    if (array_key_exists($key, $exts)) {
      return $exts[$key];
    } else {
      // throw new CRM_Extension_Exception("Unknown remote extension: $key");
      return NULL;
    }
  }

  private function _discoverRemote() {
    $tsPath    = $this->getTsPath();
    $timestamp = FALSE;

    if (file_exists($tsPath)) {
      $timestamp = file_get_contents($tsPath);
    }

    // 3 minutes ago for now
    $outdated = (int) $timestamp < (time() - 180) ? TRUE : FALSE;

    if (!$timestamp || $outdated) {
      $remotes = $this->grabRemoteKeyList();
      $cached = FALSE;
    }
    else {
      $remotes = $this->grabCachedKeyList();
      $cached = TRUE;
    }

    $this->_remotesDiscovered = array();
    foreach ($remotes as $id => $rext) {
      $xml = $this->grabRemoteInfoFile($rext['key'], $cached);
      if ($xml != FALSE) {
        $ext = CRM_Extension_Info::loadFromString($xml);
        $this->_remotesDiscovered[] = $ext;
      }
    }

    if (file_exists(dirname($tsPath))) {
      file_put_contents($tsPath, (string) time());
    }

    return $this->_remotesDiscovered;
  }

  private function grabCachedKeyList() {
    $result     = array();
    $cachedPath = $this->cacheDir . DIRECTORY_SEPARATOR;
    $files      = scandir($cachedPath);
    foreach ($files as $dc => $fname) {
      if (substr($fname, -4) == '.xml') {
        $result[] = array('key' => substr($fname, 0, -4));
      }
    }
    return $result;
  }

  /**
   * Connects to public server and grabs the list of publically available
   * extensions.
   *
   * @access public
   *
   * @return Array list of extension names
   */
  private function grabRemoteKeyList() {

    ini_set('default_socket_timeout', CRM_Utils_VersionCheck::CHECK_TIMEOUT);
    set_error_handler(array('CRM_Extension_Browser', 'downloadError'));

    if (!ini_get('allow_url_fopen')) {
      ini_set('allow_url_fopen', 1);
    }

    if(FALSE === $this->getRepositoryUrl()) {
      // don't check if the user has configured civi not to check an external
      // url for extensions. See CRM-10575.
      CRM_Core_Session::setStatus(ts('Not checking remote URL for extensions since ext_repo_url is set to false.'), ts('Check Settings'), 'alert');
      return array();
    }

    list ($status, $extdir) = CRM_Utils_HttpClient::singleton()->get($this->getRepositoryUrl() . $this->indexPath);
    if ($extdir === FALSE || $status !== CRM_Utils_HttpClient::STATUS_OK) {
      CRM_Core_Session::setStatus(ts('The CiviCRM public extensions directory at %1 could not be contacted - please check your webserver can make external HTTP requests or contact CiviCRM team on <a href="http://forum.civicrm.org/">CiviCRM forum</a>.<br />', array(1 => $this->getRepositoryUrl())), ts('Connection Error'), 'error');
    }

    $lines = explode("\n", $extdir);

    foreach ($lines as $ln) {
      if (preg_match("@\<li\>(.*)\</li\>@i", $ln, $out)) {
        // success
        $extsRaw[] = $out;
        $key = strip_tags($out[1]);
        if (substr($key, -4) == '.xml') {
          $exts[] = array('key' => substr($key, 0, -4));
        }
      }
    }

    if (empty($exts)) {
      if ($extdir !== FALSE) {
        CRM_Core_Session::setStatus(ts('Could not retrieve a list of extensions from the CiviCRM public directory at %1 - please contact CiviCRM team on <a href="http://forum.civicrm.org/">CiviCRM forum</a>.<br />', array(1 => $this->getRepositoryUrl())), ts('Failed Fetching List'), 'error');
      }
      $exts = array();
    }

    ini_restore('allow_url_fopen');
    ini_restore('default_socket_timeout');

    restore_error_handler();

    return $exts;
  }

  /**
   * Given the key, retrieves the info XML from a remote server
   * and stores locally, returning the contents.
   *
   * @access public
   *
   * @param string $key extension key
   * @param boolean $cached whether to use cached data
   *
   * @return contents of info.xml, or null if info.xml cannot be retrieved or parsed
   */
  private function grabRemoteInfoFile($key, $cached = FALSE) {
    $filename = $this->cacheDir . DIRECTORY_SEPARATOR . $key . '.xml';
    $url      = $this->getRepositoryUrl() . '/' . $key . '.xml';

    if (!$cached || !file_exists($filename)) {
      $fetchStatus = CRM_Utils_HttpClient::singleton()->fetch($url, $filename);
      if ($fetchStatus != CRM_Utils_HttpClient::STATUS_OK) {
        return NULL;
      }
    }

    if (file_exists($filename)) {
      $contents = file_get_contents($filename);

      //parse just in case
      $check = simplexml_load_string($contents);

      if (!$check) {
        foreach (libxml_get_errors() as $error) {
          CRM_Core_Error::debug('xmlError', $error);
        }
        return;
      }

      return $contents;
    }
  }

  private function getTsPath() {
      return $this->cacheDir . DIRECTORY_SEPARATOR . 'timestamp.txt';
  }

  /**
   * A dummy function required for suppressing download errors
   */
  public static function downloadError($errorNumber, $errorString) {
    return;
  }

}