<?php

namespace Civi\Api4\Service\Spec;

use CRM_Utils_Array as ArrayHelper;
use CRM_Core_DAO_AllCoreTables as AllCoreTables;

class SpecFormatter {

  /**
   * @param FieldSpec[] $fields
   * @param bool $includeFieldOptions
   *
   * @return array
   */
  public static function specToArray($fields, $includeFieldOptions = FALSE) {
    $fieldArray = [];

    foreach ($fields as $field) {
      if ($includeFieldOptions) {
        $field->getOptions();
      }
      $fieldArray[$field->getName()] = $field->toArray();
    }

    return $fieldArray;
  }

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
        $field->setCustomTableName($data['custom_group.table_name']);
        $field->setCustomFieldColumnName($data['column_name']);
      }
      $field->setCustomFieldId(ArrayHelper::value('id', $data));
      $field->setCustomGroupName($data['custom_group.name']);
      $field->setTitle(ArrayHelper::value('label', $data));
      $field->setOptions(self::customFieldHasOptions($data));
      if (\CRM_Core_BAO_CustomField::isSerialized($data)) {
        $field->setSerialize(\CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND);
      }
    }
    else {
      $name = ArrayHelper::value('name', $data);
      $field = new FieldSpec($name, $entity, $dataTypeName);
      $field->setRequired((bool) ArrayHelper::value('required', $data, FALSE));
      $field->setTitle(ArrayHelper::value('title', $data));
      $field->setOptions(!empty($data['pseudoconstant']));
      $field->setSerialize(ArrayHelper::value('serialize', $data));
    }

    $field->setDefaultValue(ArrayHelper::value('default', $data));
    $field->setDescription(ArrayHelper::value('description', $data));
    self::setInputTypeAndAttrs($field, $data, $dataTypeName);

    $fkAPIName = ArrayHelper::value('FKApiName', $data);
    $fkClassName = ArrayHelper::value('FKClassName', $data);
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

    $dataTypeInt = ArrayHelper::value('type', $data);
    $dataTypeName = \CRM_Utils_Type::typeToString($dataTypeInt);

    return $dataTypeName;
  }

  /**
   * @param \Civi\Api4\Service\Spec\FieldSpec $fieldSpec
   * @param array $data
   * @param string $dataTypeName
   */
  public static function setInputTypeAndAttrs(FieldSpec &$fieldSpec, $data, $dataTypeName) {
    $inputType = isset($data['html']['type']) ? $data['html']['type'] : ArrayHelper::value('html_type', $data);
    $inputAttrs = ArrayHelper::value('html', $data, []);
    unset($inputAttrs['type']);

    if (!$inputType) {
      // If no html type is set, guess
      switch ($dataTypeName) {
        case 'Int':
          $inputType = 'Number';
          $inputAttrs['min'] = 0;
          break;

        case 'Text':
          $inputType = ArrayHelper::value('type', $data) === \CRM_Utils_Type::T_LONGTEXT ? 'TextArea' : 'Text';
          break;

        case 'Timestamp':
          $inputType = 'Date';
          $inputAttrs['time'] = TRUE;
          break;

        case 'Date':
          $inputAttrs['time'] = FALSE;
          break;

        case 'Time':
          $inputType = 'Date';
          $inputAttrs['time'] = TRUE;
          $inputAttrs['date'] = FALSE;
          break;

        default:
          $map = [
            'Email' => 'Email',
            'Boolean' => 'Checkbox',
          ];
          $inputType = ArrayHelper::value($dataTypeName, $map, 'Text');
      }
    }
    if (strstr($inputType, 'Multi-Select') || ($inputType == 'Select' && !empty($data['serialize']))) {
      $inputAttrs['multiple'] = TRUE;
      $inputType = 'Select';
    }
    $map = [
      'Select State/Province' => 'Select',
      'Select Country' => 'Select',
      'Select Date' => 'Date',
      'Link' => 'Url',
    ];
    $inputType = ArrayHelper::value($inputType, $map, $inputType);
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
