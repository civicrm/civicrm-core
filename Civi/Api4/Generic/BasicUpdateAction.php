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
 * Update one or more $ENTITY with new values.
 *
 * Use the `where` clause (required) to select them.
 */
class BasicUpdateAction extends AbstractUpdateAction {

  /**
   * @var callable
   *   Function(array $item, BasicUpdateAction $thisAction): array
   */
  private $setter;

  /**
   * BasicUpdateAction constructor.
   *
   * @param string $entityName
   * @param string $actionName
   * @param callable $setter
   */
  public function __construct($entityName, $actionName, $setter = NULL) {
    parent::__construct($entityName, $actionName);
    // Accept setter as 4th param for now, but emit deprecated warning
    $this->setter = func_get_args()[3] ?? NULL;
    if ($this->setter) {
      \CRM_Core_Error::deprecatedWarning(__CLASS__ . ' constructor received $setter as 4th param; it should be the 3rd as the $select param has been removed');
    }
    else {
      $this->setter = $setter;
    }
  }

  /**
   * @param array $items
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function updateRecords(array $items): array {
    return array_map([$this, 'writeRecord'], $items);
  }

  /**
   * This Basic Update class can be used in one of two ways:
   *
   * 1. Use this class directly by passing a callable ($setter) to the constructor.
   * 2. Extend this class and override this function.
   *
   * Either way, this function should return an array representing the one modified object.
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
