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
