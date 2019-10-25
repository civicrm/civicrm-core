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
 *  Test APIv3 civicrm_state_province* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
 */
class api_v3_StateProvinceTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_params;

  public function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->_params = [
      'name' => 'Wessex',
      'abbreviation' => 'WEX',
      'country_id' => 1226,
    ];
  }

  public function testCreateStateProvince() {
    $result = $this->callAPIAndDocument('StateProvince', 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->callAPISuccess('StateProvince', 'delete', ['id' => $result['id']]);
  }

  public function testDeleteStateProvince() {
    // Create
    $create = $this->callAPISuccess('StateProvince', 'create', $this->_params);

    // Delete
    $result = $this->callAPIAndDocument('StateProvince', 'delete', ['id' => $create['id']], __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $get = $this->callAPISuccess('StateProvince', 'get', [
      'id' => $create['id'],
    ]);
    $this->assertEquals(0, $get['count'], 'State/province not successfully deleted');
  }

  /**
   * Test with empty params
   */
  public function testGetEmptyParams() {
    $result = $this->callAPISuccess('StateProvince', 'Get', []);
  }

  /**
   * Test with wrong params
   */
  public function testGetWrongParams() {
    $this->callAPIFailure('StateProvince', 'Get', ['id' => 'abc']);
  }

  /**
   * Test get
   */
  public function testGet() {
    $province = $this->callAPISuccess('StateProvince', 'create', $this->_params);
    $params = [
      'name' => $this->_params['name'],
    ];
    $result = $this->callAPIAndDocument('StateProvince', 'Get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($province['values'][$province['id']]['name'], $result['values'][$province['id']]['name']);
    $this->assertEquals($province['values'][$province['id']]['abbreviation'], $result['values'][$province['id']]['abbreviation']);
  }

  /**
   * There cannot be two state/provinces with the same name in the same country.
   */
  public function testCreateDuplicateFail() {
    $params = $this->_params;
    unset($params['id']);
    $this->callAPISuccess('StateProvince', 'create', $params);
    $this->callAPIFailure('StateProvince', 'create', $params);
    $check = $this->callAPISuccess('StateProvince', 'getcount', [
      'name' => $params['name'],
    ]);
    $this->assertEquals(1, $check);
  }

}
