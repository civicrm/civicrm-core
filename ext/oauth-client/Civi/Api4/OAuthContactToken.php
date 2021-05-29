<?php


namespace Civi\Api4;

/**
 * OAuthContactToken entity.
 *
 * Provided by the OAuth Client extension.
 *
 * @package Civi\Api4
 */
class OAuthContactToken extends Generic\DAOEntity {

  public static function create($checkPermissions = TRUE) {
    $action = new Action\OAuthContactToken\Create(static::class, __FUNCTION__);
    return $action->setCheckPermissions($checkPermissions);
  }

  public static function get($checkPermissions = TRUE) {
    $action = new Action\OAuthContactToken\Get(static::class, __FUNCTION__);
    return $action->setCheckPermissions($checkPermissions);
  }

  public static function update($checkPermissions = TRUE) {
    $action = new Action\OAuthContactToken\Update(static::class, __FUNCTION__);
    return $action->setCheckPermissions($checkPermissions);
  }

  public static function delete($checkPermissions = TRUE) {
    $action = new Action\OAuthContactToken\Delete(static::class, __FUNCTION__);
    return $action->setCheckPermissions($checkPermissions);
  }

  public static function permissions(): array {
    return [
      'meta' => ['access CiviCRM'],
      'default' => [
        [
          'manage my OAuth contact tokens',
          'manage all OAuth contact tokens',
        ],
      ],
    ];
  }

}
