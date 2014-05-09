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
 * File for the CiviCRM APIv3 website functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Website
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: Website.php 2011-03-16 ErikHommel $
 */

/**
 *  Add an Website for a contact
 *
 * Allowed @params array keys are:
 * {@getfields website_create}
 * @example WebsiteCreate.php
 * {@example WebsiteCreate.php}
 *
 * @param $params
 *
 * @return array of newly created website property values.
 * @access public
 * @todo convert to using basic create - BAO function non-std
 */
function civicrm_api3_website_create($params) {
  //DO NOT USE THIS FUNCTION AS THE BASIS FOR A NEW API http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards

  $websiteBAO = CRM_Core_BAO_Website::add($params);
    $values = array();
    _civicrm_api3_object_to_array($websiteBAO, $values[$websiteBAO->id]);
    return civicrm_api3_create_success($values, $params, 'website', 'get');
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_website_create_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
}

/**
 * Deletes an existing Website
 *
 * @param  array  $params
 * {@getfields website_delete}
 * @example WebsiteDelete.php Std Delete Example
 *
 * @return array API result Array
 * @access public
 * @todo convert to using Basic delete - BAO function non standard
 */
function civicrm_api3_website_delete($params) {
  //DO NOT USE THIS FUNCTION AS THE BASIS FOR A NEW API http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
  $websiteID = CRM_Utils_Array::value('id', $params);

  $websiteDAO = new CRM_Core_DAO_Website();
  $websiteDAO->id = $websiteID;
  if ($websiteDAO->find()) {
    while ($websiteDAO->fetch()) {
      $websiteDAO->delete();
      return civicrm_api3_create_success(1, $params, 'website', 'delete');
    }
  }
  else {
    return civicrm_api3_create_error('Could not delete website with id ' . $websiteID);
  }
}

/**
 * Retrieve one or more websites
 *
 * @param  mixed[]  (reference ) input parameters
 * {@getfields website_get}
 * {@example WebsiteGet.php 0}
 * @example WebsiteGet.php
 * @param  array $params  an associative array of name/value pairs.
 *
 * @return  array details of found websites
 *
 * @access public
 */
function civicrm_api3_website_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'website');
}

