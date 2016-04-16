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
 * This api exposes CiviCRM contact types and sub-types.
 *
 * CiviCRM comes with 3 primary contact types - Individual, Organization & Household.
 * Changing these is not advised, but sub_types can be created with this api.
 * Pass 'parent_id' param to specify which base type a new sub_type extends.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create/update ContactType.
 *
 * This API is used to create new ContactType or update any of the existing
 * In case of updating existing ContactType, id of that particular ContactType must
 * be in $params array.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   ContactType array
 */
function civicrm_api3_contact_type_create($params) {
  civicrm_api3_verify_mandatory($params, _civicrm_api3_get_DAO(__FUNCTION__), array('name', 'parent_id'));

  if (empty($params['id'])) {
    if (!array_key_exists('label', $params)) {
      $params['label'] = $params['name'];
    }
    if (!array_key_exists('is_active', $params)) {
      $params['is_active'] = TRUE;
    }
    $params['name'] = CRM_Utils_String::munge($params['name']);
  }

  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Returns array of contact_types matching a set of one or more properties.
 *
 * @param array $params
 *   One or more valid property_name=>value pairs.
 *   If $params is set as null, all contact_types will be returned
 *
 * @return array
 *   Array of matching contact_types
 */
function civicrm_api3_contact_type_get($params) {
  civicrm_api3_verify_mandatory($params);
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete an existing ContactType.
 *
 * This method is used to delete any existing ContactType given its id.
 *
 * @param array $params
 *   [id]
 *
 * @return array
 *   API Result Array
 */
function civicrm_api3_contact_type_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
