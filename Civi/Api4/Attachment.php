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
 * "Attachment" is a pseudo-entity which represents a record in civicrm_file combined with a record in civicrm_entity_file as well as the underlying
 * file content.
 *
 * @package Civi\Api4
 */
class Attachment extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return \Civi\Api4\Generic\BasicGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new \Civi\Api4\Action\Attachment\Get(__CLASS__, __FUNCTION__))->setCheckPermissions($checkPermissions);
  }

}
