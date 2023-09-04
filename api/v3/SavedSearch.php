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
 * This api exposes CiviCRM saved searches.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update a saved search.
 *
 * @param array $params
 *   Associative array of property name-value pairs to insert in new saved search.
 *
 * @return array
 *   api result array
 *   {@getfields saved_search_create}
 *
 * @throws \CRM_Core_Exception
 *
 * @example SavedSearch/Create.php Std create example.
 * @access public
 */
function civicrm_api3_saved_search_create($params) {
  $result = _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'SavedSearch');
  _civicrm_api3_saved_search_result_cleanup($result);
  return $result;
}

/**
 * @param array $fields
 */
function _civicrm_api3_saved_search_create_spec(&$fields) {
  $fields['form_values']['api.aliases'][] = 'formValues';
  $fields['form_values']['api.required'] = TRUE;
}

/**
 * Delete an existing saved search.
 *
 * @param array $params
 *   Associative array of property name-value pairs. $params['id'] should be
 *   the ID of the saved search to be deleted.
 *
 * @return array
 *   api result array
 *   {@getfields saved_search_delete}
 *
 * @throws \CRM_Core_Exception
 *
 * @example SavedSearch/Delete.php Std delete example.
 * @access public
 */
function civicrm_api3_saved_search_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Retrieve one or more saved search(es).
 *
 * @param array $params
 *   An associative array of name-value pairs.
 * @example SavedSearch/Get.php Std get example.
 * @return array
 *   api result array
 *   {@getfields saved_search_get}
 * @access public
 */
function civicrm_api3_saved_search_get($params) {
  $result = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
  _civicrm_api3_saved_search_result_cleanup($result);
  return $result;
}

/**
 * Unserialize the form_values field in SavedSearch API results.
 *
 * Note: APIv4 handles serialization automatically based on metadata.
 *
 * @param array $result API result to be cleaned up.
 */
function _civicrm_api3_saved_search_result_cleanup(&$result) {
  if (isset($result['values']) && is_array($result['values'])) {
    // Only run if there are values (getCount for example does not return values).
    foreach ($result['values'] as $key => $value) {
      if (isset($value['form_values'])) {
        $result['values'][$key]['form_values'] = CRM_Utils_String::unserialize($value['form_values']);
      }
    }
  }
}
