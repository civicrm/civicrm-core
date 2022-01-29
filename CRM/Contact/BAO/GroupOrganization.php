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
class CRM_Contact_BAO_GroupOrganization extends CRM_Contact_DAO_GroupOrganization {

  /**
   * Takes an associative array and creates a groupOrganization object.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Contact_DAO_GroupOrganization
   */
  public static function add(&$params) {
    if (!empty($params['group_organization'])) {
      $params['id'] = $params['group_organization'];
    }
    $dataExists = self::dataExists($params);
    if (!$dataExists && empty($params['id'])) {
      return NULL;
    }
    $groupOrganization = new CRM_Contact_DAO_GroupOrganization();
    $groupOrganization->copyValues($params);
    if (!isset($params['id'])) {
      // we have ensured we have group_id & organization_id so we can do a find knowing that
      // this can only find a matching record
      $groupOrganization->find(TRUE);
    }
    $groupOrganization->save();
    return $groupOrganization;
  }

  /**
   * Check if there is data to create the object.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return bool
   */
  public static function dataExists($params) {
    // return if no data present
    if (!empty($params['organization_id']) && !empty($params['group_id'])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param int $groupID
   * @param array $defaults
   */
  public static function retrieve($groupID, &$defaults) {
    $dao = new CRM_Contact_DAO_GroupOrganization();
    $dao->group_id = $groupID;
    if ($dao->find(TRUE)) {
      $defaults['group_organization'] = $dao->id;
      $defaults['organization_id'] = $dao->organization_id;
    }
  }

  /**
   * Method to check group organization relationship exist.
   *
   * @param int $contactID
   *
   * @return bool
   */
  public static function hasGroupAssociated($contactID) {
    $orgID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_GroupOrganization',
      $contactID, 'group_id', 'organization_id'
    );
    if ($orgID) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Delete Group Organization.
   *
   * @param int $groupOrganizationID
   *   Group organization id that needs to be deleted.
   *
   * @return int|null
   *   no of deleted group organization on success, false otherwise
   */
  public static function deleteGroupOrganization($groupOrganizationID) {
    $results = NULL;
    $groupOrganization = new CRM_Contact_DAO_GroupOrganization();
    $groupOrganization->id = $groupOrganizationID;

    $results = $groupOrganization->delete();

    return $results;
  }

}
