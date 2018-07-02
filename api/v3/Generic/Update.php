<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
  $seek = array($key_id => $apiRequest['params'][$key_id], 'version' => $apiRequest['version']);
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
  $p = array_merge($existing, $apiRequest['params']);
  return civicrm_api($apiRequest['entity'], 'create', $p);
}
