<?php

namespace Civi\Api4;

use Civi\Api4\Generic\AbstractEntity;
use Civi\Api4\Generic\BasicBatchAction;
use Civi\Api4\Generic\BasicGetFieldsAction;

/**
 * Class Afform
 * @package Civi\Api4
 */
class Afform extends AbstractEntity {

  /**
   * @return \Civi\Api4\Action\Afform\Get
   */
  public static function get() {
    return new \Civi\Api4\Action\Afform\Get('Afform', __FUNCTION__);
  }

  /**
   * @return \Civi\Api4\Generic\BasicBatchAction
   */
  public static function revert() {
    return new BasicBatchAction('Afform', __FUNCTION__, ['name'], function($item, BasicBatchAction $action) {
      $scanner = \Civi::service('afform_scanner');
      $files = [
        \CRM_Afform_AfformScanner::METADATA_FILE,
        \CRM_Afform_AfformScanner::LAYOUT_FILE,
      ];

      foreach ($files as $file) {
        $metaPath = $scanner->createSiteLocalPath($item['name'], $file);
        if (file_exists($metaPath)) {
          if (!@unlink($metaPath)) {
            throw new \API_Exception("Failed to remove afform overrides in $file");
          }
        }
      }

      // We may have changed list of files covered by the cache.
      $scanner->clear();

      // FIXME if `server_route` changes, then flush the menu cache.
      // FIXME if asset-caching is enabled, then flush the asset cache

      return $item;
    });
  }

  /**
   * @return \Civi\Api4\Action\Afform\Update
   */
  public static function update() {
    return new \Civi\Api4\Action\Afform\Update('Afform', __FUNCTION__, 'name');
  }

  public static function getFields() {
    return new BasicGetFieldsAction('Afform', __FUNCTION__, function() {
      return [
        [
          'name' => 'name',
        ],
        [
          'name' => 'requires',
        ],
        [
          'name' => 'title',
        ],
        [
          'name' => 'description',
        ],
        [
          'name' => 'is_public',
          'data_type' => 'Boolean',
        ],
        [
          'name' => 'server_route',
        ],
        [
          'name' => 'layout',
        ],
      ];
    });
  }

  /**
   * @return array
   */
  public static function permissions() {
    return [
      "meta" => ["access CiviCRM"],
      "default" => ["administer CiviCRM"],
    ];
  }

}
