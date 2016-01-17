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
  $config = CRM_Core_Config::singleton();
  $returnValues = array(
    array(
      'version' => CRM_Utils_System::version(), // deprecated in favor of civi.version
      'uf' => CIVICRM_UF, // deprecated in favor of cms.type
      'php' => array(
        'version' => phpversion(),
        'time' => time(),
        'tz' => date_default_timezone_get(),
        'sapi' => php_sapi_name(),
        'extensions' => get_loaded_extensions(),
        'ini' => _civicrm_api3_system_get_redacted_ini(),
      ),
      'mysql' => array(
        'version' => CRM_Core_DAO::singleValueQuery('SELECT @@version'),
        'time' => CRM_Core_DAO::singleValueQuery('SELECT unix_timestamp()'),
        'vars' => _civicrm_api3_system_get_redacted_mysql(),
      ),
      'cms' => array(
        'version' => $config->userSystem->getVersion(),
        'type' => CIVICRM_UF,
        'modules' => CRM_Core_Module::collectStatuses($config->userSystem->getModules()),
      ),
      'civi' => array(
        'version' => CRM_Utils_System::version(),
        'dev' => (bool) CRM_Utils_System::isDevelopment(),
        'components' => array_keys(CRM_Core_Component::getEnabledComponents()),
        'extensions' => preg_grep(
          '/^uninstalled$/',
          CRM_Extension_System::singleton()->getManager()->getStatuses(),
          PREG_GREP_INVERT
        ),
        'multidomain' => CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_domain') > 1,
        'settings' => _civicrm_api3_system_get_redacted_settings(),
        'exampleUrl' => CRM_Utils_System::url('civicrm/example', NULL, TRUE, NULL, FALSE),
      ),
      'http' => array(
        'software' => CRM_Utils_Array::value('SERVER_SOFTWARE', $_SERVER),
        'forwarded' => !empty($_SERVER['HTTP_X_FORWARDED_FOR']) || !empty($_SERVER['X_FORWARDED_PROTO']),
        'port' => (empty($_SERVER['SERVER_PORT']) || $_SERVER['SERVER_PORT'] == 80 || $_SERVER['SERVER_PORT'] == 443) ? 'Standard' : 'Nonstandard',
      ),
      'os' => array(
        'type' => php_uname('s'),
        'release' => php_uname('r'),
        'version' => php_uname('v'),
        'machine' => php_uname('m'),
      ),
    ),
  );

  return civicrm_api3_create_success($returnValues, $params, 'System', 'get');
}

/**
 * Generate a sanitized/anonymized/redacted dump of the PHP configuration.
 *
 * Some INI fields contain site-identifying information (SII) -- e.g. URLs,
 * hostnames, file paths, IP addresses, passwords, or free-form comments
 * could be used to identify a site or gain access to its resources.
 *
 * A number of INI fields have been examined to determine whether they
 * contain SII. Approved fields are put in a whitelist; all other fields
 * are redacted.
 *
 * Redaction hides the substance of a field but does not completely omit
 * all information. Consider the field 'mail.log' - setting this field
 * has a functional effect (it enables or disables the logging behavior)
 * and also points to particular file. Empty values (FALSE/NULL/0/"")
 * will pass through redaction, but all other values will be replaced
 * by a string (eg "REDACTED"). This roughly indicates whether the
 * option is enabled/disabled without giving away its content.
 *
 * @return array
 */
function _civicrm_api3_system_get_redacted_ini() {
  static $whitelist = NULL;
  if ($whitelist === NULL) {
    $whitelist = _civicrm_api3_system_get_whitelist(__DIR__ . '/System/ini-whitelist.txt');
  }

  $inis = ini_get_all(NULL, FALSE);
  $result = array();
  foreach ($inis as $k => $v) {
    if (empty($v) || in_array($k, $whitelist)) {
      $result[$k] = $v;
    }
    else {
      $result[$k] = 'REDACTED';
    }
  }

  return $result;
}

/**
 * Generate ae sanitized/anonymized/redacted dump of MySQL configuration.
 *
 * @return array
 * @see _civicrm_api3_system_get_redacted_ini
 */
function _civicrm_api3_system_get_redacted_mysql() {
  static $whitelist = NULL;
  if ($whitelist === NULL) {
    $whitelist = _civicrm_api3_system_get_whitelist(__DIR__ . '/System/mysql-whitelist.txt');
  }

  $inis = ini_get_all(NULL, FALSE);
  $result = array();
  $dao = CRM_Core_DAO::executeQuery('SHOW VARIABLES');
  while ($dao->fetch()) {
    if (empty($dao->Variable_name) || in_array($dao->Variable_name, $whitelist)) {
      $result[$dao->Variable_name] = $dao->Value;
    }
    else {
      $result[$dao->Variable_name] = 'REDACTED';
    }
  }

  return $result;
}

function _civicrm_api3_system_get_redacted_settings() {
  static $whitelist = NULL;
  if ($whitelist === NULL) {
    $whitelist = _civicrm_api3_system_get_whitelist(__DIR__ . '/System/setting-whitelist.txt');
  }

  $apiResult = civicrm_api3('Setting', 'get', array());
  $result = array();
  foreach ($apiResult['values'] as $settings) {
    foreach ($settings as $key => $value) {
      if (in_array($key, $whitelist)) {
        $result[$key] = $value;
      }
    }
  }

  return $result;
}

/**
 * Read a whitelist.
 *
 * @param string $whitelistFile
 *   Name of a file. Each line is a field name. Comments begin with "#".
 * @return array
 */
function _civicrm_api3_system_get_whitelist($whitelistFile) {
  $whitelist = array_filter(
    explode("\n", file_get_contents($whitelistFile)),
    function ($k) {
      return !empty($k) && !preg_match('/^\s*#/', $k);
    }
  );
  return $whitelist;
}
