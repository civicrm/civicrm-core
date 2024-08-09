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
 * LocBlock entity.
 *
 * Links addresses, emails & phones to Events.
 *
 * @searchable secondary
 * @searchFields address_id.street_address,address_id.city,email_id.email
 * @since 5.31
 * @package Civi\Api4
 */
class LocBlock extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\LocBlock\Create
   */
  public static function create($checkPermissions = TRUE) {
    return (new Action\LocBlock\Create('LocBlock', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\LocBlock\Update
   */
  public static function update($checkPermissions = TRUE) {
    return (new Action\LocBlock\Update('LocBlock', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\LocBlock\Save
   */
  public static function save($checkPermissions = TRUE) {
    return (new Action\LocBlock\Save('LocBlock', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
