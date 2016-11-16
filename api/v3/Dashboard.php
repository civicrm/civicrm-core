<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * This api exposes CiviCRM Dashboard.
 *
 * @package CiviCRM_APIv3
 */


/**
 * Creates or updates an Dashlet.
 *
 * @param array $params
 *
 * @return array
 *   Array containing 'is_error' to denote success or failure and details of the created activity
 */
function civicrm_api3_dashboard_create($params) {
  civicrm_api3_verify_one_mandatory($params, NULL, array(
      'name',
      'label',
      'url',
      'fullscreen_url',
    )
  );
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Dashboard');
}

/**
 * Specify Meta data for create.
 *
 * Note that this data is retrievable via the getfields function
 * and is used for pre-filling defaults and ensuring mandatory requirements are met.
 *
 * @param array $params
 *   array of parameters determined by getfields.
 */
function _civicrm_api3_dashboard_create_spec(&$params) {
  $params['is_active']['api.default'] = 1;
  unset($params['version']);
}

/**
 * Gets a CiviCRM Dashlets according to parameters.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_dashboard_get($params) {
  // NEVER COPY THIS. No idea why a newish api would not use basic_get.
  $bao = new CRM_Core_BAO_Dashboard();
  _civicrm_api3_dao_set_filter($bao, $params, TRUE);
  $dashlets = _civicrm_api3_dao_to_array($bao, $params, TRUE, 'Dashboard');
  return civicrm_api3_create_success($dashlets, $params, 'Dashboard', 'get', $bao);
}

/**
 * Delete a specified Dashlet.
 *
 * @param array $params
 *   Array holding 'id' of dashlet to be deleted.
 *
 * @return array
 */
function civicrm_api3_dashboard_delete($params) {
  if (CRM_Core_BAO_Dashboard::deleteDashlet($params['id'])) {
    return civicrm_api3_create_success(1, $params, 'Dashboard', 'delete');
  }
  else {
    return civicrm_api3_create_error('Could not delete dashlet');
  }
}
