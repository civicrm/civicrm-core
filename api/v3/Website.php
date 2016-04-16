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
 * This api exposes CiviCRM website records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Add an Website for a contact.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 * @todo convert to using basic create - BAO function non-std
 */
function civicrm_api3_website_create($params) {
  //DO NOT USE THIS FUNCTION AS THE BASIS FOR A NEW API http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
  _civicrm_api3_check_edit_permissions('CRM_Core_BAO_Website', $params);
  $websiteBAO = CRM_Core_BAO_Website::add($params);
  $values = array();
  _civicrm_api3_object_to_array($websiteBAO, $values[$websiteBAO->id]);
  return civicrm_api3_create_success($values, $params, 'Website', 'get');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_website_create_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
}

/**
 * Deletes an existing Website.
 *
 * @todo convert to using Basic delete - BAO function non standard
 *
 * @param array $params
 *
 * @return array
 *   API result array
 * @throws \API_Exception
 */
function civicrm_api3_website_delete($params) {
  //DO NOT USE THIS FUNCTION AS THE BASIS FOR A NEW API http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
  civicrm_api3_verify_mandatory($params, NULL, array('id'));
  _civicrm_api3_check_edit_permissions('CRM_Core_BAO_Website', array('id' => $params['id']));
  $websiteDAO = new CRM_Core_DAO_Website();
  $websiteDAO->id = $params['id'];
  if ($websiteDAO->find()) {
    while ($websiteDAO->fetch()) {
      $websiteDAO->delete();
      return civicrm_api3_create_success(1, $params, 'Website', 'delete');
    }
  }
  else {
    throw new API_Exception('Could not delete Website with id ' . $params['id']);
  }
}

/**
 * Retrieve one or more websites.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_website_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'Website');
}
