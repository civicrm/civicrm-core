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
 * This api exposes CiviCRM custom field.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create a 'custom field' within a custom field group.
 *
 * We also empty the static var in the getfields
 * function after deletion so that the field is available for us (getfields manages date conversion
 * among other things
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API success array
 */
function civicrm_api3_custom_field_create($params) {

  // Array created for passing options in params.
  if (isset($params['option_values']) && is_array($params['option_values'])) {
    $weight = 0;
    foreach ($params['option_values'] as $key => $value) {
      // Translate simple key/value pairs into full-blown option values
      if (!is_array($value)) {
        $value = [
          'label' => $value,
          'value' => $key,
          'is_active' => 1,
          'weight' => $weight,
        ];
        $key = $weight++;
      }
      $params['option_label'][$key] = $value['label'];
      $params['option_value'][$key] = $value['value'];
      $params['option_status'][$key] = $value['is_active'];
      $params['option_weight'][$key] = $value['weight'];
    }
  }
  $values = [];
  $customField = CRM_Core_BAO_CustomField::create($params);
  _civicrm_api3_object_to_array_unique_fields($customField, $values[$customField->id]);
  _civicrm_api3_custom_field_flush_static_caches();
  return civicrm_api3_create_success($values, $params, 'CustomField', $customField);
}

/**
 * Flush static caches in functions that might have stored available custom fields.
 */
function _civicrm_api3_custom_field_flush_static_caches() {
  civicrm_api('CustomField', 'getfields', ['version' => 3, 'cache_clear' => 1]);
  CRM_Core_BAO_UFField::getAvailableFieldsFlat(TRUE);
}

/**
 * Adjust Metadata for Create action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_custom_field_create_spec(&$params) {
  $params['label']['api.required'] = 1;
  $params['custom_group_id']['api.required'] = 1;
  $params['is_active']['api.default'] = 1;
  $params['option_values'] = [
    'title' => 'Option Values',
    'description' => "Pass an array of options (value => label) to create this field's option values",
  ];
  // TODO: Why expose this to the api at all?
  $params['option_type'] = [
    'title' => 'Option Type',
    'description' => 'This (boolean) field tells the BAO to create an option group for the field if the field type is appropriate',
    'api.default' => 1,
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];
  $params['data_type']['api.default'] = 'String';
  $params['is_active']['api.default'] = 1;
}

/**
 * Use this API to delete an existing custom field.
 *
 * @param array $params
 *   Array id of the field to be deleted.
 *
 * @return array
 */
function civicrm_api3_custom_field_delete($params) {
  $field = new CRM_Core_BAO_CustomField();
  $field->id = $params['id'];
  $field->find(TRUE);
  $customFieldDelete = CRM_Core_BAO_CustomField::deleteField($field);
  civicrm_api('CustomField', 'getfields', ['version' => 3, 'cache_clear' => 1]);
  return $customFieldDelete ? civicrm_api3_create_error('Error while deleting custom field') : civicrm_api3_create_success();
}

/**
 * Use this API to get existing custom fields.
 *
 * @param array $params
 *   Array to search on.
 *
 * @return array
 */
function civicrm_api3_custom_field_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Helper function to validate custom field value.
 *
 * @deprecated
 *
 * @param string $fieldName
 *   Custom field name (eg: custom_8 ).
 * @param mixed $value
 *   Field value to be validate.
 * @param array $fieldDetails
 *   Field Details.
 * @param array $errors
 *   Collect validation errors.
 *
 * @return array|NULL
 *   Validation errors
 * @todo remove this function - not in use but need to review functionality before
 * removing as it might be useful in wrapper layer
 */
function _civicrm_api3_custom_field_validate_field($fieldName, $value, $fieldDetails, &$errors = []) {
  return NULL;
  //see comment block
  if (!$value) {
    return $errors;
  }

  $dataType = $fieldDetails['data_type'];
  $htmlType = $fieldDetails['html_type'];

  switch ($dataType) {
    case 'Int':
      if (!CRM_Utils_Rule::integer($value)) {
        $errors[$fieldName] = 'Invalid integer value for ' . $fieldName;
      }
      break;

    case 'Float':
      if (!CRM_Utils_Rule::numeric($value)) {
        $errors[$fieldName] = 'Invalid numeric value for ' . $fieldName;
      }
      break;

    case 'Money':
      if (!CRM_Utils_Rule::money($value)) {
        $errors[$fieldName] = 'Invalid numeric value for ' . $fieldName;
      }
      break;

    case 'Link':
      if (!CRM_Utils_Rule::url($value)) {
        $errors[$fieldName] = 'Invalid link for ' . $fieldName;
      }
      break;

    case 'Boolean':
      if ($value != '1' && $value != '0') {
        $errors[$fieldName] = 'Invalid boolean (use 1 or 0) value for ' . $fieldName;
      }
      break;

    case 'Country':
      if (empty($value)) {
        break;
      }
      if ($htmlType != 'Multi-Select Country' && is_array($value)) {
        $errors[$fieldName] = 'Invalid country for ' . $fieldName;
        break;
      }

      if (!is_array($value)) {
        $value = [$value];
      }

      $query = "SELECT count(*) FROM civicrm_country WHERE id IN (" . implode(',', $value) . ")";
      if (CRM_Core_DAO::singleValueQuery($query) < count($value)) {
        $errors[$fieldName] = 'Invalid country(s) for ' . $fieldName;
      }
      break;

    case 'StateProvince':
      if (empty($value)) {
        break;
      }

      if ($htmlType != 'Multi-Select State/Province' && is_array($value)) {
        $errors[$fieldName] = 'Invalid State/Province for ' . $fieldName;
        break;
      }

      if (!is_array($value)) {
        $value = [$value];
      }

      $query = "
SELECT count(*)
  FROM civicrm_state_province
 WHERE id IN ('" . implode("','", $value) . "')";
      if (CRM_Core_DAO::singleValueQuery($query) < count($value)) {
        $errors[$fieldName] = 'Invalid State/Province for ' . $fieldName;
      }
      break;

    case 'ContactReference':
      //FIX ME
      break;
  }

  if (in_array($htmlType, [
    'Select', 'Multi-Select', 'CheckBox', 'Radio']) &&
    !isset($errors[$fieldName])
  ) {
    $options = CRM_Core_OptionGroup::valuesByID($fieldDetails['option_group_id']);
    if (!is_array($value)) {
      $value = [$value];
    }

    $invalidOptions = array_diff($value, array_keys($options));
    if (!empty($invalidOptions)) {
      $errors[$fieldName] = "Invalid option(s) for field '{$fieldName}': " . implode(',', $invalidOptions);
    }
  }

  return $errors;
}

/**
 * CRM-15191 - Hack to ensure the cache gets cleared after updating a custom field.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 */
function civicrm_api3_custom_field_setvalue($params) {
  require_once 'api/v3/Generic/Setvalue.php';
  $result = civicrm_api3_generic_setValue(["entity" => 'CustomField', 'params' => $params]);
  if (empty($result['is_error'])) {
    CRM_Utils_System::flushCache();
  }
  return $result;
}
