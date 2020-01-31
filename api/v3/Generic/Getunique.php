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
 * Generic api wrapper used to get all unique fields for a given entity.
 *
 * @param array $apiRequest
 *
 * @return mixed
 */
function civicrm_api3_generic_getunique($apiRequest) {
  $entity = _civicrm_api_get_entity_name_from_camel($apiRequest['entity']);
  $uniqueFields = [];

  $dao = _civicrm_api3_get_DAO($entity);
  $uFields = $dao::indices();

  foreach ($uFields as $fieldKey => $field) {
    if (!isset($field['unique']) || !$field['unique']) {
      continue;
    }
    $uniqueFields[$fieldKey] = $field['field'];
  }

  return civicrm_api3_create_success($uniqueFields);
}
