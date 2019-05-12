<?php

/**
 * @file CiviCRM APIv3 API wrapper.
 *
 * @package CiviCRM_APIv3
 */

/**
 * CiviCRM API wrapper function.
 *
 * @param string $entity
 *   type of entities to deal with
 * @param string $action
 *   create, get, delete or some special action name.
 * @param array $params
 *   array to be passed to function
 * @param null $extra
 *
 * @return array|int
 */
function civicrm_api($entity, $action, $params, $extra = NULL) {
  return \Civi::service('civi_api_kernel')->runSafe($entity, $action, $params, $extra);
}

/**
 * Version 3 wrapper for civicrm_api.
 *
 * Throws exception.
 *
 * @param string $entity
 *   Type of entities to deal with.
 * @param string $action
 *   Create, get, delete or some special action name.
 * @param array $params
 *   Array to be passed to function.
 *
 * @throws CiviCRM_API3_Exception
 * @return array
 */
function civicrm_api3($entity, $action, $params = []) {
  $params['version'] = 3;
  $result = \Civi::service('civi_api_kernel')->runSafe($entity, $action, $params);
  if (is_array($result) && !empty($result['is_error'])) {
    throw new CiviCRM_API3_Exception($result['error_message'], CRM_Utils_Array::value('error_code', $result, 'undefined'), $result);
  }
  return $result;
}

/**
 * Call getfields from api wrapper.
 *
 * This function ensures that settings that
 * could alter getfields output (e.g. action for all api & profile_id for
 * profile api ) are consistently passed in.
 *
 * We check whether the api call is 'getfields' because if getfields is
 * being called we return an empty array as no alias swapping, validation or
 * default filling is done on getfields & we want to avoid a loop
 *
 * @todo other output modifiers include contact_type
 *
 * @param array $apiRequest
 *
 * @return array
 *   getfields output
 */
function _civicrm_api3_api_getfields(&$apiRequest) {
  if (strtolower($apiRequest['action'] == 'getfields')) {
    // the main param getfields takes is 'action' - however this param is not compatible with REST
    // so we accept 'api_action' as an alias of action on getfields
    if (!empty($apiRequest['params']['api_action'])) {
      //  $apiRequest['params']['action'] = $apiRequest['params']['api_action'];
      // unset($apiRequest['params']['api_action']);
    }
    return ['action' => ['api.aliases' => ['api_action']]];
  }
  $getFieldsParams = ['action' => $apiRequest['action']];
  $entity = $apiRequest['entity'];
  if ($entity == 'Profile' && array_key_exists('profile_id', $apiRequest['params'])) {
    $getFieldsParams['profile_id'] = $apiRequest['params']['profile_id'];
  }
  $fields = civicrm_api3($entity, 'getfields', $getFieldsParams);
  return $fields['values'];
}

/**
 * Check if the result is an error. Note that this function has been retained from
 * api v2 for convenience but the result is more standardised in v3 and param
 * 'format.is_success' => 1
 * will result in a boolean success /fail being returned if that is what you need.
 *
 * @param $result
 *
 * @return bool
 *   true if error, false otherwise
 */
function civicrm_error($result) {
  if (is_array($result)) {
    return (array_key_exists('is_error', $result) &&
      $result['is_error']
    ) ? TRUE : FALSE;
  }
  return FALSE;
}

/**
 * Get camel case version of entity name.
 *
 * @param string|null $entity
 *
 * @return string|null
 */
function _civicrm_api_get_camel_name($entity) {
  return is_string($entity) ? CRM_Utils_String::convertStringToCamel($entity) : NULL;
}

/**
 * Swap out any $values vars.
 *
 * Ie. the value after $value is swapped for the parent $result
 * 'activity_type_id' => '$value.testfield',
 * 'tag_id'  => '$value.api.tag.create.id',
 * 'tag1_id' => '$value.api.entity.create.0.id'
 *
 * @param array $params
 * @param array $parentResult
 * @param string $separator
 */
function _civicrm_api_replace_variables(&$params, &$parentResult, $separator = '.') {
  foreach ($params as $field => &$value) {
    if (substr($field, 0, 4) == 'api.') {
      // CRM-21246 - Leave nested calls alone.
      continue;
    }
    if (is_string($value) && substr($value, 0, 6) == '$value') {
      $value = _civicrm_api_replace_variable($value, $parentResult, $separator);
    }
    // Handle the operator syntax: array('OP' => $val)
    elseif (is_array($value) && is_string(reset($value)) && substr(reset($value), 0, 6) == '$value') {
      $key = key($value);
      $value[$key] = _civicrm_api_replace_variable($value[$key], $parentResult, $separator);
      // A null value with an operator will cause an error, so remove it.
      if ($value[$key] === NULL) {
        $value = '';
      }
    }
  }
}

/**
 * Swap out a $value.foo variable with the value from parent api results.
 *
 * Called by _civicrm_api_replace_variables to do the substitution.
 *
 * @param string $value
 * @param array $parentResult
 * @param string $separator
 * @return mixed|null
 */
function _civicrm_api_replace_variable($value, $parentResult, $separator) {
  $valueSubstitute = substr($value, 7);

  if (!empty($parentResult[$valueSubstitute])) {
    return $parentResult[$valueSubstitute];
  }
  else {
    $stringParts = explode($separator, $value);
    unset($stringParts[0]);
    // CRM-16168 If we have failed to swap it out we should unset it rather than leave the placeholder.
    $value = NULL;

    $fieldname = array_shift($stringParts);

    //when our string is an array we will treat it as an array from that . onwards
    $count = count($stringParts);
    while ($count > 0) {
      $fieldname .= "." . array_shift($stringParts);
      if (array_key_exists($fieldname, $parentResult) && is_array($parentResult[$fieldname])) {
        $arrayLocation = $parentResult[$fieldname];
        foreach ($stringParts as $key => $innerValue) {
          $arrayLocation = CRM_Utils_Array::value($innerValue, $arrayLocation);
        }
        $value = $arrayLocation;
      }
      $count = count($stringParts);
    }
  }
  return $value;
}

/**
 * Convert possibly camel name to underscore separated entity name.
 *
 * @param string $entity
 *   Entity name in various formats e.g. Contribution, contribution,
 *   OptionValue, option_value, UFJoin, uf_join.
 *
 * @return string
 *   Entity name in underscore separated format.
 */
function _civicrm_api_get_entity_name_from_camel($entity) {
  if (!$entity || $entity === strtolower($entity)) {
    return $entity;
  }
  elseif ($entity == 'PCP') {
    return 'pcp';
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
 * Having a DAO object find the entity name.
 *
 * @param object $bao
 *   DAO being passed in.
 *
 * @return string
 */
function _civicrm_api_get_entity_name_from_dao($bao) {
  $daoName = str_replace("BAO", "DAO", get_class($bao));
  return CRM_Core_DAO_AllCoreTables::getBriefName($daoName);
}
