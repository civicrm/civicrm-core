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
 * StateProvince entity.
 *
 * @searchable secondary
 * @since 5.22
 * @package Civi\Api4
 */
class StateProvince extends Generic\DAOEntity {

  public static function permissions(): array {
    $permissions = parent::permissions();

    // there's nothing secret about the list of StateProvinces
    $permissions['get'] = ['*always allow*'];

    return $permissions;
  }

}
