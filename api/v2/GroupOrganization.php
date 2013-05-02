<?php
// $Id$

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
 * File for the CiviCRM APIv2 group contact functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Group
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: GroupContact.php 21624 2009-06-04 22:02:55Z mover $
 *
 */

/**
 * Include utility functions
 */
require_once 'api/v2/utils.php';

/**
 * This API will give list of the groups for particular contact
 * Particualr status can be sent in params array
 * If no status mentioned in params, by default 'added' will be used
 * to fetch the records
 *
 * @param  array $params  name value pair of contact information
 *
 * @return  array  list of groups, given contact subsribed to
 */
function civicrm_group_organization_get(&$params) {
  _civicrm_initialize();

  if (!is_array($params)) {
    return civicrm_create_error(ts('Input parameter is not an array'));
  }

  if (empty($params)) {
    return civicrm_create_error('No input parameter present');
  }

  if (!array_key_exists('organization_id', $params) &&
    !array_key_exists('group_id', $params)
  ) {
    return civicrm_create_error(ts('at least one of organization_id or group_id is a required field'));
  }

  require_once 'CRM/Contact/DAO/GroupOrganization.php';
  $dao = new CRM_Contact_DAO_GroupOrganization();
  if (array_key_exists('organization_id', $params)) {
    $dao->organization_id = $params['organization_id'];
  }
  if (array_key_exists('group_id', $params)) {
    $dao->group_id = $params['group_id'];
  }
  $dao->find();
  $values = array();
  _civicrm_object_to_array($dao, $values);
  return civicrm_create_success($values);
}

/**
 *
 * @param <type> $params
 *
 * @return <type>
 */
function civicrm_group_organization_create(&$params) {

  if (!is_array($params)) {
    return civicrm_create_error(ts('Input parameter is not an array'));
  }

  if (empty($params)) {
    return civicrm_create_error('No input parameter present');
  }

  if (!array_key_exists('organization_id', $params) ||
    !array_key_exists('group_id', $params)
  ) {
    return civicrm_create_error(ts('organization_id and group_id are required field'));
  }

  require_once 'CRM/Contact/BAO/GroupOrganization.php';
  $groupOrgBAO = CRM_Contact_BAO_GroupOrganization::add($params);
  if (is_a($groupOrgBAO, 'CRM_Core_Error') || is_null($groupOrgBAO)) {
    return civicrm_create_error("Group Organization can not be created");
  }
  _civicrm_object_to_array($groupOrgBAO, $values);
  return civicrm_create_success($values);
}

/**
 * Deletes an existing Group Organization
 *
 * This API is used for deleting a Group Organization
 *
 * @param  Array  $params  ID of the Group Organization to be deleted
 *
 * @return null if successfull, array with is_error = 1 otherwise
 * @access public
 */
function civicrm_group_organization_remove(&$params) {
  _civicrm_initialize();

  if (!is_array($params)) {
    $error = civicrm_create_error('Input parameter is not an array');
    return $error;
  }

  if (empty($params)) {
    return civicrm_create_error('No input parameter present');
  }

  if (!CRM_Utils_Array::value('id', $params)) {
    $error = civicrm_create_error('Invalid or no value for Group Organization ID');
    return $error;
  }
  require_once 'CRM/Contact/BAO/GroupOrganization.php';
  $result = CRM_Contact_BAO_GroupOrganization::delete($params['id']);
  return $result ? civicrm_create_success(ts('Deleted Group Organization successfully')) : civicrm_create_error(ts('Could not delete Group Organization'));
}

