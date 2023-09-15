<?php

require_once 'civi_case.civix.php';
use CRM_Case_ExtensionUtil as E;

/**
 * Implements hook_civicrm_managed().
 */
function civi_case_civicrm_managed(&$entities, $modules) {
  // Don't optimize for $modules because `createManagedCaseTypes` delegates to other extensions
  $entities = array_merge($entities, CRM_Case_ManagedEntities::createManagedCaseTypes());
  // These functions always declare module = civicrm
  if (!$modules || in_array('civicrm', $modules, TRUE)) {
    $entities = array_merge($entities,
      CRM_Case_ManagedEntities::createManagedActivityTypes(CRM_Case_XMLRepository::singleton()),
      CRM_Case_ManagedEntities::createManagedRelationshipTypes(CRM_Case_XMLRepository::singleton())
    );
  }
}
