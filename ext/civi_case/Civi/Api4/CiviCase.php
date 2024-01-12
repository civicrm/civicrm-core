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
 * Case entity.
 *
 * Note that the class for this entity is named "CiviCase" because "Case" is a keyword reserved by php.
 *
 * @see https://docs.civicrm.org/user/en/latest/case-management/what-is-civicase/
 * @searchable primary
 * @since 5.37
 * @package Civi\Api4
 */
class CiviCase extends Generic\DAOEntity {

  /**
   * Explicitly declare entity name because it doesn't match the name of this class
   * (due to the php reserved keyword issue)
   *
   * @return string
   */
  public static function getEntityName(): string {
    return 'Case';
  }

  /**
   * @param bool $checkPermissions
   * @return Action\CiviCase\Create
   */
  public static function create($checkPermissions = TRUE) {
    return (new Action\CiviCase\Create('Case', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\CiviCase\Save
   */
  public static function save($checkPermissions = TRUE) {
    return (new Action\CiviCase\Save('Case', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\CiviCase\Update
   */
  public static function update($checkPermissions = TRUE) {
    return (new Action\CiviCase\Update('Case', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
