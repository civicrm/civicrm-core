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

namespace Civi\Api4\Action\Mailing;

use Civi\Api4\Generic\AbstractAction;

class ProcessQueue extends AbstractAction {

  /**
   * @var bool
   */
  protected $runInNonProductionEnvironment = TRUE;

  /**
   * @var null
   */
  protected $language = NULL;

  public function _run(\Civi\Api4\Generic\Result $result) {
    $queue = \Civi::queue('civicrm.mailing.event.queue', [
      'type' => 'Sql',
      'reset' => FALSE,
      'error' => 'abort',
    ]);
    $runner = new \CRM_Queue_Runner([
      'title' => ts('CiviCRM Mailing Events Queue'),
      'queue' => $queue,
      'errorMode' => \CRM_Queue_Runner::ERROR_CONTINUE,
    ]);
    // stop executing next item after 5 minutes
    $maxRunTime = time() + 600;
    $continue = TRUE;
    while (time() < $maxRunTime && $continue) {
      $result = $runner->runNext();
      if (!$result['is_continue']) {
        // all items in the queue are processed
        $continue = FALSE;
      }
      $result[] = $result;
    }
  }

}
