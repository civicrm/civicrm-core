<?php
namespace Civi\Api4;

/**
 * OAuthClient entity.
 *
 * Provided by the OAuth Client extension.
 *
 * @package Civi\Api4
 */
class OAuthClient extends Generic\DAOEntity {

  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['manage OAuth client'],
    ];
  }

}
