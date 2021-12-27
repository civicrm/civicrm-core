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
   * FIXME: Singleton pattern should be removed when dependency-injection
   * becomes available.
   *
   * @param bool $forceNew
   *   TRUE if a new instance must be created.
   *
   * @return \CRM_Queue_Service
   */
  public static function &singleton($forceNew = FALSE) {
    if ($forceNew || !self::$_singleton) {
      self::$_singleton = new CRM_Queue_Service();
    }
    return self::$_singleton;
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
   *   - type: string, required, e.g. "interactive", "immediate", "stomp",
   *    "beanstalk"
   *   - name: string, required, e.g. "upgrade-tasks"
   *   - reset: bool, optional; if a queue is found, then it should be
   *     flushed; default to TRUE
   *   - (additional keys depending on the queue provider).
   *   - is_persistent: bool, optional; if true, then this queue is stored and discoverable
   *   - is_autorun: bool, optional; if true, then this queue will be auto-scanned
   *     by background task-runners
   *
   * @return CRM_Queue_Queue
   */
  public function create($queueSpec) {
    if (is_object($this->queues[$queueSpec['name']] ?? NULL) && empty($queueSpec['reset'])) {
      return $this->queues[$queueSpec['name']];
    }
    $queueSpec = array_merge($this->getDefaultSpec($queueSpec['name']), $queueSpec);

    if (!empty($queueSpec['is_persistent'])) {
      $this->registerPersistentQueue($queueSpec);
    }

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
   * Determine default settings based on the name of queue.
   *
   * Flags:
   *   - `?bg` ("Background") implies persistent, auto-running queue with parallel tasks
   *   - `?fg` ("Foreground") implies ephemeral queue with linear tasks
   *
   * @param string $name
   *   Ex: 'foo?bg'
   * @return array
   *   Ex: ['type' => 'SqlParallel', 'is_persistent' => TRUE, 'is_autorun' => TRUE]
   */
  protected function getDefaultSpec(string $name): array {
    $defaults = ['is_persistent' => FALSE, 'is_autorun' => FALSE];
    if (FALSE !== ($questionPos = strpos($name, '?'))) {
      $flags = explode(',', substr($name, 1 + $questionPos));

      $flagDefs = [
        'bg' => ['is_persistent' => TRUE, 'is_autorun' => TRUE, 'type' => 'SqlParallel'],
        'fg' => ['is_persistent' => FALSE, 'is_autorun' => FALSE, 'type' => 'Sql'],
        // More fine-grained flags might be more expressive. But are they really needed? Maybe...?
        // 'parallel' => ['type' => 'SqlParallel'],
        // 'linear' => ['type' => 'Sql'],
        // 'persist' => ['is_persistent' => TRUE],
        // 'autorun' => ['is_persistent' => TRUE, 'is_autorun' => TRUE],
      ];

      foreach (array_intersect($flags, array_keys($flagDefs)) as $flag) {
        $defaults = array_merge($defaults, $flagDefs[$flag]);
      }
    }
    return $defaults;
  }

  protected function registerPersistentQueue(array $queueSpec): void {
    $values = CRM_Utils_Array::subset($queueSpec, ['name', 'type', 'is_autorun']);

    // Passing this to APIv4 might have advantages; at time of writing, APIv4 seems to confuse MailingEventQueue and Queue.
    // if (Civi\Api4\Queue::get(FALSE)->addWhere('name', '=', $queueSpec['name'])->execute()->count()) {
    //   return;
    // }
    // Civi\Api4\Queue::create(FALSE)
    //   ->setValues($values)
    //   ->execute();

    $dao = new CRM_Queue_DAO_Queue();
    $dao->name = $queueSpec['name'];
    if ($dao->find()) {
      return;
    }

    $dao = new CRM_Queue_DAO_Queue();
    $dao->copyValues($values);
    $dao->insert();
  }

  /**
   * Look up an existing queue.
   *
   * @param array $queueSpec
   *   Array with keys:
   *   - type: string, required, e.g. "interactive", "immediate", "stomp",
   *     "beanstalk"
   *   - name: string, required, e.g. "upgrade-tasks"
   *   - (additional keys depending on the queue provider).
   *
   * @return CRM_Queue_Queue
   */
  public function load($queueSpec) {
    if (is_object($this->queues[$queueSpec['name']] ?? NULL)) {
      return $this->queues[$queueSpec['name']];
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
   *   E.g. "interactive", "immediate", "stomp", "beanstalk".
   *
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

}
