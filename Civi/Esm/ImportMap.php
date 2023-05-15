<?php

namespace Civi\Esm;

use Civi\Core\HookInterface;

/**
 * ECMAScript Modules (ESMs) allow you to load a JS file based on a physical-path or a
 * logical-path. Compare:
 *
 * - import { TableWidget } from 'https://example.com/sites/all/modules/civicrm/js/table-widget.js';
 * - import { TableWidget } from 'civicrm/js/tab-widget.js';
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
 * If your extension includes ESM files, then you may want to add items to the import-map:
 *
 *   function myext_civicrm_esmImportMap(array &$importMap, array $context): void {
 *     $importMap['imports']['foo/'] = E::url('js/foo/');
 *     $importMap['imports']['bar/'] = E::url('packages/bar/dist/');
 *   }
 *
 * Note: These mappings are used by an ESM Loader, such as "BasicLoader".
 *
 * @see \Civi\Esm\BrowserLoader
 *
 * @service esm.import_map
 */
class ImportMap extends \Civi\Core\Service\AutoService implements HookInterface {

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
      \CRM_Utils_Hook::esmImportMap($importMap, []);
      \Civi::cache('long')->set('import-map', $importMap);
    }
    return $importMap;
  }

  /**
   * Register default mappings on behalf of civicrm-core.
   *
   * @param array $importMap
   * @param array $context
   * @return void
   */
  public function hook_civicrm_esmImportMap(array &$importMap, array $context): void {
    $importMap['imports']['civicrm/'] = \Civi::paths()->getUrl('[civicrm.root]/');
  }

}
