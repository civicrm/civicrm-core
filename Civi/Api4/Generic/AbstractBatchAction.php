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
   * Get a list of records for this batch.
   *
   * @return array
   */
  protected function getBatchRecords() {
    return (array) $this->getBatchAction()->execute();
  }

  /**
   * Get an API action object which resolves the list of records for this batch.
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
    // If reload not needed, only select necessary fields
    if (empty($this->reload)) {
      $params['select'] = $this->getSelect();
    }
    // If reload needed, select necessary + requested fields
    else {
      $reload = is_array($this->reload) ? $this->reload : ['*'];
      $params['select'] = array_unique(array_merge($this->getSelect(), $reload));
    }
    return \Civi\API\Request::create($this->getEntityName(), 'get', ['version' => 4] + $params);
  }

  /**
   * Determines what fields will be returned by getBatchRecords
   *
   * Defaults to an entity's primary key(s), typically ['id']
   *
   * @return string[]
   */
  protected function getSelect() {
    return CoreUtil::getInfoItem($this->getEntityName(), 'primary_key');
  }

}
