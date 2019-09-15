<?php

namespace Civi\Api4\Generic;

/**
 * Create or update one or more records.
 *
 * If creating more than one record with similar values, use the "defaults" param.
 *
 * Set "reload" if you need the api to return complete records.
 */
class DAOSaveAction extends AbstractSaveAction {
  use Traits\DAOActionTrait;

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    foreach ($this->records as &$record) {
      $record += $this->defaults;
      if (empty($record['id'])) {
        $this->fillDefaults($record);
      }
    }
    $this->validateValues();

    $resultArray = $this->writeObjects($this->records);

    $result->exchangeArray($resultArray);
  }

}
