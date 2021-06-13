<?php
namespace Civi\Api4;

/**
 * Attach supplemental translations to strings stored in the database.
 *
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
