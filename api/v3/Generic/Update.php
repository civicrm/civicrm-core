<?php
// $Id$

/**
 * Our original intention was not to have an update action. However, we wound up having
 * to retain it for backward compatibility. The only difference between update and create
 * is that update will throw an error if id is not a number
 * CRM-10908
 * @param $apiRequest an array with keys:
 *  - entity: string
 *  - action: string
 *  - version: string
 *  - function: callback (mixed)
 *  - params: array, varies
 */
function civicrm_api3_generic_update($apiRequest) {

  if (!array_key_exists('id', $apiRequest['params']) ||
      empty($apiRequest['params']['id']) ||
      !is_numeric($apiRequest['params']['id'])) {
    throw new api_Exception("Mandatory parameter missing `id`", 2000);
  }
  return civicrm_api($apiRequest['entity'], 'create', $apiRequest['params']);
}

