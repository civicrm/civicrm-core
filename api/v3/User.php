<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * This api exposes CiviCRM the user framework user account.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Get details about the CMS User entity.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_user_get($params) {
  if (empty($params['contact_id'])) {
    $params['contact_id'] = civicrm_api3('UFMatch', 'getvalue', array(
      'uf_id' => $params['id'],
      'domain_id' => CRM_Core_Config::domainID(),
      'return' => 'contact_id',
    ));
  }
  $result = CRM_Core_Config::singleton()->userSystem->getUser($params['contact_id']);
  $result['contact_id'] = $params['contact_id'];
  return civicrm_api3_create_success(
    array($result['id'] => $result),
    $params,
    'user',
    'get'
  );

}

/**
 * Adjust Metadata for Get action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_user_get_spec(&$params) {
  // At this stage contact-id is required - we may be able to loosen this.
  $params['contact_id'] = array(
    'title' => 'Contact ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  );
  $params['id'] = array(
    'title' => 'CMS User ID',
    'type' => CRM_Utils_Type::T_INT,
  );
  $params['name'] = array(
    'title' => 'Username',
    'type' => CRM_Utils_Type::T_STRING,
  );
}
