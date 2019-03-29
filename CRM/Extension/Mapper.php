<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * This class proivdes various helper functions for locating extensions
 * data.  It's designed for compatibility with pre-existing functions from
 * CRM_Core_Extensions.
 *
 * Most of these helper functions originate with the first major iteration
 * of extensions -- a time when every extension had one eponymous PHP class,
 * when there was no PHP class-loader, and when there was special-case logic
 * sprinkled around to handle loading of "extension classes".
 *
 * With module-extensions (Civi 4.2+), there are no eponymous classes --
 * instead, module-extensions follow the same class-naming and class-loading
 * practices as core (and don't require special-case logic for class
 * loading).  Consequently, the helpers in here aren't much used with
 * module-extensions.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Extension_Mapper {

  /**
   * An URL for public extensions repository.
   */

  /**
   * Extension info file name.
   */
  const EXT_TEMPLATES_DIRNAME = 'templates';

  /**
   * @var CRM_Extension_Container_Interface
   */
  protected $container;

  /**
   * @var array (key => CRM_Extension_Info)
   */
  protected $infos = [];

  /**
   * @var array
   */
  protected $moduleExtensions = NULL;

  /**
   * @var CRM_Utils_Cache_Interface
   */
  protected $cache;

  protected $cacheKey;

  protected $civicrmPath;

  protected $civicrmUrl;

  /**
   * @param CRM_Extension_Container_Interface $container
   * @param CRM_Utils_Cache_Interface $cache
   * @param null $cacheKey
   * @param null $civicrmPath
   * @param null $civicrmUrl
   */
  public function __construct(CRM_Extension_Container_Interface $container, CRM_Utils_Cache_Interface $cache = NULL, $cacheKey = NULL, $civicrmPath = NULL, $civicrmUrl = NULL) {
    $this->container = $container;
    $this->cache = $cache;
    $this->cacheKey = $cacheKey;
    if ($civicrmUrl) {
      $this->civicrmUrl = rtrim($civicrmUrl, '/');
    }
    else {
      $config = CRM_Core_Config::singleton();
      $this->civicrmUrl = rtrim($config->resourceBase, '/');
    }
    if ($civicrmPath) {
      $this->civicrmPath = rtrim($civicrmPath, '/');
    }
    else {
      global $civicrm_root;
      $this->civicrmPath = rtrim($civicrm_root, '/');
    }
  }

  /**
   * Given the class, provides extension's key.
   *
   *
   * @param string $clazz
   *   Extension class name.
   *
   * @return string
   *   name of extension key
   */
  public function classToKey($clazz) {
    return str_replace('_', '.', $clazz);
  }

  /**
   * Given the class, provides extension path.
   *
   *
   * @param $clazz
   *
   * @return string
   *   full path the extension .php file
   */
  public function classToPath($clazz) {
    $elements = explode('_', $clazz);
    $key = implode('.', $elements);
    return $this->keyToPath($key);
  }

  /**
   * Given the string, returns true or false if it's an extension key.
   *
   *
   * @param string $key
   *   A string which might be an extension key.
   *
   * @return bool
   *   true if given string is an extension name
   */
  public function isExtensionKey($key) {
    // check if the string is an extension name or the class
    return (strpos($key, '.') !== FALSE) ? TRUE : FALSE;
  }

  /**
   * Given the string, returns true or false if it's an extension class name.
   *
   *
   * @param string $clazz
   *   A string which might be an extension class name.
   *
   * @return bool
   *   true if given string is an extension class name
   */
  public function isExtensionClass($clazz) {

    if (substr($clazz, 0, 4) != 'CRM_') {
      return (bool) preg_match('/^[a-z0-9]+(_[a-z0-9]+)+$/', $clazz);
    }
    return FALSE;
  }

  /**
   * @param string $key
   *   Extension fully-qualified-name.
   * @param bool $fresh
   *
   * @throws CRM_Extension_Exception
   * @throws Exception
   * @return CRM_Extension_Info
   */
  public function keyToInfo($key, $fresh = FALSE) {
    if ($fresh || !array_key_exists($key, $this->infos)) {
      try {
        $this->infos[$key] = CRM_Extension_Info::loadFromFile($this->container->getPath($key) . DIRECTORY_SEPARATOR . CRM_Extension_Info::FILENAME);
      }
      catch (CRM_Extension_Exception $e) {
        // file has more detailed info, but we'll fallback to DB if it's missing -- DB has enough info to uninstall
        $this->infos[$key] = CRM_Extension_System::singleton()->getManager()->createInfoFromDB($key);
        if (!$this->infos[$key]) {
          throw $e;
        }
      }
    }
    return $this->infos[$key];
  }

  /**
   * Given the key, provides extension's class name.
   *
   *
   * @param string $key
   *   Extension key.
   *
   * @return string
   *   name of extension's main class
   */
  public function keyToClass($key) {
    return str_replace('.', '_', $key);
  }

  /**
   * Given the key, provides the path to file containing
   * extension's main class.
   *
   *
   * @param string $key
   *   Extension key.
   *
   * @return string
   *   path to file containing extension's main class
   */
  public function keyToPath($key) {
    $info = $this->keyToInfo($key);
    return $this->container->getPath($key) . DIRECTORY_SEPARATOR . $info->file . '.php';
  }

  /**
   * Given the key, provides the path to file containing
   * extension's main class.
   *
   * @param string $key
   *   Extension key.
   * @return string
   *   local path of the extension source tree
   */
  public function keyToBasePath($key) {
    if ($key == 'civicrm') {
      return $this->civicrmPath;
    }
    return $this->container->getPath($key);
  }

  /**
   * Given the key, provides the path to file containing
   * extension's main class.
   *
   *
   * @param string $key
   *   Extension key.
   *
   * @return string
   *   url for resources in this extension
   */
  public function keyToUrl($key) {
    if ($key == 'civicrm') {
      // CRM-12130 Workaround: If the domain's config_backend is NULL at the start of the request,
      // then the Mapper is wrongly constructed with an empty value for $this->civicrmUrl.
      if (empty($this->civicrmUrl)) {
        $config = CRM_Core_Config::singleton();
        return rtrim($config->resourceBase, '/');
      }
      return $this->civicrmUrl;
    }

    return $this->container->getResUrl($key);
  }

  /**
   * Fetch the list of active extensions of type 'module'
   *
   * @param bool $fresh
   *   whether to forcibly reload extensions list from canonical store.
   * @return array
   *   array(array('prefix' => $, 'file' => $))
   */
  public function getActiveModuleFiles($fresh = FALSE) {
    $config = CRM_Core_Config::singleton();
    if ($config->isUpgradeMode() || !defined('CIVICRM_DSN')) {
      return []; // hmm, ok
    }

    $moduleExtensions = NULL;
    if ($this->cache && !$fresh) {
      $moduleExtensions = $this->cache->get($this->cacheKey . '_moduleFiles');
    }

    if (!is_array($moduleExtensions)) {
      // Check canonical module list
      $moduleExtensions = [];
      $sql = '
        SELECT full_name, file
        FROM civicrm_extension
        WHERE is_active = 1
        AND type = "module"
      ';
      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        try {
          $moduleExtensions[] = [
            'prefix' => $dao->file,
            'filePath' => $this->keyToPath($dao->full_name),
          ];
        }
        catch (CRM_Extension_Exception $e) {
          // Putting a stub here provides more consistency
          // in how getActiveModuleFiles when racing between
          // dirty file-removals and cache-clears.
          CRM_Core_Session::setStatus($e->getMessage(), '', 'error');
          $moduleExtensions[] = [
            'prefix' => $dao->file,
            'filePath' => NULL,
          ];
        }
      }

      if ($this->cache) {
        $this->cache->set($this->cacheKey . '_moduleFiles', $moduleExtensions);
      }
    }
    return $moduleExtensions;
  }

  /**
   * Get a list of base URLs for all active modules.
   *
   * @return array
   *   (string $extKey => string $baseUrl)
   */
  public function getActiveModuleUrls() {
    // TODO optimization/caching
    $urls = [];
    $urls['civicrm'] = $this->keyToUrl('civicrm');
    foreach ($this->getModules() as $module) {
      /** @var $module CRM_Core_Module */
      if ($module->is_active) {
        $urls[$module->name] = $this->keyToUrl($module->name);
      }
    }
    return $urls;
  }

  /**
   * Get a list of extension keys, filtered by the corresponding file path.
   *
   * @param string $pattern
   *   A file path. To search subdirectories, append "*".
   *   Ex: "/var/www/extensions/*"
   *   Ex: "/var/www/extensions/org.foo.bar"
   * @return array
   *   Array(string $key).
   *   Ex: array("org.foo.bar").
   */
  public function getKeysByPath($pattern) {
    $keys = [];

    if (CRM_Utils_String::endsWith($pattern, '*')) {
      $prefix = rtrim($pattern, '*');
      foreach ($this->container->getKeys() as $key) {
        $path = CRM_Utils_File::addTrailingSlash($this->container->getPath($key));
        if (realpath($prefix) == realpath($path) || CRM_Utils_File::isChildPath($prefix, $path)) {
          $keys[] = $key;
        }
      }
    }
    else {
      foreach ($this->container->getKeys() as $key) {
        $path = CRM_Utils_File::addTrailingSlash($this->container->getPath($key));
        if (realpath($pattern) == realpath($path)) {
          $keys[] = $key;
        }
      }
    }

    return $keys;
  }

  /**
   * @return array
   *   Ex: $result['org.civicrm.foobar'] = new CRM_Extension_Info(...).
   * @throws \CRM_Extension_Exception
   * @throws \Exception
   */
  public function getAllInfos() {
    foreach ($this->container->getKeys() as $key) {
      $this->keyToInfo($key);
    }
    return $this->infos;
  }

  /**
   * @param string $name
   *
   * @return bool
   */
  public function isActiveModule($name) {
    $activeModules = $this->getActiveModuleFiles();
    foreach ($activeModules as $activeModule) {
      if ($activeModule['prefix'] == $name) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get a list of all installed modules, including enabled and disabled ones
   *
   * @return array
   *   CRM_Core_Module
   */
  public function getModules() {
    $result = [];
    $dao = new CRM_Core_DAO_Extension();
    $dao->type = 'module';
    $dao->find();
    while ($dao->fetch()) {
      $result[] = new CRM_Core_Module($dao->full_name, $dao->is_active);
    }
    return $result;
  }

  /**
   * Given the class, provides the template path.
   *
   *
   * @param string $clazz
   *   Extension class name.
   *
   * @return string
   *   path to extension's templates directory
   */
  public function getTemplatePath($clazz) {
    $path = $this->container->getPath($this->classToKey($clazz));
    return $path . DIRECTORY_SEPARATOR . self::EXT_TEMPLATES_DIRNAME;
    /*
    $path = $this->classToPath($clazz);
    $pathElm = explode(DIRECTORY_SEPARATOR, $path);
    array_pop($pathElm);
    return implode(DIRECTORY_SEPARATOR, $pathElm) . DIRECTORY_SEPARATOR . self::EXT_TEMPLATES_DIRNAME;
     */
  }

  /**
   * Given te class, provides the template name.
   * @todo consider multiple templates, support for one template for now
   *
   *
   * @param string $clazz
   *   Extension class name.
   *
   * @return string
   *   extension's template name
   */
  public function getTemplateName($clazz) {
    $info = $this->keyToInfo($this->classToKey($clazz));
    return (string) $info->file . '.tpl';
  }

  public function refresh() {
    $this->infos = [];
    $this->moduleExtensions = NULL;
    if ($this->cache) {
      $this->cache->delete($this->cacheKey . '_moduleFiles');
    }
    // FIXME: How can code so code wrong be so right?
    CRM_Extension_System::singleton()->getClassLoader()->refresh();
  }

}
