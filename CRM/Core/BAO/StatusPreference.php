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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class contains functions for managing Status Preferences.
 */
class CRM_Core_BAO_StatusPreference extends CRM_Core_DAO_StatusPreference {

  /**
   * Create or update a Status Preference entry.
   *
   * @param array $params
   *
   * @return CRM_Core_DAO_StatusPreference
   * @throws CRM_Core_Exception
   */
  public static function create($params) {
    $statusPreference = new CRM_Core_DAO_StatusPreference();

    // Severity can be either text ('critical') or an integer <= 7.
    // It's a magic number, but based on PSR-3 standards.
    if (isset($params['ignore_severity']) && !CRM_Utils_Rule::integer($params['ignore_severity'])) {
      $params['ignore_severity'] = CRM_Utils_Check::severityMap($params['ignore_severity']);
      // If severity is now blank, you have an invalid severity string.
      if (is_null($params['ignore_severity'])) {
        throw new CRM_Core_Exception(ts('Invalid string passed as severity level.'));
      }
    }
    if (($params['ignore_severity'] ?? 0) > 7) {
      throw new CRM_Core_Exception(ts('You can not pass a severity level higher than 7.'));
    }

    // Set default domain when creating (or updating by name)
    if (empty($params['id']) && empty($params['domain_id'])) {
      $params['domain_id'] = CRM_Core_Config::domainID();
    }

    // Enforce unique status pref names. Update if a duplicate name is found in the same domain.
    if (empty($params['id']) && !empty($params['name'])) {
      $statusPreference->domain_id = $params['domain_id'];
      $statusPreference->name = $params['name'];
      $statusPreference->find(TRUE);
    }

    // Default severity level to ignore is 0 (DEBUG) for a new preference.
    if (empty($params['id']) && !$statusPreference->id) {
      $params['ignore_severity'] ??= 0;
    }

    $op = $statusPreference->id ? 'edit' : 'create';
    CRM_Utils_Hook::pre($op, 'StatusPreference', $statusPreference->id, $params);

    $statusPreference->copyValues($params);
    $statusPreference->save();

    CRM_Utils_Hook::post($op, 'StatusPreference', $statusPreference->id, $statusPreference, $params);

    // Clear the static cache of the CRM_Utils_Check so that the newly created message is available.
    unset(\Civi::$statics['CRM_Utils_Check']);

    return $statusPreference;
  }

}
