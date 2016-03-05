<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * This api exposes CiviCRM custom value.
 *
 * @package CiviCRM_APIv3
 */


/**
 * Sets custom values for an entity.
 *
 * @param array $params
 *   Expected keys are in format custom_fieldID:recordID or custom_groupName:fieldName:recordID.
 *
 * @example:
 * @code
 *   // entity ID. You do not need to specify entity type, we figure it out based on the fields you're using
 *   'entity_id' => 123,
 *   // (omitting :id) inserts or updates a field in a single-valued group
 *   'custom_6' => 'foo',
 *   // custom_24 is checkbox or multiselect, so pass items as an array
 *   'custom_24' => array('bar', 'baz'),
 *   // in this case custom_33 is part of a multi-valued group, and we're updating record id 5
 *   'custom_33:5' => value,
 *   // inserts new record in multi-valued group
 *   'custom_33:-1' => value,
 *   // inserts another new record in multi-valued group
 *   'custom_33:-2' => value,
 *   // you can use group_name:field_name instead of ID
 *   'custom_some_group:my_field' => 'myinfo',
 *   // updates record ID 8 in my_other_field in multi-valued some_big_group
 *   'custom_some_big_group:my_other_field:8' => 'myinfo',
 * @endcode
 *
 * @throws Exception
 * @return array
 *   ['values' => TRUE] or ['is_error' => 1, 'error_message' => 'what went wrong']
 */
function civicrm_api3_custom_value_create($params) {
  // @todo it's not clear where the entity_table is used as  CRM_Core_BAO_CustomValueTable::setValues($create)
  // didn't seem to use it
  // so not clear if it's relevant
  if (!empty($params['entity_table']) && substr($params['entity_table'], 0, 7) == 'civicrm') {
    $params['entity_table'] = substr($params['entity_table'], 8, 7);
  }
  $create = array('entityID' => $params['entity_id']);
  // Translate names and
  //Convert arrays to multi-value strings
  $sp = CRM_Core_DAO::VALUE_SEPARATOR;
  foreach ($params as $id => $param) {
    if (is_array($param)) {
      $param = $sp . implode($sp, $param) . $sp;
    }
    list($c, $id) = CRM_Utils_System::explode('_', $id, 2);
    if ($c != 'custom') {
      continue;
    }
    list($i, $n, $x) = CRM_Utils_System::explode(':', $id, 3);
    if (is_numeric($i)) {
      $key = $i;
      $x = $n;
    }
    else {
      // Lookup names if ID was not supplied
      $key = CRM_Core_BAO_CustomField::getCustomFieldID($n, $i);
      if (!$key) {
        continue;
      }
    }
    if ($x && is_numeric($x)) {
      $key .= '_' . $x;
    }
    $create['custom_' . $key] = $param;
  }
  $result = CRM_Core_BAO_CustomValueTable::setValues($create);
  if ($result['is_error']) {
    throw new Exception($result['error_message']);
  }
  return civicrm_api3_create_success(TRUE, $params, 'CustomValue');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_custom_value_create_spec(&$params) {
  $params['entity_id']['api.required'] = 1;
  $params['entity_id']['title'] = 'Entity ID';
}

/**
 * Use this API to get existing custom values for an entity.
 *
 * @param array $params
 *   Array specifying the entity_id.
 *   Optionally include entity_type param, i.e. 'entity_type' => 'Activity'
 *   If no entity_type is supplied, it will be determined based on the fields you request.
 *   If no entity_type is supplied and no fields are specified, 'Contact' will be assumed.
 *   Optionally include the desired custom data to be fetched (or else all custom data for this entity will be returned)
 *   Example: 'entity_id' => 123, 'return.custom_6' => 1, 'return.custom_33' => 1
 *   If you do not know the ID, you may use group name : field name, for example 'return.foo_stuff:my_field' => 1
 *
 * @throws API_Exception
 * @return array
 */
function civicrm_api3_custom_value_get($params) {

  $getParams = array(
    'entityID' => $params['entity_id'],
    'entityType' => CRM_Utils_Array::value('entity_table', $params, ''),
  );
  if (strstr($getParams['entityType'], 'civicrm_')) {
    $getParams['entityType'] = ucfirst(substr($getParams['entityType'], 8));
  }
  unset($params['entity_id'], $params['entity_table']);
  foreach ($params as $id => $param) {
    if ($param && substr($id, 0, 6) == 'return') {
      $id = substr($id, 7);
      list($c, $i) = CRM_Utils_System::explode('_', $id, 2);
      if ($c == 'custom' && is_numeric($i)) {
        $names['custom_' . $i] = 'custom_' . $i;
        $id = $i;
      }
      else {
        // Lookup names if ID was not supplied
        list($group, $field) = CRM_Utils_System::explode(':', $id, 2);
        $id = CRM_Core_BAO_CustomField::getCustomFieldID($field, $group);
        if (!$id) {
          continue;
        }
        $names['custom_' . $id] = 'custom_' . $i;
      }
      $getParams['custom_' . $id] = 1;
    }
  }

  $result = CRM_Core_BAO_CustomValueTable::getValues($getParams);

  if ($result['is_error']) {
    if ($result['error_message'] == "No values found for the specified entity ID and custom field(s).") {
      $values = array();
      return civicrm_api3_create_success($values, $params, 'CustomValue');
    }
    else {
      throw new API_Exception($result['error_message']);
    }
  }
  else {
    $entity_id = $result['entityID'];
    unset($result['is_error'], $result['entityID']);
    // Convert multi-value strings to arrays
    $sp = CRM_Core_DAO::VALUE_SEPARATOR;
    foreach ($result as $id => $value) {
      if (strpos($value, $sp) !== FALSE) {
        $value = explode($sp, trim($value, $sp));
      }

      $idArray = explode('_', $id);
      if ($idArray[0] != 'custom') {
        continue;
      }
      $fieldNumber = $idArray[1];
      $customFieldInfo = CRM_Core_BAO_CustomField::getNameFromID($fieldNumber);
      $info = array_pop($customFieldInfo);
      // id is the index for returned results

      if (empty($idArray[2])) {
        $n = 0;
        $id = $fieldNumber;
      }
      else {
        $n = $idArray[2];
        $id = $fieldNumber . "." . $idArray[2];
      }
      if (!empty($params['format.field_names'])) {
        $id = $info['field_name'];
      }
      else {
        $id = $fieldNumber;
      }
      $values[$id]['entity_id'] = $getParams['entityID'];
      if (!empty($getParams['entityType'])) {
        $values[$id]['entity_table'] = $getParams['entityType'];
      }
      //set 'latest' -useful for multi fields but set for single for consistency
      $values[$id]['latest'] = $value;
      $values[$id]['id'] = $id;
      $values[$id][$n] = $value;
    }
    return civicrm_api3_create_success($values, $params, 'CustomValue');
  }
}

/**
 * Adjust Metadata for Get action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_custom_value_get_spec(&$params) {
  $params['entity_id']['api.required'] = 1;
  $params['entity_id']['title'] = 'Entity ID';
}
