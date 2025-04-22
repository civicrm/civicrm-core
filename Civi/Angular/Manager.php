<?php
namespace Civi\Angular;

/**
 * Manage Angular resources.
 *
 * @package Civi\Angular
 */
class Manager {

  /**
   * @var \CRM_Core_Resources
   */
  protected $res = NULL;

  /**
   * Static cache of html partials.
   *
   * Stashing it here because it's too big to store in SqlCache
   * FIXME: So that probably means we shouldn't be storing in memory either!
   * @var array
   */
  private $partials = [];

  /**
   * @var \CRM_Utils_Cache_Interface
   */
  protected $cache;

  /**
   * @var array
   *   Array(string $name => ChangeSet $change).
   */
  protected $changeSets = NULL;

  /**
   * @param \CRM_Core_Resources $res
   *   The resource manager.
   * @param $cache
   */
  public function __construct($res, ?\CRM_Utils_Cache_Interface $cache = NULL) {
    $this->res = $res;
    $this->cache = $cache ?: new \CRM_Utils_Cache_ArrayCache([]);
  }

  /**
   * Clear out any runtime-cached metadata.
   *
   * This is useful if, eg, you have recently added or destroyed Angular modules.
   *
   * @return static
   */
  public function clear() {
    $this->cache->clear();
    $this->partials = [];
    $this->changeSets = NULL;
    // Force-refresh assetBuilder files
    \Civi::container()->get('asset_builder')->clear(FALSE);
    return $this;
  }

  /**
   * Get a list of AngularJS modules which should be autoloaded.
   *
   * @return array
   *   Each item has some combination of these keys:
   *   - ext: string
   *     The Civi extension which defines the Angular module.
   *   - js: array(string $relativeFilePath)
   *     List of JS files (relative to the extension).
   *   - css: array(string $relativeFilePath)
   *     List of CSS files (relative to the extension).
   *   - partials: array(string $relativeFilePath)
   *     A list of partial-HTML folders (relative to the extension).
   *     This will be mapped to "~/moduleName" by crmResource.
   *   - settings: array(string $key => mixed $value)
   *     List of settings to preload.
   */
  public function getModules() {
    $angularModules = $this->cache->get('angularModules') ?? [];
    // Cache not set, fetch fresh list of modules and store in cache
    if (!$angularModules) {
      // Load all modules from CiviCRM core
      $files = (array) glob(\Civi::paths()->getPath('[civicrm.root]/ang/*.ang.php'));
      foreach ($files as $file) {
        $name = basename($file, '.ang.php');
        $module = include $file;
        $module['ext'] = 'civicrm';
        $angularModules[$name] = $module;
      }

      // Load all modules from extensions
      \CRM_Utils_Hook::angularModules($angularModules);

      foreach ($angularModules as $module => $info) {
        // This property must be an array. If null, set to the historical default of ['civicrm/a']
        // (historical default preserved for backward-compat reasons, but a better default would be the more common value of []).
        $angularModules[$module]['basePages'] ??= ['civicrm/a'];
        if (!empty($info['settings'])) {
          \CRM_Core_Error::deprecatedWarning(sprintf('The Angular file "%s" from extension "%s" must be updated to use "settingsFactory" instead of "settings". See https://github.com/civicrm/civicrm-core/pull/19052', $info['module'], $info['ext']));
        }
        // Validate settingsFactory callables
        if (isset($info['settingsFactory'])) {
          // To keep the cache small, we want `settingsFactory` to contain the string names of class & function, not an object
          if (!is_array($info['settingsFactory']) && !is_string($info['settingsFactory'])) {
            throw new \CRM_Core_Exception($module . ' settingsFactory must be a callable array or string');
          }
          // To keep the cache small, convert full object to just the class name
          if (is_array($info['settingsFactory']) && is_object($info['settingsFactory'][0])) {
            $angularModules[$module]['settingsFactory'][0] = get_class($info['settingsFactory'][0]);
          }
        }
      }
      $angularModules = $this->resolvePatterns($angularModules);
      $this->cache->set('angularModules', $angularModules);
    }

    return $angularModules;
  }

  /**
   * Get the descriptor for an Angular module.
   *
   * @param string $moduleName
   * @return array
   *   Details about the module:
   *   - ext: string, the name of the Civi extension which defines the module
   *   - js: array(string $relativeFilePath).
   *   - css: array(string $relativeFilePath).
   *   - partials: array(string $relativeFilePath).
   * @throws \Exception
   */
  public function getModule($moduleName) {
    $module = $this->cache->get("module $moduleName") ?? $this->getModules()[$moduleName] ?? NULL;
    if (!isset($module)) {
      throw new \Exception("Unrecognized Angular module");
    }
    return $module;
  }

  /**
   * Resolve a full list of Angular dependencies.
   *
   * @param array $names
   *   List of Angular modules.
   *   Ex: array('crmMailing').
   * @return array
   *   List of Angular modules, include all dependencies.
   *   Ex: array('crmMailing', 'crmUi', 'crmUtil', 'ngRoute').
   * @throws \CRM_Core_Exception
   */
  public function resolveDependencies($names) {
    $allModules = $this->getModules();
    $visited = [];
    $result = $names;
    while (($missingModules = array_diff($result, array_keys($visited))) && !empty($missingModules)) {
      foreach ($missingModules as $module) {
        $visited[$module] = 1;
        if (!isset($allModules[$module])) {
          throw new \CRM_Core_Exception("Unrecognized Angular module {$module}. Please ensure that all Angular modules are declared.");
        }
        elseif (isset($allModules[$module]['requires'])) {
          $result = array_unique(array_merge($result, $allModules[$module]['requires']));
        }
      }
    }
    sort($result);
    return $result;
  }

  /**
   * Get a list of Angular modules that should be loaded on the given
   * base-page.
   *
   * @param string $basePage
   *   The name of the base-page for which we want a list of moudles.
   * @return array
   *   List of Angular modules.
   *   Ex: array('crmMailing', 'crmUi', 'crmUtil', 'ngRoute').
   */
  public function resolveDefaultModules($basePage) {
    $modules = $this->getModules();
    $result = [];
    foreach ($modules as $moduleName => $module) {
      if (in_array($basePage, $module['basePages']) || in_array('*', $module['basePages'])) {
        $result[] = $moduleName;
      }
    }
    return $result;
  }

  /**
   * Convert any globs in an Angular module to file names.
   *
   * @param array $modules
   *   List of Angular modules.
   * @return array
   *   Updated list of Angular modules
   */
  protected function resolvePatterns($modules) {
    $newModules = [];

    foreach ($modules as $moduleKey => $module) {
      foreach (['js', 'css', 'partials'] as $fileset) {
        if (!isset($module[$fileset])) {
          continue;
        }
        $module[$fileset] = $this->res->glob($module['ext'], $module[$fileset]);
      }
      $newModules[$moduleKey] = $module;
    }

    return $newModules;
  }

  /**
   * Get the partial HTML documents for a module (unfiltered).
   *
   * @param string $name
   *   Angular module name.
   * @return array
   *   Array(string $extFilePath => string $html)
   * @throws \Exception
   *   Invalid partials configuration.
   */
  public function getRawPartials($name) {
    $module = $this->getModule($name);
    $result = !empty($module['partialsCallback'])
      ? \Civi\Core\Resolver::singleton()->call($module['partialsCallback'], [$name, $module])
      : [];
    if (isset($module['partials'])) {
      foreach ($module['partials'] as $partialDir) {
        $partialDir = $this->res->getPath($module['ext']) . '/' . $partialDir;
        $files = \CRM_Utils_File::findFiles($partialDir, '*.html', TRUE);
        foreach ($files as $file) {
          $filename = '~/' . $name . '/' . $file;
          $result[$filename] = file_get_contents($partialDir . '/' . $file);
        }
      }
      return $result;
    }
    return $result;
  }

  /**
   * Get the partial HTML documents for a module.
   *
   * @param string $name
   *   Angular module name.
   * @return array
   *   Array(string $extFilePath => string $html)
   * @throws \Exception
   *   Invalid partials configuration.
   */
  public function getPartials($name) {
    if (!isset($this->partials[$name])) {
      $this->partials[$name] = ChangeSet::applyResourceFilters($this->getChangeSets(), 'partials', $this->getRawPartials($name));
    }
    return $this->partials[$name];
  }

  /**
   * Get list of translated strings for a module.
   *
   * @param string $name
   *   Angular module name.
   * @return array
   *   Translated strings: array(string $orig => string $translated).
   */
  public function getTranslatedStrings($name) {
    $module = $this->getModule($name);
    $result = [];
    $strings = $this->getStrings($name);
    foreach ($strings as $string) {
      // TODO: should we pass translation domain based on $module[ext] or $module[tsDomain]?
      // It doesn't look like client side really supports the domain right now...
      $translated = _ts($string, [
        'domain' => [$module['ext'], NULL],
      ]);
      if ($translated != $string) {
        $result[$string] = $translated;
      }
    }
    return $result;
  }

  /**
   * Get list of translatable strings for a module.
   *
   * @param string $name
   *   Angular module name.
   * @return array
   *   Translatable strings.
   */
  public function getStrings($name) {
    $module = $this->getModule($name);
    $result = [];
    if (isset($module['js'])) {
      foreach ($module['js'] as $file) {
        $strings = $this->res->getStrings()->get(
          $module['ext'],
          $this->res->getPath($module['ext'], $file),
          'text/javascript'
        );
        $result = array_unique(array_merge($result, $strings));
      }
    }
    $partials = $this->getPartials($name);
    foreach ($partials as $partial) {
      $result = array_unique(array_merge($result, \CRM_Utils_JS::parseStrings($partial)));
    }
    return $result;
  }

  /**
   * Get resources for one or more modules, applying any changesets.
   *
   * NOTE: The output of this function is a little quirky; depending on the type of resource requested,
   * the results will either be a non-associative array (for path and url-type resources)
   * or an array indexed by moduleName (for pass-thru resources like settingsFactory, requires, permissions, bundles).
   *
   * Note: ChangeSets will be applied
   * @see \CRM_Utils_Hook::alterAngular()
   *
   * @param string|array $moduleNames
   *   List of module names.
   * @param string $resType
   *   Type of resource ('js', 'css', 'settings').
   * @param string $refType
   *   Type of reference to the resource ('cacheUrl', 'rawUrl', 'path', 'settings').
   * @return array
   *   Indexed or non-associative array, depending on resource requested (see note)
   * @throws \CRM_Core_Exception
   */
  public function getResources($moduleNames, $resType, $refType) {
    $result = [];
    // Properties that do not require interpolation - they are added to the output keyed by moduleName
    $passThru = ['settings', 'settingsFactory', 'requires', 'permissions', 'bundles'];

    foreach ((array) $moduleNames as $moduleName) {
      $module = $this->getModule($moduleName);
      if (isset($module[$resType]) && in_array($resType, $passThru, TRUE)) {
        $result[$moduleName] = $module[$resType];
      }
      elseif (isset($module[$resType])) {
        foreach ($module[$resType] as $file) {
          $refTypeSuffix = '';
          if (is_string($file) && preg_match(';^(assetBuilder|ext)://;', $file)) {
            $refTypeSuffix = '-' . parse_url($file, PHP_URL_SCHEME);
          }

          switch ($refType . $refTypeSuffix) {
            case 'path':
              $result[] = $this->res->getPath($module['ext'], $file);
              break;

            case 'rawUrl':
              $result[] = $this->res->getUrl($module['ext'], $file);
              break;

            case 'cacheUrl':
              $result[] = $this->res->getUrl($module['ext'], $file, TRUE);
              break;

            case 'relUrl':
              $result[] = ['ext' => $module['ext'], 'file' => $file];
              break;

            case 'path-assetBuilder':
              $assetName = parse_url($file, PHP_URL_HOST) . parse_url($file, PHP_URL_PATH);
              $assetParams = [];
              parse_str('' . parse_url($file, PHP_URL_QUERY), $assetParams);
              $result[] = \Civi::service('asset_builder')->getPath($assetName, $assetParams);
              break;

            case 'rawUrl-assetBuilder':
            case 'cacheUrl-assetBuilder':
              $assetName = parse_url($file, PHP_URL_HOST) . parse_url($file, PHP_URL_PATH);
              $assetParams = [];
              parse_str('' . parse_url($file, PHP_URL_QUERY), $assetParams);
              $result[] = \Civi::service('asset_builder')->getUrl($assetName, $assetParams);
              break;

            case 'path-ext':
              $result[] = $this->res->getPath(parse_url($file, PHP_URL_HOST), ltrim(parse_url($file, PHP_URL_PATH), '/'));
              break;

            case 'rawUrl-ext':
              $result[] = $this->res->getUrl(parse_url($file, PHP_URL_HOST), ltrim(parse_url($file, PHP_URL_PATH), '/'));
              break;

            case 'cacheUrl-ext':
              $result[] = $this->res->getUrl(parse_url($file, PHP_URL_HOST), ltrim(parse_url($file, PHP_URL_PATH), '/'), TRUE);
              break;

            default:
              throw new \CRM_Core_Exception("Unrecognized resource format");
          }
        }
      }
    }

    return ChangeSet::applyResourceFilters($this->getChangeSets(), $resType, $result);
  }

  /**
   * @return array
   *   Array(string $name => ChangeSet $changeSet).
   */
  public function getChangeSets() {
    if ($this->changeSets === NULL) {
      $this->changeSets = [];
      \CRM_Utils_Hook::alterAngular($this);
    }
    return $this->changeSets;
  }

  /**
   * @param ChangeSet $changeSet
   * @return \Civi\Angular\Manager
   */
  public function add($changeSet) {
    $this->changeSets[$changeSet->getName()] = $changeSet;
    return $this;
  }

}
