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
 * This api exposes CiviCRM custom field.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create a 'custom field' within a custom field group.
 *
 * We also empty the static var in the getfields
 * function after deletion so that the field is available for us (getfields
 * manages date conversion among other things
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API success array
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_custom_field_create(array $params): array {

  // Legacy handling for old way of naming serialized fields
  if (!empty($params['html_type'])) {
    if ($params['html_type'] === 'CheckBox' || str_starts_with($params['html_type'], 'Multi-')) {
      $params['serialize'] = 1;
    }
    $params['html_type'] = str_replace(['Multi-Select', 'Select Country', 'Select State/Province'], 'Select', $params['html_type']);
  }

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
  elseif (
    // Legacy handling for historical apiv3 behaviour.
    empty($params['id'])
    && !empty($params['html_type'])
    && $params['html_type'] !== 'Text'
    && empty($params['option_group_id'])
    && empty($params['option_value'])
    && in_array($params['data_type'] ?? '', ['String', 'Int', 'Float', 'Money'])) {
    // Trick the BAO into creating an option group even though no option values exist
    // because that odd behaviour is locked in via a test.
    $params['option_value'] = 1;
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
  if (isset(\Civi::$statics['CRM_Core_BAO_OptionGroup']['titles_by_name'])) {
    unset(\Civi::$statics['CRM_Core_BAO_OptionGroup']['titles_by_name']);
  }
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
  $params['name']['api.required'] = 0;
  $params['option_values'] = [
    'title' => 'Option Values',
    'description' => "Pass an array of options (value => label) to create this field's option values",
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
  // Legacy handling for serialize property
  $handleLegacy = (($params['legacy_html_type'] ?? !isset($params['serialize'])) && CRM_Core_BAO_Domain::isDBVersionAtLeast('5.27.alpha1'));
  if ($handleLegacy && !empty($params['return'])) {
    if (!is_array($params['return'])) {
      $params['return'] = explode(',', str_replace(' ', '', $params['return']));
    }
    if (!in_array('serialize', $params['return'])) {
      $params['return'][] = 'serialize';
    }
    if (!in_array('data_type', $params['return'])) {
      $params['return'][] = 'data_type';
    }
  }
  $legacyDataTypes = [
    'Select State/Province' => 'StateProvince',
    'Select Country' => 'Country',
  ];
  if ($handleLegacy && !empty($params['html_type'])) {
    $serializedTypes = ['CheckBox', 'Multi-Select', 'Multi-Select Country', 'Multi-Select State/Province'];
    if (is_string($params['html_type'])) {
      if (str_starts_with($params['html_type'], 'Multi-Select')) {
        $params['html_type'] = str_replace('Multi-Select', 'Select', $params['html_type']);
        $params['serialize'] = 1;
      }
      elseif (!in_array($params['html_type'], $serializedTypes)) {
        $params['serialize'] = 0;
      }
      if (isset($legacyDataTypes[$params['html_type']])) {
        $params['data_type'] = $legacyDataTypes[$params['html_type']];
        unset($params['html_type']);
      }
    }
    elseif (is_array($params['html_type']) && !empty($params['html_type']['IN'])) {
      $excludeNonSerialized = !array_diff($params['html_type']['IN'], $serializedTypes);
      $onlyNonSerialized = !array_intersect($params['html_type']['IN'], $serializedTypes);
      $params['html_type']['IN'] = array_map(function($val) {
        return str_replace(['Multi-Select', 'Select Country', 'Select State/Province'], 'Select', $val);
      }, $params['html_type']['IN']);
      if ($excludeNonSerialized) {
        $params['serialize'] = 1;
      }
      if ($onlyNonSerialized) {
        $params['serialize'] = 0;
      }
    }
  }

  $results = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);

  if ($handleLegacy && !empty($results['values']) && is_array($results['values'])) {
    foreach ($results['values'] as $id => &$result) {
      if (!empty($result['html_type'])) {
        if (in_array($result['data_type'], $legacyDataTypes)) {
          $result['html_type'] = array_search($result['data_type'], $legacyDataTypes);
        }
        if (!empty($result['serialize']) && $result['html_type'] !== 'Autocomplete-Select') {
          $result['html_type'] = str_replace('Select', 'Multi-Select', $result['html_type']);
        }
      }
    }
  }

  return $results;
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
      if (!is_numeric($value)) {
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

  if (in_array($htmlType, ['Select', 'Multi-Select', 'CheckBox', 'Radio'])
    && !isset($errors[$fieldName])
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
    Civi::rebuild(['system' => TRUE])->execute();
  }
  return $result;
}

function civicrm_api3_custom_field_getoptions($params) {
  $result = civicrm_api3_generic_getoptions(['entity' => 'CustomField', 'params' => $params]);
  // This provides legacy support for APIv3, allowing no-longer-existent html types
  if ($params['field'] === 'html_type') {
    $extras = [
      'Multi-Select' => 'Multi-Select',
      'Select Country' => 'Select Country',
      'Multi-Select Country' => 'Multi-Select Country',
      'Select State/Province' => 'Select State/Province',
      'Multi-Select State/Province' => 'Multi-Select State/Province',
    ];
    if (!empty($params['sequential'])) {
      $extras = CRM_Utils_Array::makeNonAssociative($extras);
    }
    $result['values'] = array_merge($result['values'], $extras);
  }
  return $result;
}
