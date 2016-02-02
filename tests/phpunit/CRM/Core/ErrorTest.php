<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Tests for linking to resource files
 */
class CRM_Core_ErrorTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $config = CRM_Core_Config::singleton();
    $this->oldConfigAndLogDir = $config->configAndLogDir;
    $config->configAndLogDir = $this->createTempDir('test-log-');
  }

  public function tearDown() {
    $config = CRM_Core_Config::singleton();
    $config->configAndLogDir = $this->oldConfigAndLogDir;
    parent::tearDown();
  }

  /**
   * Make sure that formatBacktrace() accepts values from debug_backtrace()
   */
  public function testFormatBacktrace_debug() {
    $bt = debug_backtrace();
    $msg = CRM_Core_Error::formatBacktrace($bt);
    $this->assertRegexp('/CRM_Core_ErrorTest->testFormatBacktrace_debug/', $msg);
  }

  /**
   * Make sure that formatBacktrace() accepts values from Exception::getTrace()
   */
  public function testFormatBacktrace_exception() {
    $e = new Exception('foo');
    $msg = CRM_Core_Error::formatBacktrace($e->getTrace());
    $this->assertRegexp('/CRM_Core_ErrorTest->testFormatBacktrace_exception/', $msg);
  }

  /**
   * We have two coding conventions for writing to log. Make sure that they work together.
   *
   * This tests a theory about what caused CRM-10766.
   */
  public function testMixLog() {
    CRM_Core_Error::debug_log_message("static-1");
    $logger = CRM_Core_Error::createDebugLogger();
    CRM_Core_Error::debug_log_message("static-2");
    $logger->info('obj-1');
    CRM_Core_Error::debug_log_message("static-3");
    $logger->info('obj-2');
    CRM_Core_Error::debug_log_message("static-4");
    $logger2 = CRM_Core_Error::createDebugLogger();
    $logger2->info('obj-3');
    CRM_Core_Error::debug_log_message("static-5");
    $this->assertLogRegexp('/static-1.*static-2.*obj-1.*static-3.*obj-2.*static-4.*obj-3.*static-5/s');
  }

  /**
   * @param $pattern
   */
  public function assertLogRegexp($pattern) {
    $config = CRM_Core_Config::singleton();
    $logFiles = glob($config->configAndLogDir . '/CiviCRM*.log');
    $this->assertEquals(1, count($logFiles), 'Expect to find 1 file matching: ' . $config->configAndLogDir . '/CiviCRM*log*/');
    foreach ($logFiles as $logFile) {
      $this->assertRegexp($pattern, file_get_contents($logFile));
    }
  }

}
