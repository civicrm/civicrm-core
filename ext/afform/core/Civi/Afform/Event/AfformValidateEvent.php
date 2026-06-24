<?php

namespace Civi\Afform\Event;

use Civi\Afform\FormDataModel;
use Civi\Api4\Action\Afform\Submit;
use Psr\Log\LogLevel;

class AfformValidateEvent extends AfformBaseEvent {

  /**
   * @var array
   */
  private array $errors = [];

  private array $entityFieldDefn = [];

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
   * Alias for addError()
   *
   * @param string $errorMsg
   *
   * @return void
   *
   * @deprecated
   */
  public function setError(string $errorMsg): void {
    \CRM_Core_Error::deprecatedFunctionWarning('addError');
    $this->errors[] = $errorMsg;
  }

  /**
   * Replace all existing errors with the specified array
   *
   * @param array $errors
   *
   * @return void
   */
  public function setErrors(array $errors): void {
    $this->errors = $errors;
  }

  /**
   * Add an error
   *
   * @param string $errorMsg
   * @param string $errorCode
   * @param string $level
   *
   * @return void
   */
  public function addError(string $errorMsg, string $errorCode = 'error', string $level = LogLevel::ERROR): void {
    $this->errors[] = ['message' => $errorMsg, 'code' => $errorCode, 'level' => $level];
  }

  /**
   * Get all errors that have been set by other callers
   *
   * @return array
   */
  public function getErrors(): array {
    return $this->errors;
  }

  /**
   * Helper function to check if any errors were defined
   *
   * @return bool
   */
  public function hasErrors(): bool {
    return count($this->errors) > 0;
  }

  /**
   * Helper function for callers that just want to display the error string
   *
   * @param string $separator
   *
   * @return string
   */
  public function getErrorsAsString(string $separator = "\n"): string {
    $errorStrings = array_column($this->errors, 'message');
    return implode($separator, $errorStrings);
  }

  /**
   * Ordered by most serious first. These are the levels that are treated as an "error".
   *
   * @var array
   */
  private array $errorLevels = [
    LogLevel::EMERGENCY,
    LogLevel::ALERT,
    LogLevel::CRITICAL,
    LogLevel::ERROR,
  ];

  /**
   * Helper function to get the maximum severity of error
   *
   * @return string|null
   */
  public function getMaxErrorLevel(): ?string {
    $levels = array_column($this->errors, 'level');
    // Returns the first match (ie. the most severe)
    return current(array_filter(
      $this->errorLevels,
      fn($level) => in_array($level, $levels)
    )) ?: NULL;
  }

  /**
   * We might have defined "errors" which are level info, warning and should be shown to the user but won't "fail" validation.
   * If we return TRUE, assume we have something that needs resolving / is invalid.
   *
   * @return bool
   */
  public function isError(): bool {
    return in_array($this->getMaxErrorLevel(), $this->errorLevels);
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
