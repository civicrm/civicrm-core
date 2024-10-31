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

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Api4\Service\Spec\SpecFormatter;
use Civi\Api4\Utils\CoreUtil;
use Civi\Api4\Utils\FormattingUtil;
use Civi\Test\Invasive;
use Civi\Schema\EntityRepository;

/**
 * Legacy adapter for the DAO `fields_callback` quasi-hook
 *
 * @service
 * @internal
 */
class DAOFieldsCallbackAdapterSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $entityName = $spec->getEntity();
    if (CoreUtil::isContact($entityName)) {
      $entityName = 'Contact';
    }
    $entity = EntityRepository::getEntity($entityName);
    if (empty($entity['class']) || empty($entity['fields_callback'])) {
      return;
    }
    $daoName = $entity['class'];
    $unmodifiedFields = Invasive::call([$daoName, 'loadSchemaFields']);
    $modifiedFields = $unmodifiedFields;
    \CRM_Core_DAO_AllCoreTables::invoke($daoName, 'fields_callback', $modifiedFields);
    foreach ($modifiedFields as $fieldName => $fieldDefinition) {
      if (isset($unmodifiedFields[$fieldName]) && $fieldDefinition == $unmodifiedFields[$fieldName]) {
        continue;
      }
      $newFieldSpec = self::legacyArrayToField($fieldDefinition, $spec->getEntity());
      $oldFieldSpec = $spec->getFieldByName($fieldName);
      if (!$oldFieldSpec) {
        $spec->addFieldSpec($newFieldSpec);
      }
      else {
        self::updateFieldSpec($newFieldSpec, $oldFieldSpec);
      }
    }
  }

  private static function updateFieldSpec(FieldSpec $newFieldSpec, FieldSpec $oldFieldSpec) {
    // For the sake of sanity, just set the properties that might reasonably be changed by fields_callback.
    // We're purposely not dealing with 'options' because there's another hook for that.
    $oldFieldSpec->setRequired($newFieldSpec->isRequired());
    $oldFieldSpec->setTitle($newFieldSpec->getTitle());
    $oldFieldSpec->setLabel($newFieldSpec->getLabel());
    $oldFieldSpec->setLocalizable($newFieldSpec->getLocalizable());
    $oldFieldSpec->setDescription($newFieldSpec->getDescription());
    $oldFieldSpec->setUsage($newFieldSpec->getUsage());
    $oldFieldSpec->setInputType($newFieldSpec->getInputType());
    $oldFieldSpec->setInputAttrs($newFieldSpec->getInputAttrs());
    $oldFieldSpec->setDataType($newFieldSpec->getDataType());
    $oldFieldSpec->setDefaultValue($newFieldSpec->getDefaultValue());
    $oldFieldSpec->setNullable($newFieldSpec->getNullable());
    $oldFieldSpec->setReadonly($newFieldSpec->getReadonly());
    $oldFieldSpec->setFkEntity($newFieldSpec->getEntity());
  }

  /**
   * Legacy function to convert array from DAO::fields() to a FieldSpec
   */
  private static function legacyArrayToField(array $data, string $entityName): FieldSpec {
    $dataTypeName = self::getDataType($data);

    $hasDefault = isset($data['default']) && $data['default'] !== '';

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
    $field->setReadonly(!empty($data['readonly']));
    if (isset($data['usage'])) {
      $field->setUsage(array_keys(array_filter($data['usage'])));
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
   * @param \Civi\Api4\Service\Spec\FieldSpec $fieldSpec
   * @param array $data
   * @param string $dataTypeName
   */
  private static function setInputTypeAndAttrs(FieldSpec $fieldSpec, $data, $dataTypeName) {
    $inputType = $data['html']['type'] ?? $data['html_type'] ?? NULL;
    $inputAttrs = $data['html'] ?? [];
    unset($inputAttrs['type']);

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
    if ($inputType == 'Date' && !empty($inputAttrs['format_type'])) {
      SpecFormatter::setLegacyDateFormat($inputAttrs);
    }
    // Number input for numeric fields
    if ($inputType === 'Text' && in_array($dataTypeName, ['Integer', 'Float'], TRUE)) {
      $inputType = 'Number';
      $inputAttrs['step'] = $dataTypeName === 'Integer' ? 1 : .01;
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
    }
    $fieldSpec
      ->setInputType($inputType)
      ->setInputAttrs($inputAttrs);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return TRUE;
  }

}
