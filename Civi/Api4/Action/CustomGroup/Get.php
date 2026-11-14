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

namespace Civi\Api4\Action\CustomGroup;

/**
 * @inheritDoc
 */
class Get extends \Civi\Api4\Generic\CachedDAOGetAction {

  /**
   * @inheritdoc
   */
  protected function getCachedRecords(): array {
    $filters = [];
    $idsToGet = $this->_itemsToGet('id');
    if ($idsToGet) {
      $filters['id'] = $idsToGet;
    }
    return \CRM_Core_BAO_CustomGroup::getAll($filters, $this->getCheckPermissions() ? \CRM_Core_Permission::VIEW : NULL);
  }

}
