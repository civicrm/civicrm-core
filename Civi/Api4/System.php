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
 * A collection of system maintenance/diagnostic utilities.
 *
 * @package Civi\Api4
 */
class System extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\System\Flush
   */
  public static function flush($checkPermissions = TRUE) {
    return (new Action\System\Flush(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\System\Check
   */
  public static function check($checkPermissions = TRUE) {
    return (new Action\System\Check(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction(__CLASS__, __FUNCTION__, function() {
      return [];
    }))->setCheckPermissions($checkPermissions);
  }

}
