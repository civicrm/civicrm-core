<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * This api exposes CiviCRM report templates.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Retrieve a report template.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_report_template_get($params) {
  require_once 'api/v3/OptionValue.php';
  $params['option_group_id'] = CRM_Core_DAO::getFieldValue(
    'CRM_Core_DAO_OptionGroup', 'report_template', 'id', 'name'
  );
  return civicrm_api3_option_value_get($params);
}

/**
 * Add an OptionValue.
 *
 * OptionValues are used to classify CRM entities (including Contacts, Groups and Actions).
 *
 * @param array $params
 *
 * @return array
 *   API result array
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
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
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
 * Deletes an existing ReportTemplate.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_report_template_delete($params) {
  require_once 'api/v3/OptionValue.php';
  return civicrm_api3_option_value_delete($params);
}

/**
 * Retrieve rows from a report template.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_report_template_getrows($params) {
  civicrm_api3_verify_one_mandatory($params, NULL, array('report_id', 'instance_id'));
  list($rows, $instance, $metadata) = _civicrm_api3_report_template_getrows($params);
  return civicrm_api3_create_success($rows, $params, 'ReportTemplate', 'getrows', CRM_Core_DAO::$_nullObject, $metadata);
}

/**
 * Get report template rows.
 *
 * @param array $params
 *
 * @return array
 * @throws API_Exception
 * @throws CiviCRM_API3_Exception
 */
function _civicrm_api3_report_template_getrows($params) {
  if (empty($params['report_id'])) {
    $params['report_id'] = civicrm_api3('report_instance', 'getvalue', array('id' => $params['instance_id'], 'return' => 'report_id'));
  }

  $class = (string) civicrm_api3('option_value', 'getvalue', array(
    'option_group_name' => 'report_template',
    'return' => 'name',
    'value' => $params['report_id'],
    )
  );

  $reportInstance = new $class();
  if (!empty($params['instance_id'])) {
    $reportInstance->setID($params['instance_id']);
  }
  $reportInstance->setParams($params);
  $reportInstance->noController = TRUE;
  $reportInstance->preProcess();
  $reportInstance->setDefaultValues(FALSE);
  $reportInstance->setParams(array_merge($reportInstance->getDefaultValues(), $params));
  $options = _civicrm_api3_get_options_from_params($params, TRUE, 'ReportTemplate', 'get');
  $reportInstance->setLimitValue($options['limit']);
  $reportInstance->setAddPaging(FALSE);
  $reportInstance->setOffsetValue($options['offset']);
  $reportInstance->beginPostProcessCommon();
  $sql = $reportInstance->buildQuery();
  $rows = $metadata = $requiredMetadata  = array();
  $reportInstance->buildRows($sql, $rows);
  $reportInstance->formatDisplay($rows);

  if (isset($params['options']) && !empty($params['options']['metadata'])) {
    $requiredMetadata = $params['options']['metadata'];
    if (in_array('title', $requiredMetadata)) {
      $metadata['metadata']['title'] = $reportInstance->getTitle();
    }
    if (in_array('labels', $requiredMetadata)) {
      foreach ($reportInstance->_columnHeaders as $key => $header) {
        // Would be better just to expect reports to provide titles but reports are not consistent so we anticipate empty
        //NB I think these are already translated
        $metadata['metadata']['labels'][$key] = !empty($header['title']) ? $header['title'] : '';
      }
    }
    if (in_array('sql', $requiredMetadata)) {
      $metadata['metadata']['sql'] = $reportInstance->getReportSql();
    }
  }
  return array($rows, $reportInstance, $metadata);
}

/**
 * Get statistics from a given report.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_report_template_getstatistics($params) {
  list($rows, $reportInstance, $metadata) = _civicrm_api3_report_template_getrows($params);
  $stats = $reportInstance->statistics($rows);
  return civicrm_api3_create_success($stats, $params, 'ReportTemplate', 'getstatistics', CRM_Core_DAO::$_nullObject, $metadata);
}
/**
 * Adjust metadata for template getrows action.
 *
 * @param array $params
 *   Input parameters.
 */
function _civicrm_api3_report_template_getrows_spec(&$params) {
  $params['report_id'] = array(
    'title' => 'Report ID - eg. member/lapse',
  );
}

/* @codingStandardsIgnoreStart
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
}
@codingStandardsIgnoreEnd */
