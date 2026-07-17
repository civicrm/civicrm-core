<?php
namespace Civi\Api4\Action\AfformSubmissionData;

use Civi\Afform\FormDataModel;
use Civi\Api4\AfformSubmission;

class GetFields extends \Civi\Api4\Generic\BasicGetFieldsAction {

  public function getRecords(): array {
    // 1. Get standard submission fields, excluding the raw data blob.
    $fields = (array) AfformSubmission::getFields(FALSE)
      ->setAction('get')
      ->setLoadOptions($this->getLoadOptions())
      ->addWhere('name', '!=', 'data')
      ->execute();

    $afformName = $this->getValue('afform_name');

    if (!is_string($afformName) || $afformName === '') {
      return $fields;
    }

    // 2. Load layout fields for the given afform.
    $afform = \Civi\Api4\Afform::get(FALSE)
      ->addSelect('layout')
      ->addWhere('name', '=', $afformName)
      ->execute()
      ->first();

    if (empty($afform['layout'])) {
      return $fields;
    }

    $formDataModel = new FormDataModel($afform['layout']);

    // 3. Add dynamic submission-data fields from the afform layout.
    foreach ($formDataModel->getEntities() as $entityName => $entity) {
      if ($entityName !== 'extra') {
        // Main entity fields.
        foreach (($entity['fields'] ?? []) as $fieldName => $props) {
          $fieldDef = FormDataModel::getField($entity['type'], $fieldName, 'get');
          if ($fieldDef) {
            $fieldDef['name'] = "$entityName.0.$fieldName";
            $fieldDef['label'] = "$entityName: " . ($fieldDef['label'] ?? $fieldName);
            $fields[] = $fieldDef;
          }
        }

        // ID field for main entity.
        $idFieldDef = FormDataModel::getField($entity['type'], 'id', 'get');
        if ($idFieldDef) {
          $idFieldDef['name'] = "$entityName.0.id";
          $idFieldDef['label'] = "$entityName: ID";
          $fields[] = $idFieldDef;
        }

        // Join entity fields.
        foreach (($entity['joins'] ?? []) as $joinEntity => $join) {
          foreach (($join['fields'] ?? []) as $fieldName => $props) {
            $fieldDef = FormDataModel::getField($joinEntity, $fieldName, 'get');
            if ($fieldDef) {
              $fieldDef['name'] = "$entityName.0.$joinEntity.0.$fieldName";
              $fieldDef['label'] = "$entityName $joinEntity: " . ($fieldDef['label'] ?? $fieldName);
              $fields[] = $fieldDef;
            }
          }

          // ID field for join entity.
          $idFieldDef = FormDataModel::getField($joinEntity, 'id', 'get');
          if ($idFieldDef) {
            $idFieldDef['name'] = "$entityName.0.$joinEntity.0.id";
            $idFieldDef['label'] = "$entityName $joinEntity: ID";
            $fields[] = $idFieldDef;
          }
        }
      }
      else {
        // Extra fields.
        foreach (($entity['fields'] ?? []) as $fieldName => $props) {
          $fields[] = [
            'name' => "extra.$fieldName",
            'label' => "Extra: " . ($props['label'] ?? $fieldName),
            'data_type' => $props['data_type'] ?? 'String',
            'input_type' => $props['input_type'] ?? 'Text',
            'options' => $props['options'] ?? FALSE,
          ];
        }
      }
    }

    return $fields;
  }

}
