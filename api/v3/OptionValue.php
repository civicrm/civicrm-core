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
 * This api exposes CiviCRM option values.
 *
 * Values are grouped by "OptionGroup"
 *
 * @package CiviCRM_APIv3
 */

/**
 * Retrieve one or more option values.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_option_value_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'OptionValue');
}

/**
 * Adjust Metadata for get action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_option_value_get_spec(&$params) {
  $params['option_group_id']['api.aliases'] = ['option_group_name'];
}

/**
 * Add an OptionValue.
 *
 * @param array $params
 *
 * @throws CRM_Core_Exception
 * @return array
 *   API result array
 */
function civicrm_api3_option_value_create($params) {
  $result = _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'OptionValue');
  if (!empty($params['id']) && !array_key_exists('option_group_id', $params)) {
    $groupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue',
      $params['id'], 'option_group_id', 'id'
    );
  }
  else {
    $groupId = $params['option_group_id'];
  }

  civicrm_api('option_value', 'getfields', ['version' => 3, 'cache_clear' => 1, 'option_group_id' => $groupId]);
  return $result;
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_option_value_create_spec(&$params) {
  $params['is_active']['api.default'] = 1;
  //continue to support component
  $params['component_id']['api.aliases'] = ['component'];
  //  $params['name']['api.aliases'] = array('label');
  $params['option_group_id']['api.required'] = TRUE;
}

/**
 * Deletes an existing option value.
 *
 * @param array $params
 * @return array API result array
 * @throws CRM_Core_Exception
 */
function civicrm_api3_option_value_delete($params) {
  // We will get the option group id before deleting so we can flush pseudoconstants.
  $optionGroupID = civicrm_api('option_value', 'getvalue', ['version' => 3, 'id' => $params['id'], 'return' => 'option_group_id']);
  $result = CRM_Core_BAO_OptionValue::deleteRecord($params);
  if ($result) {
    civicrm_api('option_value', 'getfields', ['version' => 3, 'cache_clear' => 1, 'option_group_id' => $optionGroupID]);
    return civicrm_api3_create_success();
  }
  else {
    throw new CRM_Core_Exception('Could not delete OptionValue ' . $params['id']);
  }
}
