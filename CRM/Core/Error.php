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
 * Start of the Error framework. We should check out and inherit from
 * PEAR_ErrorStack and use that framework
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

require_once 'PEAR/ErrorStack.php';
require_once 'PEAR/Exception.php';

require_once 'Log.php';
class CRM_Exception extends PEAR_Exception {
  // Redefine the exception so message isn't optional
  public function __construct($message = NULL, $code = 0, Exception$previous = NULL) {
    parent::__construct($message, $code, $previous);
  }
}

class CRM_Core_Error extends PEAR_ErrorStack {

  /**
   * status code of various types of errors
   * @var const
   */
  CONST FATAL_ERROR = 2;
  CONST DUPLICATE_CONTACT = 8001;
  CONST DUPLICATE_CONTRIBUTION = 8002;
  CONST DUPLICATE_PARTICIPANT = 8003;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   * @var object
   * @static
   */
  private static $_singleton = NULL;

  /**
   * The logger object for this application
   * @var object
   * @static
   */
  private static $_log = NULL;

  /**
   * If modeException == true, errors are raised as exception instead of returning civicrm_errors
   * @static
   */
  public static $modeException = NULL;

  /**
   * singleton function used to manage this object.
   *
   * @return object
   * @static
   */
   static function &singleton($package = NULL, $msgCallback = FALSE, $contextCallback = FALSE, $throwPEAR_Error = FALSE, $stackClass = 'PEAR_ErrorStack') {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Core_Error('CiviCRM');
    }
    return self::$_singleton;
  }

  /**
   * construcor
   */
  function __construct() {
    parent::__construct('CiviCRM');

    $log = CRM_Core_Config::getLog();
    $this->setLogger($log);

    // set up error handling for Pear Error Stack
    $this->setDefaultCallback(array($this, 'handlePES'));
  }

  function getMessages(&$error, $separator = '<br />') {
    if (is_a($error, 'CRM_Core_Error')) {
      $errors = $error->getErrors();
      $message = array();
      foreach ($errors as $e) {
        $message[] = $e['code'] . ': ' . $e['message'];
      }
      $message = implode($separator, $message);
      return $message;
    }
    return NULL;
  }

  function displaySessionError(&$error, $separator = '<br />') {
    $message = self::getMessages($error, $separator);
    if ($message) {
      $status = ts("Payment Processor Error message") . "{$separator} $message";
      $session = CRM_Core_Session::singleton();
      $session->setStatus($status);
    }
  }

  /**
   * create the main callback method. this method centralizes error processing.
   *
   * the errors we expect are from the pear modules DB, DB_DataObject
   * which currently use PEAR::raiseError to notify of error messages.
   *
   * @param object PEAR_Error
   *
   * @return void
   * @access public
   */
  public static function handle($pearError) {

    // setup smarty with config, session and template location.
    $template = CRM_Core_Smarty::singleton();
    $config = CRM_Core_Config::singleton();

    if ($config->backtrace) {
      self::backtrace();
    }

    // create the error array
    $error               = array();
    $error['callback']   = $pearError->getCallback();
    $error['code']       = $pearError->getCode();
    $error['message']    = $pearError->getMessage();
    $error['mode']       = $pearError->getMode();
    $error['debug_info'] = $pearError->getDebugInfo();
    $error['type']       = $pearError->getType();
    $error['user_info']  = $pearError->getUserInfo();
    $error['to_string']  = $pearError->toString();
    if (function_exists('mysql_error') &&
      mysql_error()
    ) {
      $mysql_error = mysql_error() . ', ' . mysql_errno();
      $template->assign_by_ref('mysql_code', $mysql_error);

      // execute a dummy query to clear error stack
      mysql_query('select 1');
    }
    elseif (function_exists('mysqli_error')) {
      $dao = new CRM_Core_DAO();

      // we do it this way, since calling the function
      // getDatabaseConnection could potentially result
      // in an infinite loop
      global $_DB_DATAOBJECT;
      if (isset($_DB_DATAOBJECT['CONNECTIONS'][$dao->_database_dsn_md5])) {
        $conn = $_DB_DATAOBJECT['CONNECTIONS'][$dao->_database_dsn_md5];
        $link = $conn->connection;

        if (mysqli_error($link)) {
          $mysql_error = mysqli_error($link) . ', ' . mysqli_errno($link);
          $template->assign_by_ref('mysql_code', $mysql_error);

          // execute a dummy query to clear error stack
          mysqli_query($link, 'select 1');
        }
      }
    }

    $template->assign_by_ref('error', $error);
    $errorDetails = CRM_Core_Error::debug('', $error, FALSE);
    $template->assign_by_ref('errorDetails', $errorDetails);

    CRM_Core_Error::debug_var('Fatal Error Details', $error);
    CRM_Core_Error::backtrace('backTrace', TRUE);

    if ($config->initialized) {
      $content = $template->fetch('CRM/common/fatal.tpl');
      echo CRM_Utils_System::theme($content);
    }
    else {
      echo "Sorry. A non-recoverable error has occurred. The error trace below might help to resolve the issue<p>";
      CRM_Core_Error::debug(NULL, $error);
    }

    self::abend(1);
  }

  // this function is used to trap and print errors
  // during system initialization time. Hence the error
  // message is quite ugly
  public static function simpleHandler($pearError) {

    // create the error array
    $error               = array();
    $error['callback']   = $pearError->getCallback();
    $error['code']       = $pearError->getCode();
    $error['message']    = $pearError->getMessage();
    $error['mode']       = $pearError->getMode();
    $error['debug_info'] = $pearError->getDebugInfo();
    $error['type']       = $pearError->getType();
    $error['user_info']  = $pearError->getUserInfo();
    $error['to_string']  = $pearError->toString();

    CRM_Core_Error::debug('Initialization Error', $error);

    // always log the backtrace to a file
    self::backtrace('backTrace', TRUE);

    exit(0);
  }

  /**
   * Handle errors raised using the PEAR Error Stack.
   *
   * currently the handler just requests the PES framework
   * to push the error to the stack (return value PEAR_ERRORSTACK_PUSH).
   *
   * Note: we can do our own error handling here and return PEAR_ERRORSTACK_IGNORE.
   *
   * Also, if we do not return any value the PEAR_ErrorStack::push() then does the
   * action of PEAR_ERRORSTACK_PUSHANDLOG which displays the errors on the screen,
   * since the logger set for this error stack is 'display' - see CRM_Core_Config::getLog();
   *
   */
  public static function handlePES($pearError) {
    return PEAR_ERRORSTACK_PUSH;
  }

  /**
   * display an error page with an error message describing what happened
   *
   * @param string message  the error message
   * @param string code     the error code if any
   * @param string email    the email address to notify of this situation
   *
   * @return void
   * @static
   * @acess public
   */
  static function fatal($message = NULL, $code = NULL, $email = NULL) {
    $vars = array(
      'message' => $message,
      'code' => $code,
    );

    if (self::$modeException) {
      // CRM-11043
      CRM_Core_Error::debug_var('Fatal Error Details', $vars);
      CRM_Core_Error::backtrace('backTrace', TRUE);

      $details = 'A fatal error was triggered';
      if ($message) {
        $details .= ': ' . $message;
    }
      throw new Exception($details, $code);
    }

    if (!$message) {
      $message = ts('We experienced an unexpected error. Please post a detailed description and the backtrace on the CiviCRM forums: %1', array(1 => 'http://forum.civicrm.org/'));
    }

    if (php_sapi_name() == "cli") {
      print ("Sorry. A non-recoverable error has occurred.\n$message \n$code\n$email\n\n");
      debug_print_backtrace();
      die("\n");
      // FIXME: Why doesn't this call abend()?
      // Difference: abend() will cleanup transaction and (via civiExit) store session state
      // self::abend(CRM_Core_Error::FATAL_ERROR);
    }

    $config = CRM_Core_Config::singleton();

    if ($config->fatalErrorHandler &&
      function_exists($config->fatalErrorHandler)
    ) {
      $name = $config->fatalErrorHandler;
      $ret = $name($vars);
      if ($ret) {
        // the call has been successfully handled
        // so we just exit
        self::abend(CRM_Core_Error::FATAL_ERROR);
      }
    }

    if ($config->backtrace) {
      self::backtrace();
    }

    $template = CRM_Core_Smarty::singleton();
    $template->assign($vars);

    CRM_Core_Error::debug_var('Fatal Error Details', $vars);
    CRM_Core_Error::backtrace('backTrace', TRUE);
    $content = $template->fetch($config->fatalErrorTemplate);
    if ($config->userFramework == 'Joomla' && class_exists('JError')) {
      JError::raiseError('CiviCRM-001', $content);
    }
    else {
      echo CRM_Utils_System::theme($content);
    }

    self::abend(CRM_Core_Error::FATAL_ERROR);
  }

  /**
   * display an error page with an error message describing what happened
   *
   * This function is evil -- it largely replicates fatal(). Hopefully the
   * entire CRM_Core_Error system can be hollowed out and replaced with
   * something that follows a cleaner separation of concerns.
   *
   * @param Exception $exception
   *
   * @return void
   * @static
   * @acess public
   */
  static function handleUnhandledException($exception) {
    $config = CRM_Core_Config::singleton();
    $vars = array(
      'message' => $exception->getMessage(),
      'code' => NULL,
      'exception' => $exception,
    );
    if (!$vars['message']) {
      $vars['message'] = ts('We experienced an unexpected error. Please post a detailed description and the backtrace on the CiviCRM forums: %1', array(1 => 'http://forum.civicrm.org/'));
    }

    // Case A: CLI
    if (php_sapi_name() == "cli") {
      printf("Sorry. A non-recoverable error has occurred.\n%s\n", $vars['message']);
      print self::formatTextException($exception);
      die("\n");
      // FIXME: Why doesn't this call abend()?
      // Difference: abend() will cleanup transaction and (via civiExit) store session state
      // self::abend(CRM_Core_Error::FATAL_ERROR);
    }

    // Case B: Custom error handler
    if ($config->fatalErrorHandler &&
      function_exists($config->fatalErrorHandler)
    ) {
      $name = $config->fatalErrorHandler;
      $ret = $name($vars);
      if ($ret) {
        // the call has been successfully handled
        // so we just exit
        self::abend(CRM_Core_Error::FATAL_ERROR);
      }
    }

    // Case C: Default error handler

    // log to file
    CRM_Core_Error::debug_var('Fatal Error Details', $vars);
    CRM_Core_Error::backtrace('backTrace', TRUE);

    // print to screen
    $template = CRM_Core_Smarty::singleton();
    $template->assign($vars);
    $content = $template->fetch($config->fatalErrorTemplate);
    if ($config->backtrace) {
      $content = self::formatHtmlException($exception) . $content;
    }
    if ($config->userFramework == 'Joomla' &&
      class_exists('JError')
    ) {
      JError::raiseError('CiviCRM-001', $content);
    }
    else {
      echo CRM_Utils_System::theme($content);
    }

    // fin
    self::abend(CRM_Core_Error::FATAL_ERROR);
  }

  /**
   * outputs pre-formatted debug information. Flushes the buffers
   * so we can interrupt a potential POST/redirect
   *
   * @param  string name of debug section
   * @param  mixed  reference to variables that we need a trace of
   * @param  bool   should we log or return the output
   * @param  bool   whether to generate a HTML-escaped output
   *
   * @return string the generated output
   * @access public
   * @static
   */
  static function debug($name, $variable = NULL, $log = TRUE, $html = TRUE) {
    $error = self::singleton();

    if ($variable === NULL) {
      $variable = $name;
      $name = NULL;
    }

    $out = print_r($variable, TRUE);
    $prefix = NULL;
    if ($html) {
      $out = htmlspecialchars($out);
      if ($name) {
        $prefix = "<p>$name</p>";
      }
      $out = "{$prefix}<p><pre>$out</pre></p><p></p>";
    }
    else {
      if ($name) {
        $prefix = "$name:\n";
      }
      $out = "{$prefix}$out\n";
    }
    if ($log && CRM_Core_Permission::check('view debug output')) {
      echo $out;
    }

    return $out;
  }

  /**
   * Similar to the function debug. Only difference is
   * in the formatting of the output.
   *
   * @param  string variable name
   * @param  mixed  reference to variables that we need a trace of
   * @param  bool   should we use print_r ? (else we use var_dump)
   * @param  bool   should we log or return the output
   *
   * @return string the generated output
   *
   * @access public
   *
   * @static
   *
   * @see CRM_Core_Error::debug()
   * @see CRM_Core_Error::debug_log_message()
   */
  static function debug_var($variable_name,
    $variable,
    $print = TRUE,
    $log   = TRUE,
    $comp  = ''
  ) {
    // check if variable is set
    if (!isset($variable)) {
      $out = "\$$variable_name is not set";
    }
    else {
      if ($print) {
        $out = print_r($variable, TRUE);
        $out = "\$$variable_name = $out";
      }
      else {
        // use var_dump
        ob_start();
        var_dump($variable);
        $dump = ob_get_contents();
        ob_end_clean();
        $out = "\n\$$variable_name = $dump";
      }
      // reset if it is an array
      if (is_array($variable)) {
        reset($variable);
      }
    }
    return self::debug_log_message($out, FALSE, $comp);
  }

  /**
   * display the error message on terminal
   *
   * @param  string message to be output
   * @param  bool   should we log or return the output
   *
   * @return string format of the backtrace
   *
   * @access public
   *
   * @static
   */
  static function debug_log_message($message, $out = FALSE, $comp = '') {
    $config = CRM_Core_Config::singleton();

    $file_log = self::createDebugLogger($comp);
    $file_log->log("$message\n");
    $str = "<p/><code>$message</code>";
    if ($out && CRM_Core_Permission::check('view debug output')) {
      echo $str;
    }
    $file_log->close();

    if ($config->userFrameworkLogging) {
      if ($config->userSystem->is_drupal and function_exists('watchdog')) {
        watchdog('civicrm', $message, NULL, WATCHDOG_DEBUG);
      }
    }

    return $str;
  }

  /**
   * Append to the query log (if enabled)
   */
  static function debug_query($string) {
    if ( defined( 'CIVICRM_DEBUG_LOG_QUERY' ) ) {
      if ( CIVICRM_DEBUG_LOG_QUERY == 'backtrace' ) {
        CRM_Core_Error::backtrace( $string, true );
      } else if ( CIVICRM_DEBUG_LOG_QUERY ) {
        CRM_Core_Error::debug_var( 'Query', $string, false, true );
      }
    }
  }

  /**
   * Obtain a reference to the error log
   *
   * @return Log
   */
  static function createDebugLogger($comp = '') {
    $config = CRM_Core_Config::singleton();

    if ($comp) {
      $comp = $comp . '.';
    }

    $fileName = "{$config->configAndLogDir}CiviCRM." . $comp . md5($config->dsn) . '.log';

    // Roll log file monthly or if greater than 256M
    // note that PHP file functions have a limit of 2G and hence
    // the alternative was introduce
    if (file_exists($fileName)) {
      $fileTime = date("Ym", filemtime($fileName));
      $fileSize = filesize($fileName);
      if (($fileTime < date('Ym')) ||
        ($fileSize > 256 * 1024 * 1024) ||
        ($fileSize < 0)
      ) {
        rename($fileName,
          $fileName . '.' . date('Ymdhs', mktime(0, 0, 0, date("m") - 1, date("d"), date("Y")))
        );
      }
    }

    return Log::singleton('file', $fileName);
  }

  static function backtrace($msg = 'backTrace', $log = FALSE) {
    $backTrace = debug_backtrace();
    $message = self::formatBacktrace($backTrace);
    if (!$log) {
      CRM_Core_Error::debug($msg, $message);
    }
    else {
      CRM_Core_Error::debug_var($msg, $message);
    }
  }

  /**
   * Render a backtrace array as a string
   *
   * @param array $backTrace array of stack frames
   * @param boolean $showArgs TRUE if we should try to display content of function arguments (which could be sensitive); FALSE to display only the type of each function argument
   * @param int $maxArgLen maximum number of characters to show from each argument string
   * @return string printable plain-text
   * @see debug_backtrace
   * @see Exception::getTrace()
   */
  static function formatBacktrace($backTrace, $showArgs = TRUE, $maxArgLen = 80) {
    $message = '';
    foreach ($backTrace as $idx => $trace) {
      $args = array();
      $fnName = CRM_Utils_Array::value('function', $trace);
      $className = array_key_exists('class', $trace) ? ($trace['class'] . $trace['type']) : '';

      // do now show args for a few password related functions
      $skipArgs = ($className == 'DB::' && $fnName == 'connect') ? TRUE : FALSE;

      foreach ($trace['args'] as $arg) {
        if (! $showArgs || $skipArgs) {
          $args[] = '(' . gettype($arg) . ')';
          continue;
        }
        switch ($type = gettype($arg)) {
          case 'boolean':
            $args[] = $arg ? 'TRUE' : 'FALSE';
            break;
          case 'integer':
          case 'double':
            $args[] = $arg;
            break;
          case 'string':
            $args[] = '"' . CRM_Utils_String::ellipsify(addcslashes((string) $arg, "\r\n\t\""), $maxArgLen). '"';
            break;
          case 'array':
            $args[] = '(Array:'.count($arg).')';
            break;
          case 'object':
            $args[] = 'Object(' . get_class($arg) . ')';
            break;
          case 'resource':
            $args[] = 'Resource';
            break;
          case 'NULL':
            $args[] = 'NULL';
            break;
          default:
            $args[] = "($type)";
            break;
        }
      }

      $message .= sprintf(
        "#%s %s(%s): %s%s(%s)\n",
        $idx,
        CRM_Utils_Array::value('file', $trace, '[internal function]'),
        CRM_Utils_Array::value('line', $trace, ''),
        $className,
        $fnName,
        implode(", ", $args)
      );
    }
    $message .= sprintf("#%s {main}\n", 1+$idx);
    return $message;
  }

  /**
   * Render an exception as HTML string
   *
   * @param Exception $e
   * @return string printable HTML text
   */
  static function formatHtmlException(Exception $e) {
    $msg = '';

    // Exception metadata

    // Exception backtrace
    if ($e instanceof PEAR_Exception) {
      $ei = $e;
      while (is_callable(array($ei, 'getCause'))) {
        if ($ei->getCause() instanceof PEAR_Error) {
          $msg .= '<table class="crm-db-error">';
          $msg .= sprintf('<thead><tr><th>%s</th><th>%s</th></tr></thead>', ts('Error Field'), ts('Error Value'));
          $msg .= '<tbody>';
          foreach (array('Type', 'Code', 'Message', 'Mode', 'UserInfo', 'DebugInfo') as $f) {
            $msg .= sprintf('<tr><td>%s</td><td>%s</td></tr>', $f, call_user_func(array($ei->getCause(), "get$f")));
    }
          $msg .= '</tbody></table>';
    }
        $ei = $ei->getCause();
  }
      $msg .= $e->toHtml();
    } else {
      $msg .= '<p><b>' . get_class($e) . ': "' . htmlentities($e->getMessage()) . '"</b></p>';
      $msg .= '<pre>' . htmlentities(self::formatBacktrace($e->getTrace())) . '</pre>';
    }
    return $msg;
  }

  /**
   * Write details of an exception to the log
   *
   * @param Exception $e
   * @return string printable plain text
   */
  static function formatTextException(Exception $e) {
    $msg = get_class($e) . ": \"" . $e->getMessage() . "\"\n";

    $ei = $e;
    while (is_callable(array($ei, 'getCause'))) {
      if ($ei->getCause() instanceof PEAR_Error) {
        foreach (array('Type', 'Code', 'Message', 'Mode', 'UserInfo', 'DebugInfo') as $f) {
          $msg .= sprintf(" * ERROR %s: %s\n", strtoupper($f), call_user_func(array($ei->getCause(), "get$f")));
        }
      }
      $ei = $ei->getCause();
    }
    $msg .= self::formatBacktrace($e->getTrace());
    return $msg;
  }

  static function createError($message, $code = 8000, $level = 'Fatal', $params = NULL) {
    $error = CRM_Core_Error::singleton();
    $error->push($code, $level, array($params), $message);
    return $error;
  }

  /**
   * Set a status message in the session, then bounce back to the referrer.
   *
   * @param string $status        The status message to set
   *
   * @return void
   * @access public
   * @static
   */
  public static function statusBounce($status, $redirect = NULL) {
    $session = CRM_Core_Session::singleton();
    if (!$redirect) {
      $redirect = $session->readUserContext();
    }
    $session->setStatus($status);
    CRM_Utils_System::redirect($redirect);
  }

  /**
   * Function to reset the error stack
   *
   * @access public
   * @static
   */
  public static function reset() {
    $error = self::singleton();
    $error->_errors = array();
    $error->_errorsByLevel = array();
  }

  public static function ignoreException($callback = NULL) {
    if (!$callback) {
      $callback = array('CRM_Core_Error', 'nullHandler');
    }

    $GLOBALS['_PEAR_default_error_mode'] = PEAR_ERROR_CALLBACK;
    $GLOBALS['_PEAR_default_error_options'] = $callback;
  }

  public static function exceptionHandler($pearError) {
    CRM_Core_Error::backtrace('backTrace', TRUE);
    throw new PEAR_Exception($pearError->getMessage(), $pearError);
  }

  /**
   * Error handler to quietly catch otherwise fatal smtp transport errors.
   *
   * @param object $obj       The PEAR_ERROR object
   *
   * @return object $obj
   * @access public
   * @static
   */
  public static function nullHandler($obj) {
    CRM_Core_Error::debug_log_message("Ignoring exception thrown by nullHandler: {$obj->code}, {$obj->message}");
    CRM_Core_Error::backtrace('backTrace', TRUE);
    return $obj;
  }

  /**
   * (Re)set the default callback method
   *
   * @return void
   * @access public
   * @static
   */
  public static function setCallback($callback = NULL) {
    if (!$callback) {
      $callback = array('CRM_Core_Error', 'handle');
    }
    $GLOBALS['_PEAR_default_error_mode'] = PEAR_ERROR_CALLBACK;
    $GLOBALS['_PEAR_default_error_options'] = $callback;
  }

  /*
   * @deprecated
   * This function is no longer used by v3 api.
   * @fixme Some core files call it but it should be re-thought & renamed or removed
   */
  public static function &createAPIError($msg, $data = NULL) {
    if (self::$modeException) {
      throw new Exception($msg, $data);
    }

    $values = array();

    $values['is_error'] = 1;
    $values['error_message'] = $msg;
    if (isset($data)) {
      $values = array_merge($values, $data);
    }
    return $values;
  }

  public static function movedSiteError($file) {
    $url = CRM_Utils_System::url('civicrm/admin/setting/updateConfigBackend',
      'reset=1',
      TRUE
    );
    echo "We could not write $file. Have you moved your site directory or server?<p>";
    echo "Please fix the setting by running the <a href=\"$url\">update config script</a>";
    exit();
  }

  /**
   * Terminate execution abnormally
   */
  protected static function abend($code) {
    // do a hard rollback of any pending transactions
    // if we've come here, its because of some unexpected PEAR errors
    CRM_Core_Transaction::forceRollbackIfEnabled();
    CRM_Utils_System::civiExit($code);
  }

  public static function isAPIError($error, $type = CRM_Core_Error::FATAL_ERROR) {
    if (is_array($error) && CRM_Utils_Array::value('is_error', $error)) {
      $code = $error['error_message']['code'];
      if ($code == $type) {
        return TRUE;
      }
    }
    return FALSE;
  }
}

$e = new PEAR_ErrorStack('CRM');
$e->singleton('CRM', FALSE, NULL, 'CRM_Core_Error');
