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

use Civi\Api4\Action\Participant\GetDuplicates;

/**
 * Participant entity, stores the participation record of a contact in an event.
 *
 * @searchable primary
 * @searchFields contact_id.sort_name,event_id.title
 * @since 5.19
 * @package Civi\Api4
 */
class Participant extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   *
   * @return \Civi\Api4\Action\Participant\GetDuplicates
   */
  public static function getDuplicates(bool $checkPermissions = TRUE): GetDuplicates {
    return (new GetDuplicates(self::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
