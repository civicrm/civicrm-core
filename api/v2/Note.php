<?php
// $Id: Note.php 45502 2013-02-08 13:32:55Z kurund $


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
 * File for the CiviCRM APIv2 note functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Note
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: Note.php 45502 2013-02-08 13:32:55Z kurund $
 *
 */

/**
 * Files required for this package
 */
require_once 'api/v2/utils.php';
require_once 'CRM/Core/BAO/Note.php';

/**
 * Create Note
 *
 * This API is used for creating a note.
 * Required parameters : entity_id AND note
 *
 * @param   array  $params  an associative array of name/value property values of civicrm_note
 *
 * @return array note id if note is created otherwise is_error = 1
 * @access public
 */
function &civicrm_note_create(&$params) {
  _civicrm_initialize();

  if (!is_array($params)) {
    return civicrm_create_error('Params is not an array');
  }

  if (!CRM_Utils_Array::value('id', $params)) {
    if (!isset($params['entity_table']) ||
      !isset($params['entity_id']) ||
      !isset($params['note']) ||
      !isset($params['contact_id'])
    ) {
      return civicrm_create_error('Required parameter missing');
    }
  }
  elseif (!isset($params['id']) && !isset($params['contact_id'])) {
    return civicrm_create_error('Required parameter missing');
  }

  $contactID = CRM_Utils_Array::value('contact_id', $params);

  if (!isset($params['modified_date'])) {
    $params['modified_date'] = date("Ymd");
  }

  $ids     = array();
  $ids     = array('id' => CRM_Utils_Array::value('id', $params));
  $noteBAO = CRM_Core_BAO_Note::add($params, $ids);

  if (is_a($noteBAO, 'CRM_Core_Error')) {
    $error = civicrm_create_error("Note could not be created");
    return $error;
  }
  else {
    $note = array();
    _civicrm_object_to_array($noteBAO, $note);
    $note['is_error'] = 0;
  }
  return $note;
}

/**
 * Updates an existing note with information
 *
 * @params  array  $params   Params array
 *
 * @return null
 * @access public
 *
 * @todo Probably needs some work
 */
function &civicrm_note_update(&$params) {
  return civicrm_note_create($params);
}

/**
 * Deletes an existing note
 *
 * This API is used for deleting a note
 *
 * @param  Int  $noteID   Id of the note to be deleted
 *
 * @return null
 * @access public
 */
function civicrm_note_delete(&$params) {
  _civicrm_initialize();

  if (!is_array($params)) {
    $error = civicrm_create_error('Params is not an array');
    return $error;
  }

  if (!CRM_Utils_Array::value('id', $params)) {
    $error = civicrm_create_error('Invalid or no value for Note ID');
    return $error;
  }

  $result = new CRM_Core_BAO_Note();
  return $result->del($params['id']) ? civicrm_create_success() : civicrm_create_error('Error while deleting Note');
}

/**
 * Retrieve a specific note, given a set of input params
 *
 * @param  array   $params (reference ) input parameters
 *
 * @return array (reference ) array of properties,
 * if error an array with an error id and error message
 *
 * @static void
 * @access public
 */
function &civicrm_note_get(&$params) {
  _civicrm_initialize();

  $values = array();
  if (empty($params)) {
    return civicrm_create_error(ts('No input parameters present'));
  }

  if (!is_array($params)) {
    return civicrm_create_error(ts('Input parameters is not an array'));
  }

  if (!CRM_Utils_Array::value('entity_id', $params) || (CRM_Utils_Array::value('entity_id', $params) && (!is_numeric($params['entity_id'])))) {
    return civicrm_create_error(ts("Invalid entity ID"));
  }

  if (!isset($params['entity_id']) && !isset($params['entity_table'])) {
    return civicrm_create_error('Required parameters missing.');
  }

  $note = CRM_Core_BAO_Note::getNote($params['entity_id'], $params['entity_table']);

  if (civicrm_error($note)) {
    return $note;
  }

  if (count($note) < 1) {
    return civicrm_create_error(ts('%1 notes matching the input parameters', array(1 => count($note))));
  }

  $note = array_values($note);
  $note['is_error'] = 0;
  return $note;
}

/**
 * Get all descendents of given note
 *
 * @param array $params Associative array; only required 'id' parameter is used
 *
 * @return array Nested associative array beginning with direct children of given note.
 */
function &civicrm_note_tree_get(&$params) {

  if (empty($params)) {
    return civicrm_create_error(ts('No input parameters present'));
  }

  if (!is_array($params)) {
    return civicrm_create_error(ts('Input parameters is not an array'));
  }

  if (!isset($params['id'])) {
    return civicrm_create_error('Required parameter ("id") missing.');
  }

  if (!is_numeric($params['id'])) {
    return civicrm_create_error(ts("Invalid note ID"));
  }
  if (!isset($params['max_depth'])) {
    $params['max_depth'] = 0;
  }
  if (!isset($params['snippet'])) {
    $params['snippet'] = FALSE;
  }
  $noteTree = CRM_Core_BAO_Note::getNoteTree($params['id'], $params['max_depth'], $params['snippet']);
  return $noteTree;
}

