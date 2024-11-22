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

namespace Civi\Api4\Action\CustomGroup;

use Civi\Api4\Generic\Result;
use Civi\Api4\Generic\Traits\ArrayQueryActionTrait;

/**
 * @inheritDoc
 *
 */
class Get extends \Civi\Api4\Generic\DAOGetAction {
  use ArrayQueryActionTrait;

  /**
   * @var bool
   *
   * Should we use the in-memory cache to answer
   * this request?
   *
   * If unset, will be determined automatically based
   * on the complexity of the request
   */
  protected ?bool $useCache = NULL;

  /**
   * @param \Civi\Api4\Generic\Result $result
   *
   * Most of the time uses the standard DAOGetAction implementation
   *
   * However - for simple queries we can use the in-memory cache to
   * avoid hitting the database
   */
  protected function getObjects(Result $result) {
    if (is_null($this->useCache)) {
      $this->useCache = !$this->needDb();
    }
    if ($this->useCache) {
      $this->getFromCache($result);
      return;
    }
    parent::getObjects($result);
  }

  protected function needDb() {
    if ($this->groupBy || $this->having || $this->join) {
      return TRUE;
    }
    $standardFields = \Civi::entity('CustomGroup')->getFields();
    foreach ($this->select as $field) {
      if (!isset($standardFields[$field])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * This acts like a BasicBatchAction - ie provide all the records
   * upfront, and then filter using queryArray
   *
   * NOTE: we skip formatValues because any pseudoconstant fields
   * will trigger 'needDb'
   */
  protected function getFromCache($result) {
    $values = $this->getRecords();
    $this->queryArray($values, $result);
  }

  protected function getRecords() {
    return \CRM_Core_BAO_CustomGroup::getAll();
  }

}
