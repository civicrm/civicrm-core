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
use Civi\Api4\Utils\CoreUtil;
use Civi\Api4\Utils\FormattingUtil;

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
   * @param callable $cacheGetter
   */
  public function __construct($entityName, $actionName, $cacheGetter = NULL) {
    parent::__construct($entityName, $actionName);
    $this->cacheGetter = $cacheGetter;
  }

  /**
   * Toggle the in-memory cache
   *
   * @param bool $useCache
   * @return $this
   */
  public function setUseCache(bool $useCache): CachedDAOGetAction {
    $this->useCache = $useCache;
    return $this;
  }

  /**
   * @return bool|null
   */
  public function getUseCache(): ?bool {
    return $this->useCache;
  }

  /**
   * @param \Civi\Api4\Generic\Result $result
   *
   * Decide whether to use self::getFromCache or DAOGetAction::getObjects
   */
  protected function getObjects(Result $result): void {
    // Attempt to use the cache unless explicitly disabled
    if ($this->useCache !== FALSE) {
      $this->useCache = !$this->needDatabase();
    }
    $this->_debugOutput['useCache'] = $this->useCache;
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

    foreach ($this->select as $fieldName) {
      $fieldName = FormattingUtil::removeSuffix($fieldName);
      if (!isset($cachedFields[$fieldName])) {
        return TRUE;
      }
    }
    foreach ($this->where as $clause) {
      $fieldName = FormattingUtil::removeSuffix($clause[0] ?? '');
      if (!$fieldName || !isset($cachedFields[$fieldName])) {
        return TRUE;
      }
      // ArrayQueryTrait doesn't yet support field-to-field comparisons
      if (!empty($clause[3])) {
        return TRUE;
      }
    }
    foreach ($this->orderBy as $fieldName => $dir) {
      $fieldName = FormattingUtil::removeSuffix($fieldName);
      if (!isset($cachedFields[$fieldName])) {
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
    $idField = CoreUtil::getIdFieldName($this->getEntityName());
    // For parity with the DAO get action, always select ID.
    if ($this->select && !in_array($idField, $this->select)) {
      $this->select[] = $idField;
    }
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
