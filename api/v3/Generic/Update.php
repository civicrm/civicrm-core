<?php
// $Id$

/**
 * Update function is basically a hack to get around issues listed in
 * http://issues.civicrm.org/jira/browse/CRM-12144
 *
 * It is not recommended & if update doesn't work & fix does then update will not be fixed
 *
 * To do this, perform a 'get' action to load the existing values, then merge in the updates
 * and call 'create' to save the revised entity.
 *
 * @param $apiRequest an array with keys:
 *  - entity: string
 *  - action: string
 *  - version: string
 *  - function: callback (mixed)
 *  - params: array, varies
 */
function civicrm_api3_generic_update($apiRequest) {
  $errorFnName = 'civicrm_api3_create_error';

  //$key_id = strtolower ($apiRequest['entity'])."_id";
  $key_id = "id";
  if (!array_key_exists($key_id, $apiRequest['params'])) {
    return $errorFnName("Mandatory parameter missing $key_id");
  }
  // @fixme
  // tests show that contribution works better with create
  // this is horrible but to make it work we'll just handle it separately
  if(strtolower($apiRequest['entity']) == 'contribution'){
    return civicrm_api($apiRequest['entity'], 'create', $apiRequest['params']);
  }
  $seek = array($key_id => $apiRequest['params'][$key_id], 'version' => $apiRequest['version']);
  $existing = civicrm_api($apiRequest['entity'], 'get', $seek);
  if ($existing['is_error']) {
    return $existing;
  }
  if ($existing['count'] > 1) {
    return $errorFnName("More than one " . $apiRequest['entity'] . " with id " . $apiRequest['params'][$key_id]);
  }
  if ($existing['count'] == 0) {
    return $errorFnName("No " . $apiRequest['entity'] . " with id " . $apiRequest['params'][$key_id]);
  }

  $existing = array_pop($existing['values']);
  $p = array_merge($existing, $apiRequest['params']);
  return civicrm_api($apiRequest['entity'], 'create', $p);
}

