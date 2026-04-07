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
 * File entity.
 *
 * @searchable secondary
 * @since 5.41
 * @package Civi\Api4
 */
class File extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\File\Create
   */
  public static function create($checkPermissions = TRUE) {
    return (new Action\File\Create(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\File\Save
   */
  public static function save($checkPermissions = TRUE) {
    return (new Action\File\Save(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\File\Update
   */
  public static function update($checkPermissions = TRUE) {
    return (new Action\File\Update(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\File\Delete
   */
  public static function delete($checkPermissions = TRUE) {
    return (new Action\File\Delete(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
