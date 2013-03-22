<?php
// $Id$

/**
 * Retrieve one or more OptionValues
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
function civicrm_api3_option_value_get($params) {

  if (empty($params['option_group_id']) && !empty($params['option_group_name'])) {
    $opt = array('version' => 3, 'name' => $params['option_group_name']);
    $optionGroup = civicrm_api('OptionGroup', 'Get', $opt);
    if (empty($optionGroup['id'])) {
      return civicrm_api3_create_error("option group name does not correlate to a single option group");
    }
    $params['option_group_id'] = $optionGroup['id'];
  }

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
function civicrm_api3_option_value_create($params) {

  $result = _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
  civicrm_api('option_value', 'getfields', array('version' => 3, 'cache_clear' => 1, 'option_group_id' => $params['option_group_id']));
  return $result;
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_option_value_create_spec(&$params) {
  $params['is_active']['api.default'] = 1;
  //continue to support component
  $params['component_id']['api.aliases'] = array('component');
  $params['name']['api.aliases'] = array('label');
  $params['option_group_id']['api.required'] = TRUE;
}

/**
 * Deletes an existing OptionValue
 *
 * @param  array  $params
 *
 * {@example OptionValueDelete.php 0}
 *
 * @return array Api result
 * {@getfields OptionValue_create}
 * @access public
 */
function civicrm_api3_option_value_delete($params) {
  // we will get the option group id before deleting so we can flush pseudoconstants
  $optionGroupID = civicrm_api('option_value', 'getvalue', array('version' => 3, 'id' => $params['id'], 'return' => 'option_group_id'));
  if(CRM_Core_BAO_OptionValue::del((int) $params['id'])){
    civicrm_api('option_value', 'getfields', array('version' => 3, 'cache_clear' => 1, 'option_group_id' => $optionGroupID));
    return civicrm_api3_create_success();
  }
  else{
    civicrm_api3_create_error('Could not delete OptionValue ' . $params['id']);
  }
}

