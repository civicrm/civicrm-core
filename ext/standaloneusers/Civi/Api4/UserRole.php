<?php
namespace Civi\Api4;

/**
 * UserRole entity: links Users with their Role(s).
 *
 * Provided by the Standalone Users extension.
 *
 * @searchable bridge
 * @package Civi\Api4
 */
class UserRole extends Generic\DAOEntity {
  use \Civi\Api4\Generic\Traits\EntityBridge;

  /**
   * Permissions are only used to *authorize* API actions for the
   * current user.
   */
  public static function permissions() {
    return [
      'default'           => ['cms:administer users'],
    ];
  }

}
