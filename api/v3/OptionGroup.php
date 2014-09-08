<?php

/**
 * @param $params
 *
 * @return array
 */
function civicrm_api3_option_group_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * create/update survey
 *
 * This API is used to create new survey or update any of the existing
 * In case of updating existing survey, id of that particular survey must
 * be in $params array.
 *
 * @param array $params  (reference) Associative array of property
 *   name/value pairs to insert in new 'survey'
 *
 * @return array   survey array
 *
 * @access public
 */
function civicrm_api3_option_group_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_option_group_create_spec(&$params) {
  $params['name']['api.unique'] = 1;
}

/**
 * delete an existing Option Group
 *
 * This method is used to delete any existing Option Group. id of the group
 * to be deleted is required field in $params array
 *
 * @param array $params array containing id of the group
 *                       to be deleted
 *
 * @return array API Result Array
 *                message otherwise
 * {@getfields OptionGroup_delete}
 * @access public
 */
function civicrm_api3_option_group_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
