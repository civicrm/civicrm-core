<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
 * ```
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
 * ```
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
  $create = ['entityID' => $params['entity_id']];
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
 * @throws CRM_Core_Exception
 * @return array
 */
function civicrm_api3_custom_value_get($params) {

  $getParams = [
    'entityID' => $params['entity_id'],
    'entityType' => $params['entity_table'] ?? '',
  ];
  if (str_contains($getParams['entityType'], 'civicrm_')) {
    $getParams['entityType'] = ucfirst(substr($getParams['entityType'], 8));
  }
  unset($params['entity_id'], $params['entity_table']);
  foreach ($params as $id => $param) {
    if ($param && substr($id, 0, 6) == 'return') {
      $returnVal = $param;
      if (!empty(substr($id, 7))) {
        $returnVal = substr($id, 7);
      }
      if (!is_array($returnVal)) {
        $returnVal = explode(',', $returnVal);
      }
      foreach ($returnVal as $value) {
        list($c, $i) = CRM_Utils_System::explode('_', $value, 2);
        if ($c == 'custom' && is_numeric($i)) {
          $names['custom_' . $i] = 'custom_' . $i;
          $fldId = $i;
        }
        else {
          // Lookup names if ID was not supplied
          list($group, $field) = CRM_Utils_System::explode(':', $value, 2);
          $fldId = CRM_Core_BAO_CustomField::getCustomFieldID($field, $group);
          if (!$fldId) {
            continue;
          }
          $names['custom_' . $fldId] = 'custom_' . $i;
        }
        $getParams['custom_' . $fldId] = 1;
      }
    }
  }

  $result = CRM_Core_BAO_CustomValueTable::getValues($getParams);

  if ($result['is_error']) {
    if ($result['error_message'] == "No values found for the specified entity ID and custom field(s).") {
      $values = [];
      return civicrm_api3_create_success($values, $params, 'CustomValue');
    }
    else {
      throw new CRM_Core_Exception($result['error_message']);
    }
  }
  else {
    $entity_id = $result['entityID'];
    unset($result['is_error'], $result['entityID']);
    // Convert multi-value strings to arrays
    $sp = CRM_Core_DAO::VALUE_SEPARATOR;
    foreach ($result as $id => $value) {
      if (strpos(($value ?? ''), $sp) !== FALSE) {
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

/**
 * CustomValue.gettree API specification
 *
 * @param array $spec description of fields supported by this API call
 *
 * @throws \CRM_Core_Exception
 */
function _civicrm_api3_custom_value_gettree_spec(&$spec) {
  $spec['entity_id'] = [
    'title' => 'Entity Id',
    'description' => 'Id of entity',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
  $entities = civicrm_api3('Entity', 'get');
  $entities = array_diff($entities['values'], $entities['deprecated']);
  $spec['entity_type'] = [
    'title' => 'Entity Type',
    'description' => 'API name of entity type, e.g. "Contact"',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'options' => array_combine($entities, $entities),
  ];
  // Return params for custom group, field & value
  foreach (CRM_Core_DAO_CustomGroup::fields() as $field) {
    $name = 'custom_group.' . $field['name'];
    $spec[$name] = ['name' => $name] + $field;
  }
  foreach (CRM_Core_DAO_CustomField::fields() as $field) {
    $name = 'custom_field.' . $field['name'];
    $spec[$name] = ['name' => $name] + $field;
  }
  $spec['custom_value.id'] = [
    'title' => 'Custom Value Id',
    'description' => 'Id of record in custom value table',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['custom_value.data'] = [
    'title' => 'Custom Value (Raw)',
    'description' => 'Raw value as stored in the database',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['custom_value.display'] = [
    'title' => 'Custom Value (Formatted)',
    'description' => 'Custom value formatted for display',
    'type' => CRM_Utils_Type::T_STRING,
  ];
}

/**
 * CustomValue.gettree API
 *
 * @param array $params
 *
 * @return array API result
 * @throws \CRM_Core_Exception
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_custom_value_gettree($params) {
  $ret = [];
  $options = _civicrm_api3_get_options_from_params($params);
  $toReturn = [
    'custom_group' => [],
    'custom_field' => [],
    'custom_value' => [],
  ];
  foreach (array_keys($options['return']) as $r) {
    list($type, $field) = explode('.', $r);
    if (isset($toReturn[$type])) {
      $toReturn[$type][] = $field;
    }
  }
  // We must have a name if not indexing sequentially
  if (empty($params['sequential']) && $toReturn['custom_field']) {
    $toReturn['custom_field'][] = 'name';
  }
  switch ($params['entity_type']) {
    case 'Contact':
      $ret = ['entityType' => 'contact_type', 'subTypes' => 'contact_sub_type'];
      break;

    case 'Activity':
    case 'Campaign':
    case 'Case':
    case 'Contribution':
    case 'Event':
    case 'Grant':
    case 'Membership':
    case 'Relationship':
      $ret = ['subTypes' => strtolower($params['entity_type']) . '_type_id'];
      break;

    case 'Participant':
      // todo
  }
  $treeParams = [
    'entityType' => $params['entity_type'],
    'subTypes' => [],
    'subName' => NULL,
  ];
  // Fetch entity data for custom group type/sub-type
  // Also verify access permissions (api3 will throw an exception if permission denied)
  if ($ret || !empty($params['check_permissions'])) {
    $entityData = civicrm_api3($params['entity_type'], 'getsingle', [
      'id' => $params['entity_id'],
      'check_permissions' => !empty($params['check_permissions']),
      'return' => array_merge(['id'], array_values($ret)),
    ]);
    foreach ($ret as $param => $key) {
      if (isset($entityData[$key])) {
        $treeParams[$param] = $entityData[$key];
      }
    }
  }
  $permission = empty($params['check_permissions']) ? FALSE : CRM_Core_Permission::VIEW;
  $tree = CRM_Core_BAO_CustomGroup::getTree($treeParams['entityType'], $toReturn, $params['entity_id'], NULL, $treeParams['subTypes'], $treeParams['subName'], TRUE, NULL, FALSE, $permission);
  unset($tree['info']);
  $result = [];
  foreach ($tree as $group) {
    $result[$group['name']] = [];
    $groupToReturn = $toReturn['custom_group'] ?: array_keys($group);
    foreach ($groupToReturn as $item) {
      $result[$group['name']][$item] = $group[$item] ?? NULL;
    }
    $result[$group['name']]['fields'] = [];
    foreach ($group['fields'] as $fieldInfo) {
      $field = ['value' => NULL];
      $fieldToReturn = $toReturn['custom_field'] ?: array_keys($fieldInfo);
      foreach ($fieldToReturn as $item) {
        $field[$item] = $fieldInfo[$item] ?? NULL;
      }
      unset($field['customValue']);
      if (!empty($fieldInfo['customValue'])) {
        $field['value'] = CRM_Utils_Array::first($fieldInfo['customValue']);
        if (!$toReturn['custom_value'] || in_array('display', $toReturn['custom_value'])) {
          $field['value']['display'] = CRM_Core_BAO_CustomField::displayValue($field['value']['data'], $fieldInfo['id']);
        }
        foreach (array_keys($field['value']) as $key) {
          if ($toReturn['custom_value'] && !in_array($key, $toReturn['custom_value'])) {
            unset($field['value'][$key]);
          }
        }
      }
      if (empty($params['sequential'])) {
        $result[$group['name']]['fields'][$fieldInfo['name']] = $field;
      }
      else {
        $result[$group['name']]['fields'][] = $field;
      }
    }
  }
  return civicrm_api3_create_success($result, $params, 'CustomValue', 'gettree');
}

/**
 * CustomValue.getdisplayvalue API specification
 *
 * @param array $spec description of fields supported by this API call
 */
function _civicrm_api3_custom_value_getdisplayvalue_spec(&$spec) {
  $spec['entity_id'] = [
    'title' => 'Entity Id',
    'description' => 'Id of entity',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
  $spec['custom_field_id'] = [
    'title' => 'Custom Field ID',
    'description' => 'Id of custom field',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
  $spec['custom_field_value'] = [
    'title' => 'Custom Field value',
    'description' => 'Specify the value of the custom field to return as displayed value, or omit to use the current value.',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
  ];
}

/**
 * CustomValue.getdisplayvalue API
 *
 * @param array $params
 *
 * @return array API result
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_custom_value_getdisplayvalue($params) {
  // Null or missing means use the current db value, but treat '0', 0, and '' as legitimate values to look up.
  if (($params['custom_field_value'] ?? NULL) === NULL) {
    $params['custom_field_value'] = civicrm_api3('CustomValue', 'getsingle', [
      'return' => ["custom_{$params['custom_field_id']}"],
      'entity_id' => $params['entity_id'],
    ]);
    $params['custom_field_value'] = $params['custom_field_value']['latest'];
  }
  $values[$params['custom_field_id']]['display'] = CRM_Core_BAO_CustomField::displayValue($params['custom_field_value'], $params['custom_field_id'], $params['entity_id'] ?? NULL);
  $values[$params['custom_field_id']]['raw'] = $params['custom_field_value'];
  return civicrm_api3_create_success($values, $params, 'CustomValue', 'getdisplayvalue');
}
