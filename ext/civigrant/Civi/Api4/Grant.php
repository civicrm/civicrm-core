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
 * Grant entity.
 *
 * Grants are designed to be used by organisations that distribute funds to others.
 *
 * @see https://docs.civicrm.org/user/en/latest/grants/what-is-civigrant/
 *
 * @searchable primary
 * @searchFields contact_id.sort_name,grant_type_id:label
 * @since 5.33
 * @package Civi\Api4
 */
class Grant extends Generic\DAOEntity {

  public static function permissions() {
    return [
      'get' => [
        'access CiviGrant',
      ],
      'delete' => [
        'delete in CiviGrant',
      ],
      'create' => [
        'edit grants',
      ],
      'update' => [
        'edit grants',
      ],
    ];
  }

}
