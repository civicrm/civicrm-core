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

namespace Civi\Api4\Import;

/**
 * Code shared by Import Save/Update actions.
 */
trait ImportSaveTrait {

  /**
   * @inheritDoc
   */
  protected function write(array $items) {
    $userJobID = str_replace('Import_', '', $this->_entityName);
    foreach ($items as &$item) {
      $item['_user_job_id'] = (int) $userJobID;
    }
    return parent::write($items);
  }

  /**
   * Override parent method which expects self::fields() to actually return something
   * @param \CRM_Core_DAO $bao
   * @param array $input
   * @return array
   */
  public function baoToArray($bao, $input): array {
    return $bao->toArray();
  }

}
