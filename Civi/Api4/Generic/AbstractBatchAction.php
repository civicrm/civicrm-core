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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


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
   * Criteria for selecting $ENTITIES to process.
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
   * Get a list of records for this batch.
   *
   * @return array
   */
  protected function getBatchRecords() {
    return (array) $this->getBatchAction()->execute();
  }

  /**
   * Get a query which resolves the list of records for this batch.
   *
   * This is similar to `getBatchRecords()`, but you may further refine the
   * API call (e.g. selecting different fields or data-pages) before executing.
   *
   * @return \Civi\Api4\Generic\AbstractGetAction
   */
  protected function getBatchAction() {
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
    return \Civi\API\Request::create($this->getEntityName(), 'get', ['version' => 4] + $params);
  }

  /**
   * @return array
   */
  protected function getSelect() {
    return $this->select;
  }

}
