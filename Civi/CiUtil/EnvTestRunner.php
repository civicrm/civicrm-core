<?php
namespace Civi\CiUtil;

/**
 * Parse phpunit result files
 */
class EnvTestRunner {
  protected $phpunit;
  protected $envTestSuite;

  function __construct($phpunit = "phpunit", $envTestSuite = 'EnvTests') {
    $this->phpunit = $phpunit;
    $this->envTestSuite = $envTestSuite;
  }

  /**
   * @param array $tests
   * @return array (string $testName => string $status)
   */
  public function run($tests) {
    $envTests = implode(' ', $tests);
    $jsonFile = tempnam(sys_get_temp_dir(), 'phpunit-json-');
    unlink($jsonFile);
    $command = "env PHPUNIT_TESTS=\"$envTests\" {$this->phpunit} --log-json $jsonFile {$this->envTestSuite}";
    echo "Running [$command]\n";
    system($command);
    $results = PHPUnitParser::parseJsonResults(file_get_contents($jsonFile));
    unlink($jsonFile);
    return $results;
  }
}