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
 * @package CiviCRM_APIv3
 */

/**
 * Update function is basically a hack.
 *
 * We want to remove it but must resolve issues in
 * http://issues.civicrm.org/jira/browse/CRM-12144
 *
 * It is not recommended & if update doesn't work & fix does then update will not be fixed
 *
 * To do this, perform a 'get' action to load the existing values, then merge in the updates
 * and call 'create' to save the revised entity.
 *
 * @deprecated
 *
 * @param array $apiRequest
 *   Array with keys:
 *   - entity: string
 *   - action: string
 *   - version: string
 *   - function: callback (mixed)
 *   - params: array, varies
 *
 * @return array|int|mixed
 */
function civicrm_api3_generic_update($apiRequest) {
  //$key_id = strtolower ($apiRequest['entity'])."_id";
  $key_id = "id";
  if (!array_key_exists($key_id, $apiRequest['params'])) {
    return civicrm_api3_create_error("Mandatory parameter missing $key_id");
  }
  // @fixme
  // tests show that contribution works better with create
  // this is horrible but to make it work we'll just handle it separately
  if (strtolower($apiRequest['entity']) == 'contribution') {
    return civicrm_api($apiRequest['entity'], 'create', $apiRequest['params']);
  }
  $seek = [$key_id => $apiRequest['params'][$key_id], 'version' => $apiRequest['version']];
  $existing = civicrm_api($apiRequest['entity'], 'get', $seek);
  if ($existing['is_error']) {
    return $existing;
  }
  if ($existing['count'] > 1) {
    return civicrm_api3_create_error("More than one " . $apiRequest['entity'] . " with id " . $apiRequest['params'][$key_id]);
  }
  if ($existing['count'] == 0) {
    return civicrm_api3_create_error("No " . $apiRequest['entity'] . " with id " . $apiRequest['params'][$key_id]);
  }

  $existing = array_pop($existing['values']);
  // Per Unit test testUpdateHouseholdWithAll we don't want to load these from the DB
  // if they are not passed in then we'd rather they are calculated.
  // Note update is not recomended anyway...
  foreach (['sort_name', 'display_name'] as $fieldToNotSet) {
    unset($existing[$fieldToNotSet]);
  }
  $p = array_merge($existing, $apiRequest['params']);
  return civicrm_api($apiRequest['entity'], 'create', $p);
}
