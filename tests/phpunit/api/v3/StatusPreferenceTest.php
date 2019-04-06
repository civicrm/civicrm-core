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
 *  Class api_v3_StatusPreferenceTest
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_StatusPreferenceTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_contactID;
  protected $_locationType;
  protected $_params;

  public function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->_params = array(
      'name' => 'test_check',
      'domain_id' => 1,
      'hush_until' => '20151212',
      'ignore_severity' => 4,
      'check_info' => NULL,
    );
  }

  public function testCreateStatusPreference() {
    $result = $this->callAPIAndDocument('StatusPreference', 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['id'], 'In line ' . __LINE__);
    $id = $result['id'];
    $this->assertEquals('test_check', $result['values'][$id]['name'], 'In line ' . __LINE__);
    $this->assertEquals(4, $result['values'][$id]['ignore_severity'], 'In line ' . __LINE__);

    $this->callAPISuccess('StatusPreference', 'delete', array('id' => $result['id']));
  }

  public function testDeleteStatusPreference() {
    // create one
    $create = $this->callAPISuccess('StatusPreference', 'create', $this->_params);

    $result = $this->callAPIAndDocument('StatusPreference', 'delete', array('id' => $create['id']), __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);

    $get = $this->callAPISuccess('StatusPreference', 'get', array(
      'id' => $create['id'],
    ));
    $this->assertEquals(0, $get['count'], 'Status Preference not successfully deleted In line ' . __LINE__);
  }

  /**
   * Test a get with empty params.
   */
  public function testStatusPreferenceGetEmptyParams() {
    $result = $this->callAPISuccess('StatusPreference', 'Get', array());
  }

  /**
   * Test a StatusPreference get.
   */
  public function testStatusPreferenceGet() {
    $statusPreference = $this->callAPISuccess('StatusPreference', 'create', $this->_params);
    $id = $statusPreference['id'];
    $params = array(
      'id' => $id,
    );
    $result = $this->callAPIAndDocument('StatusPreference', 'Get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($statusPreference['values'][$id]['name'], $result['values'][$id]['name'], 'In line ' . __LINE__);
    $this->assertEquals($statusPreference['values'][$id]['domain_id'], $result['values'][$id]['domain_id'], 'In line ' . __LINE__);
    $this->assertEquals('2015-12-12', $result['values'][$id]['hush_until'], 'In line ' . __LINE__);
    $this->assertEquals($statusPreference['values'][$id]['ignore_severity'], $result['values'][$id]['ignore_severity'], 'In line ' . __LINE__);
  }

  /**
   * Ensure you can't create a StatusPref with ignore_severity > 7.
   */
  public function testCreateInvalidMinimumReportSeverity() {
    $this->_params['ignore_severity'] = 45;
    $result = $this->callAPIFailure('StatusPreference', 'create', $this->_params);
  }

  /**
   * Test creating a severity by name, not integer.
   */
  public function testCreateSeverityByName() {
    // Any permutation of uppercase/lowercase should work.
    $this->_params['ignore_severity'] = 'cRItical';
    $result = $this->callAPIAndDocument('StatusPreference', 'create', $this->_params, __FUNCTION__, __FILE__);
    $id = $result['id'];
    $this->assertEquals(5, $result['values'][$id]['ignore_severity'], 'In line ' . __LINE__);
  }

  /**
   * Test creating an invalid severity by name.
   */
  public function testCreateSeverityWithInvalidName() {
    $this->_params['ignore_severity'] = 'wdsadasdarning';
    $result = $this->callAPIFailure('StatusPreference', 'create', $this->_params);
  }

}
