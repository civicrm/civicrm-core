<?php
namespace Civi\Angular;

/**
 * The AngularLoader loads any JS/CSS/JSON resources
 * required for setting up AngularJS.
 *
 * This class is returned by 'angularjs.loader' service. Example use:
 *
 * ```
 * Civi::service('angularjs.loader')
 *   ->addModules('moduleFoo')
 *   ->useApp(); // Optional, if Civi's routing is desired (full-page apps only)
 * ```
 *
 * @link https://docs.angularjs.org/guide/bootstrap
 */
class AngularLoader {

  /**
   * The weight to assign to any Angular JS module files.
   */
  const DEFAULT_MODULE_WEIGHT = 200;

  /**
   * The resource manager.
   *
   * Do not use publicly. Inject your own copy!
   *
   * @var \CRM_Core_Resources
   */
  protected $res;

  /**
   * The Angular module manager.
   *
   * Do not use publicly. Inject your own copy!
   *
   * @var \Civi\Angular\Manager
   */
  protected $angular;

  /**
   * The region of the page into which JavaScript will be loaded.
   *
   * @var string
   */
  protected $region;

  /**
   * @var array
   * When adding supplimental modules via snippet,
   * these modules are already loaded.
   */
  protected $modulesAlreadyLoaded = [];

  /**
   * @var string
   *   Ex: 'civicrm/a'.
   */
  protected $pageName;

  /**
   * @var array
   *   A list of modules to load.
   */
  protected $modules;

  /**
   * @var array|null
   */
  protected $crmApp = NULL;

  /**
   * AngularLoader constructor.
   */
  public function __construct() {
    $this->res = \CRM_Core_Resources::singleton();
    $this->angular = \Civi::service('angular');
    $this->region = \CRM_Utils_Request::retrieve('snippet', 'String') ? 'ajax-snippet' : 'html-header';
    $this->pageName = \CRM_Utils_System::currentPath();
    $this->modules = [];
    // List of already-present modules may be provided by crmSnippet (see crm.ajax.js)
    if ($this->region === 'ajax-snippet' && !empty($_GET['crmAngularModules'])) {
      $this->modulesAlreadyLoaded = explode(',', $_GET['crmAngularModules']);
    }
    // Ensure region exists
    \CRM_Core_Region::instance($this->region);
  }

  /**
   * Calling this method from outside this class is deprecated.
   *
   * Use the `angularjs.loader` service instead.
   *
   * @deprecated
   * @return $this
   */
  public function load() {
    \CRM_Core_Error::deprecatedFunctionWarning('angularjs.loader service');
    $this->loadAngularResources();
    return $this;
  }

  /**
   * Load scripts, styles & settings for the active modules.
   *
   * @throws \CRM_Core_Exception
   */
  private function loadAngularResources() {
    $angular = $this->getAngular();
    $res = $this->getRes();

    if ($this->crmApp !== NULL) {
      $this->addModules($this->crmApp['modules']);

      $this->res->addSetting([
        'crmApp' => [
          'defaultRoute' => $this->crmApp['defaultRoute'],
        ],
      ]);

      // If trying to load an Angular page via AJAX, the route must be passed as a
      // URL parameter, since the server doesn't receive information about
      // URL fragments (i.e, what comes after the #).
      $this->res->addSetting([
        'angularRoute' => $this->crmApp['activeRoute'],
      ]);
    }

    $allModules = $this->findActiveModules();
    $moduleNames = array_values(array_diff($allModules, $this->modulesAlreadyLoaded));

    if (!$moduleNames && $this->modulesAlreadyLoaded) {
      // No modules to load
      return;
    }
    if (!$this->isAllModules($moduleNames)) {
      $assetParams = ['modules' => implode(',', $moduleNames)];
    }
    else {
      // The module list will be "all modules that the user can see".
      $assetParams = ['nonce' => md5(implode(',', $moduleNames))];
    }

    $res->addSettingsFactory(function () use (&$moduleNames, $angular, $res, $assetParams, $allModules) {
      // Merge static settings with the results of settingsFactory functions
      $settingsByModule = $angular->getResources($moduleNames, 'settings', 'settings');
      foreach ($angular->getResources($moduleNames, 'settingsFactory', 'settingsFactory') as $moduleName => $factory) {
        $settingsByModule[$moduleName] = array_merge($settingsByModule[$moduleName] ?? [], $factory());
      }
      // Add clientside permissions
      $permissions = [];
      $toCheck  = $angular->getResources($moduleNames, 'permissions', 'permissions');
      foreach ($toCheck as $perms) {
        foreach ((array) $perms as $perm) {
          if (!isset($permissions[$perm])) {
            $permissions[$perm] = \CRM_Core_Permission::check($perm);
          }
        }
      }
      // TODO optimization; client-side caching
      return array_merge($settingsByModule, ['permissions' => $permissions], [
        'resourceUrls' => \CRM_Extension_System::singleton()->getMapper()->getActiveModuleUrls(),
        'angular' => [
          'modules' => $allModules,
          'requires' => $angular->getResources($moduleNames, 'requires', 'requires'),
          'cacheCode' => $res->getCacheCode(),
          'bundleUrl' => \Civi::service('asset_builder')->getUrl('angular-modules.json', $assetParams),
        ],
      ]);
    });

    if (!$this->modulesAlreadyLoaded) {
      $res->addScriptFile('civicrm', 'bower_components/angular/angular.min.js', 100, $this->getRegion(), FALSE);
    }

    $headOffset = 0;
    $config = \CRM_Core_Config::singleton();
    if ($config->debug || $this->modulesAlreadyLoaded) {
      if (!$this->modulesAlreadyLoaded) {
        // FIXME: The `resetLocationProviderHashPrefix.js` has to stay in sync with `\Civi\Angular\Page\Modules::buildAngularModules()`.
        $res->addScriptFile('civicrm', 'ang/resetLocationProviderHashPrefix.js', 101, $this->getRegion(), FALSE);
      }
      foreach ($moduleNames as $moduleName) {
        foreach ($this->angular->getResources($moduleName, 'css', 'relUrl') as $relUrl) {
          $res->addStyleFile($relUrl['ext'], $relUrl['file'], self::DEFAULT_MODULE_WEIGHT + (++$headOffset), $this->getRegion());
        }
        foreach ($this->angular->getResources($moduleName, 'js', 'cacheUrl') as $url) {
          $res->addScriptUrl($url, self::DEFAULT_MODULE_WEIGHT + (++$headOffset), $this->getRegion());
          // addScriptUrl() bypasses the normal string-localization of addScriptFile(),
          // but that's OK because all Angular strings (JS+HTML) will load via crmResource.
        }
      }
    }
    else {
      // Note: addScriptUrl() bypasses the normal string-localization of addScriptFile(),
      // but that's OK because all Angular strings (JS+HTML) will load via crmResource.
      // $aggScriptUrl = \CRM_Utils_System::url('civicrm/ajax/angular-modules', 'format=js&r=' . $res->getCacheCode(), FALSE, NULL, FALSE);
      $aggScriptUrl = \Civi::service('asset_builder')->getUrl('angular-modules.js', $assetParams);
      $res->addScriptUrl($aggScriptUrl, 120, $this->getRegion());

      // FIXME: The following CSS aggregator doesn't currently handle path-adjustments - which can break icons.
      //$aggStyleUrl = \CRM_Utils_System::url('civicrm/ajax/angular-modules', 'format=css&r=' . $res->getCacheCode(), FALSE, NULL, FALSE);
      //$aggStyleUrl = \Civi::service('asset_builder')->getUrl('angular-modules.css', $assetParams);
      //$res->addStyleUrl($aggStyleUrl, 120, $this->getRegion());

      foreach ($this->angular->getResources($moduleNames, 'css', 'relUrl') as $relUrl) {
        $res->addStyleFile($relUrl['ext'], $relUrl['file'], self::DEFAULT_MODULE_WEIGHT + (++$headOffset), $this->getRegion());
      }
    }
    // Add bundles
    if (!$this->modulesAlreadyLoaded) {
      foreach ($this->angular->getResources($moduleNames, 'bundles', 'bundles') as $bundles) {
        $res->addBundle($bundles);
      }
    }
  }

  /**
   * Use Civi's generic "application" module.
   *
   * This is suitable for use on a basic, standalone Angular page
   * like `civicrm/a`. (If you need to integrate Angular with pre-existing,
   * non-Angular pages... then this probably won't help.)
   *
   * The Angular bootstrap process requires an HTML directive like
   * `<div ng-app="foo">`.
   *
   * Calling useApp() will replace the page's main body with the
   * `<div ng-app="crmApp">...</div>` and apply some configuration options
   * for the `crmApp` module.
   *
   * @param array $settings
   *   A list of settings. Accepted values:
   *    - activeRoute: string, the route to open up immediately
   *      Ex: '/case/list'
   *    - defaultRoute: string, use this to redirect the default route (`/`) to another page
   *      Ex: '/case/list'
   *    - region: string, the place on the page where we should insert the angular app
   *      Ex: 'page-body'
   * @return AngularLoader
   * @link https://code.angularjs.org/1.5.11/docs/guide/bootstrap
   */
  public function useApp($settings = []) {
    $defaults = [
      'modules' => ['crmApp'],
      'activeRoute' => NULL,
      'defaultRoute' => NULL,
      'region' => 'page-body',
      'file' => 'Civi/Angular/Page/Main.tpl',
    ];
    $this->crmApp = array_merge($defaults, $settings);
    $region = \CRM_Core_Region::instance($this->crmApp['region']);
    $region->update('default', ['disabled' => TRUE]);
    $region->add(['template' => $this->crmApp['file'], 'weight' => 0]);
    return $this;
  }

  /**
   * Get a list of all Angular modules which should be activated on this
   * page.
   *
   * @return array
   *   List of module names.
   *   Ex: array('angularFileUpload', 'crmUi', 'crmUtil').
   */
  public function findActiveModules() {
    return $this->angular->resolveDependencies(array_merge(
      $this->getModules(),
      $this->angular->resolveDefaultModules($this->getPageName())
    ));
  }

  /**
   * @param $moduleNames
   * @return int
   */
  private function isAllModules($moduleNames) {
    $allModuleNames = array_keys($this->angular->getModules());
    return count(array_diff($allModuleNames, $moduleNames)) === 0;
  }

  /**
   * @return \CRM_Core_Resources
   */
  public function getRes() {
    return $this->res;
  }

  /**
   * @param \CRM_Core_Resources $res
   * @return AngularLoader
   */
  public function setRes($res) {
    $this->res = $res;
    return $this;
  }

  /**
   * @return \Civi\Angular\Manager
   */
  public function getAngular() {
    return $this->angular;
  }

  /**
   * @param \Civi\Angular\Manager $angular
   * @return AngularLoader
   */
  public function setAngular($angular) {
    $this->angular = $angular;
    return $this;
  }

  /**
   * @return string
   */
  public function getRegion() {
    return $this->region;
  }

  /**
   * @param string $region
   * @return AngularLoader
   */
  public function setRegion($region) {
    $this->region = $region;
    return $this;
  }

  /**
   * @return string
   *   Ex: 'civicrm/a'.
   */
  public function getPageName() {
    return $this->pageName;
  }

  /**
   * @param string $pageName
   *   Ex: 'civicrm/a'.
   * @return AngularLoader
   */
  public function setPageName($pageName) {
    $this->pageName = $pageName;
    return $this;
  }

  /**
   * @param array|string $modules
   * @return AngularLoader
   */
  public function addModules($modules) {
    $modules = (array) $modules;
    $this->modules = array_unique(array_merge($this->modules, $modules));
    return $this;
  }

  /**
   * @return array
   */
  public function getModules() {
    return $this->modules;
  }

  /**
   * Replace all previously set modules.
   *
   * Use with caution, as it can cause conflicts with other extensions who have added modules.
   * @internal
   * @deprecated
   * @param array $modules
   * @return AngularLoader
   */
  public function setModules($modules) {
    \CRM_Core_Error::deprecatedFunctionWarning('addModules');
    $this->modules = $modules;
    return $this;
  }

  /**
   * Loader service callback when rendering a page region.
   *
   * Loads Angular resources if any modules have been requested for this page.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public function onRegionRender($e) {
    if ($e->region->_name === $this->region && ($this->modules || $this->crmApp)) {
      $this->loadAngularResources();
      if (!$this->modulesAlreadyLoaded) {
        $this->res->addScriptFile('civicrm', 'js/crm-angularjs-loader.js', 200, $this->getRegion(), FALSE);
      }
    }
  }

}
