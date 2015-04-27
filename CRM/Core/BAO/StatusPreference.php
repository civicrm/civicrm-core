<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
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
    $statusPreference = new CRM_Core_DAO_StatusPreference();

    // Severity can be either text ('critical') or an integer <= 8.
    if (!CRM_Utils_Rule::integer($params['minimum_report_severity'])) {
      $params['minimum_report_severity'] = CRM_Utils_Check::severityMap($params['minimum_report_severity']);
    }
    CRM_Core_Error::debug('severity', $params['minimum_report_severity']);
    if ($params['minimum_report_severity'] > 8) {
      CRM_Core_Error::fatal(ts('You can not pass a severity level higher than 8 (Emergency).'));
    }

    // Check if this StatusPreference already exists.
    if (empty($params['id']) && CRM_Utils_Array::value('name', $params)) {
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
