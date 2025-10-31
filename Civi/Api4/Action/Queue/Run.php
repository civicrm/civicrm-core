<?php

namespace Civi\Api4\Action\Queue;

use Civi\Api4\Generic\Result;
use Civi\Api4\Queue;

/**
 * Run a series of items from a queue.
 *
 * This is a lightweight main-loop for development/testing. It repeatedly runs tasks, until:
 *
 * - The queue is empty, or...
 * - The queue encounters an abortive error, or...
 * - The queue is externally paused/disabled, or...
 * - The runner reaches an execution limit (such as `$maxDuration` or `$maxRequests`).
 *
 * This main-loop is similar to the older helpers, `CRM_Queue_Runner::runAll()` and
 * `civicrm_api_job_runspecificqueue` (devdocs-template) -- except this implementation supports
 * newer hooks and configuration flags, and it requires persistent `civicrm_queue` metadata.
 *
 * This main-loop may have some utility for sysadmins who want to fine-tune runners on a
 * specific queue, but it is not a full/system-level agent. It lacks support for
 * multi-queue, privilege-separation (`runAs`), process-pooling, PHP-fatal recovery, etc.
 * (For a full/system-level agent, see `coworker`.)
 *
 * @method ?string getQueue
 * @method $this setQueue(?string $queue)
 * @method ?int getMaxRequests()
 * @method $this setMaxRequests(?int $maxRequests)
 * @method ?int getMaxDuration()
 * @method $this setMaxDuration(?int $maxDuration)
 */
class Run extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Name of the target queue.
   *
   * @var string
   * @required
   */
  protected $queue;

  /**
   * Maximum number of tasks to execute.
   *
   * After reaching this limit, the loop will stop taking new tasks.
   *
   * @var int|null
   *   Special values `null` and `-1` indicate infinite execution.
   * @see \Civi\Coworker\Configuration::$maxWorkerRequests
   */
  public $maxRequests = 10;

  /**
   * Maximum amount of time (seconds) for which a single worker should execute.
   *
   * After reaching this limit, no more tasks will be given to the worker.
   *
   * @var int|null
   *   Special values `null` and `-1` indicate infinite execution.
   * @see \Civi\Coworker\Configuration::$maxWorkerDuration
   */
  public $maxDuration = 120;

  public function _run(Result $result) {
    $queue = \Civi::queue($this->queue);
    $startTime = microtime(TRUE);
    $requests = 0;
    $errors = 0;
    $successes = 0;
    $message = NULL;
    $perm = $this->getCheckPermissions();

    while (TRUE) {
      if (!$queue->isActive()) {
        $message = sprintf('Queue is not active (status => %s)', $queue->getStatus());
        break;
      }

      if (static::isFinite($this->maxRequests) && $requests >= $this->maxRequests) {
        $message = sprintf('Reached request limit (%d)', $this->maxRequests);
        break;
      }
      if (static::isFinite($this->maxDuration) && (microtime(TRUE) - $startTime) >= $this->maxDuration) {
        $message = sprintf('Reached duration limit (%d)', $this->maxDuration);
        break;
      }

      try {
        $requests++;

        $claims = Queue::claimItems($perm)->setQueue($this->queue)->execute()->getArrayCopy();
        if (empty($claims)) {
          $message = 'No claimable items';
          break;
        }

        $batchResults = Queue::runItems($perm)->setQueue($this->queue)->setItems($claims)->execute();
      }
      catch (\Throwable $t) {
        $errors++;
        $message = sprintf('Queue-item raised unhandled exception (%s: %s)', get_class($t), $t->getMessage());
        \Civi::log('queue')->alert($message, ['subject' => 'Queue-item raised unhandled exception (' . get_class($t)]);
        break;
      }

      foreach ($batchResults as $batchResult) {
        if ($batchResult['outcome'] === 'ok') {
          $successes++;
        }
        else {
          $errors++;
          // Should we stop? No, we're just reporting stats.
          // What about queues with policy "error=>abort"? They must update ("status=>aborted") under the aegis of runItems().
          // Stopping here would obscure problems that affect all main-loops.
        }
      }
    }

    $result[] = [
      'loop_duration' => sprintf('%.3f', microtime(TRUE) - $startTime),
      'loop_requests' => $requests,
      'item_successes' => $successes,
      'item_errors' => $errors,
      'queue_ready' => $queue->getStatistic('ready'),
      'queue_blocked' => $queue->getStatistic('blocked'),
      'queue_total' => $queue->getStatistic('total'),
      'exit_message' => $message,
    ];
  }

  private static function isFinite($value): bool {
    return $value !== NULL && $value >= 0;
  }

}
