<?php

/**
 * Retrieve a report instance
 *
 * FIXME This is a bare-minimum placeholder
 *
 * @param  array  $ params input parameters
 *
 * {@example OptionValueGet.php 0}
 * @example OptionValueGet.php
 *
 * @return  array details of found Option Values
 * {@getfields OptionValue_get}
 * @access public
 */
function civicrm_api3_report_instance_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 *  Add a OptionValue. OptionValues are used to classify CRM entities (including Contacts, Groups and Actions).
 *
 * Allowed @params array keys are:
 *
 * {@example OptionValueCreate.php}
 *
 * @return array of newly created option_value property values.
 * {@getfields OptionValue_create}
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
 * {@example ReportInstanceDelete.php 0}
 *
 * @return array Api result
 * {@getfields ReportInstance_create}
 * @access public
 */
function civicrm_api3_report_instance_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
