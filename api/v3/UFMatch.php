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
 * File for the CiviCRM APIv3 user framework group functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_UF
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: UFGroup.php 30171 2010-10-14 09:11:27Z mover $
 *
 */

/**
 * get the contact_id given a uf_id or vice versa
 *
 * @param array $params
 *
 * @return array $result
 * @access public
 * {@getfields UFMatch_get}
 * @example UFMatchGet.php
 * @todo this class is missing delete & create functions (do after exisitng functions upgraded to v3)
 */
function civicrm_api3_uf_match_get($params) {
  return _civicrm_api3_basic_get('CRM_Core_BAO_UFMatch', $params);
}

/**
 * Create or update a UF Match record
 *
 * @param array $params  Associative array of property
 *                       name/value pairs to insert in new 'survey'
 * @example UFMatch.php Std Create example
 *
 * @return array api result array
 * {@getfields uf_match_create}
 * @access public
 */
function civicrm_api3_uf_match_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_uf_match_create_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
  $params['uf_id']['api.required'] = 1;
  $params['uf_name']['api.required'] = 1;
}

/**
 * Create or update a survey
 *
 * @param array $params  Associative array of property
 *                       name/value pairs to insert in new 'survey'
 * @example UFMatch.php Std Create example
 *
 * @return array api result array
 * {@getfields uf_match_create}
 * @access public
 */
function civicrm_api3_uf_match_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

