<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * File for the CiviCRM APIv3 batch functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Batch
 *
 */

/**
 * Save a Batch
 *
 * Allowed @params array keys are:
 * {@getfields batch_create}
 * @example BatchCreate.php
 *
 * @param $params
 *
 * @return array of newly created batch property values.
 * @access public
 */
function civicrm_api3_batch_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_batch_create_spec(&$params) {
  //@todo - the entity table field looks like it is not actually required & should probably be removed (or a description added if
  // it is actually required)
  $params['entity_table']['api.default'] = "civicrm_batch";
  $params['entity_table']['type'] = CRM_Utils_Type::T_STRING;
  $params['entity_table']['title'] = 'Batch Entity Table - remove?';

  $params['modified_date']['api.default'] = "now";
  $params['status_id']['api.required'] = 1;
  $params['title']['api.required'] = 1;
  $params['status_id']['api.required'] = 1;
}

/**
 * Get a Batch
 *
 * Allowed @params array keys are:
 * {@getfields batch_get}
 * @example BatchCreate.php
 *
 * @param $params
 *
 * @return array of retrieved batch property values.
 * @access public
 */
function civicrm_api3_batch_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a Batch
 *
 * Allowed @params array keys are:
 * {@getfields batch_delete}
 * @example BatchCreate.php
 *
 * @param $params
 *
 * @return array of deleted values.
 * @access public
 */
function civicrm_api3_batch_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
