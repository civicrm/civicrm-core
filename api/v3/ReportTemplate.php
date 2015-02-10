<?php

/**
 * Retrieve a report template
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
function civicrm_api3_report_template_get($params) {
  require_once 'api/v3/OptionValue.php';
  $params['option_group_id'] = CRM_Core_DAO::getFieldValue(
    'CRM_Core_DAO_OptionGroup', 'report_template', 'id', 'name'
  );
  return civicrm_api3_option_value_get($params);
}

/**
 *  Add a OptionValue. OptionValues are used to classify CRM entities (including Contacts, Groups and Actions).
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
function civicrm_api3_report_template_create($params) {
  require_once 'api/v3/OptionValue.php';
  $params['option_group_id'] = CRM_Core_DAO::getFieldValue(
    'CRM_Core_DAO_OptionGroup', 'report_template', 'id', 'name'
  );
  if (!empty($params['component_id']) && !is_numeric($params['component_id'])) {
    $components = CRM_Core_PseudoConstant::get('CRM_Core_DAO_OptionValue', 'component_id', array('onlyActive' => FALSE, 'labelColumn' => 'name'));
    $params['component_id'] = array_search($params['component_id'], $components);
  }
  return civicrm_api3_option_value_create($params);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_report_template_create_spec(&$params) {
  require_once 'api/v3/OptionValue.php';
  _civicrm_api3_option_value_create_spec($params);
  $params['value']['api.aliases'] = array('report_url');
  $params['name']['api.aliases'] = array('class_name');
  $params['option_group_id']['api.default'] = CRM_Core_DAO::getFieldValue(
    'CRM_Core_DAO_OptionGroup', 'report_template', 'id', 'name'
  );
  // $params['component']['api.required'] = TRUE;
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
function civicrm_api3_report_template_delete($params) {
  require_once 'api/v3/OptionValue.php';
  return civicrm_api3_option_value_delete($params);
}

/**
 * Retrieve rows from a report template
 *
 * @param  array  $params input parameters
 *
 * @return  array details of found instances
 * @access public
 */
function civicrm_api3_report_template_getrows($params) {
  civicrm_api3_verify_one_mandatory($params, NULL, array('report_id', 'instance_id'));
  list($rows, $instance, $metadata) = _civicrm_api3_report_template_getrows($params);
  return civicrm_api3_create_success($rows, $params, 'report_template', 'getrows', CRM_Core_DAO::$_nullObject, $metadata);
}

/**
 * @param $params
 *
 * @return array
 * @throws API_Exception
 * @throws CiviCRM_API3_Exception
 */
function _civicrm_api3_report_template_getrows($params) {
  if(empty($params['report_id'])) {
    $params['report_id'] = civicrm_api3('report_instance', 'getvalue', array('id' => $params['instance_id'], 'return' => 'report_id'));
  }

  $class = civicrm_api3('option_value', 'getvalue', array(
    'option_group_id' => 'report_template',
    'return' => 'name',
    'value' => $params['report_id'],
    )
  );

  $reportInstance = new $class();
  if(!empty($params['instance_id'])) {
    $reportInstance->setID($params['instance_id']);
  }
  $reportInstance->setParams($params);
  $reportInstance->noController = TRUE;
  $reportInstance->preProcess();
  $reportInstance->setDefaultValues(FALSE);
  $reportInstance->setParams(array_merge($reportInstance->getDefaultValues(), $params));
  $options = _civicrm_api3_get_options_from_params($params, TRUE,'report_template','get');
  $reportInstance->setLimitValue($options['limit']);
  $reportInstance->setOffsetValue($options['offset']);
  $reportInstance->beginPostProcessCommon();
  $sql = $reportInstance->buildQuery();
  $rows = $metadata = $requiredMetadata  = array();
  $reportInstance->buildRows($sql, $rows);
  $requiredMetadata = array();
  if(isset($params['options']) && !empty($params['options']['metadata'])) {
    $requiredMetadata = $params['options']['metadata'];
    if(in_array('title', $requiredMetadata)) {
      $metadata['metadata']['title'] = $reportInstance->getTitle();
    }
    if(in_array('labels', $requiredMetadata)) {
      foreach ($reportInstance->_columnHeaders as $key => $header) {
        //would be better just to expect reports to provide titles but reports are not consistent so we anticipate empty
        //NB I think these are already translated
        $metadata['metadata']['labels'][$key] = !empty($header['title']) ? $header['title'] : '';
      }
    }
  }
  return array($rows, $reportInstance, $metadata);
}

/**
 * @param $params
 *
 * @return array
 */
function civicrm_api3_report_template_getstatistics($params) {
  list($rows, $reportInstance, $metadata) = _civicrm_api3_report_template_getrows($params);
  $stats = $reportInstance->statistics($rows);
  return civicrm_api3_create_success($stats, $params, 'report_template', 'getstatistics', CRM_Core_DAO::$_nullObject, $metadata);
}
/**
 * Retrieve rows from a report template
 *
 * @param  array  $params input parameters
 *
 * @return  array details of found instances
 * @access public
 */
function _civicrm_api3_report_template_getrows_spec(&$params) {
  $params['report_id'] = array(
    'title' => 'Report ID - eg. member/lapse',
  );
}

/*
function civicrm_api3_report_template_getfields($params) {
  return civicrm_api3_create_success(array(
    'id' => array(
      'name' => 'id',
      'type' => 1,
      'required' => 1,
    ),
    'option_group_id' => array(
      'name' => 'option_group_id',
      'type' => 1,
      'required' => 1,
      'FKClassName' => 'CRM_Core_DAO_OptionGroup',
    ),
    'label' => array(
      'name' => 'label',
      'type' => 2,
      'title' => 'Option Label',
      'required' => 1,
      'maxlength' => 255,
      'size' => 45,
    ),
    'value' => array(
      'name' => 'value',
      'type' => 2,
      'title' => 'Option Value',
      'required' => 1,
      'maxlength' => 512,
      'size' => 45,
    ),
    'name' => array(
      'name' => 'name',
      'type' => 2,
      'title' => 'Option Name',
      'maxlength' => 255,
      'size' => 45,
      'import' => 1,
      'where' => 'civicrm_option_value.name',
      'export' => 1,
    ),
    'grouping' => array(
      'name' => 'grouping',
      'type' => 2,
      'title' => 'Option Grouping Name',
      'maxlength' => 255,
      'size' => 45,
    ),
    'filter' => array(
      'name' => 'filter',
      'type' => 1,
      'title' => 'Filter',
    ),
    'is_default' => array(
      'name' => 'is_default',
      'type' => 16,
    ),
    'weight' => array(
      'name' => 'weight',
      'type' => 1,
      'title' => 'Weight',
      'required' => 1,
    ),
    'description' => array(
      'name' => 'description',
      'type' => 32,
      'title' => 'Description',
      'rows' => 8,
      'cols' => 60,
    ),
    'is_optgroup' => array(
      'name' => 'is_optgroup',
      'type' => 16,
    ),
    'is_reserved' => array(
      'name' => 'is_reserved',
      'type' => 16,
    ),
    'is_active' => array(
      'name' => 'is_active',
      'type' => 16,
    ),
    'component_id' => array(
      'name' => 'component_id',
      'type' => 1,
      'FKClassName' => 'CRM_Core_DAO_Component',
    ),
    'domain_id' => array(
      'name' => 'domain_id',
      'type' => 1,
      'FKClassName' => 'CRM_Core_DAO_Domain',
    ),
    'visibility_id' => array(
      'name' => 'visibility_id',
      'type' => 1,
      'default' => 'UL',
    ),
  ));
}*/
