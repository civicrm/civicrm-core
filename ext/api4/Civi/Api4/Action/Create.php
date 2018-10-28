<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Action;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * Create a new object from supplied values.
 *
 * This function will create 1 new object. It cannot be used to update existing objects. Use the Update or Replace actions for that.
 *
 * @method $this setValues(array $values) Set all field values from an array of key => value pairs.
 * @method $this addValue($field, $value) Set field value.
 */
class Create extends AbstractAction {

  /**
   * Field values to set
   *
   * @var array
   */
  protected $values = [];

  /**
   * @param $key
   *
   * @return mixed|null
   */
  public function getValue($key) {
    return isset($this->values[$key]) ? $this->values[$key] : NULL;
  }

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    $this->validateValues();
    $params = $this->values;
    $this->fillDefaults($params);

    $resultArray = $this->writeObject($params);

    $result->exchangeArray([$resultArray]);
  }

  /**
   * @throws \API_Exception
   */
  protected function validateValues() {
    if (!empty($this->values['id'])) {
      throw new \API_Exception('Cannot pass id to Create action. Use Update action instead.');
    }
    $unmatched = [];
    foreach ($this->getEntityFields() as $fieldName => $fieldInfo) {
      if (!$this->getValue($fieldName) && !empty($fieldInfo['required']) && !isset($fieldInfo['default_value'])) {
        $unmatched[] = $fieldName;
      }
    }
    if ($unmatched) {
      throw new \API_Exception("Mandatory values missing from Api4 {$this->getEntity()}::{$this->getAction()}: '" . implode("', '", $unmatched) . "'", "mandatory_missing", array("fields" => $unmatched));
    }
  }

  /**
   * Fill field defaults which were declared by the api.
   *
   * Note: default values from core are ignored because the BAO or database layer will supply them.
   *
   * @param array $params
   */
  protected function fillDefaults(&$params) {
    $fields = $this->getEntityFields();
    $bao = $this->getBaoName();
    $coreFields = array_column($bao::fields(), NULL, 'name');

    foreach ($fields as $name => $field) {
      // If a default value is set in the api but not in core, the api should supply it.
      if (!isset($params[$name]) && !empty($field['default_value']) && empty($coreFields[$name]['default'])) {
        $params[$name] = $field['default_value'];
      }
    }
  }

}
