<?php

namespace Civi\Api4\Action\Afform;

use Civi\Afform\Utils;
use Civi\Api4\Generic\Result;
use CRM_Afform_ExtensionUtil as E;

/**
 * @inheritDoc
 * @package Civi\Api4\Action\Afform
 */
class Revert extends \Civi\Api4\Generic\BasicBatchAction {

  /**
   * @var bool
   */
  private $flushManaged = FALSE;

  /**
   * @var bool
   */
  private $flushMenu = FALSE;

  /**
   * Revert every record, and flush caches at the end.
   *
   * @inheritDoc
   */
  protected function processBatch(Result $result, array $items) {
    parent::processBatch($result, $items);

    // We may have changed list of files covered by the cache.
    _afform_clear();

    if ($this->flushManaged) {
      \CRM_Core_ManagedEntities::singleton()->reconcile(E::LONG_NAME);
    }
    if ($this->flushMenu) {
      \CRM_Core_Menu::store();
    }
  }

  /**
   * Revert (delete) a record.
   *
   * @inheritDoc
   */
  protected function doTask($item) {
    /** @var \CRM_Afform_AfformScanner $scanner */
    $scanner = \Civi::service('afform_scanner');
    $files = [
      \CRM_Afform_AfformScanner::METADATA_JSON,
      \CRM_Afform_AfformScanner::LAYOUT_FILE,
    ];

    foreach ($files as $file) {
      $metaPath = $scanner->createSiteLocalPath($item['name'], $file);
      if (file_exists($metaPath)) {
        if (!@unlink($metaPath)) {
          throw new \CRM_Core_Exception("Failed to remove afform overrides in $file");
        }
      }
    }

    $original = (array) $scanner->getMeta($item['name']);

    // If the dashlet setting changed, managed entities must be reconciled
    if (Utils::shouldReconcileManaged($item, $original)) {
      $this->flushManaged = TRUE;
    }

    // If the server_route changed, reset menu cache
    if (Utils::shouldClearMenuCache($item, $original)) {
      $this->flushMenu = TRUE;
    }

    return $item;
  }

  /**
   * Adds extra return params so caches can be conditionally flushed.
   *
   * @return string[]
   */
  protected function getSelect() {
    return ['name', 'title', 'placement', 'server_route', 'created_id'];
  }

}
