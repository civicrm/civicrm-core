<?php

namespace Civi\Esm;

use Civi;

/**
 * The AbstractLoader is a base-class BrowserLoader and ShimLoader. These are similar
 * in that they load ESM's by displaying HTML, e.g.
 *
 *   <script type="importmap">
 *   { "import": {"civicrm/": "https://example.com/sites/all/modules/civicrm"}}
 *   </script>
 *
 *   <script type="module">
 *   import { TableWidget } from "civicrm/TableWidget.js";
 *   const table = new TableWidget();
 *   </script>
 *
 * However, subclasses may use different HTML.
 */
trait BasicLoaderTrait {

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
      'markup' => empty($importMap) ? '' : $this->renderImportMap($importMap),
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
  abstract protected function renderImportMap(array $importMap): string;

  /**
   * @param array $snippet
   *   The module resource being rendered, as per "CollectionInterface::add()".
   *   Ex: ['type' => 'scriptUrl', 'scriptUrl' => 'https://example.com/foo.js', 'esm' => TRUE]
   * @return string
   *   HTML
   * @see \CRM_Core_Resources_CollectionInterface::add()
   */
  public function renderModule(array $snippet): string {
    switch ($snippet['type']) {
      case 'script':
        return $this->renderModuleScript($snippet);

      case 'scriptUrl':
        return $this->renderModuleUrl($snippet);

      default:
        $class = get_class($this);
        Civi::log()->warning($class . ' does not support {type}', ['type' => $snippet['type']]);
        return '';
    }
  }

  /**
   * @param array $snippet
   *   The module resource being rendered, as per "CollectionInterface::add()".
   *   Ex: ['type' => 'scriptUrl', 'scriptUrl' => 'https://example.com/foo.js', 'esm' => TRUE]
   * @return string
   *   HTML
   * @see \CRM_Core_Resources_CollectionInterface::add()
   */
  abstract protected function renderModuleScript(array $snippet): string;

  /**
   * @param array $snippet
   *   The module resource being rendered, as per "CollectionInterface::add()".
   *   Ex: ['type' => 'scriptUrl', 'scriptUrl' => 'https://example.com/foo.js', 'esm' => TRUE]
   * @return string
   *   HTML
   * @see \CRM_Core_Resources_CollectionInterface::add()
   */
  abstract protected function renderModuleUrl(array $snippet): string;

}
