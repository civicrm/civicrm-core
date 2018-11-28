<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
namespace Civi\Api4\Action;
use Civi\Api4\Generic\Result;

/**
 * Delete one or more items, based on criteria specified in Where param.
 */
class Delete extends Get {

  /**
   * Criteria for selecting items to delete.
   *
   * @required
   * @var array
   */
  protected $where = [];

  /**
   * Batch delete function
   * @todo much of this should be abstracted out to a generic batch handler
   */
  public function _run(Result $result) {
    $baoName = $this->getBaoName();
    $this->setSelect(['id']);
    $defaults = $this->getParamDefaults();
    if ($defaults['where'] && !array_diff_key($this->where, $defaults['where'])) {
      throw new \API_Exception('Cannot delete with no "where" paramater specified');
    }
    // run the parent action (get) to get the list
    parent::_run($result);
    // Then act on the result
    $ids = [];
    if (method_exists($baoName, 'del')) {
      foreach ($result as $item) {
        $args = [$item['id']];
        $bao = call_user_func_array([$baoName, 'del'], $args);
        if ($bao !== FALSE) {
          $ids[] = $item['id'];
        }
        else {
          throw new \API_Exception("Could not delete {$this->getEntity()} id {$item['id']}");
        }
      }
    }
    else {
      foreach ($result as $item) {
        $bao = new $baoName();
        $bao->id = $item['id'];
        // delete it
        $action_result = $bao->delete();
        if ($action_result) {
          $ids[] = $item['id'];
        }
        else {
          throw new \API_Exception("Could not delete {$this->getEntity()} id {$item['id']}");
        }
      }
    }
    $result->exchangeArray($ids);
    return $result;
  }

  /**
   * @inheritDoc
   */
  public function getParamInfo($param = NULL) {
    $info = parent::getParamInfo($param);
    if (!$param) {
      // Delete doesn't actually let you select fields.
      unset($info['select']);
    }
    return $info;
  }

}
