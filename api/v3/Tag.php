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
 * File for the CiviCRM APIv3 tag functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Tag
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: Tag.php 30486 2010-11-02 16:12:09Z shot $
 */

/**
 *  Add a Tag. Tags are used to classify CRM entities (including Contacts, Groups and Actions).
 *
 * Allowed @params array keys are:
 *
 * {@example TagCreate.php}
 *
 * @param $params
 *
 * @return array of newly created tag property values.
 * {@getfields tag_create}
 * @access public
 */
function civicrm_api3_tag_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Specify Meta data for create. Note that this data is retrievable via the getfields function
 * and is used for pre-filling defaults and ensuring mandatory requirements are met.
 *
 * @param array $params
 */
function _civicrm_api3_tag_create_spec(&$params) {
  $params['used_for']['api.default'] = 'civicrm_contact';
  $params['name']['api.required'] = 1;
  $params['id']['api.aliases'] = array('tag');
}

/**
 * Deletes an existing Tag
 *
 * @param  array  $params
 *
 * @example TagDelete.ph
 *
 * @return array API result array
 * {@getfields tag_delete}
 * @access public
 */
function civicrm_api3_tag_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Get a Tag.
 *
 * This api is used for finding an existing tag.
 * Either id or name of tag are required parameters for this api.
 *
 * @example TagGet.php
 *
 * @param  array $params  an associative array of name/value pairs.
 *
 * @return  array details of found tags else error
 * {@getfields tag_get}
 * @access public
 */
function civicrm_api3_tag_get($params) {

  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

