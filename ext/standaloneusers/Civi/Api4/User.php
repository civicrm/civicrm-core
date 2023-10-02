<?php
namespace Civi\Api4;

use Civi\Api4\Action\User\Create;
use Civi\Api4\Action\User\Save;
use Civi\Api4\Action\User\Update;
use Civi\Api4\Action\User\SendPasswordReset;

/**
 * User entity.
 *
 * Provided by the Standalone Users extension.
 *
 * @package Civi\Api4
 */
class User extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\User\Save
   */
  public static function save($checkPermissions = TRUE): Save {
    return (new Save(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\User\Create
   */
  public static function create($checkPermissions = TRUE): Create {
    return (new Create(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\User\Update
   */
  public static function update($checkPermissions = TRUE): Update {
    return (new Update(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\User\SendPasswordReset
   */
  public static function sendPasswordReset($checkPermissions = TRUE): SendPasswordReset {
    return (new SendPasswordReset(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * Permissions are wide on this but are checked in validateValues.
   */
  public static function permissions() {
    return [
      'default'           => ['access CiviCRM'],
      'passwordReset'     => ['access password resets'],
      'sendPasswordReset' => ['access password resets'],
    ];
  }

}
