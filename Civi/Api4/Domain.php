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
 * Domains - multisite instances of CiviCRM.
 *
 * @see https://docs.civicrm.org/sysadmin/en/latest/setup/multisite/
 * @searchable none
 * @since 5.19
 * @package Civi\Api4
 */
class Domain extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\Domain\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\Domain\Get(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
