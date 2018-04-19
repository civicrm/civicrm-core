<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
  $params['option_group_id']['api.aliases'] = array('option_group_name');
}

/**
 * Add an OptionValue.
 *
 * @param array $params
 *
 * @throws API_Exception
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

  civicrm_api('option_value', 'getfields', array('version' => 3, 'cache_clear' => 1, 'option_group_id' => $groupId));
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
  $params['component_id']['api.aliases'] = array('component');
  //  $params['name']['api.aliases'] = array('label');
  $params['option_group_id']['api.required'] = TRUE;
}

/**
 * Deletes an existing option value.
 *
 * @param array $params
 * @return array API result array
 * @throws API_Exception
 */
function civicrm_api3_option_value_delete($params) {
  // We will get the option group id before deleting so we can flush pseudoconstants.
  $optionGroupID = civicrm_api('option_value', 'getvalue', array('version' => 3, 'id' => $params['id'], 'return' => 'option_group_id'));
  $result = CRM_Core_BAO_OptionValue::del($params['id']);
  if ($result) {
    civicrm_api('option_value', 'getfields', array('version' => 3, 'cache_clear' => 1, 'option_group_id' => $optionGroupID));
    return civicrm_api3_create_success();
  }
  else {
    throw new API_Exception('Could not delete OptionValue ' . $params['id']);
  }
}
