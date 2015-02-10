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
 * @deprecated
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Activity
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id: ActivityType.php 30171 2010-10-14 09:11:27Z mover $
 *
 */

/**
 * @deprecated api notice
 * @return string to indicate this entire api entity is deprecated
 */
function _civicrm_api3_activity_type_deprecation() {
  return 'The activity_type api is deprecated. Please use the option_value api instead.';
}

/**
 * Function to retrieve activity types
 *
 * @param $params
 *
 * @return array $activityTypes activity types keyed by id
 * @access public
 *
 * @example ActivityTypeGet.php
 * @deprecated - use getoptions
 */
function civicrm_api3_activity_type_get($params) {

  $activityTypes = CRM_Core_OptionGroup::values('activity_type');
  return civicrm_api3_create_success($activityTypes, $params, 'activity_type', 'get');
}

/**
 * Function to create activity type (
 *
 * @param array   $params  associated array of fields
 *                 $params['option_value_id'] is required for updation of activity type
 *
 * @return array $activityType created / updated activity type
 *
 * @access public
 *
 * @deprecated - use option_value create
 */
function civicrm_api3_activity_type_create($params) {

  $action = 1;
  $groupParams = array('name' => 'activity_type');

  if ($optionValueID = CRM_Utils_Array::value('option_value_id', $params)) {
    $action = 2;
  }

  $activityObject = CRM_Core_OptionValue::addOptionValue($params, $groupParams, $action, $optionValueID);
  $activityType = array();
  _civicrm_api3_object_to_array($activityObject, $activityType[$activityObject->id]);
  return civicrm_api3_create_success($activityType, $params, 'activity_type', 'create');
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_activity_type_create_spec(&$params) {
  $params['label']['api.required'] = 1;
  $params['label']['title'] = 'Label';
  $params['weight']['api.required'] = 1;
  $params['weight']['title'] = 'Weight';
}

/**
 * Function to delete activity type
 *
 * @param array $params array including id of activity_type to delete

 * @return array API result array
 *
 * @access public
 *
 * @deprecated - we will introduce OptionValue Delete- plse consider helping with this if not done
 */
function civicrm_api3_activity_type_delete($params) {
  return civicrm_api3_create_success(CRM_Core_BAO_OptionValue::del($params['id']), $params);
}

