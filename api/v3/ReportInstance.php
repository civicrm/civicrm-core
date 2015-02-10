<?php

/**
 * Retrieve a report instance
 *
 * @param  array  $params input parameters
 *
 * @return  array details of found instances
 * @access public
 */
function civicrm_api3_report_instance_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 *  Add or update a report instance.
 *
 * @param $params
 *
 * @return array of newly created report instance property values.
 * @access public
 */
function civicrm_api3_report_instance_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_report_instance_create_spec(&$params) {
  $params['report_id']['api.required'] = 1;
  $params['title']['api.required'] = 1;
}

/**
 * Deletes an existing ReportInstance
 *
 * @param  array  $params
 *
 * @return array Api result
 * @access public
 */
function civicrm_api3_report_instance_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
