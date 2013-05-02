<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Contact_BAO_GroupOrganization extends CRM_Contact_DAO_GroupOrganization {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * takes an associative array and creates a groupOrganization object
   *
   * @param array  $params         (reference ) an assoc array of name/value pairs
   *
   * @return void
   * @access public
   * @static
   */
  static function add(&$params) {
    $formatedValues = array();
    self::formatValues($params, $formatedValues);
    $dataExists = self::dataExists($formatedValues);
    if (!$dataExists) {
      return NULL;
    }
    $groupOrganization = new CRM_Contact_DAO_GroupOrganization();
    $groupOrganization->copyValues($formatedValues);
    $groupOrganization->save();
    return $groupOrganization;
  }

  /**
   * Format the params
   *
   * @param array  $params         (reference ) an assoc array of name/value pairs
   * @param array  $formatedValues (reference ) an assoc array of name/value pairs
   *
   * @return void
   * @access public
   * @static
   */
  static function formatValues(&$params, &$formatedValues) {
    if (CRM_Utils_Array::value('group_organization', $params)) {
      $formatedValues['id'] = $params['group_organization'];
    }

    if (CRM_Utils_Array::value('group_id', $params)) {
      $formatedValues['group_id'] = $params['group_id'];
    }

    if (CRM_Utils_Array::value('organization_id', $params)) {
      $formatedValues['organization_id'] = $params['organization_id'];
    }
  }

  /**
   * Check if there is data to create the object
   *
   * @param array  $params  (reference ) an assoc array of name/value pairs
   *
   * @return boolean
   * @access public
   * @static
   */
  static function dataExists($params) {
    // return if no data present
    if (CRM_Utils_Array::value('organization_id', $params) &&
      CRM_Utils_Array::value('group_id', $params)
    ) {
      return TRUE;
    }
    return FALSE;
  }

  static function retrieve($groupID, &$defaults) {
    $dao = new CRM_Contact_DAO_GroupOrganization();
    $dao->group_id = $groupID;
    if ($dao->find(TRUE)) {
      $defaults['group_organization'] = $dao->id;
      $defaults['organization_id'] = $dao->organization_id;
    }
  }

  /**
   * Method to check group organization relationship exist
   *
   * @param  int  $contactId
   *
   * @return boolean
   * @access public
   * @static
   */
  static function hasGroupAssociated($contactID) {
    $orgID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_GroupOrganization',
      $contactID, 'group_id', 'organization_id'
    );
    if ($orgID) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Function to delete Group Organization
   *
   * @param int $groupOrganizationID group organization id that needs to be deleted
   *
   * @return $results   no of deleted group organization on success, false otherwise
   * @access public
   */
  static function deleteGroupOrganization($groupOrganizationID) {
    $results = NULL;
    $groupOrganization = new CRM_Contact_DAO_GroupOrganization();
    $groupOrganization->id = $groupOrganizationID;

    $results = $groupOrganization->delete();

    return $results;
  }
}

