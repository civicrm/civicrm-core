<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * The queue runner is a helper which runs all jobs in a queue.
 *
 * The queue runner is most useful for one-off queues (such as an upgrade);
 * if the intention is to develop a dedicated, long-running worker thread,
 * then one should consider writing a new queue consumer.
 */
class CRM_Queue_Runner {

  /**
   * The failed task should be discarded, and queue processing should continue
   */
  CONST ERROR_CONTINUE = 1;

  /**
   * The failed task should be kept in the queue, and queue processing should abort
   */
  CONST ERROR_ABORT = 2;

  /**
   * @var string
   */
  var $title;

  /**
   * @var CRM_Queue_Queue
   */
  var $queue;
  var $errorMode;
  var $isMinimal;
  var $onEnd;
  var $onEndUrl;
  var $pathPrefix;
  // queue-runner id; used for persistence
  var $qrid;

  /**
   * @var array whether to display buttons, eg ('retry' => TRUE, 'skip' => FALSE)
   */
  var $buttons;

  /**
   * @var CRM_Queue_TaskContext
   */
  var $taskCtx;

  /**
   * Locate a previously-created instance of the queue-runner
   *
   * @param $qrid string, the queue-runner ID
   *
   * @return CRM_Queue_Runner or NULL
   */
  static function instance($qrid) {
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
   * @param $runnerSpec array with keys:
   *   - queue: CRM_Queue_Queue
   *   - errorMode: int, ERROR_CONTINUE or ERROR_ABORT
   *   - onEnd: mixed, a callback to update the UI after running; should be both callable and serializable
   *   - onEndUrl: string, the URL to which one redirects
   *   - pathPrefix: string, prepended to URLs for the web-runner; default: 'civicrm/queue'
   */
  public function __construct($runnerSpec) {
    $this->title      = CRM_Utils_Array::value('title', $runnerSpec, ts('Queue Runner'));
    $this->queue      = $runnerSpec['queue'];
    $this->errorMode  = CRM_Utils_Array::value('errorMode', $runnerSpec, self::ERROR_ABORT);
    $this->isMinimal  = CRM_Utils_Array::value('isMinimal', $runnerSpec, FALSE);
    $this->onEnd      = CRM_Utils_Array::value('onEnd', $runnerSpec, NULL);
    $this->onEndUrl   = CRM_Utils_Array::value('onEndUrl', $runnerSpec, NULL);
    $this->pathPrefix = CRM_Utils_Array::value('pathPrefix', $runnerSpec, 'civicrm/queue');
    $this->buttons    = CRM_Utils_Array::value('buttons', $runnerSpec, array('retry' => TRUE,'skip' => TRUE));
    // perhaps this value should be randomized?
    $this->qrid = $this->queue->getName();
  }

  /**
   * @return array
   */
  function __sleep() {
    // exclude taskCtx
    return array('title', 'queue', 'errorMode', 'isMinimal', 'onEnd', 'onEndUrl', 'pathPrefix', 'qrid', 'buttons');
  }

  /**
   * Redirect to the web-based queue-runner and evaluate all tasks in a queue.
   */
  public function runAllViaWeb() {
    $_SESSION['queueRunners'][$this->qrid] = serialize($this);
    $url = CRM_Utils_System::url($this->pathPrefix . '/runner', 'reset=1&qrid=' . urlencode($this->qrid));
    CRM_Utils_System::redirect($url);
    // TODO: evaluate items incrementally via AJAX polling, cleanup session, call
  }

  /**
   * Immediately run all tasks in a queue (until either reaching the end
   * of the queue or encountering an error)
   *
   * If the runner has an onEndUrl, then this function will not return
   *
   * @return mixed, TRUE if all tasks complete normally; otherwise, an array describing the failed task
   */
  public function runAll() {
    $taskResult = $this->formatTaskResult(TRUE);
    while ($taskResult['is_continue']) {
      // setRaiseException should't be necessary here, but there's a bug
      // somewhere which causes this setting to be lost.  Observed while
      // upgrading 4.0=>4.2.  This preference really shouldn't be a global
      // setting -- it should be more of a contextual/stack-based setting.
      // This should be appropriate because queue-runners are not used with
      // basic web pages -- they're used with CLI/REST/AJAX.
      $errorScope = CRM_Core_TemporaryErrorScope::useException();
      $taskResult = $this->runNext();
      $errorScope = NULL;
    }

    if ($taskResult['numberOfItems'] == 0) {
      $result = $this->handleEnd();
      if (!empty($result['redirect_url'])) {
        CRM_Utils_System::redirect($result['redirect_url']);
      }
      return TRUE;
    }
    else {
      return $taskResult;
    }
  }

  /**
   * Take the next item from the queue and attempt to run it.
   *
   * Individual tasks may also throw exceptions -- caller should watch for exceptions
   *
   * @param $useSteal bool, whether to steal active locks
   *
   * @return array(is_error => bool, is_continue => bool, numberOfItems => int, 'last_task_title' => $, 'exception' => $)
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
        $isOK = $item->data->run($this->getTaskContext());
        if (!$isOK) {
          $exception = new Exception('Task returned false');
      }
      }
      catch(Exception$e) {
        $isOK = FALSE;
        $exception = $e;
        }

      if ($isOK) {
        $this->queue->deleteItem($item);
      }
      else {
        $this->releaseErrorItem($item);
      }

      return $this->formatTaskResult($isOK, $exception);
    }
    else {
      return $this->formatTaskResult(FALSE, new Exception('Failed to claim next task'));
    }
  }

  /**
   * Take the next item from the queue and attempt to run it.
   *
   * Individual tasks may also throw exceptions -- caller should watch for exceptions
   *
   * @param $useSteal bool, whether to steal active locks
   *
   * @return array(is_error => bool, is_continue => bool, numberOfItems => int)
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
   * @param $item
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
   *
   * @return array(is_error => bool, is_continue => bool, numberOfItems => int, redirect_url => string)
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
    $result = array();
    $result['is_error'] = 0;
    $result['numberOfItems'] = 0;
    $result['is_continue'] = 0;
    if (!empty($this->onEndUrl)) {
      $result['redirect_url'] = $this->onEndUrl;
    }
    return $result;
  }

  /**
   *
   * @param $isOK
   * @param null $exception
   *
   * @return array(is_error => bool, is_continue => bool, numberOfItems => int)
   */
  function formatTaskResult($isOK, $exception = NULL) {
    $numberOfItems = $this->queue->numberOfItems();

    $result = array();
    $result['is_error'] = $isOK ? 0 : 1;
    $result['exception'] = $exception;
    $result['last_task_title'] = isset($this->lastTaskTitle) ? $this->lastTaskTitle : '';
    $result['numberOfItems'] = $this->queue->numberOfItems();
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
}

