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

use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * `CRM_Queue_TaskHandler`  a list tasks from a queue. It is designed to supported background
 * tasks which run automatically.
 *
 * This runner is not appropriate for all queues or workloads, so you might choose or create
 * a different runner. For example, `CRM_Queue_Runner` is geared toward background task lists.
 *
 * @service civi.queue.task_handler
 */
class CRM_Queue_TaskHandler extends AutoService implements EventSubscriberInterface {

  use CRM_Queue_BasicHandlerTrait;

  /**
   * Symbolic name for the kinds of records that we handle.
   * This matches column 'civicrm_queue.runner' and event 'hook_civicrm_queueRun_{NAME}'.
   */
  const TYPE_NAME = 'task';

  public static function getSubscribedEvents() {
    return [
      '&hook_civicrm_queueRun_' . static::TYPE_NAME => 'runBatch',
    ];
  }

  protected function validateItem($item): bool {
    return $item->data instanceof CRM_Queue_Task;
  }

  /**
   * Do a unit of work with one item from the queue.
   *
   * @param $item
   * @param $queue
   */
  protected function runItem($item, \CRM_Queue_Queue $queue): void {
    $taskCtx = new \CRM_Queue_TaskContext();
    $taskCtx->queue = $queue;
    $taskCtx->log = \CRM_Core_Error::createDebugLogger();
    $runResult = $item->data->run($taskCtx);
    if (!$runResult) {
      throw new \CRM_Core_Exception('Queue task returned false', 'queue_false');
    }
  }

  /**
   * Get detailed info about the item. This is used for debugging.
   *
   * @param $item
   * @return array
   */
  protected function getItemDetails($item): array {
    return CRM_Utils_Array::subset((array) $item->data, ['title', 'callback', 'arguments']);
  }

}
