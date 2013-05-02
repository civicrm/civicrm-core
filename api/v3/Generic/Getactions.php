<?php
// $Id$

function civicrm_api3_generic_getActions($params) {
  civicrm_api3_verify_mandatory($params, NULL, array('entity'));
  $r = civicrm_api('Entity', 'Get', array('version' => 3));
  $entity = CRM_Utils_String::munge($params['entity']);
  if (!in_array($entity, $r['values'])) {
    return civicrm_api3_create_error("Entity " . $entity . " invalid. Use api.entity.get to have the list", array('entity' => $r['values']));
  }
  _civicrm_api_loadEntity($entity);

  $functions     = get_defined_functions();
  $actions       = array();
  $prefix        = 'civicrm_api3_' . strtolower($entity) . '_';
  $prefixGeneric = 'civicrm_api3_generic_';
  foreach ($functions['user'] as $fct) {
    if (strpos($fct, $prefix) === 0) {
      $actions[] = substr($fct, strlen($prefix));
    }
    elseif (strpos($fct, $prefixGeneric) === 0) {
      $actions[] = substr($fct, strlen($prefixGeneric));
    }
  }
  return civicrm_api3_create_success($actions);
}
