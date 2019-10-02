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

use Civi\API\Exception\NotImplementedException;

/**
 * Basic action for deleting or performing some other task with a set of records.  Ex:
 *
 * $myAction = new BasicBatchAction('Entity', 'action', function($item) {
 *   // Do something with $item
 *   $return $item;
 * });
 *
 * @package Civi\Api4\Generic
 */
class BasicBatchAction extends AbstractBatchAction {

  /**
   * @var callable
   *
   * Function(array $item, BasicBatchAction $thisAction) => array
   */
  private $doer;

  /**
   * BasicBatchAction constructor.
   *
   * @param string $entityName
   * @param string $actionName
   * @param string|array $select
   *   One or more fields to select from each matching item.
   * @param callable $doer
   *   Function(array $item, BasicBatchAction $thisAction) => array
   */
  public function __construct($entityName, $actionName, $select = 'id', $doer = NULL) {
    parent::__construct($entityName, $actionName, $select);
    $this->doer = $doer;
  }

  /**
   * We pass the doTask function an array representing one item to update.
   * We expect to get the same format back.
   *
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    foreach ($this->getBatchRecords() as $item) {
      $result[] = $this->doTask($item);
    }
  }

  /**
   * This Basic Batch class can be used in one of two ways:
   *
   * 1. Use this class directly by passing a callable ($doer) to the constructor.
   * 2. Extend this class and override this function.
   *
   * Either way, this function should return an array with an output record
   * for the item.
   *
   * @param array $item
   * @return array
   * @throws \Civi\API\Exception\NotImplementedException
   */
  protected function doTask($item) {
    if (is_callable($this->doer)) {
      return call_user_func($this->doer, $item, $this);
    }
    throw new NotImplementedException('Doer function not found for api4 ' . $this->getEntityName() . '::' . $this->getActionName());
  }

}
