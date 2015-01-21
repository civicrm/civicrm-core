<?php

namespace Civi\Angular\Page;

/**
 * This page returns HTML partials used by Angular.
 */
class Modules extends \CRM_Core_Page {

  /**
   * This page aggregates HTML partials used by Angular.
   */
  public function run() {
    //$config = \CRM_Core_Config::singleton();
    //\CRM_Core_Page_AJAX::setJsHeaders($config->debug ? 30 : NULL);
    \CRM_Core_Page_AJAX::setJsHeaders();

    /**
     * @var \Civi\Angular\Manager $angular
     */
    $angular = \Civi\Core\Container::singleton()->get('angular');
    $modules = $angular->getModules();

    $modulesExpr = \CRM_Utils_Request::retrieve('modules', 'String');
    if ($modulesExpr) {
      $moduleNames = preg_grep(
        '/^[a-zA-Z0-9\-_\.]+$/',
        explode(',', $modulesExpr)
      );
    }
    else {
      $moduleNames = array_keys($modules);
    }

    $result = array();
    foreach ($moduleNames as $moduleName) {
      if (isset($modules[$moduleName])) {
        $result[$moduleName] = array();
        $result[$moduleName]['domain'] = $modules[$moduleName]['ext'];
        $result[$moduleName]['js'] = $angular->getScriptUrls($moduleName);
        $result[$moduleName]['css'] = $angular->getStyleUrls($moduleName);
        $result[$moduleName]['partials'] = $angular->getPartials($moduleName);
        $result[$moduleName]['strings'] = $angular->getTranslatedStrings($moduleName);
      }
    }

    echo json_encode($result);
    \CRM_Utils_System::civiExit();
  }

}
