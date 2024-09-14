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

namespace Civi\WorkflowMessage\Traits;

use Civi\Api4\Utils\ReflectionUtils;

/**
 * The ReflectiveWorkflowTrait makes it easier to define
 * workflow-messages using conventional PHP class-modeling. Thus:
 *
 * - As general rule, you define all inputs+outputs as PHP properties.
 * - All key WorkflowMessage methods (getFields, import, export, validate)
 *   are based reflection (inspecting types/annotations of the PHP properties).
 * - Most common tasks can be done with annotations on the properties such
 *   as `@var` and `@scope`.
 * - If you need special behaviors (e.g. outputting derived data to the
 *   Smarty context automatically), then you may override certain methods
 *   (e.g. exportExtra*(), importExtra*()).
 *
 * Here are few important annotations:
 *
 * - `@var` - Specify the PHP type for the data. (Use '|' to list multiple permitted types.)
 *   Ex: `@var string|bool`
 * - `@scope` - Share data with another subsystem, such as the token-processor (`tokenContext`)
 *   or Smarty (`tplParams`).
 *   (By default, the property will have the same name in the other context, but)
 *   Ex: `@scope tplParams`
 *   Ex: `@scope tplParams as contact_id, tokenContext as contactId`
 */
trait ReflectiveWorkflowTrait {

  public function getWorkflowName(): ?string {
    return $this->_extras['envelope']['workflow'] ?? \CRM_Utils_Constant::value(static::CLASS . '::WORKFLOW');
  }

  /**
   * @return string|null
   * @deprecated
   *   It is not recommended that new things depend on the group-name. However, the plumbing still
   *   passes-through the group-name.
   */
  public function getGroupName(): ?string {
    return $this->_extras['envelope']['groupName'] ?? \CRM_Utils_Constant::value(static::CLASS . '::GROUP');
  }

  /**
   * The extras are an open-ended list of fields that will be passed-through to
   * tpl, tokenContext, etc. This is the storage of last-resort for imported
   * values that cannot be stored by other means.
   *
   * @var array
   *   Ex: ['tplParams' => ['assigned_value' => 'A', 'other_value' => 'B']]
   */
  protected $_extras = [];

  /**
   * @inheritDoc
   * @see \Civi\WorkflowMessage\WorkflowMessageInterface::getFields()
   */
  public function getFields(): array {
    // Thread-local cache of class metadata. Class metadata is immutable at runtime, so this is strictly write-once. It should ideally be reused across varied test-functions.
    static $caches = [];
    $cache =& $caches[static::CLASS];
    if ($cache === NULL) {
      $cache = [];
      foreach (ReflectionUtils::findStandardProperties(static::CLASS) as $property) {
        /** @var \ReflectionProperty $property */
        $parsed = ReflectionUtils::getCodeDocs($property, 'Property');
        $field = new \Civi\WorkflowMessage\FieldSpec();
        $field->setName($property->getName())->loadArray($parsed, TRUE);
        $cache[$field->getName()] = $field;
      }
    }
    return $cache;
  }

  protected function getFieldsByFormat($format): ?array {
    switch ($format) {
      case 'modelProps':
        return $this->getFields();

      case 'envelope':
      case 'tplParams':
      case 'tokenContext':
        $matches = [];
        foreach ($this->getFields() as $field) {
          /** @var \Civi\WorkflowMessage\FieldSpec $field */
          if (isset($field->getScope()[$format])) {
            $key = $field->getScope()[$format];
            $matches[$key] = $field;
          }
        }
        return $matches;

      default:
        return NULL;
    }
  }

  /**
   * @inheritDoc
   * @see \Civi\WorkflowMessage\WorkflowMessageInterface::export()
   */
  public function export(?string $format = NULL): ?array {
    switch ($format) {
      case 'modelProps':
      case 'envelope':
      case 'tokenContext':
      case 'tplParams':
        $values = $this->_extras[$format] ?? [];
        $fieldsByFormat = $this->getFieldsByFormat($format);
        foreach ($fieldsByFormat as $key => $field) {
          /** @var \Civi\WorkflowMessage\FieldSpec $field */
          $getter = 'get' . ucfirst($field->getName());
          \CRM_Utils_Array::pathSet($values, explode('.', $key), $this->$getter());
        }

        $methods = ReflectionUtils::findMethodHelpers(static::CLASS, 'exportExtra' . ucfirst($format));
        foreach ($methods as $method) {
          $this->{$method->getName()}(...[&$values]);
        }
        return $values;

      default:
        return NULL;
    }
  }

  /**
   * @inheritDoc
   * @see \Civi\WorkflowMessage\WorkflowMessageInterface::import()
   */
  public function import(string $format, array $values) {
    $MISSING = new \stdClass();

    switch ($format) {
      case 'modelProps':
      case 'envelope':
      case 'tokenContext':
      case 'tplParams':
        $fields = $this->getFieldsByFormat($format);
        foreach ($fields as $key => $field) {
          /** @var \Civi\WorkflowMessage\FieldSpec $field */
          $path = explode('.', $key);
          $value = \CRM_Utils_Array::pathGet($values, $path, $MISSING);
          if ($value !== $MISSING) {
            $setter = 'set' . ucfirst($field->getName());
            $this->$setter($value);
            \CRM_Utils_Array::pathUnset($values, $path, TRUE);
          }
        }

        $methods = ReflectionUtils::findMethodHelpers(static::CLASS, 'importExtra' . ucfirst($format));
        foreach ($methods as $method) {
          $this->{$method->getName()}($values);
        }

        if ($format !== 'modelProps' && !empty($values)) {
          $this->_extras[$format] = array_merge($this->_extras[$format] ?? [], $values);
          $values = [];
        }
        break;

    }

    return $this;
  }

  /**
   * Determine if the data for this workflow message is complete/well-formed.
   *
   * @return array
   *   A list of errors and warnings. Each record defines
   *   - severity: string, 'error' or 'warning'
   *   - fields: string[], list of fields implicated in the error
   *   - name: string, symbolic name of the error/warning
   *   - message: string, printable message describing the problem
   * @see \Civi\WorkflowMessage\WorkflowMessageInterface::validate()
   */
  public function validate(): array {
    $props = $this->export('modelProps');
    $fields = $this->getFields();

    $errors = [];
    foreach ($fields as $fieldName => $fieldSpec) {
      /** @var \Civi\WorkflowMessage\FieldSpec $fieldSpec */
      $fieldValue = $props[$fieldName] ?? NULL;
      if (!$fieldSpec->isRequired() && $fieldValue === NULL) {
        continue;
      }
      if (!\CRM_Utils_Type::validatePhpType($fieldValue, $fieldSpec->getType(), FALSE)) {
        $errors[] = [
          'severity' => 'error',
          'fields' => [$fieldName],
          'name' => 'wrong_type',
          'message' => ts('Field should have type %1.', [1 => implode('|', $fieldSpec->getType())]),
        ];
      }
      if ($fieldSpec->isRequired() && ($fieldValue === NULL || $fieldValue === '')) {
        $errors[] = [
          'severity' => 'error',
          'fields' => [$fieldName],
          'name' => 'required',
          'message' => ts('Missing required field %1.', [1 => $fieldName]),
        ];
      }
    }

    $methods = ReflectionUtils::findMethodHelpers(static::CLASS, 'validateExtra');
    foreach ($methods as $method) {
      $this->{$method->getName()}($errors);
    }

    return $errors;
  }

  // All of the methods below are empty placeholders. They may be overridden to customize behavior.

  /**
   * Get a list of key-value pairs to include the array-coded version of the class.
   *
   * @param array $export
   *   Modifiable list of export-values.
   */
  protected function exportExtraModelProps(array &$export): void {
  }

  /**
   * Get a list of key-value pairs to add to the token-context.
   *
   * @param array $export
   *   Modifiable list of export-values.
   */
  protected function exportExtraTokenContext(array &$export): void {
    $export['controller'] = static::CLASS;
  }

  /**
   * Get a list of key-value pairs to include the Smarty template context.
   *
   * Values returned here will override any defaults.
   *
   * @param array $export
   *   Modifiable list of export-values.
   */
  protected function exportExtraTplParams(array &$export): void {
  }

  /**
   * Get a list of key-value pairs to include the Smarty template context.
   *
   * @param array $export
   *   Modifiable list of export-values.
   */
  protected function exportExtraEnvelope(array &$export): void {
    if ($wfName = \CRM_Utils_Constant::value(static::CLASS . '::WORKFLOW')) {
      $export['workflow'] = $wfName;
    }
    if ($wfGroup = \CRM_Utils_Constant::value(static::CLASS . '::GROUP')) {
      $export['groupName'] = $wfGroup;
    }
  }

  /**
   * Given an import-array (in the class-format), pull out any interesting values.
   *
   * @param array $values
   *   List of import-values. Optionally, unset values that you have handled or blocked.
   */
  protected function importExtraModelProps(array &$values): void {
  }

  /**
   * Given an import-array (in the token-context-format), pull out any interesting values.
   *
   * @param array $values
   *   List of import-values. Optionally, unset values that you have handled or blocked.
   */
  protected function importExtraTokenContext(array &$values): void {
  }

  /**
   * Given an import-array (in the tpl-format), pull out any interesting values.
   *
   * @param array $values
   *   List of import-values. Optionally, unset values that you have handled or blocked.
   */
  protected function importExtraTplParams(array &$values): void {
  }

  /**
   * Given an import-array (in the envelope-format), pull out any interesting values.
   *
   * @param array $values
   *   List of import-values. Optionally, unset values that you have handled or blocked.
   */
  protected function importExtraEnvelope(array &$values): void {
    if ($wfName = \CRM_Utils_Constant::value(static::CLASS . '::WORKFLOW')) {
      if (isset($values['workflow']) && $wfName === $values['workflow']) {
        unset($values['workflow']);
      }
    }
    if ($wfGroup = \CRM_Utils_Constant::value(static::CLASS . '::GROUP')) {
      if (isset($values['groupName']) && $wfGroup === $values['groupName']) {
        unset($values['groupName']);
      }
    }
  }

}
