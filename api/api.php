<?php

/**
 * File for the CiviCRM APIv3 API wrapper
 *
 * @package CiviCRM_APIv3
 * @subpackage API
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: api.php 30486 2010-11-02 16:12:09Z shot $
 */

/**
 * @param string $entity
 *   type of entities to deal with
 * @param string $action
 *   create, get, delete or some special action name.
 * @param array $params
 *   array to be passed to function
 */
function civicrm_api($entity, $action, $params, $extra = NULL) {
  $apiWrappers = array(CRM_Core_HTMLInputCoder::singleton());
  try {
    require_once ('api/v3/utils.php');
    require_once 'api/Exception.php';
    if (!is_array($params)) {
      throw new API_Exception('Input variable `params` is not an array', 2000);
    }
    _civicrm_api3_initialize();
    $errorScope = CRM_Core_TemporaryErrorScope::useException();
    $apiRequest = array();
    $apiRequest['entity'] = CRM_Utils_String::munge($entity);
    $apiRequest['action'] = CRM_Utils_String::munge($action);
    $apiRequest['version'] = civicrm_get_api_version($params);
    $apiRequest['params'] = $params;
    $apiRequest['extra'] = $extra;
    // look up function, file, is_generic
    $apiRequest += _civicrm_api_resolve($apiRequest);
    if (strtolower($action) == 'create' || strtolower($action) == 'delete') {
      $apiRequest['is_transactional'] = 1;
      $transaction = new CRM_Core_Transaction();
    }

    // support multi-lingual requests
    if ($language = CRM_Utils_Array::value('option.language', $params)) {
      _civicrm_api_set_locale($language);
    }

    _civicrm_api3_api_check_permission($apiRequest['entity'], $apiRequest['action'], $apiRequest['params']);

    // we do this before we
    _civicrm_api3_swap_out_aliases($apiRequest);
    if (strtolower($action) != 'getfields') {
      if (!CRM_Utils_Array::value('id', $apiRequest['params'])) {
        $apiRequest['params'] = array_merge(_civicrm_api3_getdefaults($apiRequest), $apiRequest['params']);
      }
      //if 'id' is set then only 'version' will be checked but should still be checked for consistency
      civicrm_api3_verify_mandatory($apiRequest['params'], NULL, _civicrm_api3_getrequired($apiRequest));
    }

    foreach ($apiWrappers as $apiWrapper) {
      $apiRequest = $apiWrapper->fromApiInput($apiRequest);
    }

    $function = $apiRequest['function'];
    if ($apiRequest['function'] && $apiRequest['is_generic']) {
      // Unlike normal API implementations, generic implementations require explicit
      // knowledge of the entity and action (as well as $params). Bundle up these bits
      // into a convenient data structure.
      $result = $function($apiRequest);
    }
    elseif ($apiRequest['function'] && !$apiRequest['is_generic']) {
      _civicrm_api3_validate_fields($apiRequest['entity'], $apiRequest['action'], $apiRequest['params']);

      $result = isset($extra) ? $function($apiRequest['params'], $extra) : $function($apiRequest['params']);
    }
    else {
      return civicrm_api3_create_error("API (" . $apiRequest['entity'] . "," . $apiRequest['action'] . ") does not exist (join the API team and implement it!)");
    }

    foreach ($apiWrappers as $apiWrapper) {
      $result = $apiWrapper->toApiOutput($apiRequest, $result);
    }

    if (CRM_Utils_Array::value('format.is_success', $apiRequest['params']) == 1) {
      if ($result['is_error'] === 0) {
        return 1;
      }
      else {
        return 0;
      }
    }
    if (CRM_Utils_Array::value('format.only_id', $apiRequest['params']) && isset($result['id'])) {
      return $result['id'];
    }
    if (CRM_Utils_Array::value('is_error', $result, 0) == 0) {
      _civicrm_api_call_nested_api($apiRequest['params'], $result, $apiRequest['action'], $apiRequest['entity'], $apiRequest['version']);
    }
    if (function_exists('xdebug_time_index')
      && CRM_Utils_Array::value('debug', $apiRequest['params'])
      // result would not be an array for getvalue
      && is_array($result)
    ) {
      $result['xdebug']['peakMemory'] = xdebug_peak_memory_usage();
      $result['xdebug']['memory'] = xdebug_memory_usage();
      $result['xdebug']['timeIndex'] = xdebug_time_index();
    }

    return $result;
  }
  catch(PEAR_Exception $e) {
    if (CRM_Utils_Array::value('format.is_success', $apiRequest['params']) == 1) {
      return 0;
    }
    $error = $e->getCause();
    if ($error instanceof DB_Error) {
      $data["error_code"] = DB::errorMessage($error->getCode());
      $data["sql"] = $error->getDebugInfo();
    }
    if (CRM_Utils_Array::value('debug', $apiRequest['params'])) {
      $data['debug_info'] = $error->getUserInfo();
      $data['trace'] = $e->getTraceAsString();
    }
    else{
      $data['tip'] = "add debug=1 to your API call to have more info about the error";
    }
    $err = civicrm_api3_create_error($e->getMessage(), $data, $apiRequest);
    if (CRM_Utils_Array::value('is_transactional', $apiRequest)) {
      $transaction->rollback();
    }
    return $err;
  }
  catch (API_Exception $e){
    if(!isset($apiRequest)){
      $apiRequest = array();
    }
    if (CRM_Utils_Array::value('format.is_success', CRM_Utils_Array::value('params',$apiRequest)) == 1) {
      return 0;
    }
    $data = $e->getExtraParams();
    $data['entity'] = CRM_Utils_Array::value('entity', $apiRequest);
    $data['action'] = CRM_Utils_Array::value('action', $apiRequest);
    $err = civicrm_api3_create_error($e->getMessage(), $data, $apiRequest, $e->getCode());
    if (CRM_Utils_Array::value('debug', CRM_Utils_Array::value('params',$apiRequest))
      && empty($data['trace']) // prevent recursion
    ) {
      $err['trace'] = $e->getTraceAsString();
    }
    if (CRM_Utils_Array::value('is_transactional', $apiRequest)) {
      $transaction->rollback();
    }
    return $err;
  }
  catch(Exception $e) {
    if (CRM_Utils_Array::value('format.is_success', $apiRequest['params']) == 1) {
      return 0;
    }
    $data = array();
    $err = civicrm_api3_create_error($e->getMessage(), $data, $apiRequest, $e->getCode());
    if (CRM_Utils_Array::value('debug', $apiRequest['params'])) {
      $err['trace'] = $e->getTraceAsString();
    }
    if (CRM_Utils_Array::value('is_transactional', $apiRequest)) {
      $transaction->rollback();
    }
    return $err;
  }
}

/**
 * Look up the implementation for a given API request
 *
 * @param $apiRequest array with keys:
 *  - entity: string, required
 *  - action: string, required
 *  - params: array
 *  - version: scalar, required
 *
 * @return array with keys
 *  - function: callback (mixed)
 *  - is_generic: boolean
 */
function _civicrm_api_resolve($apiRequest) {
  static $cache;
  $cachekey = strtolower($apiRequest['entity']) . ':' . strtolower($apiRequest['action']) . ':' . $apiRequest['version'];
  if (isset($cache[$cachekey])) {
    return $cache[$cachekey];
  }

  $camelName = _civicrm_api_get_camel_name($apiRequest['entity'], $apiRequest['version']);
  $actionCamelName = _civicrm_api_get_camel_name($apiRequest['action']);

  // Determine if there is an entity-specific implementation of the action
  $stdFunction = civicrm_api_get_function_name($apiRequest['entity'], $apiRequest['action'], $apiRequest['version']);
  if (function_exists($stdFunction)) {
    // someone already loaded the appropriate file
    // FIXME: This has the affect of masking bugs in load order; this is included to provide bug-compatibility
    $cache[$cachekey] = array('function' => $stdFunction, 'is_generic' => FALSE);
    return $cache[$cachekey];
  }

  $stdFiles = array(
    // By convention, the $camelName.php is more likely to contain the function, so test it first
    'api/v' . $apiRequest['version'] . '/' . $camelName . '.php',
    'api/v' . $apiRequest['version'] . '/' . $camelName . '/' . $actionCamelName . '.php',
  );
  foreach ($stdFiles as $stdFile) {
    require_once 'CRM/Utils/File.php';
    if (CRM_Utils_File::isIncludable($stdFile)) {
      require_once $stdFile;
      if (function_exists($stdFunction)) {
        $cache[$cachekey] = array('function' => $stdFunction, 'is_generic' => FALSE);
        return $cache[$cachekey];
      }
    }
  }

  // Determine if there is a generic implementation of the action
  require_once 'api/v3/Generic.php';
  # $genericFunction = 'civicrm_api3_generic_' . $apiRequest['action'];
  $genericFunction = civicrm_api_get_function_name('generic', $apiRequest['action'], $apiRequest['version']);
  $genericFiles = array(
    // By convention, the Generic.php is more likely to contain the function, so test it first
    'api/v' . $apiRequest['version'] . '/Generic.php',
    'api/v' . $apiRequest['version'] . '/Generic/' . $actionCamelName . '.php',
  );
  foreach ($genericFiles as $genericFile) {
    require_once 'CRM/Utils/File.php';
    if (CRM_Utils_File::isIncludable($genericFile)) {
      require_once $genericFile;
      if (function_exists($genericFunction)) {
        $cache[$cachekey] = array('function' => $genericFunction, 'is_generic' => TRUE);
        return $cache[$cachekey];
      }
    }
  }

  $cache[$cachekey] = array('function' => FALSE, 'is_generic' => FALSE);
  return $cache[$cachekey];
}

/**
 * Load/require all files related to an entity.
 *
 * This should not normally be called because it's does a file-system scan; it's
 * only appropriate when introspection is really required (eg for "getActions").
 *
 * @param string $entity
 * @return void
 */
function _civicrm_api_loadEntity($entity, $version = 3) {
  /*
  $apiRequest = array();
  $apiRequest['entity'] = $entity;
  $apiRequest['action'] = 'pretty sure it will never exist. Trick to [try to] force resolve to scan everywhere';
  $apiRequest['version'] = $version;
  // look up function, file, is_generic
  $apiRequest = _civicrm_api_resolve($apiRequest);
  */

  $camelName = _civicrm_api_get_camel_name($entity, $version);

  // Check for master entity file; to match _civicrm_api_resolve(), only load the first one
  require_once 'CRM/Utils/File.php';
  $stdFile = 'api/v' . $version . '/' . $camelName . '.php';
  if (CRM_Utils_File::isIncludable($stdFile)) {
    require_once $stdFile;
  }

  // Check for standalone action files; to match _civicrm_api_resolve(), only load the first one
  $loaded_files = array(); // array($relativeFilePath => TRUE)
  $include_dirs = array_unique(explode(PATH_SEPARATOR, get_include_path()));
  foreach ($include_dirs as $include_dir) {
    $action_dir = implode(DIRECTORY_SEPARATOR, array($include_dir, 'api', "v${version}", $camelName));
    if (! is_dir($action_dir)) {
      continue;
    }

    $iterator = new DirectoryIterator($action_dir);
    foreach ($iterator as $fileinfo) {
      $file = $fileinfo->getFilename();
      if (array_key_exists($file, $loaded_files)) {
        continue; // action provided by an earlier item on include_path
      }

      $parts = explode(".", $file);
      if (end($parts) == "php" && !preg_match('/Tests?\.php$/', $file) ) {
        require_once $action_dir . DIRECTORY_SEPARATOR . $file;
        $loaded_files[$file] = TRUE;
      }
    }
  }
}

/**
 *
 * @deprecated
 */
function civicrm_api_get_function_name($entity, $action, $version = NULL) {

  if (empty($version)) {
    $version = civicrm_get_api_version();
  }

  $entity = _civicrm_api_get_entity_name_from_camel($entity);
  return 'civicrm_api3' . '_' . $entity . '_' . $action;
}

/**
 * We must be sure that every request uses only one version of the API.
 *
 * @param $desired_version : array or integer
 *   One chance to set the version number.
 *   After that, this version number will be used for the remaining request.
 *   This can either be a number, or an array(.., 'version' => $version, ..).
 *   This allows to directly pass the $params array.
 */
function civicrm_get_api_version($desired_version = NULL) {

  if (is_array($desired_version)) {
    // someone gave the full $params array.
    $params = $desired_version;
    $desired_version = empty($params['version']) ? NULL : (int) $params['version'];
  }
  if (isset($desired_version) && is_integer($desired_version)) {
    $_version = $desired_version;
  }
  else {
    // we will set the default to version 3 as soon as we find that it works.
    $_version = 3;
  }
  return $_version;
}

/**
 * Check if the result is an error. Note that this function has been retained from
 * api v2 for convenience but the result is more standardised in v3 and param
 * 'format.is_success' => 1
 * will result in a boolean success /fail being returned if that is what you need.
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return boolean true if error, false otherwise
 * @static void
 * @access public
 */
function civicrm_error($result) {
  if (is_array($result)) {
    return (array_key_exists('is_error', $result) &&
      $result['is_error']
    ) ? TRUE : FALSE;
  }
  return FALSE;
}

function _civicrm_api_get_camel_name($entity, $version = NULL) {
  static $_map = NULL;

  if (empty($version)) {
    $version = civicrm_get_api_version();
  }

  if (isset($_map[$version][strtolower($entity)])) {
    return $_map[$version][strtolower($entity)];
  }

  $fragments = explode('_', $entity);
  foreach ($fragments as & $fragment) {
    $fragment = ucfirst($fragment);
  }
  // Special case: UFGroup, UFJoin, UFMatch, UFField
  if ($fragments[0] === 'Uf') {
    $fragments[0] = 'UF';
  }
  return implode('', $fragments);
}

/**
 * Call any nested api calls
 */
function _civicrm_api_call_nested_api(&$params, &$result, $action, $entity, $version) {
  $entity = _civicrm_api_get_entity_name_from_camel($entity);
  if(strtolower($action) == 'getsingle'){
    // I don't understand the protocol here, but we don't want
    // $result to be a recursive array
    // $result['values'][0] = $result;
    $oldResult = $result;
    $result = array('values' => array(0 => $oldResult));
  }
  foreach ($params as $field => $newparams) {
    if ((is_array($newparams) || $newparams === 1) && $field <> 'api.has_parent' && substr($field, 0, 3) == 'api') {

      // 'api.participant.delete' => 1 is a valid options - handle 1 instead of an array
      if ($newparams === 1) {
        $newparams = array('version' => $version);
      }
      // can be api_ or api.
      $separator = $field[3];
      if (!($separator == '.' || $separator == '_')) {
        continue;
      }
      $subAPI = explode($separator, $field);

      $subaction = empty($subAPI[2]) ? $action : $subAPI[2];
      $subParams = array(
        'debug' => CRM_Utils_Array::value('debug', $params),
      );
      $subEntity = $subAPI[1];

      foreach ($result['values'] as $idIndex => $parentAPIValues) {

        if (strtolower($subEntity) != 'contact') {
          //contact spits the dummy at activity_id so what else won't it like?
          //set entity_id & entity table based on the parent's id & entity. e.g for something like
          //note if the parent call is contact 'entity_table' will be set to 'contact' & 'id' to the contact id from
          //the parent call.
          //in this case 'contact_id' will also be set to the parent's id
          $subParams["entity_id"] = $parentAPIValues['id'];
          $subParams['entity_table'] = 'civicrm_' . _civicrm_api_get_entity_name_from_camel($entity);
          $subParams[strtolower($entity) . "_id"] = $parentAPIValues['id'];
        }
        if (strtolower($entity) != 'contact' && CRM_Utils_Array::value(strtolower($subEntity . "_id"), $parentAPIValues)) {
          //e.g. if event_id is in the values returned & subentity is event then pass in event_id as 'id'
          //don't do this for contact as it does some wierd things like returning primary email &
          //thus limiting the ability to chain email
          //TODO - this might need the camel treatment
          $subParams['id'] = $parentAPIValues[$subEntity . "_id"];
        }

        if (CRM_Utils_Array::value('entity_table', $result['values'][$idIndex]) == $subEntity) {
          $subParams['id'] = $result['values'][$idIndex]['entity_id'];
        }
        // if we are dealing with the same entity pass 'id' through (useful for get + delete for example)
        if (strtolower($entity) == strtolower($subEntity)) {
          $subParams['id'] = $result['values'][$idIndex]['id'];
        }


        $subParams['version'] = $version;
        if(!empty($params['check_permissions'])){
          $subParams['check_permissions'] = $params['check_permissions'];
        }
        $subParams['sequential'] = 1;
        $subParams['api.has_parent'] = 1;
        if (array_key_exists(0, $newparams)) {
          // it is a numerically indexed array - ie. multiple creates
          foreach ($newparams as $entity => $entityparams) {
            $subParams = array_merge($subParams, $entityparams);
            _civicrm_api_replace_variables($subAPI[1], $subaction, $subParams, $result['values'][$idIndex], $separator);
            $result['values'][$result['id']][$field][] = civicrm_api($subEntity, $subaction, $subParams);
            if ($result['is_error'] === 1) {
              throw new Exception($subEntity . ' ' . $subaction . 'call failed with' . $result['error_message']);
            }
          }
        }
        else {

          $subParams = array_merge($subParams, $newparams);
          _civicrm_api_replace_variables($subAPI[1], $subaction, $subParams, $result['values'][$idIndex], $separator);
          $result['values'][$idIndex][$field] = civicrm_api($subEntity, $subaction, $subParams);
          if (!empty($result['is_error'])) {
            throw new Exception($subEntity . ' ' . $subaction . 'call failed with' . $result['error_message']);
          }
        }
      }
    }
  }
  if(strtolower($action) == 'getsingle'){
    $result = $result['values'][0];
  }
}

/**
 * Swap out any $values vars - ie. the value after $value is swapped for the parent $result
 * 'activity_type_id' => '$value.testfield',
   'tag_id'  => '$value.api.tag.create.id',
    'tag1_id' => '$value.api.entity.create.0.id'
 */
function _civicrm_api_replace_variables($entity, $action, &$params, &$parentResult, $separator = '.') {


  foreach ($params as $field => $value) {

    if (is_string($value) && substr($value, 0, 6) == '$value') {
      $valuesubstitute = substr($value, 7);

      if (!empty($parentResult[$valuesubstitute])) {
        $params[$field] = $parentResult[$valuesubstitute];
      }
      else {

        $stringParts = explode($separator, $value);
        unset($stringParts[0]);

        $fieldname = array_shift($stringParts);

        //when our string is an array we will treat it as an array from that . onwards
        $count = count($stringParts);
        while ($count > 0) {
          $fieldname .= "." . array_shift($stringParts);
          if (array_key_exists($fieldname, $parentResult) && is_array($parentResult[$fieldname])) {
            $arrayLocation = $parentResult[$fieldname];
            foreach ($stringParts as $key => $value) {
              $arrayLocation = CRM_Utils_Array::value($value, $arrayLocation);
            }
            $params[$field] = $arrayLocation;
          }
          $count = count($stringParts);
        }
      }
    }
  }
}

/**
 * Convert possibly camel name to underscore separated entity name
 *
 * @param string $entity entity name in various formats e.g. Contribution, contribution, OptionValue, option_value, UFJoin, uf_join
 * @return string $entity entity name in underscore separated format
 *
 * FIXME: Why isn't this called first thing in civicrm_api wrapper?
 */
function _civicrm_api_get_entity_name_from_camel($entity) {
  if ($entity == strtolower($entity)) {
    return $entity;
  }
  else {
    $entity = ltrim(strtolower(str_replace('U_F',
          'uf',
          // That's CamelCase, beside an odd UFCamel that is expected as uf_camel
          preg_replace('/(?=[A-Z])/', '_$0', $entity)
        )), '_');
  }
  return $entity;
}

/**
 * Having a DAO object find the entity name
 * @param object $bao DAO being passed in
 */
function _civicrm_api_get_entity_name_from_dao($bao){
  $daoName = str_replace("BAO", "DAO", get_class($bao));
  return _civicrm_api_get_entity_name_from_camel(CRM_Core_DAO_AllCoreTables::getFullName($daoName));
}


/**
 * Sets the tsLocale and dbLocale for multi-lingual sites.
 * Some code duplication from CRM/Core/BAO/ConfigSetting.php retrieve()
 * to avoid regressions from refactoring.
 */
function _civicrm_api_set_locale($lcMessagesRequest) {
  // We must validate whether the locale is valid, otherwise setting a bad
  // dbLocale could probably lead to sql-injection.
  $domain = new CRM_Core_DAO_Domain();
  $domain->id = CRM_Core_Config::domainID();
  $domain->find(TRUE);

  if ($domain->config_backend) {
    $defaults = unserialize($domain->config_backend);

    // are we in a multi-language setup?
    $multiLang = $domain->locales ? TRUE : FALSE;
    $lcMessages = NULL;

    // on multi-lang sites based on request and civicrm_uf_match
    if ($multiLang) {
      $languageLimit = array();
      if (array_key_exists('languageLimit', $defaults) && is_array($defaults['languageLimit'])) {
        $languageLimit = $defaults['languageLimit'];
      }

      if (in_array($lcMessagesRequest, array_keys($languageLimit))) {
        $lcMessages = $lcMessagesRequest;
      }
      else {
        throw new API_Exception(ts('Language not enabled: %1', array(1 => $lcMessagesRequest)));
      }
    }

    global $dbLocale;

    // set suffix for table names - use views if more than one language
    if ($lcMessages) {
      $dbLocale = $multiLang && $lcMessages ? "_{$lcMessages}" : '';

      // FIXME: an ugly hack to fix CRM-4041
      global $tsLocale;
      $tsLocale = $lcMessages;
    }
  }
}
