<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * This api exposes CiviCRM note.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create Note.
 *
 * This API is used for creating a note.
 * Required parameters : entity_id AND note
 *
 * @param array $params
 *   An associative array of name/value property values of civicrm_note.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_note_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Note');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_note_create_spec(&$params) {
  $params['entity_table']['api.default'] = "civicrm_contact";
  $params['modified_date']['api.default'] = "now";
  $params['note']['api.required'] = 1;
  $params['entity_id']['api.required'] = 1;
}

/**
 * Deletes an existing note.
 *
 * This API is used for deleting a note
 *
 * @param array $params
 *   Including id of the note to be deleted.
 *
 * @return array
 */
function civicrm_api3_note_delete($params) {
  $result = new CRM_Core_BAO_Note();
  return $result->del($params['id']) ? civicrm_api3_create_success() : civicrm_api3_create_error('Error while deleting Note');
}

/**
 * Retrieve a specific note or notes, given a set of input params.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   array of properties,
 *   if error an array with an error id and error message
 */
function civicrm_api3_note_get($params) {
  return _civicrm_api3_basic_get('CRM_Core_BAO_Note', $params);
}

/**
 * Adjust Metadata for Get action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_note_get_spec(&$params) {
  $params['entity_table']['api.default'] = "civicrm_contact";
}

/**
 * Get all descendants of given note.
 *
 * @param array $params
 *   array; only required 'id' parameter is used.
 *
 * @return array
 *   Nested associative array beginning with direct children of given note.
 */
function civicrm_api3_note_tree_get($params) {
  civicrm_api3_verify_mandatory($params, NULL, ['id']);

  if (!is_numeric($params['id'])) {
    return civicrm_api3_create_error(ts("Invalid note ID"));
  }
  if (!isset($params['max_depth'])) {
    $params['max_depth'] = 0;
  }
  if (!isset($params['snippet'])) {
    $params['snippet'] = FALSE;
  }
  $noteTree = CRM_Core_BAO_Note::getNoteTree($params['id'], $params['max_depth'], $params['snippet']);
  return civicrm_api3_create_success($noteTree, $params);
}
