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
 * File for the CiviCRM APIv3 user framework group functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_UF
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: UFGroup.php 30171 2010-10-14 09:11:27Z mover $
 *
 */

/**
 * Files required for this package
 */

require_once 'CRM/Core/BAO/UFGroup.php';

function _civicrm_api3_uf_group_create_spec(&$params) {
  $session = CRM_Core_Session::singleton();
  $params['title']['api.required'] = 1;
  $params['is_active']['api.default'] = 1;
  $params['is_update_dupe']['api.default'] = 1;
  $params['created_id']['api.default'] = 'user_contact_id';//the current user
  $params['created_date']['api.default'] = 'now';
}
/**
 * Use this API to create a new group. See the CRM Data Model for uf_group property definitions
 *
 * @param $params  array   Associative array of property name/value pairs to insert in group.
 *
 * @return   Newly create $ufGroupArray array
 * {@getfields UFGroup_create}
 * @example UFGroupCreate.php
 * @access public
 */
function civicrm_api3_uf_group_create($params) {

  $ids = array();
  $ids['ufgroup'] = $params['id'];


  $ufGroup = CRM_Core_BAO_UFGroup::add($params, $ids);
  _civicrm_api3_object_to_array($ufGroup, $ufGroupArray[$ufGroup->id]);

  return civicrm_api3_create_success($ufGroupArray, $params);
}

/**
 * Returns array of uf groups (profiles)  matching a set of one or more group properties
 *
 * @param array $params  (reference) Array of one or more valid
 *                       property_name=>value pairs. If $params is set
 *                       as null, all surveys will be returned
 *
 * @return array   Array of matching profiles
 * {@getfields UFGroup_get}
 * @example UFGroupGet.php
 * @access public
 */
function civicrm_api3_uf_group_get($params) {

  return _civicrm_api3_basic_get('CRM_Core_BAO_UFGroup', $params);
}

/**
 * Delete uf group
 *
 * @param $groupId int  Valid uf_group id that to be deleted
 *
 * @return true on successful delete or return error
 * @todo doesnt rtn success or error properly
 * @access public
 * {@getfields UFGroup_delete}
 * @example UFGroupDelete.php
 */
function civicrm_api3_uf_group_delete($params) {

  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

