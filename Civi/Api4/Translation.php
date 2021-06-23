<?php
namespace Civi\Api4;

/**
 * Attach supplemental translations to strings stored in the database.
 *
 * @since 5.40
 * @package Civi\Api4
 */
class Translation extends Generic\DAOEntity {

  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['translate CiviCRM'],
    ];
  }

}
