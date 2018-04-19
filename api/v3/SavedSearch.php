<?php

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                  |
  +--------------------------------------------------------------------+
  | Copyright Chirojeugd-Vlaanderen vzw 2015                           |
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
 * This api exposes CiviCRM saved searches.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update a saved search.
 *
 * @param array $params
 *   Associative array of property name-value pairs to insert in new saved search.
 * @example SavedSearch/Create.php Std create example.
 * @return array api result array
 *   {@getfields saved_search_create}
 * @access public
 */
function civicrm_api3_saved_search_create($params) {
  civicrm_api3_verify_one_mandatory($params, NULL, array('form_values', 'where_clause'));
  // The create function of the dao expects a 'formValues' that is
  // not serialized. The get function returns form_values, that is
  // serialized.
  // So for the create API, I guess it should work for serialized and
  // unserialized form_values.

  if (isset($params["form_values"])) {
    if (is_array($params["form_values"])) {
      $params["formValues"] = $params["form_values"];
    }
    else {
      // Assume that form_values is serialized.
      $params["formValues"] = unserialize($params["form_values"]);
    }
  }

  $result = _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'SavedSearch');
  _civicrm_api3_saved_search_result_cleanup($result);
  return $result;
}

/**
 * Delete an existing saved search.
 *
 * @param array $params
 *   Associative array of property name-value pairs. $params['id'] should be
 *   the ID of the saved search to be deleted.
 * @example SavedSearch/Delete.php Std delete example.
 * @return array api result array
 *   {@getfields saved_search_delete}
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
 * @return array api result array
 *   {@getfields saved_search_get}
 * @access public
 */
function civicrm_api3_saved_search_get($params) {
  $result = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
  _civicrm_api3_saved_search_result_cleanup($result);
  return $result;
}

/**
 * This function unserializes the form_values in an SavedSearch API result.
 *
 * @param array $result API result to be cleaned up.
 */
function _civicrm_api3_saved_search_result_cleanup(&$result) {
  if (isset($result['values']) && is_array($result['values'])) {
    // Only clean up the values if there are values. (A getCount operation
    // for example does not return values.)
    foreach ($result['values'] as $key => $value) {
      $result['values'][$key]['form_values'] = unserialize($value['form_values']);
    }
  }
}
