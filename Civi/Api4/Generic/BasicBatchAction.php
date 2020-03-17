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
 * $Id$
 *
 */


namespace Civi\Api4\Generic;

use Civi\API\Exception\NotImplementedException;

/**
 * $ACTION one or more $ENTITIES.
 *
 * $ENTITIES are selected based on criteria specified in `where` parameter (required).
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
   * ```php
   * $myAction = new BasicBatchAction($entityName, $actionName, function($item) {
   *   // Do something with $item
   *   $return $item;
   * });
   * ```
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
      $this->addCallbackToDebugOutput($this->doer);
      return call_user_func($this->doer, $item, $this);
    }
    throw new NotImplementedException('Doer function not found for api4 ' . $this->getEntityName() . '::' . $this->getActionName());
  }

}
