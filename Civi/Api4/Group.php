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
 * @since 5.19
 * @package Civi\Api4
 */
class Group extends Generic\DAOEntity {
  use Generic\Traits\ManagedEntity;

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
    ] + $permissions;
  }

}
