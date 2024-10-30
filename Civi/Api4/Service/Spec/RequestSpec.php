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

namespace Civi\Api4\Service\Spec;

use Civi\Api4\Utils\CoreUtil;

class RequestSpec implements \Iterator {

  /**
   * @var string
   */
  protected $entity;

  /**
   * @var string
   */
  protected $action;

  /**
   * @var string
   */
  protected $entityTableName;

  /**
   * @return string
   */
  public function getEntityTableName(): ?string {
    return $this->entityTableName;
  }

  /**
   * @var FieldSpec[]
   */
  protected $fields = [];

  /**
   * @var array
   */
  protected $values = [];

  /**
   * @var array
   */
  protected $valuesUsed = [];

  /**
   * @param string $entity
   * @param string $action
   * @param array $values
   */
  public function __construct(string $entity, string $action, array $values = []) {
    $this->entity = $entity;
    $this->action = $action;
    $this->entityTableName = CoreUtil::getTableName($entity);
    $this->values = $values;
  }

  /**
   * Resolve requested value based on available data in $values
   * e.g. if `id` is available then we can use it to look up other values.
   */
  private function resolveValue(string $key) {
    $this->valuesUsed[$key] = TRUE;
    if (!$this->values || array_key_exists($key, $this->values)) {
      return;
    }
    $baoName = CoreUtil::getBAOFromApiName($this->entity);
    $idCol = CoreUtil::getIdFieldName($this->entity);
    // If `id` given, use it to look up value
    if (!array_key_exists($key, $this->values) && !empty($this->values[$idCol]) && array_key_exists($key, $baoName::getSupportedFields())) {
      $this->values[$key] = $baoName::getDbVal($key, $this->values[$idCol], $idCol);
    }
    // If we need a value like `event_id.event_type_id` and we only have `event_id`,
    // use the FK to look up the value from the event.
    if (!array_key_exists($key, $this->values) && str_contains($key, '.')) {
      [$fkFrom, $fkTo] = explode('.', $key);
      $fkField = $baoName::getSupportedFields()[$fkFrom] ?? NULL;
      $fkBAO = $fkField['FKClassName'] ?? NULL;
      if (!empty($this->values[$fkFrom]) && $fkBAO) {
        $this->values[$key] = $fkBAO::getDbVal($fkTo, $this->values[$fkFrom], $fkField['FKColumnName'] ?? 'id');
      }
    }
  }

  /**
   * @param FieldSpec $field
   */
  public function addFieldSpec(FieldSpec $field) {
    if (!$field->getEntity()) {
      $field->setEntity($this->entity);
    }
    if (!$field->getTableName()) {
      $field->setTableName($this->entityTableName);
    }
    $this->fields[] = $field;
  }

  /**
   * @param $name
   *
   * @return FieldSpec|null
   */
  public function getFieldByName($name) {
    foreach ($this->fields as $field) {
      if ($field->getName() === $name) {
        return $field;
      }
    }

    return NULL;
  }

  /**
   * @return array
   *   Gets all the field names currently part of the specification
   */
  public function getFieldNames() {
    return array_map(function(FieldSpec $field) {
      return $field->getName();
    }, $this->fields);
  }

  /**
   * @return array|FieldSpec[]
   */
  public function getRequiredFields() {
    return array_filter($this->fields, function (FieldSpec $field) {
      return $field->isRequired();
    });
  }

  /**
   * @return FieldSpec[]
   */
  public function getFields() {
    return $this->fields;
  }

  /**
   * @return string
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Private because we want everyone downstream to use getValue() and hasValue().
   */
  private function getValues(): array {
    return $this->values;
  }

  /**
   * What values were requested by specProviders
   */
  public function getValuesUsed(): array {
    return $this->valuesUsed;
  }

  /**
   * @param string $key
   * @return mixed
   */
  public function getValue(string $key) {
    $this->resolveValue($key);
    return $this->values[$key] ?? NULL;
  }

  public function hasValue(string $key): bool {
    $this->resolveValue($key);
    return array_key_exists($key, $this->values);
  }

  /**
   * @return string
   */
  public function getAction() {
    return $this->action;
  }

  #[\ReturnTypeWillChange]
  public function rewind() {
    return reset($this->fields);
  }

  #[\ReturnTypeWillChange]
  public function current() {
    return current($this->fields);
  }

  #[\ReturnTypeWillChange]
  public function key() {
    return key($this->fields);
  }

  public function next(): void {
    next($this->fields);
  }

  public function valid(): bool {
    return key($this->fields) !== NULL;
  }

}
