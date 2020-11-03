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
 * MailSettings entity.
 *
 * @package Civi\Api4
 */
class MailSettings extends Generic\DAOEntity {

  /**
   * Check whether the mail store is accessible.
   *
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\MailSettings\TestConnection
   */
  public static function testConnection($checkPermissions = TRUE) {
    $action = new \Civi\Api4\Action\MailSettings\TestConnection(static::class, __FUNCTION__);
    return $action->setCheckPermissions($checkPermissions);
  }

}
