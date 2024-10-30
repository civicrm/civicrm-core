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
 * MsgTemplate entity.
 *
 * This is a collection of MsgTemplate, for reuse in import, export, etc.
 * @searchable secondary
 * @since 5.26
 * @package Civi\Api4
 */
class MessageTemplate extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\MessageTemplate\Revert
   */
  public static function revert($checkPermissions = TRUE) {
    return (new Action\MessageTemplate\Revert('MessageTemplate', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
