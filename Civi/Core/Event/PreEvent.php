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

  use EntityParamsTrait;

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

}
