<?php

namespace Civi\Angular\Page;

use Civi\Angular\Manager;

/**
 * Page callback to load Angular modules.
 */
class Modules extends \CRM_Core_Page {

  /**
   * Ajax callback used for on-demand loading of Angular modules.
   *
   * e.g. civicrm/ajax/angular-modules?modules=crmSearchDisplayList
   */
  public function run() {
    $moduleNames = $this->parseModuleNames(\CRM_Utils_Request::retrieve('modules', 'String'));

    if ($moduleNames) {
      $loader = \Civi::service('angularjs.loader');
      $loader->addModules($moduleNames);
      $this->addAjaxResources();
    }
    \CRM_Core_Page_AJAX::returnJsonResponse($this->ajaxResponse);
  }

  /**
   * Generate asset content (when accessed via AssetBuilder).
   *
   * @param \Civi\Core\Event\GenericHookEvent $event
   * @see CRM_Utils_hook::buildAsset()
   * @see \Civi\Core\AssetBuilder
   */
  public static function buildAngularModules($event) {
    $page = new Modules();
    $angular = \Civi::service('angular');

    switch ($event->asset) {
      case 'angular-modules.json':
        $moduleNames = $page->parseModuleNames($event->params['modules'] ?? NULL);
        $event->mimeType = 'application/json';
        $event->content = json_encode($page->getMetadata($moduleNames, $angular));
        break;

      case 'angular-modules.js':
        $moduleNames = $page->parseModuleNames($event->params['modules'] ?? NULL);
        $event->mimeType = 'application/javascript';
        $files = array_merge(
          // FIXME: The `resetLocationProviderHashPrefix.js` has to stay in sync with `\Civi\Angular\AngularLoader::load()`.
          [\Civi::resources()->getPath('civicrm', 'ang/resetLocationProviderHashPrefix.js')],
          $angular->getResources($moduleNames, 'js', 'path')
        );
        $event->content = $page->digestJs($files);
        break;

      case 'angular-modules.css':
        $moduleNames = $page->parseModuleNames($event->params['modules'] ?? NULL);
        $event->mimeType = 'text/css';
        $event->content = \CRM_Utils_File::concat($angular->getResources($moduleNames, 'css', 'path'), "\n");

      default:
        // Not our problem.
    }
  }

  /**
   * @param array $files
   *   File paths.
   * @return string
   */
  public function digestJs($files) {
    $scripts = [];
    foreach ($files as $file) {
      $content = file_get_contents($file);
      if (str_contains($file, 'monaco-editor')) {
        $scripts[] = $content;
      }
      else {
        $scripts[] = \CRM_Utils_JS::stripComments($content);
      }
    }
    $scripts = \CRM_Utils_JS::dedupeClosures(
      $scripts,
      ['angular', '$', '_'],
      ['angular', 'CRM.$', 'CRM._']
    );
    return implode("\n", $scripts);
  }

  /**
   * @param string $modulesExpr
   *   Comma-separated list of module names.
   * @return array
   *   Any well-formed module names. All if moduleExpr is blank.
   */
  public function parseModuleNames($modulesExpr): array {
    if ($modulesExpr) {
      $moduleNames = preg_grep(
        '/^[a-zA-Z0-9\-_\.]+$/',
        explode(',', $modulesExpr)
      );
      return $moduleNames;
    }
    else {
      $angular = \Civi::service('angular');
      $moduleNames = array_keys($angular->getModules());
      return $moduleNames;
    }
  }

  /**
   * @param array $moduleNames
   *   List of module names.
   * @param \Civi\Angular\Manager $angular
   * @return array
   */
  public function getMetadata(array $moduleNames, Manager $angular): array {
    $result = [];
    foreach ($moduleNames as $moduleName) {
      $module = $angular->getModule($moduleName);
      $result[$moduleName] = [
        'domain' => $module['ext'],
        'js' => $angular->getResources($moduleName, 'js', 'rawUrl'),
        'css' => $angular->getResources($moduleName, 'css', 'rawUrl'),
        'partials' => $angular->getPartials($moduleName),
        'strings' => $angular->getTranslatedStrings($moduleName),
      ];
    }
    return $result;
  }

}
