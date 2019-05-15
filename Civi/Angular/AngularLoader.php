<?php
namespace Civi\Angular;

/**
 * The AngularLoader loads any JS/CSS/JSON resources
 * required for setting up AngularJS.
 *
 * The AngularLoader stops short of bootstrapping AngularJS. You may
 * need to `<div ng-app="..."></div>` or `angular.bootstrap(...)`.
 *
 * @code
 * $loader = new AngularLoader();
 * $loader->setPageName('civicrm/case/a');
 * $loader->setModules(array('crmApp'));
 * $loader->load();
 * @endCode
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
   * @var array|NULL
   */
  protected $crmApp = NULL;

  /**
   * AngularLoader constructor.
   */
  public function __construct() {
    $this->res = \CRM_Core_Resources::singleton();
    $this->angular = \Civi::service('angular');
    $this->region = \CRM_Utils_Request::retrieve('snippet', 'String') ? 'ajax-snippet' : 'html-header';
    $this->pageName = isset($_GET['q']) ? $_GET['q'] : NULL;
    $this->modules = [];
  }

  /**
   * Register resources required by Angular.
   *
   * @return AngularLoader
   */
  public function load() {
    $angular = $this->getAngular();
    $res = $this->getRes();

    if ($this->crmApp !== NULL) {
      $this->addModules($this->crmApp['modules']);
      $region = \CRM_Core_Region::instance($this->crmApp['region']);
      $region->update('default', ['disabled' => TRUE]);
      $region->add(['template' => $this->crmApp['file'], 'weight' => 0]);
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

    $moduleNames = $this->findActiveModules();
    if (!$this->isAllModules($moduleNames)) {
      $assetParams = ['modules' => implode(',', $moduleNames)];
    }
    else {
      // The module list will be "all modules that the user can see".
      $assetParams = ['nonce' => md5(implode(',', $moduleNames))];
    }

    $res->addSettingsFactory(function () use (&$moduleNames, $angular, $res, $assetParams) {
      // TODO optimization; client-side caching
      $result = array_merge($angular->getResources($moduleNames, 'settings', 'settings'), [
        'resourceUrls' => \CRM_Extension_System::singleton()->getMapper()->getActiveModuleUrls(),
        'angular' => [
          'modules' => $moduleNames,
          'requires' => $angular->getResources($moduleNames, 'requires', 'requires'),
          'cacheCode' => $res->getCacheCode(),
          'bundleUrl' => \Civi::service('asset_builder')->getUrl('angular-modules.json', $assetParams),
        ],
      ]);
      return $result;
    });

    $res->addScriptFile('civicrm', 'bower_components/angular/angular.min.js', 100, $this->getRegion(), FALSE);
    $res->addScriptFile('civicrm', 'js/crm.angular.js', 101, $this->getRegion(), FALSE);

    $headOffset = 0;
    $config = \CRM_Core_Config::singleton();
    if ($config->debug) {
      foreach ($moduleNames as $moduleName) {
        foreach ($this->angular->getResources($moduleName, 'css', 'cacheUrl') as $url) {
          $res->addStyleUrl($url, self::DEFAULT_MODULE_WEIGHT + (++$headOffset), $this->getRegion());
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

      foreach ($this->angular->getResources($moduleNames, 'css', 'cacheUrl') as $url) {
        $res->addStyleUrl($url, self::DEFAULT_MODULE_WEIGHT + (++$headOffset), $this->getRegion());
      }
    }

    return $this;
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
   * @param array $modules
   * @return AngularLoader
   */
  public function setModules($modules) {
    $this->modules = $modules;
    return $this;
  }

}
