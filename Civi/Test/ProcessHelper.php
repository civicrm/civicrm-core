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

namespace Civi\Test;

use Civi\Test\Exception\ProcessErrorException;

/**
 * Define some utility functions for running subprocesses as part of an end-to-end test.
 */
class ProcessHelper {

  /**
   * Run a command. Assert that it succeeds. Otherwise, throw an exception.
   *
   * If the env-var `DEBUG` is set, then print debug information.
   *
   * @param string $cmd
   * @return string
   *   Upon success, this is the command-output.
   */
  public static function runOk(string $cmd): string {
    static::run($cmd, $stdout, $stderr, $exit);
    if ($exit !== 0) {
      throw new ProcessErrorException($cmd, $stdout, $stderr, $exit);
    }
    return $stdout;
  }

  public static function run(string $cmd, ?string &$stdout, ?string &$stderr, ?int &$exit): void {
    if (getenv('DEBUG') > 0) {
      fprintf(STDERR, "[CRM_Utils_Process::runOk] %s\n", $cmd);
    }
    $ignoreStdErr = ';^Xdebug:;';

    // In other projects, I use symfony/process for this -- it's nicer for passing-through STDOUT/STDERR. But it's a bit of dephell.
    $descriptorSpec = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open($cmd, $descriptorSpec, $pipes, __DIR__);
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $stderr = implode("\n", preg_grep($ignoreStdErr, explode("\n", $stderr), PREG_GREP_INVERT));
    $exit = proc_close($process);
    if (getenv('DEBUG') > 1) {
      fwrite(STDERR, static::formatOutput($cmd, $stdout, $stderr, $exit));
    }
  }

  public static function formatOutput(string $cmd, ?string $stdout, ?string $stderr, ?int $exit): string {
    $buf = sprintf("========================\n")
      . sprintf("==== COMMAND RESULT ====\n")
      . sprintf("========================\n")
      . sprintf("== PWD: %s\n", getcwd())
      . sprintf("== CMD: %s\n", $cmd)
      . sprintf("== EXIT: %s\n", $exit);
    if (trim($stdout) !== '') {
      $buf .= sprintf("== STDOUT:\n%s\n", $stdout);
    }
    if (trim($stderr) !== '') {
      $buf .= sprintf("== STDERR:\n%s\n", $stderr);
    }
    return $buf;
  }

  /**
   * Determine full path to an external command (by searching PATH).
   *
   * @param string $name
   * @return null|string
   */
  public static function findCommand($name) {
    $paths = explode(PATH_SEPARATOR, getenv('PATH'));
    foreach ($paths as $path) {
      if (file_exists("$path/$name")) {
        return "$path/$name";
      }
    }
    return NULL;
  }

  /**
   * Determine if $file is a shell script.
   *
   * @param string $file
   * @return bool
   */
  public static function isShellScript($file) {
    $firstLine = file_get_contents($file, FALSE, NULL, 0, 120);
    [$firstLine] = explode("\n", $firstLine);
    return (bool) preg_match(';^#.*bin.*sh;', $firstLine);
  }

}
