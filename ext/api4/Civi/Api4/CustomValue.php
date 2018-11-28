<?php

namespace Civi\Api4;
use Civi\Api4\Generic\AbstractEntity;
use Civi\Api4\Generic\AbstractAction;
use Civi\API\Exception\NotImplementedException;

/**
 * CustomGroup entity.
 *
 * @package Civi\Api4
 */
class CustomValue extends AbstractEntity {

  /**
   * @inheritDoc
   */
  public static function permissions() {
    $entity = 'contact';
    $permissions = \CRM_Core_Permission::getEntityActionPermissions();

    // Merge permissions for this entity with the defaults
    return \CRM_Utils_Array::value($entity, $permissions, []) + $permissions['default'];
  }

}
