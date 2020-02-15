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

  public function setUp() {
    $url = 'file://' . dirname(dirname(dirname(dirname(__FILE__)))) . '/mock/extension_browser_results';
    Civi::settings()->set('ext_repo_url', $url);
  }

  public function tearDown() {
    Civi::settings()->revert('ext_repo_url');
  }

  /**
   * Test getremote.
   */
  public function testGetremote() {
    $result = $this->callAPISuccess('extension', 'getremote', []);
    $this->assertEquals('org.civicrm.module.cividiscount', $result['values'][0]['key']);
    $this->assertEquals('module', $result['values'][0]['type']);
    $this->assertEquals('CiviDiscount', $result['values'][0]['name']);
  }

  /**
   * Test getting a single extension
   * CRM-20532
   */
  public function testExtensionGetSingleExtension() {
    $result = $this->callAPISuccess('extension', 'get', ['key' => 'test.extension.manager.moduletest']);
    $this->assertEquals('test.extension.manager.moduletest', $result['values'][$result['id']]['key']);
    $this->assertEquals('module', $result['values'][$result['id']]['type']);
    $this->assertEquals('test_extension_manager_moduletest', $result['values'][$result['id']]['name']);
  }

  /**
   * Test single Extension get with specific fields in return
   * CRM-20532
   */
  public function testSingleExtensionGetWithReturnFields() {
    $result = $this->callAPISuccess('extension', 'get', ['key' => 'test.extension.manager.moduletest', 'return' => ['name', 'status', 'key']]);
    $this->assertEquals('test.extension.manager.moduletest', $result['values'][$result['id']]['key']);
    $this->assertFalse(isset($result['values'][$result['id']]['type']));
    $this->assertEquals('test_extension_manager_moduletest', $result['values'][$result['id']]['name']);
    $this->assertEquals('uninstalled', $result['values'][$result['id']]['status']);
  }

  /**
   * Test Extension Get returns detailed information
   * Note that this is likely to fail locally but will work on Jenkins due to the result count check
   * CRM-20532
   */
  public function testExtensionGet() {
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
  public function testExtensionGetByStatus() {
    $installed = $this->callAPISuccess('extension', 'get', ['status' => 'installed', 'options' => ['limit' => 0]]);
    $uninstalled = $this->callAPISuccess('extension', 'get', ['status' => 'uninstalled', 'options' => ['limit' => 0]]);

    // If the filter works, then results should be strictly independent.
    $this->assertEquals(
      [],
      array_intersect(
        CRM_Utils_Array::collect('key', $installed['values']),
        CRM_Utils_Array::collect('key', $uninstalled['values'])
      )
    );

    $all = $this->callAPISuccess('extension', 'get', ['options' => ['limit' => 0]]);
    $this->assertEquals($all['count'], $installed['count'] + $uninstalled['count']);
  }

  public function testGetMultipleExtensions() {
    $result = $this->callAPISuccess('extension', 'get', ['key' => ['test.extension.manager.paymenttest', 'test.extension.manager.moduletest']]);
    $this->assertEquals(2, $result['count']);
  }

  /**
   * Test that extension get works with api request with parameter full_name as build by api explorer.
   */
  public function testGetMultipleExtensionsApiExplorer() {
    $result = $this->callAPISuccess('extension', 'get', ['full_name' => ['test.extension.manager.paymenttest', 'test.extension.manager.moduletest']]);
    $this->assertEquals(2, $result['count']);
  }

  /**
   * Test that extension get can be filtered by id.
   */
  public function testGetExtensionByID() {
    $result = $this->callAPISuccess('extension', 'get', ['id' => 2, 'return' => ['label']]);
    $this->assertEquals(1, $result['count']);
  }

}
