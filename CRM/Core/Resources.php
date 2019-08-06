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
use Civi\Core\Event\GenericHookEvent;

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
 * @copyright CiviCRM LLC (c) 2004-2019
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
   * Settings in free-form data tree.
   *
   * @var array
   */
  protected $settings = [];
  protected $addedSettings = FALSE;

  /**
   * Setting factories.
   *
   * @var callable[]
   */
  protected $settingsFactories = [];

  /**
   * Added core resources.
   *
   * Format is ($regionName => bool).
   *
   * @var array
   */
  protected $addedCoreResources = [];

  /**
   * Added core styles.
   *
   * Format is ($regionName => bool).
   *
   * @var array
   */
  protected $addedCoreStyles = [];

  /**
   * A value to append to JS/CSS URLs to coerce cache resets.
   *
   * @var string
   */
  protected $cacheCode = NULL;

  /**
   * The name of a setting which persistently stores the cacheCode.
   *
   * @var string
   */
  protected $cacheCodeKey = NULL;

  /**
   * Are ajax popup screens enabled.
   *
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
   *
   * @return CRM_Core_Resources
   */
  public static function singleton(CRM_Core_Resources $instance = NULL) {
    if ($instance !== NULL) {
      self::$_singleton = $instance;
    }
    if (self::$_singleton === NULL) {
      self::$_singleton = Civi::service('resources');
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
    $perms = [];
    foreach ($permNames as $permName) {
      $perms[$permName] = CRM_Core_Permission::check($permName);
    }
    return $this->addSetting([
      'permissions' => $perms,
    ]);
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
   * @throws \Exception
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
    CRM_Core_Region::instance($region)->add([
      'name' => $url,
      'type' => 'scriptUrl',
      'scriptUrl' => $url,
      'weight' => $weight,
      'region' => $region,
    ]);
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
    CRM_Core_Region::instance($region)->add([
        // 'name' => automatic
      'type' => 'script',
      'script' => $code,
      'weight' => $weight,
      'region' => $region,
    ]);
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
    $existing = CRM_Utils_Array::value($nameSpace, CRM_Utils_Array::value('vars', $this->settings), []);
    $vars = $this->mergeSettings($existing, $vars);
    $this->addSetting(['vars' => [$nameSpace => $vars]]);
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
      CRM_Core_Region::instance($region)->add([
        'callback' => function (&$snippet, &$html) use ($resources) {
          $html .= "\n" . $resources->renderSetting();
        },
        'weight' => -100000,
      ]);
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
    $this->addSetting([]);
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
    CRM_Utils_Hook::alterResourceSettings($result);
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
   * @param string|null $domain
   * @return CRM_Core_Resources
   */
  public function addString($text, $domain = 'civicrm') {
    foreach ((array) $text as $str) {
      $translated = ts($str, [
        'domain' => ($domain == 'civicrm') ? NULL : [$domain, NULL],
        'raw' => TRUE,
      ]);

      // We only need to push this string to client if the translation
      // is actually different from the original
      if ($translated != $str) {
        $bucket = $domain == 'civicrm' ? 'strings' : 'strings::' . $domain;
        $this->addSetting([
          $bucket => [$str => $translated],
        ]);
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
    CRM_Core_Region::instance($region)->add([
      'name' => $url,
      'type' => 'styleUrl',
      'styleUrl' => $url,
      'weight' => $weight,
      'region' => $region,
    ]);
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
    CRM_Core_Region::instance($region)->add([
        // 'name' => automatic
      'type' => 'style',
      'style' => $code,
      'weight' => $weight,
      'region' => $region,
    ]);
    return $this;
  }

  /**
   * Determine file path of a resource provided by an extension.
   *
   * @param string $ext
   *   extension name; use 'civicrm' for core.
   * @param string|null $file
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
      $file = $this->addCacheCode($file);
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
    $files = [];
    foreach ($patterns as $pattern) {
      if (preg_match(';^(assetBuilder|ext)://;', $pattern)) {
        $files[] = $pattern;
      }
      if (CRM_Utils_File::isAbsolute($pattern)) {
        // Absolute path.
        $files = array_merge($files, (array) glob($pattern, $flags));
      }
      else {
        // Relative path.
        $files = array_merge($files, (array) glob("$path/$pattern", $flags));
      }
    }
    // Deterministic order.
    sort($files);
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
        elseif (strpos($item, '.css')) {
          $this->isFullyFormedUrl($item) ? $this->addStyleUrl($item, -100, $region) : $this->addStyleFile('civicrm', $item, -100, $region);
        }
        elseif ($this->isFullyFormedUrl($item)) {
          $this->addScriptUrl($item, $jsWeight++, $region);
        }
        else {
          // Don't bother  looking for ts() calls in packages, there aren't any
          $translate = (substr($item, 0, 3) == 'js/');
          $this->addScriptFile('civicrm', $item, $jsWeight++, $region, $translate);
        }
      }
      // Add global settings
      $settings = [
        'config' => [
          'isFrontend' => $config->userFrameworkFrontend,
        ],
      ];
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
        $customCSSURL = $this->addCacheCode($config->customCSSURL);
        $this->addStyleUrl($customCSSURL, 99, $region);
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
    $vars = [
      'moneyFormat' => json_encode(CRM_Utils_Money::format(1234.56)),
      'contactSearch' => json_encode($config->includeEmailInName ? ts('Start typing a name or email...') : ts('Start typing a name...')),
      'otherSearch' => json_encode(ts('Enter search term...')),
      'entityRef' => self::getEntityRefMetadata(),
      'ajaxPopupsEnabled' => self::singleton()->ajaxPopupsEnabled,
      'allowAlertAutodismissal' => (bool) Civi::settings()->get('allow_alert_autodismissal'),
      'resourceCacheCode' => self::singleton()->getCacheCode(),
      'locale' => CRM_Core_I18n::getLocale(),
      'cid' => (int) CRM_Core_Session::getLoggedInContactID(),
    ];
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
    $items = [
      "bower_components/jquery/dist/jquery.min.js",
      "bower_components/jquery-ui/jquery-ui.min.js",
      "bower_components/jquery-ui/themes/smoothness/jquery-ui.min.css",
      "bower_components/lodash-compat/lodash.min.js",
      "packages/jquery/plugins/jquery.mousewheel.min.js",
      "bower_components/select2/select2.min.js",
      "bower_components/select2/select2.min.css",
      "bower_components/font-awesome/css/font-awesome.min.css",
      "packages/jquery/plugins/jquery.form.min.js",
      "packages/jquery/plugins/jquery.timeentry.min.js",
      "packages/jquery/plugins/jquery.blockUI.min.js",
      "bower_components/datatables/media/js/jquery.dataTables.min.js",
      "bower_components/datatables/media/css/jquery.dataTables.min.css",
      "bower_components/jquery-validation/dist/jquery.validate.min.js",
      "packages/jquery/plugins/jquery.ui.datepicker.validation.min.js",
      "js/Common.js",
      "js/crm.datepicker.js",
      "js/crm.ajax.js",
      "js/wysiwyg/crm.wysiwyg.js",
    ];

    // Dynamic localization script
    $items[] = $this->addCacheCode(
      CRM_Utils_System::url('civicrm/ajax/l10n-js/' . CRM_Core_I18n::getLocale(),
        ['cid' => CRM_Core_Session::getLoggedInContactID()], FALSE, NULL, FALSE)
    );

    // add wysiwyg editor
    $editor = Civi::settings()->get('editor_id');
    if ($editor == "CKEditor") {
      CRM_Admin_Page_CKEditorConfig::setConfigDefault();
      $items[] = [
        'config' => [
          'wysisygScriptLocation' => Civi::paths()->getUrl("[civicrm.root]/js/wysiwyg/crm.ckeditor.js"),
          'CKEditorCustomConfig' => CRM_Admin_Page_CKEditorConfig::getConfigUrl(),
        ],
      ];
    }

    // These scripts are only needed by back-office users
    if (CRM_Core_Permission::check('access CiviCRM')) {
      $items[] = "packages/jquery/plugins/jquery.tableHeader.js";
      $items[] = "packages/jquery/plugins/jquery.notify.min.js";
    }

    $contactID = CRM_Core_Session::getLoggedInContactID();

    // Menubar
    $position = 'none';
    if (
      $contactID && !$config->userFrameworkFrontend
      && CRM_Core_Permission::check('access CiviCRM')
      && !@constant('CIVICRM_DISABLE_DEFAULT_MENU')
      && !CRM_Core_Config::isUpgradeMode()
    ) {
      $position = Civi::settings()->get('menubar_position') ?: 'over-cms-menu';
    }
    if ($position !== 'none') {
      $items[] = 'bower_components/smartmenus/dist/jquery.smartmenus.min.js';
      $items[] = 'bower_components/smartmenus/dist/addons/keyboard/jquery.smartmenus.keyboard.min.js';
      $items[] = 'js/crm.menubar.js';
      $items[] = Civi::service('asset_builder')->getUrl('crm-menubar.css', [
        'color' => Civi::settings()->get('menubar_color'),
        'height' => 40,
        'breakpoint' => 768,
        'opacity' => .88,
      ]);
      $items[] = [
        'menubar' => [
          'position' => $position,
          'qfKey' => CRM_Core_Key::get('CRM_Contact_Controller_Search', TRUE),
          'cacheCode' => CRM_Core_BAO_Navigation::getCacheKey($contactID),
        ],
      ];
    }

    // JS for multilingual installations
    if (!empty($config->languageLimit) && count($config->languageLimit) > 1 && CRM_Core_Permission::check('translate CiviCRM')) {
      $items[] = "js/crm.multilingual.js";
    }

    // Enable administrators to edit option lists in a dialog
    if (CRM_Core_Permission::check('administer CiviCRM') && $this->ajaxPopupsEnabled) {
      $items[] = "js/crm.optionEdit.js";
    }

    $tsLocale = CRM_Core_I18n::getLocale();
    // Add localized jQuery UI files
    if ($tsLocale && $tsLocale != 'en_US') {
      // Search for i18n file in order of specificity (try fr-CA, then fr)
      list($lang) = explode('_', $tsLocale);
      $path = "bower_components/jquery-ui/ui/i18n";
      foreach ([str_replace('_', '-', $tsLocale), $lang] as $language) {
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
    if (in_array(CRM_Utils_Array::value('snippet', $_REQUEST), [
      CRM_Core_Smarty::PRINT_SNIPPET,
      CRM_Core_Smarty::PRINT_NOFORM,
      CRM_Core_Smarty::PRINT_JSON,
    ])
    ) {
      return TRUE;
    }
    list($arg0, $arg1) = array_pad(explode('/', CRM_Utils_System::getUrlPath()), 2, '');
    return ($arg0 === 'civicrm' && in_array($arg1, ['ajax', 'angularprofiles', 'asset']));
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::buildAsset()
   */
  public static function renderMenubarStylesheet(GenericHookEvent $e) {
    if ($e->asset !== 'crm-menubar.css') {
      return;
    }
    $e->mimeType = 'text/css';
    $e->content = '';
    $config = CRM_Core_Config::singleton();
    $cms = strtolower($config->userFramework);
    $cms = $cms === 'drupal' ? 'drupal7' : $cms;
    $items = [
      'bower_components/smartmenus/dist/css/sm-core-css.css',
      'css/crm-menubar.css',
      "css/menubar-$cms.css",
    ];
    foreach ($items as $item) {
      $e->content .= file_get_contents(self::singleton()->getPath('civicrm', $item));
    }
    $color = $e->params['color'];
    if (!CRM_Utils_Rule::color($color)) {
      $color = Civi::settings()->getDefault('menubar_color');
    }
    $vars = [
      'resourceBase' => rtrim($config->resourceBase, '/'),
      'menubarHeight' => $e->params['height'] . 'px',
      'breakMin' => $e->params['breakpoint'] . 'px',
      'breakMax' => ($e->params['breakpoint'] - 1) . 'px',
      'menubarColor' => $color,
      'menuItemColor' => 'rgba(' . implode(', ', CRM_Utils_Color::getRgb($color)) . ", {$e->params['opacity']})",
      'highlightColor' => CRM_Utils_Color::getHighlight($color),
      'textColor' => CRM_Utils_Color::getContrast($color, '#333', '#ddd'),
    ];
    $vars['highlightTextColor'] = CRM_Utils_Color::getContrast($vars['highlightColor'], '#333', '#ddd');
    foreach ($vars as $var => $val) {
      $e->content = str_replace('$' . $var, $val, $e->content);
    }
  }

  /**
   * Provide a list of available entityRef filters.
   *
   * @return array
   */
  public static function getEntityRefMetadata() {
    $data = [
      'filters' => [],
      'links' => [],
    ];
    $config = CRM_Core_Config::singleton();

    $disabledComponents = [];
    $dao = CRM_Core_DAO::executeQuery("SELECT name, namespace FROM civicrm_component");
    while ($dao->fetch()) {
      if (!in_array($dao->name, $config->enableComponents)) {
        $disabledComponents[$dao->name] = $dao->namespace;
      }
    }

    foreach (CRM_Core_DAO_AllCoreTables::daoToClass() as $entity => $daoName) {
      // Skip DAOs of disabled components
      foreach ($disabledComponents as $nameSpace) {
        if (strpos($daoName, $nameSpace) === 0) {
          continue 2;
        }
      }
      $baoName = str_replace('_DAO_', '_BAO_', $daoName);
      if (class_exists($baoName)) {
        $filters = $baoName::getEntityRefFilters();
        if ($filters) {
          $data['filters'][$entity] = $filters;
        }
        if (is_callable([$baoName, 'getEntityRefCreateLinks'])) {
          $createLinks = $baoName::getEntityRefCreateLinks();
          if ($createLinks) {
            $data['links'][$entity] = $createLinks;
          }
        }
      }
    }

    CRM_Utils_Hook::entityRefFilters($data['filters']);

    return $data;
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

  /**
   * @param string $url
   * @return string
   */
  public function addCacheCode($url) {
    $hasQuery = strpos($url, '?') !== FALSE;
    $operator = $hasQuery ? '&' : '?';

    return $url . $operator . 'r=' . $this->cacheCode;
  }

  /**
   * Checks if the given URL is fully-formed
   *
   * @param string $url
   *
   * @return bool
   */
  public static function isFullyFormedUrl($url) {
    return (substr($url, 0, 4) === 'http') || (substr($url, 0, 1) === '/');
  }

}
