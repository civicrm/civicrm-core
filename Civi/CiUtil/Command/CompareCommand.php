<?php
namespace Civi\CiUtil\Command;

/**
 * Class CompareCommand
 *
 * @package Civi\CiUtil\Command
 */
class CompareCommand {
  /**
   * @param $argv
   */
  public static function main($argv) {
    if (empty($argv[1])) {
      echo "summary: Compares the output of different test runs\n";
      echo "usage: phpunit-compare [--out=txt|csv] [--phpunit-json|--jenkins-xml] <file1> <file2>...\n";
      exit(1);
    }

    $parser = ['\Civi\CiUtil\PHPUnitParser', 'parseJsonResults'];
    $printerType = 'txt';
    $suites = []; // array('file' => string, 'results' => array)
    for ($i = 1; $i < count($argv); $i++) {
      switch ($argv[$i]) {
        case '--phpunit-json':
          $parser = ['\Civi\CiUtil\PHPUnitParser', 'parseJsonResults'];
          break;

        case '--jenkins-xml':
          $parser = ['\Civi\CiUtil\JenkinsParser', 'parseXmlResults'];
          break;

        case '--csv':
          $parser = ['\Civi\CiUtil\CSVParser', 'parseResults'];
          break;

        case '--out=txt':
          $printerType = 'txt';
          break;

        case '--out=csv':
          $printerType = 'csv';
          break;

        default:
          $suites[] = [
            'file' => $argv[$i],
            'results' => call_user_func($parser, file_get_contents($argv[$i])),
          ];
      }
    }

    $tests = []; // array(string $name)
    foreach ($suites as $suite) {
      $tests = array_unique(array_merge(
        $tests,
        array_keys($suite['results'])
      ));
    }
    sort($tests);

    if ($printerType == 'csv') {
      $printer = new \Civi\CiUtil\CsvPrinter('php://stdout', \Civi\CiUtil\Arrays::collect($suites, 'file'));
    }
    else {
      $printer = new \Civi\CiUtil\ComparisonPrinter(\Civi\CiUtil\Arrays::collect($suites, 'file'));
    }
    foreach ($tests as $test) {
      $values = [];
      foreach ($suites as $suite) {
        $values[] = isset($suite['results'][$test]) ? $suite['results'][$test] : 'MISSING';
      }

      if (count(array_unique($values)) > 1) {
        $printer->printRow($test, $values);
      }
    }
  }

}
