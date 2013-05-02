<?php
// $Id: Group.php 45502 2013-02-08 13:32:55Z kurund $


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
 * File for the CiviCRM APIv2 group functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Group
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: Group.php 45502 2013-02-08 13:32:55Z kurund $
 */

/**
 * Include utility functions
 */
require_once 'CRM/Contact/BAO/Group.php';
require_once 'api/v2/utils.php';

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
 *
 * @access public
 */
function civicrm_group_add(&$params) {
  _civicrm_initialize();
  if (is_null($params) || !is_array($params) || empty($params)) {
    return civicrm_create_error('Required parameter missing');
  }

  if (!CRM_Utils_Array::value('title', $params)) {
    return civicrm_create_error('Required parameter title missing');
  }

  $group = CRM_Contact_BAO_Group::create($params);

  if (is_null($group)) {
    return civicrm_create_error('Group not created');
  }
  else {
    return civicrm_create_success($group);
  }
}
/*
 * Wrapper for civicrm_group_add so function can take new (v3) name
 */
function civicrm_group_create(&$params) {
  $result = civicrm_group_add($params);
  return $result;
}

/**
 * Returns array of groups  matching a set of one or more group properties
 *
 * @param array $params  (referance) Array of one or more valid
 *                       property_name=>value pairs. If $params is set
 *                       as null, all groups will be returned
 *
 * @return array  (referance) Array of matching groups
 * @access public
 */
function civicrm_group_get(&$params) {
  _civicrm_initialize();
  if (!is_null($params) && !is_array($params)) {
    return civicrm_create_error('Params should be array');
  }

  $returnProperties = array();
  foreach ($params as $n => $v) {
    if (substr($n, 0, 7) == 'return.') {
      $returnProperties[] = substr($n, 7);
    }
  }

  if (!empty($returnProperties)) {
    $returnProperties[] = 'id';
  }

  $groupObjects = CRM_Contact_BAO_Group::getGroups($params, $returnProperties);

  if (count($groupObjects) == 0) {
    return civicrm_create_error('No such group exists');
  }

  $groups = array();
  foreach ($groupObjects as $group) {
    _civicrm_object_to_array($group, $groups[$group->id]);
  }

  return $groups;
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
 *
 * @access public
 */
function civicrm_group_delete(&$params) {
  _civicrm_initialize();
  if (is_null($params) || !is_array($params) || !CRM_Utils_Array::value('id', $params)) {
    return civicrm_create_error('Required parameter missing');
  }

  CRM_Contact_BAO_Group::discard($params['id']);
  return civicrm_create_success(TRUE);
}

