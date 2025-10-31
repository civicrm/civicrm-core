<?php

namespace Upgrade;

use Civi\Test\ProcessHelper;

/**
 * The UpgradeSnapshotTest walks through a list of MySQL snapshots. It loads each of them into the
 * CiviCRM database and runs the upgrader.
 *
 * The test can be controlled by setting environment variables:
 *
 * - [required] `CIVICRM_UPGRADE_EVIL=1`: Enable the test to perform
 *   highly destructive operations. The test will not run unless you enable it.
 * - [optional] `UPGRADE_TEST_FILTER=...`: Focus testing on a specific SQL datadump.
 * - [optional] `DEBUG=1`: Enable extra output for subcommands
 * - [optional] `DEBUG=2`: Enable very verbose output for subcommands
 *
 * The test is generally dependent on two major tools:
 *
 * - `civicrm-upgrade-examples`: This command from `civicrm/upgrade-test` provides a list of available examples.
 * - `cv`: This provides way to interact with the site.
 *
 * Here are a few examples of using the test:
 *
 * ## Run all tests
 * $ CIVICRM_UPGRADE_EVIL=1 phpunit9 tests/phpunit/Upgrade
 *
 * ## Run tests with detailed command output
 * $ CIVICRM_UPGRADE_EVIL=1 DEBUG=2 phpunit9 tests/phpunit/Upgrade
 *
 * ## Focus on snapshots from 5.45.*
 * $ CIVICRM_UPGRADE_EVIL=1 UPGRADE_TEST_FILTER='5.45.*' phpunit9 tests/phpunit/Upgrade
 *
 * ## Focus on snapshots from 4.7.30 through 5.70
 * $ CIVICRM_UPGRADE_EVIL=1 UPGRADE_TEST_FILTER='@4.7.30..5.70' phpunit9 tests/phpunit/Upgrade
 *
 * @group upgrade
 */
class UpgradeSnapshotTest extends \PHPUnit\Framework\TestCase {

  private static $logDir;

  /**
   * When scanning available examples, choose up-to $limit snapshots for testing.
   * The algorithm in civicrm-upgrade-examples will choose a stable and wide-spread list of versions.
   *
   * @var int
   */
  private static $limit = 10;

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    $mysql = \Civi\Test\ProcessHelper::findCommand('mysql');
    if (\Civi\Test\ProcessHelper::isShellScript($mysql)) {
      fprintf(STDERR, "WARNING: The mysql command (%s) appears to be a wrapper script. In some environments, this may interfere with credential passing.\n", $mysql);
    }

    static::$logDir = cv("path -c configAndLogDir")[0]['value'];
    static::assertTrue(is_dir(static::$logDir), sprintf('configAndLogDir (%s) could not be found.', static::$logDir));
  }

  public function setUp(): void {
    parent::setUp();
    $this->assertTrue((bool) getenv('CIVICRM_UPGRADE_EVIL'), 'Upgrade tests are destructive. Please set CIVICRM_UPGRADE_EVIL=1 to confirm that destructive tests are OK.');
    $this->assertTrue(defined('CIVICRM_BOOTSTRAP_FORBIDDEN'), 'Upgrade tests should run in a basic environment without relying on the CiviCRM runtime services');
    \CRM_Utils_File::cleanDir(static::$logDir, FALSE, FALSE);
  }

  /**
   * Get a list of MySQL example files.
   *
   * @return array
   *   Ex: [['/path/to/civicrm-5.99.88.mysql.gz']]
   * @throws \CRM_Core_Exception
   */
  public static function getExamples(): array {
    if (getenv('CIVICRM_UPGRADE_FILE')) {
      fprintf(STDERR, "Detected CIVICRM_UPGRADE_FILE. We will only evaluate one snapshot (%s).\n", getenv('CIVICRM_UPGRADE_FILE'));
      return [[getenv('CIVICRM_UPGRADE_FILE')]];
    }

    $cmd = ['civicrm-upgrade-examples'];
    if (CIVICRM_UF === 'Standalone') {
      $cmd[] = '--snapshot-library';
      $cmd[] = 'databases_standalone';
    }
    if (!empty(getenv('UPGRADE_TEST_FILTER'))) {
      fprintf(STDERR, "Detected UPGRADE_TEST_FILTER. Test focused on \"%s\".\n", getenv('UPGRADE_TEST_FILTER'));
      $cmd[] = getenv('UPGRADE_TEST_FILTER');
    }
    else {
      $cmd[] = sprintf('5.13.3-multilingual_af_bg_en* "@4.7.30..%s:%d"', \CRM_Utils_System::version(), static::$limit);
    }
    // TODO: It may be nice to move the filtering logic from `civicrm-upgrade-examples` to the PHPUnit class.
    $cmdStr = implode(' ', $cmd);
    $stdout = ProcessHelper::runOk($cmdStr);
    $result = [];
    foreach (explode("\n", trim($stdout)) as $line) {
      if ($line = trim($line)) {
        $result[basename($line)] = [$line];
      }
    }
    return $result;
  }

  /**
   * Load a SQL snapshot and run the upgrade.
   *
   * @param string $snapshot
   *   Ex: '/path/to/civicrm-5.99.88.mysql.gz'
   * @dataProvider getExamples
   */
  public function testSnapshot(string $snapshot): void {
    \Civi\Test::schema()->dropAll()->loadSnapshot($snapshot);
    $cmd = sprintf('cd %s && cv updb -vv --no-interaction', escapeshellarg($GLOBALS['civicrm_root']));

    ProcessHelper::run($cmd, $stdout, $stderr, $exit);
    $logs = [];
    foreach ((array) glob(static::$logDir . '/CiviCRM.*.log') as $logFile) {
      $logs[$logFile] = file_get_contents($logFile);
    }

    $hasProblem = ($exit > 0)
      || !preg_match(';Have a nice day;', $stdout)
      || preg_grep('/(warning|error)/', $logs);

    if ($hasProblem) {
      echo ProcessHelper::formatOutput($cmd, $stdout, $stderr, $exit);
      foreach ($logs as $logFile => $content) {
        echo $this->formatLog($logFile, $content);
      }
      $this->fail(sprintf('Upgrade of %s encountered problem', basename($snapshot)));
    }
    else {
      $this->assertTrue(TRUE, 'Upgrade succeeded');
    }
  }

  private function formatLog(string $logFile, string $content): string {
    return sprintf("========================\n")
      . sprintf("====    LOG DATA    ====\n")
      . sprintf("========================\n")
      . sprintf("== FILE: %s\n", $logFile)
      . sprintf("== DATA:\n%s\n", $content);
  }

}
