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

use Civi\API\Exception\NotImplementedException;

/**
 * @inheritDoc
 *
 */
class CachedDAOGetAction extends \Civi\Api4\Generic\DAOGetAction {
  use Traits\ArrayQueryActionTrait;
  use Traits\PseudoconstantOutputTrait;

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
   * @var callable
   *   Function(BasicGetAction $thisAction): array[]
   */
  private $cacheGetter;

  /**
   * Cached DAO Get constructor.
   *
   * Pass a function that returns the cached records
   * The cache should contain all the fields in the
   * EntityRepository schema for this entity. If not override
   * this class and override getCachedFields as well
   *
   * @param string $entityName
   * @param string $actionName
   * @param callable $getter
   */
  public function __construct($entityName, $actionName, $cacheGetter = NULL) {
    parent::__construct($entityName, $actionName);
    $this->cacheGetter = $cacheGetter;
  }

  /**
   * @param \Civi\Api4\Generic\Result $result
   *
   * Decide whether to use self::getFromCache or DAOGetAction::getObjects
   */
  protected function getObjects(Result $result): void {
    if (is_null($this->useCache)) {
      $this->useCache = !$this->needDatabase();
    }
    if ($this->useCache) {
      $this->getFromCache($result);
      return;
    }
    parent::getObjects($result);
  }

  /**
   * Determine whether this query needs to use the
   * database (or can be answered using the cache)
   *
   * @return bool
   */
  protected function needDatabase(): bool {
    if ($this->groupBy || $this->having || $this->join) {
      return TRUE;
    }

    $cachedFields = $this->getCachedFields();

    foreach ($this->select as $field) {
      [$field] = explode(':', $field);
      if (!isset($cachedFields[$field])) {
        return TRUE;
      }
    }
    foreach ($this->where as $clause) {
      [$field] = explode(':', $clause[0] ?? '');
      if (!$field || !isset($cachedFields[$field])) {
        return TRUE;
      }
      // ArrayQueryTrait doesn't yet support field-to-field comparisons
      if (!empty($clause[3])) {
        return TRUE;
      }
    }
    foreach ($this->orderBy as $field => $dir) {
      [$field] = explode(':', $field);
      if (!isset($cachedFields[$field])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Which fields are included in the cache?
   *
   * By default, this is standard fields from the
   * EntityRepository schema - but could be overridden
   * in child classes.
   *
   * @return array with known fields as array *keys*
   */
  protected function getCachedFields(): array {
    return \Civi::entity($this->getEntityName())->getFields();
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

  protected function getCachedRecords(): array {
    if (is_callable($this->cacheGetter)) {
      return call_user_func($this->cacheGetter, $this);
    }
    throw new NotImplementedException('Cache getter function not found for api4 ' . $this->getEntityName() . '::' . $this->getActionName());
  }

}
