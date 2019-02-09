<?php

namespace Civi\Api4\Action\Afform;

use Civi\Api4\Generic\Result;

class Revert extends Get {

  protected $select = ['name'];

  public function _run(Result $result) {
    $scanner = \Civi::service('afform_scanner');

    parent::_run($result);

    $files = [\CRM_Afform_AfformScanner::METADATA_FILE, \CRM_Afform_AfformScanner::LAYOUT_FILE];

    foreach ($result as $afform) {
      foreach ($files as $file) {
        $metaPath = $scanner->createSiteLocalPath($afform['name'], $file);
        if (file_exists($metaPath)) {
          if (!@unlink($metaPath)) {
            throw new \API_Exception("Failed to remove afform overrides in $file");
          }
        }
      }
    }

    // We may have changed list of files covered by the cache.
    $scanner->clear();

    // FIXME if `server_route` changes, then flush the menu cache.
    // FIXME if asset-caching is enabled, then flush the asset cache
  }

}
