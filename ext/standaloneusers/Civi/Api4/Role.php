<?php
namespace Civi\Api4;

/**
 * Role entity.
 *
 * Provided by the Standalone Users extension.
 *
 * @package Civi\Api4
 */
class Role extends Generic\DAOEntity {

  /**
   * Declare permissions needed to access this entity.
   */
  public static function permissions() {
    return [
      'default' => ['cms:administer users'],
    ];
  }

}
