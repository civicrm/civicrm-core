<?php

namespace Civi\Api4\Generic;

use Civi\Api4\Generic\Result;

/**
 * Create a new object from supplied values.
 *
 * This function will create 1 new object. It cannot be used to update existing objects. Use the Update or Replace actions for that.
 */
class DAOCreateAction extends AbstractCreateAction {
  use Traits\DAOActionTrait;

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    $this->validateValues();
    $params = $this->values;
    $this->fillDefaults($params);

    $resultArray = $this->writeObjects([$params]);

    $result->exchangeArray($resultArray);
  }

  /**
   * @throws \API_Exception
   */
  protected function validateValues() {
    if (!empty($this->values['id'])) {
      throw new \API_Exception('Cannot pass id to Create action. Use Update action instead.');
    }
    parent::validateValues();
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
