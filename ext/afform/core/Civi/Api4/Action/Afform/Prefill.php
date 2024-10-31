<?php

namespace Civi\Api4\Action\Afform;

/**
 * Class Prefill
 *
 * @package Civi\Api4\Action\Afform
 */
class Prefill extends AbstractProcessor {

  protected function processForm() {
    $entityValues = $this->_entityValues;
    foreach ($entityValues as $afformEntityName => &$valueSets) {
      $afformEntity = $this->_formDataModel->getEntity($afformEntityName);
      $this->formatViewValues($afformEntity, $valueSets);
    }
    return \CRM_Utils_Array::makeNonAssociative($entityValues, 'name', 'values');
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
