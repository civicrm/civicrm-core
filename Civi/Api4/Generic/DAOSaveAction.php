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
 * @inheritDoc
 */
class DAOSaveAction extends AbstractSaveAction {
  use Traits\DAOActionTrait;

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    $idField = CoreUtil::getIdFieldName($this->getEntityName());

    // Keep track of the number of records updated vs created
    $matched = 0;

    foreach ($this->records as &$record) {
      $record += $this->defaults;
      $this->formatWriteValues($record);
      $matched += $this->matchExisting($record);
      if (empty($record[$idField])) {
        $this->fillDefaults($record);
      }
    }
    $this->validateValues();

    $resultArray = $this->writeObjects($this->records);

    $result->exchangeArray($resultArray);
    $result->setCountMatched($matched);
  }

}
