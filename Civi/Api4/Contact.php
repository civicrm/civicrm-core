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
 * $Id$
 *
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
 * @package Civi\Api4
 */
class Contact extends Generic\DAOEntity {

  /**
   * @return \Civi\Api4\Action\Contact\GetFields|Generic\DAOGetFieldsAction
   */
  public static function getFields() {
    return new Action\Contact\GetFields(__CLASS__, __FUNCTION__);
  }

  /**
   * @return \Civi\Api4\Action\Contact\GetChecksum
   */
  public static function getChecksum() {
    return new Action\Contact\GetChecksum(__CLASS__, __FUNCTION__);
  }

  /**
   * @return \Civi\Api4\Action\Contact\ValidateChecksum
   */
  public static function validateChecksum() {
    return new Action\Contact\ValidateChecksum(__CLASS__, __FUNCTION__);
  }

}
