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
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_Resources implements CRM_Core_Resources_CollectionAdderInterface {
  const DEFAULT_WEIGHT = 0;
  const DEFAULT_REGION = 'page-footer';

  use CRM_Core_Resources_CollectionAdderTrait;

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
   * Any bundles that have been added.
   *
   * Format is ($bundleName => bool).
   *
   * @var array
   */
  protected $addedBundles = [];

  /**
   * Added core resources.
   *
   * Format is ($regionName => bool).
   *
   * @var array
   */
  protected $addedCoreResources = [];

  /**
   * Added settings.
   *
   * Format is ($regionName => bool).
   *
   * @var array
   */
  protected $addedSettings = [];

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
   * @param CRM_Core_Resources_Strings $strings
   *   JS-localization cache.
   * @param string|null $cacheCodeKey Random code to append to resource URLs; changing the code forces clients to reload resources
   */
  public function __construct($extMapper, $strings, $cacheCodeKey = NULL) {
    $this->extMapper = $extMapper;
    $this->strings = $strings;
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
   * Add an item to the collection.
   *
   * @param array $snippet
   * @return array
   *   The full/computed snippet (with defaults applied).
   * @see CRM_Core_Resources_CollectionInterface::add()
   */
  public function add($snippet) {
    if (!isset($snippet['region'])) {
      $snippet['region'] = self::DEFAULT_REGION;
    }
    if (!isset($snippet['weight'])) {
      $snippet['weight'] = self::DEFAULT_WEIGHT;
    }
    return CRM_Core_Region::instance($snippet['region'])->add($snippet);
  }

  /**
   * Locate the 'settings' snippet.
   *
   * @param array $options
   * @return array
   * @see CRM_Core_Resources_CollectionTrait::findCreateSettingSnippet()
   */
  public function &findCreateSettingSnippet($options = []): array {
    $options = self::mergeSettingOptions($options, [
      'region' => NULL,
    ]);
    return $this->getSettingRegion($options['region'])->findCreateSettingSnippet($options);
  }

  /**
   * Assimilate all the resources listed in a bundle.
   *
   * @param iterable|string|\CRM_Core_Resources_Bundle $bundle
   *   Either bundle object, or the symbolic name of a bundle, or a list of bundles.
   *   Note: For symbolic names, the bundle must be a container service ('bundle.FOO').
   * @return static
   */
  public function addBundle($bundle) {
    // There are two ways you might write this method: (1) immediately merge
    // resources from the bundle, or (2) store a reference to the bundle and
    // merge resources later. Both have pros/cons. The implementation does #1.
    //
    // The upshot of #1 is *multi-region* support. For example, a bundle might
    // add some JS to `html-header` and then add some HTML to `page-header`.
    // Implementing this requires splitting the bundle (ie copying specific
    // resources to their respective regions). The timing of `addBundle()` is
    // favorable to splitting.
    //
    // The upshot of #2 would be *reduced timing sensitivity for downstream*:
    // if party A wants to include some bundle, and party B wants to refine
    // the same bundle, then it wouldn't matter if A or B executed first.
    // This should make DX generally more forgiving. But we can't split until
    // everyone has their shot at tweaking the bundle.
    //
    // In theory, you could have both characteristics if you figure the right
    // time at which to perform a split. Or maybe you could have both by tracking
    // more detailed references+events among the bundles/regions. I haven't
    // seen a simple way to do get both.

    if (is_iterable($bundle)) {
      foreach ($bundle as $b) {
        $this->addBundle($b);
      }
      return $this;
    }

    if (is_string($bundle)) {
      $bundle = Civi::service('bundle.' . $bundle);
    }

    if (isset($this->addedBundles[$bundle->name])) {
      return $this;
    }
    $this->addedBundles[$bundle->name] = TRUE;

    // Ensure that every asset has a region.
    $bundle->filter(function($snippet) {
      if (empty($snippet['region'])) {
        $snippet['region'] = isset($snippet['settings'])
          ? $this->getSettingRegion()->_name
          : self::DEFAULT_REGION;
      }
      return $snippet;
    });

    $byRegion = CRM_Utils_Array::index(['region', 'name'], $bundle->getAll());
    foreach ($byRegion as $regionName => $snippets) {
      CRM_Core_Region::instance($regionName)->merge($snippets);
    }
    return $this;
  }

  /**
   * Helper fn for addSettingsFactory.
   */
  public function getSettings($region = NULL) {
    return $this->getSettingRegion($region)->getSettings();
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
  public function glob($ext, $patterns, $flags = 0) {
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
    // Ex: AngularJS json partials are language-specific because they ship with the strings
    // for the current language.
    return $this->cacheCode . CRM_Core_I18n::getLocale();
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
   * @param string $region
   * @return CRM_Core_Resources
   */
  public function addCoreResources($region = 'html-header') {
    if ($region !== 'html-header') {
      // The signature of this method allowed different regions. However, this
      // doesn't appear to be used - based on grepping `universe` generally
      // and `civicrm-{core,backdrop,drupal,packages,wordpress,joomla}` specifically,
      // it appears that all callers use 'html-header' (either implicitly or explicitly).
      throw new \CRM_Core_Exception("Error: addCoreResources only supports html-header");
    }
    if (!self::isAjaxMode()) {
      $this->addBundle('coreResources');
      $this->addCoreStyles($region);
    }
    return $this;
  }

  /**
   * This will add CiviCRM's standard CSS
   *
   * @param string $region
   * @return CRM_Core_Resources
   */
  public function addCoreStyles($region = 'html-header') {
    if ($region !== 'html-header') {
      // The signature of this method allowed different regions. However, this
      // doesn't appear to be used - based on grepping `universe` generally
      // and `civicrm-{core,backdrop,drupal,packages,wordpress,joomla}` specifically,
      // it appears that all callers use 'html-header' (either implicitly or explicitly).
      throw new \CRM_Core_Exception("Error: addCoreResources only supports html-header");
    }
    $this->addBundle('coreStyles');
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
  public static function renderL10nJs(GenericHookEvent $e) {
    if ($e->asset !== 'crm-l10n.js') {
      return;
    }
    $e->mimeType = 'application/javascript';
    $params = $e->params;
    $params += [
      'contactSearch' => json_encode($params['includeEmailInName'] ? ts('Search by name/email or id...') : ts('Search by name or id...')),
      'otherSearch' => json_encode(ts('Enter search term or id...')),
      'entityRef' => self::getEntityRefMetadata(),
    ];
    $e->content = CRM_Core_Smarty::singleton()->fetchWith('CRM/common/l10n.js.tpl', $params);
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
    list($arg0, $arg1) = array_pad(explode('/', CRM_Utils_System::currentPath()), 2, '');
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
    $content = '';
    $config = CRM_Core_Config::singleton();
    $cms = strtolower($config->userFramework);
    $cms = $cms === 'drupal' ? 'drupal7' : $cms;
    $items = [
      'bower_components/smartmenus/dist/css/sm-core-css.css',
      'css/crm-menubar.css',
      "css/menubar-$cms.css",
    ];
    foreach ($items as $item) {
      $content .= file_get_contents(self::singleton()->getPath('civicrm', $item));
    }
    $params = $e->params;
    // "color" is deprecated in favor of the more specific "menubarColor"
    $menubarColor = $params['color'] ?? $params['menubarColor'];
    $vars = [
      '$resourceBase' => rtrim($config->resourceBase, '/'),
      '$menubarHeight' => $params['height'] . 'px',
      '$breakMin' => $params['breakpoint'] . 'px',
      '$breakMax' => ($params['breakpoint'] - 1) . 'px',
      '$menubarColor' => $menubarColor,
      '$menuItemColor' => $params['menuItemColor'] ?? $menubarColor,
      '$highlightColor' => $params['highlightColor'] ?? CRM_Utils_Color::getHighlight($menubarColor),
      '$textColor' => $params['textColor'] ?? CRM_Utils_Color::getContrast($menubarColor, '#333', '#ddd'),
    ];
    $vars['$highlightTextColor'] = $params['highlightTextColor'] ?? CRM_Utils_Color::getContrast($vars['$highlightColor'], '#333', '#ddd');
    $e->content = str_replace(array_keys($vars), array_values($vars), $content);
  }

  /**
   * Provide a list of available entityRef filters.
   *
   * @return array
   */
  protected static function getEntityRefMetadata() {
    $data = [
      'filters' => [],
      'links' => [],
    ];

    foreach (CRM_Core_DAO_AllCoreTables::daoToClass() as $entity => $daoName) {
      // Skip DAOs of disabled components
      if (defined("$daoName::COMPONENT") && !CRM_Core_Component::isEnabled($daoName::COMPONENT)) {
        continue;
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

    CRM_Utils_Hook::entityRefFilters($data['filters'], $data['links']);

    return $data;
  }

  /**
   * Determine the minified file name.
   *
   * @param string $ext
   * @param string $file
   * @return string
   *   An updated $fileName. If a minified version exists and is supported by
   *   system policy, the minified version will be returned. Otherwise, the original.
   */
  public function filterMinify($ext, $file) {
    if (CRM_Core_Config::singleton()->debug && strpos($file, '.min.') !== FALSE) {
      $nonMiniFile = str_replace('.min.', '.', $file);
      if ($this->getPath($ext, $nonMiniFile)) {
        $file = $nonMiniFile;
      }
    }
    return $file;
  }

  /**
   * @param string $url
   * @return string
   */
  public function addCacheCode($url) {
    $hasQuery = strpos($url, '?') !== FALSE;
    $operator = $hasQuery ? '&' : '?';

    return $url . $operator . 'r=' . $this->getCacheCode();
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

  /**
   * @param string|null $region
   *   Optional request for a specific region. If NULL/omitted, use global default.
   * @return \CRM_Core_Region
   */
  private function getSettingRegion($region = NULL) {
    $region = $region ?: (self::isAjaxMode() ? 'ajax-snippet' : 'html-header');
    return CRM_Core_Region::instance($region);
  }

}
