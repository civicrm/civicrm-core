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

use Civi\Api4\Action\Mailing\UpdateAction;
use Civi\Api4\Action\Mailing\CreateAction;
use Civi\Api4\Action\Mailing\SaveAction;

/**
 * Mailing.
 *
 * Mailing entities store the contents and settings for bulk mails.
 *
 * @searchable secondary
 * @see https://docs.civicrm.org/user/en/latest/email/what-is-civimail/
 * @since 5.48
 * @package Civi\Api4
 */
class Mailing extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\Mailing\CreateAction
   */
  public static function create($checkPermissions = TRUE): CreateAction {
    return (new CreateAction(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\Mailing\UpdateAction
   */
  public static function update($checkPermissions = TRUE) {
    return (new UpdateAction(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\Mailing\SaveAction
   */
  public static function save($checkPermissions = TRUE): SaveAction {
    return (new SaveAction(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
