<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
   * @var \CRM_Extension_Info[]
   * (key => CRM_Extension_Info)
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
   * @var array
   *   Array(string $extKey => CRM_Extension_Upgrader_Interface $upgrader)
   */
  protected $upgraders = [];

  /**
   * @param CRM_Extension_Container_Interface|null $container
   * @param CRM_Utils_Cache_Interface|null $cache
   * @param null $cacheKey
   * @param null $civicrmPath
   * @param null $civicrmUrl
   */
  public function __construct(CRM_Extension_Container_Interface $container, ?CRM_Utils_Cache_Interface $cache = NULL, $cacheKey = NULL, $civicrmPath = NULL, $civicrmUrl = NULL) {
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
   * @param string $clazz
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
   *
   * @return CRM_Extension_Info
   */
  public function keyToInfo($key, $fresh = FALSE) {
    if ($fresh || !array_key_exists($key, $this->infos)) {
      try {
        $this->infos[$key] = CRM_Extension_Info::loadFromFile($this->container->getPath($key) . DIRECTORY_SEPARATOR . CRM_Extension_Info::FILENAME);
      }
      catch (CRM_Extension_Exception $e) {
        // file has more detailed info, but we'll fallback to DB if it's missing -- DB has enough info to uninstall
        $dbInfo = CRM_Extension_System::singleton()->getManager()->createInfoFromDB($key);
        if (!$dbInfo) {
          throw $e;
        }
        $this->infos[$key] = $dbInfo;
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
   *
   * @throws \CRM_Extension_Exception_MissingException
   */
  public function keyToUrl($key) {
    if ($key === 'civicrm') {
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
   *   array(array('prefix' => $, 'fullName' => $, 'filePath' => $))
   */
  public function getActiveModuleFiles($fresh = FALSE) {
    if (!defined('CIVICRM_DSN')) {
      // hmm, ok
      return [];
    }

    // The list of module files is cached in two tiers. The tiers are slightly
    // different:
    //
    // 1. The persistent tier (cache) stores
    // names WITHOUT absolute paths.
    // 2. The ephemeral/thread-local tier (statics) stores names
    // WITH absolute paths.
    // Return static value instead of re-running query
    if (isset(Civi::$statics[__CLASS__]['moduleExtensions']) && !$fresh) {
      return Civi::$statics[__CLASS__]['moduleExtensions'];
    }

    $moduleExtensions = NULL;

    // Checked if it's stored in the persistent cache.
    if ($this->cache && !$fresh) {
      $moduleExtensions = $this->cache->get($this->cacheKey . '_moduleFiles');
    }

    // If cache is empty we build it from database.
    if (!is_array($moduleExtensions)) {
      $compat = CRM_Extension_System::getCompatibilityInfo();

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
        if (!empty($compat[$dao->full_name]['force-uninstall'])) {
          continue;
        }
        $moduleExtensions[] = [
          'prefix' => $dao->file,
          'fullName' => $dao->full_name,
          'filePath' => NULL,
        ];
      }

      if ($this->cache) {
        $this->cache->set($this->cacheKey . '_moduleFiles', $moduleExtensions);
      }
    }

    // Since we're not caching the full path we add it now.
    array_walk($moduleExtensions, function(&$value, $key) {
      try {
        if (!$value['filePath']) {
          $value['filePath'] = $this->keyToPath($value['fullName']);
        }
      }
      catch (CRM_Extension_Exception $e) {
        // Putting a stub here provides more consistency
        // in how getActiveModuleFiles when racing between
        // dirty file-removals and cache-clears.
        CRM_Core_Session::setStatus($e->getMessage(), '', 'error');
        $value['filePath'] = NULL;
      }
    });

    Civi::$statics[__CLASS__]['moduleExtensions'] = $moduleExtensions;

    return $moduleExtensions;
  }

  /**
   * Get a list of base URLs for all active modules.
   *
   * @return array
   *   (string $extKey => string $baseUrl)
   *
   * @throws \CRM_Extension_Exception_MissingException
   */
  public function getActiveModuleUrls() {
    // TODO optimization/caching
    $urls = [];
    $urls['civicrm'] = $this->keyToUrl('civicrm');
    /** @var CRM_Core_Module $module */
    foreach ($this->getModules() as $module) {
      if ($module->is_active) {
        try {
          $urls[$module->name] = $this->keyToUrl($module->name);
        }
        catch (CRM_Extension_Exception_MissingException $e) {
          CRM_Core_Session::setStatus(ts('An enabled extension is missing from the extensions directory') . ':' . $module->name);
        }
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

    if (str_ends_with($pattern, '*')) {
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
   * Get a list of extensions which match a given tag.
   *
   * @param string $tag
   *   Ex: 'foo'
   * @return array
   *   Array(string $key).
   *   Ex: array("org.foo.bar").
   */
  public function getKeysByTag($tag) {
    $allTags = $this->getAllTags();
    return $allTags[$tag] ?? [];
  }

  /**
   * Get a list of extension tags.
   *
   * @return array
   *   Ex: ['form-building' => ['org.civicrm.afform-gui', 'org.civicrm.afform-html']]
   */
  public function getAllTags() {
    $tags = Civi::cache('short')->get('extension_tags', NULL);
    if ($tags !== NULL) {
      return $tags;
    }

    $tags = [];
    $allInfos = $this->getAllInfos();
    foreach ($allInfos as $key => $info) {
      foreach ($info->tags as $tag) {
        $tags[$tag][] = $key;
      }
    }
    return $tags;
  }

  /**
   * @return CRM_Extension_Info[]
   *   Ex: $result['org.civicrm.foobar'] = new CRM_Extension_Info(...).
   * @throws \CRM_Extension_Exception
   * @throws \Exception
   */
  public function getAllInfos() {
    foreach ($this->container->getKeys() as $key) {
      try {
        $this->keyToInfo($key);
      }
      catch (CRM_Extension_Exception_ParseException $e) {
        CRM_Core_Session::setStatus(ts('Parse error in extension %1: %2', [
          1 => $key,
          2 => $e->getMessage(),
        ]), '', 'error');
        CRM_Core_Error::debug_log_message("Parse error in extension " . $key . ": " . $e->getMessage());
        continue;
      }
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
   * @return CRM_Core_Module[]
   */
  public function getModules() {
    $result = [];
    $dao = new CRM_Core_DAO_Extension();
    $dao->type = 'module';
    $dao->find();
    while ($dao->fetch()) {
      $result[] = new CRM_Core_Module($dao->full_name, $dao->is_active, $dao->label);
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
   * Given the class, provides the template name.
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
    CRM_Extension_System::singleton()->getMixinLoader()->run(TRUE);
  }

  /**
   * This returns a formatted string containing an extension upgrade link for the UI.
   * @todo We should improve this to return more appropriate text. eg. when an extension is not installed
   *   it should not say "version xx is installed".
   *
   * @param CRM_Extension_Info $remoteExtensionInfo
   * @param array $localExtensionInfo
   *
   * @return string
   */
  public function getUpgradeLink($remoteExtensionInfo, $localExtensionInfo) {
    if (!empty($remoteExtensionInfo) && version_compare($localExtensionInfo['version'] ?? '', $remoteExtensionInfo->version, '<')) {
      return ts('Version %1 is installed. <a %2>Upgrade to version %3</a>.', [
        1 => $localExtensionInfo['version'],
        2 => 'href="' . CRM_Utils_System::url('civicrm/admin/extensions', "action=update&id={$localExtensionInfo['key']}&key={$localExtensionInfo['key']}") . '"',
        3 => $remoteExtensionInfo->version,
      ]);
    }
  }

  /**
   * @param string $key
   *   Long name of the extension.
   *   Ex: 'org.example.myext'
   *
   * @return \CRM_Extension_Upgrader_Interface
   */
  public function getUpgrader(string $key) {
    if (!array_key_exists($key, $this->upgraders)) {
      $this->upgraders[$key] = NULL;

      try {
        $info = $this->keyToInfo($key);
      }
      catch (CRM_Extension_Exception_ParseException $e) {
        CRM_Core_Session::setStatus(ts('Parse error in extension %1: %2', [
          1 => $key,
          2 => $e->getMessage(),
        ]), '', 'error');
        CRM_Core_Error::debug_log_message("Parse error in extension " . $key . ": " . $e->getMessage());
        return NULL;
      }

      if (!empty($info->upgrader)) {
        $class = $info->upgrader;
        $u = new $class();
        $u->init(['key' => $key]);
        $this->upgraders[$key] = $u;
      }
    }
    return $this->upgraders[$key];
  }

}
