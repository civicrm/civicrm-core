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
 * The purpose of `Civi\Esm\ImportMap` (aka `esm.import_map`) is to build a list of all
 * the logical prefixes supported by `civicrm-core` and CiviCRM extensions.
 *
 * This is generally consumed by BrowserLoader or a similar class. For a fuller description
 * of this mechanism, see the neighboring README.
 *
 * @see \Civi\Esm\BrowserLoader
 * @see ./README.md
 *
 * @service esm.import_map
 */
class ImportMap extends \Civi\Core\Service\AutoService implements HookInterface {

  /**
   * @var array|null
   *  Ex: [['prefix' => 'lodash/', 'ext' => 'civicrm', 'relPath' => 'bower_components/lodash/']]
   */
  protected $prefixes = NULL;

  /**
   * @param string $prefix
   * @param string $ext
   *   Ex: 'civicrm', 'org.example.foobar'
   * @param string $relPath
   *   Relative path within $ext.
   *   Ex: '/', '/packages/foo-1.2.3/'
   * @return $this
   * @see \CRM_Core_Resources::getUrl()
   * @see \CRM_Core_Resources::getPath()
   */
  public function addPrefix(string $prefix, string $ext, string $relPath = ''): ImportMap {
    $this->prefixes[$prefix] = [
      'prefix' => $prefix,
      'ext' => $ext,
      'relPath' => $relPath,
    ];
    return $this;
  }

  public function getPrefixes(): array {
    if ($this->prefixes === NULL) {
      $this->load();
    }
    return $this->prefixes;
  }

  /**
   * Load the list of imports, as declared by CiviCRM and its extensions.
   *
   * @return $this
   */
  protected function load(): ImportMap {
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

    $this->prefixes = \Civi::cache('long')->get('esm.import_map.prefix');
    if ($this->prefixes === NULL) {
      $this->prefixes = [];
      \CRM_Utils_Hook::esmImportMap($this);
      \Civi::cache('long')->set('esm.import_map.prefix', $this->prefixes);
    }
    return $this;
  }

  /**
   * Register default mappings on behalf of civicrm-core.
   *
   * @param ImportMap $importMap
   * @return void
   */
  public function hook_civicrm_esmImportMap(ImportMap $importMap): void {
    $importMap->addPrefix('civicrm/', 'civicrm');
  }

}
