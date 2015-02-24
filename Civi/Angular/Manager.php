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
   */
  public function getModules() {
    if ($this->modules === NULL) {

      $angularModules = array();
      $angularModules['angularFileUpload'] = array(
        'ext' => 'civicrm',
        'js' => array('bower_components/angular-file-upload/angular-file-upload.min.js'),
      );
      $angularModules['crmApp'] = array(
        'ext' => 'civicrm',
        'js' => array('js/angular-crmApp.js'),
      );
      $angularModules['crmAttachment'] = array(
        'ext' => 'civicrm',
        'js' => array('js/angular-crmAttachment.js'),
        'css' => array('css/angular-crmAttachment.css'),
        'partials' => array('partials/crmAttachment'),
      );
      $angularModules['crmAutosave'] = array(
        'ext' => 'civicrm',
        'js' => array('js/angular-crmAutosave.js'),
      );
      //$angularModules['crmExample'] = array(
      //  'ext' => 'civicrm',
      //  'js' => array('js/angular-crmExample.js'),
      //  'partials' => array('partials/crmExample'),
      //);
      $angularModules['crmResource'] = array(
        'ext' => 'civicrm',
        // 'js' => array('js/angular-crmResource/byModule.js'), // One HTTP request per module.
        'js' => array('js/angular-crmResource/all.js'), // One HTTP request for all modules.
      );
      $angularModules['crmUi'] = array(
        'ext' => 'civicrm',
        'js' => array('js/angular-crm-ui.js', 'packages/ckeditor/ckeditor.js'),
        'partials' => array('partials/crmUi'),
      );
      $angularModules['crmUtil'] = array(
        'ext' => 'civicrm',
        'js' => array('js/angular-crm-util.js'),
      );
      // https://github.com/jwstadler/angular-jquery-dialog-service
      $angularModules['dialogService'] = array(
        'ext' => 'civicrm',
        'js' => array('bower_components/angular-jquery-dialog-service/dialog-service.js'),
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
   * @param string $name
   *   Module name.
   * @return array
   *   List of URLs.
   * @throws \Exception
   */
  public function getScriptUrls($name) {
    $module = $this->getModule($name);
    $result = array();
    if (isset($module['js'])) {
      foreach ($module['js'] as $file) {
        $result[] = $this->res->getUrl($module['ext'], $file, TRUE);
      }
    }
    return $result;
  }

  /**
   * @param string $name
   *   Module name.
   * @return array
   *   List of URLs.
   * @throws \Exception
   */
  public function getStyleUrls($name) {
    $module = $this->getModule($name);
    $result = array();
    if (isset($module['css'])) {
      foreach ($module['css'] as $file) {
        $result[] = $this->res->getUrl($module['ext'], $file, TRUE);
      }
    }
    return $result;
  }

}
