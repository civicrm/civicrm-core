<?php

namespace Civi\Api4;

use Civi\Api4\Action\OAuthClient\Create;
use Civi\Api4\Action\OAuthClient\Update;
use Civi\Api4\Generic\Traits\ManagedEntity;

/**
 * OAuthClient entity.
 *
 * Provided by the OAuth Client extension.
 *
 * @package Civi\Api4
 */
class OAuthClient extends Generic\DAOEntity {

  use ManagedEntity;

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
   *
   * @return \Civi\Api4\Action\OAuthClient\AuthorizationCode
   */
  public static function authorizationCode($checkPermissions = TRUE): Action\OAuthClient\AuthorizationCode {
    $action = new \Civi\Api4\Action\OAuthClient\AuthorizationCode(static::class, __FUNCTION__);
    return $action->setCheckPermissions($checkPermissions);
  }

  /**
   * Request access with client credentials
   *
   * @param bool $checkPermissions
   *
   * @return \Civi\Api4\Action\OAuthClient\ClientCredential
   */
  public static function clientCredential($checkPermissions = TRUE): Action\OAuthClient\ClientCredential {
    $action = new \Civi\Api4\Action\OAuthClient\ClientCredential(static::class, __FUNCTION__);
    return $action->setCheckPermissions($checkPermissions);
  }

  /**
   * Request access with a username and password.
   *
   * @param bool $checkPermissions
   *
   * @return \Civi\Api4\Action\OAuthClient\UserPassword
   */
  public static function userPassword($checkPermissions = TRUE): Action\OAuthClient\UserPassword {
    $action = new \Civi\Api4\Action\OAuthClient\UserPassword(static::class, __FUNCTION__);
    return $action->setCheckPermissions($checkPermissions);
  }

  public static function permissions(): array {
    return [
      // In general, we aim for permissions on OAuthClient actions to be based
      // on OAuthProvider metadata. However, given the systemic flexibility
      // (third-party add-on actions; upstream generic actions; etc), it's hard
      // to predict what happens if you do it generically. So instead, we follow
      // this pattern:
      //
      // - Pick an action (`Civi\Api4\OAuthClient::foo()`).
      // - Update the action logic to check permissions (`_oauth_client_providers_by_perm("foo")`)
      // - In here, set the action to ALWAYS_ALLOW_PERMISSION.
      //
      // And if no one has done that yet... then we fallback to the safer default:
      'default' => ['manage OAuth client'],

      // addSelectWhereClause() enforces limits on visibility...
      'get' => [\CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION],

      // Probably need this for everyone who can 'get' records...
      'meta' => [\CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION],

      // Access controlled via AbstractGrantAction::validate()
      'authorizationCode' => [\CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION],
      'userPassword' => [\CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION],
      'clientCredential' => [\CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION],
    ];
  }

}
