<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
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
