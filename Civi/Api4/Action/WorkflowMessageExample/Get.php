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

namespace Civi\Api4\Action\WorkflowMessageExample;

use Civi\Api4\Generic\BasicGetAction;
use Civi\Api4\Generic\Result;
use Civi\WorkflowMessage\Examples;

class Get extends BasicGetAction {

  /**
   * Whether to load '@include()' data.
   *
   * @var bool
   */
  protected $includes = TRUE;

  /**
   * @var \Civi\WorkflowMessage\Examples
   */
  private $_scanner;

  public function _run(Result $result) {
    if ($this->select !== [] && !in_array('name', $this->select)) {
      $this->select[] = 'name';
    }
    parent::_run($result);
  }

  protected function getRecords() {
    $this->_scanner = new Examples();
    $all = $this->_scanner->findAll();
    foreach ($all as &$example) {
      $example['tags'] = !empty($example['tags']) ? \CRM_Utils_Array::implodePadded($example['tags']) : '';
    }
    return $all;
  }

  protected function selectArray($values) {
    $result = parent::selectArray($values);

    $heavyFields = array_intersect(['data', 'asserts'], $this->select ?: []);
    if (!empty($heavyFields)) {
      foreach ($result as &$item) {
        $heavy = $this->_scanner->getHeavy($item['name']);
        $item = array_merge($item, \CRM_Utils_Array::subset($heavy, $heavyFields));
      }
    }

    return $result;
  }

}
