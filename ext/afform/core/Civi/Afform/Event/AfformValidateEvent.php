<?php

namespace Civi\Afform\Event;

use Civi\Afform\FormDataModel;
use Civi\Api4\Action\Afform\Submit;

class AfformValidateEvent extends AfformBaseEvent {

  /**
   * @var array
   */
  private $errors = [];

  private $entityFieldDefn = [];

  /**
   * AfformValidateEvent constructor.
   *
   * @param array $afform
   * @param \Civi\Afform\FormDataModel $formDataModel
   * @param \Civi\Api4\Action\Afform\Submit $apiRequest
   */
  public function __construct(array $afform, FormDataModel $formDataModel, Submit $apiRequest) {
    parent::__construct($afform, $formDataModel, $apiRequest);
  }

  /**
   * @param string $errorMsg
   */
  public function setError(string $errorMsg) {
    $this->errors[] = $errorMsg;
  }

  /**
   * @return array
   */
  public function getErrors(): array {
    return $this->errors;
  }

  /**
   * @deprecated
   * @return array
   */
  public function getEntityValues(): array {
    \CRM_Core_Error::deprecatedFunctionWarning("getSubmittedValues");
    return $this->getSubmittedValues();
  }

  /**
   * Get submitted values for all entities on the form
   * @return array
   */
  public function getSubmittedValues() {
    return $this->getApiRequest()->getSubmittedValues();
  }

  public function getEntityFieldDefn(string $entityName, string $fieldName, ?string $joinEntity = NULL): array {
    $cacheKey = "$entityName:$fieldName:" . ($joinEntity ?? '');
    if (array_key_exists($cacheKey, $this->entityFieldDefn)) {
      return $this->entityFieldDefn[$cacheKey];
    }
    $entity = $this->getFormDataModel()->getEntity($entityName);
    if (!$entity || (isset($joinEntity) && !isset($entity['joins'][$joinEntity]))) {
      return [];
    }
    $apiEntity = $joinEntity ?? $entity['type'];
    $baseDefn = $this->getFormDataModel()->getField($apiEntity, $fieldName, 'create') ?: [];

    $fieldDefn = isset($joinEntity) ?
      ($entity['joins'][$joinEntity]['fields'][$fieldName]['defn'] ?? []) :
      ($entity['fields'][$fieldName]['defn'] ?? []);

    // Merge base field defn with what's already in the form markup.
    $fieldDefn += $baseDefn;
    // Need a label for validation messages
    $fieldDefn['label'] = $fieldDefn['label'] ?: $baseDefn['label'];
    $fieldDefn['input_attrs'] = ($fieldDefn['input_attrs'] ?? []) + ($baseDefn['input_attrs'] ?? []);

    $this->entityFieldDefn[$cacheKey] = $fieldDefn;
    return $fieldDefn;
  }

}
