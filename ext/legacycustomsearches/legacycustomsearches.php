<?php

require_once 'legacycustomsearches.civix.php';
// phpcs:disable
use CRM_Legacycustomsearches_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function legacycustomsearches_civicrm_config(&$config) {
  _legacycustomsearches_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function legacycustomsearches_civicrm_install() {
  _legacycustomsearches_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function legacycustomsearches_civicrm_enable() {
  _legacycustomsearches_civix_civicrm_enable();
}

/**
 * Determine the sql
 * @param array $savedSearch
 * @param int $groupID
 * @param string $sql
 *
 * @throws \CRM_Core_Exception
 */
function legacycustomsearches_civicrm_buildGroupContactCache(array $savedSearch, int $groupID, string &$sql): void {
  if (empty($savedSearch['search_custom_id'])) {
    return;
  }
  $savedSearchID = $savedSearch['id'];
  $excludeClause = "
    NOT IN (
    SELECT contact_id FROM civicrm_group_contact
    WHERE civicrm_group_contact.status = 'Removed'
    AND civicrm_group_contact.group_id = $groupID )";
  $addSelect = "$groupID AS group_id";
  $ssParams = CRM_Contact_BAO_SavedSearch::getFormValues($savedSearchID);

  // A lack of customSearchClass key probably indicates a deeper problem, but shouldn't hold up the system
  $customSearchClass = $ssParams['customSearchClass'] ?? NULL;

  // check if there is a special function - formatSavedSearchFields defined in the custom search form
  if ($customSearchClass && method_exists($customSearchClass, 'formatSavedSearchFields')) {
    $customSearchClass::formatSavedSearchFields($ssParams);
  }

  // CRM-7021 rectify params to what proximity search expects if there is a value for prox_distance
  if (!empty($ssParams)) {
    CRM_Contact_BAO_ProximityQuery::fixInputParams($ssParams);
  }
  $searchSQL = CRM_Contact_BAO_SearchCustom::customClass($ssParams['customSearchID'], $savedSearchID)->contactIDs();
  $searchSQL = str_replace('ORDER BY contact_a.id ASC', '', $searchSQL);
  if (strpos($searchSQL, 'WHERE') === FALSE) {
    $searchSQL .= " WHERE contact_a.id $excludeClause";
  }
  else {
    $searchSQL .= " AND contact_a.id $excludeClause";
  }
  $sql = preg_replace("/^\s*SELECT /", "SELECT $addSelect, ", $searchSQL);
}
