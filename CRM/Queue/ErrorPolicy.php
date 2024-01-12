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
 * To ensure that PHP errors or unhandled exceptions are reported in JSON
 * format, wrap this around your code. For example:
 *
 * ```
 * $errorContainer = new CRM_Queue_ErrorPolicy();
 * $errorContainer->call(function() {
 *    ...include some files, do some work, etc...
 * });
 * ```
 *
 * Note: Most of the code in this class is pretty generic vis-a-vis error
 * handling -- except for 'reportError', whose message format is only
 * appropriate for use with the CRM_Queue_Page_AJAX.  Some kind of cleanup
 * will be necessary to get reuse from the other parts of this class.
 */
class CRM_Queue_ErrorPolicy {

  /**
   * @var bool
   */
  protected $active;

  /**
   * @var int
   */
  protected $level;

  /**
   * @var array
   */
  protected $backup;

  /**
   * @param null|int $level
   *   PHP error level to capture (e.g. E_PARSE|E_USER_ERROR).
   */
  public function __construct($level = NULL) {
    register_shutdown_function([$this, 'onShutdown']);
    if ($level === NULL) {
      $level = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
    }
    $this->level = $level;
  }

  /**
   * Enable the error policy.
   */
  protected function activate() {
    $this->active = TRUE;
    $this->backup = [];
    foreach (['display_errors', 'html_errors', 'xmlrpc_errors'] as $key) {
      $this->backup[$key] = ini_get($key);
      ini_set($key, 0);
    }
    set_error_handler([$this, 'onError'], $this->level);
  }

  /**
   * Disable the error policy.
   */
  protected function deactivate() {
    restore_error_handler();
    foreach (['display_errors', 'html_errors', 'xmlrpc_errors'] as $key) {
      ini_set($key, $this->backup[$key]);
    }
    $this->active = FALSE;
  }

  /**
   * Execute the callable. Activate and deactivate the error policy
   * automatically.
   *
   * @param callable|array|string $callable
   *   A callback function.
   *
   * @return mixed
   */
  public function call($callable) {
    $this->activate();
    try {
      $result = $callable();
    }
    catch (Exception$e) {
      $this->reportException($e);
    }
    $this->deactivate();
    return $result;
  }

  /**
   * Receive (semi) recoverable error notices.
   *
   * @see set_error_handler
   *
   * @param string $errno
   * @param string $errstr
   * @param string $errfile
   * @param int $errline
   *
   * @return bool
   * @throws \Exception
   */
  public function onError($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
      return TRUE;
    }
    throw new Exception(sprintf('PHP Error %s at %s:%s: %s', $errno, $errfile, $errline, $errstr));
  }

  /**
   * Receive non-recoverable error notices
   *
   * @see register_shutdown_function
   * @see error_get_last
   */
  public function onShutdown() {
    if (!$this->active) {
      return;
    }
    $error = error_get_last();
    if (is_array($error) && ($error['type'] & $this->level)) {
      $this->reportError($error);
    }
  }

  /**
   * Print a fatal error.
   *
   * @param array $error
   *   The PHP error (with "type", "message", etc).
   */
  protected function reportError($error) {
    $response = [
      'is_error' => 1,
      'is_continue' => 0,
      'exception' => htmlentities(sprintf('Error %s: %s in %s, line %s', $error['type'], $error['message'], $error['file'], $error['line'])),
    ];
    global $activeQueueRunner;
    if (is_object($activeQueueRunner)) {
      $response['last_task_title'] = $activeQueueRunner->lastTaskTitle;
    }
    CRM_Core_Error::debug_var('CRM_Queue_ErrorPolicy_reportError', $response);
    echo json_encode($response);
    // civiExit() is unnecessary -- we're only called as part of abend
  }

  /**
   * Print an unhandled exception.
   *
   * @param Exception $e
   *   The unhandled exception.
   */
  protected function reportException(Exception $e) {
    CRM_Core_Error::debug_var('CRM_Queue_ErrorPolicy_reportException', CRM_Core_Error::formatTextException($e));

    $response = [
      'is_error' => 1,
      'is_continue' => 0,
    ];

    $config = CRM_Core_Config::singleton();
    if ($config->backtrace || CRM_Core_Config::isUpgradeMode()) {
      $response['exception'] = CRM_Core_Error::formatHtmlException($e);
    }
    else {
      $response['exception'] = htmlentities($e->getMessage());
    }

    global $activeQueueRunner;
    if (is_object($activeQueueRunner)) {
      $response['last_task_title'] = $activeQueueRunner->lastTaskTitle;
    }
    CRM_Utils_JSON::output($response);
  }

}
