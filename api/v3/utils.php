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
 * CiviCRM APIv3 utility functions.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Initialize CiviCRM - should be run at the start of each API function.
 */
function _civicrm_api3_initialize() {
  require_once 'CRM/Core/ClassLoader.php';
  CRM_Core_ClassLoader::singleton()->register();
  CRM_Core_Config::singleton();
}

/**
 * Wrapper Function for civicrm_verify_mandatory to make it simple to pass either / or fields for checking.
 *
 * @param array $params
 *   Array of fields to check.
 * @param array $daoName
 *   String DAO to check for required fields (create functions only).
 * @param array $keyoptions
 *   List of required fields options. One of the options is required.
 */
function civicrm_api3_verify_one_mandatory($params, $daoName = NULL, $keyoptions = array()) {
  $keys = array(array());
  foreach ($keyoptions as $key) {
    $keys[0][] = $key;
  }
  civicrm_api3_verify_mandatory($params, $daoName, $keys);
}

/**
 * Check mandatory fields are included.
 *
 * @param array $params
 *   Array of fields to check.
 * @param array $daoName
 *   String DAO to check for required fields (create functions only).
 * @param array $keys
 *   List of required fields. A value can be an array denoting that either this or that is required.
 * @param bool $verifyDAO
 *
 * @throws \API_Exception
 */
function civicrm_api3_verify_mandatory($params, $daoName = NULL, $keys = array(), $verifyDAO = TRUE) {
  $unmatched = array();
  if ($daoName != NULL && $verifyDAO && empty($params['id'])) {
    $unmatched = _civicrm_api3_check_required_fields($params, $daoName, TRUE);
    if (!is_array($unmatched)) {
      $unmatched = array();
    }
  }

  if (!empty($params['id'])) {
    $keys = array('version');
  }
  else {
    if (!in_array('version', $keys)) {
      // required from v3 onwards
      $keys[] = 'version';
    }
  }
  foreach ($keys as $key) {
    if (is_array($key)) {
      $match = 0;
      $optionset = array();
      foreach ($key as $subkey) {
        if (!array_key_exists($subkey, $params) || empty($params[$subkey])) {
          $optionset[] = $subkey;
        }
        else {
          // As long as there is one match we don't need to return anything.
          $match = 1;
        }
      }
      if (empty($match) && !empty($optionset)) {
        $unmatched[] = "one of (" . implode(", ", $optionset) . ")";
      }
    }
    else {
      // Disallow empty values except for the number zero.
      // TODO: create a utility for this since it's needed in many places.
      if (!array_key_exists($key, $params) || (empty($params[$key]) && $params[$key] !== 0 && $params[$key] !== '0')) {
        $unmatched[] = $key;
      }
    }
  }
  if (!empty($unmatched)) {
    throw new API_Exception("Mandatory key(s) missing from params array: " . implode(", ", $unmatched), "mandatory_missing", array("fields" => $unmatched));
  }
}

/**
 * Create error array.
 *
 * @param string $msg
 * @param array $data
 *
 * @return array
 */
function civicrm_api3_create_error($msg, $data = array()) {
  $data['is_error'] = 1;
  $data['error_message'] = $msg;

  // we will show sql to privileged user only (not sure of a specific
  // security hole here but seems sensible - perhaps should apply to the trace as well?)
  if (isset($data['sql'])) {
    if (CRM_Core_Permission::check('Administer CiviCRM') || CIVICRM_UF == 'UnitTests') {
      // Isn't this redundant?
      $data['debug_information'] = $data['sql'];
    }
    else {
      unset($data['sql']);
    }
  }
  return $data;
}

/**
 * Format array in result output style.
 *
 * @param array|int $values values generated by API operation (the result)
 * @param array $params
 *   Parameters passed into API call.
 * @param string $entity
 *   The entity being acted on.
 * @param string $action
 *   The action passed to the API.
 * @param object $dao
 *   DAO object to be freed here.
 * @param array $extraReturnValues
 *   Additional values to be added to top level of result array(.
 *   - this param is currently used for legacy behaviour support
 *
 * @return array
 */
function civicrm_api3_create_success($values = 1, $params = array(), $entity = NULL, $action = NULL, &$dao = NULL, $extraReturnValues = array()) {
  $result = array();
  $lowercase_entity = _civicrm_api_get_entity_name_from_camel($entity);
  // TODO: This shouldn't be necessary but this fn sometimes gets called with lowercase entity
  $entity = _civicrm_api_get_camel_name($entity);
  $result['is_error'] = 0;
  //lets set the ['id'] field if it's not set & we know what the entity is
  if (is_array($values) && $entity && $action != 'getfields') {
    foreach ($values as $key => $item) {
      if (empty($item['id']) && !empty($item[$lowercase_entity . "_id"])) {
        $values[$key]['id'] = $item[$lowercase_entity . "_id"];
      }
      if (!empty($item['financial_type_id'])) {
        // 4.3 legacy handling.
        $values[$key]['contribution_type_id'] = $item['financial_type_id'];
      }
      if (!empty($item['next_sched_contribution_date'])) {
        // 4.4 legacy handling
        $values[$key]['next_sched_contribution'] = $item['next_sched_contribution_date'];
      }
    }
  }

  if (is_array($params) && !empty($params['debug'])) {
    if (is_string($action) && $action != 'getfields') {
      $apiFields = civicrm_api($entity, 'getfields', array('version' => 3, 'action' => $action) + $params);
    }
    elseif ($action != 'getfields') {
      $apiFields = civicrm_api($entity, 'getfields', array('version' => 3) + $params);
    }
    else {
      $apiFields = FALSE;
    }

    $allFields = array();
    if ($action != 'getfields' && is_array($apiFields) && is_array(CRM_Utils_Array::value('values', $apiFields))) {
      $allFields = array_keys($apiFields['values']);
    }
    $paramFields = array_keys($params);
    $undefined = array_diff($paramFields, $allFields, array_keys($_COOKIE), array(
      'action',
      'entity',
      'debug',
      'version',
      'check_permissions',
      'IDS_request_uri',
      'IDS_user_agent',
      'return',
      'sequential',
      'rowCount',
      'option_offset',
      'option_limit',
      'custom',
      'option_sort',
      'options',
      'prettyprint',
      ));
    if ($undefined) {
      $result['undefined_fields'] = array_merge($undefined);
    }
  }
  if (is_object($dao)) {
    $dao->free();
  }

  $result['version'] = 3;
  if (is_array($values)) {
    $result['count'] = (int) count($values);

    // Convert value-separated strings to array
    if ($action != 'getfields') {
      _civicrm_api3_separate_values($values);
    }

    if ($result['count'] == 1) {
      list($result['id']) = array_keys($values);
    }
    elseif (!empty($values['id']) && is_int($values['id'])) {
      $result['id'] = $values['id'];
    }
  }
  else {
    $result['count'] = !empty($values) ? 1 : 0;
  }

  if (is_array($values) && isset($params['sequential']) &&
    $params['sequential'] == 1
  ) {
    $result['values'] = array_values($values);
  }
  else {
    $result['values'] = $values;
  }
  if (!empty($params['options']['metadata'])) {
    // We've made metadata an array but only supporting 'fields' atm.
    if (in_array('fields', (array) $params['options']['metadata']) && $action !== 'getfields') {
      $fields = civicrm_api3($entity, 'getfields', array(
        'action' => substr($action, 0, 3) == 'get' ? 'get' : 'create',
      ));
      $result['metadata']['fields'] = $fields['values'];
    }
  }
  // Report deprecations.
  $deprecated = _civicrm_api3_deprecation_check($entity, $result);
  // Always report "setvalue" action as deprecated.
  if (!is_string($deprecated) && ($action == 'getactions' || $action == 'setvalue')) {
    $deprecated = ((array) $deprecated) + array('setvalue' => 'The "setvalue" action is deprecated. Use "create" with an id instead.');
  }
  // Always report "update" action as deprecated.
  if (!is_string($deprecated) && ($action == 'getactions' || $action == 'update')) {
    $deprecated = ((array) $deprecated) + array('update' => 'The "update" action is deprecated. Use "create" with an id instead.');
  }
  if ($deprecated) {
    // Metadata-level deprecations or wholesale entity deprecations.
    if ($entity == 'Entity' || $action == 'getactions' || is_string($deprecated)) {
      $result['deprecated'] = $deprecated;
    }
    // Action-specific deprecations
    elseif (!empty($deprecated[$action])) {
      $result['deprecated'] = $deprecated[$action];
    }
  }
  return array_merge($result, $extraReturnValues);
}

/**
 * Load the DAO of the entity.
 *
 * @param $entity
 *
 * @return bool
 */
function _civicrm_api3_load_DAO($entity) {
  $dao = _civicrm_api3_get_DAO($entity);
  if (empty($dao)) {
    return FALSE;
  }
  $d = new $dao();
  return $d;
}

/**
 * Return the DAO of the function or Entity.
 *
 * @param string $name
 *   Either a function of the api (civicrm_{entity}_create or the entity name.
 *   return the DAO name to manipulate this function
 *   eg. "civicrm_api3_contact_create" or "Contact" will return "CRM_Contact_BAO_Contact"
 *
 * @return mixed|string
 */
function _civicrm_api3_get_DAO($name) {
  if (strpos($name, 'civicrm_api3') !== FALSE) {
    $last = strrpos($name, '_');
    // len ('civicrm_api3_') == 13
    $name = substr($name, 13, $last - 13);
  }

  $name = _civicrm_api_get_camel_name($name);

  if ($name == 'Individual' || $name == 'Household' || $name == 'Organization') {
    $name = 'Contact';
  }

  // hack to deal with incorrectly named BAO/DAO - see CRM-10859

  // FIXME: DAO should be renamed CRM_Mailing_DAO_MailingEventQueue
  if ($name == 'MailingEventQueue') {
    return 'CRM_Mailing_Event_DAO_Queue';
  }
  // FIXME: DAO should be renamed CRM_Mailing_DAO_MailingRecipients
  // but am not confident mailing_recipients is tested so have not tackled.
  if ($name == 'MailingRecipients') {
    return 'CRM_Mailing_DAO_Recipients';
  }
  // FIXME: DAO should be renamed CRM_Mailing_DAO_MailingComponent
  if ($name == 'MailingComponent') {
    return 'CRM_Mailing_DAO_Component';
  }
  // FIXME: DAO should be renamed CRM_ACL_DAO_AclRole
  if ($name == 'AclRole') {
    return 'CRM_ACL_DAO_EntityRole';
  }
  // FIXME: DAO should be renamed CRM_SMS_DAO_SmsProvider
  // But this would impact SMS extensions so need to coordinate
  // Probably best approach is to migrate them to use the api and decouple them from core BAOs
  if ($name == 'SmsProvider') {
    return 'CRM_SMS_DAO_Provider';
  }
  // FIXME: DAO names should follow CamelCase convention
  if ($name == 'Im' || $name == 'Acl' || $name == 'Pcp') {
    $name = strtoupper($name);
  }
  $dao = CRM_Core_DAO_AllCoreTables::getFullName($name);
  if ($dao || !$name) {
    return $dao;
  }

  // Really weird apis can declare their own DAO name. Not sure if this is a good idea...
  if (file_exists("api/v3/$name.php")) {
    include_once "api/v3/$name.php";
  }

  $daoFn = "_civicrm_api3_" . _civicrm_api_get_entity_name_from_camel($name) . "_DAO";
  if (function_exists($daoFn)) {
    return $daoFn();
  }

  return NULL;
}

/**
 * Return the BAO name of the function or Entity.
 *
 * @param string $name
 *   Is either a function of the api (civicrm_{entity}_create or the entity name.
 *   return the DAO name to manipulate this function
 *   eg. "civicrm_contact_create" or "Contact" will return "CRM_Contact_BAO_Contact"
 *
 * @return string|null
 */
function _civicrm_api3_get_BAO($name) {
  // FIXME: DAO should be renamed CRM_Badge_DAO_BadgeLayout
  if ($name == 'PrintLabel') {
    return 'CRM_Badge_BAO_Layout';
  }
  $dao = _civicrm_api3_get_DAO($name);
  if (!$dao) {
    return NULL;
  }
  $bao = str_replace("DAO", "BAO", $dao);
  $file = strtr($bao, '_', '/') . '.php';
  // Check if this entity actually has a BAO. Fall back on the DAO if not.
  return stream_resolve_include_path($file) ? $bao : $dao;
}

/**
 * Recursive function to explode value-separated strings into arrays.
 *
 * @param $values
 */
function _civicrm_api3_separate_values(&$values) {
  $sp = CRM_Core_DAO::VALUE_SEPARATOR;
  foreach ($values as $key => & $value) {
    if (is_array($value)) {
      _civicrm_api3_separate_values($value);
    }
    elseif (is_string($value)) {
      // This is to honor the way case API was originally written.
      if ($key == 'case_type_id') {
        $value = trim(str_replace($sp, ',', $value), ',');
      }
      elseif (strpos($value, $sp) !== FALSE) {
        $value = explode($sp, trim($value, $sp));
      }
    }
  }
}

/**
 * This is a legacy wrapper for api_store_values.
 *
 * It checks suitable fields using getfields rather than DAO->fields.
 *
 * Getfields has handling for how to deal with unique names which dao->fields doesn't
 *
 * Note this is used by BAO type create functions - eg. contribution
 *
 * @param string $entity
 * @param array $params
 * @param array $values
 */
function _civicrm_api3_filter_fields_for_bao($entity, &$params, &$values) {
  $fields = civicrm_api($entity, 'getfields', array('version' => 3, 'action' => 'create'));
  $fields = $fields['values'];
  _civicrm_api3_store_values($fields, $params, $values);
}
/**
 * Store values.
 *
 * @param array $fields
 * @param array $params
 * @param array $values
 *
 * @return Bool
 */
function _civicrm_api3_store_values(&$fields, &$params, &$values) {
  $valueFound = FALSE;

  $keys = array_intersect_key($params, $fields);
  foreach ($keys as $name => $value) {
    if ($name !== 'id') {
      $values[$name] = $value;
      $valueFound = TRUE;
    }
  }
  return $valueFound;
}

/**
 * Returns field names of the given entity fields.
 *
 * @param array $fields
 *   Fields array to retrieve the field names for.
 * @return array
 */
function _civicrm_api3_field_names($fields) {
  $result = array();
  foreach ($fields as $key => $value) {
    if (!empty($value['name'])) {
      $result[] = $value['name'];
    }
  }
  return $result;
}

/**
 * Returns an array with database information for the custom fields of an
 * entity.
 *
 * Something similar might already exist in CiviCRM. But I was not
 * able to find it.
 *
 * @param string $entity
 *
 * @return array
 *   an array that maps the custom field ID's to table name and
 *   column name. E.g.:
 *   {
 *     '1' => array {
 *       'table_name' => 'table_name_1',
 *       'column_name' => 'column_name_1',
 *       'data_type' => 'data_type_1',
 *     },
 *   }
 */
function _civicrm_api3_custom_fields_for_entity($entity) {
  $result = array();

  $query = "
SELECT f.id, f.label, f.data_type,
       f.html_type, f.is_search_range,
       f.option_group_id, f.custom_group_id,
       f.column_name, g.table_name,
       f.date_format,f.time_format
  FROM civicrm_custom_field f
  JOIN civicrm_custom_group g ON f.custom_group_id = g.id
 WHERE g.is_active = 1
   AND f.is_active = 1
   AND g.extends = %1";

  $params = array(
    '1' => array($entity, 'String'),
  );

  $dao = CRM_Core_DAO::executeQuery($query, $params);
  while ($dao->fetch()) {
    $result[$dao->id] = array(
      'table_name' => $dao->table_name,
      'column_name' => $dao->column_name,
      'data_type' => $dao->data_type,
    );
  }
  $dao->free();

  return $result;
}

/**
 * Get function for query object api.
 *
 * The API supports 2 types of get request. The more complex uses the BAO query object.
 *  This is a generic function for those functions that call it
 *
 *  At the moment only called by contact we should extend to contribution &
 *  others that use the query object. Note that this function passes permission information in.
 *  The others don't
 *
 * Ideally this would be merged with _civicrm_get_query_object but we need to resolve differences in what the
 * 2 variants call
 *
 * @param $entity
 * @param array $params
 *   As passed into api get or getcount function.
 * @param array $additional_options
 *   Array of options (so we can modify the filter).
 * @param bool $getCount
 *   Are we just after the count.
 * @param int $mode
 *   This basically correlates to the component.
 * @param null|array $defaultReturnProperties
 *   Default return properties for the entity
 *  (used if return not set - but don't do that - set return!).
 *
 * @return array
 * @throws API_Exception
 */
function _civicrm_api3_get_using_query_object($entity, $params, $additional_options = array(), $getCount = NULL, $mode = 1, $defaultReturnProperties = NULL) {
  $lowercase_entity = _civicrm_api_get_entity_name_from_camel($entity);
  // Convert id to e.g. contact_id
  if (empty($params[$lowercase_entity . '_id']) && isset($params['id'])) {
    $params[$lowercase_entity . '_id'] = $params['id'];
  }
  unset($params['id']);

  $options = _civicrm_api3_get_options_from_params($params, TRUE);

  $inputParams = array_merge(
    CRM_Utils_Array::value('input_params', $options, array()),
    CRM_Utils_Array::value('input_params', $additional_options, array())
  );
  $returnProperties = array_merge(
    CRM_Utils_Array::value('return', $options, array()),
    CRM_Utils_Array::value('return', $additional_options, array())
  );
  if (empty($returnProperties)) {
    $returnProperties = $defaultReturnProperties;
  }
  if (!empty($params['check_permissions'])) {
    // we will filter query object against getfields
    $fields = civicrm_api($entity, 'getfields', array('version' => 3, 'action' => 'get'));
    // we need to add this in as earlier in this function 'id' was unset in favour of $entity_id
    $fields['values'][$lowercase_entity . '_id'] = array();
    $varsToFilter = array('returnProperties', 'inputParams');
    foreach ($varsToFilter as $varToFilter) {
      if (!is_array($$varToFilter)) {
        continue;
      }
      //I was going to throw an exception rather than silently filter out - but
      //would need to diff out of exceptions arr other keys like 'options', 'return', 'api. etcetc
      //so we are silently ignoring parts of their request
      //$exceptionsArr = array_diff(array_keys($$varToFilter), array_keys($fields['values']));
      $$varToFilter = array_intersect_key($$varToFilter, $fields['values']);
    }
  }
  $options = array_merge($options, $additional_options);
  $sort             = CRM_Utils_Array::value('sort', $options, NULL);
  $offset             = CRM_Utils_Array::value('offset', $options, NULL);
  $limit             = CRM_Utils_Array::value('limit', $options, NULL);
  $smartGroupCache  = CRM_Utils_Array::value('smartGroupCache', $params);

  if ($getCount) {
    $limit = NULL;
    $returnProperties = NULL;
  }

  if (substr($sort, 0, 2) == 'id') {
    $sort = $lowercase_entity . "_" . $sort;
  }

  $newParams = CRM_Contact_BAO_Query::convertFormValues($inputParams);

  $skipPermissions = !empty($params['check_permissions']) ? 0 : 1;

  list($entities) = CRM_Contact_BAO_Query::apiQuery(
    $newParams,
    $returnProperties,
    NULL,
    $sort,
    $offset,
    $limit,
    $smartGroupCache,
    $getCount,
    $skipPermissions,
    $mode,
    $entity
  );

  return $entities;
}

/**
 * Get dao query object based on input params.
 *
 * Ideally this would be merged with _civicrm_get_using_query_object but we need to resolve differences in what the
 * 2 variants call
 *
 * @param array $params
 * @param string $mode
 * @param string $entity
 *
 * @return array
 *   [CRM_Core_DAO|CRM_Contact_BAO_Query]
 */
function _civicrm_api3_get_query_object($params, $mode, $entity) {
  $options = _civicrm_api3_get_options_from_params($params, TRUE, $entity, 'get');
  $sort = CRM_Utils_Array::value('sort', $options, NULL);
  $offset = CRM_Utils_Array::value('offset', $options);
  $rowCount = CRM_Utils_Array::value('limit', $options);
  $inputParams = CRM_Utils_Array::value('input_params', $options, array());
  $returnProperties = CRM_Utils_Array::value('return', $options, NULL);
  if (empty($returnProperties)) {
    $returnProperties = CRM_Contribute_BAO_Query::defaultReturnProperties($mode);
  }

  $newParams = CRM_Contact_BAO_Query::convertFormValues($inputParams, 0, FALSE, $entity);
  $query = new CRM_Contact_BAO_Query($newParams, $returnProperties, NULL,
    FALSE, FALSE, $mode,
    empty($params['check_permissions'])
  );
  list($select, $from, $where, $having) = $query->query();

  $sql = "$select $from $where $having";

  if (!empty($sort)) {
    $sql .= " ORDER BY $sort ";
  }
  if (!empty($rowCount)) {
    $sql .= " LIMIT $offset, $rowCount ";
  }
  $dao = CRM_Core_DAO::executeQuery($sql);
  return array($dao, $query);
}

/**
 * Function transfers the filters being passed into the DAO onto the params object.
 *
 * @deprecated DAO based retrieval is being phased out.
 *
 * @param CRM_Core_DAO $dao
 * @param array $params
 * @param bool $unique
 * @param array $extraSql
 *   API specific queries eg for event isCurrent would be converted to
 *   $extraSql['where'] = array('civicrm_event' => array('(start_date >= CURDATE() || end_date >= CURDATE())'));
 *
 * @throws API_Exception
 * @throws Exception
 */
function _civicrm_api3_dao_set_filter(&$dao, $params, $unique = TRUE, $extraSql = array()) {
  $entity = _civicrm_api_get_entity_name_from_dao($dao);
  $lowercase_entity = _civicrm_api_get_entity_name_from_camel($entity);
  if (!empty($params[$lowercase_entity . "_id"]) && empty($params['id'])) {
    //if entity_id is set then treat it as ID (will be overridden by id if set)
    $params['id'] = $params[$lowercase_entity . "_id"];
  }
  $allfields = _civicrm_api3_build_fields_array($dao, $unique);
  $fields = array_intersect(array_keys($allfields), array_keys($params));

  $options = _civicrm_api3_get_options_from_params($params);
  //apply options like sort
  _civicrm_api3_apply_options_to_dao($params, $dao, $entity);

  //accept filters like filter.activity_date_time_high
  // std is now 'filters' => ..
  if (strstr(implode(',', array_keys($params)), 'filter')) {
    if (isset($params['filters']) && is_array($params['filters'])) {
      foreach ($params['filters'] as $paramkey => $paramvalue) {
        _civicrm_api3_apply_filters_to_dao($paramkey, $paramvalue, $dao);
      }
    }
    else {
      foreach ($params as $paramkey => $paramvalue) {
        if (strstr($paramkey, 'filter')) {
          _civicrm_api3_apply_filters_to_dao(substr($paramkey, 7), $paramvalue, $dao);
        }
      }
    }
  }
  if (!$fields) {
    $fields = array();
  }

  foreach ($fields as $field) {
    if (is_array($params[$field])) {
      //get the actual fieldname from db
      $fieldName = $allfields[$field]['name'];
      $where = CRM_Core_DAO::createSqlFilter($fieldName, $params[$field], 'String');
      if (!empty($where)) {
        $dao->whereAdd($where);
      }
    }
    else {
      if ($unique) {
        $daoFieldName = $allfields[$field]['name'];
        if (empty($daoFieldName)) {
          throw new API_Exception("Failed to determine field name for \"$field\"");
        }
        $dao->{$daoFieldName} = $params[$field];
      }
      else {
        $dao->$field = $params[$field];
      }
    }
  }
  if (!empty($extraSql['where'])) {
    foreach ($extraSql['where'] as $table => $sqlWhere) {
      foreach ($sqlWhere as $where) {
        $dao->whereAdd($where);
      }
    }
  }
  if (!empty($options['return']) && is_array($options['return']) && empty($options['is_count'])) {
    $dao->selectAdd();
    // Ensure 'id' is included.
    $options['return']['id'] = TRUE;
    $allfields = _civicrm_api3_get_unique_name_array($dao);
    $returnMatched = array_intersect(array_keys($options['return']), $allfields);
    foreach ($returnMatched as $returnValue) {
      $dao->selectAdd($returnValue);
    }

    // Not already matched on the field names.
    $unmatchedFields = array_diff(
      array_keys($options['return']),
      $returnMatched
    );

    $returnUniqueMatched = array_intersect(
      $unmatchedFields,
      // But a match for the field keys.
      array_flip($allfields)
    );
    foreach ($returnUniqueMatched as $uniqueVal) {
      $dao->selectAdd($allfields[$uniqueVal]);
    }
  }
  $dao->setApiFilter($params);
}

/**
 * Apply filters (e.g. high, low) to DAO object (prior to find).
 *
 * @param string $filterField
 *   Field name of filter.
 * @param string $filterValue
 *   Field value of filter.
 * @param object $dao
 *   DAO object.
 */
function _civicrm_api3_apply_filters_to_dao($filterField, $filterValue, &$dao) {
  if (strstr($filterField, 'high')) {
    $fieldName = substr($filterField, 0, -5);
    $dao->whereAdd("($fieldName <= $filterValue )");
  }
  if (strstr($filterField, 'low')) {
    $fieldName = substr($filterField, 0, -4);
    $dao->whereAdd("($fieldName >= $filterValue )");
  }
  if ($filterField == 'is_current' && $filterValue == 1) {
    $todayStart = date('Ymd000000', strtotime('now'));
    $todayEnd = date('Ymd235959', strtotime('now'));
    $dao->whereAdd("(start_date <= '$todayStart' OR start_date IS NULL) AND (end_date >= '$todayEnd' OR end_date IS NULL)");
    if (property_exists($dao, 'is_active')) {
      $dao->whereAdd('is_active = 1');
    }
  }
}

/**
 * Get sort, limit etc options from the params - supporting old & new formats.
 *
 * Get returnProperties for legacy
 *
 * @param array $params
 *   Params array as passed into civicrm_api.
 * @param bool $queryObject
 *   Is this supporting a queryObject api (e.g contact) - if so we support more options.
 *   for legacy report & return a unique fields array
 *
 * @param string $entity
 * @param string $action
 *
 * @throws API_Exception
 * @return array
 *   options extracted from params
 */
function _civicrm_api3_get_options_from_params(&$params, $queryObject = FALSE, $entity = '', $action = '') {
  $lowercase_entity = _civicrm_api_get_entity_name_from_camel($entity);
  $is_count = FALSE;
  $sort = CRM_Utils_Array::value('sort', $params, 0);
  $sort = CRM_Utils_Array::value('option.sort', $params, $sort);
  $sort = CRM_Utils_Array::value('option_sort', $params, $sort);

  $offset = CRM_Utils_Array::value('offset', $params, 0);
  $offset = CRM_Utils_Array::value('option.offset', $params, $offset);
  // dear PHP thought it would be a good idea to transform a.b into a_b in the get/post
  $offset = CRM_Utils_Array::value('option_offset', $params, $offset);

  $limit = CRM_Utils_Array::value('rowCount', $params, 25);
  $limit = CRM_Utils_Array::value('option.limit', $params, $limit);
  $limit = CRM_Utils_Array::value('option_limit', $params, $limit);

  if (is_array(CRM_Utils_Array::value('options', $params))) {
    // is count is set by generic getcount not user
    $is_count = CRM_Utils_Array::value('is_count', $params['options']);
    $offset = CRM_Utils_Array::value('offset', $params['options'], $offset);
    $limit  = CRM_Utils_Array::value('limit', $params['options'], $limit);
    $sort   = CRM_Utils_Array::value('sort', $params['options'], $sort);
  }

  $returnProperties = array();
  // handle the format return =sort_name,display_name...
  if (array_key_exists('return', $params)) {
    if (is_array($params['return'])) {
      $returnProperties = array_fill_keys($params['return'], 1);
    }
    else {
      $returnProperties = explode(',', str_replace(' ', '', $params['return']));
      $returnProperties = array_fill_keys($returnProperties, 1);
    }
  }
  if ($entity && $action == 'get') {
    if (!empty($returnProperties['id'])) {
      $returnProperties[$lowercase_entity . '_id'] = 1;
      unset($returnProperties['id']);
    }
    switch (trim(strtolower($sort))) {
      case 'id':
      case 'id desc':
      case 'id asc':
        $sort = str_replace('id', $lowercase_entity . '_id', $sort);
    }
  }

  $options = array(
    'offset' => CRM_Utils_Rule::integer($offset) ? $offset : NULL,
    'sort' => CRM_Utils_Rule::string($sort) ? $sort : NULL,
    'limit' => CRM_Utils_Rule::integer($limit) ? $limit : NULL,
    'is_count' => $is_count,
    'return' => !empty($returnProperties) ? $returnProperties : array(),
  );

  if ($options['sort'] && stristr($options['sort'], 'SELECT')) {
    throw new API_Exception('invalid string in sort options');
  }

  if (!$queryObject) {
    return $options;
  }
  //here comes the legacy support for $returnProperties, $inputParams e.g for contat_get
  // if the query object is being used this should be used
  $inputParams = array();
  $legacyreturnProperties = array();
  $otherVars = array(
    'sort', 'offset', 'rowCount', 'options', 'return',
    'version', 'prettyprint', 'check_permissions', 'sequential',
  );
  foreach ($params as $n => $v) {
    if (substr($n, 0, 7) == 'return.') {
      $legacyreturnProperties[substr($n, 7)] = $v;
    }
    elseif ($n == 'id') {
      $inputParams[$lowercase_entity . '_id'] = $v;
    }
    elseif (in_array($n, $otherVars)) {
    }
    else {
      $inputParams[$n] = $v;
      if ($v && !is_array($v) && stristr($v, 'SELECT')) {
        throw new API_Exception('invalid string');
      }
    }
  }
  $options['return'] = array_merge($returnProperties, $legacyreturnProperties);
  $options['input_params'] = $inputParams;
  return $options;
}

/**
 * Apply options (e.g. sort, limit, order by) to DAO object (prior to find).
 *
 * @param array $params
 *   Params array as passed into civicrm_api.
 * @param object $dao
 *   DAO object.
 * @param $entity
 */
function _civicrm_api3_apply_options_to_dao(&$params, &$dao, $entity) {

  $options = _civicrm_api3_get_options_from_params($params, FALSE, $entity);
  if (!$options['is_count']) {
    if (!empty($options['limit'])) {
      $dao->limit((int) $options['offset'], (int) $options['limit']);
    }
    if (!empty($options['sort'])) {
      $dao->orderBy($options['sort']);
    }
  }
}

/**
 * Build fields array.
 *
 * This is the array of fields as it relates to the given DAO
 * returns unique fields as keys by default but if set but can return by DB fields
 *
 * @param CRM_Core_DAO $bao
 * @param bool $unique
 *
 * @return array
 */
function _civicrm_api3_build_fields_array(&$bao, $unique = TRUE) {
  $fields = $bao->fields();
  if ($unique) {
    if (empty($fields['id'])) {
      $lowercase_entity = _civicrm_api_get_entity_name_from_camel(_civicrm_api_get_entity_name_from_dao($bao));
      $fields['id'] = $fields[$lowercase_entity . '_id'];
      unset($fields[$lowercase_entity . '_id']);
    }
    return $fields;
  }

  foreach ($fields as $field) {
    $dbFields[$field['name']] = $field;
  }
  return $dbFields;
}

/**
 * Build fields array.
 *
 * This is the array of fields as it relates to the given DAO
 * returns unique fields as keys by default but if set but can return by DB fields
 *
 * @param CRM_Core_DAO $bao
 *
 * @return array
 */
function _civicrm_api3_get_unique_name_array(&$bao) {
  $fields = $bao->fields();
  foreach ($fields as $field => $values) {
    $uniqueFields[$field] = CRM_Utils_Array::value('name', $values, $field);
  }
  return $uniqueFields;
}

/**
 * Converts an DAO object to an array.
 *
 * @deprecated - DAO based retrieval is being phased out.
 *
 * @param CRM_Core_DAO $dao
 *   Object to convert.
 * @param array $params
 * @param bool $uniqueFields
 * @param string $entity
 * @param bool $autoFind
 *
 * @return array
 */
function _civicrm_api3_dao_to_array($dao, $params = NULL, $uniqueFields = TRUE, $entity = "", $autoFind = TRUE) {
  $result = array();
  if (isset($params['options']) && !empty($params['options']['is_count'])) {
    return $dao->count();
  }
  if (empty($dao)) {
    return array();
  }
  if ($autoFind && !$dao->find()) {
    return array();
  }

  if (isset($dao->count)) {
    return $dao->count;
  }

  $fields = array_keys(_civicrm_api3_build_fields_array($dao, FALSE));
  while ($dao->fetch()) {
    $tmp = array();
    foreach ($fields as $key) {
      if (array_key_exists($key, $dao)) {
        // not sure on that one
        if ($dao->$key !== NULL) {
          $tmp[$key] = $dao->$key;
        }
      }
    }
    $result[$dao->id] = $tmp;

    if (_civicrm_api3_custom_fields_are_required($entity, $params)) {
      _civicrm_api3_custom_data_get($result[$dao->id], $params['check_permissions'], $entity, $dao->id);
    }
  }

  return $result;
}

/**
 * Determine if custom fields need to be retrieved.
 *
 * We currently retrieve all custom fields or none at this level so if we know the entity
 * && it can take custom fields & there is the string 'custom' in their return request we get them all, they are filtered on the way out
 * @todo filter so only required fields are queried
 *
 * @param string $entity
 *   Entity name in CamelCase.
 * @param array $params
 *
 * @return bool
 */
function _civicrm_api3_custom_fields_are_required($entity, $params) {
  if (!array_key_exists($entity, CRM_Core_BAO_CustomQuery::$extendsMap)) {
    return FALSE;
  }
  $options = _civicrm_api3_get_options_from_params($params);
  // We check for possibility of 'custom' => 1 as well as specific custom fields.
  $returnString = implode('', $options['return']) . implode('', array_keys($options['return']));
  if (stristr($returnString, 'custom')) {
    return TRUE;
  }
}

/**
 * Converts an object to an array.
 *
 * @param object $dao
 *   (reference) object to convert.
 * @param array $values
 *   (reference) array.
 * @param array|bool $uniqueFields
 */
function _civicrm_api3_object_to_array(&$dao, &$values, $uniqueFields = FALSE) {

  $fields = _civicrm_api3_build_fields_array($dao, $uniqueFields);
  foreach ($fields as $key => $value) {
    if (array_key_exists($key, $dao)) {
      $values[$key] = $dao->$key;
    }
  }
}

/**
 * Wrapper for _civicrm_object_to_array when api supports unique fields.
 *
 * @param $dao
 * @param $values
 *
 * @return array
 */
function _civicrm_api3_object_to_array_unique_fields(&$dao, &$values) {
  return _civicrm_api3_object_to_array($dao, $values, TRUE);
}

/**
 * Format custom parameters.
 *
 * @param array $params
 * @param array $values
 * @param string $extends
 *   Entity that this custom field extends (e.g. contribution, event, contact).
 * @param string $entityId
 *   ID of entity per $extends.
 */
function _civicrm_api3_custom_format_params($params, &$values, $extends, $entityId = NULL) {
  $values['custom'] = array();
  $checkCheckBoxField = FALSE;
  $entity = $extends;
  if (in_array($extends, array('Household', 'Individual', 'Organization'))) {
    $entity = 'Contact';
  }

  $fields = civicrm_api($entity, 'getfields', array('version' => 3, 'action' => 'create'));
  if (!$fields['is_error']) {
    // not sure if fields could be error - maybe change to using civicrm_api3 wrapper later - this is conservative
    $fields = $fields['values'];
    $checkCheckBoxField = TRUE;
  }

  foreach ($params as $key => $value) {
    list($customFieldID, $customValueID) = CRM_Core_BAO_CustomField::getKeyID($key, TRUE);
    if ($customFieldID && (!is_null($value))) {
      if ($checkCheckBoxField && !empty($fields['custom_' . $customFieldID]) && $fields['custom_' . $customFieldID]['html_type'] == 'CheckBox') {
        formatCheckBoxField($value, 'custom_' . $customFieldID, $entity);
      }

      CRM_Core_BAO_CustomField::formatCustomField($customFieldID, $values['custom'],
        $value, $extends, $customValueID, $entityId, FALSE, FALSE, TRUE
      );
    }
  }
}

/**
 * Format parameters for create action.
 *
 * @param array $params
 * @param $entity
 */
function _civicrm_api3_format_params_for_create(&$params, $entity) {
  $nonGenericEntities = array('Contact', 'Individual', 'Household', 'Organization');

  $customFieldEntities = array_diff_key(CRM_Core_BAO_CustomQuery::$extendsMap, array_fill_keys($nonGenericEntities, 1));
  if (!array_key_exists($entity, $customFieldEntities)) {
    return;
  }
  $values = array();
  _civicrm_api3_custom_format_params($params, $values, $entity);
  $params = array_merge($params, $values);
}

/**
 * We can't rely on downstream to add separators to checkboxes so we'll check here.
 *
 * We should look at pushing to BAO function
 * and / or validate function but this is a safe place for now as it has massive test coverage & we can keep the change very specific
 * note that this is specifically tested in the GRANT api test case so later refactoring should use that as a checking point
 *
 * We will only alter the value if we are sure that changing it will make it correct - if it appears wrong but does not appear to have a clear fix we
 * don't touch - lots of very cautious code in here
 *
 * The resulting array should look like
 * array(
 *  'key' => 1,
 *  'key1' => 1,
 * );
 *
 * OR one or more keys wrapped in a CRM_Core_DAO::VALUE_SEPARATOR - either it accepted by the receiving function
 *
 * @todo - we are probably skipping handling disabled options as presumably getoptions is not giving us them. This should be non-regressive but might
 * be fixed in future
 *
 * @param mixed $checkboxFieldValue
 * @param string $customFieldLabel
 * @param string $entity
 */
function formatCheckBoxField(&$checkboxFieldValue, $customFieldLabel, $entity) {

  if (is_string($checkboxFieldValue) && stristr($checkboxFieldValue, CRM_Core_DAO::VALUE_SEPARATOR)) {
    // We can assume it's pre-formatted.
    return;
  }
  $options = civicrm_api($entity, 'getoptions', array('field' => $customFieldLabel, 'version' => 3));
  if (!empty($options['is_error'])) {
    // The check is precautionary - can probably be removed later.
    return;
  }

  $options = $options['values'];
  $validValue = TRUE;
  if (is_array($checkboxFieldValue)) {
    foreach ($checkboxFieldValue as $key => $value) {
      if (!array_key_exists($key, $options)) {
        $validValue = FALSE;
      }
    }
    if ($validValue) {
      // we have been passed an array that is already in the 'odd' custom field format
      return;
    }
  }

  // so we either have an array that is not keyed by the value or we have a string that doesn't hold separators
  // if the array only has one item we'll treat it like any other string
  if (is_array($checkboxFieldValue) && count($checkboxFieldValue) == 1) {
    $possibleValue = reset($checkboxFieldValue);
  }
  if (is_string($checkboxFieldValue)) {
    $possibleValue = $checkboxFieldValue;
  }
  if (isset($possibleValue) && array_key_exists($possibleValue, $options)) {
    $checkboxFieldValue = CRM_Core_DAO::VALUE_SEPARATOR . $possibleValue . CRM_Core_DAO::VALUE_SEPARATOR;
    return;
  }
  elseif (is_array($checkboxFieldValue)) {
    // so this time around we are considering the values in the array
    $possibleValues = $checkboxFieldValue;
    $formatValue = TRUE;
  }
  elseif (stristr($checkboxFieldValue, ',')) {
    $formatValue = TRUE;
    //lets see if we should separate it - we do this near the end so we
    // ensure we have already checked that the comma is not part of a legitimate match
    // and of course, we don't make any changes if we don't now have matches
    $possibleValues = explode(',', $checkboxFieldValue);
  }
  else {
    // run out of ideas as to what the format might be - if it's a string it doesn't match with or without the ','
    return;
  }

  foreach ($possibleValues as $index => $possibleValue) {
    if (array_key_exists($possibleValue, $options)) {
      // do nothing - we will leave formatValue set to true unless another value is not found (which would cause us to ignore the whole value set)
    }
    elseif (array_key_exists(trim($possibleValue), $options)) {
      $possibleValues[$index] = trim($possibleValue);
    }
    else {
      $formatValue = FALSE;
    }
  }
  if ($formatValue) {
    $checkboxFieldValue = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $possibleValues) . CRM_Core_DAO::VALUE_SEPARATOR;
  }
}

/**
 * This function ensures that we have the right input parameters.
 *
 * @deprecated
 *
 * This function is only called when $dao is passed into verify_mandatory.
 * The practice of passing $dao into verify_mandatory turned out to be
 * unsatisfactory as the required fields @ the dao level is so different to the abstract
 * api level. Hence the intention is to remove this function
 * & the associated param from verify_mandatory
 *
 * @param array $params
 *   Associative array of property name/value.
 *   pairs to insert in new history.
 * @param string $daoName
 * @param bool $return
 *
 * @daoName string DAO to check params against
 *
 * @return bool
 *   Should the missing fields be returned as an array (core error created as default)
 *   true if all fields present, depending on $result a core error is created of an array of missing fields is returned
 */
function _civicrm_api3_check_required_fields($params, $daoName, $return = FALSE) {
  //@deprecated - see notes
  if (isset($params['extends'])) {
    if (($params['extends'] == 'Activity' ||
        $params['extends'] == 'Phonecall' ||
        $params['extends'] == 'Meeting' ||
        $params['extends'] == 'Group' ||
        $params['extends'] == 'Contribution'
      ) &&
      ($params['style'] == 'Tab')
    ) {
      return civicrm_api3_create_error(ts("Can not create Custom Group in Tab for " . $params['extends']));
    }
  }

  $dao = new $daoName();
  $fields = $dao->fields();

  $missing = array();
  foreach ($fields as $k => $v) {
    if ($v['name'] == 'id') {
      continue;
    }

    if (!empty($v['required'])) {
      // 0 is a valid input for numbers, CRM-8122
      if (!isset($params[$k]) || (empty($params[$k]) && !($params[$k] === 0))) {
        $missing[] = $k;
      }
    }
  }

  if (!empty($missing)) {
    if (!empty($return)) {
      return $missing;
    }
    else {
      return civicrm_api3_create_error(ts("Required fields " . implode(',', $missing) . " for $daoName are not present"));
    }
  }

  return TRUE;
}

/**
 * Function to do a 'standard' api get - when the api is only doing a $bao->find then use this.
 *
 * @param string $bao_name
 *   Name of BAO.
 * @param array $params
 *   Params from api.
 * @param bool $returnAsSuccess
 *   Return in api success format.
 * @param string $entity
 * @param CRM_Utils_SQL_Select|NULL $sql
 *   Extra SQL bits to add to the query. For filtering current events, this might be:
 *   CRM_Utils_SQL_Select::fragment()->where('(start_date >= CURDATE() || end_date >= CURDATE())');
 * @param bool $uniqueFields
 *   Should unique field names be returned (for backward compatibility)
 *
 * @return array
 */
function _civicrm_api3_basic_get($bao_name, $params, $returnAsSuccess = TRUE, $entity = "", $sql = NULL, $uniqueFields = FALSE) {
  $entity = CRM_Core_DAO_AllCoreTables::getBriefName(str_replace('_BAO_', '_DAO_', $bao_name));
  $options = _civicrm_api3_get_options_from_params($params);

  $query = new \Civi\API\Api3SelectQuery($entity, CRM_Utils_Array::value('check_permissions', $params, FALSE));
  $query->where = $params;
  if ($options['is_count']) {
    $query->select = array('count_rows');
  }
  else {
    $query->select = array_keys(array_filter($options['return']));
    $query->orderBy = $options['sort'];
    $query->isFillUniqueFields = $uniqueFields;
  }
  $query->limit = $options['limit'];
  $query->offset = $options['offset'];
  $query->merge($sql);
  $result = $query->run();

  if ($returnAsSuccess) {
    return civicrm_api3_create_success($result, $params, $entity, 'get');
  }
  return $result;
}

/**
 * Function to do a 'standard' api create - when the api is only doing a $bao::create then use this.
 *
 * @param string $bao_name
 *   Name of BAO Class.
 * @param array $params
 *   Parameters passed into the api call.
 * @param string $entity
 *   Entity - pass in if entity is non-standard & required $ids array.
 *
 * @throws API_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 * @return array
 */
function _civicrm_api3_basic_create($bao_name, &$params, $entity = NULL) {
  _civicrm_api3_check_edit_permissions($bao_name, $params);
  _civicrm_api3_format_params_for_create($params, $entity);
  $args = array(&$params);
  if ($entity) {
    $ids = array($entity => CRM_Utils_Array::value('id', $params));
    $args[] = &$ids;
  }

  if (method_exists($bao_name, 'create')) {
    $fct = 'create';
    $fct_name = $bao_name . '::' . $fct;
    $bao = call_user_func_array(array($bao_name, $fct), $args);
  }
  elseif (method_exists($bao_name, 'add')) {
    $fct = 'add';
    $fct_name = $bao_name . '::' . $fct;
    $bao = call_user_func_array(array($bao_name, $fct), $args);
  }
  else {
    $fct_name = '_civicrm_api3_basic_create_fallback';
    $bao = _civicrm_api3_basic_create_fallback($bao_name, $params);
  }

  if (is_null($bao)) {
    return civicrm_api3_create_error('Entity not created (' . $fct_name . ')');
  }
  elseif (is_a($bao, 'CRM_Core_Error')) {
    //some weird circular thing means the error takes itself as an argument
    $msg = $bao->getMessages($bao);
    // the api deals with entities on a one-by-one basis. However, the contribution bao pushes entities
    // onto the error object - presumably because the contribution import is not handling multiple errors correctly
    // so we need to reset the error object here to avoid getting concatenated errors
    //@todo - the mulitple error handling should be moved out of the contribution object to the import / multiple entity processes
    CRM_Core_Error::singleton()->reset();
    throw new API_Exception($msg);
  }
  else {
    $values = array();
    _civicrm_api3_object_to_array($bao, $values[$bao->id]);
    return civicrm_api3_create_success($values, $params, $entity, 'create', $bao);
  }
}

/**
 * For BAO's which don't have a create() or add() functions, use this fallback implementation.
 *
 * @fixme There's an intuitive sense that this behavior should be defined somehow in the BAO/DAO class
 * structure. In practice, that requires a fair amount of refactoring and/or kludgery.
 *
 * @param string $bao_name
 * @param array $params
 *
 * @throws API_Exception
 *
 * @return CRM_Core_DAO|NULL
 *   An instance of the BAO
 */
function _civicrm_api3_basic_create_fallback($bao_name, &$params) {
  $dao_name = get_parent_class($bao_name);
  if ($dao_name === 'CRM_Core_DAO' || !$dao_name) {
    $dao_name = $bao_name;
  }
  $entityName = CRM_Core_DAO_AllCoreTables::getBriefName($dao_name);
  if (empty($entityName)) {
    throw new API_Exception("Class \"$bao_name\" does not map to an entity name", "unmapped_class_to_entity", array(
      'class_name' => $bao_name,
    ));
  }
  $hook = empty($params['id']) ? 'create' : 'edit';

  CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
  $instance = new $dao_name();
  $instance->copyValues($params);
  $instance->save();
  CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

  return $instance;
}

/**
 * Function to do a 'standard' api del.
 *
 * When the api is only doing a $bao::del then use this if api::del doesn't exist it will try DAO delete method.
 *
 * @param string $bao_name
 * @param array $params
 *
 * @return array
 *   API result array
 * @throws API_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function _civicrm_api3_basic_delete($bao_name, &$params) {
  civicrm_api3_verify_mandatory($params, NULL, array('id'));
  _civicrm_api3_check_edit_permissions($bao_name, array('id' => $params['id']));
  $args = array(&$params['id']);
  if (method_exists($bao_name, 'del')) {
    $dao = new $bao_name();
    $dao->id = $params['id'];
    if ($dao->find()) {
      $bao = call_user_func_array(array($bao_name, 'del'), $args);
      if ($bao !== FALSE) {
        return civicrm_api3_create_success();
      }
      throw new API_Exception('Could not delete entity id ' . $params['id']);
    }
    throw new API_Exception('Could not delete entity id ' . $params['id']);
  }
  elseif (method_exists($bao_name, 'delete')) {
    $dao = new $bao_name();
    $dao->id = $params['id'];
    if ($dao->find()) {
      while ($dao->fetch()) {
        $dao->delete();
        return civicrm_api3_create_success();
      }
    }
    else {
      throw new API_Exception('Could not delete entity id ' . $params['id']);
    }
  }

  throw new API_Exception('no delete method found');
}

/**
 * Get custom data for the given entity & Add it to the returnArray.
 *
 * This looks like 'custom_123' = 'custom string' AND
 * 'custom_123_1' = 'custom string'
 * Where 123 is field value & 1 is the id within the custom group data table (value ID)
 *
 * @param array $returnArray
 *   Array to append custom data too - generally $result[4] where 4 is the entity id.
 * @param string $entity
 *   E.g membership, event.
 * @param int $entity_id
 * @param int $groupID
 *   Per CRM_Core_BAO_CustomGroup::getTree.
 * @param int $subType
 *   E.g. membership_type_id where custom data doesn't apply to all membership types.
 * @param string $subName
 *   Subtype of entity.
 */
function _civicrm_api3_custom_data_get(&$returnArray, $checkPermission, $entity, $entity_id, $groupID = NULL, $subType = NULL, $subName = NULL) {
  $groupTree = CRM_Core_BAO_CustomGroup::getTree($entity,
    NULL,
    $entity_id,
    $groupID,
    NULL,
    $subName,
    TRUE,
    NULL,
    TRUE,
    $checkPermission
  );
  $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, 1, CRM_Core_DAO::$_nullObject);
  $customValues = array();
  CRM_Core_BAO_CustomGroup::setDefaults($groupTree, $customValues);
  $fieldInfo = array();
  foreach ($groupTree as $set) {
    $fieldInfo += $set['fields'];
  }
  if (!empty($customValues)) {
    foreach ($customValues as $key => $val) {
      // per standard - return custom_fieldID
      $id = CRM_Core_BAO_CustomField::getKeyID($key);
      $returnArray['custom_' . $id] = $val;

      //not standard - but some api did this so guess we should keep - cheap as chips
      $returnArray[$key] = $val;

      // Shim to restore legacy behavior of ContactReference custom fields
      if (!empty($fieldInfo[$id]) && $fieldInfo[$id]['data_type'] == 'ContactReference') {
        $returnArray['custom_' . $id . '_id'] = $returnArray[$key . '_id'] = $val;
        $returnArray['custom_' . $id] = $returnArray[$key] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $val, 'sort_name');
      }
    }
  }
}

/**
 * Used by the Validate API.
 * @param string $entity
 * @param string $action
 * @param array $params
 *
 * @return array $errors
 */
function _civicrm_api3_validate($entity, $action, $params) {
  $errors = array();
  $fields = civicrm_api3($entity, 'getfields', array('sequential' => 1, 'api_action' => $action));
  $fields = $fields['values'];

  // Check for required fields.
  foreach ($fields as $values) {
    if (!empty($values['api.required']) && empty($params[$values['name']])) {
      $errors[$values['name']] = array(
        'message' => "Mandatory key(s) missing from params array: " . $values['name'],
        'code' => "mandatory_missing",
      );
    }
  }

  // Select only the fields which have been input as a param.
  $finalfields = array();
  foreach ($fields as $values) {
    if (array_key_exists($values['name'], $params)) {
      $finalfields[] = $values;
    }
  }

  // This derives heavily from the function "_civicrm_api3_validate_fields".
  // However, the difference is that try-catch blocks are nested in the loop, making it
  // possible for us to get all errors in one go.
  foreach ($finalfields as $fieldInfo) {
    $fieldName = $fieldInfo['name'];
    try {
      _civicrm_api3_validate_switch_cases($fieldName, $fieldInfo, $entity, $params);
    }
    catch (Exception $e) {
      $errors[$fieldName] = array(
        'message' => $e->getMessage(),
        'code' => 'incorrect_value',
      );
    }
  }

  return array($errors);
}
/**
 * Used by the Validate API.
 * @param array $fieldInfo
 * @param string $entity
 * @param array $params
 *
 * @throws Exception
 */
function _civicrm_api3_validate_switch_cases($fieldName, $fieldInfo, $entity, $params) {
  switch (CRM_Utils_Array::value('type', $fieldInfo)) {
    case CRM_Utils_Type::T_INT:
      _civicrm_api3_validate_integer($params, $fieldName, $fieldInfo, $entity);
      break;

    case CRM_Utils_Type::T_DATE:
    case CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME:
    case CRM_Utils_Type::T_TIMESTAMP:
      //field is of type date or datetime
      _civicrm_api3_validate_date($params, $fieldName, $fieldInfo);
      break;

    case CRM_Utils_Type::T_TEXT:
      _civicrm_api3_validate_html($params, $fieldName, $fieldInfo);
      break;

    case CRM_Utils_Type::T_STRING:
      _civicrm_api3_validate_string($params, $fieldName, $fieldInfo, $entity);
      break;

    case CRM_Utils_Type::T_MONEY:
      list($fieldValue, $op) = _civicrm_api3_field_value_check($params, $fieldName);

      foreach ((array) $fieldValue as $fieldvalue) {
        if (!CRM_Utils_Rule::money($fieldvalue) && !empty($fieldvalue)) {
          throw new Exception($fieldName . " is  not a valid amount: " . $params[$fieldName]);
        }
      }
      break;
  }
}

/**
 * Validate fields being passed into API.
 *
 * This function relies on the getFields function working accurately
 * for the given API.
 *
 * As of writing only date was implemented.
 *
 * @param string $entity
 * @param string $action
 * @param array $params
 *   -.
 * @param array $fields
 *   Response from getfields all variables are the same as per civicrm_api.
 *
 * @throws Exception
 */
function _civicrm_api3_validate_fields($entity, $action, &$params, $fields) {
  //CRM-15792 handle datetime for custom fields below code handles chain api call
  $chainApikeys = array_flip(preg_grep("/^api./", array_keys($params)));
  if (!empty($chainApikeys) && is_array($chainApikeys)) {
    foreach ($chainApikeys as $key => $value) {
      if (is_array($params[$key])) {
        $chainApiParams = array_intersect_key($fields, $params[$key]);
        $customFields = array_fill_keys(array_keys($params[$key]), $key);
      }
    }
  }
  $fields = array_intersect_key($fields, $params);
  if (!empty($chainApiParams)) {
    $fields = array_merge($fields, $chainApiParams);
  }
  foreach ($fields as $fieldName => $fieldInfo) {
    switch (CRM_Utils_Array::value('type', $fieldInfo)) {
      case CRM_Utils_Type::T_INT:
        //field is of type integer
        _civicrm_api3_validate_integer($params, $fieldName, $fieldInfo, $entity);
        break;

      case CRM_Utils_Type::T_DATE:
      case CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME:
      case CRM_Utils_Type::T_TIMESTAMP:
        //field is of type date or datetime
        if (!empty($customFields) && array_key_exists($fieldName, $customFields)) {
          $dateParams = &$params[$customFields[$fieldName]];
        }
        else {
          $dateParams = &$params;
        }
        _civicrm_api3_validate_date($dateParams, $fieldName, $fieldInfo);
        break;

      case CRM_Utils_Type::T_TEXT:
        //blob
        _civicrm_api3_validate_html($params, $fieldName, $fieldInfo);
        break;

      case CRM_Utils_Type::T_STRING:
        _civicrm_api3_validate_string($params, $fieldName, $fieldInfo, $entity);
        break;

      case CRM_Utils_Type::T_MONEY:
        list($fieldValue, $op) = _civicrm_api3_field_value_check($params, $fieldName);
        if (strpos($op, 'NULL') !== FALSE || strpos($op, 'EMPTY') !== FALSE) {
          break;
        }
        foreach ((array) $fieldValue as $fieldvalue) {
          if (!CRM_Utils_Rule::money($fieldvalue) && !empty($fieldvalue)) {
            throw new Exception($fieldName . " is  not a valid amount: " . $params[$fieldName]);
          }
        }
        break;
    }
  }
}

/**
 * Validate foreign key values of fields being passed into API.
 *
 * This function relies on the getFields function working accurately
 * for the given API.
 *
 * @param string $entity
 * @param string $action
 * @param array $params
 *
 * @param array $fields
 *   Response from getfields all variables are the same as per civicrm_api.
 *
 * @throws Exception
 */
function _civicrm_api3_validate_foreign_keys($entity, $action, &$params, $fields) {
  // intensive checks - usually only called after DB level fail
  foreach ($fields as $fieldName => $fieldInfo) {
    if (!empty($fieldInfo['FKClassName'])) {
      if (!empty($params[$fieldName])) {
        _civicrm_api3_validate_constraint($params[$fieldName], $fieldName, $fieldInfo);
      }
      elseif (!empty($fieldInfo['required'])) {
        throw new Exception("DB Constraint Violation - possibly $fieldName should possibly be marked as mandatory for this API. If so, please raise a bug report.");
      }
    }
    if (!empty($fieldInfo['api.unique'])) {
      $params['entity'] = $entity;
      _civicrm_api3_validate_unique_key($params, $fieldName);
    }
  }
}

/**
 * Validate date fields being passed into API.
 *
 * It currently converts both unique fields and DB field names to a mysql date.
 * @todo - probably the unique field handling & the if exists handling is now done before this
 * function is reached in the wrapper - can reduce this code down to assume we
 * are only checking the passed in field
 *
 * It also checks against the RULE:date function. This is a centralisation of code that was scattered and
 * may not be the best thing to do. There is no code level documentation on the existing functions to work off
 *
 * @param array $params
 *   Params from civicrm_api.
 * @param string $fieldName
 *   Uniquename of field being checked.
 * @param array $fieldInfo
 *   Array of fields from getfields function.
 *
 * @throws Exception
 */
function _civicrm_api3_validate_date(&$params, &$fieldName, &$fieldInfo) {
  list($fieldValue, $op) = _civicrm_api3_field_value_check($params, $fieldName);
  if (strpos($op, 'NULL') !== FALSE || strpos($op, 'EMPTY') !== FALSE) {
    return;
  }
  //should we check first to prevent it from being copied if they have passed in sql friendly format?
  if (!empty($params[$fieldInfo['name']])) {
    $fieldValue = _civicrm_api3_getValidDate($fieldValue, $fieldInfo['name'], $fieldInfo['type']);
  }
  if ((CRM_Utils_Array::value('name', $fieldInfo) != $fieldName) && !empty($fieldValue)) {
    $fieldValue = _civicrm_api3_getValidDate($fieldValue, $fieldName, $fieldInfo['type']);
  }

  if (!empty($op)) {
    $params[$fieldName][$op] = $fieldValue;
  }
  else {
    $params[$fieldName] = $fieldValue;
  }
}

/**
 * Convert date into BAO friendly date.
 *
 * We accept 'whatever strtotime accepts'
 *
 * @param string $dateValue
 * @param string $fieldName
 * @param $fieldType
 *
 * @throws Exception
 * @return mixed
 */
function _civicrm_api3_getValidDate($dateValue, $fieldName, $fieldType) {
  if (is_array($dateValue)) {
    foreach ($dateValue as $key => $value) {
      $dateValue[$key] = _civicrm_api3_getValidDate($value, $fieldName, $fieldType);
    }
    return $dateValue;
  }
  if (strtotime($dateValue) === FALSE) {
    throw new Exception($fieldName . " is not a valid date: " . $dateValue);
  }
  $format = ($fieldType == CRM_Utils_Type::T_DATE) ? 'Ymd000000' : 'YmdHis';
  return CRM_Utils_Date::processDate($dateValue, NULL, FALSE, $format);
}

/**
 * Validate foreign constraint fields being passed into API.
 *
 * @param mixed $fieldValue
 * @param string $fieldName
 *   Uniquename of field being checked.
 * @param array $fieldInfo
 *   Array of fields from getfields function.
 *
 * @throws \API_Exception
 */
function _civicrm_api3_validate_constraint(&$fieldValue, &$fieldName, &$fieldInfo) {
  $daoName = $fieldInfo['FKClassName'];
  $dao = new $daoName();
  $dao->id = $fieldValue;
  $dao->selectAdd();
  $dao->selectAdd('id');
  if (!$dao->find()) {
    throw new API_Exception("$fieldName is not valid : " . $fieldValue);
  }
}

/**
 * Validate foreign constraint fields being passed into API.
 *
 * @param array $params
 *   Params from civicrm_api.
 * @param string $fieldName
 *   Uniquename of field being checked.
 *
 * @throws Exception
 */
function _civicrm_api3_validate_unique_key(&$params, &$fieldName) {
  list($fieldValue, $op) = _civicrm_api3_field_value_check($params, $fieldName);
  if (strpos($op, 'NULL') !== FALSE || strpos($op, 'EMPTY') !== FALSE) {
    return;
  }
  $existing = civicrm_api($params['entity'], 'get', array(
      'version' => $params['version'],
      $fieldName => $fieldValue,
    ));
  // an entry already exists for this unique field
  if ($existing['count'] == 1) {
    // question - could this ever be a security issue?
    throw new API_Exception("Field: `$fieldName` must be unique. An conflicting entity already exists - id: " . $existing['id']);
  }
}

/**
 * Generic implementation of the "replace" action.
 *
 * Replace the old set of entities (matching some given keys) with a new set of
 * entities (matching the same keys).
 *
 * @note This will verify that 'values' is present, but it does not directly verify
 * any other parameters.
 *
 * @param string $entity
 *   Entity name.
 * @param array $params
 *   Params from civicrm_api, including:.
 *   - 'values': an array of records to save
 *   - all other items: keys which identify new/pre-existing records.
 *
 * @return array|int
 */
function _civicrm_api3_generic_replace($entity, $params) {

  $transaction = new CRM_Core_Transaction();
  try {
    if (!is_array($params['values'])) {
      throw new Exception("Mandatory key(s) missing from params array: values");
    }

    // Extract the keys -- somewhat scary, don't think too hard about it
    $baseParams = _civicrm_api3_generic_replace_base_params($params);

    // Lookup pre-existing records
    $preexisting = civicrm_api($entity, 'get', $baseParams, $params);
    if (civicrm_error($preexisting)) {
      $transaction->rollback();
      return $preexisting;
    }

    // Save the new/updated records
    $creates = array();
    foreach ($params['values'] as $replacement) {
      // Sugar: Don't force clients to duplicate the 'key' data
      $replacement = array_merge($baseParams, $replacement);
      $action      = (isset($replacement['id']) || isset($replacement[$entity . '_id'])) ? 'update' : 'create';
      $create      = civicrm_api($entity, $action, $replacement);
      if (civicrm_error($create)) {
        $transaction->rollback();
        return $create;
      }
      foreach ($create['values'] as $entity_id => $entity_value) {
        $creates[$entity_id] = $entity_value;
      }
    }

    // Remove stale records
    $staleIDs = array_diff(
      array_keys($preexisting['values']),
      array_keys($creates)
    );
    foreach ($staleIDs as $staleID) {
      $delete = civicrm_api($entity, 'delete', array(
          'version' => $params['version'],
          'id' => $staleID,
        ));
      if (civicrm_error($delete)) {
        $transaction->rollback();
        return $delete;
      }
    }

    return civicrm_api3_create_success($creates, $params);
  }
  catch(PEAR_Exception $e) {
    $transaction->rollback();
    return civicrm_api3_create_error($e->getMessage());
  }
  catch(Exception $e) {
    $transaction->rollback();
    return civicrm_api3_create_error($e->getMessage());
  }
}

/**
 * Replace base parameters.
 *
 * @param array $params
 *
 * @return array
 */
function _civicrm_api3_generic_replace_base_params($params) {
  $baseParams = $params;
  unset($baseParams['values']);
  unset($baseParams['sequential']);
  unset($baseParams['options']);
  return $baseParams;
}

/**
 * Returns fields allowable by api.
 *
 * @param $entity
 *   String Entity to query.
 * @param bool $unique
 *   Index by unique fields?.
 * @param array $params
 *
 * @return array
 */
function _civicrm_api_get_fields($entity, $unique = FALSE, &$params = array()) {
  $unsetIfEmpty = array(
    'dataPattern',
    'headerPattern',
    'default',
    'export',
    'import',
  );
  $dao = _civicrm_api3_get_DAO($entity);
  if (empty($dao)) {
    return array();
  }
  $d = new $dao();
  $fields = $d->fields();

  // Set html attributes for text fields
  foreach ($fields as $name => &$field) {
    if (isset($field['html'])) {
      $field['html'] += (array) $d::makeAttribute($field);
    }
  }

  // replace uniqueNames by the normal names as the key
  if (empty($unique)) {
    foreach ($fields as $name => &$field) {
      //getting rid of unused attributes
      foreach ($unsetIfEmpty as $attr) {
        if (empty($field[$attr])) {
          unset($field[$attr]);
        }
      }
      if ($name == $field['name']) {
        continue;
      }
      if (array_key_exists($field['name'], $fields)) {
        $field['error'] = 'name conflict';
        // it should never happen, but better safe than sorry
        continue;
      }
      $fields[$field['name']] = $field;
      $fields[$field['name']]['uniqueName'] = $name;
      unset($fields[$name]);
    }
  }
  // Translate FKClassName to the corresponding api
  foreach ($fields as $name => &$field) {
    if (!empty($field['FKClassName'])) {
      $FKApi = CRM_Core_DAO_AllCoreTables::getBriefName($field['FKClassName']);
      if ($FKApi) {
        $field['FKApiName'] = $FKApi;
      }
    }
  }
  $fields += _civicrm_api_get_custom_fields($entity, $params);
  return $fields;
}

/**
 * Return an array of fields for a given entity.
 *
 * This is the same as the BAO function but fields are prefixed with 'custom_' to represent api params.
 *
 * @param $entity
 * @param array $params
 *
 * @return array
 */
function _civicrm_api_get_custom_fields($entity, &$params) {
  $entity = _civicrm_api_get_camel_name($entity);
  if ($entity == 'Contact') {
    // Use sub-type if available, otherwise "NULL" to fetch from all contact types
    $entity = CRM_Utils_Array::value('contact_type', $params);
  }
  $customfields = CRM_Core_BAO_CustomField::getFields($entity,
    FALSE,
    FALSE,
    // we could / should probably test for other subtypes here - e.g. activity_type_id
    CRM_Utils_Array::value('contact_sub_type', $params),
    NULL,
    FALSE,
    FALSE,
    FALSE
  );

  $ret = array();

  foreach ($customfields as $key => $value) {
    // Regular fields have a 'name' property
    $value['name'] = 'custom_' . $key;
    $value['title'] = $value['label'];
    $value['type'] = _getStandardTypeFromCustomDataType($value);
    $ret['custom_' . $key] = $value;
  }
  return $ret;
}

/**
 * Translate the custom field data_type attribute into a std 'type'.
 *
 * @param array $value
 *
 * @return int
 */
function _getStandardTypeFromCustomDataType($value) {
  $dataType = $value['data_type'];
  //CRM-15792 - If date custom field contains timeformat change type to DateTime
  if ($value['data_type'] == 'Date' && isset($value['time_format']) && $value['time_format'] > 0) {
    $dataType = 'DateTime';
  }
  $mapping = array(
    'String' => CRM_Utils_Type::T_STRING,
    'Int' => CRM_Utils_Type::T_INT,
    'Money' => CRM_Utils_Type::T_MONEY,
    'Memo' => CRM_Utils_Type::T_LONGTEXT,
    'Float' => CRM_Utils_Type::T_FLOAT,
    'Date' => CRM_Utils_Type::T_DATE,
    'DateTime' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
    'Boolean' => CRM_Utils_Type::T_BOOLEAN,
    'StateProvince' => CRM_Utils_Type::T_INT,
    'File' => CRM_Utils_Type::T_STRING,
    'Link' => CRM_Utils_Type::T_STRING,
    'ContactReference' => CRM_Utils_Type::T_INT,
    'Country' => CRM_Utils_Type::T_INT,
  );
  return $mapping[$dataType];
}


/**
 * Fill params array with alternate (alias) values where a field has an alias and that is filled & the main field isn't.
 *
 * If multiple aliases the last takes precedence
 *
 * Function also swaps unique fields for non-unique fields & vice versa.
 *
 * @param $apiRequest
 * @param $fields
 */
function _civicrm_api3_swap_out_aliases(&$apiRequest, $fields) {
  foreach ($fields as $field => $values) {
    $uniqueName = CRM_Utils_Array::value('uniqueName', $values);
    if (!empty($values['api.aliases'])) {
      // if aliased field is not set we try to use field alias
      if (!isset($apiRequest['params'][$field])) {
        foreach ($values['api.aliases'] as $alias) {
          if (isset($apiRequest['params'][$alias])) {
            $apiRequest['params'][$field] = $apiRequest['params'][$alias];
          }
          //unset original field  nb - need to be careful with this as it may bring inconsistencies
          // out of the woodwork but will be implementing only as _spec function extended
          unset($apiRequest['params'][$alias]);
        }
      }
    }
    if (!isset($apiRequest['params'][$field]) && !empty($values['name']) && $field != $values['name']
      && isset($apiRequest['params'][$values['name']])
    ) {
      $apiRequest['params'][$field] = $apiRequest['params'][$values['name']];
      // note that it would make sense to unset the original field here but tests need to be in place first
      if ($field != 'domain_version') {
        unset($apiRequest['params'][$values['name']]);
      }
    }
    if (!isset($apiRequest['params'][$field])
      && $uniqueName
      && $field != $uniqueName
      && array_key_exists($uniqueName, $apiRequest['params'])
    ) {
      $apiRequest['params'][$field] = CRM_Utils_Array::value($values['uniqueName'], $apiRequest['params']);
      // note that it would make sense to unset the original field here but tests need to be in place first
    }
  }

}

/**
 * Validate integer fields being passed into API.
 *
 * It currently converts the incoming value 'user_contact_id' into the id of the currently logged in user.
 *
 * @param array $params
 *   Params from civicrm_api.
 * @param string $fieldName
 *   Uniquename of field being checked.
 * @param array $fieldInfo
 *   Array of fields from getfields function.
 * @param string $entity
 *
 * @throws API_Exception
 */
function _civicrm_api3_validate_integer(&$params, &$fieldName, &$fieldInfo, $entity) {
  list($fieldValue, $op) = _civicrm_api3_field_value_check($params, $fieldName);
  if (strpos($op, 'NULL') !== FALSE || strpos($op, 'EMPTY') !== FALSE) {
    return;
  }

  if (!empty($fieldValue) || $fieldValue === '0' || $fieldValue === 0) {
    // if value = 'user_contact_id' (or similar), replace value with contact id
    if (!is_numeric($fieldValue) && is_scalar($fieldValue)) {
      $realContactId = _civicrm_api3_resolve_contactID($fieldValue);
      if ('unknown-user' === $realContactId) {
        throw new API_Exception("\"$fieldName\" \"{$fieldValue}\" cannot be resolved to a contact ID", 2002, array('error_field' => $fieldName, "type" => "integer"));
      }
      elseif (is_numeric($realContactId)) {
        $fieldValue = $realContactId;
      }
    }
    if (!empty($fieldInfo['pseudoconstant']) || !empty($fieldInfo['options'])) {
      _civicrm_api3_api_match_pseudoconstant($fieldValue, $entity, $fieldName, $fieldInfo, $op);
    }

    // After swapping options, ensure we have an integer(s)
    foreach ((array) ($fieldValue) as $value) {
      if ($value && !is_numeric($value) && $value !== 'null' && !is_array($value)) {
        throw new API_Exception("$fieldName is not a valid integer", 2001, array('error_field' => $fieldName, "type" => "integer"));
      }
    }

    // Check our field length
    if (is_string($fieldValue) && !empty($fieldInfo['maxlength']) && strlen($fieldValue) > $fieldInfo['maxlength']
      ) {
      throw new API_Exception($fieldValue . " is " . strlen($fieldValue) . " characters  - longer than $fieldName length" . $fieldInfo['maxlength'] . ' characters',
        2100, array('field' => $fieldName, "max_length" => $fieldInfo['maxlength'])
      );
    }
  }

  if (!empty($op)) {
    $params[$fieldName][$op] = $fieldValue;
  }
  else {
    $params[$fieldName] = $fieldValue;
  }
}

/**
 * Determine a contact ID using a string expression.
 *
 * @param string $contactIdExpr
 *   E.g. "user_contact_id" or "@user:username".
 *
 * @return int|NULL|'unknown-user'
 */
function _civicrm_api3_resolve_contactID($contactIdExpr) {
  // If value = 'user_contact_id' replace value with logged in user id.
  if ($contactIdExpr == "user_contact_id") {
    return CRM_Core_Session::getLoggedInContactID();
  }
  elseif (preg_match('/^@user:(.*)$/', $contactIdExpr, $matches)) {
    $config = CRM_Core_Config::singleton();

    $ufID = $config->userSystem->getUfId($matches[1]);
    if (!$ufID) {
      return 'unknown-user';
    }

    $contactID = CRM_Core_BAO_UFMatch::getContactId($ufID);
    if (!$contactID) {
      return 'unknown-user';
    }

    return $contactID;
  }
  return NULL;
}

/**
 * Validate html (check for scripting attack).
 *
 * @param array $params
 * @param string $fieldName
 * @param array $fieldInfo
 *
 * @throws API_Exception
 */
function _civicrm_api3_validate_html(&$params, &$fieldName, $fieldInfo) {
  list($fieldValue, $op) = _civicrm_api3_field_value_check($params, $fieldName);
  if (strpos($op, 'NULL') || strpos($op, 'EMPTY')) {
    return;
  }
  if ($fieldValue) {
    if (!CRM_Utils_Rule::xssString($fieldValue)) {
      throw new API_Exception('Input contains illegal SCRIPT tag.', array("field" => $fieldName, "error_code" => "xss"));
    }
  }
}

/**
 * Validate string fields being passed into API.
 *
 * @param array $params
 *   Params from civicrm_api.
 * @param string $fieldName
 *   Uniquename of field being checked.
 * @param array $fieldInfo
 *   Array of fields from getfields function.
 * @param string $entity
 *
 * @throws API_Exception
 * @throws Exception
 */
function _civicrm_api3_validate_string(&$params, &$fieldName, &$fieldInfo, $entity) {
  list($fieldValue, $op) = _civicrm_api3_field_value_check($params, $fieldName, 'String');
  if (strpos($op, 'NULL') !== FALSE || strpos($op, 'EMPTY') !== FALSE || CRM_Utils_System::isNull($fieldValue)) {
    return;
  }

  if (!is_array($fieldValue)) {
    $fieldValue = (string) $fieldValue;
  }
  else {
    //@todo what do we do about passed in arrays. For many of these fields
    // the missing piece of functionality is separating them to a separated string
    // & many save incorrectly. But can we change them wholesale?
  }
  if ($fieldValue) {
    foreach ((array) $fieldValue as $value) {
      if (!CRM_Utils_Rule::xssString($fieldValue)) {
        throw new Exception('Input contains illegal SCRIPT tag.');
      }
      if ($fieldName == 'currency') {
        //When using IN operator $fieldValue is a array of currency codes
        if (!CRM_Utils_Rule::currencyCode($value)) {
          throw new Exception("Currency not a valid code: $currency");
        }
      }
    }
  }
  if (!empty($fieldInfo['pseudoconstant']) || !empty($fieldInfo['options'])) {
    _civicrm_api3_api_match_pseudoconstant($fieldValue, $entity, $fieldName, $fieldInfo, $op);
  }
  // Check our field length
  elseif (is_string($fieldValue) && !empty($fieldInfo['maxlength']) && strlen(utf8_decode($fieldValue)) > $fieldInfo['maxlength']) {
    throw new API_Exception("Value for $fieldName is " . strlen(utf8_decode($value)) . " characters  - This field has a maxlength of {$fieldInfo['maxlength']} characters.",
      2100, array('field' => $fieldName)
    );
  }

  if (!empty($op)) {
    $params[$fieldName][$op] = $fieldValue;
  }
  else {
    $params[$fieldName] = $fieldValue;
  }
}

/**
 * Validate & swap out any pseudoconstants / options.
 *
 * @param mixed $fieldValue
 * @param string $entity : api entity name
 * @param string $fieldName : field name used in api call (not necessarily the canonical name)
 * @param array $fieldInfo : getfields meta-data
 * @param string $op
 *
 * @throws \API_Exception
 */
function _civicrm_api3_api_match_pseudoconstant(&$fieldValue, $entity, $fieldName, $fieldInfo, $op = '=') {
  if (in_array($op, array('>', '<', '>=', '<=', 'LIKE', 'NOT LIKE'))) {
    return;
  }

  $options = CRM_Utils_Array::value('options', $fieldInfo);

  if (!$options) {
    if (strtolower($entity) == 'profile' && !empty($fieldInfo['entity'])) {
      // We need to get the options from the entity the field relates to.
      $entity = $fieldInfo['entity'];
    }
    $options = civicrm_api($entity, 'getoptions', array(
      'version' => 3,
      'field' => $fieldInfo['name'],
      'context' => 'validate',
    ));
    $options = CRM_Utils_Array::value('values', $options, array());
  }

  // If passed a value-separated string, explode to an array, then re-implode after matching values.
  $implode = FALSE;
  if (is_string($fieldValue) && strpos($fieldValue, CRM_Core_DAO::VALUE_SEPARATOR) !== FALSE) {
    $fieldValue = CRM_Utils_Array::explodePadded($fieldValue);
    $implode = TRUE;
  }
  // If passed multiple options, validate each.
  if (is_array($fieldValue)) {
    foreach ($fieldValue as &$value) {
      if (!is_array($value)) {
        _civicrm_api3_api_match_pseudoconstant_value($value, $options, $fieldName);
      }
    }
    // TODO: unwrap the call to implodePadded from the conditional and do it always
    // need to verify that this is safe and doesn't break anything though.
    // Better yet would be to leave it as an array and ensure that every dao/bao can handle array input
    if ($implode) {
      CRM_Utils_Array::implodePadded($fieldValue);
    }
  }
  else {
    _civicrm_api3_api_match_pseudoconstant_value($fieldValue, $options, $fieldName);
  }
}

/**
 * Validate & swap a single option value for a field.
 *
 * @param string $value field value
 * @param array $options array of options for this field
 * @param string $fieldName field name used in api call (not necessarily the canonical name)
 *
 * @throws API_Exception
 */
function _civicrm_api3_api_match_pseudoconstant_value(&$value, $options, $fieldName) {
  // If option is a key, no need to translate
  // or if no options are avaiable for pseudoconstant 'table' property
  if (array_key_exists($value, $options) || !$options) {
    return;
  }

  // Translate value into key
  // Cast $value to string to avoid a bug in array_search
  $newValue = array_search((string) $value, $options);
  if ($newValue !== FALSE) {
    $value = $newValue;
    return;
  }
  // Case-insensitive matching
  $newValue = strtolower($value);
  $options = array_map("strtolower", $options);
  $newValue = array_search($newValue, $options);
  if ($newValue === FALSE) {
    throw new API_Exception("'$value' is not a valid option for field $fieldName", 2001, array('error_field' => $fieldName));
  }
  $value = $newValue;
}

/**
 * Returns the canonical name of a field.
 *
 * @param $entity
 *   api entity name (string should already be standardized - no camelCase).
 * @param $fieldName
 *   any variation of a field's name (name, unique_name, api.alias).
 *
 * @param string $action
 *
 * @return bool|string
 *   FieldName or FALSE if the field does not exist
 */
function _civicrm_api3_api_resolve_alias($entity, $fieldName, $action = 'create') {
  if (!$fieldName) {
    return FALSE;
  }
  if (strpos($fieldName, 'custom_') === 0 && is_numeric($fieldName[7])) {
    return $fieldName;
  }
  if ($fieldName == _civicrm_api_get_entity_name_from_camel($entity) . '_id') {
    return 'id';
  }
  $result = civicrm_api($entity, 'getfields', array(
    'version' => 3,
    'action' => $action,
  ));
  $meta = $result['values'];
  if (!isset($meta[$fieldName]['name']) && isset($meta[$fieldName . '_id'])) {
    $fieldName = $fieldName . '_id';
  }
  if (isset($meta[$fieldName])) {
    return $meta[$fieldName]['name'];
  }
  foreach ($meta as $info) {
    if ($fieldName == $info['name'] || $fieldName == CRM_Utils_Array::value('uniqueName', $info)) {
      return $info['name'];
    }
    if (array_search($fieldName, CRM_Utils_Array::value('api.aliases', $info, array())) !== FALSE) {
      return $info['name'];
    }
  }
  // Create didn't work, try with get
  if ($action == 'create') {
    return _civicrm_api3_api_resolve_alias($entity, $fieldName, 'get');
  }
  return FALSE;
}

/**
 * Check if the function is deprecated.
 *
 * @param string $entity
 * @param array $result
 *
 * @return string|array|null
 */
function _civicrm_api3_deprecation_check($entity, $result = array()) {
  if ($entity) {
    $apiFile = "api/v3/$entity.php";
    if (CRM_Utils_File::isIncludable($apiFile)) {
      require_once $apiFile;
    }
    $lowercase_entity = _civicrm_api_get_entity_name_from_camel($entity);
    $fnName = "_civicrm_api3_{$lowercase_entity}_deprecation";
    if (function_exists($fnName)) {
      return $fnName($result);
    }
  }
}

/**
 * Get the actual field value.
 *
 * In some case $params[$fieldName] holds Array value in this format Array([operator] => [value])
 * So this function returns the actual field value.
 *
 * @param array $params
 * @param string $fieldName
 * @param string $type
 *
 * @return mixed
 */
function _civicrm_api3_field_value_check(&$params, $fieldName, $type = NULL) {
  $fieldValue = CRM_Utils_Array::value($fieldName, $params);
  $op = NULL;

  if (!empty($fieldValue) && is_array($fieldValue) &&
    (array_search(key($fieldValue), CRM_Core_DAO::acceptedSQLOperators()) ||
      $type == 'String' && strstr(key($fieldValue), 'EMPTY'))
  ) {
    $op = key($fieldValue);
    $fieldValue = CRM_Utils_Array::value($op, $fieldValue);
  }
  return array($fieldValue, $op);
}

/**
 * A generic "get" API based on simple array data. This is comparable to
 * _civicrm_api3_basic_get but does not use DAO/BAO. This is useful for
 * small/mid-size data loaded from external JSON or XML documents.
 *
 * @param $entity
 * @param array $params
 *   API parameters.
 * @param array $records
 *   List of all records.
 * @param string $idCol
 *   The property which defines the ID of a record
 * @param array $fields
 *   List of filterable fields.
 *
 * @return array
 * @throws \API_Exception
 */
function _civicrm_api3_basic_array_get($entity, $params, $records, $idCol, $fields) {
  $options = _civicrm_api3_get_options_from_params($params, TRUE, $entity, 'get');
  // TODO // $sort = CRM_Utils_Array::value('sort', $options, NULL);
  $offset = CRM_Utils_Array::value('offset', $options);
  $limit = CRM_Utils_Array::value('limit', $options);

  $matches = array();

  $currentOffset = 0;
  foreach ($records as $record) {
    if ($idCol != 'id') {
      $record['id'] = $record[$idCol];
    }
    $match = TRUE;
    foreach ($params as $k => $v) {
      if ($k == 'id') {
        $k = $idCol;
      }
      if (in_array($k, $fields) && $record[$k] != $v) {
        $match = FALSE;
        break;
      }
    }
    if ($match) {
      if ($currentOffset >= $offset) {
        $matches[$record[$idCol]] = $record;
      }
      if ($limit && count($matches) >= $limit) {
        break;
      }
      $currentOffset++;
    }
  }

  $return = CRM_Utils_Array::value('return', $options, array());
  if (!empty($return)) {
    $return['id'] = 1;
    $matches = CRM_Utils_Array::filterColumns($matches, array_keys($return));
  }

  return civicrm_api3_create_success($matches, $params);
}

/**
 * @param string $bao_name
 * @param array $params
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function _civicrm_api3_check_edit_permissions($bao_name, $params) {
  // For lack of something more clever, here's a whitelist of entities whos permissions
  // are inherited from a contact record.
  // Note, when adding here, also remember to modify _civicrm_api3_permissions()
  $contactEntities = array(
    'CRM_Core_BAO_Email',
    'CRM_Core_BAO_Phone',
    'CRM_Core_BAO_Address',
    'CRM_Core_BAO_IM',
    'CRM_Core_BAO_Website',
  );
  if (!empty($params['check_permissions']) && in_array($bao_name, $contactEntities)) {
    $cid = !empty($params['contact_id']) ? $params['contact_id'] : CRM_Core_DAO::getFieldValue($bao_name, $params['id'], 'contact_id');
    if (!CRM_Contact_BAO_Contact_Permission::allow($cid, CRM_Core_Permission::EDIT)) {
      throw new \Civi\API\Exception\UnauthorizedException('Permission denied to modify contact record');
    }
  }
}
