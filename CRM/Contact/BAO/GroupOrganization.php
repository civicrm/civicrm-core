<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
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
   * @return CRM_Contact_DAO_GroupOrganization
   * @access public
   * @static
   */
  static function add(&$params) {
    $formattedValues = array();
    self::formatValues($params, $formattedValues);
    $dataExists = self::dataExists($formattedValues);
    if (!$dataExists) {
      return NULL;
    }
    $groupOrganization = new CRM_Contact_DAO_GroupOrganization();
    $groupOrganization->copyValues($formattedValues);
    // we have ensured we have group_id & organization_id so we can do a find knowing that
    // this can only find a matching record
    $groupOrganization->find(TRUE);
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
    if (!empty($params['group_organization'])) {
      $formatedValues['id'] = $params['group_organization'];
    }

    if (!empty($params['group_id'])) {
      $formatedValues['group_id'] = $params['group_id'];
    }

    if (!empty($params['organization_id'])) {
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
    if (!empty($params['organization_id']) && !empty($params['group_id'])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param $groupID
   * @param $defaults
   */
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
   * @param $contactID
   *
   * @internal param int $contactId
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
   * @return mixed|null $results   no of deleted group organization on success, false otherwise@access public
   */
  static function deleteGroupOrganization($groupOrganizationID) {
    $results = NULL;
    $groupOrganization = new CRM_Contact_DAO_GroupOrganization();
    $groupOrganization->id = $groupOrganizationID;

    $results = $groupOrganization->delete();

    return $results;
  }
}

