<?php

namespace Civi\Api4\Service\Spec;

use CRM_Utils_Array as ArrayHelper;
use CRM_Core_DAO_AllCoreTables as TableHelper;

class SpecFormatter {
  /**
   * @param FieldSpec[] $fields
   * @param array $return
   * @param bool $includeFieldOptions
   *
   * @return array
   */
  public static function specToArray($fields, $return = [], $includeFieldOptions = FALSE) {
    $fieldArray = [];

    foreach ($fields as $field) {
      if ($includeFieldOptions || in_array('options', $return)) {
        $field->getOptions();
      }
      $fieldArray[$field->getName()] = $field->toArray($return);
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
        $field->setName($data['custom_group']['name'] . '.' . $data['name']);
      }
      else {
        $field->setCustomTableName($data['custom_group']['table_name']);
        $field->setCustomFieldColumnName($data['column_name']);
      }
      $field->setCustomFieldId(ArrayHelper::value('id', $data));
      $field->setCustomGroupName($data['custom_group']['name']);
      $field->setRequired((bool) ArrayHelper::value('is_required', $data, FALSE));
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

    $fkAPIName = ArrayHelper::value('FKApiName', $data);
    $fkClassName = ArrayHelper::value('FKClassName', $data);
    if ($fkAPIName || $fkClassName) {
      $field->setFkEntity($fkAPIName ?: TableHelper::getBriefName($fkClassName));
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
    if (strpos($field['html_type'], 'Select')) {
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
      return $data['data_type'];
    }

    $dataTypeInt = ArrayHelper::value('type', $data);
    $dataTypeName = \CRM_Utils_Type::typeToString($dataTypeInt);

    return $dataTypeName;
  }

}
