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

use Civi\Api4\Action\Membership\Validate;

/**
 * Membership entity.
 *
 * @searchable primary
 * @searchFields contact_id.sort_name
 * @since 5.42
 * @package Civi\Api4
 */
class Membership extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Civi\Api4\Action\Membership\Validate
   */
  public static function validate($checkPermissions = TRUE) {
    return (new Validate(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
