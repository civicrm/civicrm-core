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
