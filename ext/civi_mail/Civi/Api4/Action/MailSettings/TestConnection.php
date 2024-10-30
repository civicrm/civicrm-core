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

namespace Civi\Api4\Action\MailSettings;

use Civi\Api4\Generic\BasicBatchAction;

class TestConnection extends BasicBatchAction {

  /**
   * @return string[]
   */
  protected function getSelect() {
    return ['id', 'name'];
  }

  /**
   * @param array $item
   * @return array
   */
  protected function doTask($item) {
    try {
      $mailStore = \CRM_Mailing_MailStore::getStore($item['name']);
    }
    catch (\Throwable $t) {
      \Civi::log()->warning('MailSettings: Failed to establish test connection', [
        'exception' => $t,
      ]);

      return [
        'title' => ts("Failed to connect"),
        'details' => $t->getMessage() . "\n" . ts('(See log for more details.)'),
        'error' => TRUE,
      ];
    }

    if (empty($mailStore)) {
      return [
        'title' => ts("Failed to connect"),
        'details' => ts('The mail service was not instantiated.'),
        'error' => TRUE,
      ];
    }

    $limitTestCount = 5;
    try {
      $msgs = $mailStore->fetchNext($limitTestCount);
    }
    catch (\Throwable $t) {
      \Civi::log()->warning('MailSettings: Failed to read test message', [
        'exception' => $t,
      ]);
      return [
        'title' => ts('Failed to read test message'),
        'details' => $t->getMessage() . "\n" . ts('(See log for more details.)'),
        'error' => TRUE,
      ];
    }

    if (count($msgs) === 0) {
      return [
        'title' => ts('Connection succeeded.'),
        'details' => ts('No new messages found.'),
        'error' => FALSE,
      ];
    }
    else {
      return [
        'title' => ts('Connection succeeded.'),
        'details' => ts('Found at least %1 new messages.', [
          1 => count($msgs),
        ]),
        'error' => FALSE,
      ];
    }
  }

}
