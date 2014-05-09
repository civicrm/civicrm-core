<?php

/**
 * Retrieve a CustomSearches
 *
 * FIXME This is a bare-minimum placeholder
 *
 * @param $params
 *
 * @internal param $array $ params input parameters
 *
 * {@example OptionValueGet.php 0}
 * @example OptionValueGet.php
 *
 * @return  array details of found Option Values
 * {@getfields OptionValue_get}
 * @access public
 */
function civicrm_api3_custom_search_get($params) {
  require_once 'api/v3/OptionValue.php';
  $params['option_group_id'] = CRM_Core_DAO::getFieldValue(
    'CRM_Core_DAO_OptionGroup', 'custom_search', 'id', 'name'
  );
  return civicrm_api3_option_value_get($params);
}

/**
 * Add a CustomSearch
 *
 * Allowed @params array keys are:
 *
 * {@example OptionValueCreate.php}
 *
 * @param $params
 *
 * @return array of newly created option_value property values.
 * {@getfields OptionValue_create}
 * @access public
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
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_custom_search_create_spec(&$params) {
  require_once 'api/v3/OptionValue.php';
  _civicrm_api3_option_value_create_spec($params);
  $params['option_group_id']['api.default'] = CRM_Core_DAO::getFieldValue(
    'CRM_Core_DAO_OptionGroup', 'custom_search', 'id', 'name'
  );
  $params['name']['api.aliases'] = array('class_name');
}

/**
 * Deletes an existing ReportTemplate
 *
 * @param  array  $params
 *
 * {@example ReportTemplateDelete.php 0}
 *
 * @return array Api result
 * {@getfields ReportTemplate_create}
 * @access public
 */
function civicrm_api3_custom_search_delete($params) {
  require_once 'api/v3/OptionValue.php';
  return civicrm_api3_option_value_delete($params);
}
