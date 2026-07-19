<?php
namespace Civi\Api4\Action\AfformSubmissionData;

use Civi\Afform\FormDataModel;

/**
 * Shared helpers for AfformSubmissionData actions.
 */
trait AfformSubmissionDataTrait {

  /**
   * Pre-loads entity and join specifications in bulk.
   *
   * @param \Civi\Afform\FormDataModel|null $formDataModel
   * @param array|bool $loadOptions
   * @return array
   */
  protected function loadEntitySpecs(?FormDataModel $formDataModel, $loadOptions): array {
    $entitySpecs = [];
    if (!$formDataModel) {
      return $entitySpecs;
    }
    foreach ($formDataModel->getEntities() as $entityName => $entity) {
      if ($entityName !== 'extra') {
        $entityType = $entity['type'];
        if (!isset($entitySpecs[$entityType])) {
          $entitySpecs[$entityType] = (array) civicrm_api4($entityType, 'getFields', [
            'action' => 'get',
            'loadOptions' => $loadOptions,
            'checkPermissions' => FALSE,
          ])->indexBy('name');
        }
        foreach (($entity['joins'] ?? []) as $joinEntity => $join) {
          if (!isset($entitySpecs[$joinEntity])) {
            $entitySpecs[$joinEntity] = (array) civicrm_api4($joinEntity, 'getFields', [
              'action' => 'get',
              'loadOptions' => $loadOptions,
              'checkPermissions' => FALSE,
            ])->indexBy('name');
          }
        }
      }
    }
    return $entitySpecs;
  }

  /**
   * Retrieves a FormDataModel instance for a given afform.
   *
   * @param string|null $afformName
   * @return \Civi\Afform\FormDataModel|null
   */
  protected function getFormDataModel(?string $afformName): ?FormDataModel {
    if (empty($afformName)) {
      return NULL;
    }
    $afform = \Civi\Api4\Afform::get(FALSE)
      ->addSelect('layout')
      ->addWhere('name', '=', $afformName)
      ->execute()
      ->first();

    return !empty($afform['layout']) ? new FormDataModel($afform['layout']) : NULL;
  }

  /**
   * Returns a list of fields declared in the afform layout.
   *
   * @param \Civi\Afform\FormDataModel $formDataModel
   * @return array
   */
  protected function getLayoutFields(FormDataModel $formDataModel): array {
    $fields = [];
    foreach ($formDataModel->getEntities() as $entityName => $entity) {
      if ($entityName !== 'extra') {
        foreach (($entity['fields'] ?? []) as $fieldName => $props) {
          $fields[] = [
            'name' => "$entityName.0.$fieldName",
            'entity' => $entity['type'],
            'field' => $fieldName,
            'label_prefix' => "$entityName: ",
          ];
        }
        $fields[] = [
          'name' => "$entityName.0.id",
          'entity' => $entity['type'],
          'field' => 'id',
          'label_prefix' => "$entityName: ",
        ];
        foreach (($entity['joins'] ?? []) as $joinEntity => $join) {
          foreach (($join['fields'] ?? []) as $fieldName => $props) {
            $fields[] = [
              'name' => "$entityName.0.$joinEntity.0.$fieldName",
              'entity' => $joinEntity,
              'field' => $fieldName,
              'label_prefix' => "$entityName $joinEntity: ",
            ];
          }
          $fields[] = [
            'name' => "$entityName.0.$joinEntity.0.id",
            'entity' => $joinEntity,
            'field' => 'id',
            'label_prefix' => "$entityName $joinEntity: ",
          ];
        }
      }
      else {
        foreach (($entity['fields'] ?? []) as $fieldName => $props) {
          $fields[] = [
            'name' => "extra.$fieldName",
            'entity' => 'extra',
            'field' => $fieldName,
            'label_prefix' => "Extra: ",
            'props' => $props,
          ];
        }
      }
    }
    return $fields;
  }

  /**
   * Options callback: returns the names of all afforms that create submissions.
   *
   * @return string[]
   */
  protected function getAfformNameOptions(): array {
    return \Civi\Api4\Afform::get(FALSE)
      ->addSelect('name')
      ->addWhere('type', '=', 'form')
      ->execute()
      ->column('name');
  }

}
