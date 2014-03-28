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
 * File for the CiviCRM APIv3 user framework join functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_UF
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: UFJoin.php 30171 2010-10-14 09:11:27Z mover $
 *
 */

/**
 * takes an associative array and creates a uf join in the database
 *
 * @param array $params assoc array of name/value pairs
 *
 * @return array CRM_Core_DAO_UFJoin Array
 * @access public
 * @example UFJoinCreate.php
 *  {@getfields UFJoin_create}
 *
 */
function civicrm_api3_uf_join_create($params) {

  $ufJoin = CRM_Core_BAO_UFJoin::create($params);
  _civicrm_api3_object_to_array($ufJoin, $ufJoinArray[]);
  return civicrm_api3_create_success($ufJoinArray, $params, 'uf_join', 'create');
}

/**
 * Adjust Metadata for Create action
 *
 * @param array $params array or parameters determined by getfields
 * @todo - suspect module, weight don't need to be required - need to test
 */
function _civicrm_api3_uf_join_create_spec(&$params) {
  $params['module']['api.required'] = 1;
  $params['weight']['api.required'] = 1;
  $params['uf_group_id']['api.required'] = 1;
}

/**
 * Get CiviCRM UF_Joins (ie joins between CMS user records & CiviCRM user record
 *
 * @param array $params (reference) an assoc array of name/value pairs
 *
 * @return array $result CiviCRM Result Array or null
 * @todo Delete function missing
 * @access public
 * {getfields UFJoin_get}
 */
function civicrm_api3_uf_join_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

