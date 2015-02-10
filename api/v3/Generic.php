<?php

/**
 * Get information about fields for a given api request. Getfields information
 * is used for documentation, validation, default setting
 * We first query the scheme using the $dao->fields function & then augment
 * that information by calling the _spec functions that apply to the relevant function
 * Note that we use 'unique' field names as described in the xml/schema files
 * for get requests & just field name for create. This is because some get functions
 * access multiple objects e.g. contact api accesses is_deleted from the activity
 * table & from the contact table
 *
 * @param array $apiRequest api request as an array. Keys are
 *  - entity: string
 *  - action: string
 *  - version: string
 *  - function: callback (mixed)
 *  - params: array, varies
 *  @return array API success object
 */
function civicrm_api3_generic_getfields($apiRequest) {
  static $results = array();
  if ((CRM_Utils_Array::value('cache_clear', $apiRequest['params']))) {
    $results = array();
    // we will also clear pseudoconstants here - should potentially be moved to relevant BAO classes
    CRM_Core_PseudoConstant::flush();
    if(!empty($apiRequest['params']['fieldname'])){
      CRM_Utils_PseudoConstant::flushConstant($apiRequest['params']['fieldname']);
    }
    if(!empty($apiRequest['params']['option_group_id'])){
      $optionGroupName = civicrm_api('option_group', 'getvalue', array('version' => 3, 'id' => $apiRequest['params']['option_group_id'], 'return' => 'name') );
      if(is_string($optionGroupName)){
        CRM_Utils_PseudoConstant::flushConstant(_civicrm_api_get_camel_name($optionGroupName));
      }
    }
  }
  $entity       = _civicrm_api_get_camel_name($apiRequest['entity']);
  $lcase_entity = _civicrm_api_get_entity_name_from_camel($entity);
  $subentity    = CRM_Utils_Array::value('contact_type', $apiRequest['params']);
  $action       = strtolower(CRM_Utils_Array::value('action', $apiRequest['params']));
  $sequential = empty($apiRequest['params']) ? 0 : 1;
  $apiOptions = CRM_Utils_Array::value('options', $apiRequest['params'], array());
  if (!$action || $action == 'getvalue' || $action == 'getcount') {
    $action = 'get';
  }
  // determines whether to use unique field names - seem comment block above
  $unique = TRUE;
  if (empty($apiOptions) && isset($results[$entity . $subentity]) && isset($action, $results[$entity . $subentity])
    && isset($action, $results[$entity . $subentity][$sequential])) {
    return $results[$entity . $subentity][$action][$sequential];
  }
  // defaults based on data model and API policy
  switch ($action) {
    case 'getfields':
      $values = _civicrm_api_get_fields($entity, FALSE, $apiRequest['params']);
      return civicrm_api3_create_success($values, $apiRequest['params'], $entity, 'getfields');
    case 'create':
    case 'update':
    case 'replace':
      $unique = FALSE;
    case 'get':
    case 'getsingle':
    case 'getcount':
      $metadata = _civicrm_api_get_fields($apiRequest['entity'], $unique, $apiRequest['params']);
      if (empty($metadata['id'])){
        // if id is not set we will set it eg. 'id' from 'case_id', case_id will be an alias
        if(!empty($metadata[strtolower($apiRequest['entity']) . '_id'])) {
          $metadata['id'] = $metadata[$lcase_entity . '_id'];
          unset($metadata[$lcase_entity . '_id']);
          $metadata['id']['api.aliases'] = array($lcase_entity . '_id');
        }
      }
      else{
        // really the preference would be to set the unique name in the xml
        // question is which is a less risky fix this close to a release - setting in xml for the known failure
        // (note) or setting for all api where fields is returning 'id' & we want to accept 'note_id' @ the api layer
        // nb we don't officially accept note_id anyway - rationale here is more about centralising a now-tested
        // inconsistency
        $metadata['id']['api.aliases'] = array($lcase_entity . '_id');
      }
      break;

    case 'delete':
      $metadata = array(
        'id' => array(
          'title' => $entity . ' ID',
          'name' => 'id',
          'api.required' => 1,
          'api.aliases' => array($lcase_entity . '_id'),
          'type' => CRM_Utils_Type::T_INT,
        ));
      break;

    case 'getoptions':
      $metadata = array(
        'field' => array(
          'name' => 'field',
          'title' => 'Field name',
          'api.required' => 1,
        ),
        'context' => array(
          'name' => 'context',
          'title' => 'Context',
        ),
      );
        break;
    default:
      // oddballs are on their own
      $metadata = array();
  }

  // find any supplemental information
  $hypApiRequest = array('entity' => $apiRequest['entity'], 'action' => $action, 'version' => $apiRequest['version']);
  try {
    list ($apiProvider, $hypApiRequest) = \Civi\Core\Container::singleton()->get('civi_api_kernel')->resolve($hypApiRequest);
    if (isset($hypApiRequest['function'])) {
      $helper = '_' . $hypApiRequest['function'] . '_spec';
    } else {
      // not implemented MagicFunctionProvider
      $helper = NULL;
    }
  } catch (\Civi\API\Exception\NotImplementedException $e) {
    $helper = NULL;
  }
  if (function_exists($helper)) {
    // alter
    $helper($metadata, $apiRequest);
  }

  $fieldsToResolve = (array) CRM_Utils_Array::value('get_options', $apiOptions, array());

  foreach ($metadata as $fieldname => $fieldSpec) {
    _civicrm_api3_generic_get_metadata_options($metadata, $apiRequest, $fieldname, $fieldSpec, $fieldsToResolve);
  }

  $results[$entity][$action][$sequential] = civicrm_api3_create_success($metadata, $apiRequest['params'], $entity, 'getfields');
  return $results[$entity][$action][$sequential];
}

/**
 * API return function to reformat results as count
 *
 * @param array $apiRequest api request as an array. Keys are
 *
 * @throws API_Exception
 * @return integer count of results
 */
function civicrm_api3_generic_getcount($apiRequest) {
  $apiRequest['params']['options']['is_count'] = TRUE;
  $result = civicrm_api($apiRequest['entity'], 'get', $apiRequest['params']);
  if(is_numeric (CRM_Utils_Array::value('values', $result))) {
    return (int) $result['values'];
  }
  if(!isset($result['count'])) {
    throw new API_Exception(ts('Unexpected result from getcount') . print_r($result, TRUE));
  }
  return $result['count'];
}

/**
 * API return function to reformat results as single result
 *
 * @param array $apiRequest api request as an array. Keys are
 *
 * @return integer count of results
 */
function civicrm_api3_generic_getsingle($apiRequest) {
  // so the first entity is always result['values'][0]
  $apiRequest['params']['sequential'] = 1;
  $result = civicrm_api($apiRequest['entity'], 'get', $apiRequest['params']);
  if ($result['is_error'] !== 0) {
    return $result;
  }
  if ($result['count'] === 1) {
    return $result['values'][0];
  }
  if ($result['count'] !== 1) {
    return civicrm_api3_create_error("Expected one " . $apiRequest['entity'] . " but found " . $result['count'], array('count' => $result['count']));
  }
  return civicrm_api3_create_error("Undefined behavior");
}

/**
 * API return function to reformat results as single value
 *
 * @param array $apiRequest api request as an array. Keys are
 *
 * @return integer count of results
 */
function civicrm_api3_generic_getvalue($apiRequest) {
  $apiRequest['params']['sequential'] = 1;
  $result = civicrm_api($apiRequest['entity'], 'get', $apiRequest['params']);
  if ($result['is_error'] !== 0) {
    return $result;
  }
  if ($result['count'] !== 1) {
    $result = civicrm_api3_create_error("Expected one " . $apiRequest['entity'] . " but found " . $result['count'], array('count' => $result['count']));
    return $result;
  }

  // we only take "return=" as valid options
  if (!empty($apiRequest['params']['return'])) {
    if (!isset($result['values'][0][$apiRequest['params']['return']])) {
      return civicrm_api3_create_error("field " . $apiRequest['params']['return'] . " unset or not existing", array('invalid_field' => $apiRequest['params']['return']));
    }

    return $result['values'][0][$apiRequest['params']['return']];
  }

  return civicrm_api3_create_error("missing param return=field you want to read the value of", array('error_type' => 'mandatory_missing', 'missing_param' => 'return'));
}

/**
 * @param $params
 */
function _civicrm_api3_generic_getrefcount_spec(&$params) {
  $params['id']['api.required'] = 1;
  $params['id']['title'] = 'Entity ID';
}

/**
 * API to determine if a record is in-use
 *
 * @param array $apiRequest api request as an array
 *
 * @throws API_Exception
 * @return array API result (int 0 or 1)
 */
function civicrm_api3_generic_getrefcount($apiRequest) {
  $entityToClassMap = CRM_Core_DAO_AllCoreTables::daoToClass();
  if (!isset($entityToClassMap[$apiRequest['entity']])) {
    throw new API_Exception("The entity '{$apiRequest['entity']}' is unknown or unsupported by 'getrefcount'. Consider implementing this API.", 'getrefcount_unsupported');
  }
  $daoClass = $entityToClassMap[$apiRequest['entity']];

  /* @var $dao CRM_Core_DAO */
  $dao = new $daoClass();
  $dao->id = $apiRequest['params']['id'];
  if ($dao->find(TRUE)) {
    return civicrm_api3_create_success($dao->getReferenceCounts());
  }
  else {
    return civicrm_api3_create_success(array());
  }
}

/**
 * API wrapper for replace function
 *
 * @param array $apiRequest api request as an array. Keys are
 *
 * @return integer count of results
 */
function civicrm_api3_generic_replace($apiRequest) {
  return _civicrm_api3_generic_replace($apiRequest['entity'], $apiRequest['params']);
}

/**
 * API wrapper for getoptions function
 *
 * @param array $apiRequest api request as an array.
 *
 * @return array of results
 */
function civicrm_api3_generic_getoptions($apiRequest) {
  // Resolve aliases
  $fieldName = _civicrm_api3_api_resolve_alias($apiRequest['entity'], $apiRequest['params']['field']);
  if (!$fieldName) {
    return civicrm_api3_create_error("The field '{$apiRequest['params']['field']}' doesn't exist.");
  }
  // Validate 'context' from params
  $context = CRM_Utils_Array::value('context', $apiRequest['params']);
  CRM_Core_DAO::buildOptionsContext($context);
  unset($apiRequest['params']['context'], $apiRequest['params']['field']);

  $baoName = _civicrm_api3_get_BAO($apiRequest['entity']);
  $options = $output = $baoName::buildOptions($fieldName, $context, $apiRequest['params']);
  if ($options === FALSE) {
    return civicrm_api3_create_error("The field '{$fieldName}' has no associated option list.");
  }
  // Support 'sequential' output as a non-associative array
  if (!empty($apiRequest['params']['sequential'])) {
    $output = array();
    foreach ($options as $key => $val) {
      $output[] = array('key' => $key, 'value' => $val);
    }
  }
  return civicrm_api3_create_success($output, $apiRequest['params'], $apiRequest['entity'], 'getoptions');
}

/**
 * Function fills the 'options' array on the metadata returned by getfields if
 * 1) the param option 'get_options' is defined - e.g. $params['options']['get_options'] => array('custom_1)
 * (this is passed in as the $fieldsToResolve array)
 * 2) the field is a pseudoconstant and is NOT an FK
 * - the reason for this is that checking / transformation is done on pseudoconstants but
 * - if the field is an FK then mysql will enforce the data quality (& we have handling on failure)
 * @todo - if may be we should define a 'resolve' key on the pseudoconstant for when these rules are not fine enough
 *
 * This function is only split out for the purpose of code clarity / comment block documentation
 *
 * @param array $metadata the array of metadata that will form the result of the getfields function
 * @param $apiRequest
 * @param string $fieldname field currently being processed
 * @param array $fieldSpec metadata for that field
 * @param array $fieldsToResolve anny field resolutions specifically requested
 */
function _civicrm_api3_generic_get_metadata_options(&$metadata, $apiRequest, $fieldname, $fieldSpec, $fieldsToResolve){
  if (empty($fieldSpec['pseudoconstant']) && empty($fieldSpec['option_group_id'])) {
    return;
  }

  if (!empty($metadata[$fieldname]['options']) || (!in_array($fieldname, $fieldsToResolve) && !in_array('all', $fieldsToResolve))) {
    return;
  }

  $options = civicrm_api($apiRequest['entity'], 'getoptions', array('version' => 3, 'field' => $fieldname, 'sequential' => !empty($apiRequest['params']['sequential'])));
  if (is_array(CRM_Utils_Array::value('values', $options))) {
    $metadata[$fieldname]['options'] = $options['values'];
  }
}
