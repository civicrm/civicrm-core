<?php

namespace Civi\Api4\Generic;

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

}
