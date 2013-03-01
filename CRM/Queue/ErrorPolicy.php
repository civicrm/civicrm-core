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
 * To ensure that PHP errors or unhandled exceptions are reported in JSON format,
 * wrap this around your code. For example:
 *
 * @code
 * $errorContainer = new CRM_Queue_ErrorPolicy();
 * $errorContainer->call(function(){
 *    ...include some files, do some work, etc...
 * });
 * @endcode
 *
 * Note: Most of the code in this class is pretty generic vis-a-vis error
 * handling -- except for 'reportError', whose message format is only
 * appropriate for use with the CRM_Queue_Page_AJAX.  Some kind of cleanup
 * will be necessary to get reuse from the other parts of this class.
 */
class CRM_Queue_ErrorPolicy {
  var $active;
  function __construct($level = NULL) {
    register_shutdown_function(array($this, 'onShutdown'));
    if ($level === NULL) {
      $level = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
    }
    $this->level = $level;
  }

  function activate() {
    $this->active = TRUE;
    $this->backup = array();
    foreach (array(
      'display_errors', 'html_errors', 'xmlrpc_errors') as $key) {
      $this->backup[$key] = ini_get($key);
      ini_set($key, 0);
    }
    set_error_handler(array($this, 'onError'), $this->level);
    // FIXME make this temporary/reversible
    $this->errorScope = CRM_Core_TemporaryErrorScope::useException();
  }

  function deactivate() {
    $this->errorScope = NULL;
    restore_error_handler();
    foreach (array(
      'display_errors', 'html_errors', 'xmlrpc_errors') as $key) {
      ini_set($key, $this->backup[$key]);
    }
    $this->active = FALSE;
  }

  function call($callable) {
    $this->activate();
    try {
      $result = $callable();
    }
    catch(Exception$e) {
      $this->reportException($e);
    }
    $this->deactivate();
    return $result;
  }

  /**
   * Receive (semi) recoverable error notices
   *
   * @see set_error_handler
   */
  function onError($errno, $errstr, $errfile, $errline) {
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
  function onShutdown() {
    if (!$this->active) {
      return;
    }
    $error = error_get_last();
    if (is_array($error) && ($error['type'] & $this->level)) {
      $this->reportError($error);
    }
  }

  /**
   * Print a fatal error
   *
   * @param $error
   */
  function reportError($error) {
    $response = array(
      'is_error' => 1,
      'is_continue' => 0,
      'exception' => htmlentities(sprintf('Error %s: %s in %s, line %s', $error['type'], $error['message'], $error['file'], $error['line'])),
    );
    global $activeQueueRunner;
    if (is_object($activeQueueRunner)) {
      $response['last_task_title'] = $activeQueueRunner->lastTaskTitle;
    }
    CRM_Core_Error::debug_var('CRM_Queue_ErrorPolicy_reportError', $response);
    echo json_encode($response);
    // civiExit() is unnecessary -- we're only called as part of abend
  }

  /**
   * Print an unhandled exception
   *
   * @param $e
   */
  function reportException(Exception $e) {
    CRM_Core_Error::debug_var('CRM_Queue_ErrorPolicy_reportException', CRM_Core_Error::formatTextException($e));

    $response = array(
      'is_error' => 1,
      'is_continue' => 0,
    );

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
    echo json_encode($response);
    CRM_Utils_System::civiExit();
  }
}

