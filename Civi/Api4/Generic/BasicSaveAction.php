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
 * If saving more than one new $ENTITY with similar values, use the `defaults` parameter.
 *
 * Set `reload` if you need the api to return complete $ENTITY records.
 */
class BasicSaveAction extends AbstractSaveAction {

  /**
   * @var callable
   *
   * Function(array $item, BasicCreateAction $thisAction) => array
   */
  private $setter;

  /**
   * Basic Create constructor.
   *
   * @param string $entityName
   * @param string $actionName
   * @param string $idField
   * @param callable $setter
   *   Function(array $item, BasicCreateAction $thisAction) => array
   */
  public function __construct($entityName, $actionName, $idField = 'id', $setter = NULL) {
    parent::__construct($entityName, $actionName, $idField);
    $this->setter = $setter;
  }

  /**
   * We pass the writeRecord function an array representing one item to write.
   * We expect to get the same format back.
   *
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $this->validateValues();
    foreach ($this->records as $record) {
      $record += $this->defaults;
      $result[] = $this->writeRecord($record);
    }
    if ($this->reload) {
      /** @var BasicGetAction $get */
      $get = \Civi\API\Request::create($this->getEntityName(), 'get', ['version' => 4]);
      $get
        ->setCheckPermissions($this->getCheckPermissions())
        ->addWhere($this->getIdField(), 'IN', (array) $result->column($this->getIdField()));
      $result->exchangeArray((array) $get->execute());
    }
  }

  /**
   * This Basic Save class can be used in one of two ways:
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
