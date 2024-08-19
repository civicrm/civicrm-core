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

namespace Civi\Api4\Action\MessageTemplate;

use Civi\Api4\Generic\Result;
use Civi\Api4\MessageTemplate;

/**
 * Reverts a MessageTemplate subject and message to the contents of the master
 */
class Revert extends \Civi\Api4\Generic\BasicBatchAction {

  protected function processBatch(Result $result, array $items) {
    $revertable = MessageTemplate::get($this->getCheckPermissions())
      ->addSelect('id', 'master_id', 'master_id.msg_subject', 'master_id.msg_html')
      ->addWhere('id', 'IN', array_column($items, 'id'))
      ->execute();
    foreach ($revertable as $item) {
      if (!empty($item['master_id'])) {
        MessageTemplate::update(FALSE)
          ->addWhere('id', '=', $item['id'])
          ->addValue('msg_subject', $item['master_id.msg_subject'])
          ->addValue('msg_html', $item['master_id.msg_html'])
          ->execute();
      }
    }
  }

}
