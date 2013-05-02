<?php
// $Id: UFJoin.php 45502 2013-02-08 13:32:55Z kurund $


/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * File for the CiviCRM APIv2 user framework join functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_UF
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: UFJoin.php 45502 2013-02-08 13:32:55Z kurund $
 *
 */

/**
 * Files required for this package
 */
require_once 'api/v2/utils.php';

require_once 'CRM/Core/BAO/UFJoin.php';

/**
 * takes an associative array and creates a uf join array
 *
 * @param array $params assoc array of name/value pairs
 *
 * @return array CRM_Core_DAO_UFJoin Array
 * @access public
 *
 */
function civicrm_uf_join_add($params) {
  if (!is_array($params)) {
    return civicrm_create_error("params is not an array");
  }

  if (empty($params)) {
    return civicrm_create_error("params is an empty array");
  }

  if (!isset($params['uf_group_id'])) {
    return civicrm_create_error("uf_group_id is required field");
  }

  $ufJoin = CRM_Core_BAO_UFJoin::create($params);
  _civicrm_object_to_array($ufJoin, $ufJoinArray);
  return $ufJoinArray;
}

/**
 * takes an associative array and updates a uf join array
 *
 * @param array $params assoc array of name/value pairs
 *
 * @return array  updated CRM_Core_DAO_UFJoin Array
 * @access public
 *
 */
function civicrm_uf_join_edit($params) {
  if (!is_array($params)) {
    return civicrm_create_error("params is not an array");
  }

  if (empty($params)) {
    return civicrm_create_error("params is an empty array");
  }

  if (!isset($params['uf_group_id'])) {
    return civicrm_create_error("uf_group_id is required field");
  }

  $ufJoin = CRM_Core_BAO_UFJoin::create($params);
  _civicrm_object_to_array($ufJoin, $ufJoinArray);
  return $ufJoinArray;
}

/**
 * Given an assoc list of params, finds if there is a record
 * for this set of params
 *
 * @param array $params (reference) an assoc array of name/value pairs
 *
 * @return int or null
 * @access public
 *
 */
function civicrm_uf_join_id_find(&$params) {
  if (!is_array($params) || empty($params)) {
    return civicrm_create_error("$params is not valid array");
  }

  if (!isset($params['id']) &&
    (!isset($params['entity_table']) &&
      !isset($params['entity_id']) &&
      !isset($params['weight'])
    )
  ) {
    return civicrm_create_error("$param should have atleast entity_table or entiy_id or weight");
  }

  return CRM_Core_BAO_UFJoin::findJoinEntryId($params);
}

/**
 * Given an assoc list of params, find if there is a record
 * for this set of params and return the group id
 *
 * @param array $params (reference) an assoc array of name/value pairs
 *
 * @return int or null
 * @access public
 *
 */
function civicrm_uf_join_UFGroupId_find(&$params) {
  if (!is_array($params) || empty($params)) {
    return civicrm_create_error("$params is not valid array");
  }

  if (!isset($params['entity_table']) &&
    !isset($params['entity_id']) &&
    !isset($params['weight'])
  ) {
    return civicrm_create_error("$param should have atleast entity_table or entiy_id or weight");
  }

  return CRM_Core_BAO_UFJoin::findUFGroupId($params);
}

