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
 * This api exposes CiviCRM option groups.
 *
 * OptionGroups are containers for option values.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Get option groups.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_option_group_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Create/update option group.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 */
function civicrm_api3_option_group_create($params) {
  // Legacy code moved from old CRM_Core_BAO_OptionGroup::add to keep Api3 behavior as-is.
  if (empty($params['name']) && empty($params['id'])) {
    $params['name'] = CRM_Utils_String::titleToVar(strtolower($params['title']));
  }
  elseif (!empty($params['name']) && strpos($params['name'], ' ')) {
    $params['name'] = CRM_Utils_String::titleToVar(strtolower($params['name']));
  }
  elseif (!empty($params['name'])) {
    $params['name'] = strtolower($params['name']);
  }
  $bao = CRM_Core_BAO_OptionGroup::writeRecord($params);
  civicrm_api('option_value', 'getfields', ['version' => 3, 'cache_clear' => 1]);
  $values = [];
  _civicrm_api3_object_to_array($bao, $values[$bao->id]);
  return civicrm_api3_create_success($values, $params, 'OptionGroup', 'create', $bao);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_option_group_create_spec(&$params) {
  $params['name']['api.unique'] = 1;
  $params['is_active']['api.default'] = TRUE;
}

/**
 * Delete an existing Option Group.
 *
 * This method is used to delete any existing OptionGroup given its id.
 *
 * @param array $params
 *   [id]
 *
 * @return array
 *   API Result Array
 */
function civicrm_api3_option_group_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
