<?php

namespace Civi\Esm;

use Civi;

/**
 * The ShimLoader leverages `es-module-shims` for loading ECMAScript Modules (ESM's).
 *
 * @link https://github.com/guybedford/es-module-shims
 *
 * ShimLoader is very similar to BrowserLoader. It adds more backward compatibility
 * (e.g. Safari 10 vs 16; Firefox 60 vs 108) and more features (e.g. "JSON Modules"), but
 * it also incurs more runtime overhead.
 *
 * ShimLoader works by:
 *
 * 1. Adding the extra `es-module-shims.js` file.
 * 2. Swapping HTML tags to prefer shim-loading.
 *     -<script type='importmap'>
 *     +<script type='importmap-shim'>
 *     -<script type='module'>
 *     +<script type='module-shim'>
 *
 * The current implementation prefers the shim-based loader regardless of the extent of browser
 * support. This ensures consistent functionality on all browsers, but it also makes the overhead
 * mandatory.
 *
 * For a fuller description of this mechanism, see the neighboring README.
 *
 * @see \Civi\Esm\BrowserLoader
 * @see ./README.md
 *
 * @service esm.loader.shim
 */
class ShimLoader extends BrowserLoader {

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

    $shimUrl = Civi::paths()->getUrl('[civicrm.bower]/es-module-shims/dist/es-module-shims.js');
    $shimHtml = sprintf("<script async src='%s'></script>\n", htmlentities($shimUrl));

    $flags = JSON_UNESCAPED_SLASHES;
    if (Civi::settings()->get('debug_enabled')) {
      $flags |= JSON_PRETTY_PRINT;
    }
    return $shimHtml . sprintf("<script type='importmap-shim'>\n%s\n</script>", json_encode($importMap, $flags));
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
        return sprintf("<script type=\"module-shim\">\n%s\n</script>\n", $snippet['script']);

      case 'scriptUrl':
        return sprintf("<script type=\"module-shim\" src=\"%s\">\n</script>\n", $snippet['scriptUrl']);

      default:
        return parent::renderModule($snippet);
    }
  }

}
