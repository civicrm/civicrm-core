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
 * Formatting helper for errors (backtraces and exceptions).
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * @internal
 */
class CRM_Core_Error_Formatter {

  protected string $format;
  protected bool $showArgs;
  protected int $maxArgLen;

  /**
   * @param string $format
   *   Desired output format.
   *   Ex: 'text', 'html', or 'array'
   * @param bool $showArgs
   *   TRUE if we should try to display content of function arguments (which could be sensitive); FALSE to display only the type of each function argument.
   * @param int $maxArgLen
   *   Maximum number of characters to show from each argument string.
   */
  public function __construct(string $format, bool $showArgs = TRUE, int $maxArgLen = 80) {
    $this->format = $format;
    $this->showArgs = $showArgs;
    $this->maxArgLen = $maxArgLen;
  }

  /**
   * @param \Throwable $e
   *   The exception.
   * @return mixed
   *   Varies by $format.
   */
  public function formatException(Throwable $e) {
    return match($this->format) {
      'text' => $this->formatTextException($e),
      'html' => $this->formatHtmlException($e),
      default => throw new \RuntimeException("Cannot format exception as {$this->format}", 0, $e),
    };
  }

  /**
   * @param array $trace
   *   The backtrace. (List of stack-frames.)
   * @return mixed
   *   Varies by $format.
   */
  public function formatBacktrace(array $trace) {
    return match($this->format) {
      'array' => $this->formatArrayBacktrace($trace),
      'text' => $this->formatTextBacktrace($trace),
      'html' => $this->formatHtmlBacktrace($trace),
      default => throw new \RuntimeException("Cannot format backtrace as {$this->format}"),
    };
  }

  protected function formatHtmlBacktrace(array $trace): string {
    return '<pre>' . htmlentities($this->formatTextBacktrace($trace)) . '</pre>';
  }

  /**
   * Render an exception as HTML string.
   *
   * @param Throwable $e
   * @return string
   *   printable HTML text
   */
  protected function formatHtmlException(Throwable $e): string {
    $msg = '';
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
      $msg .= sprintf('<p><b>%s</b>: "%s" in <b>%s</b> on line <b>%s</b></p>',
        htmlentities(get_class($e)),
        htmlentities($e->getMessage()),
        htmlentities($e->getFile()),
        $e->getLine()
      );
      $msg .= $this->formatHtmlBacktrace($e->getTrace());
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
  protected function formatTextException(Throwable $e): string {
    $msg = sprintf("%s: \"%s\" in %s on line %s\n",
      get_class($e),
      $e->getMessage(),
      $e->getFile(),
      $e->getLine()
    );

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
    $msg .= $this->formatTextBacktrace($e->getTrace());
    return $msg;
  }

  /**
   * Render a backtrace array as a string.
   *
   * @param array $backTrace
   *   Array of stack frames.
   * @return string
   *   printable plain-text
   */
  protected function formatTextBacktrace(array $backTrace): string {
    $message = '';
    foreach ($this->formatArrayBacktrace($backTrace) as $idx => $trace) {
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
   * @return array
   * @see debug_backtrace
   * @see Exception::getTrace()
   */
  protected function formatArrayBacktrace(array $backTrace): array {
    $ret = [];
    foreach ($backTrace as $trace) {
      $args = [];
      $fnName = $trace['function'] ?? NULL;
      $className = isset($trace['class']) ? ($trace['class'] . $trace['type']) : '';

      // Do not show args for a few password related functions
      $skipArgs = $className == 'DB::' && $fnName == 'connect';

      if (!empty($trace['args'])) {
        foreach ($trace['args'] as $arg) {
          if (!$this->showArgs || $skipArgs) {
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
              $args[] = '"' . CRM_Utils_String::ellipsify(addcslashes((string) $arg, "\r\n\t\""), $this->maxArgLen) . '"';
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
        $trace['file'] ?? '[internal function]',
        $trace['line'] ?? '',
        $className,
        $fnName,
        implode(", ", $args)
      );
    }
    return $ret;
  }

}
