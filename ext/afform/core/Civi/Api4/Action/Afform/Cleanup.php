<?php

namespace Civi\Api4\Action\Afform;

use Civi\Afform\Utils;
use Civi\Api4\Generic\Result;
use CRM_Afform_ExtensionUtil as E;

/**
 * @inheritDoc
 * @package Civi\Api4\Action\Afform
 */
class Cleanup extends \Civi\Api4\Generic\BasicBatchAction {

  /**
   * Revert every record, and flush caches at the end.
   *
   * @inheritDoc
   */
  protected function processBatch(Result $result, array $items) {
    parent::processBatch($result, $items);

    // We may have changed list of files covered by the cache.
    _afform_clear();
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

    $localAfform = $scanner->getMeta($item['name'], TRUE, 'local');
    $managedAfform = $scanner->getMeta($item['name'], TRUE, 'managed');

    // @todo: Check this: We should return the right things
    // to show, override, no change, change etc.
    $item['current'] = 'local';
    $item['is_modified'] = FALSE;
    if (empty($localAfform)) {
      $item['current'] = 'managed';
      return $item;
    }
    if (empty($managedAfform)) {
      return $item;
    }

    // Pass through filter to add field defaults (json saves all fields, PHP only saves changed ones)
    $localAfform2 = _afform_fields_filter($localAfform, TRUE);
    $managedAfform2 = _afform_fields_filter($managedAfform, TRUE);

    $fieldsToIgnore = [
      'modified_date',
      'created_id',
    ];
    foreach ($fieldsToIgnore as $fieldKey) {
      unset($localAfform2[$fieldKey]);
      unset($managedAfform2[$fieldKey]);
    }
    $emptyArrayOrNullFields = [
      'placement',
      'placement_filters',
    ];
    foreach ($emptyArrayOrNullFields as $fieldKey) {
      if (empty($localAfform2[$fieldKey])) {
        $localAfform2[$fieldKey] = NULL;
      }
      if (empty($managedAfform2[$fieldKey])) {
        $managedAfform2[$fieldKey] = NULL;
      }
    }
    $differences = array_diff($localAfform2, $managedAfform2);
    // @todo: Do a diff on xAfform['layout']
    // @todo: Why is local returning a lot more keys in defn?

    exit;
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
