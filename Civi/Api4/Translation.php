<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */
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
