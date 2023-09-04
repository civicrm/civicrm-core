<?php

require_once 'civi_case.civix.php';
use CRM_Case_ExtensionUtil as E;

/**
 * Implements hook_civicrm_managed().
 */
function civi_case_civicrm_managed(&$entities, $modules) {
  // Don't optimize for $modules because the below functions delegate to other extensions
  $entities = array_merge($entities,
    CRM_Case_ManagedEntities::createManagedCaseTypes(),
    CRM_Case_ManagedEntities::createManagedActivityTypes(CRM_Case_XMLRepository::singleton(), CRM_Core_ManagedEntities::singleton()),
    CRM_Case_ManagedEntities::createManagedRelationshipTypes(CRM_Case_XMLRepository::singleton(), CRM_Core_ManagedEntities::singleton())
  );
}
