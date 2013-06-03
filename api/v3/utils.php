<?php
// $Id$

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.3                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * File for CiviCRM APIv3 utilitity functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_utils
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: utils.php 30879 2010-11-22 15:45:55Z shot $
 *
 */

/**
 * Initialize CiviCRM - should be run at the start of each API function
 */
function _civicrm_api3_initialize() {
  require_once 'CRM/Core/Config.php';
  $config = CRM_Core_Config::singleton();
  }

/**
 * Wrapper Function for civicrm_verify_mandatory to make it simple to pass either / or fields for checking
 *
 * @param array $params array of fields to check
 * @param array $daoName string DAO to check for required fields (create functions only)
 * @param array $keys list of required fields options. One of the options is required
 * @return null or throws error if there the required fields not present

 * @
 *
 */
function civicrm_api3_verify_one_mandatory($params, $daoName = NULL, $keyoptions = array(
  )) {
  $keys = array(array());
  foreach ($keyoptions as $key) {
    $keys[0][] = $key;
  }
  civicrm_api3_verify_mandatory($params, $daoName, $keys);
}

/**
 * Function to check mandatory fields are included
 *
 * @param array $params array of fields to check
 * @param array $daoName string DAO to check for required fields (create functions only)
 * @param array $keys list of required fields. A value can be an array denoting that either this or that is required.
 * @param bool $verifyDAO
 *
 * @return null or throws error if there the required fields not present
 *
 * @todo see notes on _civicrm_api3_check_required_fields regarding removing $daoName param
 */
function civicrm_api3_verify_mandatory($params, $daoName = NULL, $keys = array(
  ), $verifyDAO = TRUE) {

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
          // as long as there is one match then we don't need to rtn anything
          $match = 1;
        }
      }
      if (empty($match) && !empty($optionset)) {
        $unmatched[] = "one of (" . implode(", ", $optionset) . ")";
      }
    }
    else {
      if (!array_key_exists($key, $params) || empty($params[$key])) {
        $unmatched[] = $key;
      }
    }
  }
  if (!empty($unmatched)) {
    throw new API_Exception("Mandatory key(s) missing from params array: " . implode(", ", $unmatched),"mandatory_missing",array("fields"=>$unmatched));
  }
}

/**
 *
 * @param <type> $msg
 * @param <type> $data
 * @param object $dao DAO / BAO object to be freed here
 *
 * @return <type>
 */
function civicrm_api3_create_error($msg, $data = array(), &$dao = NULL) {
  //fix me - $dao should be param 4 & 3 should be $apiRequest
  if (is_object($dao)) {
    $dao->free();
  }

  if (is_array($dao)) {
    if ($msg == 'DB Error: constraint violation' || substr($msg, 0,9)  == 'DB Error:' || $msg == 'DB Error: already exists') {
      try {
        _civicrm_api3_validate_fields($dao['entity'], $dao['action'], $dao['params'], TRUE);
      }
      catch(Exception $e) {
        $msg = $e->getMessage();
      }
    }
  }
  $data['is_error'] = 1;
  $data['error_message'] = $msg;
  if (is_array($dao) && isset($dao['params']) && is_array($dao['params']) && CRM_Utils_Array::value('api.has_parent', $dao['params'])) {
    $errorCode = empty($data['error_code']) ? 'chained_api_failed' : $data['error_code'];
    throw new API_Exception('Error in call to ' . $dao['entity'] . '_' . $dao['action'] . ' : ' . $msg, $errorCode, $data);
  }
  return $data;
}

/**
 * Format array in result output styple
 *
 * @param array $values values generated by API operation (the result)
 * @param array $params parameters passed into API call
 * @param string $entity the entity being acted on
 * @param string $action the action passed to the API
 * @param object $dao DAO object to be freed here
 * @param array $extraReturnValues additional values to be added to top level of result array(
 *   - this param is currently used for legacy behaviour support
 *
 * @return array $result
 */
function civicrm_api3_create_success($values = 1, $params = array(
  ), $entity = NULL, $action = NULL, &$dao = NULL, $extraReturnValues = array()) {
  $result = array();
  $result['is_error'] = 0;
  //lets set the ['id'] field if it's not set & we know what the entity is
  if (is_array($values) && !empty($entity)) {
    foreach ($values as $key => $item) {
      if (empty($item['id']) && !empty($item[$entity . "_id"])) {
        $values[$key]['id'] = $item[$entity . "_id"];
      }
      if(!empty($item['financial_type_id'])){
        $values[$key]['contribution_type_id'] = $item['financial_type_id'];
      }
    }
  }
  //if ( array_key_exists ('debug',$params) && is_object ($dao)) {
  if (is_array($params) && array_key_exists('debug', $params)) {
    if (!is_object($dao)) {
      $d = _civicrm_api3_get_DAO(CRM_Utils_Array::value('entity', $params));
      if (!empty($d)) {
        $file = str_replace('_', '/', $d) . ".php";
        require_once ($file);
        $dao = new $d();
      }
    }
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
    $undefined = array_diff($paramFields, $allFields, array_keys($_COOKIE), array('action', 'entity', 'debug', 'version', 'check_permissions', 'IDS_request_uri', 'IDS_user_agent', 'return', 'sequential', 'rowCount', 'option_offset', 'option_limit', 'custom', 'option_sort'));
    if ($undefined) {
      $result['undefined_fields'] = array_merge($undefined);
    }
  }
  if (is_object($dao)) {
    $dao->free();
  }

  $result['version'] = 3;
  if (is_array($values)) {
    $result['count'] = count($values);

    // Convert value-separated strings to array
    _civicrm_api3_separate_values($values);

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

  return array_merge($result, $extraReturnValues);
}

/**
 * Load the DAO of the entity
 */
function _civicrm_api3_load_DAO($entity) {
  $dao = _civicrm_api3_get_DAO($entity);
  if (empty($dao)) {
    return FALSE;
  }
  $file = str_replace('_', '/', $dao) . ".php";
  require_once ($file);
  $d = new $dao();
  return $d;
}

/**
 * Function to return the DAO of the function or Entity
 * @param  $name is either a function of the api (civicrm_{entity}_create or the entity name
 * return the DAO name to manipulate this function
 * eg. "civicrm_api3_contact_create" or "Contact" will return "CRM_Contact_BAO_Contact"
 */
function _civicrm_api3_get_DAO($name) {
  static $dao = NULL;
  if (!$dao) {
    require ('CRM/Core/DAO/listAll.php');
  }

  if (strpos($name, 'civicrm_api3') !== FALSE) {
    $last = strrpos($name, '_');
    // len ('civicrm_api3_') == 13
    $name = substr($name, 13, $last - 13);
  }

  if (strtolower($name) == 'individual' || strtolower($name) == 'household' || strtolower($name) == 'organization') {
    $name = 'Contact';
  }

  //hack to deal with incorrectly named BAO/DAO - see CRM-10859 - remove after rename
  if($name == 'price_set'){
    return 'CRM_Price_DAO_Set';
  }
  if($name == 'price_field'){
    return 'CRM_Price_DAO_Field';
  }
  if($name == 'price_field_value'){
    return 'CRM_Price_DAO_FieldValue';
  }
  // these aren't listed on ticket CRM-10859 - but same problem - lack of standardisation
  if($name == 'mailing_job' || $name == 'MailingJob'){
    return 'CRM_Mailing_BAO_Job';
  }
  if($name == 'mailing_recipients' || $name == 'MailingRecipients'){
    return 'CRM_Mailing_BAO_Recipients';
  }
  if(strtolower($name) == 'im'){
    return 'CRM_Core_BAO_IM';
  }


  return CRM_Utils_Array::value(_civicrm_api_get_camel_name($name, 3), $dao);
}

/**
 * Function to return the DAO of the function or Entity
 * @param  $name is either a function of the api (civicrm_{entity}_create or the entity name
 * return the DAO name to manipulate this function
 * eg. "civicrm_contact_create" or "Contact" will return "CRM_Contact_BAO_Contact"
 */
function _civicrm_api3_get_BAO($name) {
  $dao = _civicrm_api3_get_DAO($name);
  $dao = str_replace("DAO", "BAO", $dao);
  return $dao;
}

/**
 *  Recursive function to explode value-separated strings into arrays
 *
 */
function _civicrm_api3_separate_values(&$values) {
  $sp = CRM_Core_DAO::VALUE_SEPARATOR;
  foreach ($values as $key => & $value) {
    if (is_array($value)) {
      _civicrm_api3_separate_values($value);
    }
    elseif (is_string($value)) {
      if($key == 'case_type_id'){// this is to honor the way case API was originally written
        $value = trim(str_replace($sp, ',', $value), ',');
      }
      elseif (strpos($value, $sp) !== FALSE) {
        $value = explode($sp, trim($value, $sp));
      }
    }
  }
}

/**
 * This is a wrapper for api_store_values which will check the suitable fields using getfields
 * rather than DAO->fields
 *
 * Getfields has handling for how to deal with uniquenames which dao->fields doesn't
 *
 * Note this is used by BAO type create functions - eg. contribution
 * @param string $entity
 * @param array $params
 * @param array $values
 */
function _civicrm_api3_filter_fields_for_bao($entity, &$params, &$values){
  $fields = civicrm_api($entity,'getfields', array('version' => 3,'action' => 'create'));
  $fields = $fields['values'];
  _civicrm_api3_store_values($fields, $params, $values);
}
/**
 *
 * @param array $fields
 * @param array $params
 * @param array $values
 *
 * @return Bool $valueFound
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
 * The API supports 2 types of get requestion. The more complex uses the BAO query object.
 *  This is a generic function for those functions that call it
 *
 *  At the moment only called by contact we should extend to contribution &
 *  others that use the query object. Note that this function passes permission information in.
 *  The others don't
 *
 * @param array $params as passed into api get or getcount function
 * @param array $options array of options (so we can modify the filter)
 * @param bool $getCount are we just after the count
 */
function _civicrm_api3_get_using_query_object($entity, $params, $additional_options = array(), $getCount = NULL){

  // Convert id to e.g. contact_id
  if (empty($params[$entity . '_id']) && isset($params['id'])) {
    $params[$entity . '_id'] = $params['id'];
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
  if(empty($returnProperties)){
    $returnProperties = NULL;
  }
  if(!empty($params['check_permissions'])){
    // we will filter query object against getfields
    $fields = civicrm_api($entity, 'getfields', array('version' => 3, 'action' => 'get'));
    // we need to add this in as earlier in this function 'id' was unset in favour of $entity_id
    $fields['values'][$entity . '_id'] = array();
    $varsToFilter = array('returnProperties', 'inputParams');
    foreach ($varsToFilter as $varToFilter){
      if(!is_array($$varToFilter)){
        continue;
      }
      //I was going to throw an exception rather than silently filter out - but
      //would need to diff out of exceptions arr other keys like 'options', 'return', 'api. etcetc
      //so we are silently ignoring parts of their request
      //$exceptionsArr = array_diff(array_keys($$varToFilter), array_keys($fields['values']));
      $$varToFilter = array_intersect_key($$varToFilter, $fields['values']);
    }
  }
  $options = array_merge($options,$additional_options);
  $sort             = CRM_Utils_Array::value('sort', $options, NULL);
  $offset             = CRM_Utils_Array::value('offset', $options, NULL);
  $limit             = CRM_Utils_Array::value('limit', $options, NULL);
  $smartGroupCache  = CRM_Utils_Array::value('smartGroupCache', $params);

  if($getCount){
    $limit = NULL;
    $returnProperties = NULL;
  }

  $newParams = CRM_Contact_BAO_Query::convertFormValues($inputParams);
  $skipPermissions = CRM_Utils_Array::value('check_permissions', $params)? 0 :1;
  list($entities, $options) = CRM_Contact_BAO_Query::apiQuery(
    $newParams,
    $returnProperties,
    NULL,
    $sort,
    $offset ,
    $limit,
    $smartGroupCache,
    $getCount,
    $skipPermissions
  );
  if ($getCount) { // only return the count of contacts
    return $entities;
  }

  return $entities;
}

/**
 * Function transfers the filters being passed into the DAO onto the params object
 */
function _civicrm_api3_dao_set_filter(&$dao, $params, $unique = TRUE, $entity) {
  $entity = substr($dao->__table, 8);

  $allfields = _civicrm_api3_build_fields_array($dao, $unique);

  $fields = array_intersect(array_keys($allfields), array_keys($params));
  if (isset($params[$entity . "_id"])) {
    //if entity_id is set then treat it as ID (will be overridden by id if set)
    $dao->id = $params[$entity . "_id"];
  }
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
  // http://issues.civicrm.org/jira/browse/CRM-9150 - stick with 'simple' operators for now
  // support for other syntaxes is discussed in ticket but being put off for now
  $acceptedSQLOperators = array('=', '<=', '>=', '>', '<', 'LIKE', "<>", "!=", "NOT LIKE", 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN');
  if (!$fields) {
    $fields = array();
  }

  foreach ($fields as $field) {
    if (is_array($params[$field])) {
      //get the actual fieldname from db
      $fieldName = $allfields[$field]['name'];
      //array is the syntax for SQL clause
      foreach ($params[$field] as $operator => $criteria) {
        if (in_array($operator, $acceptedSQLOperators)) {
          switch ($operator) {
            // unary operators

            case 'IS NULL':
            case 'IS NOT NULL':
              $dao->whereAdd(sprintf('%s %s', $fieldName, $operator));
              break;

            // ternary operators

            case 'BETWEEN':
            case 'NOT BETWEEN':
              if (empty($criteria[0]) || empty($criteria[1])) {
                throw new exception("invalid criteria for $operator");
              }
              $dao->whereAdd(sprintf('%s ' . $operator . ' "%s" AND "%s"', $fieldName, CRM_Core_DAO::escapeString($criteria[0]), CRM_Core_DAO::escapeString($criteria[1])));
              break;

            // n-ary operators

            case 'IN':
            case 'NOT IN':
              if (empty($criteria)) {
                throw new exception("invalid criteria for $operator");
              }
              $escapedCriteria = array_map(array('CRM_Core_DAO', 'escapeString'), $criteria);
              $dao->whereAdd(sprintf('%s %s ("%s")', $fieldName, $operator, implode('", "', $escapedCriteria)));
              break;

            // binary operators

            default:

              $dao->whereAdd(sprintf('%s %s "%s"', $fieldName, $operator, CRM_Core_DAO::escapeString($criteria)));
          }
        }
      }
    }
    else {
      if ($unique) {
        $dao->$allfields[$field]['name'] = $params[$field];
      }
      else {
        $dao->$field = $params[$field];
      }
    }
  }
  if (!empty($params['return']) && is_array($params['return'])) {
    $dao->selectAdd();
    $allfields =  _civicrm_api3_get_unique_name_array($dao);
    $returnMatched = array_intersect($params['return'], $allfields);
    $returnUniqueMatched = array_intersect(
      array_diff(// not already matched on the field names
        $params['return'],
        $returnMatched),
        array_flip($allfields)// but a match for the field keys
    );

    foreach ($returnMatched as $returnValue) {
        $dao->selectAdd($returnValue);
    }
    foreach ($returnUniqueMatched as $uniqueVal){
      $dao->selectAdd($allfields[$uniqueVal]);

    }
    $dao->selectAdd('id');
  }
}

/**
 * Apply filters (e.g. high, low) to DAO object (prior to find)
 * @param string $filterField field name of filter
 * @param string $filterValue field value of filter
 * @param object $dao DAO object
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
  if($filterField == 'is_current' && $filterValue == 1){
    $todayStart = date('Ymd000000', strtotime('now'));
    $todayEnd = date('Ymd235959', strtotime('now'));
    $dao->whereAdd("(start_date <= '$todayStart' OR start_date IS NULL) AND (end_date >= '$todayEnd' OR end_date IS NULL)");
    if(property_exists($dao, 'is_active')){
      $dao->whereAdd('is_active = 1');
    }
  }
}

/**
 * Get sort, limit etc options from the params - supporting old & new formats.
 * get returnproperties for legacy
 * @param array $params params array as passed into civicrm_api
 * @param bool $queryObject - is this supporting a queryobject api (e.g contact) - if so we support more options
 * for legacy report & return a unique fields array
 * @return array $options options extracted from params
 */
function _civicrm_api3_get_options_from_params(&$params, $queryObject = FALSE, $entity = '', $action = '') {
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
  if($entity && $action =='get' ){
    if(CRM_Utils_Array::value('id',$returnProperties)){
      $returnProperties[$entity . '_id'] = 1;
      unset($returnProperties['id']);
    }
    switch (trim(strtolower($sort))){
    case 'id':
    case 'id desc':
    case 'id asc':
      $sort = str_replace('id', $entity . '_id',$sort);
    }
  }


  $options = array(
    'offset' => $offset,
    'sort' => $sort,
    'limit' => $limit,
    'return' => !empty($returnProperties) ? $returnProperties : NULL,
  );
  if (!$queryObject) {
    return $options;
  }
  //here comes the legacy support for $returnProperties, $inputParams e.g for contat_get
  // if the queryobject is being used this should be used
  $inputParams = array();
  $legacyreturnProperties = array();
  $otherVars = array(
    'sort', 'offset', 'rowCount', 'options','return',
  );
  foreach ($params as $n => $v) {
    if (substr($n, 0, 7) == 'return.') {
      $legacyreturnProperties[substr($n, 7)] = $v;
    }
    elseif($n == 'id'){
      $inputParams[$entity. '_id'] = $v;
    }
    elseif (in_array($n, $otherVars)) {}
    else{
      $inputParams[$n] = $v;
    }
  }
  $options['return'] = array_merge($returnProperties, $legacyreturnProperties);
  $options['input_params'] = $inputParams;
  return $options;
}

/**
 * Apply options (e.g. sort, limit, order by) to DAO object (prior to find)
 * @param array $params params array as passed into civicrm_api
 * @param object $dao DAO object
 */
function _civicrm_api3_apply_options_to_dao(&$params, &$dao, $entity) {

  $options = _civicrm_api3_get_options_from_params($params,FALSE,$entity);
  $dao->limit((int)$options['offset'], (int)$options['limit']);
  if (!empty($options['sort'])) {
    $dao->orderBy($options['sort']);
  }
}

/**
 * build fields array. This is the array of fields as it relates to the given DAO
 * returns unique fields as keys by default but if set but can return by DB fields
 */
function _civicrm_api3_build_fields_array(&$bao, $unique = TRUE) {
  $fields = $bao->fields();
  if ($unique) {
    if(!CRM_Utils_Array::value('id', $fields)){
     $entity = _civicrm_api_get_entity_name_from_dao($bao);
     $fields['id'] = $fields[$entity . '_id'];
     unset($fields[$entity . '_id']);
    }
    return $fields;
  }

  foreach ($fields as $field) {
    $dbFields[$field['name']] = $field;
  }
  return $dbFields;
}

/**
 * build fields array. This is the array of fields as it relates to the given DAO
 * returns unique fields as keys by default but if set but can return by DB fields
 */
function _civicrm_api3_get_unique_name_array(&$bao) {
  $fields = $bao->fields();
  foreach ($fields as $field => $values) {
    $uniqueFields[$field] = CRM_Utils_Array::value('name',$values, $field);
  }
  return $uniqueFields;
}

/**
 * Converts an DAO object to an array
 *
 * @param  object   $dao           (reference )object to convert
 * @params array of arrays (key = id) of array of fields
 * @static void
 * @access public
 */
function _civicrm_api3_dao_to_array($dao, $params = NULL, $uniqueFields = TRUE, $entity = "") {
  $result = array();
  if (empty($dao) || !$dao->find()) {
    return array();
  }

  //if custom fields are required we will endeavour to set them . NB passing $entity in might be a bit clunky / unrequired
  if (!empty($entity) && CRM_Utils_Array::value('return', $params) && is_array($params['return'])) {
    foreach ($params['return'] as $return) {
      if (substr($return, 0, 6) == 'custom') {
        $custom = TRUE;
      }
    }
  }


  $fields = array_keys(_civicrm_api3_build_fields_array($dao, $uniqueFields));

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
    if (!empty($custom)) {
      _civicrm_api3_custom_data_get($result[$dao->id], $entity, $dao->id);
    }
  }


  return $result;
}

/**
 * Converts an object to an array
 *
 * @param  object   $dao           (reference) object to convert
 * @param  array    $values        (reference) array
 * @param  array    $uniqueFields
 *
 * @return array
 * @static void
 * @access public
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
 * Wrapper for _civicrm_object_to_array when api supports unique fields
 */
function _civicrm_api3_object_to_array_unique_fields(&$dao, &$values) {
  return _civicrm_api3_object_to_array($dao, $values, TRUE);
}

/**
 *
 * @param array $params
 * @param array $values
 * @param string $extends entity that this custom field extends (e.g. contribution, event, contact)
 * @param string $entityId ID of entity per $extends
 */
function _civicrm_api3_custom_format_params($params, &$values, $extends, $entityId = NULL) {
  $values['custom'] = array();
  foreach ($params as $key => $value) {
    list($customFieldID, $customValueID) = CRM_Core_BAO_CustomField::getKeyID($key, TRUE);
    if ($customFieldID) {
      CRM_Core_BAO_CustomField::formatCustomField($customFieldID, $values['custom'],
        $value, $extends, $customValueID, $entityId, FALSE, FALSE
      );
    }
  }
}

/**
 * @deprecated
 * This function ensures that we have the right input parameters
 *
 * This function is only called when $dao is passed into verify_mandatory.
 * The practice of passing $dao into verify_mandatory turned out to be
 * unsatisfactory as the required fields @ the dao level is so diffent to the abstract
 * api level. Hence the intention is to remove this function
 * & the associated param from viery_mandatory
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new history.
 * @daoName string DAO to check params agains
 *
 * @return bool should the missing fields be returned as an array (core error created as default)
 *
 * @return bool true if all fields present, depending on $result a core error is created of an array of missing fields is returned
 * @access public
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

    if (CRM_Utils_Array::value('required', $v)) {
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
 * Check permissions for a given API call.
 *
 * @param $entity string API entity being accessed
 * @param $action string API action being performed
 * @param $params array  params of the API call
 * @param $throw bool    whether to throw exception instead of returning false
 *
 * @return bool whether the current API user has the permission to make the call
 */
function _civicrm_api3_api_check_permission($entity, $action, &$params, $throw = TRUE) {
  // return early unless we’re told explicitly to do the permission check
  if (empty($params['check_permissions']) or $params['check_permissions'] == FALSE) {
    return TRUE;
  }

  require_once 'CRM/Core/Permission.php';

  require_once 'CRM/Core/DAO/permissions.php';
  $permissions = _civicrm_api3_permissions($entity, $action, $params);

  // $params might’ve been reset by the alterAPIPermissions() hook
  if (isset($params['check_permissions']) and $params['check_permissions'] == FALSE) {
    return TRUE;
  }

  foreach ($permissions as $perm) {
    if (!CRM_Core_Permission::check($perm)) {
      if ($throw) {
        throw new Exception("API permission check failed for $entity/$action call; missing permission: $perm.");
      }
      else {
        return FALSE;
      }
    }
  }
  return TRUE;
}

/**
 * Function to do a 'standard' api get - when the api is only doing a $bao->find then use this
 *
 * @param string $bao_name name of BAO
 * @param array $params params from api
 * @param bool $returnAsSuccess return in api success format
 */
function _civicrm_api3_basic_get($bao_name, &$params, $returnAsSuccess = TRUE, $entity = "") {
  $bao = new $bao_name();
  _civicrm_api3_dao_set_filter($bao, $params, TRUE,$entity);
  if ($returnAsSuccess) {
    return civicrm_api3_create_success(_civicrm_api3_dao_to_array($bao, $params, FALSE, $entity), $params, $entity);
  }
  else {
    return _civicrm_api3_dao_to_array($bao, $params, FALSE, $entity);
  }
}

/**
 * Function to do a 'standard' api create - when the api is only doing a $bao::create then use this
 * @param string $bao_name Name of BAO Class
 * @param array $params parameters passed into the api call
 * @param string $entity Entity - pass in if entity is non-standard & required $ids array
 */
function _civicrm_api3_basic_create($bao_name, &$params, $entity = NULL) {

  $args = array(&$params);
  if(!empty($entity)){
    $ids = array($entity => CRM_Utils_Array::value('id', $params));
    $args[] = &$ids;
  }
  if (method_exists($bao_name, 'create')) {
    $fct = 'create';
  }
  elseif (method_exists($bao_name, 'add')) {
    $fct = 'add';
  }
  if (!isset($fct)) {
    return civicrm_api3_create_error('Entity not created, missing create or add method for ' . $bao_name);
  }
  $bao = call_user_func_array(array($bao_name, $fct), $args);
  if (is_null($bao)) {
    return civicrm_api3_create_error('Entity not created ' . $bao_name . '::' . $fct);
  }
  else {
    $values = array();
    _civicrm_api3_object_to_array($bao, $values[$bao->id]);
    return civicrm_api3_create_success($values, $params, NULL, 'create', $bao);
  }
}

/**
 * Function to do a 'standard' api del - when the api is only doing a $bao::del then use this
 * if api::del doesn't exist it will try DAO delete method
 */
function _civicrm_api3_basic_delete($bao_name, &$params) {

  civicrm_api3_verify_mandatory($params, NULL, array('id'));
  $args = array(&$params['id']);
  if (method_exists($bao_name, 'del')) {
    $bao = call_user_func_array(array($bao_name, 'del'), $args);
    if ($bao !== FALSE) {
      return civicrm_api3_create_success(TRUE);
    }
    return civicrm_api3_create_error('Could not delete entity id ' . $params['id']);
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
      return civicrm_api3_create_error('Could not delete entity id ' . $params['id']);
    }
  }

  return civicrm_api3_create_error('no delete method found');
}

/**
 * Get custom data for the given entity & Add it to the returnArray as 'custom_123' = 'custom string' AND 'custom_123_1' = 'custom string'
 * Where 123 is field value & 1 is the id within the custom group data table (value ID)
 *
 * @param array $returnArray - array to append custom data too - generally $result[4] where 4 is the entity id.
 * @param string $entity  e.g membership, event
 * @param int $groupID - per CRM_Core_BAO_CustomGroup::getTree
 * @param int $subType e.g. membership_type_id where custom data doesn't apply to all membership types
 * @param string $subName - Subtype of entity
 *
 */
function _civicrm_api3_custom_data_get(&$returnArray, $entity, $entity_id, $groupID = NULL, $subType = NULL, $subName = NULL) {
  require_once 'CRM/Core/BAO/CustomGroup.php';
  require_once 'CRM/Core/BAO/CustomField.php';
  $groupTree = &CRM_Core_BAO_CustomGroup::getTree($entity,
    CRM_Core_DAO::$_nullObject,
    $entity_id,
    $groupID,
    $subType,
    $subName
  );
  $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, 1, CRM_Core_DAO::$_nullObject);
  $customValues = array();
  CRM_Core_BAO_CustomGroup::setDefaults($groupTree, $customValues);
  if (!empty($customValues)) {
    foreach ($customValues as $key => $val) {
      if (strstr($key, '_id')) {
        $idkey = substr($key, 0, -3);
        $returnArray['custom_' . (CRM_Core_BAO_CustomField::getKeyID($idkey) . "_id")] = $val;
        $returnArray[$key] = $val;
      }
      else {
        // per standard - return custom_fieldID
        $returnArray['custom_' . (CRM_Core_BAO_CustomField::getKeyID($key))] = $val;

        //not standard - but some api did this so guess we should keep - cheap as chips
        $returnArray[$key] = $val;
      }
    }
  }
}

/**
 * Validate fields being passed into API. This function relies on the getFields function working accurately
 * for the given API. If error mode is set to TRUE then it will also check
 * foreign keys
 *
 * As of writing only date was implemented.
 * @param string $entity
 * @param string $action
 * @param array $params -
 * all variables are the same as per civicrm_api
 */
function _civicrm_api3_validate_fields($entity, $action, &$params, $errorMode = NULL) {
  //skip any entities without working getfields functions
  $skippedEntities = array('entity', 'mailinggroup', 'customvalue', 'custom_value', 'mailing_group');
  if (in_array(strtolower($entity), $skippedEntities) || strtolower($action) == 'getfields') {
    return;
  }
  $fields = civicrm_api($entity, 'getfields', array('version' => 3, 'action' => $action));
  $fields = array_intersect_key($fields['values'], $params);
  foreach ($fields as $fieldname => $fieldInfo) {
    switch (CRM_Utils_Array::value('type', $fieldInfo)) {
      case CRM_Utils_Type::T_INT:
        //field is of type integer
        _civicrm_api3_validate_integer($params, $fieldname, $fieldInfo, $entity);
        break;

      case 4:
      case 12:
        //field is of type date or datetime
        _civicrm_api3_validate_date($params, $fieldname, $fieldInfo);
        break;

      case 32://blob
        _civicrm_api3_validate_html($params, $fieldname, $fieldInfo);
        break;

      case CRM_Utils_Type::T_STRING:
        _civicrm_api3_validate_string($params, $fieldname, $fieldInfo);
        break;

      case CRM_Utils_Type::T_MONEY:
        if (!CRM_Utils_Rule::money($params[$fieldname])) {
          throw new Exception($fieldname . " is  not a valid amount: " . $params[$fieldname]);
        }
    }

    // intensive checks - usually only called after DB level fail
    if (!empty($errorMode) && strtolower($action) == 'create') {
      if (CRM_Utils_Array::value('FKClassName', $fieldInfo)) {
        if (CRM_Utils_Array::value($fieldname, $params)) {
          _civicrm_api3_validate_constraint($params, $fieldname, $fieldInfo);
        }
        elseif (CRM_Utils_Array::value('required', $fieldInfo)) {
          throw new Exception("DB Constraint Violation - possibly $fieldname should possibly be marked as mandatory for this API. If so, please raise a bug report");
        }
      }
      if (CRM_Utils_Array::value('api.unique', $fieldInfo)) {
        $params['entity'] = $entity;
        _civicrm_api3_validate_uniquekey($params, $fieldname, $fieldInfo);
      }
    }
  }
}

/**
 * Validate date fields being passed into API.
 * It currently converts both unique fields and DB field names to a mysql date.
 * @todo - probably the unique field handling & the if exists handling is now done before this
 * function is reached in the wrapper - can reduce this code down to assume we
 * are only checking the passed in field
 *
 * It also checks against the RULE:date function. This is a centralisation of code that was scattered and
 * may not be the best thing to do. There is no code level documentation on the existing functions to work off
 *
 * @param array $params params from civicrm_api
 * @param string $fieldname uniquename of field being checked
 * @param array $fieldinfo array of fields from getfields function
 */
function _civicrm_api3_validate_date(&$params, &$fieldname, &$fieldInfo) {
  //should we check first to prevent it from being copied if they have passed in sql friendly format?
  if (CRM_Utils_Array::value($fieldInfo['name'], $params)) {
    //accept 'whatever strtotime accepts
    if (strtotime($params[$fieldInfo['name']]) === FALSE) {
      throw new Exception($fieldInfo['name'] . " is not a valid date: " . $params[$fieldInfo['name']]);
    }
    $params[$fieldInfo['name']] = CRM_Utils_Date::processDate($params[$fieldInfo['name']]);
  }
  if ((CRM_Utils_Array::value('name', $fieldInfo) != $fieldname) && CRM_Utils_Array::value($fieldname, $params)) {
    //If the unique field name differs from the db name & is set handle it here
    if (strtotime($params[$fieldname]) === FALSE) {
      throw new Exception($fieldname . " is not a valid date: " . $params[$fieldname]);
    }
    $params[$fieldname] = CRM_Utils_Date::processDate($params[$fieldname]);
  }
}

/**
 * Validate foreign constraint fields being passed into API.
 *
 * @param array $params params from civicrm_api
 * @param string $fieldname uniquename of field being checked
 * @param array $fieldinfo array of fields from getfields function
 */
function _civicrm_api3_validate_constraint(&$params, &$fieldname, &$fieldInfo) {
  $file = str_replace('_', '/', $fieldInfo['FKClassName']) . ".php";
  require_once ($file);
  $dao = new $fieldInfo['FKClassName'];
  $dao->id = $params[$fieldname];
  $dao->selectAdd();
  $dao->selectAdd('id');
  if (!$dao->find()) {
    throw new Exception("$fieldname is not valid : " . $params[$fieldname]);
  }
}

/**
 * Validate foreign constraint fields being passed into API.
 *
 * @param array $params params from civicrm_api
 * @param string $fieldname uniquename of field being checked
 * @param array $fieldinfo array of fields from getfields function
 */
function _civicrm_api3_validate_uniquekey(&$params, &$fieldname, &$fieldInfo) {
  $existing = civicrm_api($params['entity'], 'get', array(
      'version' => $params['version'],
      $fieldname => $params[$fieldname],
    ));
  // an entry already exists for this unique field
  if ($existing['count'] == 1) {
    // question - could this ever be a security issue?
    throw new Exception("Field: `$fieldname` must be unique. An conflicting entity already exists - id: " . $existing['id']);
  }
}

/**
 * Generic implementation of the "replace" action.
 *
 * Replace the old set of entities (matching some given keys) with a new set of
 * entities (matching the same keys).
 *
 * Note: This will verify that 'values' is present, but it does not directly verify
 * any other parameters.
 *
 * @param string $entity entity name
 * @param array $params params from civicrm_api, including:
 *   - 'values': an array of records to save
 *   - all other items: keys which identify new/pre-existing records
 */
function _civicrm_api3_generic_replace($entity, $params) {

  require_once 'CRM/Core/Transaction.php';
  $transaction = new CRM_Core_Transaction();
  try {
    if (!is_array($params['values'])) {
      throw new Exception("Mandatory key(s) missing from params array: values");
    }

    // Extract the keys -- somewhat scary, don't think too hard about it
    $baseParams = $params;
    unset($baseParams['values']);
    unset($baseParams['sequential']);

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
 * returns fields allowable by api
 * @param $entity string Entity to query
 * @param bool $unique index by unique fields?
 */
function _civicrm_api_get_fields($entity, $unique = FALSE, &$params = array(
  )) {
  $unsetIfEmpty = array('dataPattern', 'headerPattern', 'default', 'export', 'import');
  $dao = _civicrm_api3_get_DAO($entity);
  if (empty($dao)) {
    return array();
  }
  $file = str_replace('_', '/', $dao) . ".php";
  require_once ($file);
  $d = new $dao();
  $fields = $d->fields();
  // replace uniqueNames by the normal names as the key
  if (empty($unique)) {
    foreach ($fields as $name => & $field) {
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
  $fields += _civicrm_api_get_custom_fields($entity, $params);
  return $fields;
}

/**
 * Return an array of fields for a given entity - this is the same as the BAO function but
 * fields are prefixed with 'custom_' to represent api params
 */
function _civicrm_api_get_custom_fields($entity, &$params) {
  require_once 'CRM/Core/BAO/CustomField.php';
  $customfields = array();
  $entity = _civicrm_api_get_camel_name($entity);
  if (strtolower($entity) == 'contact') {
    $entity = CRM_Utils_Array::value('contact_type', $params);
  }
  $retrieveOnlyParent = FALSE;
  // we could / should probably test for other subtypes here - e.g. activity_type_id
  if($entity == 'Contact'){
    empty($params['contact_sub_type']);
  }
  $customfields = CRM_Core_BAO_CustomField::getFields($entity,
    FALSE,
    FALSE,
    CRM_Utils_Array::value('contact_sub_type', $params, FALSE),
    NULL,
    $retrieveOnlyParent,
    FALSE,
    FALSE
  );
  // find out if we have any requests to resolve options
  $getoptions = CRM_Utils_Array::value('get_options', CRM_Utils_Array::value('options',$params));
  if(!is_array($getoptions)){
      $getoptions = array($getoptions);
  }

  foreach ($customfields as $key => $value) {
    $customfields['custom_' . $key] = $value;
   if(in_array('custom_' . $key, $getoptions)){
     $customfields['custom_' . $key]['options'] = CRM_Core_BAO_CustomOption::valuesByID($key);
   }
    unset($customfields[$key]);
  }
  return $customfields;
}

/**
 * Return array of defaults for the given API (function is a wrapper on getfields)
 */
function _civicrm_api3_getdefaults($apiRequest) {
  $defaults = array();

  $result = civicrm_api($apiRequest['entity'],
    'getfields',
    array(
      'version' => 3,
      'action' => $apiRequest['action'],
    )
  );

  foreach ($result['values'] as $field => $values) {
    if (isset($values['api.default'])) {
      $defaults[$field] = $values['api.default'];
    }
  }
  return $defaults;
}

/**
 * Return array of defaults for the given API (function is a wrapper on getfields)
 */
function _civicrm_api3_getrequired($apiRequest) {
  $required = array('version');

  $result = civicrm_api($apiRequest['entity'],
    'getfields',
    array(
      'version' => 3,
      'action' => $apiRequest['action'],
    )
  );
  foreach ($result['values'] as $field => $values) {
    if (CRM_Utils_Array::value('api.required', $values)) {
      $required[] = $field;
    }
  }
  return $required;
}

/**
 * Fill params array with alternate (alias) values where a field has an alias and that is filled & the main field isn't
 * If multiple aliases the last takes precedence
 *
 * Function also swaps unique fields for non-unique fields & vice versa.
 */
function _civicrm_api3_swap_out_aliases(&$apiRequest) {
  if (strtolower($apiRequest['action'] == 'getfields')) {
    if (CRM_Utils_Array::value('api_action', $apiRequest['params'])) {
      $apiRequest['params']['action'] = $apiRequest['params']['api_action'];
      unset($apiRequest['params']['api_action']);
    }
    return;
  }
  $result = civicrm_api($apiRequest['entity'],
    'getfields',
    array(
      'version' => 3,
      'action' => $apiRequest['action'],
    )
  );

  foreach ($result['values'] as $field => $values) {
    $uniqueName = CRM_Utils_Array::value('uniqueName', $values);
    if (CRM_Utils_Array::value('api.aliases', $values)) {
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
    if (!isset($apiRequest['params'][$field])
      && CRM_Utils_Array::value('name', $values)
      && $field != $values['name']
      && isset($apiRequest['params'][$values['name']])
    ) {
      $apiRequest['params'][$field] = $apiRequest['params'][$values['name']];
      // note that it would make sense to unset the original field here but tests need to be in place first
    }
    if (!isset($apiRequest['params'][$field])
      && $uniqueName
      && $field != $uniqueName
      && array_key_exists($uniqueName, $apiRequest['params'])
      )
    {
      $apiRequest['params'][$field] = CRM_Utils_Array::value($values['uniqueName'], $apiRequest['params']);
      // note that it would make sense to unset the original field here but tests need to be in place first
    }
  }

}

/**
 * Validate integer fields being passed into API.
 * It currently converts the incoming value 'user_contact_id' into the id of the currenty logged in user
 *
 * @param array $params params from civicrm_api
 * @param string $fieldname uniquename of field being checked
 * @param array $fieldinfo array of fields from getfields function
 */
function _civicrm_api3_validate_integer(&$params, &$fieldname, &$fieldInfo, $entity) {
  //if fieldname exists in params
  if (CRM_Utils_Array::value($fieldname, $params)) {
    //if value = 'user_contact_id' replace value with logged in user id
    if ($params[$fieldname] == "user_contact_id") {
      $session = &CRM_Core_Session::singleton();
      $params[$fieldname] = $session->get('userID');
    }
    if (CRM_Utils_Array::value('pseudoconstant', $fieldInfo) ) {
      $constant = CRM_Utils_Array::value('options', $fieldInfo);
      if (is_numeric($params[$fieldname]) && !CRM_Utils_Array::value('FKClassName',$fieldInfo) && !array_key_exists($params[$fieldname], $fieldInfo['options'])) {
        throw new API_Exception("$fieldname is not valid", 2001, array('error_field' => $fieldname,"type"=>"integer"));
      }
    }
    // we are looking for strings that should be swapped out e.g swap 'Donation' to financial_type_id 1
    if (!is_numeric($params[$fieldname]) && !is_array($params[$fieldname])) {
      if(CRM_Utils_Array::value('FKClassName', $fieldInfo)){
        // we'll get the options for this now since we are doing a swap out
        $options = civicrm_api($entity, 'getoptions', array('version' => 3, 'field' => $fieldname));
        if(empty($options['is_error'])){
          $fieldInfo['options'] = $options['values'];
        }
      }
      if(!empty($fieldInfo['options'])){
        $numericvalue = array_search($params[$fieldname], $fieldInfo['options']);
        if (empty($numericvalue)) {
          throw new Exception("$fieldname " . $params[$fieldname] . " is not valid");
        }
        else {
          $params[$fieldname] = $numericvalue;
        }
      }
    }

    // once we have done any swaps check our field length
    if(is_string($params[$fieldname]) &&
      CRM_Utils_Array::value('maxlength',$fieldInfo)
      && strlen($params[$fieldname]) > $fieldInfo['maxlength']
      ){
      throw new API_Exception( $params[$fieldname] . " is " . strlen($params[$fieldname]) . " characters  - longer than $fieldname length" . $fieldInfo['maxlength'] . ' characters',
        2100, array('field' => $fieldname, "max_length"=>$fieldInfo['maxlength'])
      );
    }
  }
}

function _civicrm_api3_validate_html(&$params, &$fieldname, &$fieldInfo) {
  if ($value = CRM_Utils_Array::value($fieldname, $params)) {
    if (!CRM_Utils_Rule::xssString($value)) {
      throw new API_Exception('Illegal characters in input (potential scripting attack)',array("field"=>$fieldname,"error_code"=>"xss"));
    }
  }
}

/**
 * Validate string fields being passed into API.
 * @param array $params params from civicrm_api
 * @param string $fieldname uniquename of field being checked
 * @param array $fieldinfo array of fields from getfields function
 */
function _civicrm_api3_validate_string(&$params, &$fieldname, &$fieldInfo) {
  // If fieldname exists in params
  $value = (string) CRM_Utils_Array::value($fieldname, $params,'');
  if ($value ) {
    if (!CRM_Utils_Rule::xssString($value)) {
      throw new Exception('Illegal characters in input (potential scripting attack)');
    }
    if ($fieldname == 'currency') {
      if (!CRM_Utils_Rule::currencyCode($value)) {
        throw new Exception("Currency not a valid code: $value");
      }
    }
    if (!empty ($fieldInfo['options'])) {
      // Validate & swap out any pseudoconstants / options
      $options = $fieldInfo['options'];
      $lowerCaseOptions = array_map("strtolower", $options);
      // If value passed is not a key, it may be a label
      // Try to lookup key from label - if it can't be found throw error
      if (!isset($options[strtolower($value)]) && !isset($options[$value]) ) {
        if (!in_array(strtolower($value), $lowerCaseOptions)) {
          throw new Exception("$fieldname `$value` is not valid.");
        }
      }
    }
    // Check our field length
    elseif (is_string($value) && !empty($fieldInfo['maxlength']) && strlen($value) > $fieldInfo['maxlength']) {
      throw new API_Exception("Value for $fieldname is " . strlen($value) . " characters  - This field has a maxlength of {$fieldInfo['maxlength']} characters.",
        2100, array('field' => $fieldname)
      );
    }
  }
}
