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
 * `CRM_Queue_TaskHandler`  a list tasks from a queue. It is designed to supported background
 * tasks which run automatically.
 *
 * This runner is not appropriate for all queues or workloads, so you might choose or create
 * a different runner. For example, `CRM_Queue_Runner` is geared toward background task lists.
 *
 * @service civi.queue.task_handler
 */
class CRM_Queue_TaskHandler extends CRM_Queue_BasicHandler {

  public static function getTypeName(): string {
    return 'task';
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
  protected function runItem($item, $queue): void {
    $runResult = $item->data->run($this->createContext($queue));
    if (!$runResult) {
      throw new \CRM_Core_Exception('Queue task returned false', 'queue_false');
    }
  }

  /**
   * Get a nice title for the item.
   *
   * @param $item
   * @return string|null
   */
  protected function getItemTitle($item): string {
    $title = parent::getItemTitle($item);
    if (isset($item->data->title)) {
      $title .= '(' . $item->data->title . ')';
    }
    return $title;
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

  /**
   * @param \CRM_Queue_Queue $queue
   * return CRM_Queue_TaskContext;
   */
  private function createContext(\CRM_Queue_Queue $queue): \CRM_Queue_TaskContext {
    $taskCtx = new \CRM_Queue_TaskContext();
    $taskCtx->queue = $queue;
    $taskCtx->log = \CRM_Core_Error::createDebugLogger();
    return $taskCtx;
  }

}
