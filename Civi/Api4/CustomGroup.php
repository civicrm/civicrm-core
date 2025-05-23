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
namespace Civi\Api4;

use Civi\Api4\Generic\CachedDAOGetAction;

/**
 * CustomGroup entity.
 *
 * @see https://docs.civicrm.org/user/en/latest/organising-your-data/creating-custom-fields/
 * @searchable secondary
 * @orderBy weight
 * @since 5.19
 * @package Civi\Api4
 */
class CustomGroup extends Generic\DAOEntity {
  use Generic\Traits\ManagedEntity;
  use Generic\Traits\SortableEntity;

  /**
   * @param bool $checkPermissions
   * @return Generic\CachedDAOGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new CachedDAOGetAction(__CLASS__, __FUNCTION__, function (CachedDAOGetAction $action) {
      return \CRM_Core_BAO_CustomGroup::getAll([], $action->getCheckPermissions() ? \CRM_Core_Permission::VIEW : NULL);
    }))->setCheckPermissions($checkPermissions);
  }

}
