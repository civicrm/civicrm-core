<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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


require_once 'CiviTest/CiviUnitTestCase.php';


/**
 * System.check API has many special test cases, so they have their own class.
 *
 * We presume that in a test environment, checkDefaultMailbox and
 * checkDomainNameEmail always fail with a warning, and checkLastCron fails with
 * an error.
 *
 * @package CiviCRM_APIv3
 */
class api_v3_SystemCheckTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_contactID;
  protected $_locationType;
  protected $_params;


  public function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  /**
   *    Ensure that without any SystemPreference set, checkDefaultMailbox shows
   *    up.
   */
  public function testSystemCheckBasic() {
    $result = $this->callAPISuccess('System', 'check', array());
    foreach ($result['values'] as $check) {
      if ($check['name'] == 'checkDefaultMailbox') {
        $testedCheck = $check;
        break;
      }
    }
    $this->assertEquals($testedCheck['severity'], 'warning', ' in line ' . __LINE__);
  }

  public function testSystemCheckHushForever() {
    $this->_params = array(
      'name' => 'checkDefaultMailbox',
      'minimum_report_severity' => 4,
    );
    $statusPreference = $this->callAPISuccess('StatusPreference', 'create', $this->_params);
    $result = $this->callAPISuccess('System', 'check', array());
    foreach ($result['values'] as $check) {
      if ($check['name'] == 'checkDefaultMailbox') {
        $testedCheck = $check;
        break;
      }
    }
    $this->assertArrayNotHasKey('name', $testedCheck, 'warning', ' in line ' . __LINE__);
  }

  public function testSystemCheckHushFuture() {
    $tomorrow = new DateTime('tomorrow');
    $this->_params = array(
      'name' => 'checkDefaultMailbox',
      'minimum_report_severity' => 4,
      'hush_until' => $tomorrow->format('Y-m-d'),
    );
    $statusPreference = $this->callAPISuccess('StatusPreference', 'create', $this->_params);
    $result = $this->callAPISuccess('System', 'check', array());
    foreach ($result['values'] as $check) {
      if ($check['name'] == 'checkDefaultMailbox') {
        $testedCheck = $check;
        break;
      }
    }
    fwrite(STDERR, print_r($statusPreference, TRUE));
    fwrite(STDERR, 'tomorrow?');
    fwrite(STDERR, print_r($tomorrow->format('Y-m-d'), TRUE));
    $this->assertArrayNotHasKey('name', $testedCheck, 'warning', ' in line ' . __LINE__);
  }

}
