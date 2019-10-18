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
 * Update one or more records with new values.
 *
 * Use the where clause (required) to select them.
 */
class BasicUpdateAction extends AbstractUpdateAction {

  /**
   * @var callable
   *
   * Function(array $item, BasicUpdateAction $thisAction) => array
   */
  private $setter;

  /**
   * BasicUpdateAction constructor.
   *
   * @param string $entityName
   * @param string $actionName
   * @param string|array $select
   *   One or more fields to select from each matching item.
   * @param callable $setter
   *   Function(array $item, BasicUpdateAction $thisAction) => array
   */
  public function __construct($entityName, $actionName, $select = 'id', $setter = NULL) {
    parent::__construct($entityName, $actionName, $select);
    $this->setter = $setter;
  }

  /**
   * We pass the writeRecord function an array representing one item to update.
   * We expect to get the same format back.
   *
   * @param \Civi\Api4\Generic\Result $result
   * @throws \API_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   */
  public function _run(Result $result) {
    foreach ($this->getBatchRecords() as $item) {
      $result[] = $this->writeRecord($this->values + $item);
    }

    if (!$result->count()) {
      throw new \API_Exception('Cannot ' . $this->getActionName() . ' ' . $this->getEntityName() . ', no records found with ' . $this->whereClauseToString());
    }
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
      return call_user_func($this->setter, $item, $this);
    }
    throw new NotImplementedException('Setter function not found for api4 ' . $this->getEntityName() . '::' . $this->getActionName());
  }

}
