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
    $dataTypeName = self::getDataType($data);

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
      $field->setRequired(!empty($data['required']) && !$hasDefault && empty($data['primary_key']));
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
        foreach (array_diff(FormattingUtil::$pseudoConstantSuffixes, $suffixes) as $suffix) {
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
    $field->setReadonly(!empty($data['readonly']));
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
   * Get the data type from an array. Defaults to 'data_type' with fallback to
   * mapping based on the 'sql_type'.
   *
   * @param array $data
   *
   * @return string
   */
  private static function getDataType(array $data) {
    if (isset($data['data_type'])) {
      return $data['data_type'];
    }

    // If no data_type provided, look it up from the sql_type
    $dataTypeInt = \CRM_Utils_Schema::getCrmTypeFromSqlType($data['sql_type']);
    $dataTypeName = \CRM_Utils_Type::typeToString($dataTypeInt);

    return $dataTypeName === 'Int' ? 'Integer' : $dataTypeName;
  }

  /**
   * Callback function to build option lists for all DAO & custom fields.
   *
   * @param array $field
   * @param array $values
   * @param bool|array $returnFormat
   * @param bool $checkPermissions
   * @return array|false
   */
  public static function getOptions($field, $values, $returnFormat, $checkPermissions) {
    $fieldName = $field['name'];

    if (!empty($field['custom_field_id'])) {
      // buildOptions relies on the custom_* type of field names
      $fieldName = sprintf('custom_%d', $field['custom_field_id']);
    }

    // BAO::buildOptions returns a single-dimensional list, we call that first because of the hook contract,
    // @see CRM_Utils_Hook::fieldOptions
    // We then supplement the data with additional properties if requested.
    $bao = CoreUtil::getBAOFromApiName($field['entity']);
    $optionLabels = $bao::buildOptions($fieldName, NULL, $values);

    if (!is_array($optionLabels)) {
      $options = FALSE;
    }
    else {
      $options = self::formatOptionList($field, $optionLabels);
      if (is_array($returnFormat) && $options) {
        self::addOptionProps($options, $field, $bao, $fieldName, $values, $returnFormat);
      }
    }
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

  private static function formatOptionList(array $field, array $optionLabels) {
    $options = \CRM_Utils_Array::makeNonAssociative($optionLabels, 'id', 'label');
    foreach ($options as &$option) {
      switch ($field['data_type']) {
        case 'String':
        case 'Text':
          $option['id'] = (string) $option['id'];
          break;

        case 'Float':
        case 'Money':
          $option['id'] = (float) $option['id'];
          break;

        case 'Integer':
          $option['id'] = (int) $option['id'];
          break;
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
   * @param array $field
   * @param \CRM_Core_DAO $baoName
   * @param string $fieldName
   * @param array $values
   * @param array $returnFormat
   */
  private static function addOptionProps(&$options, $field, $baoName, $fieldName, $values, $returnFormat) {
    // FIXME: For now, call the buildOptions function again and then combine the arrays. Not an ideal approach.
    // TODO: Teach CRM_Core_Pseudoconstant to always load multidimensional option lists so we can get more properties like 'color' and 'icon',
    // however that might require a change to the hook_civicrm_fieldOptions signature so that's a bit tricky.
    if (in_array('name', $returnFormat)) {
      $props['name'] = $baoName::buildOptions($fieldName, 'validate', $values);
    }
    $returnFormat = array_diff($returnFormat, ['id', 'name', 'label']);
    // CRM_Core_Pseudoconstant doesn't know how to fetch extra stuff like icon, description, color, etc., so we have to invent that wheel here...
    if ($returnFormat) {
      $optionIndex = array_flip(array_column($options, 'id'));
      if (!empty($field['custom_field_id'])) {
        $optionGroupId = \CRM_Core_BAO_CustomField::getField($field['custom_field_id'])['option_group_id'];
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
        if ($returnFormat && !empty($pseudoconstant['table'])) {
          $idCol = $pseudoconstant['keyColumn'] ?? 'id';
          $optionIds = \CRM_Core_DAO::escapeStrings(array_column($options, 'id'));
          $sql = "SELECT * FROM {$pseudoconstant['table']} WHERE `$idCol` IN ($optionIds)";
          $query = \CRM_Core_DAO::executeQuery($sql);
          while ($query->fetch()) {
            foreach ($returnFormat as $ret) {
              $retCol = $pseudoconstant[$ret . 'Column'] ?? $ret;
              if (property_exists($query, $retCol)) {
                // Note: our schema is inconsistent about whether `description` fields allow html,
                // but it's usually assumed to be plain text, so we strip_tags() to standardize it.
                $options[$optionIndex[$query->$idCol]][$ret] = isset($query->$retCol) ? strip_tags($query->$retCol) : NULL;
              }
            }
          }
        }
        elseif ($returnFormat && !empty($pseudoconstant['callback'])) {
          $callbackOptions = call_user_func(\Civi\Core\Resolver::singleton()->get($pseudoconstant['callback']), $fieldName, ['values' => $values]);
          foreach ($callbackOptions as $callbackOption) {
            if (is_array($callbackOption) && !empty($callbackOption['id']) && isset($optionIndex[$callbackOption['id']])) {
              $options[$optionIndex[$callbackOption['id']]] += $callbackOption;
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
   */
  public static function setInputTypeAndAttrs(FieldSpec $fieldSpec, $data) {
    $inputType = $data['input_type'] ?? NULL;
    $inputAttrs = $data['input_attrs'] ?? [];

    if (in_array($inputType, ['Select', 'EntityRef'], TRUE) && !empty($data['serialize'])) {
      $inputAttrs['multiple'] = TRUE;
    }
    if ($inputType == 'Date' && !empty($inputAttrs['formatType'])) {
      self::setLegacyDateFormat($inputAttrs);
    }
    if ($inputType == 'Text' && !empty($data['maxlength'])) {
      $inputAttrs['maxlength'] = (int) $data['maxlength'];
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
