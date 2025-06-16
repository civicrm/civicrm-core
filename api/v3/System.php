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
  Civi::rebuild([
    '*' => TRUE,
    'triggers' => $params['triggers'] ?? FALSE,
    'sessions' => $params['session'] ?? FALSE,
  ])->execute();
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
  $params['triggers'] = [
    'title' => 'Triggers',
    'description' => 'rebuild triggers (boolean)',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];
  $params['session'] = [
    'title' => 'Sessions',
    'description' => 'refresh sessions (boolean)',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];
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
  $spec['id'] = [
    'title' => 'ID',
    'description' => 'Not a real identifier - do not use',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['name'] = [
    'title' => 'Name',
    'description' => 'Unique identifier',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['title'] = [
    'title' => 'Title',
    'description' => 'Short title text',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['message'] = [
    'title' => 'Message',
    'description' => 'Long description html',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['help'] = [
    'title' => 'Help',
    'description' => 'Optional extra help (html string)',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['severity'] = [
    'title' => 'Severity',
    'description' => 'Psr\Log\LogLevel string',
    'type' => CRM_Utils_Type::T_STRING,
    'options' => array_combine(CRM_Utils_Check::getSeverityList(), CRM_Utils_Check::getSeverityList()),
  ];
  $spec['severity_id'] = [
    'title' => 'Severity ID',
    'description' => 'Integer representation of Psr\Log\LogLevel',
    'type' => CRM_Utils_Type::T_INT,
    'options' => CRM_Utils_Check::getSeverityList(),
  ];
  $spec['is_visible'] = [
    'title' => 'is visible',
    'description' => '0 if message has been hidden by the user',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];
  $spec['hidden_until'] = [
    'title' => 'Hidden_until',
    'description' => 'When will hidden message be visible again?',
    'type' => CRM_Utils_Type::T_DATE,
  ];
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
 * @throws CRM_Core_Exception
 */
function civicrm_api3_system_check($params) {
  // array(array('name'=> $, 'severity'=>$, ...))
  $id = 1;
  $returnValues = $fields = [];
  _civicrm_api3_system_check_spec($fields);

  // array(CRM_Utils_Check_Message)
  $messages = CRM_Utils_Check::checkAll();

  foreach ($messages as $msg) {
    $returnValues[] = $msg->toArray() + ['id' => $id++];
  }

  return _civicrm_api3_basic_array_get('systemCheck', $params, $returnValues, "id", array_keys($fields));
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
    $params['context'] = [];
  }
  $specialFields = ['contact_id', 'hostname'];
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
  $params['level'] = [
    'title' => 'Log Level',
    'description' => 'Log level as described in PSR3 (info, debug, warning etc)',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => TRUE,
  ];
  $params['message'] = [
    'title' => 'Log Message',
    'description' => 'Standardised message string, you can also ',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => TRUE,
  ];
  $params['context'] = [
    'title' => 'Log Context',
    'description' => 'An array of additional data to store.',
    'type' => CRM_Utils_Type::T_LONGTEXT,
    'api.default' => [],
  ];
  $params['contact_id'] = [
    'title' => 'Log Contact ID',
    'description' => 'Optional ID of relevant contact',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['hostname'] = [
    'title' => 'Log Hostname',
    'description' => 'Optional name of host',
    'type' => CRM_Utils_Type::T_STRING,
  ];
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
  $returnValues = [
    [
      // deprecated in favor of civi.version
      'version' => CRM_Utils_System::version(),
      // deprecated in favor of cms.type
      'uf' => CIVICRM_UF,
      'php' => [
        'version' => phpversion(),
        'time' => time(),
        'tz' => date_default_timezone_get(),
        'sapi' => php_sapi_name(),
        'extensions' => get_loaded_extensions(),
        'ini' => _civicrm_api3_system_get_redacted_ini(),
      ],
      'mysql' => [
        'version' => CRM_Core_DAO::singleValueQuery('SELECT @@version'),
        'time' => CRM_Core_DAO::singleValueQuery('SELECT unix_timestamp()'),
        'vars' => _civicrm_api3_system_get_redacted_mysql(),
      ],
      'cms' => [
        'version' => $config->userSystem->getVersion(),
        'type' => CIVICRM_UF,
        'modules' => CRM_Core_Module::collectStatuses($config->userSystem->getModules()),
      ],
      'civi' => [
        'version' => CRM_Utils_System::version(),
        'dev' => (\Civi::settings()->get('environment') === 'Development'),
        'components' => array_keys(CRM_Core_Component::getEnabledComponents()),
        'extensions' => preg_grep('/^uninstalled$/', CRM_Extension_System::singleton()->getManager()->getStatuses(), PREG_GREP_INVERT),
        'multidomain' => CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_domain') > 1,
        'settings' => _civicrm_api3_system_get_redacted_settings(),
        'exampleUrl' => CRM_Utils_System::url('civicrm/example', NULL, TRUE, NULL, FALSE),
      ],
      'http' => [
        'software' => $_SERVER['SERVER_SOFTWARE'] ?? NULL,
        'forwarded' => !empty($_SERVER['HTTP_X_FORWARDED_FOR']) || !empty($_SERVER['X_FORWARDED_PROTO']),
        'port' => (empty($_SERVER['SERVER_PORT']) || $_SERVER['SERVER_PORT'] == 80 || $_SERVER['SERVER_PORT'] == 443) ? 'Standard' : 'Nonstandard',
      ],
      'os' => [
        'type' => php_uname('s'),
        'release' => php_uname('r'),
        'version' => php_uname('v'),
        'machine' => php_uname('m'),
      ],
    ],
  ];

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
  $result = [];
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
  $result = [];
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

/**
 * Get redacted settings.
 *
 * @return array
 * @throws CRM_Core_Exception
 */
function _civicrm_api3_system_get_redacted_settings() {
  static $whitelist = NULL;
  if ($whitelist === NULL) {
    $whitelist = _civicrm_api3_system_get_whitelist(__DIR__ . '/System/setting-whitelist.txt');
  }

  $apiResult = civicrm_api3('Setting', 'get', []);
  $result = [];
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

/**
 * Update log table structures.
 *
 * This updates the engine type if defined in the hook and changes the field type
 * for log_conn_id to reflect CRM-18193.
 */
function civicrm_api3_system_updatelogtables($params) {
  $schema = new CRM_Logging_Schema();
  $updatedTablesCount = $schema->updateLogTableSchema($params);
  return civicrm_api3_create_success($updatedTablesCount);
}

/**
 * Update log table structures.
 *
 * This updates the engine type if defined in the hook and changes the field type
 * for log_conn_id to reflect CRM-18193.
 *
 * @param array $params
 *
 * @return array
 *
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_system_utf8conversion($params) {
  $params['patterns'] = explode(',', $params['patterns']);
  $params['databases'] = empty($params['databases']) ? NULL : explode(',', $params['databases']);
  if (CRM_Core_BAO_SchemaHandler::migrateUtf8mb4(
    $params['is_revert'],
    $params['patterns'],
    $params['databases']
    )
  ) {
    return civicrm_api3_create_success(1);
  }
  throw new CRM_Core_Exception('Conversion failed');
}

/**
 * Metadata for conversion function.
 *
 * @param array $params
 */
function _civicrm_api3_system_utf8conversion_spec(&$params) {
  $params['is_revert'] = [
    'title' => ts('Revert back from UTF8MB4 to UTF8?'),
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => FALSE,
  ];
  $params['patterns'] = [
    'title' => ts('CSV list of table patterns (defaults to "civicrm\_%")'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => 'civicrm\_%',
  ];
  $params['databases'] = [
    'title' => ts('CSV list of database names (defaults to CiviCRM database)'),
    'type' => CRM_Utils_Type::T_STRING,
  ];
}

/**
 * Adjust Metadata for Flush action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_system_updatelogtables_spec(&$params) {
  $params['updateChangedEngineConfig'] = [
    'title' => 'Update Engine Config if changed?',
    'description' => 'By default, we only update if the ENGINE has changed, set this to TRUE to update if the ENGINE_CONFIG has changed.',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => FALSE,
  ];
  $params['forceEngineMigration'] = [
    'title' => 'Force storage engine to upgrade to InnoDB?',
    'description' => 'Older versions of CiviCRM used the ARCHIVE engine by default. Set this to TRUE to migrate the engine to the new default.',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => FALSE,
  ];
}

/**
 * Update indexes.
 *
 * This adds any indexes that exist in the schema but not the database.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_system_updateindexes(array $params):array {
  $tables = empty($params['tables']) ? FALSE : (array) $params['tables'];
  CRM_Core_BAO_SchemaHandler::createMissingIndices(CRM_Core_BAO_SchemaHandler::getMissingIndices(TRUE, $tables));
  return civicrm_api3_create_success(1);
}

/**
 * Declare metadata for api System.getmissingindices
 *
 * @param array $params
 */
function _civicrm_api3_system_updateindexes_spec(array &$params) {
  $params['tables'] = [
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => FALSE,
    'title' => ts('Optional tables filter'),
  ];
}

/**
 * Get an array of indices that should be defined but are not.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_system_getmissingindices($params) {
  $tables = empty($params['tables']) ? FALSE : (array) $params['tables'];
  $indices = CRM_Core_BAO_SchemaHandler::getMissingIndices(FALSE, $tables);
  return civicrm_api3_create_success($indices);
}

/**
 * Declare metadata for api System.getmissingindices
 *
 * @param array $params
 */
function _civicrm_api3_system_getmissingindices_spec(&$params) {
  $params['tables'] = [
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => FALSE,
    'title' => ts('Optional tables filter'),
  ];
}

/**
 * Creates missing log tables.
 *
 * CRM-20838 - This adds any missing log tables into the database.
 */
function civicrm_api3_system_createmissinglogtables() {
  $schema = new CRM_Logging_Schema();
  $missingLogTables = $schema->getMissingLogTables();
  if (!empty($missingLogTables)) {
    foreach ($missingLogTables as $tableName) {
      $schema->fixSchemaDifferencesFor($tableName);
    }
  }
  return civicrm_api3_create_success(1);
}

/**
 * Rebuild Multilingual Schema
 *
 */
function civicrm_api3_system_rebuildmultilingualschema() {
  $locales = CRM_Core_I18n::getMultilingual();
  if ($locales) {
    CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales);
    return civicrm_api3_create_success(1);
  }
  else {
    throw new CRM_Core_Exception('Cannot call rebuild Multilingual schema on non Multilingual database');
  }
}
