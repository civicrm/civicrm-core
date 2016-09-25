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
class CRM_Extension_System {
  private static $singleton;

  private $cache = NULL;
  private $fullContainer = NULL;
  private $defaultContainer = NULL;
  private $mapper = NULL;
  private $manager = NULL;
  private $browser = NULL;
  private $downloader = NULL;

  /**
   * @var CRM_Extension_ClassLoader
   * */
  private $classLoader;

  /**
   * The URL of the remote extensions repository.
   *
   * @var string|FALSE
   */
  private $_repoUrl = NULL;

  /**
   * @var array
   *   Construction parameters. These are primarily retained so
   *   that they can influence the cache name.
   */
  protected $parameters;

  /**
   * @param bool $fresh
   *   TRUE to force creation of a new system.
   *
   * @return CRM_Extension_System
   */
  public static function singleton($fresh = FALSE) {
    if (!self::$singleton || $fresh) {
      if (self::$singleton) {
        self::$singleton = new CRM_Extension_System(self::$singleton->parameters);
      }
      else {
        self::$singleton = new CRM_Extension_System();
      }
    }
    return self::$singleton;
  }

  /**
   * @param CRM_Extension_System $singleton
   *   The new, singleton extension system.
   */
  public static function setSingleton(CRM_Extension_System $singleton) {
    self::$singleton = $singleton;
  }

  /**
   * @param array $parameters
   *   List of configuration values required by the extension system.
   *   Missing values will be guessed based on $config.
   */
  public function __construct($parameters = array()) {
    $config = CRM_Core_Config::singleton();
    $parameters['extensionsDir'] = CRM_Utils_Array::value('extensionsDir', $parameters, $config->extensionsDir);
    $parameters['extensionsURL'] = CRM_Utils_Array::value('extensionsURL', $parameters, $config->extensionsURL);
    $parameters['resourceBase'] = CRM_Utils_Array::value('resourceBase', $parameters, $config->resourceBase);
    $parameters['userFrameworkBaseURL'] = CRM_Utils_Array::value('userFrameworkBaseURL', $parameters, $config->userFrameworkBaseURL);
    if (!array_key_exists('civicrm_root', $parameters)) {
      $parameters['civicrm_root'] = $GLOBALS['civicrm_root'];
    }
    if (!array_key_exists('cmsRootPath', $parameters)) {
      $parameters['cmsRootPath'] = $config->userSystem->cmsRootPath();
    }
    if (!array_key_exists('domain_id', $parameters)) {
      $parameters['domain_id'] = CRM_Core_Config::domainID();
    }
    ksort($parameters); // guaranteed ordering - useful for md5(serialize($parameters))

    $this->parameters = $parameters;
  }

  /**
   * Get a container which represents all available extensions.
   *
   * @return CRM_Extension_Container_Interface
   */
  public function getFullContainer() {
    if ($this->fullContainer === NULL) {
      $containers = array();

      if ($this->getDefaultContainer()) {
        $containers['default'] = $this->getDefaultContainer();
      }

      $containers['civiroot'] = new CRM_Extension_Container_Basic(
        $this->parameters['civicrm_root'],
        $this->parameters['resourceBase'],
        $this->getCache(),
        'civiroot'
      );

      // TODO: CRM_Extension_Container_Basic( /sites/all/modules )
      // TODO: CRM_Extension_Container_Basic( /sites/$domain/modules
      // TODO: CRM_Extension_Container_Basic( /modules )
      // TODO: CRM_Extension_Container_Basic( /vendors )

      // At time of writing, D6, D7, and WP support cmsRootPath() but J does not
      if (NULL !== $this->parameters['cmsRootPath']) {
        $vendorPath = $this->parameters['cmsRootPath'] . DIRECTORY_SEPARATOR . 'vendor';
        if (is_dir($vendorPath)) {
          $containers['cmsvendor'] = new CRM_Extension_Container_Basic(
            $vendorPath,
            $this->parameters['userFrameworkBaseURL'] . DIRECTORY_SEPARATOR . 'vendor',
            $this->getCache(),
            'cmsvendor'
          );
        }
      }

      $this->fullContainer = new CRM_Extension_Container_Collection($containers, $this->getCache(), 'full');
    }
    return $this->fullContainer;
  }

  /**
   * Get the container to which new extensions are installed.
   *
   * This container should be a particular, writeable directory.
   *
   * @return CRM_Extension_Container_Default|FALSE (false if not configured)
   */
  public function getDefaultContainer() {
    if ($this->defaultContainer === NULL) {
      if ($this->parameters['extensionsDir']) {
        $this->defaultContainer = new CRM_Extension_Container_Default($this->parameters['extensionsDir'], $this->parameters['extensionsURL'], $this->getCache(), 'default');
      }
      else {
        $this->defaultContainer = FALSE;
      }
    }
    return $this->defaultContainer;
  }

  /**
   * Get the service which provides runtime information about extensions.
   *
   * @return CRM_Extension_Mapper
   */
  public function getMapper() {
    if ($this->mapper === NULL) {
      $this->mapper = new CRM_Extension_Mapper($this->getFullContainer(), $this->getCache(), 'mapper');
    }
    return $this->mapper;
  }

  /**
   * @return \CRM_Extension_ClassLoader
   */
  public function getClassLoader() {
    if ($this->classLoader === NULL) {
      $this->classLoader = new CRM_Extension_ClassLoader($this->getMapper(), $this->getFullContainer(), $this->getManager());
    }
    return $this->classLoader;
  }

  /**
   * Get the service for enabling and disabling extensions.
   *
   * @return CRM_Extension_Manager
   */
  public function getManager() {
    if ($this->manager === NULL) {
      $typeManagers = array(
        'payment' => new CRM_Extension_Manager_Payment($this->getMapper()),
        'report' => new CRM_Extension_Manager_Report(),
        'search' => new CRM_Extension_Manager_Search(),
        'module' => new CRM_Extension_Manager_Module($this->getMapper()),
      );
      $this->manager = new CRM_Extension_Manager($this->getFullContainer(), $this->getDefaultContainer(), $this->getMapper(), $typeManagers);
    }
    return $this->manager;
  }

  /**
   * Get the service for finding remotely-available extensions
   *
   * @return CRM_Extension_Browser
   */
  public function getBrowser() {
    if ($this->browser === NULL) {
      $cacheDir = NULL;
      if ($this->getDefaultContainer()) {
        $cacheDir = $this->getDefaultContainer()->getBaseDir() . DIRECTORY_SEPARATOR . 'cache';
      }
      $this->browser = new CRM_Extension_Browser($this->getRepositoryUrl(), '', $cacheDir);
    }
    return $this->browser;
  }

  /**
   * Get the service for loading code from remotely-available extensions
   *
   * @return CRM_Extension_Downloader
   */
  public function getDownloader() {
    if ($this->downloader === NULL) {
      $basedir = ($this->getDefaultContainer() ? $this->getDefaultContainer()->getBaseDir() : NULL);
      $this->downloader = new CRM_Extension_Downloader(
        $this->getManager(),
        $basedir,
        CRM_Utils_File::tempdir() // WAS: $config->extensionsDir . DIRECTORY_SEPARATOR . 'tmp';
      );
    }
    return $this->downloader;
  }

  /**
   * @return CRM_Utils_Cache_Interface
   */
  public function getCache() {
    if ($this->cache === NULL) {
      $cacheGroup = md5(serialize(array('ext', $this->parameters)));
      // Extension system starts before container. Manage our own cache.
      $this->cache = CRM_Utils_Cache::create(array(
        'name' => $cacheGroup,
        'type' => array('*memory*', 'SqlGroup', 'ArrayCache'),
        'prefetch' => TRUE,
      ));
    }
    return $this->cache;
  }

  /**
   * Determine the URL which provides a feed of available extensions.
   *
   * @return string|FALSE
   */
  public function getRepositoryUrl() {
    if (empty($this->_repoUrl) && $this->_repoUrl !== FALSE) {
      $url = Civi::settings()->get('ext_repo_url');

      // boolean false means don't try to check extensions
      // CRM-10575
      if ($url === FALSE) {
        $this->_repoUrl = FALSE;
      }
      else {
        $this->_repoUrl = CRM_Utils_System::evalUrl($url);
      }
    }
    return $this->_repoUrl;
  }

}
