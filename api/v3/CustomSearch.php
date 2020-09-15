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
 * This api exposes CiviCRM custom search.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Retrieve custom searches.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_custom_search_get($params) {
  require_once 'api/v3/OptionValue.php';
  $params['option_group_id'] = CRM_Core_DAO::getFieldValue(
    'CRM_Core_DAO_OptionGroup', 'custom_search', 'id', 'name'
  );
  return civicrm_api3_option_value_get($params);
}

/**
 * Add a CustomSearch.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_custom_search_create($params) {
  require_once 'api/v3/OptionValue.php';
  $params['option_group_id'] = CRM_Core_DAO::getFieldValue(
    'CRM_Core_DAO_OptionGroup', 'custom_search', 'id', 'name'
  );
  // empirically, class name goes to both 'name' and 'label'
  if (array_key_exists('name', $params)) {
    $params['label'] = $params['name'];
  }
  return civicrm_api3_option_value_create($params);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_custom_search_create_spec(&$params) {
  require_once 'api/v3/OptionValue.php';
  _civicrm_api3_option_value_create_spec($params);
  $params['option_group_id']['api.default'] = CRM_Core_DAO::getFieldValue(
    'CRM_Core_DAO_OptionGroup', 'custom_search', 'id', 'name'
  );
  $params['name']['api.aliases'] = ['class_name'];
}

/**
 * Deletes an existing CustomSearch.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_custom_search_delete($params) {
  require_once 'api/v3/OptionValue.php';
  return civicrm_api3_option_value_delete($params);
}
