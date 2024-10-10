<?php

/**
 * @file CiviCRM APIv3 API wrapper.
 *
 * @package CiviCRM_APIv3
 */

/**
 * The original API wrapper.
 *
 * @deprecated
 * Not recommended for new code but ok for existing code to continue using.
 *
 * Calling `civicrm_api()` is functionally identical to `civicrm_api3()` or `civicrm_api4()` except:
 *   1. It requires `$params['version']`.
 *   2. It catches exceptions and returns an array like `['is_error' => 1, 'error_message' => ...]`.
 * This is disfavored for typical business-logic/hooks/forms/etc.
 * However, if an existing caller handles `civicrm_api()`-style errors, then there is no functional benefit to reworking it.
 *
 * @param string $entity
 * @param string $action
 * @param array $params
 *
 * @return array|int|Civi\Api4\Generic\Result
 */
function civicrm_api(string $entity, string $action, array $params) {
  return \Civi::service('civi_api_kernel')->runSafe($entity, $action, $params);
}

/**
 * CiviCRM API version 4.
 *
 * This API (Application Programming Interface) is used to access and manage data in CiviCRM.
 *
 * APIv4 is the latest stable version.
 *
 * @see https://docs.civicrm.org/dev/en/latest/api/v4/usage/
 *
 * @param string $entity Name of the CiviCRM entity to access.
 *   All entity names are capitalized CamelCase, e.g. `ContributionPage`.
 *   Most entities correspond to a database table (e.g. `Contact` is the table `civicrm_contact`).
 *   For a complete list of available entities, call `civicrm_api4('Entity', 'get');`
 *
 * @param string $action The "verb" of the api call.
 *   For a complete list of actions for a given entity (e.g. `Contact`), call `civicrm_api4('Contact', 'getActions');`
 *
 * @param array $params An array of API input keyed by parameter name.
 *   The easiest way to discover all available parameters is to visit the API Explorer on your CiviCRM site.
 *   The API Explorer is listed in the CiviCRM menu under Support -> Developer.
 *
 * @param string|int|array $index Controls the Result array format.
 *   By default the api Result contains a non-associative array of data. Passing an $index tells the api to
 *   automatically reformat the array, depending on the variable type passed:
 *   - **Integer:** return a single result array;
 *     e.g. `$index = 0` will return the first result, 1 will return the second, and -1 will return the last.
 *
 *     For APIv4 Explorer, use e.g. `0` in the Index box.
 *
 *   - **String:** index the results by a field value;
 *     e.g. `$index = "name"` will return an associative array with the field 'name' as keys.
 *
 *     For APIv4 Explorer, use e.g. `name` in the Index box.
 *
 *   - **Non-associative array:** return a single value from each result;
 *     e.g. `$index = ['title']` will return a non-associative array of strings - the 'title' field from each result.
 *
 *     For APIv4 Explorer, use e.g. `[title]` in the Index box.
 *
 *   - **Associative array:** a combination of the previous two modes;
 *     e.g. `$index = ['name' => 'title']` will return an array of strings - the 'title' field keyed by the 'name' field.
 *
 *     For APIv4 Explorer, use e.g. `{name: title}` in the Index box.
 *
 * @return \Civi\Api4\Generic\Result
 * @throws \CRM_Core_Exception
 * @throws \Civi\API\Exception\NotImplementedException
 */
function civicrm_api4(string $entity, string $action, array $params = [], $index = NULL) {
  $indexField = $index && is_string($index) && !CRM_Utils_Rule::integer($index) ? $index : NULL;
  $removeIndexField = FALSE;

  // If index field is not part of the select query, we add it here and remove it below (except for oddball "Setting" api)
  if ($indexField && !empty($params['select']) && is_array($params['select']) && !($entity === 'Setting' && $action === 'get') && !\Civi\Api4\Utils\SelectUtil::isFieldSelected($indexField, $params['select'])) {
    $params['select'][] = $indexField;
    $removeIndexField = TRUE;
  }
  $apiCall = \Civi\API\Request::create($entity, $action, ['version' => 4] + $params);

  if ($index && is_array($index)) {
    $indexCol = reset($index);
    $indexField = key($index);
    // Automatically add index fields(s) to the SELECT clause
    if ($entity !== 'Setting' && method_exists($apiCall, 'addSelect')) {
      $apiCall->addSelect($indexCol);
      if ($indexField && $indexField != $indexCol) {
        $apiCall->addSelect($indexField);
      }
    }
  }

  $result = $apiCall->execute();

  // Index results by key
  if ($indexField) {
    $result->indexBy($indexField);
    if ($removeIndexField) {
      foreach ($result as $key => $value) {
        unset($result[$key][$indexField]);
      }
    }
  }
  // Return result at index
  elseif (CRM_Utils_Rule::integer($index)) {
    $item = $result->itemAt($index);
    if (is_null($item)) {
      throw new \CRM_Core_Exception("Index $index not found in api results");
    }
    // Attempt to return a Result object if item is array, otherwise just return the item
    if (!is_array($item)) {
      return $item;
    }
    $result->exchangeArray($item);
  }
  if (!empty($indexCol)) {
    $result->exchangeArray($result->column($indexCol));
  }
  return $result;
}

/**
 * Version 3 wrapper for civicrm_api.
 *
 * Throws exception.
 *
 * @param string $entity
 * @param string $action
 * @param array $params
 *
 * @throws CRM_Core_Exception
 *
 * @return array|int
 *   Dependent on the $action
 */
function civicrm_api3(string $entity, string $action, array $params = []) {
  $params['version'] = 3;
  $result = \Civi::service('civi_api_kernel')->runSafe($entity, $action, $params);
  if (is_array($result) && !empty($result['is_error'])) {
    throw new CRM_Core_Exception($result['error_message'], $result['error_code'] ?? 'undefined', $result);
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
 * @param mixed $result
 *
 * @return bool
 *   true if error, false otherwise
 */
function civicrm_error($result) {
  return is_array($result) && !empty($result['is_error']);
}

/**
 * Get camel case version of entity name.
 *
 * @param string|null $entity
 *
 * @return string|null
 */
function _civicrm_api_get_camel_name($entity) {
  return is_string($entity) ? \Civi\API\Request::normalizeEntityName($entity) : NULL;
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
          $arrayLocation = $arrayLocation[$innerValue] ?? NULL;
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
 *
 * @deprecated
 */
function _civicrm_api_get_entity_name_from_camel($entity) {
  if (!$entity) {
    // @todo - this should not be called when empty.
    return '';
  }
  return CRM_Core_DAO_AllCoreTables::convertEntityNameToLower($entity);
}

/**
 * Having a DAO object find the entity name.
 *
 * @param CRM_Core_DAO $bao
 *   DAO being passed in.
 *
 * @return string
 */
function _civicrm_api_get_entity_name_from_dao($bao) {
  return CRM_Core_DAO_AllCoreTables::getEntityNameForClass(get_class($bao));
}
