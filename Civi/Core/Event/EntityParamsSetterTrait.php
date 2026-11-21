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
 * Setters for pre-save events, keeping a custom field's long name,
 * short name (custom_<id>) and $params['custom'] entry in step.
 */
trait EntityParamsSetterTrait {

  /**
   * Sets a parameter value.
   *
   * Custom fields are expected to be in long-name (CustomGroup.CustomField) format.
   *
   * @since 6.15
   */
  public function setValue(string $name, $value): void {
    $this->params[$name] = $value;
    $customField = \CRM_Core_BAO_CustomField::getFieldByName($name);
    if (!$customField) {
      $this->setObjectValue($name, $value);
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
      $this->getEntityId(),
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
   * Sets a non-custom value somewhere other than $params, if the event has one.
   */
  protected function setObjectValue(string $name, $value): void {
  }

  /**
   * Id of the record being saved, or NULL on create.
   */
  abstract protected function getEntityId(): ?int;

}
