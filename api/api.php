<?php

/**
 * File for the CiviCRM APIv3 API wrapper
 *
 * @package CiviCRM_APIv3
 * @subpackage API
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: api.php 30486 2010-11-02 16:12:09Z shot $
 */

/**
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
  return \Civi\Core\Container::singleton()->get('civi_api_kernel')->run($entity, $action, $params, $extra);
}

/**
 * Version 3 wrapper for civicrm_api. Throws exception
 *
 * @param string $entity type of entities to deal with
 * @param string $action create, get, delete or some special action name.
 * @param array $params array to be passed to function
 *
 * @throws CiviCRM_API3_Exception
 * @return array
 */
function civicrm_api3($entity, $action, $params = array()) {
  $params['version'] = 3;
  $result = civicrm_api($entity, $action, $params);
  if(is_array($result) && !empty($result['is_error'])){
    throw new CiviCRM_API3_Exception($result['error_message'], CRM_Utils_Array::value('error_code', $result, 'undefined'), $result);
  }
  return $result;
}

/**
 * Function to call getfields from api wrapper. This function ensures that settings that could alter
 * getfields output (e.g. action for all api & profile_id for profile api ) are consistently passed in.
 *
 * We check whether the api call is 'getfields' because if getfields is being called we return an empty array
 * as no alias swapping, validation or default filling is done on getfields & we want to avoid a loop
 *
 * @todo other output modifiers include contact_type
 *
 * @param array $apiRequest
 * @return array getfields output
 */
function _civicrm_api3_api_getfields(&$apiRequest) {
  if (strtolower($apiRequest['action'] == 'getfields')) {
    // the main param getfields takes is 'action' - however this param is not compatible with REST
    // so we accept 'api_action' as an alias of action on getfields
    if (!empty($apiRequest['params']['api_action'])) {
    //  $apiRequest['params']['action'] = $apiRequest['params']['api_action'];
     // unset($apiRequest['params']['api_action']);
    }
    return array('action' => array('api.aliases' => array('api_action')));
  }
  $getFieldsParams = array('action' => $apiRequest['action']);
  $entity = $apiRequest['entity'];
  if($entity == 'profile' && array_key_exists('profile_id', $apiRequest['params'])) {
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
 * @internal param array $params (reference ) input parameters
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

/**
 * @param $entity
 * @param null $version
 *
 * @return string
 */
function _civicrm_api_get_camel_name($entity, $version = NULL) {
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
 * @return string
 */
function _civicrm_api_get_entity_name_from_dao($bao){
  $daoName = str_replace("BAO", "DAO", get_class($bao));
  return _civicrm_api_get_entity_name_from_camel(CRM_Core_DAO_AllCoreTables::getBriefName($daoName));
}

