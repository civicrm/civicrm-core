<?php

namespace Civi\Esm;

use Civi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The BrowserLoader leverages the browser's built-in support for ECMAScript Modules (ESM's).
 *
 * Any ESM's required by CiviCRM or its extensions are rendered like so:
 *
 *   <script type="importmap">
 *   { "import": {"civicrm/": "https://example.com/sites/all/modules/civicrm"}}
 *   </script>
 *   <script type="module">
 *   import { TableWidget } from "civicrm/TableWidget.js";
 *   const table = new TableWidget();
 *   </script>
 *
 * This should be the simplest and most efficient way to load modules. However, there may be
 * compatibility issues with older browsers or future UFs.
 *
 * For a fuller description of this mechanism, see the neighboring README.
 * @see ./README.md
 *
 * @service esm.loader.browser
 */
class BrowserLoader extends \Civi\Core\Service\AutoService implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      '&civi.esm.useModule' => 'onUseModule',
      '&civi.region.render' => 'onRegionRender',
    ];
  }

  /**
   * Should we generate tags like `<script type="importmap">`?
   *
   * @var bool
   */
  protected $enableMap = TRUE;

  /**
   * Should we generate tags like `<script type="module src="foo.js">`?
   *
   * @var bool
   */
  protected $enableModules = TRUE;

  /**
   * @var \Civi\Esm\ImportMap
   * @inject esm.import_map
   */
  protected $importMap;

  /**
   * Do we need to send an import-map for the current page-view?
   *
   * For the moment, we figure this dynamically -- based on whether any "esm" scripts have
   * been added. During the early stages (where ESMs aren't in widespread use), this seems
   * safer. However, in the future, we might find some kind of race (e.g. where the system
   * renders "<head>" before it decides on a specific "<script type=module"> to load.
   * If that edge-case happens, then it's probably fair to switch this default
   * (`$required=TRUE`).
   *
   * @var bool
   */
  protected $required = FALSE;

  /**
   * @param bool $enableMap
   */
  public function setEnableMap(bool $enableMap): void {
    $this->enableMap = $enableMap;
  }

  /**
   * @param bool $enableModules
   */
  public function setEnableModules(bool $enableModules): void {
    $this->enableModules = $enableModules;
  }

  /**
   * Receive a notification that an ESM is being used.
   *
   * @param array $snippet
   *   The module resource being rendered, as per "CollectionInterface::add()".
   *   Ex: ['type' => 'scriptUrl', 'scriptUrl' => 'https://example.com/foo.js', 'esm' => TRUE, 'region' => 'page-footer']
   * @see \CRM_Core_Resources_CollectionTrait::add()
   */
  public function onUseModule(array &$snippet): void {
    $this->required = TRUE;
  }

  /**
   * Listen to 'civi.region.render[html-header]'.
   *
   * If there are any active "module"s on this page, then output the "import-map".
   *
   * @param \CRM_Core_Region $region
   */
  public function onRegionRender(\CRM_Core_Region $region): void {
    if ($region->_name !== 'html-header' || !$this->required || $this !== Civi::service('esm.loader')) {
      return;
    }

    $importMap = $this->importMap->get();
    $region->add([
      'name' => 'import-map',
      'markup' => $this->renderImportMap($importMap),
      'weight' => -1000,
    ]);
  }

  /**
   * Format the list of imports as an HTML tag.
   *
   * @param array $importMap
   *   Ex: ['imports' => ['square/' => 'https://example.com/square/']]
   * @return string
   *   Ex: '<script type="importmap">{"imports": ...}</script>'
   */
  protected function renderImportMap(array $importMap): string {
    if (!$this->enableMap || empty($importMap)) {
      return '';
    }

    $flags = JSON_UNESCAPED_SLASHES;
    if (Civi::settings()->get('debug_enabled')) {
      $flags |= JSON_PRETTY_PRINT;
    }
    return sprintf("<script type='importmap'>\n%s\n</script>", json_encode($importMap, $flags));
  }

  /**
   * @param array $snippet
   *   The module resource being rendered, as per "CollectionInterface::add()".
   *   Ex: ['type' => 'scriptUrl', 'scriptUrl' => 'https://example.com/foo.js', 'esm' => TRUE]
   * @return string
   *   HTML
   * @see \CRM_Core_Resources_CollectionInterface::add()
   */
  public function renderModule(array $snippet): string {
    if (!$this->enableModules) {
      return '';
    }

    switch ($snippet['type']) {
      case 'script':
        return sprintf("<script type=\"module\">\n%s\n</script>\n", $snippet['script']);

      case 'scriptUrl':
        return sprintf("<script type=\"module\" src=\"%s\">\n</script>\n", $snippet['scriptUrl']);

      default:
        $class = get_class($this);
        Civi::log()->warning($class . ' does not support {type}', ['type' => $snippet['type']]);
        return '';
    }
  }

}
