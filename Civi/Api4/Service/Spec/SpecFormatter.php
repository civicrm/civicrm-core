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

namespace Civi\Api4\Service\Spec;

use Civi\Api4\Utils\CoreUtil;
use Civi\Api4\Utils\FormattingUtil;

class SpecFormatter {

  /**
   * Convert array from `$entity->getFields()` or `$entity->getCustomFields()` into a FieldSpec object
   */
  public static function arrayToField(string $fieldName, array $data, string $entityName): FieldSpec {
    $dataTypeName = \CRM_Utils_Schema::getDataType($data);

    $isCustom = !empty($data['custom_field_id']);
    $hasDefault = isset($data['default']) && $data['default'] !== '';
    // Custom field
    if ($isCustom) {
      $field = new CustomFieldSpec($fieldName, $entityName, $dataTypeName);
      $field->setColumnName($data['column_name']);
      [$groupName, $fieldName] = explode('.', $fieldName);
      // Fields belonging to custom entities are treated as normal
      if (str_starts_with($entityName, 'Custom_')) {
        // Set type = Field instead of Custom
        $field->setType('Field');
        // Remove customGroupName prefix
        $field->setName($fieldName);
      }
      $field->setTableName($data['table_name']);
      $field->setCustomFieldId($data['custom_field_id']);
      $field->setCustomGroupName($groupName);
      $field->setHelpPre($data['help_pre'] ?? NULL);
      $field->setHelpPost($data['help_post'] ?? NULL);
    }
    // Core field
    else {
      $field = new FieldSpec($fieldName, $entityName, $dataTypeName);
      $field->setColumnName($fieldName);
      $field->setType('Field');
      $field->setRequired(!empty($data['required']) && !$hasDefault && empty($data['primary_key']) && empty($data['default_fallback']) && empty($data['default_callback']));
      // Translate 'default_fallback' into 'required_if' rules:
      if (!empty($data['default_fallback'])) {
        $field->setRequiredIf('empty($values.' . implode(') && empty($values.', $data['default_fallback']) . ')');
      }
    }
    // Either
    $field->setNullable(empty($data['required']));
    $field->setTitle($data['title'] ?? NULL);
    $field->setLabel($data['input_attrs']['label'] ?? NULL);
    $field->setLocalizable($data['localizable'] ?? FALSE);
    if (!empty($data['DFKEntities'])) {
      $field->setDfkEntities($data['DFKEntities']);
    }
    if (!empty($data['pseudoconstant'])) {
      // Do not load options if 'prefetch' is disabled
      if (($data['pseudoconstant']['prefetch'] ?? NULL) !== 'disabled') {
        $field->setOptionsCallback([__CLASS__, 'getOptions']);
      }
      // Explicitly declared suffixes
      if (!empty($data['pseudoconstant']['suffixes'])) {
        $suffixes = $data['pseudoconstant']['suffixes'];
      }
      else {
        // These suffixes are always supported if a field has options
        $suffixes = ['name', 'label'];
        // Add other columns specified in schema (e.g. 'abbr_column')
        foreach (array_diff(array_keys(\CRM_Core_SelectValues::optionAttributes()), $suffixes) as $suffix) {
          if (!empty($data['pseudoconstant'][$suffix . '_column'])) {
            $suffixes[] = $suffix;
          }
        }
        if (!empty($data['pseudoconstant']['option_group_name'])) {
          $suffixes = CoreUtil::getOptionValueFields($data['pseudoconstant']['option_group_name'], 'name');
        }
      }
      $field->setSuffixes($suffixes);
    }
    // Primary keys are also considered readonly, since they cannot be changed
    $field->setReadonly(!empty($data['readonly']) || !empty($data['primary_key']));
    if (isset($data['usage'])) {
      $field->setUsage($data['usage']);
    }
    if ($hasDefault) {
      $field->setDefaultValue(FormattingUtil::convertDataType($data['default'], $dataTypeName));
    }
    $field->setSerialize($data['serialize'] ?? NULL);
    $field->setDescription($data['description'] ?? NULL);
    $field->setDeprecated($data['deprecated'] ?? FALSE);
    self::setInputTypeAndAttrs($field, $data);

    $field->setPermission($data['permission'] ?? NULL);
    $fkAPIName = $data['entity_reference']['entity'] ?? NULL;
    if ($fkAPIName) {
      $field->setFkEntity($fkAPIName);
      $field->setFkColumn($data['entity_reference']['key'] ?? CoreUtil::getIdFieldName($fkAPIName));
    }
    // For pseudo-fk fields like `civicrm_group.parents`
    elseif (($data['input_type'] ?? NULL) === 'EntityRef' && !empty($data['pseudoconstant']['table'])) {
      $field->setFkEntity(CoreUtil::getApiNameFromTableName($data['pseudoconstant']['table']));
    }

    return $field;
  }

  /**
   * Callback function to build option lists for all entities.
   *
   * @param array $field
   * @param array $values
   * @param bool|array $returnFormat
   * @param bool $checkPermissions
   * @return array|false
   */
  public static function getOptions($field, $values, $returnFormat, $checkPermissions) {
    $options = FormattingUtil::getFieldOptions($field, $values, FALSE, $checkPermissions);

    // Special 'current_domain' option
    if ($field['fk_entity'] === 'Domain') {
      array_unshift($options, [
        'id' => 'current_domain',
        'name' => 'current_domain',
        'label' => ts('Current Domain'),
        'icon' => 'fa-sign-in',
      ]);
    }
    return $options;
  }

  /**
   * @param \Civi\Api4\Service\Spec\FieldSpec $fieldSpec
   * @param array $data
   */
  public static function setInputTypeAndAttrs(FieldSpec $fieldSpec, $data) {
    $inputTypeMap = [
      'Select Date' => 'Date',
    ];
    $inputType = $inputTypeMap[$data['input_type'] ?? ''] ?? $data['input_type'] ?? NULL;
    $inputAttrs = $data['input_attrs'] ?? [];

    if (in_array($inputType, ['Select', 'EntityRef'], TRUE) && !empty($data['serialize'])) {
      $inputAttrs['multiple'] = TRUE;
    }
    if ($inputType == 'Date' && !empty($inputAttrs['format_type'])) {
      self::setLegacyDateFormat($inputAttrs);
    }
    if ($inputType == 'Text' && !empty($data['maxlength'])) {
      $inputAttrs['maxlength'] = (int) $data['maxlength'];
    }
    if (isset($inputAttrs['min']) && is_string($inputAttrs['min'])) {
      $inputAttrs['min'] = (int) $inputAttrs['min'];
    }
    if (isset($inputAttrs['max']) && is_string($inputAttrs['max'])) {
      $inputAttrs['max'] = (int) $inputAttrs['max'];
    }
    // Ensure all keys use lower_case not camelCase
    $snakeKeys = array_map('CRM_Utils_String::convertStringToSnakeCase', array_keys($inputAttrs));
    $inputAttrs = array_combine($snakeKeys, $inputAttrs);

    $fieldSpec
      ->setInputType($inputType)
      ->setInputAttrs($inputAttrs);
  }

  /**
   * @param array $inputAttrs
   */
  public static function setLegacyDateFormat(&$inputAttrs) {
    if (empty(\Civi::$statics['legacyDatePrefs'][$inputAttrs['format_type']])) {
      \Civi::$statics['legacyDatePrefs'][$inputAttrs['format_type']] = [];
      $params = ['name' => $inputAttrs['format_type']];
      \CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_PreferencesDate', $params, \Civi::$statics['legacyDatePrefs'][$inputAttrs['format_type']]);
    }
    $dateFormat = \Civi::$statics['legacyDatePrefs'][$inputAttrs['format_type']];
    unset($inputAttrs['format_type']);
    $inputAttrs['time'] = !empty($dateFormat['time_format']);
    $inputAttrs['date'] = TRUE;
    $inputAttrs['start_date_years'] = (int) $dateFormat['start'];
    $inputAttrs['end_date_years'] = (int) $dateFormat['end'];
  }

}
