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

use Civi\Api4\Event\ValidateValuesEvent;

/**
 * Base class for all `Validate` api actions.
 *
 * @method $this setValues(array $values) Set all field values from an array of key => value pairs.
 * @method array getValues() Get field values.
 *
 * @package Civi\Api4\Generic
 */
class ValidateAction extends AbstractAction {

  use Traits\GetSetValueTrait;

  /**
   * Run the api Action.
   *
   * @param \Civi\Api4\Generic\Result $result
   *
   */
  public function _run(Result $result): void {
    $e = new ValidateValuesEvent($this, [$this->getValues()], new \CRM_Utils_LazyArray(function () {
      return [['old' => NULL, 'new' => $this->getValues()]];
    }));

    $this->onValidateValues($e);
    \Civi::dispatcher()->dispatch('civi.api4.validate', $e);
    if (!empty($e->errors)) {
      $result[] = $e->errors;
    }
  }

  protected function onValidateValues(ValidateValuesEvent $e) {
    foreach ($e->records as $recordKey => $record) {
      $unmatched = $this->checkRequiredFields($record);
      if ($unmatched) {
        $e->addError($recordKey, $unmatched, 'mandatory_missing', "Mandatory values missing from Api4 {$this->getEntityName()}::{$this->getActionName()}: " . implode(", ", $unmatched));
      }
    }
  }

}
