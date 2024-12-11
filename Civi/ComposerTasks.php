<?php

namespace Civi;

use Symfony\Component\Finder\Finder;

class ComposerTasks {

  /**
   * Find Javascript files and remove the "sourceMappingURL" option.
   *
   * @param array $task
   *   Ex: ['src-path' => 'bower_components/foo']
   * @return void
   */
  public static function stripSourceMap(array $task = []): void {
    if (empty($task['src-path']) || !is_string($task['src-path']) || strpos($task['src-path'], '..') !== FALSE) {
      throw new \LogicException("stripSourceMap(): Task must specify a valid 'src-path'");
    }
    $path = dirname(__DIR__) . '/' . $task['src-path'];
    $files = (new Finder())->files()->name('*.js')->in($path)->ignoreVCS(TRUE);
    foreach ($files as $file) {
      $code = file_get_contents($file);
      $lines = explode("\n", $code);
      $filteredLines = preg_grep(';^//# sourceMappingURL=;', $lines, PREG_GREP_INVERT);
      if (count($lines) > count($filteredLines)) {
        \CCL::dumpFile($file, implode("\n", $filteredLines));
      }
    }
  }

}
