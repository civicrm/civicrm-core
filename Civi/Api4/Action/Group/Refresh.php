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

namespace Civi\Api4\Action\Group;

use Civi\Api4\Generic\Result;

/**
 * @inheritDoc
 */
class Refresh extends \Civi\Api4\Generic\BasicBatchAction {

  protected function processBatch(Result $result, array $items) {
    if ($items) {
      \CRM_Contact_BAO_GroupContactCache::invalidateGroupContactCache(array_column($items, 'id'));
    }
    foreach ($items as $item) {
      $group = new \CRM_Contact_DAO_Group();
      $group->id = $item['id'];
      if (\CRM_Contact_BAO_GroupContactCache::load($group)) {
        $result[] = $item;
      }
    }
  }

}
