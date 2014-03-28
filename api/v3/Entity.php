<?php

require_once 'api/v3/utils.php';

/**
 *  Placeholder function. This should never be called, as it doesn't have any meaning
 */
function civicrm_api3_entity_create($params) {
  return civicrm_api3_create_error("API (Entity, Create) does not exist Creating a new entity means modifying the source code of civiCRM.");
}

/**
 *  Placeholder function. This should never be called, as it doesn't have any meaning
 */
function civicrm_api3_entity_delete($params) {
  return civicrm_api3_create_error("API (Entity, Delete) does not exist Deleting an entity means modifying the source code of civiCRM.");
}

/**
 *  Placeholder function. This should never be called, as it doesn't have any meaning
 */
function civicrm_api3_entity_getfields($params) {
  // we return an empty array so it makes it easier to write generic getdefaults / required tests
  // without putting an exception in for entity
  return civicrm_api3_create_success(array());
}

