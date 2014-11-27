<?php
namespace Civi\CiUtil\Command;

class AntagonistCommand {
  static function main($argv) {
    if (count($argv) != 3) {
      print "usage: {$argv[0]} <TargetTest::testFunc> </path/to/suite>\n";
      exit(1);
    }
    list ($program, $target, $suite) = $argv;

    $candidateTests = \Civi\CiUtil\PHPUnitScanner::findTestsByPath(array($suite));
//    $candidateTests = array(
//      array('class' => 'CRM_Core_RegionTest', 'method' => 'testBlank'),
//      array('class' => 'CRM_Core_RegionTest', 'method' => 'testDefault'),
//      array('class' => 'CRM_Core_RegionTest', 'method' => 'testOverride'),
//      array('class' => 'CRM_Core_RegionTest', 'method' => 'testAllTypes'),
//    );
    $antagonist = self::findAntagonist($target, $candidateTests);
    if ($antagonist) {
      print_r(array('found an antagonist' => $antagonist));
    }
    else {
      print_r(array('found no antagonists'));
    }
  }

  /**
   * @param string $target e.g. "MyTest::testFoo"
   * @param array $candidateTests list of strings (e.g. "MyTest::testFoo")
   * @return array|null array contains keys:
   *  - antagonist: array
   *    - file: string
   *    - class: string
   *    - method: string
   *  - expectedResults: array
   *  - actualResults: array
   */
  static function findAntagonist($target, $candidateTests) {
    //$phpUnit = new \Civi\CiUtil\EnvTestRunner('./scripts/phpunit', 'EnvTests');
    $phpUnit = new \Civi\CiUtil\EnvTestRunner('phpunit', 'tests/phpunit/EnvTests.php');
    $expectedResults = $phpUnit->run(array($target));
    print_r(array('$expectedResults' => $expectedResults));

    foreach ($candidateTests as $candidateTest) {
      $candidateTestName = $candidateTest['class'] . '::' . $candidateTest['method'];
      if ($candidateTestName == $target) {
        continue;
      }
      $actualResults = $phpUnit->run(array(
        $candidateTestName,
        $target,
      ));
      print_r(array('$actualResults' => $actualResults));
      foreach ($expectedResults as $testName => $expectedResult) {
        if ($actualResults[$testName] != $expectedResult) {
          return array(
            'antagonist' => $candidateTest,
            'expectedResults' => $expectedResults,
            'actualResults' => $actualResults,
          );
        }
      }
    }
    return NULL;
  }
}