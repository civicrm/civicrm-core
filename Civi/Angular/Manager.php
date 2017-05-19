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
      $angularModules['crmUi'] = include "$civicrm_root/ang/crmUi.ang.php";
      $angularModules['crmUtil'] = include "$civicrm_root/ang/crmUtil.ang.php";
      $angularModules['dialogService'] = include "$civicrm_root/ang/dialogService.ang.php";
      $angularModules['ngRoute'] = include "$civicrm_root/ang/ngRoute.ang.php";
      $angularModules['ngSanitize'] = include "$civicrm_root/ang/ngSanitize.ang.php";
      $angularModules['ui.utils'] = include "$civicrm_root/ang/ui.utils.ang.php";
      $angularModules['ui.sortable'] = include "$civicrm_root/ang/ui.sortable.ang.php";
      $angularModules['unsavedChanges'] = include "$civicrm_root/ang/unsavedChanges.ang.php";
      $angularModules['statuspage'] = include "$civicrm_root/ang/crmStatusPage.ang.php";

      foreach (\CRM_Core_Component::getEnabledComponents() as $component) {
        $angularModules = array_merge($angularModules, $component->getAngularModules());
      }
      \CRM_Utils_Hook::angularModules($angularModules);
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
    $cacheKey = "angular-partials::$name";
    $cacheValue = $this->cache->get($cacheKey);
    if ($cacheValue !== NULL) {
      return $cacheValue;
    }
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
    }

    $this->cache->set($cacheKey, $result);
    return $result;
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
          switch ($refType) {
            case 'path':
              $result[] = $this->res->getPath($module['ext'], $file);
              break;

            case 'rawUrl':
              $result[] = $this->res->getUrl($module['ext'], $file);
              break;

            case 'cacheUrl':
              $result[] = $this->res->getUrl($module['ext'], $file, TRUE);
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
    return $result;
  }

}
