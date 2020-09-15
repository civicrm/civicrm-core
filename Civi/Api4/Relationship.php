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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace Civi\Api4;

/**
 * Relationship entity.
 *
 * @see https://docs.civicrm.org/user/en/latest/organising-your-data/relationships/
 *
 * @package Civi\Api4
 */
class Relationship extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\Relationship\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\Relationship\Get(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
