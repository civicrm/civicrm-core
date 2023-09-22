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
 * A task is an item that can be enqueued and later executed
 */
class CRM_Queue_Task {

  /**
   * Task was performed successfully.
   */
  const TASK_SUCCESS = 1;

  /**
   * Task failed and should not be retried.
   */
  const TASK_FAIL = 2;

  /**
   * @var mixed
   * serializable
   */
  public $callback;

  /**
   * @var array
   * serializable
   */
  public $arguments;

  /**
   * @var string|null
   */
  public $title;

  /**
   * @var array|null
   * serializable
   *
   * If specified, it may include these keys:
   *   - contactId: int|null
   *   - domainId: int|null
   */
  public $runAs;

  /**
   * @param mixed $callback
   *   Serializable, a callable PHP item; must accept at least one argument
   *   (CRM_Queue_TaskContext).
   * @param array $arguments
   *   Serializable, extra arguments to pass to the callback (in order).
   * @param string|null $title
   *   A printable string which describes this task.
   */
  public function __construct($callback, array $arguments = [], ?string $title = NULL) {
    $this->callback = $callback;
    $this->arguments = $arguments;
    $this->title = $title;
  }

  /**
   * Perform the task.
   *
   * @param \CRM_Queue_TaskContext $taskCtx
   * @throws Exception
   * @return bool
   *   TRUE if task completes successfully.
   *   FALSE or exception if task fails.
   */
  public function run($taskCtx) {
    Civi::dispatcher()->dispatch('civi.queue.runTask.start', \Civi\Core\Event\GenericHookEvent::create([
      'task' => $this,
      'taskCtx' => $taskCtx,
    ]));

    $args = $this->arguments;
    array_unshift($args, $taskCtx);

    if ($this->runAs !== NULL) {
      $equals = function($a, $b) {
        return $a === $b || (is_numeric($a) && is_numeric($b) && $a == $b);
      };
      if (array_key_exists('contactId', $this->runAs) && !$equals(CRM_Core_Session::getLoggedInContactID(), $this->runAs['contactId'])) {
        throw new Exception(sprintf('Cannot execute queue task. Unexpected contact "%s" for job "%s"', CRM_Core_Session::getLoggedInContactID(), $this->getSummary()));
      }
      if (array_key_exists('domainId', $this->runAs) && !$equals(CRM_Core_BAO_Domain::getDomain()->id, $this->runAs['domainId'])) {
        throw new Exception(sprintf('Cannot execute queue task. Unexpected domain "%s" for job "%s"', CRM_Core_BAO_Domain::getDomain()->id, $this->getSummary()));
      }
    }

    try {
      if (is_callable($this->callback)) {
        $result = call_user_func_array($this->callback, $args);
        return $result;
      }
      else {
        throw new Exception('Failed to call callback: ' . $this->getSummary());
      }
    }
    finally {
      Civi::dispatcher()->dispatch('civi.queue.runTask.finally', \Civi\Core\Event\GenericHookEvent::create([
        'task' => $this,
        'taskCtx' => $taskCtx,
      ]));
    }
  }

  private function getSummary(): string {
    return json_encode(['title' => $this->title, 'runAs' => $this->runAs, 'callback' => $this->callback], JSON_UNESCAPED_SLASHES);
  }

}
