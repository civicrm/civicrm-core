<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * File for the CiviCRM APIv3 for Dashboard Contact
 *
 * @package CiviCRM_APIv3
 * @subpackage API_ActionSchedule
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 *
 */

/**
 * Creates/Updates a new Dashboard Contact Entry
 *
 * @param array $params
 *
 * @return array
 *
 */
function civicrm_api3_dashboard_contact_create($params) {
  if (empty($params['id'])) {
    civicrm_api3_verify_one_mandatory($params,
      NULL,
      array(
        'dashboard_id',
      )
    );
  }
  $errors = _civicrm_api3_dashboard_contact_check_params($params);
  if ($errors !== NULL) {
    return $errors;
  }
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Gets a CiviCRM Dashlets of Contacts according to parameters
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs for the activity.
 *
 * @return array
 *
 */
function civicrm_api3_dashboard_contact_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_dashboard_contact_create_spec(&$params) {
  unset($params['version']);
}

/**
 * @param $params
 *
 * @return array|null
 */
function _civicrm_api3_dashboard_contact_check_params(&$params) {
  $dashboard_id = CRM_Utils_Array::value('dashboard_id', $params);
  if ($dashboard_id) {
    $allDashlets = CRM_Core_BAO_Dashboard::getDashlets(TRUE, CRM_Utils_Array::value('check_permissions', $params, 0));
    if (!isset($allDashlets[$dashboard_id])) {
      return civicrm_api3_create_error('Invalid or inaccessible dashboard ID');
    }
  }
  return NULL;
}

/**
 * Delete an existing dashboard-contact
 *
 * This method is used to delete any existing dashboard-board. the id of the dashboard-contact
 * is required field in $params array
 *
 * {@getfields dashboard_contact_delete}
 * @access public
 */
function civicrm_api3_dashboard_contact_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
