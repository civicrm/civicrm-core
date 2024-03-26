<?php

namespace Civi\Api4\Action\Afform;

use Civi\Api4\Utils\CoreUtil;

/**
 * Class Prefill
 * @package Civi\Api4\Action\Afform
 */
class Prefill extends AbstractProcessor {

  /**
   * Name of the field being matched (typically 'id')
   * @var string
   */
  protected $matchField;

  protected function processForm() {
    $entityValues = $this->_entityValues;
    foreach ($entityValues as $afformEntityName => &$valueSets) {
      $afformEntity = $this->_formDataModel->getEntity($afformEntityName);
      if ($this->matchField) {
        $this->handleMatchField($afformEntity['type'], $valueSets);
      }
      $this->formatViewValues($afformEntity, $valueSets);
    }
    return \CRM_Utils_Array::makeNonAssociative($entityValues, 'name', 'values');
  }

  /**
   * Set entity values based on an existing record.
   *
   * This is used for e.g. Event.template_id,
   * based on 'autofill' => 'create' metadata in APIv4 getFields.
   *
   * @param string $entityType
   * @param array $valueSets
   */
  private function handleMatchField(string $entityType, array &$valueSets): void {
    $matchFieldDefn = $this->_formDataModel->getField($entityType, $this->matchField, 'create');
    // @see EventCreationSpecProvider for the `template_id` declaration which includes this 'autofill' = 'create' flag.
    if (($matchFieldDefn['input_attrs']['autofill'] ?? NULL) === 'create') {
      $idField = CoreUtil::getIdFieldName($entityType);
      foreach ($valueSets as &$valueSet) {
        $valueSet['fields'][$this->matchField] = $valueSet['fields'][$idField];
        unset($valueSet['fields'][$idField]);
      }
    }
  }

  private function formatViewValues(array $afformEntity, array &$valueSets): void {
    $originalValues = $valueSets;
    foreach ($this->getDisplayOnlyFields($afformEntity['fields']) as $fieldName) {
      foreach ($valueSets as $index => $valueSet) {
        $this->replaceViewValue($afformEntity['type'], $fieldName, $valueSets[$index]['fields'], $originalValues[$index]['fields']);
      }
    }
    foreach ($afformEntity['joins'] ?? [] as $joinEntity => $join) {
      foreach ($this->getDisplayOnlyFields($join['fields']) as $fieldName) {
        foreach ($valueSets as $index => $valueSet) {
          if (!empty($valueSet['joins'][$joinEntity])) {
            foreach ($valueSet['joins'][$joinEntity] as $joinIndex => $joinValues) {
              $this->replaceViewValue($joinEntity, $fieldName, $valueSets[$index]['joins'][$joinEntity][$joinIndex], $originalValues[$index]['joins'][$joinEntity][$joinIndex]);
            }
          }
        }
      }
    }
  }

  private function replaceViewValue(string $entityType, string $fieldName, array &$values, $originalValues) {
    if (isset($values[$fieldName])) {
      $fieldInfo = $this->_formDataModel->getField($entityType, $fieldName, 'create', $originalValues);
      $values[$fieldName] = \Civi\Afform\Utils::formatViewValue($fieldName, $fieldInfo, $originalValues);
    }
  }

  private function getDisplayOnlyFields(array $fields) {
    $displayOnly = array_filter($fields, fn($field) => ($field['defn']['input_type'] ?? NULL) === 'DisplayOnly');
    return array_keys($displayOnly);
  }

}
