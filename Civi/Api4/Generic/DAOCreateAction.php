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

namespace Civi\Api4\Generic;

use Civi\Api4\Utils\CoreUtil;

/**
 * Create a new $ENTITY from supplied values.
 *
 * This action will create 1 new $ENTITY.
 * It cannot be used to update existing $ENTITIES; use the `Update` or `Replace` actions for that.
 */
class DAOCreateAction extends AbstractCreateAction {
  use Traits\DAOActionTrait;

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    $this->formatWriteValues($this->values);
    $this->injectFacadeCondition();
    $this->fillDefaults($this->values);
    $this->validateValues();

    $items = [$this->values];
    $result->exchangeArray($this->writeObjects($items));
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function validateValues() {
    $idField = CoreUtil::getIdFieldName($this->getEntityName());
    if (!empty($this->values[$idField])) {
      throw new \CRM_Core_Exception("Cannot pass $idField to Create action. Use Update action instead.");
    }
    parent::validateValues();
  }

  protected function injectFacadeCondition() {
    $facadeConfig = $this->getFacadeConfig();
    if (!$facadeConfig) {
      return;
    }
    $field = $facadeConfig['field'];
    $value = $facadeConfig['value'];
    if (!isset($this->values[$field])) {
      $this->values[$field] = $value;
    }
    elseif ($this->values[$field] !== $value) {
      // Warn if user tried to set a different value
      \Civi::log()->warning(
        "Facade field '{$field}' must be '{$value}' for {$this->getEntityName()} but got '{$this->values[$field]}'"
      );
      // Force the correct value
      $this->values[$field] = $value;
    }
  }

}
