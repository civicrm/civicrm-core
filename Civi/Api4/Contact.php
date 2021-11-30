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
 * Contacts - Individuals, Organizations, Households.
 *
 * This is the central entity in the CiviCRM database, and links to
 * many other entities (Email, Phone, Participant, etc.).
 *
 * Creating a new contact requires at minimum a name or email address.
 *
 * @see https://docs.civicrm.org/user/en/latest/organising-your-data/contacts/
 * @searchable primary
 * @orderBy sort_name
 * @since 5.19
 * @package Civi\Api4
 */
class Contact extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\Contact\Delete
   */
  public static function delete($checkPermissions = TRUE) {
    return (new Action\Contact\Delete(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Contact\GetChecksum
   */
  public static function getChecksum($checkPermissions = TRUE) {
    return (new Action\Contact\GetChecksum(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Contact\ValidateChecksum
   */
  public static function validateChecksum($checkPermissions = TRUE) {
    return (new Action\Contact\ValidateChecksum(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
