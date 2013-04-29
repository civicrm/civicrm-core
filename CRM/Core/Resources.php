<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
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
   * @static
   */
  private static $_singleton = NULL;

  /**
   * @var CRM_Extension_Mapper
   */
  private $extMapper = NULL;

  /**
   * @var CRM_Utils_Cache_Interface
   */
  private $cache = NULL;

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
   * Get or set the single instance of CRM_Core_Resources
   *
   * @param $instance CRM_Core_Resources, new copy of the manager
   * @return CRM_Core_Resources
   */
  static public function singleton(CRM_Core_Resources $instance = NULL) {
    if ($instance !== NULL) {
      self::$_singleton = $instance;
    }
    if (self::$_singleton === NULL) {
      $sys = CRM_Extension_System::singleton();
      $cache = new CRM_Utils_Cache_SqlGroup(array(
        'group' => 'js-strings',
        'prefetch' => FALSE,
      ));
      self::$_singleton = new CRM_Core_Resources(
        $sys->getMapper(),
        $cache,
        CRM_Core_Config::isUpgradeMode() ? NULL : 'resCacheCode'
      );
    }
    return self::$_singleton;
  }

  /**
   * Construct a resource manager
   *
   * @param CRM_Extension_Mapper $extMapper Map extension names to their base path or URLs.
   */
  public function __construct($extMapper, $cache, $cacheCodeKey = NULL) {
    $this->extMapper = $extMapper;
    $this->cache = $cache;
    $this->cacheCodeKey = $cacheCodeKey;
    if ($cacheCodeKey !== NULL) {
      $this->cacheCode = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, $cacheCodeKey);
    }
    if (!$this->cacheCode) {
      $this->resetCacheCode();
    }
  }

  /**
   * Add a JavaScript file to the current page using <SCRIPT SRC>.
   *
   * @param $ext string, extension name; use 'civicrm' for core
   * @param $file string, file path -- relative to the extension base dir
   * @param $weight int, relative weight within a given region
   * @param $region string, location within the file; 'html-header', 'page-header', 'page-footer'
   * @param $translate, whether to parse this file for strings enclosed in ts()
   *
   * @return CRM_Core_Resources
   */
  public function addScriptFile($ext, $file, $weight = self::DEFAULT_WEIGHT, $region = self::DEFAULT_REGION, $translate = TRUE) {
    if ($translate) {
      $this->translateScript($ext, $file);
    }
    return $this->addScriptUrl($this->getUrl($ext, $file, TRUE), $weight, $region);
  }

  /**
   * Add a JavaScript file to the current page using <SCRIPT SRC>.
   *
   * @param $url string
   * @param $weight int, relative weight within a given region
   * @param $region string, location within the file; 'html-header', 'page-header', 'page-footer'
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
   * @param $code string, JavaScript source code
   * @param $weight int, relative weight within a given region
   * @param $region string, location within the file; 'html-header', 'page-header', 'page-footer'
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
   * Add JavaScript variables to the global CRM object.
   *
   * Example:
   * From the server:
   * CRM_Core_Resources::singleton()->addSetting(array('myNamespace' => array('foo' => 'bar')));
   * From javascript:
   * CRM.myNamespace.foo // "bar"
   *
   * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/Javascript+Reference
   *
   * @param $settings array
   * @return CRM_Core_Resources
   */
  public function addSetting($settings) {
    $this->settings = $this->mergeSettings($this->settings, $settings);
    if (!$this->addedSettings) {
      $resources = $this;
      CRM_Core_Region::instance('html-header')->add(array(
        'callback' => function(&$snippet, &$html) use ($resources) {
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
   * @param $callable function
   * @return CRM_Core_Resources
   */
  public function addSettingsFactory($callable) {
    // Make sure our callback has been registered
    $this->addSetting(array());
    $this->settingsFactories[] = $callable;
    return $this;
  }

  /**
   * Helper fn for addSettingsFactory
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
   * @return array combination of $settings and $additions
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
   * Helper fn for addSetting
   * Render JavaScript variables for the global CRM object.
   *
   * @return string
   */
  public function renderSetting() {
    $js = 'var CRM = ' . json_encode($this->getSettings()) . ';';
    return sprintf("<script type=\"text/javascript\">\n%s\n</script>\n", $js);
  }

  /**
   * Add translated string to the js CRM object.
   * It can then be retrived from the client-side ts() function
   * Variable substitutions can happen from client-side
   * 
   * Note: this function rarely needs to be called directly and is mostly for internal use.
   * @see CRM_Core_Resources::addScriptFile which automatically adds translated strings from js files
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
   * @param $text string|array
   * @return CRM_Core_Resources
   */
  public function addString($text) {
    foreach ((array) $text as $str) {
      $translated = ts($str);
      // We only need to push this string to client if the translation
      // is actually different from the original
      if ($translated != $str) {
        $this->addSetting(array('strings' => array($str => $translated)));
      }
    }
    return $this;
  }

  /**
   * Add a CSS file to the current page using <LINK HREF>.
   *
   * @param $ext string, extension name; use 'civicrm' for core
   * @param $file string, file path -- relative to the extension base dir
   * @param $weight int, relative weight within a given region
   * @param $region string, location within the file; 'html-header', 'page-header', 'page-footer'
   * @return CRM_Core_Resources
   */
  public function addStyleFile($ext, $file, $weight = self::DEFAULT_WEIGHT, $region = self::DEFAULT_REGION) {
    return $this->addStyleUrl($this->getUrl($ext, $file, TRUE), $weight, $region);
  }

  /**
   * Add a CSS file to the current page using <LINK HREF>.
   *
   * @param $url string
   * @param $weight int, relative weight within a given region
   * @param $region string, location within the file; 'html-header', 'page-header', 'page-footer'
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
   * @param $code string, CSS source code
   * @param $weight int, relative weight within a given region
   * @param $region string, location within the file; 'html-header', 'page-header', 'page-footer'
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
   * Determine file path of a resource provided by an extension
   *
   * @param $ext string, extension name; use 'civicrm' for core
   * @param $file string, file path -- relative to the extension base dir
   *
   * @return (string|bool), full file path or FALSE if not found
   */
  public function getPath($ext, $file) {
    // TODO consider caching results
    $path = $this->extMapper->keyToBasePath($ext) . '/' . $file;
    if (is_file($path)) {
      return $path;
    }
    return FALSE;
  }

  /**
   * Determine public URL of a resource provided by an extension
   *
   * @param $ext string, extension name; use 'civicrm' for core
   * @param $file string, file path -- relative to the extension base dir
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
    return $this->extMapper->keyToUrl($ext) . '/' . $file;
  }

  public function getCacheCode() {
    return $this->cacheCode;
  }

  public function setCacheCode($value) {
    $this->cacheCode = $value;
    if ($this->cacheCodeKey) {
      CRM_Core_BAO_Setting::setItem($value, CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, $this->cacheCodeKey);
    }
  }

  public function resetCacheCode() {
    $this->setCacheCode(CRM_Utils_String::createRandom(5, CRM_Utils_String::ALPHANUMERIC));
  }

  /**
   * This adds CiviCRM's standard css and js to the specified region of the document.
   * It will only run once.
   *
   * TODO: Separate the functional code (like addStyle/addScript) from the policy code
   * (like addCoreResources/addCoreStyles).
   *
   * @return CRM_Core_Resources
   * @access public
   */
  public function addCoreResources($region = 'html-header') {
    if (!isset($this->addedCoreResources[$region])) {
      $this->addedCoreResources[$region] = TRUE;
      $config = CRM_Core_Config::singleton();

      // Add resources from jquery.files.tpl
      $files = self::parseTemplate('CRM/common/jquery.files.tpl');
      $jsWeight = -9999;
      foreach ($files as $file => $type) {
        if ($type == 'js') {
          // Don't bother  looking for ts() calls in packages, there aren't any
          $translate = (substr($file, 0, 9) != 'packages/');
          $this->addScriptFile('civicrm', $file, $jsWeight++, $region, $translate);
        }
        elseif ($type == 'css') {
          $this->addStyleFile('civicrm', $file, -100, $region);
        }
      }

      // Add localized calendar js
      // Search for i18n file in order of specificity (try fr-CA, then fr)
      list($lang) = explode('_', $config->lcMessages);
      foreach (array(str_replace('_', '-', $config->lcMessages), $lang) as $language) {
        $localizationFile = "packages/jquery/jquery-ui-1.9.0/development-bundle/ui/i18n/jquery.ui.datepicker-{$language}.js";
        if ($this->getPath('civicrm', $localizationFile)) {
          $this->addScriptFile('civicrm', $localizationFile, $jsWeight++, $region, FALSE);
          break;
        }
      }

      // Initialize CRM.url
      $url = CRM_Utils_System::url('civicrm/example', 'placeholder', FALSE, NULL, FALSE);
      $js = "CRM.url('init', '$url');";
      $this->addScript($js, $jsWeight++, $region);

      // Add global settings
      $settings = array(
        'userFramework' => $config->userFramework,
        'resourceBase' => $config->resourceBase,
      );
      $this->addSetting(array('config' => $settings));

      // Give control of jQuery back to the CMS - this loads last
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
        $this->addStyleUrl($config->customCSSURL, -99, $region);
      }
      else {
        $this->addStyleFile('civicrm', 'css/civicrm.css', -99, $region);
        $this->addStyleFile('civicrm', 'css/extras.css', -98, $region);
      }
    }
    return $this;
  }

  /**
   * Flushes cached translated strings
   */
  public function flushStrings() {
    $this->cache->flush();
  }

  /**
   * Translate strings in a javascript file
   *
   * @param $ext string, extension name
   * @param $file string, file path
   * @return void
   */
  private function translateScript($ext, $file) {
    // For each extension, maintain one cache record which
    // includes parsed (translatable) strings for all its JS files.
    $stringsByFile = $this->cache->get($ext); // array($file => array(...strings...))
    if (!$stringsByFile) {
      $stringsByFile = array();
    }
    if (!isset($stringsByFile[$file])) {
      $filePath = $this->getPath($ext, $file);
      if ($filePath && is_readable($filePath)) {
        $stringsByFile[$file] = CRM_Utils_JS::parseStrings(file_get_contents($filePath));
      } else {
        $stringsByFile[$file] = array();
      }
      $this->cache->set($ext, $stringsByFile);
    }
    $this->addString($stringsByFile[$file]);
  }

  /**
   * Read resource files from a template
   *
   * @param $tpl (str) template file name
   * @return array: filename => filetype
   */
  static function parseTemplate($tpl) {
    $items = array();
    $template = CRM_Core_Smarty::singleton();
    $buffer = $template->fetch($tpl);
    $lines = preg_split('/\s+/', $buffer);
    foreach ($lines as $line) {
      $line = trim($line);
      if ($line) {
        $items[$line] = substr($line, 1 + strrpos($line, '.'));
      }
    }
    return $items;
  }
}
