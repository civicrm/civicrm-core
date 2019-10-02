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

/**
 * Delete one or more items, based on criteria specified in Where param (required).
 */
class DAODeleteAction extends AbstractBatchAction {
  use Traits\DAOActionTrait;

  /**
   * Batch delete function
   */
  public function _run(Result $result) {
    $defaults = $this->getParamDefaults();
    if ($defaults['where'] && !array_diff_key($this->where, $defaults['where'])) {
      throw new \API_Exception('Cannot delete ' . $this->getEntityName() . ' with no "where" parameter specified');
    }

    $items = $this->getObjects();

    if (!$items) {
      throw new \API_Exception('Cannot delete ' . $this->getEntityName() . ', no records found with ' . $this->whereClauseToString());
    }

    $ids = $this->deleteObjects($items);

    $result->exchangeArray($ids);
  }

  /**
   * @param $items
   * @return array
   * @throws \API_Exception
   */
  protected function deleteObjects($items) {
    $ids = [];
    $baoName = $this->getBaoName();

    if ($this->getCheckPermissions()) {
      foreach ($items as $item) {
        $this->checkContactPermissions($baoName, $item);
      }
    }

    if ($this->getEntityName() !== 'EntityTag' && method_exists($baoName, 'del')) {
      foreach ($items as $item) {
        $args = [$item['id']];
        $bao = call_user_func_array([$baoName, 'del'], $args);
        if ($bao !== FALSE) {
          $ids[] = ['id' => $item['id']];
        }
        else {
          throw new \API_Exception("Could not delete {$this->getEntityName()} id {$item['id']}");
        }
      }
    }
    else {
      foreach ($items as $item) {
        $bao = new $baoName();
        $bao->id = $item['id'];
        // delete it
        $action_result = $bao->delete();
        if ($action_result) {
          $ids[] = ['id' => $item['id']];
        }
        else {
          throw new \API_Exception("Could not delete {$this->getEntityName()} id {$item['id']}");
        }
      }
    }
    return $ids;
  }

}
