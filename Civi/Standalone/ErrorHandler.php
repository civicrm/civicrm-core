<?php

namespace Civi\Standalone;

/**
 * Standalone's custom error handler (was previously in index.php)
 */
class ErrorHandler {

  protected static bool $handlingError = FALSE;

  public static function setHandler(int $errorLevel = E_ALL): void {
    set_error_handler([self::class, 'handleError'], $errorLevel);
  }

  public static function handleError(
    int $errno,
    string $errstr,
    ?string $errfile,
    ?int $errline
  ) {

    self::$handlingError = FALSE;

    if (self::$handlingError) {
      throw new \RuntimeException("Died: error was thrown during error handling");
    }

    $config = \CRM_Core_Config::singleton();
    if (!$config->debug) {
      // For these errors to show, we must be debugging.
      return;
    }
    self::$handlingError = TRUE;

    $trace = '';
    if ($config->backtrace) {
      // Backtrace is configured for errors.
      $trace = [];
      foreach (array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1) as $item) {
        $_ = '';
        if (!empty($item['function'])) {
          if (!empty($item['class']) && !empty($item['type'])) {
            $_ = htmlspecialchars("$item[class]$item[type]$item[function]() ");
          }
          else {
            $_ = htmlspecialchars("$item[function]() ");
          }
        }
        $_ .= "<code>" . htmlspecialchars($item['file']) . '</code> line ' . $item['line'];
        $trace[] = $_;
      }
      $trace = '<pre class=backtrace>' . implode("\n", $trace) . '</pre>';
    }

    if (!isset(\Civi::$statics[__FUNCTION__])) {
      \Civi::$statics[__FUNCTION__] = [];
    }
    \Civi::$statics[__FUNCTION__][] = '<li style="white-space:pre-wrap">'
    . htmlspecialchars("$errstr [$errno]\n") . '<code>' . htmlspecialchars($errfile) . "</code> line $errline"
    . $trace
    . '</li>';
    \CRM_Core_Smarty::singleton()->assign('standaloneErrors', implode("\n", \Civi::$statics[__FUNCTION__]));

    $handlingError = FALSE;
  }

}
