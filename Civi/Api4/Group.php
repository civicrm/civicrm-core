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

/**
 * Group entity.
 *
 * @see https://docs.civicrm.org/user/en/latest/organising-your-data/groups-and-tags/#groups
 *
 * @searchable secondary
 * @parentField parents
 * @since 5.19
 * @package Civi\Api4
 */
class Group extends Generic\DAOEntity {
  use Generic\Traits\ManagedEntity;
  use Generic\Traits\HierarchicalEntity;

  /**
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\Group\Refresh
   * @throws \CRM_Core_Exception
   */
  public static function refresh(bool $checkPermissions = TRUE): Action\Group\Refresh {
    return (new Action\Group\Refresh(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * Provides more-open permissions that will be further restricted by checkAccess
   *
   * @see \CRM_Contact_BAO_Group::_checkAccess()
   * @return array
   */
  public static function permissions():array {
    $permissions = parent::permissions();

    return [
      // Create permission depends on the group type (see CRM_Contact_BAO_Group::_checkAccess).
      'create' => ['access CiviCRM', ['edit groups', 'access CiviMail', 'create mailings']],
      'refresh' => ['access CiviCRM'],
    ] + $permissions;
  }

}
