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
 * Membership entity.
 *
 * @searchable primary
 * @searchFields contact_id.sort_name
 * @since 5.42
 * @package Civi\Api4
 */
class Membership extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\Contribution\Create
   */
  public static function create($checkPermissions = TRUE) {
    return (new Action\Membership\Create(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Membership\Save
   */
  public static function save($checkPermissions = TRUE) {
    return (new Action\Membership\Save(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Membership\Update
   */
  public static function update($checkPermissions = TRUE) {
    return (new Action\Membership\Update(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
