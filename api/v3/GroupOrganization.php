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
 * File for the CiviCRM APIv3 group contact functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Group
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: GroupContact.php 21624 2009-06-04 22:02:55Z mover $
 *
 */


/**
 * This API will give list of the groups for particular contact
 * Particualr status can be sent in params array
 * If no status mentioned in params, by default 'added' will be used
 * to fetch the records
 *
 * @param  array $params  name value pair of contact information
 * {@getfields GroupOrganization_get}
 * @example GroupOrganizationGet.php
 *
 * @return  array  list of groups, given contact subsribed to
 */
function civicrm_api3_group_organization_get($params) {
  return _civicrm_api3_basic_get('CRM_Contact_DAO_GroupOrganization', $params);
}

/**
 * @example GroupOrganizationCreate.php
 * {@getfields GroupOrganization_create}
 *
 * @param $params array
 *
 * @return array
 *
 */
function civicrm_api3_group_organization_create($params) {

  $groupOrgBAO = CRM_Contact_BAO_GroupOrganization::add($params);

  if (is_null($groupOrgBAO)) {
    return civicrm_api3_create_error("group organization not created");
  }

  _civicrm_api3_object_to_array($groupOrgBAO, $values);
  return civicrm_api3_create_success($values, $params, 'group_organization', 'get', $groupOrgBAO);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_group_organization_create_spec(&$params) {
  $params['organization_id']['api.required'] = 1;
  $params['group_id']['api.required'] = 1;
}

/**
 * Deletes an existing Group Organization
 *
 * This API is used for deleting a Group Organization
 *
 * @param  array  $params  with 'id' = ID of the Group Organization to be deleted
 *
 * @return array API Result
 * {@getfields GroupOrganization_delete}
 * @example GroupOrganizationDelete.php
 * @access public
 */
function civicrm_api3_group_organization_delete($params) {

  $result = CRM_Contact_BAO_GroupOrganization::deleteGroupOrganization($params['id']);
  return $result ? civicrm_api3_create_success('Deleted Group Organization successfully') : civicrm_api3_create_error('Could not delete Group Organization');
}

