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

/**
 * System.check API has many special test cases, so they have their own class.
 *
 * We presume that in a test environment, checkDefaultMailbox and
 * checkDomainNameEmail always fail with a warning, and checkLastCron fails with
 * an error.
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_SystemCheckTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  /**
   * Ensure that without any StatusPreference set, checkDefaultMailbox shows up.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   *
   * @throws \CRM_Core_Exception
   */
  public function testSystemCheckBasic($version) {
    $this->_apiversion = $version;
    $this->runStatusCheck([], ['severity_id' => 3]);
  }

  /**
   * Permanently hushed items should never show up.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testSystemCheckHushForever($version) {
    $this->_apiversion = $version;
    $this->runStatusCheck([
      'name' => 'checkDefaultMailbox',
      'ignore_severity' => 7,
    ], ['is_visible' => 0]);
  }

  /**
   * Disabled items should never show up.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   *
   * @throws \CRM_Core_Exception
   */
  public function testIsInactive($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess('StatusPreference', 'create', [
      'name' => 'checkDefaultMailbox',
      'is_active' => 0,
    ]);
    $result = $this->callAPISuccess('System', 'check', [])['values'];
    foreach ($result as $check) {
      if ($check['name'] === 'checkDefaultMailbox') {
        $this->fail('Check should have been skipped');
      }
    }
  }

  /**
   * Items hushed through tomorrow shouldn't show up.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \Exception
   */
  public function testSystemCheckHushFuture($version) {
    $this->_apiversion = $version;
    $tomorrow = new DateTime('tomorrow');
    $this->runStatusCheck([
      'name' => 'checkDefaultMailbox',
      'ignore_severity' => 7,
      'hush_until' => $tomorrow->format('Y-m-d'),
    ], ['is_visible' => 0]);
  }

  /**
   * Items hushed through today should show up.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testSystemCheckHushToday($version) {
    $this->_apiversion = $version;
    $today = new DateTime('today');
    $this->runStatusCheck([
      'name' => 'checkDefaultMailbox',
      'ignore_severity' => 7,
      'hush_until' => $today->format('Y-m-d'),
    ], ['is_visible' => 1]);
  }

  /**
   * Items hushed through yesterday should show up.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   *
   * @throws \CRM_Core_Exception
   */
  public function testSystemCheckHushYesterday($version) {
    $this->_apiversion = $version;
    $yesterday = new DateTime('yesterday');
    $this->runStatusCheck([
      'name' => 'checkDefaultMailbox',
      'ignore_severity' => 7,
      'hush_until' => $yesterday->format('Y-m-d'),
    ], ['is_visible' => 1]);
  }

  /**
   * Items hushed above current severity should be hidden.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   *
   * @throws \CRM_Core_Exception
   */
  public function testSystemCheckHushAboveSeverity($version) {
    $this->_apiversion = $version;
    $this->runStatusCheck([
      'name' => 'checkDefaultMailbox',
      'ignore_severity' => 4,
    ], ['is_visible' => 0]);
  }

  /**
   * Items hushed at current severity should be hidden.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   *
   * @throws \CRM_Core_Exception
   */
  public function testSystemCheckHushAtSeverity($version) {
    $this->_apiversion = $version;
    $this->runStatusCheck([
      'name' => 'checkDefaultMailbox',
      'ignore_severity' => 3,
    ], ['is_visible' => 0]);
  }

  /**
   * Items hushed below current severity should be shown.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testSystemCheckHushBelowSeverity($version) {
    $this->_apiversion = $version;
    $this->runStatusCheck([
      'name' => 'checkDefaultMailbox',
      'ignore_severity' => 2,
    ], ['is_visible' => 1]);
  }

  /**
   * Run check and assert result is as expected.
   *
   * @param array $params
   *   Values to update the status check with.
   * @param array $expected
   *
   * @throws \CRM_Core_Exception
   */
  protected function runStatusCheck($params, $expected) {
    if (!empty($params)) {
      $this->callAPISuccess('StatusPreference', 'create', $params);
    }
    $result = $this->callAPISuccess('System', 'check', []);
    foreach ($result['values'] as $check) {
      if ($check['name'] === 'checkDefaultMailbox') {
        foreach ($expected as $key => $value) {
          $this->assertEquals($check[$key], $value);
        }
        return;
      }
    }
    throw new CRM_Core_Exception('checkDefaultMailbox not in results');
  }

}
