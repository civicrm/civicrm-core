<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
  protected $_apiversion =3;
  protected $_contactID;
  protected $_locationType;
  protected $_params;

  protected $_entity;

  function setUp() {
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
    );
  }

  function tearDown() {
    $this->locationTypeDelete($this->_locationType->id);
    $this->contactDelete($this->_contactID);
  }

  public function testCreateAddress() {
    $result = $this->callAPIAndDocument('address', 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->getAndCheck($this->_params, $result['id'], 'address');
  }

  public function testCreateAddressParsing() {
    $params = array(
      'street_parsing' => 1,
      'street_address' => '54A Excelsior Ave. Apt 1C',
      'location_type_id' => $this->_locationType->id,
      'contact_id' => $this->_contactID,
    );
    $subfile     = "AddressParse";
    $description = "Demonstrates Use of address parsing param";
    $result = $this->callAPIAndDocument('address', 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(54, $result['values'][$result['id']]['street_number'], 'In line ' . __LINE__);
    $this->assertEquals('A', $result['values'][$result['id']]['street_number_suffix'], 'In line ' . __LINE__);
    $this->assertEquals('Excelsior Ave.', $result['values'][$result['id']]['street_name'], 'In line ' . __LINE__);
    $this->assertEquals('Apt 1C', $result['values'][$result['id']]['street_unit'], 'In line ' . __LINE__);
    $this->callAPISuccess('address', 'delete', array('id' => $result['id']));

  }

  /*
     * is_primary should be set as a default
     */



  public function testCreateAddressTestDefaults() {
    $params = $this->_params;
    unset($params['is_primary']);
    $result = $this->callAPISuccess('address', 'create', $params);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['values'][$result['id']]['is_primary'], 'In line ' . __LINE__);
    $this->getAndCheck($this->_params, $result['id'], 'address');
  }

  public function testCreateAddressTooLongSuffix() {
    $params = $this->_params;
    $params['street_number_suffix'] = 'really long string';
    $result = $this->callAPIFailure('address', 'create', $params);
   }
  /*
     * is_primary shoule be set as a default. ie. create the address, unset the params & recreate.
     * is_primary should be 0 before & after the update. ie - having no other address
     * is_primary is invalid
     */



  public function testCreateAddressTestDefaultWithID() {
    $params = $this->_params;
    $params['is_primary'] = 0;
    $result = $this->callAPISuccess('address', 'create', $params);
    unset($params['is_primary']);
    $params['id'] = $result['id'];
    $result       = $this->callAPISuccess('address', 'create', $params);
    $address      = $this->callAPISuccess('address', 'get', array('contact_id' => $params['contact_id']));
   $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['values'][$result['id']]['is_primary'], 'In line ' . __LINE__);
    $this->getAndCheck($params, $result['id'], 'address', __FUNCTION__);
  }
  public function testDeleteAddress() {

    //check there are no addresss to start with
    $get = $this->callAPISuccess('address', 'get', array(
      'location_type_id' => $this->_locationType->id,
    ));
    $this->assertEquals(0, $get['count'], 'Contact already exists ' . __LINE__);

    //create one
    $create = $this->callAPISuccess('address', 'create', $this->_params);

    $result = $this->callAPIAndDocument('address', 'delete', array('id' => $create['id'],), __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $get = $this->callAPISuccess('address', 'get', array(
     'location_type_id' => $this->_locationType->id,
    ));
    $this->assertEquals(0, $get['count'], 'Contact not successfully deleted In line ' . __LINE__);
  }

  /**
   * Test civicrm_address_get - success expected.
   */
  public function testGetAddress() {
    $address = $this->callAPISuccess('address', 'create', $this->_params);

    $params = array(
      'contact_id' => $this->_contactID,
      'street_name' => $address['values'][$address['id']]['street_name'],
    );
    $result = $this->callAPIAndDocument('Address', 'Get', $params, __FUNCTION__, __FILE__);
    $this->callAPISuccess('Address', 'delete', array('id' => $result['id']));
    $this->assertEquals($address['values'][$address['id']]['location_type_id'], $result['values'][$address['id']]['location_type_id'], 'In line ' . __LINE__);
    $this->assertEquals($address['values'][$address['id']]['is_primary'], $result['values'][$address['id']]['is_primary'], 'In line ' . __LINE__);
    $this->assertEquals($address['values'][$address['id']]['street_address'], $result['values'][$address['id']]['street_address'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_address_get - success expected.
   */
  public function testGetSingleAddress() {
    $this->callAPISuccess('address', 'create', $this->_params);
    $params = array(
      'contact_id' => $this->_contactID,
    );
    $address = $this->callAPISuccess('Address', 'getsingle', ($params));
    $this->assertEquals($address['location_type_id'], $this->_params['location_type_id'], 'In line ' . __LINE__);
    $this->callAPISuccess('address', 'delete', array('id' => $address['id']));
  }

  /**
   * Test civicrm_address_get with sort option- success expected.
   */
  public function testGetAddressSort() {
    $create = $this->callAPISuccess('address', 'create', $this->_params);
    $subfile     = "AddressSort";
    $description = "Demonstrates Use of sort filter";
    $params      = array(
      'options' => array(
        'sort' => 'street_address DESC',
        'limit' => 2,
      ),
      'sequential' => 1,
    );
    $result = $this->callAPIAndDocument('Address', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(2, $result['count'], 'In line ' . __LINE__);
    $this->assertEquals('Ambachtstraat 23', $result['values'][0]['street_address'], 'In line ' . __LINE__);
    $this->callAPISuccess('address', 'delete', array('id' => $create['id']));
  }

  /**
   * Test civicrm_address_get with sort option- success expected.
   */
  public function testGetAddressLikeSuccess() {
    $this->callAPISuccess('address', 'create', $this->_params);
    $subfile     = "AddressLike";
    $description = "Demonstrates Use of Like";
    $params      = array('street_address' => array('LIKE' => '%mb%'),
      'sequential' => 1,
    );
    $result = $this->callAPIAndDocument('Address', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertEquals('Ambachtstraat 23', $result['values'][0]['street_address'], 'In line ' . __LINE__);
    $this->callAPISuccess('address', 'delete', array('id' => $result['id']));
  }

  /**
   * Test civicrm_address_get with sort option- success expected.
   */
  public function testGetAddressLikeFail() {
    $create = $this->callAPISuccess('address', 'create', $this->_params);
    $subfile     = "AddressLike";
    $description = "Demonstrates Use of Like";
    $params      = array('street_address' => array('LIKE' => "'%xy%'"),
      'sequential' => 1,
    );
    $result = $this->callAPISuccess('Address', 'Get', ($params));
    $this->assertEquals(0, $result['count'], 'In line ' . __LINE__);
    $this->callAPISuccess('address', 'delete', array('id' => $create['id']));
  }

  function testGetWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = $this->callAPISuccess($this->_entity, 'create', $params);

    $getParams = array('id' => $result['id'], 'return' => array('custom'));
    $check = $this->callAPISuccess($this->_entity, 'get', $getParams);

    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
    $this->callAPISuccess('address', 'delete', array('id' => $result['id']));
  }

  public function testCreateAddressPrimaryHandlingChangeToPrimary() {
    $params = $this->_params;
    unset($params['is_primary']);
    $address1 = $this->callAPISuccess('address', 'create', $params);
    $this->assertApiSuccess($address1, 'In line ' . __LINE__);
    //now we check & make sure it has been set to primary
    $check = $this->callAPISuccess('address', 'getcount', array(
        'is_primary' => 1,
        'id' => $address1['id'],
      ));
    $this->assertEquals(1, $check);
    $this->callAPISuccess('address', 'delete', array('id' => $address1['id']));
  }
  public function testCreateAddressPrimaryHandlingChangeExisting() {
    $address1 = $this->callAPISuccess('address', 'create', $this->_params);
    $address2 = $this->callAPISuccess('address', 'create', $this->_params);
    $check = $this->callAPISuccess('address', 'getcount', array(
        'is_primary' => 1,
        'contact_id' => $this->_contactID,
      ));
    $this->assertEquals(1, $check);
    $this->callAPISuccess('address', 'delete', array('id' => $address1['id']));
  }
}

