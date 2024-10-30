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
 * Contribution entity.
 *
 * @searchable primary
 * @searchFields contact_id.sort_name,total_amount
 * @since 5.19
 * @package Civi\Api4
 */
class Contribution extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\Contribution\Create
   */
  public static function create($checkPermissions = TRUE) {
    return (new Action\Contribution\Create(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Contribution\Save
   */
  public static function save($checkPermissions = TRUE) {
    return (new Action\Contribution\Save(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Contribution\Update
   */
  public static function update($checkPermissions = TRUE) {
    return (new Action\Contribution\Update(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
