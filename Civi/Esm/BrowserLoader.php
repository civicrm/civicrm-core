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

  use BasicLoaderTrait;

  /**
   * @inheritDoc
   */
  protected function renderImportMap(array $importMap): string {
    $flags = JSON_UNESCAPED_SLASHES;
    if (Civi::settings()->get('debug_enabled')) {
      $flags |= JSON_PRETTY_PRINT;
    }
    return sprintf("<script type='importmap'>\n%s\n</script>", json_encode($importMap, $flags));
  }

  /**
   * @inheritDoc
   */
  protected function renderModuleScript(array $snippet): string {
    return sprintf("<script type=\"module\">\n%s\n</script>\n", $snippet['script']);
  }

  /**
   * @inheritDoc
   */
  protected function renderModuleUrl(array $snippet): string {
    return sprintf("<script type=\"module\" src=\"%s\">\n</script>\n", $snippet['scriptUrl']);
  }

}
