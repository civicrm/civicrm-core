<?php

namespace Civi\Api4\Generic;

use Civi\Api4\Generic\Result;

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
      throw new \API_Exception('Cannot delete with no "where" parameter specified');
    }

    $items = $this->getObjects();

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
          $ids[] = $item['id'];
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
          $ids[] = $item['id'];
        }
        else {
          throw new \API_Exception("Could not delete {$this->getEntityName()} id {$item['id']}");
        }
      }
    }
    return $ids;
  }

}
