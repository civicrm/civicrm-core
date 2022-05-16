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

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Utils\CoreUtil;
use Civi\Api4\Utils\ReflectionUtils;

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

    $items = $this->getBatchRecords();

    if ($this->getCheckPermissions()) {
      foreach ($items as $key => $item) {
        if (!CoreUtil::checkAccessRecord($this, $item, \CRM_Core_Session::getLoggedInContactID() ?: 0)) {
          throw new UnauthorizedException("ACL check failed");
        }
        $items[$key]['check_permissions'] = TRUE;
      }
    }
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

    // Use BAO::del() method if it is not deprecated
    if (method_exists($baoName, 'del') && !ReflectionUtils::isMethodDeprecated($baoName, 'del')) {
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
      foreach ($baoName::deleteRecords($items) as $instance) {
        $ids[] = ['id' => $instance->id];
      }
    }
    return $ids;
  }

}
