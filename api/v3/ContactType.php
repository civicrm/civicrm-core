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
 * File for the CiviCRM APIv3 group functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Survey
 * @copyright CiviCRM LLC (c) 2004-2014
 */

/**
 * create/update contact_type
 *
 * This API is used to create new contact_type or update any of the existing
 * In case of updating existing contact_type, id of that particular contact_type must
 * be in $params array.
 *
 * @param array $params  (reference) Associative array of property
 *                       name/value pairs to insert in new 'contact_type'
 *
 * @return array   contact_type array
 *
 * @access public
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
 * Returns array of contact_types  matching a set of one or more group properties
 *
 * @param array $params one or more valid
 *                       property_name=>value pairs. If $params is set
 *                       as null, all contact_types will be returned
 *
 * @return array Array of matching contact_types
 * @access public
 */
function civicrm_api3_contact_type_get($params) {
  civicrm_api3_verify_mandatory($params);
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * delete an existing contact_type
 *
 * This method is used to delete any existing contact_type. id of the group
 * to be deleted is required field in $params array
 *
 * @param array $params array containing id of the group
 *                       to be deleted
 *
 * @return array  API Result Array
 *
 * @access public
 */
function civicrm_api3_contact_type_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

