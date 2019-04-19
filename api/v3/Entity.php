<?php

/**
 * Get list of deprecated entities.
 *
 * This is called by the api wrapper when returning the result of api.Entity.get.
 *
 * @param array $entities
 *
 * @return array
 *   Array of deprecated api entities
 */
function _civicrm_api3_entity_deprecation($entities) {
  $deprecated = [];
  if (!empty($entities['values'])) {
    foreach ($entities['values'] as $entity) {
      if (is_string(_civicrm_api3_deprecation_check($entity))) {
        $deprecated[] = $entity;
      }
    }
  }
  return $deprecated;
}

/**
 * Placeholder function.
 *
 * This should never be called, as it doesn't have any meaning.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_entity_create($params) {
  return civicrm_api3_create_error("API (Entity, Create) does not exist Creating a new entity means modifying the source code of civiCRM.");
}

/**
 * Placeholder function.
 *
 * This should never be called, as it doesn't have any meaning.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_entity_delete($params) {
  return civicrm_api3_create_error("API (Entity, Delete) does not exist Deleting an entity means modifying the source code of civiCRM.");
}

/**
 * Placeholder function.
 *
 * This should never be called, as it doesn't have any meaning.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_entity_getfields($params) {
  // we return an empty array so it makes it easier to write generic getdefaults / required tests
  // without putting an exception in for entity
  return civicrm_api3_create_success([]);
}
