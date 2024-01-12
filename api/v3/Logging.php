<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
 *   API Success Array
 * @throws \CRM_Core_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function civicrm_api3_logging_revert($params) {
  $schema = new CRM_Logging_Schema();
  $reverter = new CRM_Logging_Reverter($params['log_conn_id'], $params['log_date'] ?? NULL);
  $tables = !empty($params['tables']) ? (array) $params['tables'] : $schema->getLogTablesForContact();
  $reverter->calculateDiffsFromLogConnAndDate($tables);
  $reverter->revert();
  return civicrm_api3_create_success(1);
}

/**
 * Get a log change.
 *
 * @param array $params
 *
 * @throws \CRM_Core_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function _civicrm_api3_logging_revert_spec(&$params) {
  $params['log_conn_id'] = [
    'title' => 'Logging Connection ID',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => TRUE,
  ];
  $params['log_date'] = [
    'title' => 'Logging Timestamp',
    'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
  ];
  $params['interval'] = [
    'title' => ts('Interval (required if date is included)'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => '10 SECOND',
    'description' => ts('Used when log_date is passed in'),
  ];

  $params['tables'] = [
    'title' => ts('Tables to revert'),
    'type' => CRM_Utils_Type::T_STRING,
    'description' => ts('Tables to revert, if not set all contact-referring entities will be reverted'),
  ];
}

/**
 * Get a log change.
 *
 * @param array $params
 *
 * @return array
 *   API Success Array
 * @throws \CRM_Core_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function civicrm_api3_logging_get($params) {
  $schema = new CRM_Logging_Schema();
  $interval = (empty($params['log_date'])) ? NULL : $params['interval'];
  $differ = new CRM_Logging_Differ($params['log_conn_id'], $params['log_date'] ?? NULL, $interval);
  $tables = !empty($params['tables']) ? (array) $params['tables'] : $schema->getLogTablesForContact();
  return civicrm_api3_create_success($differ->getAllChangesForConnection($tables));
}

/**
 * Get a log change.
 *
 * @param array $params
 *
 * @throws \CRM_Core_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function _civicrm_api3_logging_get_spec(&$params) {
  $params['log_conn_id'] = [
    'title' => 'Logging Connection ID',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => TRUE,
  ];
  $params['log_date'] = [
    'title' => 'Logging Timestamp',
    'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
  ];
  $params['interval'] = [
    'title' => ts('Interval (required if date is included)'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => '10 SECOND',
    'description' => ts('Used when log_date is passed in'),
  ];
  $params['tables'] = [
    'title' => ts('Tables to query'),
    'type' => CRM_Utils_Type::T_STRING,
    'description' => ts('Tables to query, if not set all contact-referring entities will be queried'),
  ];
}
