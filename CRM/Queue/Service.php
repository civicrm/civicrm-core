<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * if (my_process($item->data)) {
 *   $myMessage->deleteItem();
 * } else {
 *   $myMessage->releaseItem();
 * }
 * @endcode
 */
class CRM_Queue_Service {

  static $_singleton;

  /**
   * FIXME: Singleton pattern should be removed when dependency-injection
   * becomes available.
   *
   * @param $forceNew bool
   */
  static function &singleton($forceNew = FALSE) {
    if ($forceNew || !self::$_singleton) {
      self::$_singleton = new CRM_Queue_Service();
    }
    return self::$_singleton;
  }

  /**
   * @var array(queueName => CRM_Queue_Queue)
   */
  var $queues;
  function __construct() {
    $this->queues = array();
  }

  /**
   *
   * @param $queueSpec, array with keys:
   *   - type: string, required, e.g. "interactive", "immediate", "stomp", "beanstalk"
   *   - name: string, required, e.g. "upgrade-tasks"
   *   - reset: bool, optional; if a queue is found, then it should be flushed; default to TRUE
   *   - (additional keys depending on the queue provider)
   *
   * @return CRM_Queue_Queue
   */
  function create($queueSpec) {
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
   *
   * @param $queueSpec, array with keys:
   *   - type: string, required, e.g. "interactive", "immediate", "stomp", "beanstalk"
   *   - name: string, required, e.g. "upgrade-tasks"
   *   - (additional keys depending on the queue provider)
   *
   * @return CRM_Queue_Queue
   */
  function load($queueSpec) {
    if (is_object($this->queues[$queueSpec['name']])) {
      return $this->queues[$queueSpec['name']];
    }
    $queue = $this->instantiateQueueObject($queueSpec);
    $queue->loadQueue();
    $this->queues[$queueSpec['name']] = $queue;
    return $queue;
  }

  /**
   * Convert a queue "type" name to a class name
   *
   * @param $type string, e.g. "interactive", "immediate", "stomp", "beanstalk"
   *
   * @return string, class-name
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
   *
   * @param $queueSpec array, see create()
   *
   * @return CRM_Queue_Queue
   */
  protected function instantiateQueueObject($queueSpec) {
    // note: you should probably never do anything else here
    $class = new ReflectionClass($this->getQueueClass($queueSpec['type']));
    return $class->newInstance($queueSpec);
  }
}

