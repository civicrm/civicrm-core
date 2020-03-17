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
 * Delete one or more $ENTITIES.
 *
 * $ENTITIES are deleted based on criteria specified in `where` parameter (required).
 */
class DAODeleteAction extends AbstractBatchAction {
  use Traits\DAOActionTrait;

  /**
   * Batch delete function
   */
  public function _run(Result $result) {
    $defaults = $this->getParamDefaults();
    if ($defaults['where'] && $this->where === $defaults['where']) {
      throw new \API_Exception('Cannot delete ' . $this->getEntityName() . ' with no "where" parameter specified');
    }

    $items = $this->getObjects();
    if ($items) {
      $result->exchangeArray($this->deleteObjects($items));
    }
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
