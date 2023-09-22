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
 *  Test APIv3 civicrm_extension_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Core
 */

/**
 * Class api_v3_ExtensionTest.
 * @group headless
 */
class api_v3_ExtensionTest extends CiviUnitTestCase {

  use \Civi\Test\GuzzleTestTrait;

  public function setUp(): void {
    Civi::settings()->set('ext_repo_url', 'http://localhost:9999/fake-repo');
  }

  public function tearDown(): void {
    Civi::settings()->revert('ext_repo_url');
  }

  /**
   * Test getremote.
   */
  public function testGetremote(): void {
    $testsDir = dirname(dirname(dirname(dirname(__FILE__))));
    $this->createMockHandler([file_get_contents($testsDir . '/mock/extension_browser_results/single')]);
    $this->setUpClientWithHistoryContainer();
    CRM_Extension_System::singleton()->getBrowser()->setGuzzleClient($this->getGuzzleClient());
    CRM_Extension_System::singleton()->getBrowser()->refresh();

    $result = $this->callAPISuccess('extension', 'getremote', []);
    $this->assertEquals('org.civicrm.module.cividiscount', $result['values'][0]['key']);
    $this->assertEquals('module', $result['values'][0]['type']);
    $this->assertEquals('CiviDiscount', $result['values'][0]['name']);

    $this->assertEquals(['http://localhost:9999/fake-repo/single'], $this->getRequestUrls());
  }

  /**
   * Test getting a single extension
   * @see https://issues.civicrm.org/jira/browse/CRM-20532
   */
  public function testExtensionGetSingleExtension(): void {
    $result = $this->callAPISuccess('extension', 'get', ['key' => 'test.extension.manager.moduletest']);
    $this->assertEquals('test.extension.manager.moduletest', $result['values'][$result['id']]['key']);
    $this->assertEquals('module', $result['values'][$result['id']]['type']);
    $this->assertEquals('test_extension_manager_moduletest', $result['values'][$result['id']]['name']);
  }

  /**
   * Test single Extension get with specific fields in return
   * @see https://issues.civicrm.org/jira/browse/CRM-20532
   */
  public function testSingleExtensionGetWithReturnFields(): void {
    $result = $this->callAPISuccess('extension', 'get', ['key' => 'test.extension.manager.moduletest', 'return' => ['name', 'status', 'key']]);
    $this->assertEquals('test.extension.manager.moduletest', $result['values'][$result['id']]['key']);
    $this->assertFalse(isset($result['values'][$result['id']]['type']));
    $this->assertEquals('test_extension_manager_moduletest', $result['values'][$result['id']]['name']);
    $this->assertEquals('uninstalled', $result['values'][$result['id']]['status']);
  }

  /**
   * Test Extension Get returns detailed information
   * Note that this is likely to fail locally but will work on Jenkins due to the result count check
   * @see https://issues.civicrm.org/jira/browse/CRM-20532
   */
  public function testExtensionGet(): void {
    $result = $this->callAPISuccess('extension', 'get', ['options' => ['limit' => 0]]);
    $testExtensionResult = $this->callAPISuccess('extension', 'get', ['key' => 'test.extension.manager.paymenttest']);
    $ext = $result['values'][$testExtensionResult['id']];
    $this->assertNotNull($ext['typeInfo']);
    $this->assertEquals(['mock'], $ext['tags']);
    $this->assertTrue($result['count'] >= 6);
  }

  /**
   * Filtering by status=installed or status=uninstalled should produce different results.
   */
  public function testExtensionGetByStatus(): void {
    $installed = $this->callAPISuccess('extension', 'get', ['status' => 'installed', 'options' => ['limit' => 0]]);
    $uninstalled = $this->callAPISuccess('extension', 'get', ['status' => 'uninstalled', 'options' => ['limit' => 0]]);
    $disabled = $this->callAPISuccess('extension', 'get', ['status' => 'disabled', 'options' => ['limit' => 0]]);

    // If the filter works, then results should be strictly independent.
    $this->assertEquals(
      [],
      array_intersect(
        CRM_Utils_Array::collect('key', $installed['values']),
        CRM_Utils_Array::collect('key', $uninstalled['values']),
        CRM_Utils_Array::collect('key', $disabled['values'])
      )
    );

    $all = $this->callAPISuccess('extension', 'get', ['options' => ['limit' => 0]]);
    $this->assertEquals($all['count'], $installed['count'] + $uninstalled['count'] + $disabled['count']);
  }

  public function testGetMultipleExtensions(): void {
    $result = $this->callAPISuccess('extension', 'get', ['key' => ['test.extension.manager.paymenttest', 'test.extension.manager.moduletest']]);
    $this->assertEquals(2, $result['count']);
  }

  /**
   * Test that extension get works with api request with parameter full_name as build by api explorer.
   */
  public function testGetMultipleExtensionsApiExplorer(): void {
    $result = $this->callAPISuccess('extension', 'get', ['full_name' => ['test.extension.manager.paymenttest', 'test.extension.manager.moduletest']]);
    $this->assertEquals(2, $result['count']);
  }

  /**
   * Test that extension get can be filtered by id.
   */
  public function testGetExtensionByID(): void {
    $result = $this->callAPISuccess('extension', 'get', ['id' => 2, 'return' => ['label']]);
    $this->assertEquals(1, $result['count']);
  }

}
