<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * This api exposes CiviCRM system functionality.
 *
 * Includes caching, logging, and checking system functionality.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Flush all system caches.
 *
 * @param array $params
 *   Input parameters.
 *   - triggers: bool, whether to drop/create SQL triggers; default: FALSE
 *   - session:  bool, whether to reset the CiviCRM session data; default: FALSE
 *
 * @return array
 */
function civicrm_api3_system_flush($params) {
  CRM_Core_Invoke::rebuildMenuAndCaches(
    CRM_Utils_Array::value('triggers', $params, FALSE),
    CRM_Utils_Array::value('session', $params, FALSE)
  );
  return civicrm_api3_create_success();
}

/**
 * Adjust Metadata for Flush action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_system_flush_spec(&$params) {
  $params['triggers'] = array(
    'title' => 'Triggers',
    'description' => 'rebuild triggers (boolean)',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  );
  $params['session'] = array(
    'title' => 'Sessions',
    'description' => 'refresh sessions (boolean)',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  );
}

/**
 * System.Check API specification (optional).
 *
 * This is used for documentation and validation.
 *
 * @param array $spec
 *   Description of fields supported by this API call.
 *
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_system_check_spec(&$spec) {
  // $spec['magicword']['api.required'] = 1;
}

/**
 * System Check API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor; return items are alert codes/messages
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_system_check($params) {
  $returnValues = array();
  foreach (CRM_Utils_Check::singleton()->checkAll() as $message) {
    $returnValues[] = $message->toArray();
  }

  // Spec: civicrm_api3_create_success($values = 1, $params = array(), $entity = NULL, $action = NULL)
  return civicrm_api3_create_success($returnValues, $params, 'System', 'Check');
}

/**
 * Log entry to system log table.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_system_log($params) {
  $log = new CRM_Utils_SystemLogger();
  // This part means fields with separate db storage are accepted as params which kind of seems more intuitive to me
  // because I felt like not doing this required a bunch of explanation in the spec function - but perhaps other won't see it as helpful?
  if (!isset($params['context'])) {
    $params['context'] = array();
  }
  $specialFields = array('contact_id', 'hostname');
  foreach ($specialFields as $specialField) {
    if (isset($params[$specialField]) && !isset($params['context'])) {
      $params['context'][$specialField] = $params[$specialField];
    }
  }
  $returnValues = $log->log($params['level'], $params['message'], $params['context']);
  return civicrm_api3_create_success($returnValues, $params, 'System', 'Log');
}

/**
 * Metadata for log function.
 *
 * @param array $params
 */
function _civicrm_api3_system_log_spec(&$params) {
  $params['level'] = array(
    'title' => 'Log Level',
    'description' => 'Log level as described in PSR3 (info, debug, warning etc)',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => TRUE,
  );
  $params['message'] = array(
    'title' => 'Log Message',
    'description' => 'Standardised message string, you can also ',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => TRUE,
  );
  $params['context'] = array(
    'title' => 'Log Context',
    'description' => 'An array of additional data to store.',
    'type' => CRM_Utils_Type::T_LONGTEXT,
    'api.default' => array(),
  );
  $params['contact_id'] = array(
    'title' => 'Log Contact ID',
    'description' => 'Optional ID of relevant contact',
    'type' => CRM_Utils_Type::T_INT,
  );
  $params['hostname'] = array(
    'title' => 'Log Hostname',
    'description' => 'Optional name of host',
    'type' => CRM_Utils_Type::T_STRING,
  );
}

/**
 * System.Get API.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_system_get($params) {
  $returnValues = array(
    array(
      'version' => CRM_Utils_System::version(),
      'uf' => CIVICRM_UF,
    ),
  );
  return civicrm_api3_create_success($returnValues, $params, 'System', 'get');
}
