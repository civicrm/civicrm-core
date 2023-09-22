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
 * Create a new $ENTITY from supplied values.
 *
 * This action will create 1 new $ENTITY.
 * It cannot be used to update existing $ENTITIES; use the `Update` or `Replace` actions for that.
 */
class BasicCreateAction extends AbstractCreateAction {

  /**
   * @var callable
   *   Function(array $item, BasicCreateAction $thisAction): array
   */
  private $setter;

  /**
   * Basic Create constructor.
   *
   * @param string $entityName
   * @param string $actionName
   * @param callable $setter
   */
  public function __construct($entityName, $actionName, $setter = NULL) {
    parent::__construct($entityName, $actionName);
    $this->setter = $setter;
  }

  /**
   * We pass the writeRecord function an array representing one item to write.
   * We expect to get the same format back.
   *
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $this->formatWriteValues($this->values);
    $this->validateValues();
    $result->exchangeArray([$this->writeRecord($this->values)]);
  }

  /**
   * This Basic Create class can be used in one of two ways:
   *
   * 1. Use this class directly by passing a callable ($setter) to the constructor.
   * 2. Extend this class and override this function.
   *
   * Either way, this function should return an array representing the one new object.
   *
   * @param array $item
   * @return array
   * @throws \Civi\API\Exception\NotImplementedException
   */
  protected function writeRecord($item) {
    if (is_callable($this->setter)) {
      $this->addCallbackToDebugOutput($this->setter);
      return call_user_func($this->setter, $item, $this);
    }
    throw new NotImplementedException('Setter function not found for api4 ' . $this->getEntityName() . '::' . $this->getActionName());
  }

}
