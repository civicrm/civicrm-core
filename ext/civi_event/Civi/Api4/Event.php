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
 * Event entity.
 *
 * @see https://docs.civicrm.org/user/en/latest/events/what-is-civievent/
 *
 * @searchable primary
 * @since 5.19
 * @package Civi\Api4
 */
class Event extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\Event\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\Event\Get(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
