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

namespace Civi\Core\Event;

/**
 * Class AuthorizeEvent
 * @package Civi\API\Event
 */
class PreEvent extends GenericHookEvent {

  /**
   * One of: 'create'|'edit'|'delete'
   *
   * @var string
   */
  public $action;

  /**
   * @var string
   */
  public $entity;

  /**
   * @var int|null
   */
  public $id;

  /**
   * @var array
   */
  public $params;

  /**
   * Class constructor.
   *
   * @param string $action
   * @param string $entity
   * @param int|null $id
   * @param array $params
   */
  public function __construct($action, $entity, $id, &$params) {
    $this->action = $action;
    $this->entity = $entity;
    $this->id = $id;
    $this->params = &$params;
  }

  /**
   * @inheritDoc
   */
  public function getHookValues() {
    return [$this->action, $this->entity, $this->id, &$this->params];
  }

  /**
   * Retrieve a parameter value by name, uses long-name (CustomGroup.CustomField) for custom field names.
   *
   * This is more performant than getValues() unless all params are needed.
   *
   * @since 6.15
   */
  public function getValue(string $paramName) {
    return $this->findValue($paramName)[1];
  }

  /**
   * Checks whether a parameter is present, even if its value is NULL.
   *
   * Unlike getValue(), this distinguishes a parameter set to NULL from one that
   * is not present at all.
   *
   * @since 6.17
   */
  public function hasValue(string $paramName): bool {
    return $this->findValue($paramName)[0];
  }

  /**
   * Looks up a parameter in all the places it may be set.
   *
   * @return array [if parameter is present, value or NULL].
   */
  private function findValue(string $paramName): array {
    if (array_key_exists($paramName, $this->params)) {
      return [TRUE, $this->params[$paramName]];
    }

    // If this looks like the name of a custom field, try to find it.
    if (substr_count($paramName, '.') !== 1 || str_starts_with($paramName, '.') || str_ends_with($paramName, '.')) {
      return [FALSE, NULL];
    }
    $customField = \CRM_Core_BAO_CustomField::getFieldByName($paramName);
    if (!$customField) {
      return [FALSE, NULL];
    }
    $customFieldId = $customField['id'];
    $shortName = "custom_$customFieldId";

    // Custom field params might be set in 2 different ways:
    // 1. In the 'custom' array, keyed by custom field id.
    if (!empty($this->params['custom'][$customFieldId])) {
      $customParam = reset($this->params['custom'][$customFieldId]);
      return [TRUE, $this->formatCustomValue($customField, $customParam['value'] ?? NULL)];
    }
    // 2. In the params, possibly suffixed with an id.
    foreach ($this->params as $key => $value) {
      if ($key === $shortName || str_starts_with($key, $shortName . '_')) {
        return [TRUE, $this->formatCustomValue($customField, $value)];
      }
    }
    return [FALSE, NULL];
  }

  /**
   * Sets a parameter value.
   *
   * Custom fields are expected to be in long-name (CustomGroup.CustomField) format.
   *
   * @since 6.15
   */
  public function setValue(string $name, $value): void {
    $this->params[$name] = $value;
    // When setting a custom field param, add it to the 'custom' array.
    $customField = \CRM_Core_BAO_CustomField::getFieldByName($name);
    if (!$customField) {
      return;
    }
    $customFieldId = $customField['id'];
    $shortName = "custom_$customFieldId";
    // Update any existing param with that custom field id
    foreach (array_keys($this->params) as $key) {
      if ($key === $shortName || str_starts_with($key, $shortName . '_')) {
        $this->params[$key] = $value;
        break;
      }
    }
    // Update the 'custom' array with the new param.
    $this->params += ['custom' => []];
    unset($this->params['custom'][$customFieldId]);
    \CRM_Core_BAO_CustomField::formatCustomField(
      $customFieldId,
      $this->params['custom'],
      $value,
      NULL,
      NULL,
      $this->id,
      FALSE,
      FALSE,
      TRUE,
      FALSE
    );
  }

  /**
   * Sets multiple parameter values.
   *
   * Note that this does not overwrite the entire params array, only the provided keys.
   *
   * @since 6.15
   */
  public function mergeValues(array $values): void {
    foreach ($values as $key => $value) {
      $this->setValue($key, $value);
    }
  }

  /**
   * Retrieves all parameter values. Custom fields are in long name format (CustomGroup.CustomField).
   *
   * Note: unless all values are needed, getValue() is more performant.
   *
   * @since 6.15
   */
  public function getValues(): array {
    $values = $this->params;
    unset($values['custom']);

    // Transform any custom values with shortNames (`custom_X`) to long name.
    foreach ($values as $key => $value) {
      if (str_starts_with($key, 'custom_')) {
        $customField = \CRM_Core_BAO_CustomField::getFieldByName($key);
        if ($customField) {
          $values[$customField['custom_group']['name'] . '.' . $customField['name']] = $this->formatCustomValue($customField, $value);
          unset($values[$key]);
        }
      }
    }

    // Collect custom values from $params['custom'] and add to params as their long names.
    foreach ($this->params['custom'] ?? [] as $customFieldId => $customValues) {
      $customField = \CRM_Core_BAO_CustomField::getField($customFieldId);
      if ($customField && $customValues) {
        $customValue = reset($customValues);
        $values[$customField['custom_group']['name'] . '.' . $customField['name']] = $this->formatCustomValue($customField, $customValue['value'] ?? NULL);
      }
    }

    return $values;
  }

  private function formatCustomValue(array $customField, mixed $value) {
    if ($customField['serialize'] && is_string($value)) {
      return \CRM_Core_DAO::unSerializeField($value, $customField['serialize']);
    }
    return $value;
  }

}
