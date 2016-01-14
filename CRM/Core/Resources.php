<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.7                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * This class facilitates the loading of resources
 * such as JavaScript files and CSS files.
 *
 * Any URLs generated for resources may include a 'cache-code'. By resetting the
 * cache-code, one may force clients to re-download resource files (regardless of
 * any HTTP caching rules).
 *
 * TODO: This is currently a thin wrapper over CRM_Core_Region. We
 * should incorporte services for aggregation, minimization, etc.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Core_Resources {
  const DEFAULT_WEIGHT = 0;
  const DEFAULT_REGION = 'page-footer';

  /**
   * We don't have a container or dependency-injection, so use singleton instead
   *
   * @var object
   */
  private static $_singleton = NULL;

  /**
   * @var CRM_Extension_Mapper
   */
  private $extMapper = NULL;

  /**
   * @var CRM_Core_Resources_Strings
   */
  private $strings = NULL;

  /**
   * @var array free-form data tree
   */
  protected $settings = array();
  protected $addedSettings = FALSE;

  /**
   * @var array of callables
   */
  protected $settingsFactories = array();

  /**
   * @var array ($regionName => bool)
   */
  protected $addedCoreResources = array();

  /**
   * @var array ($regionName => bool)
   */
  protected $addedCoreStyles = array();

  /**
   * @var string a value to append to JS/CSS URLs to coerce cache resets
   */
  protected $cacheCode = NULL;

  /**
   * @var string the name of a setting which persistently stores the cacheCode
   */
  protected $cacheCodeKey = NULL;

  /**
   * @var bool
   */
  public $ajaxPopupsEnabled;

  /**
   * @var \Civi\Core\Paths
   */
  protected $paths;

  /**
   * Get or set the single instance of CRM_Core_Resources.
   *
   * @param CRM_Core_Resources $instance
   *   New copy of the manager.
   * @return CRM_Core_Resources
   */
  static public function singleton(CRM_Core_Resources $instance = NULL) {
    if ($instance !== NULL) {
      self::$_singleton = $instance;
    }
    if (self::$_singleton === NULL) {
      $sys = CRM_Extension_System::singleton();
      $cache = Civi::cache('js_strings');
      self::$_singleton = new CRM_Core_Resources(
        $sys->getMapper(),
        $cache,
        CRM_Core_Config::isUpgradeMode() ? NULL : 'resCacheCode'
      );
    }
    return self::$_singleton;
  }

  /**
   * Construct a resource manager.
   *
   * @param CRM_Extension_Mapper $extMapper
   *   Map extension names to their base path or URLs.
   * @param CRM_Utils_Cache_Interface $cache
   *   JS-localization cache.
   * @param string|null $cacheCodeKey Random code to append to resource URLs; changing the code forces clients to reload resources
   */
  public function __construct($extMapper, $cache, $cacheCodeKey = NULL) {
    $this->extMapper = $extMapper;
    $this->strings = new CRM_Core_Resources_Strings($cache);
    $this->cacheCodeKey = $cacheCodeKey;
    if ($cacheCodeKey !== NULL) {
      $this->cacheCode = Civi::settings()->get($cacheCodeKey);
    }
    if (!$this->cacheCode) {
      $this->resetCacheCode();
    }
    $this->ajaxPopupsEnabled = (bool) Civi::settings()->get('ajaxPopupsEnabled');
    $this->paths = Civi::paths();
  }

  /**
   * Export permission data to the client to enable smarter GUIs.
   *
   * Note: Application security stems from the server's enforcement
   * of the security logic (e.g. in the API permissions). There's no way
   * the client can use this info to make the app more secure; however,
   * it can produce a better-tuned (non-broken) UI.
   *
   * @param array $permNames
   *   List of permission names to check/export.
   * @return CRM_Core_Resources
   */
  public function addPermissions($permNames) {
    $permNames = (array) $permNames;
    $perms = array();
    foreach ($permNames as $permName) {
      $perms[$permName] = CRM_Core_Permission::check($permName);
    }
    return $this->addSetting(array(
      'permissions' => $perms,
    ));
  }

  /**
   * Add a JavaScript file to the current page using <SCRIPT SRC>.
   *
   * @param string $ext
   *   extension name; use 'civicrm' for core.
   * @param string $file
   *   file path -- relative to the extension base dir.
   * @param int $weight
   *   relative weight within a given region.
   * @param string $region
   *   location within the file; 'html-header', 'page-header', 'page-footer'.
   * @param bool|string $translate
   *   Whether to load translated strings for this file. Use one of:
   *   - FALSE: Do not load translated strings.
   *   - TRUE: Load translated strings. Use the $ext's default domain.
   *   - string: Load translated strings. Use a specific domain.
   *
   * @return CRM_Core_Resources
   */
  public function addScriptFile($ext, $file, $weight = self::DEFAULT_WEIGHT, $region = self::DEFAULT_REGION, $translate = TRUE) {
    if ($translate) {
      $domain = ($translate === TRUE) ? $ext : $translate;
      $this->addString($this->strings->get($domain, $this->getPath($ext, $file), 'text/javascript'), $domain);
    }
    $this->resolveFileName($file, $ext);
    return $this->addScriptUrl($this->getUrl($ext, $file, TRUE), $weight, $region);
  }

  /**
   * Add a JavaScript file to the current page using <SCRIPT SRC>.
   *
   * @param string $url
   * @param int $weight
   *   relative weight within a given region.
   * @param string $region
   *   location within the file; 'html-header', 'page-header', 'page-footer'.
   * @return CRM_Core_Resources
   */
  public function addScriptUrl($url, $weight = self::DEFAULT_WEIGHT, $region = self::DEFAULT_REGION) {
    CRM_Core_Region::instance($region)->add(array(
        'name' => $url,
        'type' => 'scriptUrl',
        'scriptUrl' => $url,
        'weight' => $weight,
        'region' => $region,
      ));
    return $this;
  }

  /**
   * Add a JavaScript file to the current page using <SCRIPT SRC>.
   *
   * @param string $code
   *   JavaScript source code.
   * @param int $weight
   *   relative weight within a given region.
   * @param string $region
   *   location within the file; 'html-header', 'page-header', 'page-footer'.
   * @return CRM_Core_Resources
   */
  public function addScript($code, $weight = self::DEFAULT_WEIGHT, $region = self::DEFAULT_REGION) {
    CRM_Core_Region::instance($region)->add(array(
        // 'name' => automatic
        'type' => 'script',
        'script' => $code,
        'weight' => $weight,
        'region' => $region,
      ));
    return $this;
  }

  /**
   * Add JavaScript variables to CRM.vars
   *
   * Example:
   * From the server:
   * CRM_Core_Resources::singleton()->addVars('myNamespace', array('foo' => 'bar'));
   * Access var from javascript:
   * CRM.vars.myNamespace.foo // "bar"
   *
   * @see http://wiki.civicrm.org/confluence/display/CRMDOC/Javascript+Reference
   *
   * @param string $nameSpace
   *   Usually the name of your extension.
   * @param array $vars
   * @return CRM_Core_Resources
   */
  public function addVars($nameSpace, $vars) {
    $existing = CRM_Utils_Array::value($nameSpace, CRM_Utils_Array::value('vars', $this->settings), array());
    $vars = $this->mergeSettings($existing, $vars);
    $this->addSetting(array('vars' => array($nameSpace => $vars)));
    return $this;
  }

  /**
   * Add JavaScript variables to the root of the CRM object.
   * This function is usually reserved for low-level system use.
   * Extensions and components should generally use addVars instead.
   *
   * @param array $settings
   * @return CRM_Core_Resources
   */
  public function addSetting($settings) {
    $this->settings = $this->mergeSettings($this->settings, $settings);
    if (!$this->addedSettings) {
      $region = self::isAjaxMode() ? 'ajax-snippet' : 'html-header';
      $resources = $this;
      CRM_Core_Region::instance($region)->add(array(
        'callback' => function (&$snippet, &$html) use ($resources) {
          $html .= "\n" . $resources->renderSetting();
        },
        'weight' => -100000,
      ));
      $this->addedSettings = TRUE;
    }
    return $this;
  }

  /**
   * Add JavaScript variables to the global CRM object via a callback function.
   *
   * @param callable $callable
   * @return CRM_Core_Resources
   */
  public function addSettingsFactory($callable) {
    // Make sure our callback has been registered
    $this->addSetting(array());
    $this->settingsFactories[] = $callable;
    return $this;
  }

  /**
   * Helper fn for addSettingsFactory.
   */
  public function getSettings() {
    $result = $this->settings;
    foreach ($this->settingsFactories as $callable) {
      $result = $this->mergeSettings($result, $callable());
    }
    return $result;
  }

  /**
   * @param array $settings
   * @param array $additions
   * @return array
   *   combination of $settings and $additions
   */
  protected function mergeSettings($settings, $additions) {
    foreach ($additions as $k => $v) {
      if (isset($settings[$k]) && is_array($settings[$k]) && is_array($v)) {
        $v += $settings[$k];
      }
      $settings[$k] = $v;
    }
    return $settings;
  }

  /**
   * Helper fn for addSetting.
   * Render JavaScript variables for the global CRM object.
   *
   * @return string
   */
  public function renderSetting() {
    // On a standard page request we construct the CRM object from scratch
    if (!self::isAjaxMode()) {
      $js = 'var CRM = ' . json_encode($this->getSettings()) . ';';
    }
    // For an ajax request we append to it
    else {
      $js = 'CRM.$.extend(true, CRM, ' . json_encode($this->getSettings()) . ');';
    }
    return sprintf("<script type=\"text/javascript\">\n%s\n</script>\n", $js);
  }

  /**
   * Add translated string to the js CRM object.
   * It can then be retrived from the client-side ts() function
   * Variable substitutions can happen from client-side
   *
   * Note: this function rarely needs to be called directly and is mostly for internal use.
   * See CRM_Core_Resources::addScriptFile which automatically adds translated strings from js files
   *
   * Simple example:
   * // From php:
   * CRM_Core_Resources::singleton()->addString('Hello');
   * // The string is now available to javascript code i.e.
   * ts('Hello');
   *
   * Example with client-side substitutions:
   * // From php:
   * CRM_Core_Resources::singleton()->addString('Your %1 has been %2');
   * // ts() in javascript works the same as in php, for example:
   * ts('Your %1 has been %2', {1: objectName, 2: actionTaken});
   *
   * NOTE: This function does not work with server-side substitutions
   * (as this might result in collisions and unwanted variable injections)
   * Instead, use code like:
   * CRM_Core_Resources::singleton()->addSetting(array('myNamespace' => array('myString' => ts('Your %1 has been %2', array(subs)))));
   * And from javascript access it at CRM.myNamespace.myString
   *
   * @param string|array $text
   * @param string|NULL $domain
   * @return CRM_Core_Resources
   */
  public function addString($text, $domain = 'civicrm') {
    foreach ((array) $text as $str) {
      $translated = ts($str, array(
        'domain' => ($domain == 'civicrm') ? NULL : array($domain, NULL),
        'raw' => TRUE,
      ));

      // We only need to push this string to client if the translation
      // is actually different from the original
      if ($translated != $str) {
        $bucket = $domain == 'civicrm' ? 'strings' : 'strings::' . $domain;
        $this->addSetting(array(
          $bucket => array($str => $translated),
        ));
      }
    }
    return $this;
  }

  /**
   * Add a CSS file to the current page using <LINK HREF>.
   *
   * @param string $ext
   *   extension name; use 'civicrm' for core.
   * @param string $file
   *   file path -- relative to the extension base dir.
   * @param int $weight
   *   relative weight within a given region.
   * @param string $region
   *   location within the file; 'html-header', 'page-header', 'page-footer'.
   * @return CRM_Core_Resources
   */
  public function addStyleFile($ext, $file, $weight = self::DEFAULT_WEIGHT, $region = self::DEFAULT_REGION) {
    $this->resolveFileName($file, $ext);
    return $this->addStyleUrl($this->getUrl($ext, $file, TRUE), $weight, $region);
  }

  /**
   * Add a CSS file to the current page using <LINK HREF>.
   *
   * @param string $url
   * @param int $weight
   *   relative weight within a given region.
   * @param string $region
   *   location within the file; 'html-header', 'page-header', 'page-footer'.
   * @return CRM_Core_Resources
   */
  public function addStyleUrl($url, $weight = self::DEFAULT_WEIGHT, $region = self::DEFAULT_REGION) {
    CRM_Core_Region::instance($region)->add(array(
        'name' => $url,
        'type' => 'styleUrl',
        'styleUrl' => $url,
        'weight' => $weight,
        'region' => $region,
      ));
    return $this;
  }

  /**
   * Add a CSS content to the current page using <STYLE>.
   *
   * @param string $code
   *   CSS source code.
   * @param int $weight
   *   relative weight within a given region.
   * @param string $region
   *   location within the file; 'html-header', 'page-header', 'page-footer'.
   * @return CRM_Core_Resources
   */
  public function addStyle($code, $weight = self::DEFAULT_WEIGHT, $region = self::DEFAULT_REGION) {
    CRM_Core_Region::instance($region)->add(array(
        // 'name' => automatic
        'type' => 'style',
        'style' => $code,
        'weight' => $weight,
        'region' => $region,
      ));
    return $this;
  }

  /**
   * Determine file path of a resource provided by an extension.
   *
   * @param string $ext
   *   extension name; use 'civicrm' for core.
   * @param string|NULL $file
   *   file path -- relative to the extension base dir.
   *
   * @return bool|string
   *   full file path or FALSE if not found
   */
  public function getPath($ext, $file = NULL) {
    // TODO consider caching results
    $base = $this->paths->hasVariable($ext)
      ? rtrim($this->paths->getVariable($ext, 'path'), '/')
      : $this->extMapper->keyToBasePath($ext);
    if ($file === NULL) {
      return $base;
    }
    $path = $base . '/' . $file;
    if (is_file($path)) {
      return $path;
    }
    return FALSE;
  }

  /**
   * Determine public URL of a resource provided by an extension.
   *
   * @param string $ext
   *   extension name; use 'civicrm' for core.
   * @param string $file
   *   file path -- relative to the extension base dir.
   * @param bool $addCacheCode
   *
   * @return string, URL
   */
  public function getUrl($ext, $file = NULL, $addCacheCode = FALSE) {
    if ($file === NULL) {
      $file = '';
    }
    if ($addCacheCode) {
      $file .= '?r=' . $this->getCacheCode();
    }
    // TODO consider caching results
    $base = $this->paths->hasVariable($ext)
      ? $this->paths->getVariable($ext, 'url')
      : ($this->extMapper->keyToUrl($ext) . '/');
    return $base . $file;
  }

  /**
   * Evaluate a glob pattern in the context of a particular extension.
   *
   * @param string $ext
   *   Extension name; use 'civicrm' for core.
   * @param string|array $patterns
   *   Glob pattern; e.g. "*.html".
   * @param null|int $flags
   *   See glob().
   * @return array
   *   List of matching files, relative to the extension base dir.
   * @see glob()
   */
  public function glob($ext, $patterns, $flags = NULL) {
    $path = $this->getPath($ext);
    $patterns = (array) $patterns;
    $files = array();
    foreach ($patterns as $pattern) {
      if (CRM_Utils_File::isAbsolute($pattern)) {
        // Absolute path.
        $files = array_merge($files, (array) glob($pattern, $flags));
      }
      else {
        // Relative path.
        $files = array_merge($files, (array) glob("$path/$pattern", $flags));
      }
    }
    sort($files); // Deterministic order.
    $files = array_unique($files);
    return array_map(function ($file) use ($path) {
      return CRM_Utils_File::relativize($file, "$path/");
    }, $files);
  }

  /**
   * @return string
   */
  public function getCacheCode() {
    return $this->cacheCode;
  }

  /**
   * @param $value
   * @return CRM_Core_Resources
   */
  public function setCacheCode($value) {
    $this->cacheCode = $value;
    if ($this->cacheCodeKey) {
      Civi::settings()->set($this->cacheCodeKey, $value);
    }
    return $this;
  }

  /**
   * @return CRM_Core_Resources
   */
  public function resetCacheCode() {
    $this->setCacheCode(CRM_Utils_String::createRandom(5, CRM_Utils_String::ALPHANUMERIC));
    // Also flush cms resource cache if needed
    CRM_Core_Config::singleton()->userSystem->clearResourceCache();
    return $this;
  }

  /**
   * This adds CiviCRM's standard css and js to the specified region of the document.
   * It will only run once.
   *
   * TODO: Separate the functional code (like addStyle/addScript) from the policy code
   * (like addCoreResources/addCoreStyles).
   *
   * @param string $region
   * @return CRM_Core_Resources
   */
  public function addCoreResources($region = 'html-header') {
    if (!isset($this->addedCoreResources[$region]) && !self::isAjaxMode()) {
      $this->addedCoreResources[$region] = TRUE;
      $config = CRM_Core_Config::singleton();

      // Add resources from coreResourceList
      $jsWeight = -9999;
      foreach ($this->coreResourceList($region) as $item) {
        if (is_array($item)) {
          $this->addSetting($item);
        }
        elseif (substr($item, -2) == 'js') {
          // Don't bother  looking for ts() calls in packages, there aren't any
          $translate = (substr($item, 0, 3) == 'js/');
          $this->addScriptFile('civicrm', $item, $jsWeight++, $region, $translate);
        }
        else {
          $this->addStyleFile('civicrm', $item, -100, $region);
        }
      }

      // Dynamic localization script
      $this->addScriptUrl(CRM_Utils_System::url('civicrm/ajax/l10n-js/' . $config->lcMessages, array('r' => $this->getCacheCode())), $jsWeight++, $region);

      // Add global settings
      $settings = array(
        'config' => array(
          'isFrontend' => $config->userFrameworkFrontend,
        ),
      );
      // Disable profile creation if user lacks permission
      if (!CRM_Core_Permission::check('edit all contacts') && !CRM_Core_Permission::check('add contacts')) {
        $settings['config']['entityRef']['contactCreate'] = FALSE;
      }
      $this->addSetting($settings);

      // Give control of jQuery and _ back to the CMS - this loads last
      $this->addScriptFile('civicrm', 'js/noconflict.js', 9999, $region, FALSE);

      $this->addCoreStyles($region);
    }
    return $this;
  }

  /**
   * This will add CiviCRM's standard CSS
   *
   * TODO: Separate the functional code (like addStyle/addScript) from the policy code
   * (like addCoreResources/addCoreStyles).
   *
   * @param string $region
   * @return CRM_Core_Resources
   */
  public function addCoreStyles($region = 'html-header') {
    if (!isset($this->addedCoreStyles[$region])) {
      $this->addedCoreStyles[$region] = TRUE;

      // Load custom or core css
      $config = CRM_Core_Config::singleton();
      if (!empty($config->customCSSURL)) {
        $this->addStyleUrl($config->customCSSURL, 99, $region);
      }
      if (!Civi::settings()->get('disable_core_css')) {
        $this->addStyleFile('civicrm', 'css/civicrm.css', -99, $region);
      }
      // crm-i.css added ahead of other styles so it can be overridden by FA.
      $this->addStyleFile('civicrm', 'css/crm-i.css', -101, $region);
    }
    return $this;
  }

  /**
   * Flushes cached translated strings.
   * @return CRM_Core_Resources
   */
  public function flushStrings() {
    $this->strings->flush();
    return $this;
  }

  /**
   * @return CRM_Core_Resources_Strings
   */
  public function getStrings() {
    return $this->strings;
  }

  /**
   * Create dynamic script for localizing js widgets.
   */
  public static function outputLocalizationJS() {
    CRM_Core_Page_AJAX::setJsHeaders();
    $config = CRM_Core_Config::singleton();
    $vars = array(
      'moneyFormat' => json_encode(CRM_Utils_Money::format(1234.56)),
      'contactSearch' => json_encode($config->includeEmailInName ? ts('Start typing a name or email...') : ts('Start typing a name...')),
      'otherSearch' => json_encode(ts('Enter search term...')),
      'entityRef' => array(
        'contactCreate' => CRM_Core_BAO_UFGroup::getCreateLinks(),
        'filters' => self::getEntityRefFilters(),
      ),
      'ajaxPopupsEnabled' => self::singleton()->ajaxPopupsEnabled,
    );
    print CRM_Core_Smarty::singleton()->fetchWith('CRM/common/l10n.js.tpl', $vars);
    CRM_Utils_System::civiExit();
  }

  /**
   * List of core resources we add to every CiviCRM page.
   *
   * Note: non-compressed versions of .min files will be used in debug mode
   *
   * @param string $region
   * @return array
   */
  public function coreResourceList($region) {
    $config = CRM_Core_Config::singleton();

    // Scripts needed by everyone, everywhere
    // FIXME: This is too long; list needs finer-grained segmentation
    $items = array(
      "bower_components/jquery/dist/jquery.min.js",
      "bower_components/jquery-ui/jquery-ui.min.js",
      "bower_components/jquery-ui/themes/smoothness/jquery-ui.min.css",
      "bower_components/lodash-compat/lodash.min.js",
      "packages/jquery/plugins/jquery.mousewheel.min.js",
      "bower_components/select2/select2.min.js",
      "bower_components/select2/select2.min.css",
      "bower_components/font-awesome/css/font-awesome.min.css",
      "packages/jquery/plugins/jquery.tableHeader.js",
      "packages/jquery/plugins/jquery.form.min.js",
      "packages/jquery/plugins/jquery.timeentry.min.js",
      "packages/jquery/plugins/jquery.blockUI.min.js",
      "bower_components/datatables/media/js/jquery.dataTables.min.js",
      "bower_components/datatables/media/css/jquery.dataTables.min.css",
      "bower_components/jquery-validation/dist/jquery.validate.min.js",
      "packages/jquery/plugins/jquery.ui.datepicker.validation.min.js",
      "js/Common.js",
      "js/crm.ajax.js",
      "js/wysiwyg/crm.wysiwyg.js",
    );
    // add wysiwyg editor
    $editor = Civi::settings()->get('editor_id');
    if ($editor == "CKEditor") {
      $items[] = "js/wysiwyg/crm.ckeditor.js";
      $ckConfig = CRM_Admin_Page_CKEditorConfig::getConfigUrl();
      if ($ckConfig) {
        $items[] = array('config' => array('CKEditorCustomConfig' => $ckConfig));
      }
    }

    // These scripts are only needed by back-office users
    if (CRM_Core_Permission::check('access CiviCRM')) {
      $items[] = "packages/jquery/plugins/jquery.menu.min.js";
      $items[] = "css/civicrmNavigation.css";
      $items[] = "packages/jquery/plugins/jquery.jeditable.min.js";
      $items[] = "packages/jquery/plugins/jquery.notify.min.js";
      $items[] = "js/jquery/jquery.crmeditable.js";
    }

    // JS for multilingual installations
    if (!empty($config->languageLimit) && count($config->languageLimit) > 1 && CRM_Core_Permission::check('translate CiviCRM')) {
      $items[] = "js/crm.multilingual.js";
    }

    // Enable administrators to edit option lists in a dialog
    if (CRM_Core_Permission::check('administer CiviCRM') && $this->ajaxPopupsEnabled) {
      $items[] = "js/crm.optionEdit.js";
    }

    // Add localized jQuery UI files
    if ($config->lcMessages && $config->lcMessages != 'en_US') {
      // Search for i18n file in order of specificity (try fr-CA, then fr)
      list($lang) = explode('_', $config->lcMessages);
      $path = "bower_components/jquery-ui/ui/i18n";
      foreach (array(str_replace('_', '-', $config->lcMessages), $lang) as $language) {
        $localizationFile = "$path/datepicker-{$language}.js";
        if ($this->getPath('civicrm', $localizationFile)) {
          $items[] = $localizationFile;
          break;
        }
      }
    }

    // Allow hooks to modify this list
    CRM_Utils_Hook::coreResourceList($items, $region);

    return $items;
  }

  /**
   * @return bool
   *   is this page request an ajax snippet?
   */
  public static function isAjaxMode() {
    return in_array(CRM_Utils_Array::value('snippet', $_REQUEST), array(
        CRM_Core_Smarty::PRINT_SNIPPET,
        CRM_Core_Smarty::PRINT_NOFORM,
        CRM_Core_Smarty::PRINT_JSON,
      ));
  }

  /**
   * Provide a list of available entityRef filters.
   * FIXME: This function doesn't really belong in this class
   * @TODO: Provide a sane way to extend this list for other entities - a hook or??
   * @return array
   */
  public static function getEntityRefFilters() {
    $filters = array();
    $config = CRM_Core_Config::singleton();

    if (in_array('CiviEvent', $config->enableComponents)) {
      $filters['event'] = array(
        array('key' => 'event_type_id', 'value' => ts('Event Type')),
        array(
          'key' => 'start_date',
          'value' => ts('Start Date'),
          'options' => array(
            array('key' => '{">":"now"}', 'value' => ts('Upcoming')),
            array(
              'key' => '{"BETWEEN":["now - 3 month","now"]}',
              'value' => ts('Past 3 Months'),
            ),
            array(
              'key' => '{"BETWEEN":["now - 6 month","now"]}',
              'value' => ts('Past 6 Months'),
            ),
            array(
              'key' => '{"BETWEEN":["now - 1 year","now"]}',
              'value' => ts('Past Year'),
            ),
          ),
        ),
      );
    }

    $filters['activity'] = array(
      array('key' => 'activity_type_id', 'value' => ts('Activity Type')),
      array('key' => 'status_id', 'value' => ts('Activity Status')),
    );

    $filters['contact'] = array(
      array('key' => 'contact_type', 'value' => ts('Contact Type')),
      array('key' => 'group', 'value' => ts('Group'), 'entity' => 'group_contact'),
      array('key' => 'tag', 'value' => ts('Tag'), 'entity' => 'entity_tag'),
      array('key' => 'state_province', 'value' => ts('State/Province'), 'entity' => 'address'),
      array('key' => 'country', 'value' => ts('Country'), 'entity' => 'address'),
      array('key' => 'gender_id', 'value' => ts('Gender')),
      array('key' => 'is_deceased', 'value' => ts('Deceased')),
    );

    if (in_array('CiviCase', $config->enableComponents)) {
      $filters['case'] = array(
        array(
          'key' => 'case_id.case_type_id',
          'value' => ts('Case Type'),
          'entity' => 'Case',
        ),
        array(
          'key' => 'case_id.status_id',
          'value' => ts('Case Status'),
          'entity' => 'Case',
        ),
      );
      foreach ($filters['contact'] as $filter) {
        $filter += array('entity' => 'contact');
        $filter['key'] = 'contact_id.' . $filter['key'];
        $filters['case'][] = $filter;
      }
    }

    return $filters;
  }

  /**
   * In debug mode, look for a non-minified version of this file
   *
   * @param string $fileName
   * @param string $extName
   */
  private function resolveFileName(&$fileName, $extName) {
    if (CRM_Core_Config::singleton()->debug && strpos($fileName, '.min.') !== FALSE) {
      $nonMiniFile = str_replace('.min.', '.', $fileName);
      if ($this->getPath($extName, $nonMiniFile)) {
        $fileName = $nonMiniFile;
      }
    }
  }

}
