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
 * File for the CiviCRM APIv3 group functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Group
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: Group.php 30171 2010-10-14 09:11:27Z mover $
 */

/**
 * Include utility functions
 */
require_once 'CRM/Contact/BAO/Group.php';

/**
 * create/update group
 *
 * This API is used to create new group or update any of the existing
 * In case of updating existing group, id of that particular grop must
 * be in $params array. Either id or name is required field in the
 * $params array
 *
 * @param array $params  (referance) Associative array of property
 *                       name/value pairs to insert in new 'group'
 *
 * @return array   returns id of the group created if success,
 *                 error message otherwise
 *@example GroupCreate.php
 *{@getfields group_create}
 * @access public
 */
function civicrm_api3_group_create($params) {

  $group = CRM_Contact_BAO_Group::create($params);

  if (is_null($group)) {
    return civicrm_api3_create_error('Group not created');
  }
  else {
    $values = array();
    _civicrm_api3_object_to_array_unique_fields($group, $values[$group->id]);
    return civicrm_api3_create_success($values, $params, 'group', 'create', $group);
  }
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_group_create_spec(&$params) {
  $params['is_active']['api.default'] = 1;
  $params['title']['api.required'] = 1;
}

/**
 * Returns array of groups  matching a set of one or more group properties
 *
 * @param array $params  (referance) Array of one or more valid
 *                       property_name=>value pairs. If $params is set
 *                       as null, all groups will be returned
 *
 * @return array  Array of matching groups
 * @example GroupGet.php
 * {@getfields group_get}
 * @access public
 */
function civicrm_api3_group_get($params) {

  $options          = _civicrm_api3_get_options_from_params($params, TRUE, 'group', 'get');
  $sort             = CRM_Utils_Array::value('sort', $options, NULL);
  $offset           = CRM_Utils_Array::value('offset', $options);
  $rowCount         = CRM_Utils_Array::value('limit', $options);
  $returnProperties = CRM_Utils_Array::value('return', $options, NULL);
  $inputParams      = CRM_Utils_Array::value('input_params', $options, array());
  if(is_array($returnProperties) && !empty($returnProperties)){
    // group function takes $returnProperties in non standard format & doesn't add id
    unset($returnProperties['group_id']);
    $returnProperties['id'] = 1;
    $returnProperties = array_keys($returnProperties);
  }
  if (CRM_Utils_Array::value('group_id', $inputParams)) {
    $inputParams['id'] = $inputParams['group_id'];
  }
  $groupObjects = CRM_Contact_BAO_Group::getGroups($inputParams, $returnProperties, $sort, $offset, $rowCount);
  if (empty($groupObjects)) {
    return civicrm_api3_create_success(FALSE);
  }
  $groups = array();
  foreach ($groupObjects as $group) {
    _civicrm_api3_object_to_array($group, $groups[$group->id]);
    _civicrm_api3_custom_data_get($groups[$group->id], 'Group', $group->id);
  }


  return civicrm_api3_create_success($groups, $params, 'group', 'create');
}

/**
 * delete an existing group
 *
 * This method is used to delete any existing group. id of the group
 * to be deleted is required field in $params array
 *
 * @param array $params  (referance) array containing id of the group
 *                       to be deleted
 *
 * @return array  (referance) returns flag true if successfull, error
 *                message otherwise
 *@example GroupDelete.php
 *{@getfields group_delete}
 *
 * @access public
 */
function civicrm_api3_group_delete($params) {

  CRM_Contact_BAO_Group::discard($params['id']);
  return civicrm_api3_create_success(TRUE);
}

