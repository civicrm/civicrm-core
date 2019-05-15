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
 *  Test APIv3 civicrm_country* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
 */
class api_v3_CountryTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_params;

  public function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->_params = array(
      'name' => 'Made Up Land',
      'iso_code' => 'ZZ',
      'region_id' => 1,
    );
  }

  public function testCreateCountry() {

    $result = $this->callAPIAndDocument('country', 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);

    $this->callAPISuccess('country', 'delete', array('id' => $result['id']));
  }

  public function testDeleteCountry() {
    //create one
    $create = $this->callAPISuccess('country', 'create', $this->_params);

    $result = $this->callAPIAndDocument('country', 'delete', array('id' => $create['id']), __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $get = $this->callAPISuccess('country', 'get', array(
      'id' => $create['id'],
    ));
    $this->assertEquals(0, $get['count'], 'Country not successfully deleted');
  }

  /**
   * Test civicrm_phone_get with empty params.
   */
  public function testGetEmptyParams() {
    $result = $this->callAPISuccess('Country', 'Get', array());
  }

  /**
   * Test civicrm_phone_get with wrong params.
   */
  public function testGetWrongParams() {
    $this->callAPIFailure('Country', 'Get', array('id' => 'abc'));
  }

  /**
   * Test civicrm_phone_get - success expected.
   */
  public function testGet() {
    $country = $this->callAPISuccess('Country', 'create', $this->_params);
    $params = array(
      'iso_code' => $this->_params['iso_code'],
    );
    $result = $this->callAPIAndDocument('Country', 'Get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($country['values'][$country['id']]['name'], $result['values'][$country['id']]['name']);
    $this->assertEquals($country['values'][$country['id']]['iso_code'], $result['values'][$country['id']]['iso_code']);
  }

  ///////////////// civicrm_country_create methods

  /**
   * If a new country is created and it is created again it should not create a second one.
   * We check on the iso code (there should be only one iso code
   */
  public function testCreateDuplicateFail() {
    $params = $this->_params;
    unset($params['id']);
    $this->callAPISuccess('country', 'create', $params);
    $this->callAPIFailure('country', 'create', $params);
    $check = $this->callAPISuccess('country', 'getcount', array(
      'iso_code' => $params['iso_code'],
    ));
    $this->assertEquals(1, $check);
  }

}
