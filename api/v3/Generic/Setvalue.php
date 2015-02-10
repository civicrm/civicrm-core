<?php
/**
 * params must contain at least id=xx & {one of the fields from getfields}=value
 *
 * @param $apiRequest
 *
 * @throws API_Exception
 * @return array
 */
function civicrm_api3_generic_setValue($apiRequest) {
  $entity = $apiRequest['entity'];
  $params = $apiRequest['params'];
  // we can't use _spec, doesn't work with generic
  civicrm_api3_verify_mandatory($params, NULL, array('id', 'field', 'value'));
  $id = $params['id'];
  if (!is_numeric($id)) {
    return civicrm_api3_create_error(ts('Please enter a number'), array('error_code' => 'NaN', 'field' => "id"));
  }

  $field = CRM_Utils_String::munge($params['field']);
  $value = $params['value'];

  $fields = civicrm_api($entity, 'getFields', array('version' => 3, 'action' => 'create', "sequential"));
  // getfields error, shouldn't happen.
  if ($fields['is_error'])
  return $fields;
  $fields = $fields['values'];

  if (!array_key_exists($field, $fields)) {
    return civicrm_api3_create_error("Param 'field' ($field) is invalid. must be an existing field", array("error_code" => "invalid_field", "fields" => array_keys($fields)));
  }

  $def = $fields[$field];
  // Disallow empty values except for the number zero.
  // TODO: create a utility for this since it's needed in many places
  // if (array_key_exists('required', $def) && CRM_Utils_System::isNull($value)) {
  if (array_key_exists('required', $def) && empty($value) && $value !== '0' && $value !== 0) {
    return civicrm_api3_create_error(ts("This can't be empty, please provide a value"), array("error_code" => "required", "field" => $field));
  }

  switch ($def['type']) {
    case CRM_Utils_Type::T_INT:
      if (!is_numeric($value)) {
        return civicrm_api3_create_error("Param '$field' must be a number", array('error_code' => 'NaN'));
      }

    case CRM_Utils_Type::T_STRING:
    case CRM_Utils_Type::T_TEXT:
      if (!CRM_Utils_Rule::xssString($value)) {
        return civicrm_api3_create_error(ts('Illegal characters in input (potential scripting attack)'), array('error_code' => 'XSS'));
      }
    if (array_key_exists('maxlength', $def)) {
      $value = substr($value, 0, $def['maxlength']);
    }
    break;

    case CRM_Utils_Type::T_DATE:
      $value = CRM_Utils_Type::escape($value,"Date",false);
      if (!$value)
        return civicrm_api3_create_error("Param '$field' is not a date. format YYYYMMDD or YYYYMMDDHHMMSS");
      break;

    case CRM_Utils_Type::T_BOOLEAN:
      $value = (boolean) $value;
      break;

    default:
      return civicrm_api3_create_error("Param '$field' is of a type not managed yet (".$def['type']."). Join the API team and help us implement it", array('error_code' => 'NOT_IMPLEMENTED'));
  }

  $dao_name = _civicrm_api3_get_DAO($entity);
  $params = array('id' => $id, $field => $value);
  CRM_Utils_Hook::pre('edit', $entity, $id, $params);

  // Custom fields
  if (strpos($field, 'custom_') === 0) {
    CRM_Utils_Array::crmReplaceKey($params, 'id', 'entityID');
    CRM_Core_BAO_CustomValueTable::setValues($params);
    CRM_Utils_Hook::post('edit', $entity, $id, CRM_Core_DAO::$_nullObject);
    return civicrm_api3_create_success($params);
  }
  // Core fields
  elseif (CRM_Core_DAO::setFieldValue($dao_name, $id, $field, $params[$field])) {
    $entityDAO = new $dao_name();
    $entityDAO->copyValues($params);
    CRM_Utils_Hook::post('edit', $entity, $entityDAO->id, $entityDAO);
    return civicrm_api3_create_success($params);
  }
  else {
    return civicrm_api3_create_error("error assigning $field=$value for $entity (id=$id)");
  }
}
