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
use Civi\Api4\Utils\CoreUtil;

/**
 * @inheritDoc
 */
class BasicSaveAction extends AbstractSaveAction {

  /**
   * @var callable
   *   Function(array $item, BasicCreateAction $thisAction): array
   */
  private $setter;

  /**
   * Basic Save constructor.
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
   * We pass the writeRecord function an array representing one item to write.
   * We expect to get the same format back.
   *
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $idField = CoreUtil::getIdFieldName($this->getEntityName());
    foreach ($this->records as &$record) {
      $record += $this->defaults;
      $this->formatWriteValues($record);
      $this->matchExisting($record);
    }
    $this->validateValues();
    foreach ($this->records as $item) {
      $result[] = $this->writeRecord($item);
    }
    if ($this->reload) {
      /** @var BasicGetAction $get */
      $get = \Civi\API\Request::create($this->getEntityName(), 'get', ['version' => 4]);
      $get
        ->setCheckPermissions($this->getCheckPermissions())
        ->addWhere($idField, 'IN', (array) $result->column($idField));
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
