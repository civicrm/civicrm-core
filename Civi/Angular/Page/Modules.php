<?php

namespace Civi\Angular\Page;

use Civi\Angular\Manager;

/**
 * This page aggregates data from Angular modules.
 *
 * Example: Aggregate metadata about all modules in JSON format.
 *   civicrm/ajax/angular-modules?format=json
 *
 * Example: Aggregate metadata for crmUi and crmUtil modules.
 *    civicrm/ajax/angular-modules?format=json&modules=crmUi,crmUtil
 *
 * Example: Aggregate *.js files for all modules.
 *   civicrm/ajax/angular-modules?format=js
 *
 * Example: Aggregate *.css files for all modules.
 *   civicrm/ajax/angular-modules?format=css
 */
class Modules extends \CRM_Core_Page {

  /**
   * Generate asset content (when accessed via older, custom
   * "civicrm/ajax/anulgar-modules" route).
   *
   * @deprecated
   *
   * @throws \CRM_Core_Exception
   */
  public function run() {
    /**
     * @var \Civi\Angular\Manager $angular
     */
    $angular = \Civi::service('angular');
    $moduleNames = $this->parseModuleNames(\CRM_Utils_Request::retrieve('modules', 'String'), $angular);

    switch (\CRM_Utils_Request::retrieve('format', 'String')) {
      case 'json':
      case '':
        $this->send(
          'application/javascript',
          json_encode($this->getMetadata($moduleNames, $angular))
        );
        break;

      case 'js':
        $this->send(
          'application/javascript',
          $this->digestJs($angular->getResources($moduleNames, 'js', 'path'))
        );
        break;

      case 'css':
        $this->send(
          'text/css',
          \CRM_Utils_File::concat($angular->getResources($moduleNames, 'css', 'path'), "\n")
        );
        break;

      default:
        throw new \CRM_Core_Exception("Unrecognized format");
    }

    \CRM_Utils_System::civiExit();
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
        $moduleNames = $page->parseModuleNames($event->params['modules'] ?? NULL, $angular);
        $event->mimeType = 'application/json';
        $event->content = json_encode($page->getMetadata($moduleNames, $angular));
        break;

      case 'angular-modules.js':
        $moduleNames = $page->parseModuleNames($event->params['modules'] ?? NULL, $angular);
        $event->mimeType = 'application/javascript';
        $files = array_merge(
          // FIXME: The `resetLocationProviderHashPrefix.js` has to stay in sync with `\Civi\Angular\AngularLoader::load()`.
          [\Civi::resources()->getPath('civicrm', 'ang/resetLocationProviderHashPrefix.js')],
          $angular->getResources($moduleNames, 'js', 'path')
        );
        $event->content = $page->digestJs($files);
        break;

      case 'angular-modules.css':
        $moduleNames = $page->parseModuleNames($event->params['modules'] ?? NULL, $angular);
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
   * @param \Civi\Angular\Manager $angular
   * @return array
   *   Any well-formed module names. All if moduleExpr is blank.
   */
  public function parseModuleNames($modulesExpr, $angular) {
    if ($modulesExpr) {
      $moduleNames = preg_grep(
        '/^[a-zA-Z0-9\-_\.]+$/',
        explode(',', $modulesExpr)
      );
      return $moduleNames;
    }
    else {
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

  /**
   * Send a response.
   *
   * @param string $type
   *   Content type.
   * @param string $data
   *   Content.
   */
  public function send($type, $data) {
    // Encourage browsers to cache for a long time - 1 year
    $ttl = 60 * 60 * 24 * 364;
    \CRM_Utils_System::setHttpHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $ttl));
    \CRM_Utils_System::setHttpHeader("Content-Type", $type);
    \CRM_Utils_System::setHttpHeader("Cache-Control", "max-age=$ttl, public");
    echo $data;
  }

}
