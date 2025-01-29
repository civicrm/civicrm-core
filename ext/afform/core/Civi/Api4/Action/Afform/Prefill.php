<?php

namespace Civi\Api4\Action\Afform;

use Civi\Afform\Utils;

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

  protected function loadEntities() {
    if ($this->fillMode === 'form') {
      if (!empty($this->args['sid'])) {
        $afformSubmission = \Civi\Api4\AfformSubmission::get()
          ->addSelect('data')
          ->addWhere('id', '=', $this->args['sid'])
          ->addWhere('afform_name', '=', $this->name)
          ->execute()->first();
      }
      // Restore saved draft
      elseif (\CRM_Core_Session::getLoggedInContactID()) {
        $afformSubmission = \Civi\Api4\AfformSubmission::get(FALSE)
          ->addSelect('data')
          ->addWhere('contact_id', '=', \CRM_Core_Session::getLoggedInContactID())
          ->addWhere('afform_name', '=', $this->name)
          ->addWhere('status_id:name', '=', 'Draft')
          ->execute()->first();
      }
    }
    if (!empty($afformSubmission['data'])) {
      $this->populateSubmissionData($afformSubmission['data']);
    }
    else {
      parent::loadEntities();
    }
  }

  /**
   * Find and replace values for fields that are "DisplayOnly"
   * @param array $afformEntity
   * @param array $valueSets
   * @return void
   */
  private function formatViewValues(array $afformEntity, array &$valueSets): void {
    foreach ($valueSets as $index => $valueSet) {
      $this->replaceViewValues($afformEntity['name'], $afformEntity['type'], $afformEntity['fields'], $valueSets[$index]['fields']);
    }
    foreach ($afformEntity['joins'] ?? [] as $joinEntity => $join) {
      foreach ($valueSets as $index => $valueSet) {
        if (!empty($valueSet['joins'][$joinEntity])) {
          foreach ($valueSet['joins'][$joinEntity] as $joinIndex => $joinValues) {
            $this->replaceViewValues("{$afformEntity['name']}+$joinEntity", $joinEntity, $join['fields'], $valueSets[$index]['joins'][$joinEntity][$joinIndex]);
          }
        }
      }
    }
  }

  private function replaceViewValues(string $entityName, string $entityType, array $fields, ?array &$values): void {
    if (!$fields || !$values) {
      return;
    }
    $originalValues = $values;
    $conditions = [['input_type', '=', 'DisplayOnly']];
    $displayOnlyFields = $this->getDisplayOnlyFields($fields);
    if ($displayOnlyFields) {
      $conditions[] = ['name', 'IN', $displayOnlyFields];
    }
    $getFields = civicrm_api4($entityType, 'getFields', [
      'checkPermissions' => FALSE,
      'loadOptions' => ['id', 'label'],
      'values' => $values,
      'action' => 'create',
      'where' => [
        ['name', 'IN', array_keys($fields)],
        ['OR', $conditions],
      ],
    ]);
    foreach ($getFields as $fieldInfo) {
      $this->replaceViewValue($entityName, $fieldInfo, $values, $originalValues);
    }
  }

  private function replaceViewValue(string $entityName, array $fieldInfo, array &$values, $originalValues): void {
    $fieldName = $fieldInfo['name'];
    if (isset($values[$fieldName]) && !isset($values[$fieldName]['file_name'])) {
      $values[$fieldName] = Utils::formatViewValue($fieldName, $fieldInfo, $originalValues, $entityName, $this->name);
    }
  }

  /**
   * Gets fields that have been explicitly configured "DisplayOnly" on the form
   * @param array $fields
   * @return array
   */
  private function getDisplayOnlyFields(array $fields): array {
    $displayOnly = array_filter($fields, fn($field) => ($field['defn']['input_type'] ?? NULL) === 'DisplayOnly');
    return array_keys($displayOnly);
  }

  /**
   * Load the data from submission table
   */
  protected function populateSubmissionData(array $submissionData) {
    $this->_entityValues = $this->_formDataModel->getEntities();
    foreach ($this->_entityValues as $entity => &$values) {
      foreach ($submissionData as $e => $submission) {
        if ($entity === $e) {
          $values = $submission ?? [];
        }
      }
    }
  }

}
