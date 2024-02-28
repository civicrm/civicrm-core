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
   * Convert array from BAO::fields() or CustomGroup::getAll() into a FieldSpec object
   */
  public static function arrayToField(array $data, string $entityName, array $customGroup = NULL): FieldSpec {
    $dataTypeName = self::getDataType($data);

    $hasDefault = isset($data['default']) && $data['default'] !== '';
    // Custom field
    if ($customGroup) {
      $field = new CustomFieldSpec($data['name'], $entityName, $dataTypeName);
      if (!str_starts_with($entityName, 'Custom_')) {
        $field->setName($customGroup['name'] . '.' . $data['name']);
      }
      else {
        // Fields belonging to custom entities are treated as normal; type = Field instead of Custom
        $field->setType('Field');
      }
      $field->setTableName($customGroup['table_name']);
      if ($dataTypeName === 'EntityReference') {
        $field->setFkEntity($data['fk_entity']);
      }
      $field->setColumnName($data['column_name']);
      $field->setNullable(empty($data['is_required']));
      $field->setCustomFieldId($data['id'] ?? NULL);
      $field->setCustomGroupName($customGroup['name']);
      $field->setTitle($data['label']);
      $field->setLabel($customGroup['title'] . ': ' . $data['label']);
      $field->setHelpPre($data['help_pre'] ?? NULL);
      $field->setHelpPost($data['help_post'] ?? NULL);
      if (\CRM_Core_BAO_CustomField::hasOptions($data)) {
        $field->setOptionsCallback([__CLASS__, 'getOptions']);
        $suffixes = ['label'];
        if (!empty($data['option_group_id'])) {
          $suffixes = CoreUtil::getOptionValueFields($data['option_group_id'], 'id');
        }
        $field->setSuffixes($suffixes);
      }
      $field->setReadonly($data['is_view']);
    }
    // Core field
    else {
      $name = $data['name'] ?? NULL;
      $field = new FieldSpec($name, $entityName, $dataTypeName);
      $field->setType('Field');
      $field->setColumnName($name);
      $field->setNullable(empty($data['required']));
      $field->setRequired(!empty($data['required']) && !$hasDefault && $name !== 'id');
      $field->setTitle($data['title'] ?? NULL);
      $field->setLabel($data['html']['label'] ?? NULL);
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
          // Add other columns specified in schema (e.g. 'abbrColumn')
          foreach (array_diff(FormattingUtil::$pseudoConstantSuffixes, $suffixes) as $suffix) {
            if (!empty($data['pseudoconstant'][$suffix . 'Column'])) {
              $suffixes[] = $suffix;
            }
          }
          if (!empty($data['pseudoconstant']['optionGroupName'])) {
            $suffixes = CoreUtil::getOptionValueFields($data['pseudoconstant']['optionGroupName'], 'name');
          }
        }
        $field->setSuffixes($suffixes);
      }
      $field->setReadonly(!empty($data['readonly']));
    }
    if ($hasDefault) {
      $field->setDefaultValue(FormattingUtil::convertDataType($data['default'], $dataTypeName));
    }
    $field->setSerialize($data['serialize'] ?? NULL);
    $field->setDescription($data['description'] ?? NULL);
    $field->setDeprecated($data['deprecated'] ?? FALSE);
    self::setInputTypeAndAttrs($field, $data, $dataTypeName);

    $field->setPermission($data['permission'] ?? NULL);
    $fkAPIName = $data['FKApiName'] ?? NULL;
    $fkClassName = $data['FKClassName'] ?? NULL;
    if ($fkAPIName || $fkClassName) {
      $field->setFkEntity($fkAPIName ?: CoreUtil::getApiNameFromBAO($fkClassName));
    }
    // For pseudo-fk fields like `civicrm_group.parents`
    elseif (($data['html']['type'] ?? NULL) === 'EntityRef' && !empty($data['pseudoconstant']['table'])) {
      $field->setFkEntity(CoreUtil::getApiNameFromTableName($data['pseudoconstant']['table']));
    }
    if (!empty($data['FKColumnName'])) {
      $field->setFkColumn($data['FKColumnName']);
    }

    return $field;
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
    $dataType = $data['data_type'] ?? $data['dataType'] ?? NULL;
    if (isset($dataType)) {
      return !empty($data['time_format']) ? 'Timestamp' : $dataType;
    }

    $dataTypeInt = $data['type'] ?? NULL;
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
      $options = \CRM_Utils_Array::makeNonAssociative($optionLabels, 'id', 'label');
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
   * @param string $dataTypeName
   */
  public static function setInputTypeAndAttrs(FieldSpec $fieldSpec, $data, $dataTypeName) {
    $inputType = $data['html']['type'] ?? $data['html_type'] ?? NULL;
    $inputAttrs = $data['html'] ?? [];
    unset($inputAttrs['type']);
    // Custom field EntityRef or ContactRef filters
    if (is_string($data['filter'] ?? NULL) && strpos($data['filter'], '=')) {
      $filters = explode('&', $data['filter']);
      $inputAttrs['filter'] = $filters;
    }

    $map = [
      'Select Date' => 'Date',
      'Link' => 'Url',
      'Autocomplete-Select' => 'EntityRef',
    ];
    $inputType = $map[$inputType] ?? $inputType;
    if ($dataTypeName === 'ContactReference' || $dataTypeName === 'EntityReference') {
      $inputType = 'EntityRef';
    }
    if (in_array($inputType, ['Select', 'EntityRef'], TRUE) && !empty($data['serialize'])) {
      $inputAttrs['multiple'] = TRUE;
    }
    if ($inputType == 'Date' && !empty($inputAttrs['formatType'])) {
      self::setLegacyDateFormat($inputAttrs);
    }
    // Number input for numeric fields
    if ($inputType === 'Text' && in_array($dataTypeName, ['Integer', 'Float'], TRUE)) {
      $inputType = 'Number';
      // Todo: make 'step' configurable for the custom field
      $inputAttrs['step'] = $dataTypeName === 'Integer' ? 1 : .01;
    }
    // Date/time settings from custom fields
    if ($inputType == 'Date' && is_a($fieldSpec, CustomFieldSpec::class)) {
      $inputAttrs['time'] = empty($data['time_format']) ? FALSE : ($data['time_format'] == 1 ? 12 : 24);
      $inputAttrs['date'] = $data['date_format'];
      $inputAttrs['start_date_years'] = isset($data['start_date_years']) ? (int) $data['start_date_years'] : NULL;
      $inputAttrs['end_date_years'] = isset($data['end_date_years']) ? (int) $data['end_date_years'] : NULL;
    }
    if ($inputType == 'Text' && !empty($data['maxlength'])) {
      $inputAttrs['maxlength'] = (int) $data['maxlength'];
    }
    if ($inputType == 'TextArea') {
      foreach (['rows', 'cols', 'note_rows', 'note_columns'] as $prop) {
        if (!empty($data[$prop])) {
          $key = str_replace('note_', '', $prop);
          // per @colemanw https://github.com/civicrm/civicrm-core/pull/28388#issuecomment-1835717428
          $key = str_replace('columns', 'cols', $key);
          $inputAttrs[$key] = (int) $data[$prop];
        }
      }
    }
    // Ensure all keys use lower_case not camelCase
    foreach ($inputAttrs as $key => $val) {
      if ($key !== strtolower($key)) {
        unset($inputAttrs[$key]);
        $key = \CRM_Utils_String::convertStringToSnakeCase($key);
        $inputAttrs[$key] = $val;
      }
      // Format EntityRef filter property (core and custom fields)
      if ($key === 'filter' && is_array($val)) {
        $filters = [];
        foreach ($val as $filter) {
          [$k, $v] = explode('=', $filter);
          // Explode comma-separated values
          $filters[$k] = strpos($v, ',') ? explode(',', $v) : $v;
        }
        // Legacy APIv3 custom field stuff
        if ($dataTypeName === 'ContactReference') {
          if (!empty($filters['group'])) {
            $filters['groups'] = $filters['group'];
          }
          unset($filters['action'], $filters['group']);
        }
        $inputAttrs['filter'] = $filters;
      }
    }
    // Custom autocompletes
    if (!empty($data['option_group_id']) && $inputType === 'EntityRef') {
      $fieldSpec->setFkEntity('OptionValue');
      $inputAttrs['filter']['option_group_id'] = $data['option_group_id'];
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
