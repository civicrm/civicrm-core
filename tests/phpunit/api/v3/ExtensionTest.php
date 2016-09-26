<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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

}
