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

use Civi\Core\Exception\DBQueryException;

/**
 * Tests for linking to resource files
 * @group headless
 */
class CRM_Core_ErrorTest extends CiviUnitTestCase {

  /**
   * @var string
   */
  private $oldConfigAndLogDir;

  public function setUp(): void {
    parent::setUp();
    $config = CRM_Core_Config::singleton();
    $this->oldConfigAndLogDir = $config->configAndLogDir;
    $config->configAndLogDir = $this->createTempDir('test-log-');
  }

  public function tearDown(): void {
    $config = CRM_Core_Config::singleton();
    $config->configAndLogDir = $this->oldConfigAndLogDir;
    parent::tearDown();
  }

  /**
   * Make sure that formatBacktrace() accepts values from debug_backtrace()
   */
  public function testFormatBacktraceDebug(): void {
    $bt = debug_backtrace();
    $msg = CRM_Core_Error::formatBacktrace($bt);
    $this->assertMatchesRegularExpression('/CRM_Core_ErrorTest->testFormatBacktraceDebug/', $msg);
  }

  /**
   * Make sure that formatBacktrace() accepts values from Exception::getTrace()
   */
  public function testFormatBacktraceException(): void {
    $e = new Exception('foo');
    $msg = CRM_Core_Error::formatBacktrace($e->getTrace());
    $this->assertMatchesRegularExpression('/CRM_Core_ErrorTest->testFormatBacktraceException/', $msg);
  }

  public function testExceptionLogging(): void {
    $e = new \Exception('the exception');
    Civi::log()->notice('There was an exception!', [
      'exception' => $e,
    ]);

    $e = new Error('the error');
    Civi::log()->notice('There was an error!', [
      'exception' => $e,
    ]);
  }

  /**
   * We have two coding conventions for writing to log. Make sure that they work together.
   *
   * This tests a theory about what caused CRM-10766.
   */
  public function testMixLog(): void {
    CRM_Core_Error::debug_log_message('static-1');
    $logger = CRM_Core_Error::createDebugLogger();
    CRM_Core_Error::debug_log_message('static-2');
    $logger->info('obj-1');
    CRM_Core_Error::debug_log_message('static-3');
    $logger->info('obj-2');
    CRM_Core_Error::debug_log_message('static-4');
    $logger2 = CRM_Core_Error::createDebugLogger();
    $logger2->info('obj-3');
    CRM_Core_Error::debug_log_message('static-5');
    $this->assertLogRegexp('/static-1.*static-2.*obj-1.*static-3.*obj-2.*static-4.*obj-3.*static-5/s');
  }

  /**
   * @param $pattern
   */
  public function assertLogRegexp($pattern): void {
    $config = CRM_Core_Config::singleton();
    $logFiles = glob($config->configAndLogDir . '/CiviCRM*.log');
    $this->assertEquals(1, count($logFiles), 'Expect to find 1 file matching: ' . $config->configAndLogDir . '/CiviCRM*log*/');
    foreach ($logFiles as $logFile) {
      $this->assertMatchesRegularExpression($pattern, file_get_contents($logFile));
    }
  }

  /**
   * Check that a debugger is created and there is no error when passing in a prefix.
   *
   * Do some basic content checks.
   */
  public function testDebugLoggerFormat(): void {
    $log = CRM_Core_Error::createDebugLogger('my-test');
    $log->log('Mary had a little lamb');
    $log->log('Little lamb');
    $config = CRM_Core_Config::singleton();
    $fileContents = file_get_contents($config->configAndLogDir . 'CiviCRM.' . CIVICRM_DOMAIN_ID . '.' . 'my-test.' . CRM_Core_Error::generateLogFileHash($config) . '.log');
    // The 5 here is a bit arbitrary - on my local the date part is 15 chars (Mar 29 05:29:16) - but we are just checking that
    // there are chars for the date at the start.
    $this->assertTrue(strpos($fileContents, '[info] Mary had a little lamb') > 10);
    $this->assertStringContainsString('[info] Little lamb', $fileContents);
  }

  /**
   * Test the contents of the exception thrown for invalid sql.
   *
   * @dataProvider getErrorSQL
   *
   * @param array $testData
   */
  public function testDBError(array $testData): void {
    try {
      CRM_Core_DAO::executeQuery($testData['sql']);
    }
    catch (DBQueryException $e) {
      $this->assertEquals(0, $e->getCode());
      $this->assertInstanceOf('DB_Error', $e->getCause());
      $this->assertEquals($testData['message'], $e->getMessage());
      $this->assertEquals($testData['error_code'], $e->getErrorCode());
      $this->assertEquals($testData['sql_error_code'], $e->getSQLErrorCode());
      $this->assertStringStartsWith($testData['sql'] . ' [nativecode=' . $testData['sql_error_code'], $e->getDebugInfo());
      $this->assertEquals($testData['sql'], $e->getSQL());
      $this->assertStringStartsWith($testData['user_message'], $e->getUserMessage());
      return;
    }
    $this->fail();
  }

  /**
   * Data provider for sql error testing.
   *
   * @return array[]
   */
  public function getErrorSQL(): array {
    return [
      'invalid_table' => [
        [
          'sql' => 'SELECT a FROM b',
          'message' => 'DB Error: no such table',
          'error_code' => -18,
          'user_message' => 'Invalid Query no such table',
          'sql_error_code' => 1146,
        ],
      ],
      'invalid_field' => [
        [
          'sql' => 'SELECT a FROM civicrm_contact',
          'message' => 'DB Error: no such field',
          'error_code' => -19,
          'user_message' => "Invalid Query no such field Unknown column 'a' in 'field list'",
          'sql_error_code' => 1054,
        ],
      ],
      'invalid_syntax' => [
        [
          'sql' => 'FROM civicrm_contact',
          'message' => 'DB Error: syntax error',
          'error_code' => -2,
          'user_message' => 'Invalid Query syntax error You have an error in your SQL syntax; check the manual that corresponds to your',
          'sql_error_code' => 1064,
        ],
      ],
    ];
  }

}
