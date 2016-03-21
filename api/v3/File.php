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
 * This api is a simple wrapper of the CiviCRM file DAO.
 *
 * Creating and updating files is a complex process and this api is usually insufficient.
 * Use the "Attachment" api instead for more robust file handling.
 *
 * @fixme no unit tests
 * @package CiviCRM_APIv3
 */

/**
 * Create a file record.
 * @note This is only one of several steps needed to create a file in CiviCRM.
 * Use the "Attachment" api to better handle all steps.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 */
function civicrm_api3_file_create($params) {

  civicrm_api3_verify_mandatory($params, 'CRM_Core_DAO_File', array('uri'));

  if (!isset($params['upload_date'])) {
    $params['upload_date'] = date("Ymd");
  }

  $fileDAO = new CRM_Core_DAO_File();
  $properties = array(
    'id',
    'file_type_id',
    'mime_type',
    'uri',
    'document',
    'description',
    'upload_date',
  );

  foreach ($properties as $name) {
    if (array_key_exists($name, $params)) {
      $fileDAO->$name = $params[$name];
    }
  }

  $fileDAO->save();

  $file = array();
  _civicrm_api3_object_to_array($fileDAO, $file);

  return civicrm_api3_create_success($file, $params, 'File', 'create', $fileDAO);
}

/**
 * Get a File.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   Array of all found file object property values.
 */
function civicrm_api3_file_get($params) {
  civicrm_api3_verify_one_mandatory($params);
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Update an existing File.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
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
 * Delete an existing File.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API Result Array
 */
function civicrm_api3_file_delete($params) {

  civicrm_api3_verify_mandatory($params, NULL, array('id'));
  if (CRM_Core_BAO_File::deleteEntityFile('*', $params['id'])) {
    return civicrm_api3_create_success();
  }
  else {
    throw new API_Exception('Error while deleting a file.');
  }
}
