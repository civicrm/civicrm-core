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
   * AngularLoader constructor.
   */
  public function __construct() {
    $this->res = \CRM_Core_Resources::singleton();
    $this->angular = \Civi::service('angular');
    $this->region = \CRM_Utils_Request::retrieve('snippet', 'String') ? 'ajax-snippet' : 'html-header';
    $this->pageName = isset($_GET['q']) ? $_GET['q'] : NULL;
    $this->modules = array();
  }

  /**
   * Register resources required by Angular.
   */
  public function load() {
    $angular = $this->getAngular();
    $res = $this->getRes();

    $moduleNames = $this->findActiveModules();
    if (!$this->isAllModules($moduleNames)) {
      $assetParams = array('modules' => implode(',', $moduleNames));
    }
    else {
      // The module list will be "all modules that the user can see".
      $assetParams = array('nonce' => md5(implode(',', $moduleNames)));
    }

    $res->addSettingsFactory(function () use (&$moduleNames, $angular, $res, $assetParams) {
      // TODO optimization; client-side caching
      $result = array_merge($angular->getResources($moduleNames, 'settings', 'settings'), array(
        'resourceUrls' => \CRM_Extension_System::singleton()->getMapper()->getActiveModuleUrls(),
        'angular' => array(
          'modules' => $moduleNames,
          'requires' => $angular->getResources($moduleNames, 'requires', 'requires'),
          'cacheCode' => $res->getCacheCode(),
          'bundleUrl' => \Civi::service('asset_builder')->getUrl('angular-modules.json', $assetParams),
        ),
      ));
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
   */
  public function setRes($res) {
    $this->res = $res;
  }

  /**
   * @return \Civi\Angular\Manager
   */
  public function getAngular() {
    return $this->angular;
  }

  /**
   * @param \Civi\Angular\Manager $angular
   */
  public function setAngular($angular) {
    $this->angular = $angular;
  }

  /**
   * @return string
   */
  public function getRegion() {
    return $this->region;
  }

  /**
   * @param string $region
   */
  public function setRegion($region) {
    $this->region = $region;
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
   */
  public function setPageName($pageName) {
    $this->pageName = $pageName;
  }

  /**
   * @return array
   */
  public function getModules() {
    return $this->modules;
  }

  /**
   * @param array $modules
   */
  public function setModules($modules) {
    $this->modules = $modules;
  }

}
