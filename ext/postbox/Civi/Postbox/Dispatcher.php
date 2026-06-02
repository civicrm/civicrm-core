<?php

namespace Civi\Postbox;

use Civi\Core\Service\AutoService;

use CRM_Postbox_ExtensionUtil as E;

/**
 * Coordinate sending messages using a Queue
 *
 * @service civi.postbox.dispatcher
 */
class Dispatcher extends AutoService {

  public const QUEUE_NAME = 'postbox_dispatch';

  /**
   * Register shutdown worker to dispatch anything in the queue
   */
  public function registerShutdownDispatcher(): void {
    if (\Civi::settings()->get('postbox_shutdown_dispatcher')) {
      \Civi\Queue\ShutdownWorker::register(self::QUEUE_NAME);
    }
  }

  public function getQueue(): \CRM_Queue_Queue {
    return \Civi::queue(self::QUEUE_NAME, [
      'type'  => 'SqlParallel',
      'reset' => FALSE,
      'error' => 'delete',
      'runner' => 'task',
      'retry_limit' => 3,
      'retry_interval' => 10,
    ]);
  }

  /**
   * When a message is created, add a queue task to send it
   *
   * @todo queuing messages individually is highly parellisable but maybe
   * inefficient when reloading data - could we create tasks for batches of
   * unsent messages?
   */
  public function queueNewMessage(int $messageId): void {
    $this->getQueue()->createItem(new \CRM_Queue_Task(
      // callback
      [self::class, 'sendMessageFromQueue'],
      // arguments
      [$messageId],
      // title
      "Send EmailMessage {$messageId}"
    ));
  }

  /**
   * Send a message from the queue
   */
  public static function sendMessageFromQueue(\CRM_Queue_TaskContext $ctx, int $messageId) {
    $send = \Civi\Api4\EmailMessage::send(FALSE)
      ->addWhere('id', '=', $messageId)
      ->addWhere('error_message', 'IS EMPTY')
      ->addWhere('date_sent', 'IS EMPTY')
      ->execute()
      ->single();

    if ($send['status'] === 'error') {
      \Civi::log()->error($send['message']);
      return FALSE;
    }

    return TRUE;
  }

}
