<?php
// $Id: Tag.php 45502 2013-02-08 13:32:55Z kurund $


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
 * File for the CiviCRM APIv2 tag functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Tag
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: Tag.php 45502 2013-02-08 13:32:55Z kurund $
 * @todo Erik Hommel 15/12/2010 version to be implemented
 */

/**
 * Include utility functions
 */
require_once 'api/v2/utils.php';

/**
 *  Add a Tag. Tags are used to classify CRM entities (including Contacts, Groups and Actions).
 *
 * @param   array   $params          an associative array used in
 *                                   construction / retrieval of the
 *                                   object
 *
 * @return array of newly created tag property values.
 * @access public
 * @todo Erik Hommel 15/12/2010 : check if function is ok for update
 */
function civicrm_tag_create(&$params) {
  _civicrm_initialize();
  $errorScope = CRM_Core_TemporaryErrorScope::useException();
  try {

    civicrm_verify_mandatory($params, 'CRM_Core_DAO_Tag', array('name'));

    if (!array_key_exists('used_for', $params)) {
      $params['used_for'] = "civicrm_contact";
    }

    require_once 'CRM/Core/BAO/Tag.php';
    $ids = array('tag' => CRM_Utils_Array::value('tag', $params));
    if (CRM_Utils_Array::value('tag', $params)) {
      $ids['tag'] = $params['tag'];
    }

    $tagBAO = CRM_Core_BAO_Tag::add($params, $ids);

    if (is_a($tagBAO, 'CRM_Core_Error')) {
      return civicrm_create_error("Tag is not created");
    }
    else {
      $values = array();
      _civicrm_object_to_array($tagBAO, $values);
      $tag             = array();
      $tag['tag_id']   = $values['id'];
      $tag['name']     = $values['name'];
      $tag['is_error'] = 0;
    }
    return $tag;
  }
  catch(PEAR_Exception$e) {
    return civicrm_create_error($e->getMessage());
  }
  catch(Exception$e) {
    return civicrm_create_error($e->getMessage());
  }
}

/**
 * Deletes an existing Tag
 *
 * @param  array  $params
 *
 * @return boolean | error  true if successfull, error otherwise
 * @access public
 */
function civicrm_tag_delete(&$params) {
  _civicrm_initialize();
  $errorScope = CRM_Core_TemporaryErrorScope::useException();
  try {
    civicrm_verify_mandatory($params, NULL, array('tag_id'));
    $tagID = CRM_Utils_Array::value('tag_id', $params);

    require_once 'CRM/Core/BAO/Tag.php';
    return CRM_Core_BAO_Tag::del($tagID) ? civicrm_create_success() : civicrm_create_error(ts('Could not delete tag'));
  }
  catch(Exception$e) {
    if (CRM_Core_Error::$modeException) {
      throw $e;
    }
    return civicrm_create_error($e->getMessage());
  }
}

/**
 * Get a Tag.
 *
 * This api is used for finding an existing tag.
 * Either id or name of tag are required parameters for this api.
 *
 * @param  array $params  an associative array of name/value pairs.
 *
 * @return  array details of found tag else error
 * @access public
 */
function civicrm_tag_get($params) {
  _civicrm_initialize();
  require_once 'CRM/Core/BAO/Tag.php';
  $tagBAO = new CRM_Core_BAO_Tag();

  if (!is_array($params)) {
    return civicrm_create_error('Params is not an array.');
  }
  if (!isset($params['id']) && !isset($params['name'])) {
    return civicrm_create_error('Required parameters missing.');
  }

  $properties = array(
    'id', 'name', 'description', 'parent_id', 'is_selectable', 'is_hidden',
    'is_reserved', 'used_for',
  );
  foreach ($properties as $name) {
    if (array_key_exists($name, $params)) {
      $tagBAO->$name = $params[$name];
    }
  }

  if (!$tagBAO->find(TRUE)) {
    return civicrm_create_error('Exact match not found.');
  }

  _civicrm_object_to_array($tagBAO, $tag);
  $tag['is_error'] = 0;
  return $tag;
}

