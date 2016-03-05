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
   * @param \CRM_Core_Resources $res
   *   The resource manager.
   */
  public function __construct($res) {
    $this->res = $res;
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

      $angularModules = array();
      $angularModules['angularFileUpload'] = array(
        'ext' => 'civicrm',
        'js' => array('bower_components/angular-file-upload/angular-file-upload.min.js'),
      );
      $angularModules['crmApp'] = array(
        'ext' => 'civicrm',
        'js' => array('ang/crmApp.js'),
      );
      $angularModules['crmAttachment'] = array(
        'ext' => 'civicrm',
        'js' => array('ang/crmAttachment.js'),
        'css' => array('ang/crmAttachment.css'),
        'partials' => array('ang/crmAttachment'),
        'settings' => array(
          'token' => \CRM_Core_Page_AJAX_Attachment::createToken(),
        ),
      );
      $angularModules['crmAutosave'] = array(
        'ext' => 'civicrm',
        'js' => array('ang/crmAutosave.js'),
      );
      $angularModules['crmCxn'] = array(
        'ext' => 'civicrm',
        'js' => array('ang/crmCxn.js', 'ang/crmCxn/*.js'),
        'css' => array('ang/crmCxn.css'),
        'partials' => array('ang/crmCxn'),
      );
      //$angularModules['crmExample'] = array(
      //  'ext' => 'civicrm',
      //  'js' => array('ang/crmExample.js'),
      //  'partials' => array('ang/crmExample'),
      //);
      $angularModules['crmResource'] = array(
        'ext' => 'civicrm',
        // 'js' => array('js/angular-crmResource/byModule.js'), // One HTTP request per module.
        'js' => array('js/angular-crmResource/all.js'), // One HTTP request for all modules.
      );
      $angularModules['crmUi'] = array(
        'ext' => 'civicrm',
        'js' => array('ang/crmUi.js'),
        'partials' => array('ang/crmUi'),
      );
      $angularModules['crmUtil'] = array(
        'ext' => 'civicrm',
        'js' => array('ang/crmUtil.js'),
      );
      // https://github.com/jwstadler/angular-jquery-dialog-service
      $angularModules['dialogService'] = array(
        'ext' => 'civicrm',
        'js' => array('bower_components/angular-jquery-dialog-service/dialog-service.js'),
      );
      $angularModules['ngRoute'] = array(
        'ext' => 'civicrm',
        'js' => array('bower_components/angular-route/angular-route.min.js'),
      );
      $angularModules['ngSanitize'] = array(
        'ext' => 'civicrm',
        'js' => array('bower_components/angular-sanitize/angular-sanitize.min.js'),
      );
      $angularModules['ui.utils'] = array(
        'ext' => 'civicrm',
        'js' => array('bower_components/angular-ui-utils/ui-utils.min.js'),
      );
      $angularModules['ui.sortable'] = array(
        'ext' => 'civicrm',
        'js' => array('bower_components/angular-ui-sortable/sortable.min.js'),
      );
      $angularModules['unsavedChanges'] = array(
        'ext' => 'civicrm',
        'js' => array('bower_components/angular-unsavedChanges/dist/unsavedChanges.min.js'),
      );

      $angularModules['statuspage'] = array(
        'ext' => 'civicrm',
        'js' => array('ang/crmStatusPage.js', 'ang/crmStatusPage/*.js'),
        'css' => array('ang/crmStatusPage.css'),
        'partials' => array('ang/crmStatusPage'),
        'settings' => array(),
      );

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
    if (isset($module['partials'])) {
      foreach ($module['partials'] as $partialDir) {
        $partialDir = $this->res->getPath($module['ext']) . '/' . $partialDir;
        $files = \CRM_Utils_File::findFiles($partialDir, '*.html');
        foreach ($files as $file) {
          $strings = $this->res->getStrings()->get(
            $module['ext'],
            $file,
            'text/html'
          );
          $result = array_unique(array_merge($result, $strings));
        }
      }
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
