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

  // CRM-10921: do not fill-in defaults if this is an update
  if (!CRM_Utils_Array::value('id', $params)) {
    if (!CRM_Utils_Array::value('label', $params) && CRM_Utils_Array::value('name', $params)) {
      // 'label' defaults to 'name'
      $params['label'] = $params['name'];
    }
    if (!CRM_Utils_Array::value('value', $params) && CRM_Utils_Array::value('option_group_id', $params)) {
      // 'value' defaults to next weight in option_group
      $params['value'] = (int) CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue',
        array('option_group_id' => $params['option_group_id'])
      );
    }
    if (!CRM_Utils_Array::value('weight', $params) && CRM_Utils_Array::value('value', $params)) {
      // 'weight' defaults to 'value'
      $params['weight'] = $params['value'];
    } elseif (CRM_Utils_Array::value('weight', $params) && $params['weight'] == 'next' && CRM_Utils_Array::value('option_group_id', $params)) {
      // weight is numeric, so it's safe-ish to treat symbol 'next' as magical value
      $params['weight'] = (int) CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue',
        array('option_group_id' => $params['option_group_id'])
      );
    }
  }

  if (isset($params['component'])) {// allow a component to be reset to ''
    // convert 'component' to 'component_id'
    if (empty($params['component'])) {
      $params['component_id'] = '';
    } else {
      $params['component_id'] = array_search($params['component'], CRM_Core_PseudoConstant::component());
    }
    unset($params['component']);
  }

  if (CRM_Utils_Array::value('id', $params)) {
    $ids = array('optionValue' => $params['id']);
  }
  $optionValueBAO = CRM_Core_BAO_OptionValue::add($params, $ids);
  civicrm_api('option_value', 'getfields', array('version' => 3, 'cache_clear' => 1, 'option_group_id' => $params['option_group_id']));
  $values = array();
  _civicrm_api3_object_to_array($optionValueBAO, $values[$optionValueBAO->id]);
  return civicrm_api3_create_success($values, $params);
}

/*
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_option_value_create_spec(&$params) {
  $params['is_active']['api.default'] = 1;
  $params['component']['type'] = CRM_Utils_Type::T_STRING;
  $params['component']['options'] = array_values(CRM_Core_PseudoConstant::component());
  $params['name']['api.aliases'] = array('label');
  // $params['component_id']['pseudoconstant'] = 'component';
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

