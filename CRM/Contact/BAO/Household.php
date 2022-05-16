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
class CRM_Contact_BAO_Household extends CRM_Contact_DAO_Contact {

  /**
   * Update the household with primary contact id.
   *
   * @param int $primaryContactId
   *   Null if deleting primary contact.
   * @param int $contactId
   *   Contact id.
   *
   * @return Object
   *   DAO object on success
   */
  public static function updatePrimaryContact($primaryContactId, $contactId) {
    $queryString = "UPDATE civicrm_contact
                           SET primary_contact_id = ";

    $params = [];
    if ($primaryContactId) {
      $queryString .= '%1';
      $params[1] = [$primaryContactId, 'Integer'];
    }
    else {
      $queryString .= "null";
    }

    $queryString .= " WHERE id = %2";
    $params[2] = [$contactId, 'Integer'];

    return CRM_Core_DAO::executeQuery($queryString, $params);
  }

}
