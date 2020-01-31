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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
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
   * @return array
   */
  public static function create($params) {
    $statusPreference = new CRM_Core_BAO_StatusPreference();

    // Default severity level to ignore is 0 (DEBUG).
    if (!isset($params['ignore_severity'])) {
      $params['ignore_severity'] = 0;
    }
    // Severity can be either text ('critical') or an integer <= 7.
    // It's a magic number, but based on PSR-3 standards.
    if (!CRM_Utils_Rule::integer($params['ignore_severity'])) {
      $params['ignore_severity'] = CRM_Utils_Check::severityMap($params['ignore_severity']);
    }
    if ($params['ignore_severity'] > 7) {
      CRM_Core_Error::fatal(ts('You can not pass a severity level higher than 7.'));
    }
    // If severity is now blank, you have an invalid severity string.
    if (is_null($params['ignore_severity'])) {
      CRM_Core_Error::fatal(ts('Invalid string passed as severity level.'));
    }

    // Check if this StatusPreference already exists.
    if (empty($params['id']) && !empty($params['name'])) {
      $statusPreference->domain_id = CRM_Utils_Array::value('domain_id', $params, CRM_Core_Config::domainID());
      $statusPreference->name = $params['name'];

      $statusPreference->find(TRUE);
    }

    $statusPreference->copyValues($params);

    $edit = ($statusPreference->id) ? TRUE : FALSE;
    if ($edit) {
      CRM_Utils_Hook::pre('edit', 'StatusPreference', $statusPreference->id, $statusPreference);
    }
    else {
      CRM_Utils_Hook::pre('create', 'StatusPreference', NULL, $statusPreference);
    }

    $statusPreference->save();

    if ($edit) {
      CRM_Utils_Hook::post('edit', 'StatusPreference', $statusPreference->id, $statusPreference);
    }
    else {
      CRM_Utils_Hook::post('create', 'StatusPreference', NULL, $statusPreference);
    }

    return $statusPreference;
  }

}
