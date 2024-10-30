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
use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Utils\CoreUtil;

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
   *   Function(array $item, BasicBatchAction $thisAction): array
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
   * @param callable $doer
   */
  public function __construct($entityName, $actionName, $doer = NULL) {
    parent::__construct($entityName, $actionName);
    $this->doer = $doer;
    // Accept doer as 4th param for now, but emit deprecated warning
    $this->doer = func_get_args()[3] ?? NULL;
    if ($this->doer) {
      \CRM_Core_Error::deprecatedWarning(__CLASS__ . ' constructor received $doer as 4th param; it should be the 3rd as the $select param has been removed');
    }
    else {
      if ($doer && !is_callable($doer)) {
        \CRM_Core_Error::deprecatedWarning(__CLASS__ . ' constructor received $doer as a non-callable; the 3rd param as the $select param has been removed');
      }
      $this->doer = $doer;
    }
  }

  /**
   * Checks permissions and then delegates to processBatch.
   *
   * Note: Unconditional logic must go here in the run function, as delegated functions may be overridden.
   *
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $items = $this->getBatchRecords();
    foreach ($items as $item) {
      if ($this->checkPermissions && !CoreUtil::checkAccessRecord($this, $item, \CRM_Core_Session::getLoggedInContactID() ?: 0)) {
        throw new UnauthorizedException("ACL check failed");
      }
    }
    $this->processBatch($result, $items);
  }

  /**
   * Calls doTask once per item and stores the result.
   *
   * We pass the doTask function an array representing one item to process.
   * We expect to get the same format back.
   *
   * Note: This function may be overridden by the end api.
   *
   * @param Result $result
   * @param array $items
   * @throws NotImplementedException
   */
  protected function processBatch(Result $result, array $items) {
    foreach ($items as $item) {
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
