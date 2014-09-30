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
 *
 * Definition of the Tag of the CRM API.
 * More detailed documentation can be found
 * {@link http://objectledge.org/confluence/display/CRM/CRM+v1.0+Public+APIs
 * here}
 *
 * @package CiviCRM_APIv3
 * @subpackage API_File
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id: $
 *
 */

/**
 * Create a file
 *
 * This API is used for creating a file
 *
 * @param   array  $params  an associative array of name/value property values of civicrm_file
 *
 * @return array of newly created file property values.
 * @access public
 */
function civicrm_api3_file_create($params) {

  civicrm_api3_verify_mandatory($params, 'CRM_Core_DAO_File', array('uri'));

  if (!isset($params['upload_date'])) {
    $params['upload_date'] = date("Ymd");
  }

  $fileDAO = new CRM_Core_DAO_File();
  $properties = array('id', 'file_type_id', 'mime_type', 'uri', 'document', 'description', 'upload_date');

  foreach ($properties as $name) {
    if (array_key_exists($name, $params)) {
      $fileDAO->$name = $params[$name];
    }
  }

  $fileDAO->save();

  $file = array();
  _civicrm_api3_object_to_array($fileDAO, $file);

  return civicrm_api3_create_success($file, $params, 'file', 'create', $fileDAO);
}

/**
 * Get a file.
 *
 * This api is used for finding an existing file.
 * Required parameters : id OR file_type_id of a file
 *
 * @param  array $params  an associative array of name/value property values of civicrm_file
 *
 * @return  Array of all found file object property values.
 * @access public
 */
function civicrm_api3_file_get($params) {
  civicrm_api3_verify_one_mandatory($params);
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Update an existing file
 *
 * This api is used for updating an existing file.
 * Required parameters : id of a file
 *
 * @param  Array   $params an array of name/value property values of civicrm_file
 *
 * @return array of updated file object property values
 * @access public
 */
function civicrm_api3_file_update($params) {

  if (!isset($params['id'])) {
    return civicrm_api3_create_error('Required parameter missing');
  }

  $fileDAO = new CRM_Core_DAO_File();
  $fileDAO->id = $params['id'];
  if ($fileDAO->find(TRUE)) {
    $fileDAO->copyValues($params);
    if (!$params['upload_date'] && !$fileDAO->upload_date) {
      $fileDAO->upload_date = date("Ymd");
    }
    $fileDAO->save();
  }
  $file = array();
  _civicrm_api3_object_to_array(clone($fileDAO), $file);
  return $file;
}

/**
 * Deletes an existing file
 *
 * This API is used for deleting a file
 * Required parameters : id of a file
 *
 * @param $params
 *
 * @return array API result array
 * @access public
 */
function civicrm_api3_file_delete($params) {

  civicrm_api3_verify_mandatory($params, NULL, array('id'));

  $check = FALSE;

  $entityFileDAO = new CRM_Core_DAO_EntityFile();
  $entityFileDAO->file_id = $params['id'];
  if ($entityFileDAO->find()) {
    $check = $entityFileDAO->delete();
  }

  $fileDAO = new CRM_Core_DAO_File();
  $fileDAO->id = $params['id'];
  if ($fileDAO->find(TRUE)) {
    $check = $fileDAO->delete();
  }

  return $check ? NULL : civicrm_api3_create_error('Error while deleting a file.');
}
