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

namespace Civi\Core\DAO\Event;

use Civi\Core\Event\EntityParamsTrait;

/**
 * Class PreUpdate
 * @package Civi\Core\DAO\Event
 */
class PreUpdate extends \Civi\Core\Event\GenericHookEvent {

  use EntityParamsTrait;

  /**
   * @var \CRM_Core_DAO
   */
  public $object;

  /**
   * @var string
   */
  public $eventID;

  /**
   * @var array
   */
  public $params;

  /**
   * @param \CRM_Core_DAO $object
   * @param array $params
   */
  public function __construct($object, array $params = []) {
    $this->object = $object;
    $this->params = $params;
  }

  /**
   * Sets a parameter value.
   *
   * Custom fields are expected to be in long-name (CustomGroup.CustomField) format.
   */
  public function setValue(string $name, $value): void {
    $this->params[$name] = $value;
    $customField = \CRM_Core_BAO_CustomField::getFieldByName($name);
    if (!$customField) {
      if (property_exists($this->object, $name)) {
        $this->object->$name = $value;
      }
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
      $this->object->id ?? NULL,
      FALSE,
      FALSE,
      TRUE,
      FALSE
    );
  }

  /**
   * Sets multiple parameter values.
   */
  public function mergeValues(array $values): void {
    foreach ($values as $key => $value) {
      $this->setValue($key, $value);
    }
  }

  /**
   * Core fields are read from the DAO, where NULL means "not being saved".
   */
  protected function findObjectValue(string $paramName): array {
    if (!isset($this->object->$paramName)) {
      return [FALSE, NULL];
    }
    // The DAO uses the string 'null' to write a NULL.
    $value = $this->object->$paramName;
    return [TRUE, $value === 'null' ? NULL : $value];
  }

}
