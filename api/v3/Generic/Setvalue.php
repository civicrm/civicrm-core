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
 * @package CiviCRM_APIv3
 */

/**
 * Set a single value using the api.
 *
 * This function is called when no specific setvalue api exists.
 * Params must contain at least id=xx & {one of the fields from getfields}=value
 *
 * @param array $apiRequest
 *
 * @throws CRM_Core_Exception
 * @return array
 */
function civicrm_api3_generic_setValue($apiRequest) {
  $entity = $apiRequest['entity'];
  $params = $apiRequest['params'];
  $id = $params['id'];
  if (!is_numeric($id)) {
    return civicrm_api3_create_error(ts('Please enter a number'), [
      'error_code' => 'NaN',
      'field' => "id",
    ]);
  }

  $field = CRM_Utils_String::munge($params['field']);
  $value = $params['value'];

  $fields = civicrm_api($entity, 'getFields', [
    'version' => 3,
    'action' => 'create',
  ]);
  // getfields error, shouldn't happen.
  if ($fields['is_error']) {
    return $fields;
  }
  $fields = $fields['values'];

  $isCustom = strpos($field, 'custom_') === 0;
  // Trim off the id portion of a multivalued custom field name
  $fieldKey = $isCustom && substr_count($field, '_') > 1 ? rtrim(rtrim($field, '1234567890'), '_') : $field;
  if (!array_key_exists($fieldKey, $fields)) {
    return civicrm_api3_create_error("Param 'field' ($field) is invalid. must be an existing field", ["error_code" => "invalid_field", "fields" => array_keys($fields)]);
  }

  $def = $fields[$fieldKey];
  $title = $def['title'] ?? ts('Field');
  // Disallow empty values except for the number zero.
  // TODO: create a utility for this since it's needed in many places
  if (!empty($def['required']) || !empty($def['is_required'])) {
    if ((empty($value) || $value === 'null') && $value !== '0' && $value !== 0) {
      return civicrm_api3_create_error(ts('%1 is a required field.', [1 => $title]), ["error_code" => "required", "field" => $field]);
    }
  }

  switch ($def['type']) {
    case CRM_Utils_Type::T_FLOAT:
      if (!is_numeric($value) && !empty($value) && $value !== 'null') {
        return civicrm_api3_create_error(ts('%1 must be a number.', [1 => $title]), ['error_code' => 'NaN']);
      }
      break;

    case CRM_Utils_Type::T_INT:
      if (!CRM_Utils_Rule::integer($value) && !empty($value) && $value !== 'null') {
        return civicrm_api3_create_error(ts('%1 must be a number.', [1 => $title]), ['error_code' => 'NaN']);
      }
      break;

    case CRM_Utils_Type::T_STRING:
    case CRM_Utils_Type::T_TEXT:
      if (array_key_exists('maxlength', $def)) {
        $value = substr($value, 0, $def['maxlength']);
      }
      break;

    case CRM_Utils_Type::T_DATE:
      $value = CRM_Utils_Type::escape($value, "Date", FALSE);
      if (!$value) {
        return civicrm_api3_create_error("Param '$field' is not a date. format YYYYMMDD or YYYYMMDDHHMMSS");
      }
      break;

    case CRM_Utils_Type::T_BOOLEAN:
      // Allow empty value for non-required fields
      if ($value === '' || $value === 'null') {
        $value = '';
      }
      else {
        $value = (boolean) $value;
      }
      break;

    default:
      return civicrm_api3_create_error("Param '$field' is of a type not managed yet (" . $def['type'] . "). Join the API team and help us implement it", ['error_code' => 'NOT_IMPLEMENTED']);
  }

  $dao_name = _civicrm_api3_get_DAO($entity);
  $params = ['id' => $id, $field => $value];

  if ((!empty($def['pseudoconstant']) || !empty($def['option_group_id'])) && $value !== '' && $value !== 'null') {
    _civicrm_api3_api_match_pseudoconstant($params[$field], $entity, $field, $def);
  }

  CRM_Utils_Hook::pre('edit', $entity, $id, $params);

  // Custom fields
  if ($isCustom) {
    CRM_Utils_Array::crmReplaceKey($params, 'id', 'entityID');
    // Treat 'null' as empty value. This is awful but the rest of the code supports it.
    if ($params[$field] === 'null') {
      $params[$field] = '';
    }
    CRM_Core_BAO_CustomValueTable::setValues($params);
    CRM_Utils_Hook::post('edit', $entity, $id);
  }
  // Core fields
  elseif (CRM_Core_DAO::setFieldValue($dao_name, $id, $field, $params[$field])) {
    $entityDAO = new $dao_name();
    $entityDAO->copyValues($params);
    CRM_Utils_Hook::post('edit', $entity, $entityDAO->id, $entityDAO);
  }
  else {
    return civicrm_api3_create_error("error assigning $field=$value for $entity (id=$id)");
  }

  // Add changelog entry - TODO: Should we do this for other entities as well?
  if (strtolower($entity) === 'contact') {
    CRM_Core_BAO_Log::register($id, 'civicrm_contact', $id);
  }

  return civicrm_api3_create_success($params);
}
