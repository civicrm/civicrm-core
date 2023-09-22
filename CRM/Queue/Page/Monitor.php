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
 * The queue-monitor page provides an interactive, web-based system
 * running the tasks in a queue and monitoring its progression.
 *
 * Do not link or redirect to this page directly -- go through
 * CRM_Queue_Runner::runAllViaWeb().
 *
 * Note: The queue monitor only requires 'access CiviCRM' permission.
 * It fetches all other data via APIv4 (Queue) and respects the
 * permissions thereof.
 */
class CRM_Queue_Page_Monitor extends CRM_Core_Page {

  /**
   *
   * GET Param 'qrid': string, usually the name of the queue
   */
  public function run() {
    $queueName = CRM_Utils_Request::retrieve('name', 'String');
    $queue = \Civi\Api4\Queue::get()->addWhere('name', '=', $queueName)->execute()->first();

    if (!$queue) {
      header("HTTP/1.0 404 Not found or not visible");
      CRM_Utils_System::civiExit();
      return;
    }

    // If this queue was created for a user-job, then use the title.
    $userJob = \Civi\Api4\UserJob::get()->addWhere('queue_id.name', '=', $queueName)->execute()->first();
    $runnerOptions = $userJob['metadata']['runner'] ?? [];

    Civi::service('angularjs.loader')->addModules('crmQueueMonitor');
    CRM_Utils_System::setTitle($runnerOptions['title'] ?? ts('Queue Monitor "%1"', [
      1 => htmlentities($queueName),
    ]));
    $this->assign('queueNameJS', CRM_Utils_JS::encode($queueName));
    parent::run();
  }

}
