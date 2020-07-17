<?php

namespace Civi\Api4;

use Civi\Api4\Generic\BasicBatchAction;

/**
 * User-configurable forms.
 *
 * Afform stands for *The Affable Administrative Angular Form Framework*.
 *
 * This API provides actions for
 *   1. **_Managing_ forms:**
 *      The `create`, `get`, `save`, `update`, & `revert` actions read/write form html & json files.
 *   2. **_Using_ forms:**
 *      The `prefill` and `submit` actions are used for preparing forms and processing submissions.
 *
 * @see https://lab.civicrm.org/extensions/afform
 * @package Civi\Api4
 */
class Afform extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\Afform\Get('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\Create
   */
  public static function create($checkPermissions = TRUE) {
    return (new Action\Afform\Create('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\Update
   */
  public static function update($checkPermissions = TRUE) {
    return (new Action\Afform\Update('Afform', __FUNCTION__, 'name'))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\Save
   */
  public static function save($checkPermissions = TRUE) {
    return (new Action\Afform\Save('Afform', __FUNCTION__, 'name'))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\Prefill
   */
  public static function prefill($checkPermissions = TRUE) {
    return (new Action\Afform\Prefill('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\Submit
   */
  public static function submit($checkPermissions = TRUE) {
    return (new Action\Afform\Submit('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicBatchAction
   */
  public static function revert($checkPermissions = TRUE) {
    return (new BasicBatchAction('Afform', __FUNCTION__, ['name'], function($item, BasicBatchAction $action) {
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
      _afform_clear();

      // FIXME if `server_route` changes, then flush the menu cache.
      // FIXME if asset-caching is enabled, then flush the asset cache

      return $item;
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction('Afform', __FUNCTION__, function($self) {
      $fields = [
        [
          'name' => 'name',
        ],
        [
          'name' => 'requires',
        ],
        [
          'name' => 'block',
        ],
        [
          'name' => 'join',
        ],
        [
          'name' => 'title',
          'required' => $self->getAction() === 'create',
        ],
        [
          'name' => 'description',
        ],
        [
          'name' => 'is_public',
          'data_type' => 'Boolean',
        ],
        [
          'name' => 'repeat',
          'data_type' => 'Mixed',
        ],
        [
          'name' => 'server_route',
        ],
        [
          'name' => 'permission',
        ],
        [
          'name' => 'layout',
        ],
      ];

      if ($self->getAction() === 'get') {
        $fields[] = [
          'name' => 'module_name',
        ];
        $fields[] = [
          'name' => 'directive_name',
        ];
        $fields[] = [
          'name' => 'has_local',
        ];
        $fields[] = [
          'name' => 'has_base',
        ];
      }

      return $fields;
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @return array
   */
  public static function permissions() {
    return [
      "meta" => ["access CiviCRM"],
      "default" => ["administer CiviCRM"],
      'prefill' => [],
      'submit' => [],
    ];
  }

}
