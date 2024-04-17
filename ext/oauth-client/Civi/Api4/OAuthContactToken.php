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

  public static function permissions(): array {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['manage my OAuth contact tokens'],
    ];
  }

}
