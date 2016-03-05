<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * This api exposes CiviCRM report instances.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Retrieve a report instance.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_report_instance_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Add or update a report instance.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_report_instance_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_report_instance_create_spec(&$params) {
  $params['report_id']['api.required'] = 1;
  $params['title']['api.required'] = 1;
  $params['view_mode']['api.default'] = 'view';
  $params['view_mode']['title'] = ts('View Mode for Navigation URL');
  $params['view_mode']['type'] = CRM_Utils_Type::T_STRING;
  $params['view_mode']['options'] = array(
    'view' => ts('View'),
    'criteria' => ts('Show Criteria'),
  );
}

/**
 * Deletes an existing ReportInstance.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_report_instance_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
