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

/**
 * @inheritDoc
 */
class DAOSaveAction extends AbstractSaveAction {
  use Traits\DAOActionTrait;

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    foreach ($this->records as &$record) {
      $record += $this->defaults;
      $this->formatWriteValues($record);
      $this->matchExisting($record);
      if (empty($record['id'])) {
        $this->fillDefaults($record);
      }
    }
    $this->validateValues();

    $resultArray = $this->writeObjects($this->records);

    $result->exchangeArray($resultArray);
  }

}
