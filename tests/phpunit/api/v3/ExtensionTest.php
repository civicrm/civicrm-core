<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
    $result = $this->callAPISuccess('extension', 'getremote', array());
    $this->assertEquals('org.civicrm.module.cividiscount', $result['values'][0]['key']);
    $this->assertEquals('module', $result['values'][0]['type']);
    $this->assertEquals('CiviDiscount', $result['values'][0]['name']);
  }

  /**
   * Test getting a single extension
   * CRM-20532
   */
  public function testExtensionGetSingleExtension() {
    $result = $this->callAPISuccess('extension', 'get', array('key' => 'test.extension.manager.moduletest'));
    $this->assertEquals('test.extension.manager.moduletest', $result['values'][$result['id']]['key']);
    $this->assertEquals('module', $result['values'][$result['id']]['type']);
    $this->assertEquals('test_extension_manager_moduletest', $result['values'][$result['id']]['name']);
  }

  /**
   * Test single Extension get with specific fields in return
   * CRM-20532
   */
  public function testSingleExtensionGetWithReturnFields() {
    $result = $this->callAPISuccess('extension', 'get', array('key' => 'test.extension.manager.moduletest', 'return' => array('name', 'status', 'key')));
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
    $result = $this->callAPISuccess('extension', 'get', array());
    $testExtensionResult = $this->callAPISuccess('extension', 'get', array('key' => 'test.extension.manager.paymenttest'));
    $this->assertNotNull($result['values'][$testExtensionResult['id']]['typeInfo']);
    $this->assertEquals(6, $result['count']);
  }

  public function testGetMultipleExtensions() {
    $result = $this->callAPISuccess('extension', 'get', array('key' => array('test.extension.manager.paymenttest', 'test.extension.manager.moduletest')));
    $this->assertEquals(2, $result['count']);
  }

  /**
   * Test that extension get works with api request with parameter full_name as build by api explorer.
   */
  public function testGetMultipleExtensionsApiExplorer() {
    $result = $this->callAPISuccess('extension', 'get', array('full_name' => array('test.extension.manager.paymenttest', 'test.extension.manager.moduletest')));
    $this->assertEquals(2, $result['count']);
  }

  /**
   * Test that extension get can be filtered by id.
   */
  public function testGetExtensionByID() {
    $result = $this->callAPISuccess('extension', 'get', array('id' => 2, 'return' => array('label')));
    $this->assertEquals(1, $result['count']);
  }

}
