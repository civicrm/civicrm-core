<?php

namespace Civi\Api4\Generic;

/**
 * Base class for all batch actions (Update, Delete, Replace).
 *
 * This differs from the AbstractQuery class in that the "Where" clause is required.
 *
 * @package Civi\Api4\Generic
 */
abstract class AbstractBatchAction extends AbstractQueryAction {

  /**
   * Criteria for selecting items to process.
   *
   * @var array
   * @required
   */
  protected $where = [];

  /**
   * @var array
   */
  private $select;

  /**
   * BatchAction constructor.
   * @param string $entityName
   * @param string $actionName
   * @param string|array $select
   *   One or more fields to load for each item.
   */
  public function __construct($entityName, $actionName, $select = 'id') {
    $this->select = (array) $select;
    parent::__construct($entityName, $actionName);
  }

  /**
   * @return array
   */
  protected function getBatchRecords() {
    $params = [
      'checkPermissions' => $this->checkPermissions,
      'where' => $this->where,
      'orderBy' => $this->orderBy,
      'limit' => $this->limit,
      'offset' => $this->offset,
    ];
    if (empty($this->reload)) {
      $params['select'] = $this->select;
    }

    return (array) civicrm_api4($this->getEntityName(), 'get', $params);
  }

  /**
   * @return array
   */
  protected function getSelect() {
    return $this->select;
  }

}
