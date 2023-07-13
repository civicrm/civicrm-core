<?php

namespace Civi\Esm;

use Civi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
 *     +<script async src="...../es-module-shims/dist/es-module-shims.js">
 * 2. Swapping HTML tags to prefer shim-loading.
 *     -<script type='importmap'>
 *     +<script type='importmap-shim'>
 *     -<script type='module'>
 *     +<script type='module-shim'>
 *
 * There are a few different modes with trade-offs for performance, consistency, and compatibility.
 * The methods `createFastShim()` and `createSlowShim()`  have some notes about the trade-offs.
 *
 * For a fuller description of this mechanism, see the neighboring README.
 *
 * @see \Civi\Esm\BrowserLoader
 * @see ./README.md
 */
class ShimLoader extends \Civi\Core\Service\AutoService implements EventSubscriberInterface {

  use BasicLoaderTrait;

  /**
   * List of supported script types.
   *
   * @var array
   *   Ex: ['importmap' => 'importmap-shim', 'module' => 'module-shim'];
   */
  protected $scriptTypes;

  /**
   * Load the importmap with `es-module-shims`. Allow it to auto-detect browser support for ESM.
   *
   * For browsers that support ESM, this should allow faster execution. However, there may be
   * small variations or missing features in specific browser implementations.
   *
   * @service esm.loader.shim-fast
   * @return \Civi\Esm\ShimLoader
   */
  public static function createFashShim() {
    $loader = new static();
    $loader->scriptTypes = ['importmap' => 'importmap', 'module' => 'module'];
    return $loader;
  }

  /**
   * In this flavor, we use `es-module-shims`. We force it to use "shim mode".
   *
   * This should provide the most consistent functionality across browser implementations, but
   * there may be a performance penalty.
   *
   * @service esm.loader.shim-slow
   * @return \Civi\Esm\ShimLoader
   */
  public static function createSlowShim() {
    $loader = new static();
    $loader->scriptTypes = ['importmap' => 'importmap-shim', 'module' => 'module-shim'];
    return $loader;
  }

  /**
   * @inheritDoc
   */
  protected function renderImportMap(array $importMap): string {
    $shimUrl = Civi::paths()->getUrl('[civicrm.bower]/es-module-shims/dist/es-module-shims.js');
    $shimHtml = sprintf("<script async src='%s'></script>\n", htmlentities($shimUrl));

    $flags = JSON_UNESCAPED_SLASHES;
    if (Civi::settings()->get('debug_enabled')) {
      $flags |= JSON_PRETTY_PRINT;
    }
    return $shimHtml . sprintf("<script type='%s'>\n%s\n</script>", htmlentities($this->scriptTypes['importmap']), json_encode($importMap, $flags));
  }

  /**
   * @inheritDoc
   */
  protected function renderModuleScript(array $snippet): string {
    return sprintf("<script type=\"%s\">\n%s\n</script>\n", htmlentities($this->scriptTypes['module']), $snippet['script']);
  }

  /**
   * @inheritDoc
   */
  protected function renderModuleUrl(array $snippet): string {
    return sprintf("<script type=\"%s\" src=\"%s\">\n</script>\n", htmlentities($this->scriptTypes['module']), $snippet['scriptUrl']);
  }

}
