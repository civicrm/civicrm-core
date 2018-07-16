<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * The queue service provides an interface for creating or locating
 * queues. Note that this approach hides the details of data-storage:
 * different queue-providers may store the queue content in different
 * ways (in memory, in SQL, or in an external service).
 *
 * @code
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
 * @endcode
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
   * @var array (string $queueName => CRM_Queue_Queue)
   */
  public $queues;

  /**
   */
  public function __construct() {
    $this->queues = array();
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
   *
   * @return CRM_Queue_Queue
   */
  public function create($queueSpec) {
    if (@is_object($this->queues[$queueSpec['name']]) && empty($queueSpec['reset'])) {
      return $this->queues[$queueSpec['name']];
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
    if (is_object($this->queues[$queueSpec['name']])) {
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
