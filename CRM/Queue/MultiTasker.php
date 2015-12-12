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
 * A multitasker is a helper for defining several inter-related tasks
 * (e.g. where one task fires off others tasks).
 *
 * @code
 * class Foo extends CRM_Queue_MultiTasker {
 *   public function foo() {
 *     $this->addTask('Do the bar thing', 'bar', 123);
 *     $this->addTask('Do the bar thing', 'bar', 456);
 *   }
 *   public function bar($x) {
 *     $this->ctx->log->info("Got value $x");
 *   }
 * }
 * @endCode
 */
class CRM_Queue_MultiTasker {

  /**
   * @var CRM_Queue_TaskContext
   */
  protected $ctx;

  /**
   * @var array
   *   Quasi-persistent data that's serialized and restored whenever we
   *   run a task for this class.
   */
  protected $stickyData;

  /**
   * CRM_Upgrade_Incremental_SimpleBase constructor.
   *
   * @param CRM_Queue_TaskContext|NULL $ctx
   * @param array|NULL $stickyData
   */
  public function __construct($ctx = NULL, $stickyData = array()) {
    $this->ctx = $ctx;
    $this->stickyData = $stickyData;
    if (!isset($this->stickyData['class'])) {
      $this->stickyData['class'] = get_class($this);
    }
    if ($ctx && !isset($this->stickyData['queue'])) {
      $this->stickyData['queue'] = $ctx->queue->getName();
    }
  }

  /**
   * Syntactic sugar for adding a task.
   *
   * Task is (a) in this class and (b) has a very high priority.
   *
   * After passing the $funcName, you can also pass parameters that will go to
   * the function. Note that all params must be serializable.
   *
   * Given the very high priority, this is intended for last-minute additions to the start
   * of the queue. It should *not* be used for initializing the queue.
   *
   * @param string $title
   * @param string $funcName
   */
  public function addTask($title, $funcName) {
    $task = call_user_func_array(array($this, 'createTask'), func_get_args());
    $this->getQueue()->createItem($task, array('weight' => -1));
  }

  public function addInitialTask($title, $funcName) {
    $task = call_user_func_array(array($this, 'createTask'), func_get_args());
    $this->getQueue()->createItem($task, array('weight' => 0));
  }

  /**
   * Create a task which points to local function (in this class).
   *
   * After passing the $funcName, you can also pass parameters that will go to
   * the function. Note that all params must be serializable.
   *
   * Note: The task is *not* enqueued.
   *
   * @param $title
   * @param $funcName
   * @return \CRM_Queue_Task
   */
  protected function createTask($title, $funcName) {
    $funcArgs = func_get_args();
    $title = array_shift($funcArgs);
    $funcName = array_shift($funcArgs);
    $task = new CRM_Queue_Task(
      array(get_class($this), 'doTask'),
      array($this->stickyData, $funcName, $funcArgs),
      $title
    );
    return $task;
  }

  /**
   * @return \CRM_Queue_Queue
   */
  protected function getQueue() {
    $queue = CRM_Queue_Service::singleton()->load(array(
      'type' => 'Sql',
      'name' => $this->stickyData['queue'],
    ));
    return $queue;
  }

  public static function doTask(CRM_Queue_TaskContext $ctx, $stickyData, $funcName, $funcArgs) {
    // FIXME
    // $upgrade = new CRM_Upgrade_Form();
    // $upgrade->setSchemaStructureTables($rev);

    /** @var self $obj */
    $className = $stickyData['class'];
    $obj = new $className($ctx, $stickyData);
    call_user_func_array(array($obj, $funcName), $funcArgs);

    return TRUE;
  }

}
