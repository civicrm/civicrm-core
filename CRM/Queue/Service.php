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
 * The queue service provides an interface for creating or locating
 * queues. Note that this approach hides the details of data-storage:
 * different queue-providers may store the queue content in different
 * ways (in memory, in SQL, or in an external service).
 *
 * ```
 * $queue = CRM_Queue_Service::singleton()->create(array(
 *   'type' => 'interactive',
 *   'name' => 'upgrade-tasks',
 * ));
 * $queue->createItem($myData);
 *
 * // Some time later...
 * $item = $queue->claimItem();
 * if ($item) {
 *   if (my_process($item->data)) {
 *     $queue->deleteItem($item);
 *   } else {
 *     $queue->releaseItem($item);
 *   }
 * }
 * ```
 */
class CRM_Queue_Service {

  protected static $_singleton;

  /**
   * List of fields which are shared by `$queueSpec` and `civicrm_queue`.
   *
   * @var string[]
   * @readonly
   */
  private static $commonFields = ['name', 'type', 'runner', 'status', 'error', 'batch_limit', 'lease_time', 'retry_limit', 'retry_interval'];

  /**
   * FIXME: Singleton pattern should be removed when dependency-injection
   * becomes available.
   *
   * @param bool $forceNew
   *   TRUE if a new instance must be created.
   *
   * @return \CRM_Queue_Service
   */
  public static function &singleton(bool $forceNew = FALSE) {
    if ($forceNew || !isset(\Civi::$statics[__CLASS__]['singleton'])) {
      \Civi::$statics[__CLASS__]['singleton'] = new CRM_Queue_Service();
    }
    return \Civi::$statics[__CLASS__]['singleton'];
  }

  /**
   * Queues.
   *
   * Format is (string $queueName => CRM_Queue_Queue).
   *
   * @var array
   */
  public $queues;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->queues = [];
  }

  /**
   * Create a queue. If one already exists, then it will be reused.
   *
   * @param array $queueSpec
   *   Array with keys:
   *   - type: string, required, e.g. `Sql`, `SqlParallel`, `Memory`
   *   - name: string, required, e.g. "upgrade-tasks"
   *   - reset: bool, optional; if a queue is found, then it should be
   *     flushed; default to TRUE
   *   - (additional keys depending on the queue provider).
   *   - is_persistent: bool, optional; if true, then this queue is loaded from `civicrm_queue` list
   *   - runner: string, optional; if given, then items in this queue can run
   *     automatically via `hook_civicrm_queueRun_{$runner}`
   *   - status: string, required for runnable-queues; specify whether the runner is currently active
   *     ex: 'active', 'draft', 'completed'
   *   - error: string, required for runnable-queues; specify what to do with unhandled errors
   *     ex: "drop" or "abort"
   *   - batch_limit: int, Maximum number of items in a batch.
   *   - lease_time: int, When claiming an item (or batch of items) for work, how long should the item(s) be reserved. (Seconds)
   *   - retry_limit: int, Number of permitted retries. Set to zero (0) to disable.
   *   - retry_interval: int, Number of seconds to wait before retrying a failed execution.
   * @return CRM_Queue_Queue
   */
  public function create($queueSpec) {
    if (is_object($this->queues[$queueSpec['name']] ?? NULL) && empty($queueSpec['reset'])) {
      return $this->queues[$queueSpec['name']];
    }

    if (!empty($queueSpec['is_persistent'])) {
      $queueSpec = $this->findCreateQueueSpec($queueSpec);
    }
    $this->validateQueueSpec($queueSpec);
    $queue = $this->instantiateQueueObject($queueSpec);
    $exists = $queue->existsQueue();
    if (!$exists) {
      $queue->createQueue();
    }
    elseif (@$queueSpec['reset']) {
      $queue->deleteQueue();
      $queue->createQueue();
    }
    else {
      $queue->loadQueue();
    }
    $this->queues[$queueSpec['name']] = $queue;
    return $queue;
  }

  /**
   * Find/create the queue-spec. Specifically:
   *
   * - If there is a stored queue, use its spec.
   * - If there is no stored queue, and if we have enough information, then create queue.
   *
   * @param array $queueSpec
   * @return array
   *   Updated queueSpec.
   * @throws \CRM_Core_Exception
   */
  protected function findCreateQueueSpec(array $queueSpec): array {
    $loaded = $this->findQueueSpec($queueSpec);
    if ($loaded !== NULL) {
      return $loaded;
    }

    if (isset($queueSpec['template'])) {
      $base = $this->findQueueSpec(['name' => $queueSpec['template']]);
      $reset = ['is_template' => 0];
      $queueSpec = array_merge($base, $reset, $queueSpec);
    }

    $this->validateQueueSpec($queueSpec);

    $dao = new CRM_Queue_DAO_Queue();
    $dao->name = $queueSpec['name'];
    $dao->copyValues($queueSpec);
    $dao->insert();

    return $this->findQueueSpec($queueSpec);
  }

  protected function findQueueSpec(array $queueSpec): ?array {
    $dao = new CRM_Queue_DAO_Queue();
    $dao->name = $queueSpec['name'];
    if ($dao->find(TRUE)) {
      return array_merge($queueSpec, CRM_Utils_Array::subset($dao->toArray(), static::$commonFields));
    }
    else {
      return NULL;
    }
  }

  /**
   * Look up an existing queue.
   *
   * @param array $queueSpec
   *   Array with keys:
   *   - type: string, required, e.g. `Sql`, `SqlParallel`, `Memory`
   *   - name: string, required, e.g. "upgrade-tasks"
   *   - (additional keys depending on the queue provider).
   *   - is_persistent: bool, optional; if true, then this queue is loaded from `civicrm_queue` list
   *
   * @return CRM_Queue_Queue
   */
  public function load($queueSpec) {
    if (is_object($this->queues[$queueSpec['name']] ?? NULL)) {
      return $this->queues[$queueSpec['name']];
    }
    if (!empty($queueSpec['is_persistent'])) {
      $queueSpec = $this->findCreateQueueSpec($queueSpec);
    }
    $queue = $this->instantiateQueueObject($queueSpec);
    $queue->loadQueue();
    $this->queues[$queueSpec['name']] = $queue;
    return $queue;
  }

  /**
   * Convert a queue "type" name to a class name.
   *
   * @param string $type
   *   - type: string, required, e.g. `Sql`, `SqlParallel`, `Memory`
   * @return string
   *   Class-name
   */
  protected function getQueueClass($type) {
    $type = preg_replace('/[^a-zA-Z0-9]/', '', $type);
    $className = 'CRM_Queue_Queue_' . $type;
    // FIXME: when used with class-autoloader, this may be unnecessary
    if (!class_exists($className)) {
      $classFile = 'CRM/Queue/Queue/' . $type . '.php';
      require_once $classFile;
    }
    return $className;
  }

  /**
   * @param array $queueSpec
   *   See create().
   *
   * @return CRM_Queue_Queue
   */
  protected function instantiateQueueObject($queueSpec) {
    // note: you should probably never do anything else here
    $class = new ReflectionClass($this->getQueueClass($queueSpec['type']));
    return $class->newInstance($queueSpec);
  }

  /**
   * Assert that the queueSpec is well-formed.
   *
   * @param array $queueSpec
   * @throws \CRM_Core_Exception
   */
  public function validateQueueSpec(array $queueSpec): void {
    $throw = function(string $message, ...$args) use ($queueSpec) {
      $prefix = sprintf('Failed to create queue "%s". ', $queueSpec['name']);
      throw new CRM_Core_Exception($prefix . sprintf($message, ...$args));
    };

    if (empty($queueSpec['type'])) {
      $throw('Missing field "type".');
    }

    // The rest of the validations only apply to persistent, runnable queues.
    if (empty($queueSpec['is_persistent']) || empty($queueSpec['runner'])) {
      return;
    }

    $statuses = CRM_Queue_BAO_Queue::getStatuses();
    $status = $queueSpec['status'] ?? NULL;
    if (!isset($statuses[$status])) {
      $throw('Invalid queue status "%s".', $status);
    }

    $errorModes = CRM_Queue_BAO_Queue::getErrorModes();
    $errorMode = $queueSpec['error'] ?? NULL;
    if ($queueSpec['runner'] === 'task' && !isset($errorModes[$errorMode])) {
      $throw('Invalid error mode "%s".', $errorMode);
    }
  }

}
