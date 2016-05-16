<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * This class stores logic for managing CiviCRM extensions.
 * On this level, we are only manipulating extension objects.
 * Refer to CRM_Core_Extensions_Extension class for more
 * information on single extension's operations.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */
class CRM_Core_Extensions {

  /**
   * An URL for public extensions repository
   */
  CONST DEFAULT_EXTENSIONS_REPOSITORY = 'http://civicrm.org/extdir/ver={ver}|cms={uf}';

  /**
   * Extension info file name
   */
  CONST EXT_INFO_FILENAME = 'info.xml';

  /**
   * Extension info file name
   */
  CONST EXT_TEMPLATES_DIRNAME = 'templates';

  /**
   * Allows quickly verifying if extensions are enabled
   *
   * @access private
   * @var boolean
   */
  public $enabled = FALSE;

  /**
   * Full path to extensions directory
   *
   * @access private
   * @var null|string
   */
  private $_extDir = NULL;

  /**
   * List of active (installed) extensions ordered by id
   *
   * @access private
   * @var null|array
   */
  private $_extById = NULL;

  /**
   * List of active (installed) extensions ordered by id
   *
   * @access private
   * @var null|array
   */
  private $_extByKey = NULL;


  private $_remotesDiscovered = NULL;
  
  /**
   * The URL of the remote extensions repository
   *
   * @var string
   */
  private $_repoUrl;

  /**
   * Constructor - we're not initializing information here
   * since we don't want any database hits upon object
   * initialization.
   *
   * @access public
   *
   * @return void
   */
  public function __construct() {
    $config = CRM_Core_Config::singleton();
    if (isset($config->extensionsDir)) {
      $this->_extDir = $config->extensionsDir;
    }

    if (!empty($this->_extDir)) {
      $this->enabled = TRUE;
      $tmp           = $this->_extDir . DIRECTORY_SEPARATOR . 'tmp';
      $cache         = $this->_extDir . DIRECTORY_SEPARATOR . 'cache';
      if (is_writable($this->_extDir)) {
        if (!file_exists($tmp)) {
          CRM_Utils_File::createDir($tmp, FALSE);
        }
        if (!file_exists($cache)) {
          CRM_Utils_File::createDir($cache, FALSE);
        }
      }
      else {
        if (CRM_Core_Permission::check('administer CiviCRM') && $this->isDownloadEnabled()) {
          $civicrmDestination = urlencode(CRM_Utils_System::url('civicrm/admin/extensions', 'reset=1'));
          $url = CRM_Utils_System::url('civicrm/admin/setting/path', "reset=1&civicrmDestination=${civicrmDestination}");
          CRM_Core_Session::setStatus(ts('Your extensions directory: %1 is not web server writable. Please go to the <a href="%2">path setting page</a> and correct it.<br/>',
            array(
              1 => $this->_extDir,
              2 => $url,
            )
          ));
        }
        $this->_extDir = NULL;
      }

      if (!class_exists('ZipArchive') && CRM_Core_Permission::check('administer CiviCRM') && $this->isDownloadEnabled()) {
        // everyone else is dumping messages wily-nily, why can't I?
        CRM_Core_Session::setStatus(ts('You will not be able to install extensions at this time because your installation of PHP does not support ZIP archives. Please ask your system administrator to install the standard PHP-ZIP extension.'));
      }

      if (empty($config->extensionsURL) && CRM_Core_Permission::check('administer CiviCRM')) {
        $civicrmDestination = urlencode(CRM_Utils_System::url('civicrm/admin/extensions', 'reset=1'));
        $url = CRM_Utils_System::url('civicrm/admin/setting/url', "reset=1&civicrmDestination=${civicrmDestination}");
        CRM_Core_Session::setStatus(ts('Your Extensions Directory (%1) does not have a matching Extensions Resource URL. Please go to the <a href="%2">URL setting page</a> and correct it.<br/>',
            array(
              1 => $this->_extDir,
              2 => $url,
            )
          ));
      }
    }
  }
  
  public function getRepositoryUrl() {
    if (empty($this->_repoUrl) && $this->_repoUrl !== FALSE) {
      $config = CRM_Core_Config::singleton();
      $url = CRM_Core_BAO_Setting::getItem('Extension Preferences', 'ext_repo_url', NULL, self::DEFAULT_EXTENSIONS_REPOSITORY);

      // boolean false means don't try to check extensions
      // http://issues.civicrm.org/jira/browse/CRM-10575
      if($url === false) {
        $this->_repoUrl = false;
      } 
      else { 
        $vars = array(
          '{ver}' => CRM_Utils_System::version(),
          '{uf}' => $config->userFramework,
          '{php}' => phpversion(),
        );
        $this->_repoUrl = strtr($url, $vars);
      }
    }
    return $this->_repoUrl;
  }

  /**
   * Get a list of all installed modules, including enabled and disabled ones
   *
   * @return array CRM_Core_Module
   */
  public function getModules() {
    $result = array();
    $dao = new CRM_Core_DAO_Extension();
    $dao->type = 'module';
    $dao->find();
    while ($dao->fetch()) {
      $result[] = new CRM_Core_Module($dao->full_name, $dao->is_active);
    }
    return $result;
  }

  /**
   * Populates variables containing information about extension.
   * This method is not supposed to call on object initialisation.
   *
   * @access public
   *
   * @param boolean $fullInfo provide full info (read XML files) if true, otherwise only DB stored data
   *
   * @return void
   */
  public function populate($fullInfo = FALSE) {
    if (is_null($this->_extDir) || empty($this->_extDir)) {
      return;
    }

    $installed       = $this->getInstalled($fullInfo);
    $uploaded        = $this->getNotInstalled();
    $this->_extById  = array_merge($installed, $uploaded);
    $this->_extByKey = array();
    foreach ($this->_extById as $id => $ext) {
      $this->_extByKey[$ext->key] = $ext;
    }
  }

  /**
   * Returns the list of extensions ordered by extension key.
   *
   * @access public
   *
   * @param boolean $fullInfo provide full info (read XML files) if true, otherwise only DB stored data
   *
   * @return array the list of installed extensions
   */
  public function getExtensionsByKey($fullInfo = FALSE) {
    $this->populate($fullInfo);
    return $this->_extByKey;
  }

  /**
   * Returns the list of extensions ordered by id.
   *
   * @access public
   *
   * @param boolean $fullInfo provide full info (read XML files) if true, otherwise only DB stored data
   *
   * @return array the list of installed extensions
   */
  public function getExtensionsById($fullInfo = FALSE) {
    $this->populate($fullInfo);
    return $this->_extById;
  }

  /**
   * @todo DEPRECATE
   *
   * @access public
   *
   * @param boolean $fullInfo provide full info (read XML files) if true, otherwise only DB stored data
   *
   * @return array list of extensions
   */
  public function getInstalled($fullInfo = FALSE) {
    return $this->_discoverInstalled($fullInfo);
  }

  /**
   * @todo DEPRECATE
   *
   * @access public
   *
   * @return array list of extensions
   */
  public function getAvailable() {
    return $this->_discoverAvailable();
  }

  /**
   * Returns the list of extensions which hasn't been installed.
   *
   * @access public
   *
   * @return array list of extensions
   */
  public function getNotInstalled() {
    $installed = $this->_discoverInstalled();
    $result    = $this->_discoverAvailable();
    $instKeys  = array();
    foreach ($installed as $id => $ext) {
      $instKeys[] = $ext->key;
    }
    foreach ($result as $id => $ext) {
      if (array_key_exists($ext->key, array_flip($instKeys))) {
        unset($result[$id]);
      }
    }
    return $result;
  }

  public function getExtensions($fullInfo = FALSE) {

    // Workflow for extensions:
    // * Remote (made available on public server)
    // * Local (downloaded, code available locally)
    // * Installed /+Enabled/ (downloaded, entry in db, is_active = 1)
    // * Installed /+Disabled/ (downloadded, entry in db, is_active = 0)
    // * Outdated (Local or Installed with newer version available Remotely)

    $exts = array();

    // locally available extensions first (those which are installed
    // will be overwritten later on)
    $local = $this->_discoverAvailable(TRUE);
    foreach ($local as $dc => $e) {
      if (array_key_exists($e->key, $exts)) {}
      $exts[$e->key] = $e;
    }

    // now those which are available on public directory
    $remote = $this->_discoverRemote();

    if (is_array($remote)) {
      foreach ($remote as $dc => $e) {
        $exts[$e->key] = $e;
      }
    }

    // get installed extensions at the end, they overwrite everything
    $installed = $this->_discoverInstalled(TRUE);
    foreach ($installed as $dc => $e) {
      $exts[$e->key] = $e;
    }

    // now check for upgrades - rolling over installed, since
    // those that we care to upgrade
    if (is_array($remote)) {
      
      foreach ($installed as $dc => $i) {
        if ($i->status == 'missing') {
          // don't check for upgrades if expected installed file(s) are missing
          continue;
        }
        $key = $i->key;
        foreach ($remote as $dc => $r) {
          if ($key == $r->key) {
            $upg = $exts[$key];
            if (version_compare($i->version, $r->version) < 0) {
              $upg->setUpgradable();
              $upg->setUpgradeVersion($r->version);
            }
          }
        }
      }
    }

    return $exts;
  }

  /**
   * Searches for and returnes installed extensions.
   *
   * @access private
   *
   * @param boolean $fullInfo provide full info (read XML files) if true, otherwise only DB stored data
   *
   * @return array list of extensions
   */
  private function _discoverInstalled($fullInfo = FALSE) {
    $result      = array();
    $dao         = new CRM_Core_DAO_Extension();
    $dao->find(); // TODO need bool?
    while ($dao->fetch()) {
      $ext = new CRM_Core_Extensions_Extension($dao->full_name, $dao->type, $dao->name,
        $dao->label, $dao->file, $dao->is_active
      );
      $ext->setInstalled();
      $ext->setId((integer)$dao->id);
      if ($fullInfo) {
        if ($ext->hasXMLInfo()) {
          $ext->readXMLInfo();
        } else {
          $ext->setMissing();
          CRM_Core_Session::setStatus(ts('The extension %1 (%2) is listed as installed, but expected files(s) including info.xml are missing. Has this site been moved to a different server location?', array(
            1 => $dao->label, 2 => $dao->full_name,
          )). '<br/>');
        }
      }
      $result[(integer)$dao->id] = $ext;
    }
    return $result;
  }

  public function getRemoteByKey() {
    $re = $this->_discoverRemote();
    $result = array();
    foreach ($re as $id => $ext) {
      $result[$ext->key] = $ext;
    }
    return $result;
  }

  public function _discoverRemote() {

    $config    = CRM_Core_Config::singleton();
    $tsPath    = $config->extensionsDir . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'timestamp.txt';
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

    foreach ($remotes as $id => $rext) {
      $ext = new CRM_Core_Extensions_Extension($rext['key']);
      $ext->setRemote();
      $xml = $this->grabRemoteInfoFile($rext['key'], $cached);
      if ($xml != FALSE) {
        $ext->readXMLInfo($xml);
        $this->_remotesDiscovered[] = $ext;
      }
    }

    if (file_exists(dirname($tsPath))) {
      file_put_contents($tsPath, (string) time());
    }

    return $this->_remotesDiscovered;
  }

  /**
   * Retrieve all the extension information for all the extensions
   * in extension directory. Beware, we're relying on scandir's
   * extension retrieval order here, array indices will be used as
   * ids for extensions that are not installed later on.
   *
   * @access private
   *
   * @return array list of extensions
   */
  private function _discoverAvailable() {
    $result = array();
    if ($this->_extDir) {
      $e = scandir($this->_extDir);
      foreach ($e as $dc => $name) {
        $dir = $this->_extDir . DIRECTORY_SEPARATOR . $name;
        $infoFile = $dir . DIRECTORY_SEPARATOR . self::EXT_INFO_FILENAME;
        if (is_dir($dir) && file_exists($infoFile)) {
          $ext = new CRM_Core_Extensions_Extension($name);
          $ext->setLocal();
          $ext->readXMLInfo();
          $result[] = $ext;
        }
      }
    }
    return $result;
  }

  /**
   * Given the key, provides the path to file containing
   * extension's main class.
   *
   * @access public
   *
   * @param string $key extension key
   *
   * @return string path to file containing extension's main class
   */
  public function keyToPath($key) {
    $this->populate();
    $e = $this->_extByKey;

    $file = (string) $e[$key]->file;

    return $this->_extDir . DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR . $file . '.php';
  }

  /**
   * Given the key, provides extension's class name.
   *
   * @access public
   *
   * @param string $key extension key
   *
   * @return string name of extension's main class
   */
  public function keyToClass($key) {
    return str_replace('.', '_', $key);
  }

  /**
   * Given the class, provides extension's key.
   *
   * @access public
   *
   * @param string $clazz extension class name
   *
   * @return string name of extension key
   */
  public function classToKey($clazz) {
    return str_replace('_', '.', $clazz);
  }

  /**
   * Given the class, provides extension path.
   *
   * @access public
   *
   * @param string $key extension key
   *
   * @return string name of extension key
   */
  public function classToPath($clazz) {
    $elements = explode('_', $clazz);
    $key = implode('.', $elements);
    return $this->keyToPath($key);
  }

  /**
   * Given the class, provides the template path.
   *
   * @access public
   *
   * @param string $clazz extension class name
   *
   * @return string path to extension's templates directory
   */
  public function getTemplatePath($clazz) {
    $path = $this->classToPath($clazz);
    $pathElm = explode(DIRECTORY_SEPARATOR, $path);
    array_pop($pathElm);
    return implode(DIRECTORY_SEPARATOR, $pathElm) . DIRECTORY_SEPARATOR . self::EXT_TEMPLATES_DIRNAME;
  }

  /**
   * Given te class, provides the template name.
   * @todo consider multiple templates, support for one template for now
   *
   * @access public
   *
   * @param string $clazz extension class name
   *
   * @return string extension's template name
   */
  public function getTemplateName($clazz) {
    $this->populate();
    $e    = $this->_extByKey;
    $key  = $this->classToKey($clazz);
    $file = (string) $e[$key]->file;
    return (string) $e[$key]->file . '.tpl';
  }

  /**
   * Given the string, returns true or false if it's an extension key.
   *
   * @access public
   *
   * @param string $key a string which might be an extension key
   *
   * @return boolean true if given string is an extension name
   */
  public function isExtensionKey($key) {
    // check if the string is an extension name or the class
    return (strpos($key, '.') !== FALSE) ? TRUE : FALSE;
  }

  /**
   * Given the string, returns true or false if it's an extension class name.
   *
   * @access public
   *
   * @param string $clazz a string which might be an extension class name
   *
   * @return boolean true if given string is an extension class name
   */
  public function isExtensionClass($clazz) {

    if (substr($clazz, 0, 4) != 'CRM_') {
      $extensions = CRM_Core_PseudoConstant::getExtensions($clazz);
      if (array_key_exists($this->classToKey($clazz), $extensions)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Sets extension's record active or disabled.
   *
   * @access public
   *
   * @param int $id id of the extension record
   * @param boolean $is_active active state
   *
   * @return mixed result of CRM_Core_DAO::setFieldValue
   */
  static public function setIsActive($id, $is_active) {
    $extensions = new CRM_Core_Extensions();
    $e = $extensions->getExtensionsByKey();
    foreach ($e as $key => $eo) {
      if ($eo->id == $id) {
        $ext = $eo;
      }
    }
    $is_active ? $ext->enable() : $ext->disable();
    return TRUE;
  }

  /**
   * Given the key,
   * fires off appropriate CRM_Core_Extensions_Extension object's install method.
   *
   * @todo change method signature, drop $id, work with $key only
   *
   * @access public
   *
   * @param int $id id of the extension record; deprecated
   * @param string $key extension key
   *
   * @return void
   */
  public function install($id, $key) {
    $e = $this->getExtensions();
    $ext = $e[$key];
    $ext->install();
  }

  public function enable($id, $key) {
    $e = $this->getExtensions();
    $ext = $e[$key];
    $ext->enable();
  }

  public function disable($id, $key) {
    $e = $this->getExtensions();
    $ext = $e[$key];
    $ext->disable();
  }

  /**
   * Given the key, fires off appropriate CRM_Core_Extensions_Extension object's
   * uninstall method.
   *
   * @todo change method signature, drop $id, work with $key only
   *
   * @access public
   *
   * @param int $id id of the extension record
   * @param string $key extension key
   * @param bool $removeFiles whether to remove PHP source tree for the extension
   *
   * @return void
   */
  public function uninstall($id, $key, $removeFiles = TRUE) {
    $this->populate();
    $e = $this->getExtensions();
    $ext = $e[$key];
    if ($ext->uninstall($removeFiles)) {
      return TRUE;
    }
  }

  /**
   * Given the key, fires off appropriate CRM_Core_Extensions_Extension object's
   * upgrade method.
   *
   * @todo change method signature, drop $id, work with $key only
   *
   * @access public
   *
   * @param int $id id of the extension record
   * @param string $key extension key
   *
   * @return void
   */
  public function upgrade($id, $key) {
    $this->populate();
    
    $e = $this->getExtensionsByKey(TRUE);
    $ext = $e[$key];
    if (! $ext->isUpgradeable()) {
      $ext->uninstall();

      // get fresh scope and install
      $e = $this->getExtensions();
      $ext = $e[$key];
      $ext->install();
    } else {
      // get the info.xml with newest downloadUrl
      $remotes = $this->getRemoteByKey();
      $remoteExt = $remotes[$key];
      $remoteExt->upgrade();

      // refresh
      $e = $this->getExtensions();
    }
  }


  public function grabCachedKeyList() {
    $result     = array();
    $config     = CRM_Core_Config::singleton();
    $cachedPath = $config->extensionsDir . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
    $files      = scandir($cachedPath);
    foreach ($files as $dc => $fname) {
      if (substr($fname, -4) == '.xml') {
        $result[] = array('key' => trim($fname, '.xml'));
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
  public function grabRemoteKeyList() {

    ini_set('default_socket_timeout', CRM_Utils_VersionCheck::CHECK_TIMEOUT);
    set_error_handler(array('CRM_Utils_VersionCheck', 'downloadError'));

    if (!ini_get('allow_url_fopen')) {
      ini_set('allow_url_fopen', 1);
    }

    if(FALSE === $this->getRepositoryUrl()) {
      // don't check if the user has configured civi not to check an external
      // url for extensions. See CRM-10575.
      CRM_Core_Session::setStatus(ts('Not checking remote URL for extensions since ext_repo_url is set to false.'));
      return array();
    }

    $extdir = file_get_contents($this->getRepositoryUrl());

    if ($extdir === FALSE) {
      CRM_Core_Session::setStatus(ts('The CiviCRM public extensions directory at %1 could not be contacted - please check your webserver can make external HTTP requests or contact CiviCRM team on <a href="http://forum.civicrm.org/">CiviCRM forum</a>.<br />', array(1 => $this->getRepositoryUrl())));
    }

    $lines = explode("\n", $extdir);

    foreach ($lines as $ln) {
      if (preg_match("@\<li\>(.*)\</li\>@i", $ln, $out)) {
        // success
        $extsRaw[] = $out;
        $key = strip_tags($out[1]);
        if (substr($key, -4) == '.xml') {
          $exts[] = array('key' => trim($key, '.xml'));
        }
      }
    }

    if (empty($exts)) {
      if ($extdir !== FALSE) {
        CRM_Core_Session::setStatus(ts('Could not retrieve a list of extensions from the CiviCRM public directory at %1 - please contact CiviCRM team on <a href="http://forum.civicrm.org/">CiviCRM forum</a>.<br />', array(1 => $this->getRepositoryUrl())));
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
  public function grabRemoteInfoFile($key, $cached = FALSE) {
    $config = CRM_Core_Config::singleton();

    $path     = $config->extensionsDir . DIRECTORY_SEPARATOR . 'cache';
    $filename = $path . DIRECTORY_SEPARATOR . $key . '.xml';
    $url      = $this->getRepositoryUrl() . '/' . $key . '.xml';

    if (!$cached || !file_exists($filename)) {
      file_put_contents($filename, file_get_contents($url));
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

  /**
   * Determine whether the extensions subsystem is properly configured
   */
  public function isEnabled() {
      return $this->enabled;
  }

  /**
   * Determine whether the system allows downloading new extensions
   *
   * @return bool
   */
  public function isDownloadEnabled() {
    return (FALSE !== $this->getRepositoryUrl());
  }
}

