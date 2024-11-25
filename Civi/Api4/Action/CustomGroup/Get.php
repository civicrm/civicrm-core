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
use Civi\Api4\Generic\Traits\PseudoconstantOutputTrait;

/**
 * @inheritDoc
 *
 */
class Get extends \Civi\Api4\Generic\DAOGetAction {
  use ArrayQueryActionTrait;
  use PseudoconstantOutputTrait;

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
    $standardFields = \Civi::entity($this->getEntityName())->getFields();
    foreach ($this->select as $field) {
      [$field] = explode(':', $field);
      if (!isset($standardFields[$field])) {
        return TRUE;
      }
    }
    foreach ($this->where as $clause) {
      [$field] = explode(':', $clause[0] ?? '');
      if (!$field || !isset($standardFields[$field])) {
        return TRUE;
      }
    }
    foreach ($this->orderBy as $field => $dir) {
      [$field] = explode(':', $field);
      if (!isset($standardFields[$field])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * This works like BasicGetAction:
   * - provide all the records upfront from the cache
   * - format suffixes using PseudoconstantOutputTrait
   * - filter using ArrayQueryActionTrait
   */
  protected function getFromCache($result): void {
    $values = $this->getCachedRecords();
    $this->formatRawValues($values);
    $this->queryArray($values, $result);
  }

  protected function getCachedRecords() {
    return \CRM_Core_BAO_CustomGroup::getAll();
  }

}
