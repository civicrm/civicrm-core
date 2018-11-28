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
   * @var array|NULL
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
  protected $modules = NULL;

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
   */
  public function __construct($res, \CRM_Utils_Cache_Interface $cache = NULL) {
    $this->res = $res;
    $this->cache = $cache ? $cache : new \CRM_Utils_Cache_Arraycache(array());
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
    if ($this->modules === NULL) {
      $config = \CRM_Core_Config::singleton();
      global $civicrm_root;

      // Note: It would be nice to just glob("$civicrm_root/ang/*.ang.php"), but at time
      // of writing CiviMail and CiviCase have special conditionals.

      $angularModules = array();
      $angularModules['angularFileUpload'] = include "$civicrm_root/ang/angularFileUpload.ang.php";
      $angularModules['crmApp'] = include "$civicrm_root/ang/crmApp.ang.php";
      $angularModules['crmAttachment'] = include "$civicrm_root/ang/crmAttachment.ang.php";
      $angularModules['crmAutosave'] = include "$civicrm_root/ang/crmAutosave.ang.php";
      $angularModules['crmCxn'] = include "$civicrm_root/ang/crmCxn.ang.php";
      // $angularModules['crmExample'] = include "$civicrm_root/ang/crmExample.ang.php";
      $angularModules['crmResource'] = include "$civicrm_root/ang/crmResource.ang.php";
      $angularModules['crmRouteBinder'] = include "$civicrm_root/ang/crmRouteBinder.ang.php";
      $angularModules['crmUi'] = include "$civicrm_root/ang/crmUi.ang.php";
      $angularModules['crmUtil'] = include "$civicrm_root/ang/crmUtil.ang.php";
      $angularModules['dialogService'] = include "$civicrm_root/ang/dialogService.ang.php";
      $angularModules['ngRoute'] = include "$civicrm_root/ang/ngRoute.ang.php";
      $angularModules['ngSanitize'] = include "$civicrm_root/ang/ngSanitize.ang.php";
      $angularModules['ui.utils'] = include "$civicrm_root/ang/ui.utils.ang.php";
      $angularModules['ui.bootstrap'] = include "$civicrm_root/ang/ui.bootstrap.ang.php";
      $angularModules['ui.sortable'] = include "$civicrm_root/ang/ui.sortable.ang.php";
      $angularModules['unsavedChanges'] = include "$civicrm_root/ang/unsavedChanges.ang.php";
      $angularModules['statuspage'] = include "$civicrm_root/ang/crmStatusPage.ang.php";

      foreach (\CRM_Core_Component::getEnabledComponents() as $component) {
        $angularModules = array_merge($angularModules, $component->getAngularModules());
      }
      \CRM_Utils_Hook::angularModules($angularModules);
      foreach (array_keys($angularModules) as $module) {
        if (!isset($angularModules[$module]['basePages'])) {
          $angularModules[$module]['basePages'] = array('civicrm/a');
        }
      }
      $this->modules = $this->resolvePatterns($angularModules);
    }

    return $this->modules;
  }

  /**
   * Get the descriptor for an Angular module.
   *
   * @param string $name
   *   Module name.
   * @return array
   *   Details about the module:
   *   - ext: string, the name of the Civi extension which defines the module
   *   - js: array(string $relativeFilePath).
   *   - css: array(string $relativeFilePath).
   *   - partials: array(string $relativeFilePath).
   * @throws \Exception
   */
  public function getModule($name) {
    $modules = $this->getModules();
    if (!isset($modules[$name])) {
      throw new \Exception("Unrecognized Angular module");
    }
    return $modules[$name];
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
   */
  public function resolveDependencies($names) {
    $allModules = $this->getModules();
    $visited = array();
    $result = $names;
    while (($missingModules = array_diff($result, array_keys($visited))) && !empty($missingModules)) {
      foreach ($missingModules as $module) {
        $visited[$module] = 1;
        if (!isset($allModules[$module])) {
          \Civi::log()->warning('Unrecognized Angular module {name}. Please ensure that all Angular modules are declared.', array(
            'name' => $module,
            'civi.tag' => 'deprecated',
          ));
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
    $result = array();
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
    $newModules = array();

    foreach ($modules as $moduleKey => $module) {
      foreach (array('js', 'css', 'partials') as $fileset) {
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
    $result = array();
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
    $cacheKey = "angular-partials_$name";
    $cacheValue = $this->cache->get($cacheKey);
    if ($cacheValue === NULL) {
      $cacheValue = ChangeSet::applyResourceFilters($this->getChangeSets(), 'partials', $this->getRawPartials($name));
      $this->cache->set($cacheKey, $cacheValue);
    }
    return $cacheValue;
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
    $result = array();
    $strings = $this->getStrings($name);
    foreach ($strings as $string) {
      // TODO: should we pass translation domain based on $module[ext] or $module[tsDomain]?
      // It doesn't look like client side really supports the domain right now...
      $translated = ts($string, array(
        'domain' => array($module['ext'], NULL),
      ));
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
    $result = array();
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
   * Get resources for one or more modules.
   *
   * @param string|array $moduleNames
   *   List of module names.
   * @param string $resType
   *   Type of resource ('js', 'css', 'settings').
   * @param string $refType
   *   Type of reference to the resource ('cacheUrl', 'rawUrl', 'path', 'settings').
   * @return array
   *   List of URLs or paths.
   * @throws \CRM_Core_Exception
   */
  public function getResources($moduleNames, $resType, $refType) {
    $result = array();
    $moduleNames = (array) $moduleNames;
    foreach ($moduleNames as $moduleName) {
      $module = $this->getModule($moduleName);
      if (isset($module[$resType])) {
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

            case 'path-assetBuilder':
              $assetName = parse_url($file, PHP_URL_HOST) . parse_url($file, PHP_URL_PATH);
              $assetParams = array();
              parse_str('' . parse_url($file, PHP_URL_QUERY), $assetParams);
              $result[] = \Civi::service('asset_builder')->getPath($assetName, $assetParams);
              break;

            case 'rawUrl-assetBuilder':
            case 'cacheUrl-assetBuilder':
              $assetName = parse_url($file, PHP_URL_HOST) . parse_url($file, PHP_URL_PATH);
              $assetParams = array();
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

            case 'settings':
            case 'requires':
              if (!empty($module[$resType])) {
                $result[$moduleName] = $module[$resType];
              }
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
      $this->changeSets = array();
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
