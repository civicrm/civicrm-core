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
 * Start of the Error framework. We should check out and inherit from
 * PEAR_ErrorStack and use that framework
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Core\Exception\DBQueryException;

require_once 'PEAR/ErrorStack.php';
require_once 'PEAR/Exception.php';
require_once 'CRM/Core/Exception.php';

require_once 'Log.php';

/**
 * Class CRM_Core_Error
 */
class CRM_Core_Error extends PEAR_ErrorStack {

  /**
   * Status code of various types of errors.
   */
  const FATAL_ERROR = 2;
  const DUPLICATE_CONTACT = 8001;
  const DUPLICATE_CONTRIBUTION = 8002;
  const DUPLICATE_PARTICIPANT = 8003;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   * @var object
   */
  private static $_singleton = NULL;

  /**
   * The logger object for this application.
   * @var object
   */
  private static $_log = NULL;

  /**
   * If modeException == true, errors are raised as exception instead of returning civicrm_errors
   * @var bool
   */
  public static $modeException = NULL;

  /**
   * Singleton function used to manage this object.
   *
   * @param null $package
   * @param bool $msgCallback
   * @param bool $contextCallback
   * @param bool $throwPEAR_Error
   * @param string $stackClass
   *
   * @return CRM_Core_Error
   */
  public static function &singleton($package = NULL, $msgCallback = FALSE, $contextCallback = FALSE, $throwPEAR_Error = FALSE, $stackClass = 'PEAR_ErrorStack') {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Core_Error('CiviCRM');
    }
    return self::$_singleton;
  }

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct('CiviCRM');

    $log = CRM_Core_Config::getLog();
    $this->setLogger($log);

    // PEAR<=1.9.0 does not declare "static" properly.
    if (!is_callable(['PEAR', '__callStatic'])) {
      $this->setDefaultCallback([$this, 'handlePES']);
    }
    else {
      PEAR_ErrorStack::setDefaultCallback([$this, 'handlePES']);
    }
  }

  /**
   * @param $error
   * @param string $separator
   *
   * @return array|null|string
   */
  public static function getMessages(&$error, $separator = '<br />') {
    if (is_a($error, 'CRM_Core_Error')) {
      $errors = $error->getErrors();
      $message = [];
      foreach ($errors as $e) {
        $message[] = $e['code'] . ': ' . $e['message'];
      }
      $message = implode($separator, $message);
      return $message;
    }
    elseif (is_a($error, 'Civi\Payment\Exception\PaymentProcessorException')) {
      return $error->getMessage();
    }
    return NULL;
  }

  /**
   * Status display function specific to payment processor errors.
   * @param $error
   * @param string $separator
   */
  public static function displaySessionError(&$error, $separator = '<br />') {
    $message = self::getMessages($error, $separator);
    if ($message) {
      $status = ts('Payment Processor Error message') . "{$separator} $message";
      $session = CRM_Core_Session::singleton();
      $session->setStatus($status);
    }
  }

  /**
   * Create the main callback method. this method centralizes error processing.
   *
   * the errors we expect are from the pear modules DB, DB_DataObject
   * which currently use PEAR::raiseError to notify of error messages.
   *
   * @param object $pearError PEAR_Error
   */
  public static function handle($pearError) {
    if (defined('CIVICRM_TEST')) {
      return self::simpleHandler($pearError);
    }

    // setup smarty with config, session and template location.
    $template = CRM_Core_Smarty::singleton();
    $config = CRM_Core_Config::singleton();

    if ($config->backtrace) {
      self::backtrace();
    }

    // create the error array
    $error = self::getErrorDetails($pearError);

    // We access connection info via _DB_DATAOBJECT instead
    // of, e.g., calling getDatabaseConnection(), so that we
    // can avoid infinite loops.
    global $_DB_DATAOBJECT;

    if (isset($_DB_DATAOBJECT['CONFIG']['database'])) {
      $dao = new CRM_Core_DAO();
      if (isset($_DB_DATAOBJECT['CONNECTIONS'][$dao->_database_dsn_md5])) {
        $conn = $_DB_DATAOBJECT['CONNECTIONS'][$dao->_database_dsn_md5];

        // FIXME: Polymorphism for the win.
        if ($conn instanceof DB_mysqli) {
          $link = $conn->connection;
          if (mysqli_error($link)) {
            $mysql_error = mysqli_error($link) . ', ' . mysqli_errno($link);
            // execute a dummy query to clear error stack
            mysqli_query($link, 'select 1');
          }
        }
        elseif ($conn instanceof DB_mysql) {
          if (mysql_error()) {
            $mysql_error = mysql_error() . ', ' . mysql_errno();
            // execute a dummy query to clear error stack
            mysql_query('select 1');
          }
        }
        else {
          $mysql_error = 'fixme-unknown-db-cxn';
        }
        $template->assign('mysql_code', $mysql_error);
      }
    }

    // Use the custom fatalErrorHandler if defined
    if ($config->fatalErrorHandler && function_exists($config->fatalErrorHandler)) {
      $name = $config->fatalErrorHandler;
      $vars = [
        'pearError' => $pearError,
      ];
      $ret = $name($vars);
      if ($ret) {
        // the call has been successfully handled so we just exit
        self::abend(CRM_Core_Error::FATAL_ERROR);
      }
    }

    $template->assign('error', $error);
    $errorDetails = CRM_Core_Error::debug('', $error, FALSE);
    $template->assign('errorDetails', $errorDetails);

    CRM_Core_Error::debug_var('Fatal Error Details', $error, TRUE, TRUE, '', PEAR_LOG_ERR);
    CRM_Core_Error::backtrace('backTrace', TRUE);

    $exit = TRUE;
    if ($config->initialized) {
      $content = $template->fetch('CRM/common/fatal.tpl');
      echo CRM_Utils_System::theme($content);
      $exit = CRM_Utils_System::shouldExitAfterFatal();
    }
    else {
      echo "Sorry. A non-recoverable error has occurred. The error trace below might help to resolve the issue<p>";
      CRM_Core_Error::debug(NULL, $error);
    }
    static $runOnce = FALSE;
    if ($runOnce) {
      exit;
    }
    $runOnce = TRUE;

    if ($exit) {
      self::abend(CRM_Core_Error::FATAL_ERROR);
    }
    else {
      self::inpageExceptionDisplay(CRM_Core_Error::FATAL_ERROR);
    }
  }

  /**
   * this function is used to trap and print errors
   * during system initialization time. Hence the error
   * message is quite ugly
   *
   * @param $pearError
   */
  public static function simpleHandler($pearError) {

    $error = self::getErrorDetails($pearError);

    // ensure that debug does not check permissions since we are in bootstrap
    // mode and need to print a decent message to help the user
    CRM_Core_Error::debug('Initialization Error', $error, TRUE, TRUE, FALSE);

    // always log the backtrace to a file
    self::backtrace('backTrace', TRUE);

    exit(0);
  }

  /**
   * This function is used to return error details
   *
   * @param PEAR_Error $pearError
   *
   * @return array $error
   */
  public static function getErrorDetails($pearError) {
    // create the error array
    $error = [];
    $error['callback'] = $pearError->getCallback();
    $error['code'] = $pearError->getCode();
    $error['message'] = $pearError->getMessage();
    $error['mode'] = $pearError->getMode();
    $error['debug_info'] = $pearError->getDebugInfo();
    $error['type'] = $pearError->getType();
    $error['user_info'] = $pearError->getUserInfo();
    $error['to_string'] = $pearError->toString();

    return $error;
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
   * @param mixed $pearError
   *
   * @return int
   */
  public static function handlePES($pearError) {
    return PEAR_ERRORSTACK_PUSH;
  }

  /**
   * Display an error page with an error message describing what happened.
   *
   * @deprecated
   *  This is a really annoying function. We ❤ exceptions. Be exceptional!
   *
   * @see CRM-20181
   *
   * @param string $message
   *   The error message.
   * @param string $code
   *   The error code if any.
   * @param string $email
   *   The email address to notify of this situation.
   *
   * @throws Exception
   */
  public static function fatal($message = NULL, $code = NULL, $email = NULL) {
    CRM_Core_Error::deprecatedFunctionWarning('throw new CRM_Core_Exception or use CRM_Core_Error::statusBounce', 'CRM_Core_Error::fatal');
    $vars = [
      'message' => $message,
      'code' => $code,
    ];

    if (self::$modeException) {
      // CRM-11043
      CRM_Core_Error::debug_var('Fatal Error Details', $vars, TRUE, TRUE, '', PEAR_LOG_ERR);
      CRM_Core_Error::backtrace('backTrace', TRUE);

      $details = 'A fatal error was triggered';
      if ($message) {
        $details .= ': ' . $message;
      }
      throw new Exception($details, $code);
    }

    if (!$message) {
      $message = ts('We experienced an unexpected error. You may have found a bug. For more information on how to provide a bug report, please read: %1', [1 => 'https://civicrm.org/bug-reporting']);
    }

    if (php_sapi_name() == "cli") {
      print ("Sorry. A non-recoverable error has occurred.\n$message \n$code\n$email\n\n");
      // Fix for CRM-16899
      echo static::formatBacktrace(debug_backtrace());
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

    CRM_Core_Error::debug_var('Fatal Error Details', $vars, TRUE, TRUE, '', PEAR_LOG_ERR);
    CRM_Core_Error::backtrace('backTrace', TRUE);

    // If we are in an ajax callback, format output appropriately
    if (($_REQUEST['snippet'] ?? NULL) === CRM_Core_Smarty::PRINT_JSON) {
      $out = [
        'status' => 'fatal',
        'content' => '<div class="messages status no-popup">' . CRM_Core_Page::crmIcon('fa-info-circle') . ' ' . ts('Sorry but we are not able to provide this at the moment.') . '</div>',
      ];
      if ($config->backtrace && CRM_Core_Permission::check('view debug output')) {
        $out['backtrace'] = self::parseBacktrace(debug_backtrace());
        $message .= '<p><em>See console for backtrace</em></p>';
      }
      CRM_Core_Session::setStatus($message, ts('Sorry an error occurred'), 'error');
      CRM_Core_Transaction::forceRollbackIfEnabled();
      CRM_Core_Page_AJAX::returnJsonResponse($out);
    }

    $template = CRM_Core_Smarty::singleton();
    $template->assign($vars);
    $config->userSystem->outputError($template->fetch('CRM/common/fatal.tpl'));

    self::abend(CRM_Core_Error::FATAL_ERROR);
  }

  /**
   * Display an error page with an error message describing what happened.
   *
   * This function is evil -- it largely replicates fatal(). Hopefully the
   * entire CRM_Core_Error system can be hollowed out and replaced with
   * something that follows a cleaner separation of concerns.
   *
   * @param Exception $exception
   */
  public static function handleUnhandledException($exception) {
    try {
      CRM_Utils_Hook::unhandledException($exception);
    }
    catch (Exception $other) {
      // if the exception-handler generates an exception, then that sucks! oh, well. carry on.
      CRM_Core_Error::debug_var('handleUnhandledException_nestedException', self::formatTextException($other), TRUE, TRUE, '', PEAR_LOG_ERR);
    }
    $config = CRM_Core_Config::singleton();
    $vars = [
      'message' => $exception->getMessage(),
      'code' => NULL,
      'exception' => $exception,
    ];
    if (!$vars['message']) {
      $vars['message'] = ts('We experienced an unexpected error. You may have found a bug. For more information on how to provide a bug report, please read: %1', [1 => 'https://civicrm.org/bug-reporting']);
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
    CRM_Core_Error::debug_var('Fatal Error Details', $vars, FALSE, TRUE, '', PEAR_LOG_ERR);
    CRM_Core_Error::backtrace('backTrace', TRUE);

    // print to screen
    $template = CRM_Core_Smarty::singleton();
    $template->assign($vars);
    $content = $template->fetch('CRM/common/fatal.tpl');

    if ($config->backtrace) {
      $content = self::formatHtmlException($exception) . $content;
    }

    // set the response code before starting the request
    http_response_code(500);

    echo CRM_Utils_System::theme($content);
    $exit = CRM_Utils_System::shouldExitAfterFatal();

    if ($exit) {
      self::abend(CRM_Core_Error::FATAL_ERROR);
    }
    else {
      self::inpageExceptionDisplay(CRM_Core_Error::FATAL_ERROR);
    }
  }

  /**
   * Outputs pre-formatted debug information. Flushes the buffers
   * so we can interrupt a potential POST/redirect
   *
   * @param string $name name of debug section
   * @param mixed $variable reference to variables that we need a trace of
   * @param bool $log
   *    - TRUE: log to error_log and echo one of: everything / a code / nothing, depending on permissions.
   *    - FALSE: only return the output as a string. Nothing to error_log or echo.
   * @param bool $html whether to HTML-escape output (typically use TRUE unless you know your $variable is safe)
   * @param bool $checkPermission should we check permissions before displaying output
   *    useful when we die during initialization and permissioning
   *    subsystem is not initialized - CRM-13765
   *
   * @return string
   *   the generated output
   *
   * @deprecated prefer Civi::log().
   * @see https://github.com/civicrm/civicrm-core/pull/27138
   */
  public static function debug($name, $variable = NULL, $log = TRUE, $html = TRUE, $checkPermission = TRUE) {
    if ($variable === NULL) {
      $variable = $name;
      $name = NULL;
    }

    $out = print_r($variable, TRUE);
    $outHTML = htmlspecialchars($out);

    if ($name) {
      $outHTML = "<p>" . htmlspecialchars($name) . "</p><p><pre>$outHTML</pre></p>";
      $out = "$name:\n$out";
    }

    if ($log) {
      // Log the output to error_log with a unique reference.
      $unique = substr(md5(random_bytes(32)), 0, 12);
      error_log("errorID:$unique\n$out");

      if (!$checkPermission) {
        // Permission system inactive, only emit a reference to content in logfile
        echo "Critical error. Please see server logs for errorID:$unique";
      }
      else {
        if (CRM_Core_Permission::check('view debug output')) {
          // We are outputting to the browser.
          echo $html ? $outHTML : $out;
        }
        else {
          // No permission; show nothing visible, but include the error in an HTML
          // comment in case a dev wants to inspect.
          echo $html ? "<!-- critical error reference $unique -->" : '';
        }
      }
    }

    return $html ? $outHTML : $out;
  }

  /**
   * Similar to the function debug. Only difference is
   * in the formatting of the output.
   *
   * @param string $variable_name
   *   Variable name.
   * @param mixed $variable
   *   Variable value.
   * @param bool $print
   *   Use print_r (if true) or var_dump (if false).
   * @param bool $log
   *   Log or return the output?
   * @param string $prefix
   *   Prefix for output logfile.
   * @param int $priority
   *   The log priority level.
   *
   * @return string
   *   The generated output
   *
   * @see CRM_Core_Error::debug()
   * @see CRM_Core_Error::debug_log_message()
   */
  public static function debug_var($variable_name, $variable, $print = TRUE, $log = TRUE, $prefix = '', $priority = NULL) {
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
        // Use Symfony var-dumper to avoid circular references that exhaust
        // memory when using var_dump().
        // Use its CliDumper since if we use the simpler `dump()` then it
        // comes out as some overly decorated html which is hard to read.
        $dump = (new \Symfony\Component\VarDumper\Dumper\CliDumper('php://output'))
          ->dump(
            (new \Symfony\Component\VarDumper\Cloner\VarCloner())->cloneVar($variable),
            TRUE);
        $out = "\n\$$variable_name = $dump";
      }
      // reset if it is an array
      if (is_array($variable)) {
        reset($variable);
      }
    }
    return self::debug_log_message($out, FALSE, $prefix, $priority);
  }

  /**
   * Display the error message on terminal and append it to the log file.
   *
   * Provided the user has the 'view debug output' the output should be displayed. In all
   * cases it should be logged.
   *
   * @deprecated see https://docs.civicrm.org/dev/en/latest/framework/logging/
   *
   * Use (e.g) `Civi::log()->error()` (priority dependent).
   *
   * @param string $message
   * @param bool $out
   *   Should we log or return the output.
   *
   * @param string $prefix
   *   Message prefix.
   * @param string $priority
   *
   * @return string
   *   Format of the backtrace
   */
  public static function debug_log_message($message, $out = FALSE, $prefix = '', $priority = NULL) {
    $config = CRM_Core_Config::singleton();

    $file_log = self::createDebugLogger($prefix);
    $file_log->log("$message\n", $priority);

    $str = '<p/><code>' . htmlspecialchars($message) . '</code>';
    if ($out && CRM_Core_Permission::check('view debug output')) {
      echo $str;
    }
    $file_log->close();

    if (!isset(\Civi::$statics[__CLASS__]['userFrameworkLogging'])) {
      // Set it to FALSE first & then try to set it. This is to prevent a loop as calling
      // $config->userFrameworkLogging can trigger DB queries & under log mode this
      // then gets called again.
      \Civi::$statics[__CLASS__]['userFrameworkLogging'] = FALSE;
      \Civi::$statics[__CLASS__]['userFrameworkLogging'] = $config->userFrameworkLogging;
    }

    if (!empty(\Civi::$statics[__CLASS__]['userFrameworkLogging'])) {
      $config->userSystem->logger($message, $priority);
    }

    return $str;
  }

  /**
   * Append to the query log (if enabled)
   *
   * @param string $string
   */
  public static function debug_query($string) {
    $debugLogQuery = CRM_Utils_Constant::value('CIVICRM_DEBUG_LOG_QUERY', FALSE);
    if ($debugLogQuery === 'backtrace') {
      CRM_Core_Error::backtrace($string, TRUE);
    }
    elseif ($debugLogQuery) {
      CRM_Core_Error::debug_var('Query', $string, TRUE, TRUE, 'sql_log' . $debugLogQuery, PEAR_LOG_DEBUG);
    }
  }

  /**
   * Execute a query and log the results.
   *
   * @param string $query
   */
  public static function debug_query_result($query) {
    $results = CRM_Core_DAO::executeQuery($query)->fetchAll();
    CRM_Core_Error::debug_var('dao result', ['query' => $query, 'results' => $results], TRUE, TRUE, '', PEAR_LOG_DEBUG);
  }

  /**
   * Obtain a reference to the error log.
   *
   * @param string $prefix
   *
   * @return Log_file
   */
  public static function createDebugLogger($prefix = '') {
    self::generateLogFileName($prefix);
    $log = Log::singleton('file', \Civi::$statics[__CLASS__]['logger_file' . $prefix], '', [
      'timeFormat' => 'Y-m-d H:i:sO',
      'mode' => CRM_Utils_Constant::value('CIVICRM_LOG_FILE_PERMISSIONS', '0664'),
      'dirmode' => CRM_Utils_Constant::value('CIVICRM_LOG_FILE_DIR_PERMISSIONS', '0775'),
    ]);
    return $log;
  }

  /**
   * Generate a hash for the logfile.
   *
   * CRM-13640.
   *
   * @param CRM_Core_Config $config
   *
   * @return string
   */
  public static function generateLogFileHash($config) {
    // Use multiple (but stable) inputs for hash information.
    $md5inputs = [
      defined('CIVICRM_SITE_KEY') ? CIVICRM_SITE_KEY : 'NO_SITE_KEY',
      CRM_Utils_System::languageNegotiationURL($config->userFrameworkBaseURL, FALSE, TRUE),
      md5($config->dsn),
      $config->dsn,
    ];
    // Trim 8 chars off the string, make it slightly easier to find
    // but reveals less information from the hash.
    return substr(md5(var_export($md5inputs, 1)), 8);
  }

  /**
   * Generate the name of the logfile to use and store it as a static.
   *
   * This function includes simplistic log rotation and a check as to whether
   * the file exists.
   *
   * @param string $prefix
   * @return string The full path to the file.
   */
  public static function generateLogFileName($prefix): string {
    if (!isset(\Civi::$statics[__CLASS__]['logger_file' . $prefix])) {
      $config = CRM_Core_Config::singleton();

      $prefixString = $prefix ? ($prefix . '.') : '';

      if (CRM_Utils_Constant::value('CIVICRM_LOG_HASH', TRUE)) {
        $hash = self::generateLogFileHash($config) . '.';
      }
      else {
        $hash = '';
      }
      $fileName = $config->configAndLogDir . 'CiviCRM.' . CIVICRM_DOMAIN_ID . '.' . $prefixString . $hash . 'log';

      // Roll log file monthly or if greater than our threshold.
      // Size-based rotation introduced in response to filesize limits on
      // certain OS/PHP combos.
      $maxBytes = CRM_Utils_Constant::value('CIVICRM_LOG_ROTATESIZE', 256 * 1024 * 1024);
      if ($maxBytes) {
        if (file_exists($fileName)) {
          $fileTime = date("Ym", filemtime($fileName));
          $fileSize = filesize($fileName);
          if (($fileTime < date('Ym')) ||
            ($fileSize > $maxBytes) ||
            ($fileSize < 0)
          ) {
            rename($fileName,
              $fileName . '.' . date('YmdHi')
            );
          }
        }
      }
      \Civi::$statics[__CLASS__]['logger_file' . $prefix] = $fileName;
    }
    return \Civi::$statics[__CLASS__]['logger_file' . $prefix];
  }

  /**
   * @param string $msg
   * @param bool $log
   */
  public static function backtrace($msg = 'backTrace', $log = FALSE) {
    $backTrace = debug_backtrace();
    $message = self::formatBacktrace($backTrace);
    if (!$log) {
      CRM_Core_Error::debug($msg, $message);
    }
    else {
      CRM_Core_Error::debug_var($msg, $message, TRUE, TRUE, '', PEAR_LOG_DEBUG);
    }
  }

  /**
   * Render a backtrace array as a string.
   *
   * @param array $backTrace
   *   Array of stack frames.
   * @param bool $showArgs
   *   TRUE if we should try to display content of function arguments (which could be sensitive); FALSE to display only the type of each function argument.
   * @param int $maxArgLen
   *   Maximum number of characters to show from each argument string.
   * @return string
   *   printable plain-text
   */
  public static function formatBacktrace($backTrace, $showArgs = TRUE, $maxArgLen = 80) {
    $message = '';
    foreach (self::parseBacktrace($backTrace, $showArgs, $maxArgLen) as $idx => $trace) {
      $message .= sprintf("#%s %s\n", $idx, $trace);
    }
    $message .= sprintf("#%s {main}\n", 1 + $idx);
    return $message;
  }

  /**
   * Render a backtrace array as an array.
   *
   * @param array $backTrace
   *   Array of stack frames.
   * @param bool $showArgs
   *   TRUE if we should try to display content of function arguments (which could be sensitive); FALSE to display only the type of each function argument.
   * @param int $maxArgLen
   *   Maximum number of characters to show from each argument string.
   * @return array
   * @see debug_backtrace
   * @see Exception::getTrace()
   */
  public static function parseBacktrace($backTrace, $showArgs = TRUE, $maxArgLen = 80) {
    $ret = [];
    foreach ($backTrace as $trace) {
      $args = [];
      $fnName = $trace['function'] ?? NULL;
      $className = isset($trace['class']) ? ($trace['class'] . $trace['type']) : '';

      // Do not show args for a few password related functions
      $skipArgs = $className == 'DB::' && $fnName == 'connect';

      if (!empty($trace['args'])) {
        foreach ($trace['args'] as $arg) {
          if (!$showArgs || $skipArgs) {
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
              $args[] = '"' . CRM_Utils_String::ellipsify(addcslashes((string) $arg, "\r\n\t\""), $maxArgLen) . '"';
              break;

            case 'array':
              $args[] = '(Array:' . count($arg) . ')';
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
      }

      $ret[] = sprintf(
        "%s(%s): %s%s(%s)",
        CRM_Utils_Array::value('file', $trace, '[internal function]'),
        CRM_Utils_Array::value('line', $trace, ''),
        $className,
        $fnName,
        implode(", ", $args)
      );
    }
    return $ret;
  }

  /**
   * Render an exception as HTML string.
   *
   * @param Throwable $e
   * @return string
   *   printable HTML text
   */
  public static function formatHtmlException(Throwable $e) {
    $msg = '';

    // Exception metadata

    // Exception backtrace
    if ($e instanceof PEAR_Exception) {
      $ei = $e;
      if (is_callable([$ei, 'getCause'])) {
        // DB_ERROR doesn't have a getCause but does have a __call function which tricks is_callable.
        if (!$ei instanceof DB_Error) {
          if ($ei->getCause() instanceof PEAR_Error) {
            $msg .= '<table class="crm-db-error">';
            $msg .= sprintf('<thead><tr><th>%s</th><th>%s</th></tr></thead>', ts('Error Field'), ts('Error Value'));
            $msg .= '<tbody>';
            foreach (['Type', 'Code', 'Message', 'Mode', 'UserInfo', 'DebugInfo'] as $f) {
              $msg .= sprintf('<tr><td>%s</td><td>%s</td></tr>', $f, call_user_func([$ei->getCause(), "get$f"]));
            }
            $msg .= '</tbody></table>';
          }
          $ei = $ei->getCause();
        }
      }
      $msg .= $e->toHtml();
    }
    else {
      $msg .= '<p><b>' . get_class($e) . ': "' . htmlentities($e->getMessage()) . '"</b></p>';
      $msg .= '<pre>' . htmlentities(self::formatBacktrace($e->getTrace())) . '</pre>';
    }
    return $msg;
  }

  /**
   * Write details of an exception to the log.
   *
   * @param Throwable $e
   * @return string
   *   printable plain text
   */
  public static function formatTextException(Throwable $e) {
    $msg = get_class($e) . ": \"" . $e->getMessage() . "\"\n";

    $ei = $e;
    while (is_callable([$ei, 'getCause'])) {
      // DB_ERROR doesn't have a getCause but does have a __call function which tricks is_callable.
      if (!$ei instanceof DB_Error) {
        if ($ei->getCause() instanceof PEAR_Error) {
          foreach (['Type', 'Code', 'Message', 'Mode', 'UserInfo', 'DebugInfo'] as $f) {
            $msg .= sprintf(" * ERROR %s: %s\n", strtoupper($f), call_user_func([$ei->getCause(), "get$f"]));
          }
        }
        $ei = $ei->getCause();
      }
      // if we have reached a DB_Error assume that is the end of the road.
      else {
        $ei = NULL;
      }
    }
    $msg .= self::formatBacktrace($e->getTrace());
    return $msg;
  }

  /**
   * @param $message
   * @param int $code
   * @param string $level
   * @param array $params
   *
   * @return object
   */
  public static function createError($message, $code = 8000, $level = 'Fatal', $params = NULL) {
    $error = CRM_Core_Error::singleton();
    $error->push($code, $level, [$params], $message);
    return $error;
  }

  /**
   * Set a status message in the session, then bounce back to the referrer.
   *
   * @param string $status
   *   The status message to set.
   * @param string|null $redirect
   * @param string|null $title
   */
  public static function statusBounce($status, $redirect = NULL, $title = NULL) {
    $session = CRM_Core_Session::singleton();
    if (!$redirect) {
      $redirect = $session->readUserContext();
    }
    if ($title === NULL) {
      $title = ts('Error');
    }
    $session->setStatus($status, $title, 'alert', ['expires' => 0]);
    if (($_REQUEST['snippet'] ?? NULL) === CRM_Core_Smarty::PRINT_JSON) {
      CRM_Core_Page_AJAX::returnJsonResponse(['status' => 'error']);
    }
    CRM_Utils_System::redirect($redirect);
  }

  /**
   * Reset the error stack.
   *
   */
  public static function reset() {
    $error = self::singleton();
    $error->_errors = [];
    $error->_errorsByLevel = [];
  }

  /**
   * PEAR error-handler which converts errors to exceptions
   *
   * @param PEAR_Error $pearError
   * @throws PEAR_Exception
   */
  public static function exceptionHandler($pearError) {
    if ($pearError instanceof DB_Error) {
      throw new DBQueryException($pearError->getMessage(), $pearError->getCode(), ['exception' => $pearError]);
    }
    throw new CRM_Core_Exception($pearError->getMessage(), $pearError->getCode(), ['exception' => $pearError]);
  }

  /**
   * PEAR error-handler to quietly catch otherwise fatal errors. Intended for use with smtp transport.
   *
   * @param object $obj
   *   The PEAR_ERROR object.
   * @return object
   *   $obj
   */
  public static function nullHandler($obj) {
    CRM_Core_Error::debug_log_message("Ignoring exception thrown by nullHandler: {$obj->code}, {$obj->message}", FALSE, '', PEAR_LOG_ERR);
    CRM_Core_Error::backtrace('backTrace', TRUE);
    return $obj;
  }

  /**
   * @deprecated
   * This function is no longer used by v3 api.
   * @fixme Some core files call it but it should be re-thought & renamed or removed
   *
   * @param $msg
   * @param null $data
   *
   * @return array
   * @throws Exception
   */
  public static function &createAPIError($msg, $data = NULL) {
    if (self::$modeException) {
      throw new Exception($msg, $data);
    }

    $values = [];

    $values['is_error'] = 1;
    $values['error_message'] = $msg;
    if (isset($data)) {
      $values = array_merge($values, $data);
    }
    return $values;
  }

  /**
   * @param $file
   */
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
   * Terminate execution abnormally.
   *
   * @param int $code
   */
  protected static function abend($code) {
    // do a hard rollback of any pending transactions
    // if we've come here, its because of some unexpected PEAR errors
    CRM_Core_Transaction::forceRollbackIfEnabled();
    CRM_Utils_System::civiExit($code);
  }

  /**
   * Show in-page exception
   * For situations where where calling abend will block the ability for a branded error screen
   *
   * Although the host page will run past this point, CiviCRM should not,
   * therefore we trigger the civi.exit events
   *
   * @param string $code
   */
  protected static function inpageExceptionDisplay($code) {
    // do a hard rollback of any pending transactions
    // if we've come here, its because of some unexpected PEAR errors
    CRM_Core_Transaction::forceRollbackIfEnabled();

    if ($code > 0 && !headers_sent()) {
      http_response_code(500);
    }

    // move things to CiviCRM cache as needed
    CRM_Core_Session::storeSessionObjects();

    if (Civi\Core\Container::isContainerBooted()) {
      Civi::dispatcher()->dispatch('civi.core.exit');
    }

    $userSystem = CRM_Core_Config::singleton()->userSystem;
    if (is_callable([$userSystem, 'onCiviExit'])) {
      $userSystem->onCiviExit();
    }
  }

  /**
   * @param array $error
   * @param int $type
   *
   * @return bool
   */
  public static function isAPIError($error, $type = CRM_Core_Error::FATAL_ERROR) {
    if (is_array($error) && !empty($error['is_error'])) {
      $code = $error['error_message']['code'];
      if ($code == $type) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Output a deprecated function warning to log file.
   *
   * Deprecated class:function is automatically generated from calling function.
   *
   * @param string $newMethod
   *   description of new method (eg. "buildOptions() method in the appropriate BAO object").
   * @param string|null $oldMethod
   *   optional description of old method (if not the calling method). eg. CRM_MyClass::myOldMethodToGetTheOptions()
   */
  public static function deprecatedFunctionWarning(string $newMethod, ?string $oldMethod = NULL): void {
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
    if (!$oldMethod) {
      $callerFunction = $backtrace[1]['function'] ?? NULL;
      $callerClass = $backtrace[1]['class'] ?? NULL;
      $oldMethod = "{$callerClass}::{$callerFunction}";
    }
    $message = "Deprecated function $oldMethod, use $newMethod.";
    // Add a mini backtrace. Just the function is too little to be meaningful but people are
    // saying they can't track down where the deprecated calls are coming from.
    $miniBacktrace = [];
    foreach ($backtrace as $backtraceLine) {
      $miniBacktrace[] = ($backtraceLine['class'] ?? '') . '::' . ($backtraceLine['function'] ?? '');
    }
    Civi::log('deprecated')->warning($message . "\n" . implode("\n", $miniBacktrace), ['civi.tag' => 'deprecated']);
    trigger_error($message, E_USER_DEPRECATED);
  }

  /**
   * Output a deprecated notice about a deprecated call path, rather than deprecating a whole function.
   *
   * @param string $message
   */
  public static function deprecatedWarning($message) {
    // Even though the tag is no longer used within the log() function,
    // \Civi\API\LogObserver instances may still be monitoring it.
    $dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
    $callerFunction = $dbt[2]['function'] ?? NULL;
    $callerClass = $dbt[2]['class'] ?? NULL;
    $message .= " Caller: {$callerClass}::{$callerFunction}";
    Civi::log('deprecated')->warning($message, ['civi.tag' => 'deprecated']);
    trigger_error($message, E_USER_DEPRECATED);
  }

}

$e = new PEAR_ErrorStack('CRM');
$e->singleton('CRM', FALSE, NULL, 'CRM_Core_Error');
