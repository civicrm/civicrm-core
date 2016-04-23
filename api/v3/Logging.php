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
 * This api exposes functionality for interacting with the logging functionality.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Revert a log change.
 *
 * @param array $params
 *
 * @return array
 *    API Success Array
 * @throws \API_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function civicrm_api3_logging_revert($params) {
  $schema = new CRM_Logging_Schema();
  $reverter = new CRM_Logging_Reverter($params['log_conn_id'], CRM_Utils_Array::value('log_date', $params));
  $reverter->calculateDiffsFromLogConnAndDate($schema->getLogTablesForContact());
  $reverter->revert();
  return civicrm_api3_create_success(1);
}

/**
 * Get a log change.
 *
 * @param array $params
 *
 * @throws \API_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function _civicrm_api3_logging_revert_spec(&$params) {
  $params['log_conn_id'] = array(
    'title' => 'Logging Connection ID',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => TRUE,
  );
  $params['log_date'] = array(
    'title' => 'Logging Timestamp',
    'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
  );
  $params['interval'] = array(
    'title' => ts('Interval (required if date is included)'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => '10 SECOND',
    'description' => ts('Used when log_date is passed in'),
  );
}

/**
 * Get a log change.
 *
 * @param array $params
 *
 * @return array
 *    API Success Array
 * @throws \API_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function civicrm_api3_logging_get($params) {
  $schema = new CRM_Logging_Schema();
  $interval = (empty($params['log_date'])) ? NULL : $params['interval'];
  $differ = new CRM_Logging_Differ($params['log_conn_id'], CRM_Utils_Array::value('log_date', $params), $interval);
  return civicrm_api3_create_success($differ->getAllChangesForConnection($schema->getLogTablesForContact()));
}

/**
 * Get a log change.
 *
 * @param array $params
 *
 * @throws \API_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function _civicrm_api3_logging_get_spec(&$params) {
  $params['log_conn_id'] = array(
    'title' => 'Logging Connection ID',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => TRUE,
  );
  $params['log_date'] = array(
    'title' => 'Logging Timestamp',
    'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
  );
  $params['interval'] = array(
    'title' => ts('Interval (required if date is included)'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => '10 SECOND',
    'description' => ts('Used when log_date is passed in'),
  );
}
