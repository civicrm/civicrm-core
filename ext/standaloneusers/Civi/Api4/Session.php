<?php
namespace Civi\Api4;

/**
 * Session entity.
 *
 * Provided by the Standalone Users extension.
 *
 * @package Civi\Api4
 */
class Session extends Generic\DAOEntity {

  public static function permissions() {
    return [
      'default' => ['cms:administer users'],
    ];
  }

}
