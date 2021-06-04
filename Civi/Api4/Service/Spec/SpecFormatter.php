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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace Civi\Api4\Service\Spec;

use Civi\Api4\Utils\CoreUtil;
use CRM_Core_DAO_AllCoreTables as AllCoreTables;

class SpecFormatter {

  /**
   * @param array $data
   * @param string $entity
   *
   * @return FieldSpec
   */
  public static function arrayToField(array $data, $entity) {
    $dataTypeName = self::getDataType($data);

    if (!empty($data['custom_group_id'])) {
      $field = new CustomFieldSpec($data['name'], $entity, $dataTypeName);
      if (strpos($entity, 'Custom_') !== 0) {
        $field->setName($data['custom_group.name'] . '.' . $data['name']);
      }
      else {
        $field->setTableName($data['custom_group.table_name']);
      }
      $field->setColumnName($data['column_name']);
      $field->setCustomFieldId($data['id'] ?? NULL);
      $field->setCustomGroupName($data['custom_group.name']);
      $field->setTitle($data['label']);
      $field->setLabel($data['custom_group.title'] . ': ' . $data['label']);
      $field->setHelpPre($data['help_pre'] ?? NULL);
      $field->setHelpPost($data['help_post'] ?? NULL);
      if (self::customFieldHasOptions($data)) {
        $field->setOptionsCallback([__CLASS__, 'getOptions']);
      }
      $field->setReadonly($data['is_view']);
    }
    else {
      $name = $data['name'] ?? NULL;
      $field = new FieldSpec($name, $entity, $dataTypeName);
      $field->setType('Field');
      $field->setColumnName($name);
      $field->setRequired(!empty($data['required']));
      $field->setTitle($data['title'] ?? NULL);
      $field->setLabel($data['html']['label'] ?? NULL);
      if (!empty($data['pseudoconstant'])) {
        $field->setOptionsCallback([__CLASS__, 'getOptions']);
      }
      $field->setReadonly(!empty($data['readonly']));
    }
    $field->setSerialize($data['serialize'] ?? NULL);
    $field->setDefaultValue($data['default'] ?? NULL);
    $field->setDescription($data['description'] ?? NULL);
    self::setInputTypeAndAttrs($field, $data, $dataTypeName);

    $field->setPermission($data['permission'] ?? NULL);
    $fkAPIName = $data['FKApiName'] ?? NULL;
    $fkClassName = $data['FKClassName'] ?? NULL;
    if ($fkAPIName || $fkClassName) {
      $field->setFkEntity($fkAPIName ?: AllCoreTables::getBriefName($fkClassName));
    }

    return $field;
  }

  /**
   * Does this custom field have options
   *
   * @param array $field
   * @return bool
   */
  private static function customFieldHasOptions($field) {
    // This will include boolean fields with Yes/No options.
    if (in_array($field['html_type'], ['Radio', 'CheckBox'])) {
      return TRUE;
    }
    // Do this before the "Select" string search because date fields have a "Select Date" html_type
    // and contactRef fields have an "Autocomplete-Select" html_type - contacts are an FK not an option list.
    if (in_array($field['data_type'], ['ContactReference', 'Date'])) {
      return FALSE;
    }
    if (strpos($field['html_type'], 'Select') !== FALSE) {
      return TRUE;
    }
    return !empty($field['option_group_id']);
  }

  /**
   * Get the data type from an array. Defaults to 'data_type' with fallback to
   * mapping for the integer value 'type'
   *
   * @param array $data
   *
   * @return string
   */
  private static function getDataType(array $data) {
    if (isset($data['data_type'])) {
      return !empty($data['time_format']) ? 'Timestamp' : $data['data_type'];
    }

    $dataTypeInt = $data['type'] ?? NULL;
    $dataTypeName = \CRM_Utils_Type::typeToString($dataTypeInt);

    return $dataTypeName;
  }

  /**
   * Callback function to build option lists for all DAO & custom fields.
   *
   * @param FieldSpec $spec
   * @param array $values
   * @param bool|array $returnFormat
   * @param bool $checkPermissions
   * @return array|false
   */
  public static function getOptions($spec, $values, $returnFormat, $checkPermissions) {
    $fieldName = $spec->getName();

    if ($spec instanceof CustomFieldSpec) {
      // buildOptions relies on the custom_* type of field names
      $fieldName = sprintf('custom_%d', $spec->getCustomFieldId());
    }

    // BAO::buildOptions returns a single-dimensional list, we call that first because of the hook contract,
    // @see CRM_Utils_Hook::fieldOptions
    // We then supplement the data with additional properties if requested.
    $bao = CoreUtil::getBAOFromApiName($spec->getEntity());
    $optionLabels = $bao::buildOptions($fieldName, NULL, $values);

    if (!is_array($optionLabels) || !$optionLabels) {
      $options = FALSE;
    }
    else {
      $options = \CRM_Utils_Array::makeNonAssociative($optionLabels, 'id', 'label');
      if (is_array($returnFormat)) {
        self::addOptionProps($options, $spec, $bao, $fieldName, $values, $returnFormat);
      }
    }
    return $options;
  }

  /**
   * Augment the 2 values returned by BAO::buildOptions (id, label) with extra properties (name, description, color, icon, etc).
   *
   * We start with BAO::buildOptions in order to respect hooks which may be adding/removing items, then we add the extra data.
   *
   * @param array $options
   * @param FieldSpec $spec
   * @param \CRM_Core_DAO $baoName
   * @param string $fieldName
   * @param array $values
   * @param array $returnFormat
   */
  private static function addOptionProps(&$options, $spec, $baoName, $fieldName, $values, $returnFormat) {
    // FIXME: For now, call the buildOptions function again and then combine the arrays. Not an ideal approach.
    // TODO: Teach CRM_Core_Pseudoconstant to always load multidimensional option lists so we can get more properties like 'color' and 'icon',
    // however that might require a change to the hook_civicrm_fieldOptions signature so that's a bit tricky.
    if (in_array('name', $returnFormat)) {
      $props['name'] = $baoName::buildOptions($fieldName, 'validate', $values);
    }
    $returnFormat = array_diff($returnFormat, ['id', 'name', 'label']);
    // CRM_Core_Pseudoconstant doesn't know how to fetch extra stuff like icon, description, color, etc., so we have to invent that wheel here...
    if ($returnFormat) {
      $optionIds = implode(',', array_column($options, 'id'));
      $optionIndex = array_flip(array_column($options, 'id'));
      if ($spec instanceof CustomFieldSpec) {
        $optionGroupId = \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $spec->getCustomFieldId(), 'option_group_id');
      }
      else {
        $dao = new $baoName();
        $fieldSpec = $dao->getFieldSpec($fieldName);
        $pseudoconstant = $fieldSpec['pseudoconstant'] ?? NULL;
        $optionGroupName = $pseudoconstant['optionGroupName'] ?? NULL;
        $optionGroupId = $optionGroupName ? \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $optionGroupName, 'id', 'name') : NULL;
      }
      if (!empty($optionGroupId)) {
        $extraStuff = \CRM_Core_BAO_OptionValue::getOptionValuesArray($optionGroupId);
        $keyColumn = $pseudoconstant['keyColumn'] ?? 'value';
        foreach ($extraStuff as $item) {
          if (isset($optionIndex[$item[$keyColumn]])) {
            foreach ($returnFormat as $ret) {
              // Note: our schema is inconsistent about whether `description` fields allow html,
              // but it's usually assumed to be plain text, so we strip_tags() to standardize it.
              $options[$optionIndex[$item[$keyColumn]]][$ret] = ($ret === 'description' && isset($item[$ret])) ? strip_tags($item[$ret]) : $item[$ret] ?? NULL;
            }
          }
        }
      }
      else {
        // Fetch the abbr if requested using context: abbreviate
        if (in_array('abbr', $returnFormat)) {
          $props['abbr'] = $baoName::buildOptions($fieldName, 'abbreviate', $values);
          $returnFormat = array_diff($returnFormat, ['abbr']);
        }
        // Fetch anything else (color, icon, description)
        if ($returnFormat && !empty($pseudoconstant['table']) && \CRM_Utils_Rule::commaSeparatedIntegers($optionIds)) {
          $sql = "SELECT * FROM {$pseudoconstant['table']} WHERE id IN (%1)";
          $query = \CRM_Core_DAO::executeQuery($sql, [1 => [$optionIds, 'CommaSeparatedIntegers']]);
          while ($query->fetch()) {
            foreach ($returnFormat as $ret) {
              if (property_exists($query, $ret)) {
                // Note: our schema is inconsistent about whether `description` fields allow html,
                // but it's usually assumed to be plain text, so we strip_tags() to standardize it.
                $options[$optionIndex[$query->id]][$ret] = $ret === 'description' ? strip_tags($query->$ret) : $query->$ret;
              }
            }
          }
        }
      }
    }
    if (isset($props)) {
      foreach ($options as &$option) {
        foreach ($props as $name => $prop) {
          $option[$name] = $prop[$option['id']] ?? NULL;
        }
      }
    }
  }

  /**
   * @param \Civi\Api4\Service\Spec\FieldSpec $fieldSpec
   * @param array $data
   * @param string $dataTypeName
   */
  public static function setInputTypeAndAttrs(FieldSpec &$fieldSpec, $data, $dataTypeName) {
    $inputType = $data['html']['type'] ?? $data['html_type'] ?? NULL;
    $inputAttrs = $data['html'] ?? [];
    unset($inputAttrs['type']);

    $map = [
      'Select Date' => 'Date',
      'Link' => 'Url',
    ];
    $inputType = $map[$inputType] ?? $inputType;
    if ($dataTypeName === 'ContactReference') {
      $inputType = 'EntityRef';
    }
    if (in_array($inputType, ['Select', 'EntityRef'], TRUE) && !empty($data['serialize'])) {
      $inputAttrs['multiple'] = TRUE;
    }
    if ($inputType == 'Date' && !empty($inputAttrs['formatType'])) {
      self::setLegacyDateFormat($inputAttrs);
    }
    // Date/time settings from custom fields
    if ($inputType == 'Date' && !empty($data['custom_group_id'])) {
      $inputAttrs['time'] = empty($data['time_format']) ? FALSE : ($data['time_format'] == 1 ? 12 : 24);
      $inputAttrs['date'] = $data['date_format'];
      $inputAttrs['start_date_years'] = (int) $data['start_date_years'];
      $inputAttrs['end_date_years'] = (int) $data['end_date_years'];
    }
    if ($inputType == 'Text' && !empty($data['maxlength'])) {
      $inputAttrs['maxlength'] = (int) $data['maxlength'];
    }
    if ($inputType == 'TextArea') {
      foreach (['rows', 'cols', 'note_rows', 'note_cols'] as $prop) {
        if (!empty($data[$prop])) {
          $inputAttrs[str_replace('note_', '', $prop)] = (int) $data[$prop];
        }
      }
    }
    // Ensure all keys use lower_case not camelCase
    foreach ($inputAttrs as $key => $val) {
      if ($key !== strtolower($key)) {
        unset($inputAttrs[$key]);
        $key = strtolower(preg_replace('/(?=[A-Z])/', '_$0', $key));
        $inputAttrs[$key] = $val;
      }
    }
    $fieldSpec
      ->setInputType($inputType)
      ->setInputAttrs($inputAttrs);
  }

  /**
   * @param array $inputAttrs
   */
  private static function setLegacyDateFormat(&$inputAttrs) {
    if (empty(\Civi::$statics['legacyDatePrefs'][$inputAttrs['formatType']])) {
      \Civi::$statics['legacyDatePrefs'][$inputAttrs['formatType']] = [];
      $params = ['name' => $inputAttrs['formatType']];
      \CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_PreferencesDate', $params, \Civi::$statics['legacyDatePrefs'][$inputAttrs['formatType']]);
    }
    $dateFormat = \Civi::$statics['legacyDatePrefs'][$inputAttrs['formatType']];
    unset($inputAttrs['formatType']);
    $inputAttrs['time'] = !empty($dateFormat['time_format']);
    $inputAttrs['date'] = TRUE;
    $inputAttrs['start_date_years'] = (int) $dateFormat['start'];
    $inputAttrs['end_date_years'] = (int) $dateFormat['end'];
  }

}
