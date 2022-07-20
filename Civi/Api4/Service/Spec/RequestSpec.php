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
   * @var FieldSpec[]
   */
  protected $fields = [];

  /**
   * @var array
   */
  protected $values = [];

  /**
   * @param string $entity
   * @param string $action
   * @param array $values
   */
  public function __construct($entity, $action, $values = []) {
    $this->entity = $entity;
    $this->action = $action;
    $this->entityTableName = CoreUtil::getTableName($entity);

    // If `id` given, lookup other values needed to filter custom fields
    $customInfo = \Civi\Api4\Utils\CoreUtil::getCustomGroupExtends($entity);
    $idCol = $customInfo['column'] ?? NULL;
    if ($idCol && !empty($values[$idCol])) {
      $grouping = (array) $customInfo['grouping'];
      $lookupNeeded = array_diff($grouping, array_keys($values));
      if ($lookupNeeded) {
        $record = \civicrm_api4($entity, 'get', [
          'checkPermissions' => FALSE,
          'where' => [[$idCol, '=', $values[$idCol]]],
          'select' => $lookupNeeded,
        ])->first();
        if ($record) {
          $values += $record;
        }
      }
    }
    $this->values = $values;
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
   * @return array|FieldSpec[]
   */
  public function getConditionalRequiredFields() {
    return array_filter($this->fields, function (FieldSpec $field) {
      return $field->getRequiredIf();
    });
  }

  /**
   * @param array $fieldNames
   *   Optional array of fields to return
   * @return FieldSpec[]
   */
  public function getFields($fieldNames = NULL) {
    if (!$fieldNames) {
      return $this->fields;
    }
    // Return all exact matches plus partial matches (to support retrieving fk fields)
    return array_filter($this->fields, function($field) use($fieldNames) {
      foreach ($fieldNames as $fieldName) {
        if (strpos($fieldName, $field->getName()) === 0) {
          return TRUE;
        }
      }
      return FALSE;
    });
  }

  /**
   * @return string
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * @return array
   */
  public function getValues() {
    return $this->values;
  }

  /**
   * @param string $key
   * @return mixed
   */
  public function getValue(string $key) {
    return $this->values[$key] ?? NULL;
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
