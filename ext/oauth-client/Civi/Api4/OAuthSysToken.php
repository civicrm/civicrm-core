<?php
namespace Civi\Api4;

/**
 * OAuthSysToken entity.
 *
 * Provided by the OAuth Client extension.
 *
 * @package Civi\Api4
 */
class OAuthSysToken extends Generic\DAOEntity {

  /**
   * Load and conditionally refresh a stored token.
   *
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\OAuthSysToken\Refresh
   */
  public static function refresh($checkPermissions = TRUE) {
    $action = new \Civi\Api4\Action\OAuthSysToken\Refresh(static::class, __FUNCTION__);
    return $action->setCheckPermissions($checkPermissions);
  }

  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['manage OAuth client'],
      'delete' => ['manage OAuth client'],
      'get' => ['manage OAuth client'],
      'refresh' => ['manage OAuth client'],
      'create' => ['manage OAuth client secrets'],
      'update' => ['manage OAuth client secrets'],
      // In theory, there might be cases to 'create' or 'update' an OAuthSysToken
      // without access to its secrets, but you should think through the
      // lifecycle/errors/permissions. For now, easier to limit 'create'/update'.
    ];
  }

}
