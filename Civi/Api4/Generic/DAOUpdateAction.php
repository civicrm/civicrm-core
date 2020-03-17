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

/**
 * Update one or more $ENTITY with new values.
 *
 * Use the `where` clause (required) to select them.
 */
class DAOUpdateAction extends AbstractUpdateAction {
  use Traits\DAOActionTrait;

  /**
   * Criteria for selecting items to update.
   *
   * Required if no id is supplied in values.
   *
   * @var array
   */
  protected $where = [];

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    // Add ID from values to WHERE clause and check for mismatch
    if (!empty($this->values['id'])) {
      $wheres = array_column($this->where, NULL, 0);
      if (!isset($wheres['id'])) {
        $this->addWhere('id', '=', $this->values['id']);
      }
      elseif (!($wheres['id'][1] === '=' && $wheres['id'][2] == $this->values['id'])) {
        throw new \Exception("Cannot update the id of an existing " . $this->getEntityName() . '.');
      }
    }

    // Require WHERE if we didn't get an ID from values
    if (!$this->where) {
      throw new \API_Exception('Parameter "where" is required unless an id is supplied in values.');
    }

    // Update a single record by ID unless select requires more than id
    if ($this->getSelect() === ['id'] && count($this->where) === 1 && $this->where[0][0] === 'id' && $this->where[0][1] === '=' && !empty($this->where[0][2])) {
      $this->values['id'] = $this->where[0][2];
      $result->exchangeArray($this->writeObjects([$this->values]));
      return;
    }

    // Batch update 1 or more records based on WHERE clause
    $items = $this->getObjects();
    foreach ($items as &$item) {
      $item = $this->values + $item;
    }

    $result->exchangeArray($this->writeObjects($items));
  }

}
