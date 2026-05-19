<?php

namespace Civi\Queue;

/**
 * Shutdown worker uses register_shutdown_function to work
 * on a queue for some time after a given request has returned to the client
 * (so we can do long running tasks without slowing user interaction)
 */
class ShutdownWorker {

  protected static bool $registered = FALSE;

  /**
   * @var array
   * Queues to work on shutdown:
   *   queueName => seconds to work on it
   */
  protected static array $queues = [];

  /**
   * Add a given queue to the list of queues to process on shutdown, and register
   * the handler if it hasn't done already
   *
   * UNLESS:
   * - background processing is paused using the `queue_paused` setting
   * - fastcgi_finish_request is not available (e.g. in CLI context) - this
   *   is required for async handling
   */
  public static function register(string $queueName, int $seconds = 30) {
    if (\Civi::settings()->get('queue_paused')) {
      return;
    }
    if (!\function_exists('fastcgi_finish_request')) {
      return;
    }
    self::$queues[$queueName] = $seconds;

    if (!self::$registered) {
      register_shutdown_function([self::class, 'workQueues']);
      self::$registered = TRUE;
    }
  }

  public static function workQueues() {
    // finish the client request
    \session_write_close();
    \fastcgi_finish_request();

    foreach (self::$queues as $queueName => $seconds) {
      self::workOnQueue($queueName, $seconds);
    }
  }

  protected static function workOnQueue(string $queueName, int $seconds): void {
    $runner = new \CRM_Queue_Runner([
      'title' => ts("Shutdown Worker for {$queueName}"),
      'queue' => \Civi::queue($queueName),
      'errorMode' => \CRM_Queue_Runner::ERROR_CONTINUE,
    ]);

    // work for specified number of seconds post shutdown
    $timeout = time() + $seconds;

    while (time() < $timeout) {
      $result = $runner->runNext(FALSE);
      if (!$result['is_continue']) {
        return;
      }
    }
  }

}
