<?php

namespace Civi\Esm;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\HookInterface;

/**
 * ECMAScript Modules (ESMs) allow you to load a JS file based on a physical-path or a
 * logical-path. Compare:
 *
 *    import { TableWidget } from 'https://example.com/sites/all/modules/civicrm/js/table-widget.js';
 *    import { TableWidget } from 'civicrm/js/tab-widget.js';
 *
 * The logical-path (`civicrm/js/tab-widget.js`) is much easier to read, and it adapts
 * better to more environments.
 *
 * Logical-paths must be defined with an import-map:
 *
 *   <script type="importmap">
 *   { "import": {"civicrm/": "https://example.com/sites/all/modules/civicrm"}}
 *   </script>
 *
 * This service defines the import-map for CiviCRM and its extensions. There are a few
 * perspectives on how to use this service.
 *
 * ###################################################################################
 * ## Extension Developer: How to register additional mappings
 *
 * If you are writing Javascript code for an extension, then you may want to define new
 * mappings, e.g.
 *
 *   function myext_civicrm_esmImportMap(array &$importMap, array $context): void {
 *     $importMap['imports']['foo/'] = E::url('js/foo/');
 *     $importMap['imports']['bar/'] = E::url('packages/bar/dist/');
 *   }
 *
 * ###################################################################################
 * ## Core Developer: How to render a default SCRIPT tag
 *
 * CiviCRM must generate the SCRIPT tag because (at time of writing) none of the supported
 * UF's have a mechanism to do so.
 *
 * - Logic: IF the current page has any ESM modules, THEN display a SCRIPT in the HEAD.
 *
 * - Implementation: The import-map listens to the `civi.region.render[html-header]` event.
 *   Whenever the header is generated, it makes a dynamic decision about whether to display.
 *
 * ###################################################################################
 * ## UF/CMS Developer: How to integrate import-maps from Civi and UF/CMS.
 *
 * In the future, UFs may define their own protocols for generating their own import-maps.
 * But the browser can only load one import-map. Therefore, the CiviCRM and UF import-maps
 * will need to be integrated. Here is how:
 *
 * 1. Disable CiviCRM's default SCRIPT renderer:
 *
 *    Civi::dispatcher()->addListener('hook_config', fn($e) => Civi::service('import_map')->setAutoInject(FALSE));
 *
 *    (*You might do this in `CRM_Utils_System_*::initialize()`*)
 *
 * 2. Get the import-map from CiviCRM:
 *
 *    $importMap = Civi::service('import_map')->get();
 *
 * 3. Pass the $importMap to the UF's native API (with any required transformations).
 *
 * @service import_map
 */
class ImportMap extends \Civi\Core\Service\AutoService implements HookInterface {

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
   * @see \CRM_Core_Resources_CollectionTrait::add()
   */
  protected $required = FALSE;

  /**
   * Should we automatically add the import-map to the HTML header?
   *
   * This is currently TRUE. In the future, there may be UF services that generate
   * the import-map.
   *
   * @var bool
   */
  protected $autoInject = TRUE;

  /**
   * Listen to 'civi.region.render[html-header]'.
   *
   * If there are any active "module"s on this page, then output the "import-map".
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public function on_civi_region_render(GenericHookEvent $e): void {
    if ($e->region->_name !== 'html-header' || !$this->isAutoInject() || !$this->isRequired()) {
      return;
    }

    $importMap = $this->get();
    $e->region->add([
      'name' => 'import-map',
      'markup' => $this->render($importMap),
      'weight' => -1000,
    ]);
  }

  /**
   * Should we automatically add the import-map to the HTML header?
   *
   * This is currently TRUE. In the future, there may be UF services that generate
   * the import-map.
   *
   * @return bool
   */
  public function isAutoInject(): bool {
    return $this->autoInject;
  }

  /**
   * Does the current page-load define any "module" files?
   *
   * @return bool
   */
  public function isRequired(): bool {
    return $this->required;
  }

  /**
   * @param bool $required
   * @return ImportMap
   */
  public function setRequired(bool $required): ImportMap {
    $this->required = $required;
    return $this;
  }

  /**
   * @param bool $autoInject
   * @return ImportMap
   */
  public function setAutoInject(bool $autoInject): ImportMap {
    $this->autoInject = $autoInject;
    return $this;
  }

  /**
   * Get the list of imports.
   *
   * @return array
   *   Ex: ['imports' => ['square/' => 'https://example.com/square/']]
   * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/script/type/importmap
   * @link https://github.com/WICG/import-maps
   */
  public function get(): array {
    // Just how dynamic is the import-map? Perhaps every page-view would have a different
    // import-map? Perhaps there should be one static import-map used for all page-views?
    // Can we cache the import-map?

    // This implementation treats the import-map as a static, cacheable document (one
    // import-map serves diverse page-views).

    // This should prepare us for a few contingencies:
    //   - In the future, we may encourage client-side caching
    //     (`<script type="importmap" src="https://example.com/files/cached.importmap">`).
    //     This is defined by WICG but is not yet supported by all browsers.
    //   - In the future, we may integrate into the import-map services of various UFs.
    //     We cannot predict whether their approach will be static or dynamic. If our
    //     map is static, then we can safely feed it into UFs either way.

    $importMap = \Civi::cache('long')->get('import-map');
    if ($importMap === NULL) {
      $importMap = [];
      $importMap['imports']['civicrm/'] = \Civi::paths()->getUrl('[civicrm.root]/');
      \CRM_Utils_Hook::esmImportMap($importMap, []);
      \Civi::cache('long')->set('import-map', $importMap);
    }
    return $importMap;
  }

  /**
   * Format the list of imports as an HTML tag.
   *
   * @param array $importMap
   *   Ex: ['imports' => ['square/' => 'https://example.com/square/']]
   * @return string
   *   Ex: '<script type="importmap">{"imports": ...}</script>'
   */
  public function render(array $importMap): string {
    if (empty($importMap)) {
      return '';
    }

    $flags = JSON_UNESCAPED_SLASHES;
    if (\Civi::settings()->get('debug_enabled')) {
      $flags |= JSON_PRETTY_PRINT;
    }
    return sprintf("<script type='importmap'>\n%s\n</script>", json_encode($importMap, $flags));
  }

}
