<?php

namespace Civi\Api4;

use Civi\Api4\Generic\AbstractEntity;
use Civi\Api4\Generic\BasicBatchAction;
use Civi\Api4\Generic\BasicGetAction;
use Civi\Api4\Generic\BasicGetFieldsAction;
use Civi\Api4\Generic\BasicUpdateAction;

/**
 * Class Afform
 * @package Civi\Api4
 */
class Afform extends AbstractEntity {

  /**
   * @return BasicGetAction
   */
  public static function get() {
    return new BasicGetAction('Afform', __FUNCTION__, function(BasicGetAction $action) {
      /** @var \CRM_Afform_AfformScanner $scanner */
      $scanner = \Civi::service('afform_scanner');
      $converter = new \CRM_Afform_ArrayHtml();

      $where = $action->getWhere();
      if (count($where) === 1 && $where[0][0] === 'name' && $where[0][1] == '=') {
        $names = [$where[0][2]];
      }
      else {
        $names = array_keys($scanner->findFilePaths());
      }

      $values = [];
      foreach ($names as $name) {
        $record = $scanner->getMeta($name);
        $layout = $scanner->findFilePath($name, 'aff.html');
        if ($layout) {
          // FIXME check for file existence+substance+validity
          $record['layout'] = $converter->convertHtmlToArray(file_get_contents($layout));
        }
        $values[] = $record;
      }

      return $values;
    });
  }

  /**
   * @return BasicBatchAction
   */
  public static function revert() {
    return new BasicBatchAction('Afform', __FUNCTION__, ['name'], function($item, BasicBatchAction $action) {
      $scanner = \Civi::service('afform_scanner');
      $files = [
        \CRM_Afform_AfformScanner::METADATA_FILE,
        \CRM_Afform_AfformScanner::LAYOUT_FILE
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
   * @return BasicUpdateAction
   */
  public static function update() {
    $save = function ($item, BasicUpdateAction $action) {
      /** @var \CRM_Afform_AfformScanner $scanner */
      $scanner = \Civi::service('afform_scanner');
      $converter = new \CRM_Afform_ArrayHtml();

      if (empty($item['name']) || !preg_match('/^[a-zA-Z][a-zA-Z0-9\-]*$/', $item['name'])) {
        throw new \API_Exception("Afform.create: name is a mandatory field. It should use alphanumerics and dashes.");
      }
      $name = $item['name'];

      // FIXME validate all field data.
      $updates = _afform_fields_filter($item);

      // Create or update aff.html.
      if (isset($updates['layout'])) {
        $layoutPath = $scanner->createSiteLocalPath($name, 'aff.html');
        \ CRM_Utils_File::createDir(dirname($layoutPath));
        file_put_contents($layoutPath, $converter->convertArrayToHtml($updates['layout']));
        // FIXME check for writability then success. Report errors.
      }

      // Create or update *.aff.json.
      $orig = \Civi\Api4\Afform::get()
        ->setCheckPermissions($action->getCheckPermissions())
        ->addWhere('name', '=', $name)
        ->execute();

      if (isset($orig[0])) {
        $meta = _afform_fields_filter(array_merge($orig[0], $updates));
      }
      else {
        $meta = $updates;
      }
      unset($meta['layout']);
      unset($meta['name']);
      if (!empty($meta)) {
        $metaPath = $scanner->createSiteLocalPath($name, \CRM_Afform_AfformScanner::METADATA_FILE);
        // printf("[%s] Update meta %s: %s\n", $name, $metaPath, print_R(['updates'=>$updates, 'meta'=>$meta], 1));
        \CRM_Utils_File::createDir(dirname($metaPath));
        file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT));
        // FIXME check for writability then success. Report errors.
      }

      // We may have changed list of files covered by the cache.
      $scanner->clear();

      // FIXME if `server_route` changes, then flush the menu cache.
      // FIXME if asset-caching is enabled, then flush the asset cache.

      return $updates;
    };
    return new BasicUpdateAction('Afform', __FUNCTION__, $save, 'name');
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
