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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */


namespace Civi\Api4\Generic;

/**
 * Base class for all `Create` api actions.
 *
 * @method $this setValues(array $values) Set all field values from an array of key => value pairs.
 * @method array getValues() Get field values.
 *
 * @package Civi\Api4\Generic
 */
abstract class AbstractCreateAction extends AbstractAction {

  /**
   * Field values to set for the new $ENTITY.
   *
   * @var array
   */
  protected $values = [];

  /**
   * @param string $fieldName
   * @return mixed|null
   */
  public function getValue(string $fieldName) {
    return isset($this->values[$fieldName]) ? $this->values[$fieldName] : NULL;
  }

  /**
   * Add a field value.
   * @param string $fieldName
   * @param mixed $value
   * @return $this
   */
  public function addValue(string $fieldName, $value) {
    $this->values[$fieldName] = $value;
    return $this;
  }

  /**
   * @throws \API_Exception
   */
  protected function validateValues() {
    $unmatched = $this->checkRequiredFields($this->getValues());
    if ($unmatched) {
      throw new \API_Exception("Mandatory values missing from Api4 {$this->getEntityName()}::{$this->getActionName()}: " . implode(", ", $unmatched), "mandatory_missing", ["fields" => $unmatched]);
    }
  }

}
