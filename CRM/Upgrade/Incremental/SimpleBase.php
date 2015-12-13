<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Upgrade_Incremental_SimpleBase
 *
 * This is a base class for upgrade tasks. To use it:
 *  - Make a new class which extends `CRM_Upgrade_Incremental_SimpleBase`.
 *  - Override `upgrade()` to define the main upgrade task. (See function docs for tips.)
 *  - Optional: Override `getTitle()` to change the display title.
 *  - Optional: Override `createPreUpgradeMessage` to display advisory message *before* upgrade.
 *
 * @see CRM_Upgrade_Steps_47_000_Example
 */
class CRM_Upgrade_Incremental_SimpleBase extends CRM_Queue_MultiTasker implements CRM_Upgrade_Incremental_Interface {

  /**
   * @return string
   */
  public function getName() {
    return get_class($this);
  }

  /**
   * @param string $startVer
   * @param string $endVer
   * @return null
   */
  public function createPreUpgradeMessage($startVer, $endVer) {
    return NULL;
  }

  /**
   * @param \CRM_Queue_Queue $queue
   * @param string $postUpgradeMessageFile
   * @param string $startVer
   * @param string $endVer
   */
  public function buildQueue(CRM_Queue_Queue $queue, $postUpgradeMessageFile, $startVer, $endVer) {
    $this->stickyData['startVer'] = $startVer;
    $this->stickyData['endVer'] = $endVer;
    $this->stickyData['postUpgradeMessageFile'] = $postUpgradeMessageFile;
    $this->stickyData['queue'] = $queue->getName();
    $this->buildTasks();
  }

  /**
   * Schedule the initial set of tasks.
   *
   * If you want to schedule several tasks from the outset, you may override this.
   */
  protected function buildTasks() {
    $this->addInitialTask($this->getTitle(), 'upgrade');
  }

  /**
   * Perform some upgrade work.
   *
   * In implementing this function, you may find it helpful to:
   *  - Break down the work into small chunks by calling addTask(...).
   *    (Note: If any information is required for the task, pass it through
   *    as an argument. You cannot store persistently at the class level.)
   *  - Display a notification by calling addPostUpgradeMessage(...).
   */
  public function upgrade() {
    // Override me!
  }

  /**
   * @return string
   */
  public function getTitle() {
    return "Upgrade DB: " . get_class($this);
  }

  /**
   * Append another message.
   *
   * @param $message
   * @return $this
   */
  public function addPostUpgradeMessage($message) {
    file_put_contents(
      $this->stickyData['postUpgradeMessageFile'],
      '<br /><br />' . $message,
      FILE_APPEND
    );
    return $this;
  }

}
