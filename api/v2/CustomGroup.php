<?php
// $Id: CustomGroup.php 45502 2013-02-08 13:32:55Z kurund $


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
 * File for the CiviCRM APIv2 custom group functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_CustomGroup
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: CustomGroup.php 45502 2013-02-08 13:32:55Z kurund $
 */

/**
 * Files required for this package
 */
require_once 'api/v2/utils.php';

/**
 * Most API functions take in associative arrays ( name => value pairs
 * as parameters. Some of the most commonly used parameters are
 * described below
 *
 * @param array $params           an associative array used in construction
 * retrieval of the object
 *
 *
 */

/**
 * Use this API to create a new group. See the CRM Data Model for custom_group property definitions
 * $params['class_name'] is a required field, class being extended.
 *
 * @param $params     array   Associative array of property name/value pairs to insert in group.
 *
 *
 * @return   Newly create custom_group object
 *
 * @access public
 */
function civicrm_custom_group_create($params) {
  _civicrm_initialize();

  if (!is_array($params)) {
    return civicrm_create_error("params is not an array");
  }

  // Require either param['class_name'] (string) - for backwards compatibility - OR parm['extends'] (array)
  // If passing extends array - set class_name (e.g. 'Contact', 'Participant'...) as extends[0]. You may optionally
  // pass an extends_entity_column_value as extends[1] (e.g. an Activity Type ID).
  if (isset($params['class_name']) && trim($params['class_name'])) {
    $params['extends'][0] = trim($params['class_name']);
  }
  else {
    if (!isset($params['extends']) || !is_array($params['extends'])) {
      return civicrm_create_error("Params must include either 'class_name' (string) or 'extends' (array).");
    }
    else {
      if (!isset($params['extends'][0]) || !trim($params['extends'][0])) {
        return civicrm_create_error("First item in params['extends'] must be a class name (e.g. 'Contact').");
      }
    }
  }

  $error = _civicrm_check_required_fields($params, 'CRM_Core_DAO_CustomGroup');

  require_once 'CRM/Utils/String.php';
  if (!isset($params['title']) ||
    !trim($params['title'])
  ) {
    return civicrm_create_error("Title parameter is required.");
  }

  if (!isset($params['style']) || !trim($params['style'])) {
    $params['style'] = 'Inline';
  }

  if (is_a($error, 'CRM_Core_Error')) {
    return civicrm_create_error($error->_errors[0]['message']);
  }

  require_once 'CRM/Core/BAO/CustomGroup.php';
  $customGroup = CRM_Core_BAO_CustomGroup::create($params);

  _civicrm_object_to_array($customGroup, $values);

  if (is_a($customGroup, 'CRM_Core_Error')) {
    return civicrm_create_error($customGroup->_errors[0]['message']);
  }
  else {
    $values['is_error'] = 0;
  }
  if (CRM_Utils_Array::value('html_type', $params)) {
    $params['custom_group_id'] = $customGroup->id;
    $fieldValues = civicrm_custom_field_create($params);
    $values = array_merge($values, $fieldValues['result']);
  }
  return $values;
}

/**
 * Use this API to delete an existing group.
 *
 * @param array id of the group to be deleted
 *
 * @return Null if success
 * @access public
 **/
function civicrm_custom_group_delete($params) {
  _civicrm_initialize();

  if (!is_array($params)) {
    return civicrm_create_error('Params is not an array');
  }

  if (!CRM_Utils_Array::value('id', $params)) {
    return civicrm_create_error('Invalid or no value for Custom group ID');
  }
  // convert params array into Object
  require_once 'CRM/Core/DAO/CustomGroup.php';
  $values = new CRM_Core_DAO_CustomGroup();
  $values->id = $params['id'];
  $values->find(TRUE);

  require_once 'CRM/Core/BAO/CustomGroup.php';
  $result = CRM_Core_BAO_CustomGroup::deleteGroup($values);
  return $result ? civicrm_create_success() : civicrm_error('Error while deleting custom group');
}

/**
 * Defines 'custom field' within a group.
 *
 *
 * @param $params       array  Associative array of property name/value pairs to create new custom field.
 *
 * @return Newly created custom_field id array
 *
 * @access public
 *
 */
function civicrm_custom_field_create($params) {
  _civicrm_initialize();

  if (!is_array($params)) {
    return civicrm_create_error("params is not an array ");
  }

  if (!CRM_Utils_Array::value('custom_group_id', $params)) {
    return civicrm_create_error("Missing Required field :custom_group_id");
  }

  if (!(CRM_Utils_Array::value('label', $params))) {
    return civicrm_create_error("Missing Required field :label");
  }

  if (!(CRM_Utils_Array::value('option_type', $params))) {
    if (CRM_Utils_Array::value('id', $params)) {
      $params['option_type'] = 2;
    }
    else {
      $params['option_type'] = 1;
    }
  }

  $error = _civicrm_check_required_fields($params, 'CRM_Core_DAO_CustomField');
  if (is_a($error, 'CRM_Core_Error')) {
    return civicrm_create_error($error->_errors[0]['message']);
  }

  // Array created for passing options in params
  if (isset($params['option_values']) && is_array($params['option_values'])) {
    foreach ($params['option_values'] as $key => $value) {
      $params['option_label'][$value['weight']] = $value['label'];
      $params['option_value'][$value['weight']] = $value['value'];
      $params['option_status'][$value['weight']] = $value['is_active'];
      $params['option_weight'][$value['weight']] = $value['weight'];
    }
  }
  require_once 'CRM/Core/BAO/CustomField.php';
  $customField = CRM_Core_BAO_CustomField::create($params);

  $values['customFieldId'] = $customField->id;

  if (is_a($customField, 'CRM_Core_Error') && is_a($column, 'CRM_Core_Error')) {
    return civicrm_create_error($customField->_errors[0]['message']);
  }
  else {
    return civicrm_create_success($values);
  }
}

/**
 * Use this API to delete an existing custom group field.
 *
 * @param $params     Array id of the field to be deleted
 *
 *
 * @access public
 **/
function civicrm_custom_field_delete($params) {
  _civicrm_initialize();

  if (!is_array($params)) {
    return civicrm_create_error('Params is not an array');
  }

  if (!CRM_Utils_Array::value('id', $params)) {
    return civicrm_create_error('Invalid or no value for Custom Field ID');
  }

  require_once 'CRM/Core/DAO/CustomField.php';
  $field = new CRM_Core_DAO_CustomField();
  $field->id = $params['id'];
  $field->find(TRUE);

  require_once 'CRM/Core/BAO/CustomField.php';
  $customFieldDelete = CRM_Core_BAO_CustomField::deleteField($field);
  return $customFieldDelete ? civicrm_create_error('Error while deleting custom field') : civicrm_create_success();
}

