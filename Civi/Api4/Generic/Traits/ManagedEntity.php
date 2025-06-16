<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Generic\Traits;

use Civi\Api4\Generic\BasicBatchAction;
use Civi\Api4\Generic\ExportAction;

/**
 * A managed entity includes extra fields and methods to revert from an overridden local to base state.
 *
 * Includes the extra fields `has_base` and `base_module`
 */
trait ManagedEntity {

  /**
   * @param bool $checkPermissions
   * @param ?string $entityName
   *   Usually the entityName matches the calling class, but for custom/dynamic entities
   *   eg. ECK we need to specify a custom entityName.
   *
   * @return \Civi\Api4\Generic\BasicBatchAction
   */
  public static function revert($checkPermissions = TRUE, ?string $entityName = NULL) {
    return (new BasicBatchAction($entityName ?? static::getEntityName(), __FUNCTION__, function($item, BasicBatchAction $action) {
      if (\CRM_Core_ManagedEntities::singleton()->revert($action->getEntityName(), $item['id'])) {
        return $item;
      }
      else {
        throw new \CRM_Core_Exception('Cannot revert ' . $action->getEntityName() . ' with id ' . $item['id']);
      }
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @param ?string $entityName
   *   Usually the entityName matches the calling class, but for custom/dynamic entities
   *   eg. ECK we need to specify a custom entityName.
   *
   * @return \Civi\Api4\Generic\ExportAction
   */
  public static function export($checkPermissions = TRUE, ?string $entityName = NULL) {
    return (new ExportAction($entityName ?? static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
