<?php
namespace Civi\Api4;

use Civi\Api4\Action\OAuthClient\Create;
use Civi\Api4\Action\OAuthClient\Update;

/**
 * OAuthClient entity.
 *
 * Provided by the OAuth Client extension.
 *
 * @package Civi\Api4
 */
class OAuthClient extends Generic\DAOEntity {

  public static function create($checkPermissions = TRUE) {
    $action = new Create(static::class, __FUNCTION__);
    return $action->setCheckPermissions($checkPermissions);
  }

  public static function update($checkPermissions = TRUE) {
    $action = new Update(static::class, __FUNCTION__);
    return $action->setCheckPermissions($checkPermissions);
  }

  /**
   * Initiate the "Authorization Code" workflow.
   *
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\OAuthClient\AuthorizationCode
   */
  public static function authorizationCode($checkPermissions = TRUE) {
    $action = new \Civi\Api4\Action\OAuthClient\AuthorizationCode(static::class, __FUNCTION__);
    return $action->setCheckPermissions($checkPermissions);
  }

  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['manage OAuth client'],
    ];
  }

}
