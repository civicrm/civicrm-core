<?php
namespace Civi\CiUtil\Command;

class LsCommand {
  static function main($argv) {
    $paths = $argv;
    array_shift($paths);
    foreach (\Civi\CiUtil\PHPUnitScanner::findTestsByPath($paths) as $test) {
      printf("%s %s %s\n", $test['file'], $test['class'], $test['method']);
    }
  }
}