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
 * The QueueTrait provides helper methods for adding new tasks to a queue.
 */
trait CRM_Extension_Upgrader_QueueTrait {

  abstract public function getExtensionKey();

  /**
   * @var \CRM_Queue_Queue
   */
  protected $queue;

  /**
   * @var \CRM_Queue_TaskContext
   */
  protected $ctx;

  /**
   * Adapter that lets you add normal (non-static) member functions to the queue.
   *
   * While working through a task-queue, the _queueAdapter is called statically. It looks up
   * the appropriate object and invokes the expected method.
   *
   * ```
   * CRM_Extension_Upgrader::_queueAdapter($ctx, 'org.example.myext', 'methodName', 'arg1', 'arg2');
   * ```
   */
  public static function _queueAdapter(CRM_Queue_TaskContext $ctx, string $extensionKey, string $method, ...$args) {
    /** @var static $upgrader */
    $upgrader = \CRM_Extension_System::singleton()->getMapper()->getUpgrader($extensionKey);
    if ($upgrader->ctx !== NULL) {
      throw new \RuntimeException(sprintf("Cannot execute task for %s (%s::%s) - task already active.", $extensionKey, get_class($upgrader), $method));
    }

    $upgrader->ctx = $ctx;
    $upgrader->queue = $ctx->queue;
    try {
      return call_user_func_array([$upgrader, $method], $args);
    } finally {
      $upgrader->ctx = NULL;
    }
  }

  public function addTask(string $title, string $funcName, ...$options) {
    return $this->prependTask($title, $funcName, ...$options);
  }

  /**
   * Enqueue a task based on a method in this class.
   *
   * The task is weighted so that it is processed as part of the currently-pending revision.
   *
   * After passing the $funcName, you can also pass parameters that will go to
   * the function. Note that all params must be serializable.
   */
  public function prependTask(string $title, string $funcName, ...$options) {
    $task = new CRM_Queue_Task(
      [get_class($this), '_queueAdapter'],
      array_merge([$this->getExtensionKey(), $funcName], $options),
      $title
    );
    return $this->queue->createItem($task, ['weight' => -1]);
  }

  /**
   * Enqueue a task based on a method in this class.
   *
   * The task has a default weight.
   *
   * @return mixed
   */
  protected function appendTask(string $title, string $funcName, ...$options) {
    $task = new CRM_Queue_Task(
      [get_class($this), '_queueAdapter'],
      array_merge([$this->getExtensionKey(), $funcName], $options),
      $title
    );
    return $this->queue->createItem($task);
  }

  // ******** Basic getters/setters ********

  /**
   * @return \CRM_Queue_Queue
   */
  public function getQueue(): \CRM_Queue_Queue {
    return $this->queue;
  }

  /**
   * @param \CRM_Queue_Queue $queue
   */
  public function setQueue(\CRM_Queue_Queue $queue): void {
    $this->queue = $queue;
  }

}
