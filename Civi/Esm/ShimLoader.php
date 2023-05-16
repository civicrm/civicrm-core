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
class ShimLoader extends \Civi\Core\Service\AutoService implements EventSubscriberInterface {

  use BasicLoaderTrait;

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
    return $shimHtml . sprintf("<script type='importmap-shim'>\n%s\n</script>", json_encode($importMap, $flags));
  }

  /**
   * @inheritDoc
   */
  protected function renderModuleScript(array $snippet): string {
    return sprintf("<script type=\"module-shim\">\n%s\n</script>\n", $snippet['script']);
  }

  /**
   * @inheritDoc
   */
  protected function renderModuleUrl(array $snippet): string {
    return sprintf("<script type=\"module-shim\" src=\"%s\">\n</script>\n", $snippet['scriptUrl']);
  }

}
