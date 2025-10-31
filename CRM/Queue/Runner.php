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

use Civi\Api4\UserJob;
use Civi\Core\Event\GenericHookEvent;

/**
 * `CRM_Queue_Runner` runs a list tasks from a queue. It originally supported the database-upgrade
 * queue. Consequently, this runner is optimal for queues which are:
 *
 * - Short lived and discrete. You have a fixed list of tasks that will be run to completion.
 * - Strictly linear. Tasks must run 1-by-1. Often, one task depends on the success of a previous task.
 * - Slightly dangerous. An error, omission, or mistake indicates that the database is in an
 *   inconsistent state. Errors call for skilled human intervention.
 *
 * This runner supports a few modes of operation, eg
 *
 * - `runAllViaWeb()`: Use a web-browser and a series of AJAX requests to perform all steps.
 *   If there is an error, prompt the sysadmin/user to decide how to handle it.
 * - `runAll()`: Run all tasks, 1-by-1, back-to-back. If there is an error, give up.
 *   This is used by some CLI upgrades.
 *
 * This runner is not appropriate for all queues or workloads, so you might choose or create
 * a different runner. For example, `CRM_Queue_TaskRunner` is geared toward background task lists.
 *
 * @see CRM_Queue_TaskRunner
 */
class CRM_Queue_Runner {

  /**
   * The failed task should be discarded, and queue processing should continue.
   */
  const ERROR_CONTINUE = 1;

  /**
   * The failed task should be kept in the queue, and queue processing should
   * abort.
   */
  const ERROR_ABORT = 2;

  /**
   * @var string
   */
  public $title;

  /**
   * @var CRM_Queue_Queue
   */
  public $queue;
  public $errorMode;
  public $isMinimal;
  public $onEnd;
  public $onEndUrl;
  public $pathPrefix;
  /**
   * queue-runner id; used for persistence
   * @var int
   */
  public $qrid;

  /**
   * @var array
   * Whether to display buttons, eg ('retry' => TRUE, 'skip' => FALSE)
   */
  public $buttons;

  /**
   * @var CRM_Queue_TaskContext
   */
  public $taskCtx;

  /**
   * @var string
   */
  public $lastTaskTitle;

  /**
   * Locate a previously-created instance of the queue-runner.
   *
   * @param string $qrid
   *   The queue-runner ID.
   *
   * @return CRM_Queue_Runner|NULL
   */
  public static function instance($qrid) {
    if (!empty($_SESSION['queueRunners'][$qrid])) {
      return unserialize($_SESSION['queueRunners'][$qrid]);
    }
    else {
      return NULL;
    }
  }

  /**
   *
   * FIXME: parameter validation
   * FIXME: document signature of onEnd callback
   *
   * @param array $runnerSpec
   *   Array with keys:
   *   - queue: CRM_Queue_Queue
   *   - errorMode: int, ERROR_CONTINUE or ERROR_ABORT.
   *     If omitted, it inherits from `$queue->spec['error']` or falls back to `ERROR_ABORT`.
   *   - onEnd: mixed, a callback to update the UI after running; should be
   *     both callable and serializable.
   *   - onEndUrl: string, the URL to which one redirects.
   *   - pathPrefix: string, prepended to URLs for the web-runner;
   *     default: 'civicrm/queue'.
   *   - buttons
   */
  public function __construct($runnerSpec) {
    $this->title = $runnerSpec['title'] ?? ts('Queue Runner');
    $this->queue = $runnerSpec['queue'];
    $this->errorMode = $runnerSpec['errorMode'] ?? $this->pickErrorMode($this->queue);
    $this->isMinimal = $runnerSpec['isMinimal'] ?? FALSE;
    $this->onEnd = $runnerSpec['onEnd'] ?? NULL;
    $this->onEndUrl = $runnerSpec['onEndUrl'] ?? NULL;
    $this->pathPrefix = $runnerSpec['pathPrefix'] ?? 'civicrm/queue';
    $this->buttons = $runnerSpec['buttons'] ?? ['retry' => TRUE, 'skip' => TRUE];
    // perhaps this value should be randomized?
    $this->qrid = $this->queue->getName();
  }

  /**
   * @return array
   */
  public function __sleep() {
    // exclude taskCtx
    return [
      'title',
      'queue',
      'errorMode',
      'isMinimal',
      'onEnd',
      'onEndUrl',
      'pathPrefix',
      'qrid',
      'buttons',
    ];
  }

  /**
   * Run all tasks interactively. Redirect to a screen which presents the progress.
   *
   * The exact mechanism and pageflow may be determined by the system configuration --
   * environments which support multiprocessing (background queue-workers) can use those;
   * otherwise, they can use the traditional AJAX runner.
   *
   * To ensure portability, requesters must satisfy the requirements of
   * *both/all* execution mechanisms.
   *
   * @throws \CRM_Core_Exception
   */
  public function runAllInteractive(bool $redirectImmediately = TRUE): ?string {
    $this->assertRequirementsWeb();
    $this->assertRequirementsBackground();

    $userJob = $this->findUserJob();
    $userJob['metadata']['runner'] = [
      'title' => $this->title,
      'onEndUrl' => $this->onEndUrl,
      // 'onEnd' ==> No, see comments in assertRequirementsBackground()
    ];
    UserJob::save(FALSE)->setRecords([$userJob])->execute();

    if (Civi::settings()->get('enableBackgroundQueue')) {
      $url = $this->startBackgroundRun();
    }
    else {
      $url = $this->startWebRun();
    }
    if ($redirectImmediately) {
      CRM_Utils_System::redirect($url);
    }
    return $url;
  }

  protected function runAllViaBackground() {
    $url = $this->startBackgroundRun();
    CRM_Utils_System::redirect($url);
  }

  /**
   * Redirect to the web-based queue-runner and evaluate all tasks in a queue.
   */
  public function runAllViaWeb() {
    $url = $this->startWebRun();
    CRM_Utils_System::redirect($url);
  }

  /**
   * Start background runner and return url
   *
   * @return string queue monitor url
   */
  public function startBackgroundRun(): string {
    CRM_Core_DAO::executeQuery('UPDATE civicrm_queue SET status = "active" WHERE name = %1', [
      1 => [$this->queue->getName(), 'String'],
    ]);
    return CRM_Utils_System::url('civicrm/queue/monitor', ['name' => $this->queue->getName()], FALSE, NULL, FALSE);
  }

  /**
   * Start web-based queue-runner and return url.
   *
   * @return string queue runner url
   */
  public function startWebRun(): string {
    $_SESSION['queueRunners'][$this->qrid] = serialize($this);
    $this->disableBackgroundExecution();
    return CRM_Utils_System::url($this->pathPrefix . '/runner', 'reset=1&qrid=' . urlencode($this->qrid), FALSE, NULL, FALSE);
  }

  /**
   * Immediately run all tasks in a queue (until either reaching the end
   * of the queue or encountering an error)
   *
   * If the runner has an onEndUrl, then this function will not return
   *
   * @return array|true
   *   TRUE if all tasks complete normally; otherwise, an array describing the
   *   failed task
   */
  public function runAll() {
    $this->disableBackgroundExecution();
    $taskResult = $this->formatTaskResult(TRUE);
    while ($taskResult['is_continue']) {
      $taskResult = $this->runNext();
    }

    if ($taskResult['numberOfItems'] === 0) {
      $result = $this->handleEnd();
      if (!empty($result['redirect_url'])) {
        CRM_Utils_System::redirect($result['redirect_url']);
      }
      return TRUE;
    }
    return $taskResult;
  }

  /**
   * Take the next item from the queue and attempt to run it.
   *
   * Individual tasks may also throw exceptions -- caller should watch for
   * exceptions.
   *
   * @param bool $useSteal
   *   Whether to steal active locks.
   *
   * @return array
   *   - is_error => bool,
   *   - is_continue => bool,
   *   - numberOfItems => int,
   *   - 'last_task_title' => $,
   *   - 'exception' => $
   */
  public function runNext($useSteal = FALSE) {
    if ($useSteal) {
      $item = $this->queue->stealItem();
    }
    else {
      $item = $this->queue->claimItem();
    }

    if ($item) {
      $this->lastTaskTitle = $item->data->title;

      $exception = NULL;
      try {
        CRM_Core_Error::debug_log_message("Running task: " . $this->lastTaskTitle);
        $isOK = $item->data->run($this->getTaskContext());
        if (!$isOK) {
          $exception = new Exception('Task returned false');
        }
      }
      catch (Exception $e) {
        $isOK = FALSE;
        $exception = $e;
      }

      if ($isOK) {
        $this->queue->deleteItem($item);
      }
      else {
        $this->releaseErrorItem($item);
      }

      \Civi::dispatcher()->dispatch('civi.queue.check', GenericHookEvent::create([
        'queue' => $this->queue,
      ]));

      return $this->formatTaskResult($isOK, $exception);
    }
    else {
      return $this->formatTaskResult(FALSE, new Exception('Failed to claim next task'));
    }
  }

  /**
   * Take the next item from the queue and attempt to run it.
   *
   * Individual tasks may also throw exceptions -- caller should watch for
   * exceptions.
   *
   * @param bool $useSteal
   *   Whether to steal active locks.
   *
   * @return array
   *   - is_error => bool,
   *   - is_continue => bool,
   *   - numberOfItems => int)
   */
  public function skipNext($useSteal = FALSE) {
    if ($useSteal) {
      $item = $this->queue->stealItem();
    }
    else {
      $item = $this->queue->claimItem();
    }

    if ($item) {
      $this->lastTaskTitle = $item->data->title;
      $this->queue->deleteItem($item);
      return $this->formatTaskResult(TRUE);
    }
    else {
      return $this->formatTaskResult(FALSE, new Exception('Failed to claim next task'));
    }
  }

  /**
   * Release an item in keeping with the error mode.
   *
   * @param object $item
   *   The item previously produced by Queue::claimItem.
   */
  protected function releaseErrorItem($item) {
    switch ($this->errorMode) {
      case self::ERROR_CONTINUE:
        $this->queue->deleteItem($item);
      case self::ERROR_ABORT:
      default:
        $this->queue->releaseItem($item);
    }
  }

  /**
   * @return array
   *   - is_error => bool,
   *   - is_continue => bool,
   *   - numberOfItems => int,
   *   - redirect_url => string
   */
  public function handleEnd() {
    if (is_callable($this->onEnd)) {
      call_user_func($this->onEnd, $this->getTaskContext());
    }
    // Don't remove queueRunner until onEnd succeeds
    if (!empty($_SESSION['queueRunners'][$this->qrid])) {
      unset($_SESSION['queueRunners'][$this->qrid]);
    }

    // Fallback; web UI does redirect in Javascript
    $result = [];
    $result['is_error'] = 0;
    $result['numberOfItems'] = 0;
    $result['is_continue'] = 0;
    if (!empty($this->onEndUrl)) {
      $result['redirect_url'] = $this->onEndUrl;
    }
    $this->enableBackgroundExecution();
    return $result;
  }

  /**
   * Format a result record which describes whether the task completed.
   *
   * @param bool $isOK
   *   TRUE if the task completed successfully.
   * @param Exception|null $exception
   *   If applicable, an unhandled exception that arose during execution.
   *
   * @return array
   *   (is_error => bool, is_continue => bool, numberOfItems => int)
   */
  public function formatTaskResult($isOK, $exception = NULL) {
    $numberOfItems = $this->queue->numberOfItems();

    $result = [];
    $result['is_error'] = $isOK ? 0 : 1;
    $result['exception'] = $exception;
    $result['last_task_title'] = $this->lastTaskTitle ?? '';
    $result['numberOfItems'] = (int) $this->queue->numberOfItems();
    if ($result['numberOfItems'] <= 0) {
      // nothing to do
      $result['is_continue'] = 0;
    }
    elseif ($isOK) {
      // more tasks remain, and this task succeeded
      $result['is_continue'] = 1;
    }
    elseif ($this->errorMode == CRM_Queue_Runner::ERROR_CONTINUE) {
      // more tasks remain, and we can disregard this error
      $result['is_continue'] = 1;
    }
    else {
      // more tasks remain, but we can't disregard the error
      $result['is_continue'] = 0;
    }

    return $result;
  }

  /**
   * @return CRM_Queue_TaskContext
   */
  protected function getTaskContext() {
    if (!is_object($this->taskCtx)) {
      $this->taskCtx = new CRM_Queue_TaskContext();
      $this->taskCtx->queue = $this->queue;
      // $this->taskCtx->log = CRM_Core_Config::getLog();
      $this->taskCtx->log = CRM_Core_Error::createDebugLogger();
    }
    return $this->taskCtx;
  }

  /**
   * If the runner doesn't its own error-policy, then try to inherit the policy
   * from the queue configuration.
   *
   * @param \CRM_Queue_Queue $queue
   * @return int
   */
  protected function pickErrorMode(CRM_Queue_Queue $queue) {
    switch ($queue->getSpec('error')) {
      case 'delete':
        return static::ERROR_CONTINUE;

      case 'abort':
      case '':
      case NULL:
        // ERROR_ABORT is the traditional default for AJAX runner.
        return static::ERROR_ABORT;

      default:
        Civi::log()->warning('Unrecognized queue error mode: {mode}', [
          'mode' => $queue->getSpec('error'),
        ]);
        return static::ERROR_ABORT;
    }

  }

  /**
   * Find the `UserJob` that corresponds to this queue (if any).
   *
   * @return array|null
   *   The record, per APIv4.
   *   This may return NULL. UserJobs are required for `runAllInteractively()`
   *   and
   *   `runAllViaBackground()`, but (for backward compatibility) they are not
   *   required for `runAllViaWeb()`.
   *
   * @throws \CRM_Core_Exception
   */
  protected function findUserJob(): ?array {
    return UserJob::get(FALSE)
      ->addWhere('queue_id.name', '=', $this->queue->getName())
      ->execute()
      ->first();
  }

  /**
   * Assert that we meet the requirements for running tasks in background.
   * @throws \CRM_Core_Exception
   */
  protected function assertRequirementsBackground(): void {
    $prefix = sprintf('Cannot execute queue "%s".', $this->queue->getName());

    if (CRM_Core_Config::isUpgradeMode()) {
      // Too many dependencies for use in upgrading - eg background runner relies on APIv4, and
      // monitoring relies on APIv4 and Angular-modules. Only use runAllViaWeb() for upgrade-mode.
      throw new \CRM_Core_Exception($prefix . ' It does not support upgrade mode.');
    }

    if (!$this->queue->getSpec('runner')) {
      throw new \CRM_Core_Exception($prefix . ' The "civicrm_queue.runner" property is missing.');
    }

    $errorModes = CRM_Queue_BAO_Queue::getErrorModes();
    if (!isset($errorModes[$this->queue->getSpec('error')])) {
      throw new \CRM_Core_Exception($prefix . ' The "civicrm_queue.error" property is invalid.');
    }

    if ($this->onEnd) {
      throw new \CRM_Core_Exception($prefix . ' The "onEnd" property is not supported by background workers. However, "hook_civicrm_queueStatus" is supported by both foreground and background.');
      // Also: There's nowhere to store it. 'UserJob.metadata' allows remote CRUD, which means you cannot securely store callables.
    }

    $userJob = $this->findUserJob();
    if (!$userJob) {
      throw new \CRM_Core_Exception($prefix . ' There is no associated UserJob.');
    }
  }

  /**
   * Assert that we meet the requirements for running tasks via AJAX.
   * @throws \CRM_Core_Exception
   */
  protected function assertRequirementsWeb(): void {
    $prefix = sprintf('Cannot execute queue "%s".', $this->queue->getName());

    $runnerType = $this->queue->getSpec('runner');
    if ($runnerType && $runnerType !== 'task') {
      // The AJAX frontend doesn't read `runner` (so it's not required here); but
      // it only truly support `task` data (at time of writing). Anything else indicates confusion.
      throw new \CRM_Core_Exception($prefix . ' AJAX workers only support "runner=task".');
    }
  }

  /**
   * Ensure that background workers will not try to run this queue.
   */
  protected function disableBackgroundExecution(): void {
    if (CRM_Core_Config::isUpgradeMode()) {
      // Versions <=5.50 do not have `status` column.
      if (!CRM_Core_DAO::checkTableExists('civicrm_queue') || !CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_queue', 'status')) {
        // The system doesn't have automatic background workers yet. Neither necessary nor possible to toggle `status`.
        // See also: https://lab.civicrm.org/dev/core/-/issues/3653
        return;
      }
    }

    // We don't actually know if the queue was registered persistently.
    // But if it was, then it should be disabled.
    CRM_Core_DAO::executeQuery('UPDATE civicrm_queue SET status = NULL WHERE name = %1', [
      1 => [$this->queue->getName(), 'String'],
    ]);
  }

  /**
   * Ensure that background workers will not try to run this queue.
   */
  protected function enableBackgroundExecution(): void {
    if (CRM_Core_Config::isUpgradeMode()) {
      // Versions <=5.50 do not have `status` column.
      if (!CRM_Core_DAO::checkTableExists('civicrm_queue') || !CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_queue', 'status')) {
        // The system doesn't have automatic background workers yet. Neither necessary nor possible to toggle `status`.
        // See also: https://lab.civicrm.org/dev/core/-/issues/3653
        return;
      }
    }

    // If it was disabled for background processing & has not been otherwise altered then
    // re-enable it as it might be a persistent queue.
    CRM_Core_DAO::executeQuery('UPDATE civicrm_queue SET status = "active" WHERE name = %1 AND status IS NULL', [
      1 => [$this->queue->getName(), 'String'],
    ]);
  }

}
