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
 * Pledge entity.
 *
 * @see https://docs.civicrm.org/user/en/latest/pledges/what-is-civipledge/
 * @searchable primary
 * @searchFields contact_id.display_name,amount
 * @since 5.35
 * @package Civi\Api4
 */
class Pledge extends Generic\DAOEntity {

  /**
   * Cancel pledge and any pending payments.
   *
   * @param $checkPermissions
   * @return Generic\BasicBatchAction
   */
  public static function cancel($checkPermissions = TRUE) {
    return (new Generic\BasicBatchAction(static::getEntityName(), __FUNCTION__, fn($item) => \CRM_Pledge_BAO_Pledge::cancel($item['id'])))
      ->setCheckPermissions($checkPermissions);
  }

  public static function permissions(): array {
    $permissions = parent::permissions();
    // Use 'update' permission for 'cancel' action.
    $permissions['cancel'] = $permissions['update'];
    return $permissions;
  }

}
