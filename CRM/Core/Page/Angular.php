<?php

/**
 * This page is simply a container; any Angular modules defined by CiviCRM (or by CiviCRM extensions)
 * will be activated on this page.
 *
 * @link https://issues.civicrm.org/jira/browse/CRM-14479
 */
class CRM_Core_Page_Angular extends CRM_Core_Page {
  /**
   * The weight to assign to any Angular JS module files
   */
  const DEFAULT_MODULE_WEIGHT = 200;

  function run() {
    $res = CRM_Core_Resources::singleton();
    $modules = self::getAngularModules();

    $res->addSettingsFactory(function () use (&$modules) {
      // TODO optimization; client-side caching
      return array(
        'resourceUrls' => CRM_Extension_System::singleton()->getMapper()->getActiveModuleUrls(),
        'angular' => array(
          'modules' => array_merge(array('ngRoute'), array_keys($modules)),
        ),
      );
    });

    $res->addScriptFile('civicrm', 'packages/bower_components/angular/angular.js', 100, 'html-header', FALSE);
    $res->addScriptFile('civicrm', 'packages/bower_components/angular-route/angular-route.js', 110, 'html-header', FALSE);
    foreach ($modules as $module) {
      if (!empty($module['file'])) {
        $res->addScriptFile($module['ext'], $module['file'], self::DEFAULT_MODULE_WEIGHT, 'html-header', TRUE);
      }
    }

    return parent::run();
  }

  /**
   * Get a list of AngularJS modules which should be autoloaded
   *
   * @return array (string $name => array('ext' => string $key, 'file' => string $path))
   */
  public static function getAngularModules() {
    $angularModules = array();
    foreach (CRM_Core_Component::getEnabledComponents() as $component) {
      $angularModules = array_merge($angularModules, $component->getAngularModules());
    }
    CRM_Utils_Hook::angularModules($angularModules);
    return $angularModules;
  }

}