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
 * File for the CiviCRM APIv3 group functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Group
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: Group.php 30171 2010-10-14 09:11:27Z mover $
 */

/**
 * create/update group
 *
 * This API is used to create new group or update any of the existing
 * In case of updating existing group, id of that particular group must
 * be in $params array. Either id or name is required field in the
 * $params array
 *
 * @param array $params Associative array of property
 *                       name/value pairs to insert in new 'group'
 *
 * @return array  API result array
 *@example GroupCreate.php
 *{@getfields group_create}
 * @access public
 */
function civicrm_api3_group_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Group');
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
 * @param array $params Array of one or more valid
 *                       property_name=>value pairs. If $params is set
 *                       as null, all groups will be returned
 *
 * @return array  Array of matching groups
 * @example GroupGet.php
 * {@getfields group_get}
 * @access public
 */
function civicrm_api3_group_get($params) {
  $options = _civicrm_api3_get_options_from_params($params, TRUE, 'group', 'get');
  if(empty($options['return']) || !in_array('member_count', $options['return'])) {
    return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'Group');
  }

  $groups = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, FALSE, 'Group');
  foreach ($groups as $id => $group) {
    $groups[$id]['member_count'] = CRM_Contact_BAO_Group::memberCount($id);
  }
  return civicrm_api3_create_success($groups, $params, 'group', 'get');
}

/**
 * delete an existing group
 *
 * This method is used to delete any existing group. id of the group
 * to be deleted is required field in $params array
 *
 * @param array $params array containing id of the group
 *                       to be deleted
 *
 * @return array API result array
 *@example GroupDelete.php
 *{@getfields group_delete}
 *
 * @access public
 */
function civicrm_api3_group_delete($params) {

  CRM_Contact_BAO_Group::discard($params['id']);
  return civicrm_api3_create_success(TRUE);
}

