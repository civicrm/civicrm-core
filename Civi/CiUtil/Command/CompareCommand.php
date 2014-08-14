<?php
namespace Civi\CiUtil\Command;

class CompareCommand {
  static function main($argv) {
    if (empty($argv[1])) {
      echo "summary: Compares the output of different test runs\n";
      echo "usage: phpunit-compare <json-file1> [<json-file2>...]\n";
      exit(1);
    }


    $suites = array(); // array('file' => string, 'results' => array)
    for ($i = 1; $i < count($argv); $i++) {
      $suites[$i] = array(
        'file' => $argv[$i],
        'results' => \Civi\CiUtil\PHPUnitParser::parseJsonResults(file_get_contents($argv[$i]))
      );
    }

    $tests = array(); // array(string $name)
    foreach ($suites as $suiteName => $suite) {
      $tests = array_unique(array_merge(
        $tests,
        array_keys($suite['results'])
      ));
    }
    sort($tests);

    $printer = new \Civi\CiUtil\ComparisonPrinter(\Civi\CiUtil\Arrays::collect($suites, 'file'));
    foreach ($tests as $test) {
      $values = array();
      foreach ($suites as $suiteName => $suite) {
        $values[] = isset($suite['results'][$test]) ? $suite['results'][$test] : 'MISSING';
      }

      if (count(array_unique($values)) > 1) {
        $printer->printRow($test, $values);
      }
    }
  }
}
