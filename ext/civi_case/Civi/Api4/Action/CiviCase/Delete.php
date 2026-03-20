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

namespace Civi\Api4\Action\CiviCase;

/**
 * @inheritDoc
 * @method $this setUseTrash(bool $useTrash) Pass TRUE to move Case to trash instead of deleting
 * @method bool getUseTrash()
 */
class Delete extends \Civi\Api4\Generic\DAODeleteAction {

  /**
   * Should $ENTITY be moved to the trash instead of permanently deleted?
   * @var bool
   */
  protected $useTrash = FALSE;

  /**
   * @param $items
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function deleteObjects($items) {
    foreach ($items as $item) {
      \CRM_Case_BAO_Case::deleteCase($item['id'], $this->useTrash);
      $ids[] = ['id' => $item['id']];
    }
    return $ids;
  }

}
