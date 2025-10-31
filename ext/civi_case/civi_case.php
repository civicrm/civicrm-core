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

/**
 * Applies Case permissions to Activities
 *
 * @implements CRM_Utils_Hook::selectWhereClause
 */
function civi_case_civicrm_selectWhereClause($entityName, &$clauses, $userId, $conditions) {
  if ($entityName === 'Activity') {
    $casePerms = CRM_Utils_SQL::mergeSubquery('Case');
    if (!$casePerms) {
      // Unrestricted access to CiviCase
      return;
    }
    // OR group: either it's a non-case activity OR case permissions apply
    $orGroup = [
      'NOT IN (SELECT activity_id FROM civicrm_case_activity)',
      'IN (SELECT activity_id FROM civicrm_case_activity WHERE case_id ' . implode(' AND case_id ', $casePerms) . ')',
    ];
    $clauses['id'][] = $orGroup;
  }
}

/**
 * @implements CRM_Utils_Hook::referenceCounts
 */
function civi_case_civicrm_referenceCounts($dao, &$refCounts) {
  $refCounts = array_merge($refCounts, CRM_Case_Info::getReferenceCounts($dao));
}
