<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 *  Test APIv3 civicrm_activity_* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Contact
 */

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_AddressTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_contactID;
  protected $_locationType;
  protected $_params;
  public $_eNoticeCompliant = TRUE;
  protected $_entity;

  function setUp() {
    $this->_apiversion = 3;
    $this->_entity = 'Address';
    parent::setUp();

    $this->_contactID = $this->organizationCreate();
    $this->_locationType = $this->locationTypeCreate();
    CRM_Core_PseudoConstant::flush();

    $this->_params = array(
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
      'street_name' => 'Ambachtstraat',
      'street_number' => '23',
      'street_address' => 'Ambachtstraat 23',
      'postal_code' => '6971 BN',
      'country_id' => '1152',
      'city' => 'Brummen',
      'is_primary' => 1,
      'version' => $this->_apiversion,
    );
  }

  function tearDown() {
    $this->locationTypeDelete($this->_locationType->id);
    $this->contactDelete($this->_contactID);
  }

  public function testCreateAddress() {

    $result = civicrm_api('address', 'create', $this->_params);
    $this->documentMe($this->_params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->getAndCheck($this->_params, $result['id'], 'address');
  }
  public function testCreateAddressParsing() {
    $params = array(
      'version' => $this->_apiversion,
      'street_parsing' => 1,
      'street_address' => '54A Excelsior Ave. Apt 1C',
      'location_type_id' => $this->_locationType->id,
      'contact_id' => $this->_contactID,
    );
    $subfile     = "AddressParse";
    $description = "Demonstrates Use of address parsing param";
    $result = civicrm_api('address', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(54, $result['values'][$result['id']]['street_number'], 'In line ' . __LINE__);
    $this->assertEquals('A', $result['values'][$result['id']]['street_number_suffix'], 'In line ' . __LINE__);
    $this->assertEquals('Excelsior Ave.', $result['values'][$result['id']]['street_name'], 'In line ' . __LINE__);
    $this->assertEquals('Apt 1C', $result['values'][$result['id']]['street_unit'], 'In line ' . __LINE__);
    civicrm_api('address', 'delete', array('version' => 3, 'id' => $result['id']));

  }

  /*
     * is_primary should be set as a default
     */



  public function testCreateAddressTestDefaults() {
    $params = $this->_params;
    unset($params['is_primary']);
    $result = civicrm_api('address', 'create', $params);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['values'][$result['id']]['is_primary'], 'In line ' . __LINE__);
    $this->getAndCheck($this->_params, $result['id'], 'address');
  }

  public function testCreateAddressTooLongSuffix() {
    $params = $this->_params;
    $params['street_number_suffix'] = 'really long string';
    $result = $this->callAPIFailure('address', 'create', $params);
    $this->assertEquals(2100, $result['error_code']);
  }
  /*
     * is_primary shoule be set as a default. ie. create the address, unset the params & recreate.
     * is_primary should be 0 before & after the update. ie - having no other address
     * is_primary is invalid
     */



  public function testCreateAddressTestDefaultWithID() {
    $params = $this->_params;
    $params['is_primary'] = 0;
    $result = civicrm_api('address', 'create', $params);
    unset($params['is_primary']);
    $params['id'] = $result['id'];
    $result       = civicrm_api('address', 'create', $params);
    $address      = civicrm_api('address', 'get', array('version' => 3, 'contact_id' => $params['contact_id']));
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['values'][$result['id']]['is_primary'], 'In line ' . __LINE__);
    $this->getAndCheck($params, $result['id'], 'address', __FUNCTION__);
  }
  public function testDeleteAddress() {

    //check there are no addresss to start with
    $get = civicrm_api('address', 'get', array(
      'version' => 3,
        'location_type_id' => $this->_locationType->id,
      ));
    $this->assertEquals(0, $get['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'Contact already exists ' . __LINE__);

    //create one
    $create = civicrm_api('address', 'create', $this->_params);

    $this->assertEquals(0, $create['is_error'], 'In line ' . __LINE__);

    $result = civicrm_api('address', 'delete', array('id' => $create['id'], 'version' => 3));
    $this->documentMe($this->_params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $get = civicrm_api('address', 'get', array(
      'version' => 3,
        'location_type_id' => $this->_locationType->id,
      ));
    $this->assertEquals(0, $get['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'Contact not successfully deleted In line ' . __LINE__);
  }

  /**
   * Test civicrm_address_get - success expected.
   */
  public function testGetAddress() {
    $address = civicrm_api('address', 'create', $this->_params);
    $this->assertAPISuccess($address, 'In line ' . __LINE__);

    $params = array(
      'contact_id' => $this->_contactID,
      'street_name' => $address['values'][$address['id']]['street_name'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('Address', 'Get', ($params));
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    civicrm_api('Address', 'delete', array('version' => 3, 'id' => $result['id']));
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals($address['values'][$address['id']]['location_type_id'], $result['values'][$address['id']]['location_type_id'], 'In line ' . __LINE__);
    $this->assertEquals($address['values'][$address['id']]['is_primary'], $result['values'][$address['id']]['is_primary'], 'In line ' . __LINE__);
    $this->assertEquals($address['values'][$address['id']]['street_address'], $result['values'][$address['id']]['street_address'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_address_get - success expected.
   */
  public function testGetSingleAddress() {
    civicrm_api('address', 'create', $this->_params);
    $params = array(
      'contact_id' => $this->_contactID,
      'version' => $this->_apiversion,
    );
    $address = civicrm_api('Address', 'getsingle', ($params));
    $this->assertEquals($address['location_type_id'], $this->_params['location_type_id'], 'In line ' . __LINE__);
    civicrm_api('address', 'delete', array('version' => 3, 'id' => $address['id']));
  }

  /**
   * Test civicrm_address_get with sort option- success expected.
   */
  public function testGetAddressSort() {
    $create = civicrm_api('address', 'create', $this->_params);
    $subfile     = "AddressSort";
    $description = "Demonstrates Use of sort filter";
    $params      = array(
      'options' => array(
        'sort' => 'street_address DESC',
        'limit' => 2,
      ),
      'version' => $this->_apiversion,
      'sequential' => 1,
    );
    $result = civicrm_api('Address', 'Get', ($params));
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(2, $result['count'], 'In line ' . __LINE__);
    $this->assertEquals('Ambachtstraat 23', $result['values'][0]['street_address'], 'In line ' . __LINE__);
    civicrm_api('address', 'delete', array('version' => 3, 'id' => $create['id']));
  }

  /**
   * Test civicrm_address_get with sort option- success expected.
   */
  public function testGetAddressLikeSuccess() {
    civicrm_api('address', 'create', $this->_params);
    $subfile     = "AddressLike";
    $description = "Demonstrates Use of Like";
    $params      = array('street_address' => array('LIKE' => '%mb%'),
      'version' => $this->_apiversion,
      'sequential' => 1,
    );
    $result = civicrm_api('Address', 'Get', ($params));
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertEquals('Ambachtstraat 23', $result['values'][0]['street_address'], 'In line ' . __LINE__);
    civicrm_api('address', 'delete', array('version' => 3, 'id' => $result['id']));
  }

  /**
   * Test civicrm_address_get with sort option- success expected.
   */
  public function testGetAddressLikeFail() {
    $create = civicrm_api('address', 'create', $this->_params);
    $subfile     = "AddressLike";
    $description = "Demonstrates Use of Like";
    $params      = array('street_address' => array('LIKE' => "'%xy%'"),
      'version' => $this->_apiversion,
      'sequential' => 1,
    );
    $result = civicrm_api('Address', 'Get', ($params));
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $result['count'], 'In line ' . __LINE__);
    civicrm_api('address', 'delete', array('version' => 3, 'id' => $create['id']));
  }

  function testGetWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = civicrm_api($this->_entity, 'create', $params);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);

    $getParams = array('version' => 3, 'id' => $result['id'], 'return' => array('custom'));
    $check = civicrm_api($this->_entity, 'get', $getParams);

    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
    civicrm_api('address', 'delete', array('version' => 3, 'id' => $result['id']));
  }

  public function testCreateAddressPrimaryHandlingChangeToPrimary() {
    $params = $this->_params;
    unset($params['is_primary']);
    $address1 = civicrm_api('address', 'create', $params);
    $this->assertApiSuccess($address1, 'In line ' . __LINE__);
    //now we check & make sure it has been set to primary
    $check = civicrm_api('address', 'getcount', array(
        'version' => 3,
        'is_primary' => 1,
        'id' => $address1['id'],
      ));
    $this->assertEquals(1, $check);
    civicrm_api('address', 'delete', array('version' => 3, 'id' => $address1['id']));
  }
  public function testCreateAddressPrimaryHandlingChangeExisting() {
    $address1 = civicrm_api('address', 'create', $this->_params);
    $this->assertApiSuccess($address1, 'In line ' . __LINE__);
    $address2 = civicrm_api('address', 'create', $this->_params);
    $this->assertApiSuccess($address2, 'In line ' . __LINE__);
    $check = civicrm_api('address', 'getcount', array(
        'version' => 3,
        'is_primary' => 1,
        'contact_id' => $this->_contactID,
      ));
    $this->assertEquals(1, $check);
    civicrm_api('address', 'delete', array('version' => 3, 'id' => $address1['id']));
  }
}

