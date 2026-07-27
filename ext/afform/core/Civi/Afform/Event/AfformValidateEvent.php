<?php

namespace Civi\Afform\Event;

use Civi\Afform\FormDataModel;
use Civi\Api4\Action\Afform\Submit;
use Civi\Api4\Generic\Result;

class AfformValidateEvent extends AfformBaseEvent {

  private array $entityFieldDefn = [];

  private Result $result;

  /**
   * AfformValidateEvent constructor.
   *
   * @param array $afform
   * @param \Civi\Afform\FormDataModel $formDataModel
   * @param \Civi\Api4\Action\Afform\Submit $apiRequest
   */
  public function __construct(array $afform, FormDataModel $formDataModel, Submit $apiRequest, Result $result) {
    parent::__construct($afform, $formDataModel, $apiRequest);
    $this->result = $result;
  }

  /**
   * @return \Civi\Api4\Generic\Result
   */
  public function getResult(): Result {
    return $this->result;
  }

  /**
   * Alias for addError()
   *
   * @param string $errorMsg
   *
   * @return void
   *
   * @deprecated
   */
  public function setError(string $errorMsg): void {
    \CRM_Core_Error::deprecatedFunctionWarning('$this->getResult()->addError()');
    $this->getResult()->addError($errorMsg);
  }

  /**
   * Add an error
   *
   * @param string $errorMsg
   *
   * @return void
   *
   * @deprecated
   */
  public function addError(string $errorMsg): void {
    \CRM_Core_Error::deprecatedFunctionWarning('$this->getResult()->addError()');
    $this->result->addError($errorMsg);
  }

  /**
   * Replace all existing errors with the specified array
   *
   * @param array $errors
   *
   * @return void
   *
   * @deprecated
   */
  public function setErrors(array $errors): void {
    \CRM_Core_Error::deprecatedFunctionWarning('$this->getResult()->setErrors()');
    $this->result->setErrors($errors);
  }

  /**
   * Get all errors that have been set by other callers
   *
   * @return array
   *
   * @deprecated
   */
  public function getErrors(): array {
    \CRM_Core_Error::deprecatedFunctionWarning('$this->getResult()->getErrors()');
    return $this->result->getErrors();
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

    $fieldDefn = isset($joinEntity, $entity['type']) ?
      ($entity['joins'][$joinEntity]['fields'][$fieldName]['defn'] ?? []) :
      ($entity['fields'][$fieldName]['defn'] ?? []);

    if (!$entity['type']) {
      return $fieldDefn;
    }

    $apiEntity = $joinEntity ?? $entity['type'];
    $baseDefn = $this->getFormDataModel()->getField($apiEntity, $fieldName, 'create') ?: [];

    // Merge base field defn with what's already in the form markup.
    $fieldDefn += $baseDefn;
    // Need a label for validation messages
    $fieldDefn['label'] = $fieldDefn['label'] ?: $baseDefn['label'];
    $fieldDefn['input_attrs'] = ($fieldDefn['input_attrs'] ?? []) + ($baseDefn['input_attrs'] ?? []);

    $this->entityFieldDefn[$cacheKey] = $fieldDefn;
    return $fieldDefn;
  }

}
