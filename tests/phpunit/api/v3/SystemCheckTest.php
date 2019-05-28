<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
   * Ensure that without any StatusPreference set, checkDefaultMailbox shows
   * up.
   */
  public function testSystemCheckBasic() {
    $result = $this->callAPISuccess('System', 'check', array());
    foreach ($result['values'] as $check) {
      if ($check['name'] == 'checkDefaultMailbox') {
        $testedCheck = $check;
        break;
      }
    }
    $this->assertEquals($testedCheck['severity_id'], '3', ' in line ' . __LINE__);
  }

  /**
   * Permanently hushed items should never show up.
   */
  public function testSystemCheckHushForever() {
    $this->_params = array(
      'name' => 'checkDefaultMailbox',
      'ignore_severity' => 7,
    );
    $statusPreference = $this->callAPISuccess('StatusPreference', 'create', $this->_params);
    $result = $this->callAPISuccess('System', 'check', array());
    foreach ($result['values'] as $check) {
      if ($check['name'] == 'checkDefaultMailbox') {
        $testedCheck = $check;
        break;
      }
      else {
        $testedCheck = array();
      }
    }
    $this->assertEquals($testedCheck['is_visible'], '0', 'in line ' . __LINE__);
  }

  /**
   * Items hushed through tomorrow shouldn't show up.
   */
  public function testSystemCheckHushFuture() {
    $tomorrow = new DateTime('tomorrow');
    $this->_params = array(
      'name' => 'checkDefaultMailbox',
      'ignore_severity' => 7,
      'hush_until' => $tomorrow->format('Y-m-d'),
    );
    $statusPreference = $this->callAPISuccess('StatusPreference', 'create', $this->_params);
    $result = $this->callAPISuccess('System', 'check', array());
    foreach ($result['values'] as $check) {
      if ($check['name'] == 'checkDefaultMailbox') {
        $testedCheck = $check;
        break;
      }
      else {
        $testedCheck = array();
      }
    }
    $this->assertEquals($testedCheck['is_visible'], '0', 'in line ' . __LINE__);;
  }

  /**
   * Items hushed through today should show up.
   */
  public function testSystemCheckHushToday() {
    $today = new DateTime('today');
    $this->_params = array(
      'name' => 'checkDefaultMailbox',
      'ignore_severity' => 7,
      'hush_until' => $today->format('Y-m-d'),
    );
    $statusPreference = $this->callAPISuccess('StatusPreference', 'create', $this->_params);
    $result = $this->callAPISuccess('System', 'check', array());
    foreach ($result['values'] as $check) {
      if ($check['name'] == 'checkDefaultMailbox') {
        $testedCheck = $check;
        break;
      }
      else {
        $testedCheck = array();
      }
    }
    $this->assertEquals($testedCheck['is_visible'], '1', 'in line ' . __LINE__);
  }

  /**
   * Items hushed through yesterday should show up.
   */
  public function testSystemCheckHushYesterday() {
    $yesterday = new DateTime('yesterday');
    $this->_params = array(
      'name' => 'checkDefaultMailbox',
      'ignore_severity' => 7,
      'hush_until' => $yesterday->format('Y-m-d'),
    );
    $statusPreference = $this->callAPISuccess('StatusPreference', 'create', $this->_params);
    $result = $this->callAPISuccess('System', 'check', array());
    foreach ($result['values'] as $check) {
      if ($check['name'] == 'checkDefaultMailbox') {
        $testedCheck = $check;
        break;
      }
      else {
        $testedCheck = array();
      }
    }
    $this->assertEquals($testedCheck['is_visible'], '1', 'in line ' . __LINE__);
  }

  /**
   * Items hushed above current severity should be hidden.
   */
  public function testSystemCheckHushAboveSeverity() {
    $this->_params = array(
      'name' => 'checkDefaultMailbox',
      'ignore_severity' => 4,
    );
    $statusPreference = $this->callAPISuccess('StatusPreference', 'create', $this->_params);
    $result = $this->callAPISuccess('System', 'check', array());
    foreach ($result['values'] as $check) {
      if ($check['name'] == 'checkDefaultMailbox') {
        $testedCheck = $check;
        break;
      }
      else {
        $testedCheck = array();
      }
    }
    $this->assertEquals($testedCheck['is_visible'], '0', 'in line ' . __LINE__);
  }

  /**
   * Items hushed at current severity should be hidden.
   */
  public function testSystemCheckHushAtSeverity() {
    $this->_params = array(
      'name' => 'checkDefaultMailbox',
      'ignore_severity' => 3,
    );
    $statusPreference = $this->callAPISuccess('StatusPreference', 'create', $this->_params);
    $result = $this->callAPISuccess('System', 'check', array());
    foreach ($result['values'] as $check) {
      if ($check['name'] == 'checkDefaultMailbox') {
        $testedCheck = $check;
        break;
      }
      else {
        $testedCheck = array();
      }
    }
    $this->assertEquals($testedCheck['is_visible'], '0', 'in line ' . __LINE__);
  }

  /**
   * Items hushed below current severity should be shown.
   */
  public function testSystemCheckHushBelowSeverity() {
    $this->_params = array(
      'name' => 'checkDefaultMailbox',
      'ignore_severity' => 2,
    );
    $statusPreference = $this->callAPISuccess('StatusPreference', 'create', $this->_params);
    $result = $this->callAPISuccess('System', 'check', array());
    foreach ($result['values'] as $check) {
      if ($check['name'] == 'checkDefaultMailbox') {
        $testedCheck = $check;
        break;
      }
      else {
        $testedCheck = array();
      }
    }
    $this->assertEquals($testedCheck['is_visible'], '1', 'in line ' . __LINE__);
  }

}
