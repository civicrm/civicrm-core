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
 * The ActivityType api is deprecated. Please use the OptionValue api instead.
 *
 * @deprecated
 *
 * @package CiviCRM_APIv3
 */

/**
 * Notification of deprecated function.
 *
 * @deprecated api notice
 * @return string
 *   to indicate this entire api entity is deprecated
 */
function _civicrm_api3_activity_type_deprecation() {
  return 'The ActivityType api is deprecated. Please use the OptionValue api instead.';
}

/**
 * Retrieve activity types.
 *
 * @param array $params
 *
 * @return array
 *   activity types keyed by id
 * @deprecated - use the getoptions action instead
 */
function civicrm_api3_activity_type_get($params) {

  $activityTypes = CRM_Core_OptionGroup::values('activity_type');
  return civicrm_api3_create_success($activityTypes, $params, 'activity_type', 'get');
}

/**
 * Create activity type.
 *
 * @param array $params
 *
 * @return array
 *   created / updated activity type
 *
 * @deprecated use the OptionValue api instead
 */
function civicrm_api3_activity_type_create($params) {

  $action = 1;

  $optionValueID = $params['option_value_id'] ?? NULL;
  if ($optionValueID) {
    $action = 2;
  }

  $activityObject = CRM_Core_OptionValue::addOptionValue($params, 'activity_type', $action, $optionValueID);
  $activityType = [];
  _civicrm_api3_object_to_array($activityObject, $activityType[$activityObject->id]);
  return civicrm_api3_create_success($activityType, $params, 'activity_type', 'create');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_activity_type_create_spec(&$params) {
  $params['label'] = [
    'api.required' => 1,
    'title' => 'Label',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['weight'] = [
    'api.required' => 1,
    'title' => 'Weight',
    'type' => CRM_Utils_Type::T_STRING,
  ];
}

/**
 * Delete ActivityType.
 *
 * @param array $params
 *   Array including id of activity_type to delete.
 * @return array API result array
 * @throws CRM_Core_Exception
 * @deprecated use OptionValue api
 */
function civicrm_api3_activity_type_delete($params) {
  $result = CRM_Core_BAO_OptionValue::deleteRecord($params);
  if ($result) {
    return civicrm_api3_create_success(TRUE, $params);
  }
  throw new CRM_Core_Exception("Failure to delete activity type id {$params['id']}");
}
