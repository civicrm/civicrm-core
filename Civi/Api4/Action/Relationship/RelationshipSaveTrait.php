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

namespace Civi\Api4\Action\Relationship;

use Civi\Api4\Generic\Result;

/**
 * @inheritDoc
 */
trait RelationshipSaveTrait {

  /**
   * @var \Civi\Api4\Generic\Result|null
   */
  private $_result;

  public function _run(Result $result) {
    $this->_result = $result;
    parent::_run($result);
  }

  /**
   * @inheritDoc
   */
  protected function write(array $items) {
    $result = [];
    foreach ($items as $index => $item) {
      try {
        $result[$index] = \CRM_Contact_BAO_Relationship::create($item);
      }
      catch (\CRM_Core_Exception $e) {
        $this->_result->addError($e->getMessage(), code: $e->getErrorCode(), metadata: $e->getErrorData());
        if (count($items) === 1) {
          throw $e;
        }
      }
    }
    return $result;
  }

  public function baoToArray($bao, $input) {
    if (is_array($bao)) {
      $bao['id'] = $bao['duplicate_id'] ?? NULL;
      return $bao;
    }
    return parent::baoToArray($bao, $input);
  }

}
