<?php
namespace Civi\Api4;

use Civi\Api4\Action\User\Create;
use Civi\Api4\Action\User\Login;
use Civi\Api4\Action\User\Save;
use Civi\Api4\Action\User\SendPasswordReset;
use Civi\Api4\Action\User\Update;

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
   * @return \Civi\Api4\Action\User\Login
   */
  public static function login($checkPermissions = TRUE): Login {
    return (new Login(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

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
   * Permissions are only used to *authorize* API actions for the
   * current user. This authorization knows nothing of the parameters,
   * values etc. passed into the API call, so it's authorization or not
   * cannot depend on the values. So you do not implement "update own
   * user, but not others" here.
   *
   * For this reason, the default permission is just 'access CiviCRM'
   * which is very (too) permissive, but each API method implemented
   * further restricts its use, e.g. write methods typically use
   * _checkAccess()
   *
   * Note that 'access password resets' permission is defined in
   * this standaloneusers ext. and is intended to be public.
   *
   * We have to provide a permission for 'save' because it won't use
   * 'default'; it will use the same as 'create' and we want users
   * to be able to use save (on their own record).
   */
  public static function permissions() {
    return [
      'default'           => ['access CiviCRM'],
      'save'              => ['access CiviCRM'],
      'create'            => ['cms:administer users'],
      'delete'            => ['cms:administer users'],
      'passwordReset'     => ['access password resets'],
      'sendPasswordReset' => ['access password resets'],
      'login'             => ['access password resets'],
    ];
  }

}
