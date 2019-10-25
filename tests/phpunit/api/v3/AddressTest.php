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
 *  Test APIv3 civicrm_activity_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 */

/**
 * Class api_v3_AddressTest
 * @group headless
 */
class api_v3_AddressTest extends CiviUnitTestCase {
  protected $_contactID;
  protected $_locationType;
  protected $_params;

  protected $_entity;

  public function setUp() {
    $this->_entity = 'Address';
    parent::setUp();

    $this->_contactID = $this->organizationCreate();
    $this->_locationType = $this->locationTypeCreate();
    CRM_Core_PseudoConstant::flush();

    $this->_params = [
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
      'street_name' => 'Ambachtstraat',
      'street_number' => '23',
      'street_address' => 'Ambachtstraat 23',
      'postal_code' => '6971 BN',
      'country_id' => '1152',
      'city' => 'Brummen',
      'is_primary' => 1,
    ];
  }

  public function tearDown() {
    $this->locationTypeDelete($this->_locationType->id);
    $this->contactDelete($this->_contactID);
    $this->quickCleanup(['civicrm_address', 'civicrm_relationship']);
    parent::tearDown();
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateAddress($version) {
    $this->_apiversion = $version;
    $result = $this->callAPIAndDocument('address', 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck($this->_params, $result['id'], 'address');
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateAddressParsing($version) {
    $this->_apiversion = $version;
    $params = [
      'street_parsing' => 1,
      'street_address' => '54A Excelsior Ave. Apt 1C',
      'location_type_id' => $this->_locationType->id,
      'contact_id' => $this->_contactID,
    ];
    $subfile = "AddressParse";
    $description = "Demonstrates Use of address parsing param.";
    $result = $this->callAPIAndDocument('address', 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(54, $result['values'][$result['id']]['street_number']);
    $this->assertEquals('A', $result['values'][$result['id']]['street_number_suffix']);
    $this->assertEquals('Excelsior Ave.', $result['values'][$result['id']]['street_name']);
    $this->assertEquals('Apt 1C', $result['values'][$result['id']]['street_unit']);
    $this->callAPISuccess('address', 'delete', ['id' => $result['id']]);

  }

  /**
   * Is_primary should be set as a default.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateAddressTestDefaults($version) {
    $this->_apiversion = $version;
    $params = $this->_params;
    unset($params['is_primary']);
    $result = $this->callAPISuccess('address', 'create', $params);
    $this->assertEquals(1, $result['count']);
    $this->assertEquals(1, $result['values'][$result['id']]['is_primary']);
    $this->getAndCheck($this->_params, $result['id'], 'address');
  }

  /**
   * If no location is specified when creating a new address, it should default to
   * the LocationType default
   *
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateAddressDefaultLocation($version) {
    $this->_apiversion = $version;
    $params = $this->_params;
    unset($params['location_type_id']);
    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(CRM_Core_BAO_LocationType::getDefault()->id, $result['values'][$result['id']]['location_type_id']);
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $result['id']]);
  }

  /**
   * FIXME: Api4
   */
  public function testCreateAddressTooLongSuffix() {
    $params = $this->_params;
    $params['street_number_suffix'] = 'really long string';
    $this->callAPIFailure('address', 'create', $params);
  }

  /**
   * Create an address with a master ID and ensure that a relationship is created.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateAddressWithMasterRelationshipHousehold($version) {
    $this->_apiversion = $version;
    $householdID = $this->householdCreate();
    $address = $this->callAPISuccess('address', 'create', array_merge($this->_params, $this->_params, ['contact_id' => $householdID]));
    $individualID = $this->individualCreate();
    $individualParams = [
      'contact_id' => $individualID,
      'master_id' => $address['id'],
    ];
    $this->callAPISuccess('address', 'create', array_merge($this->_params, $individualParams));
    $this->callAPISuccess('relationship', 'getcount', [
      'contact_id_a' => $individualID,
      'contact_id_b' => $this->_contactID,
    ]);
  }

  /**
   * Create an address with a master ID and ensure that a relationship is created.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateAddressWithMasterRelationshipOrganization($version) {
    $this->_apiversion = $version;
    $address = $this->callAPISuccess('address', 'create', $this->_params);
    $individualID = $this->individualCreate();
    $individualParams = [
      'contact_id' => $individualID,
      'master_id' => $address['id'],
    ];
    $this->callAPISuccess('address', 'create', array_merge($this->_params, $individualParams));
    $this->callAPISuccess('relationship', 'getcount', [
      'contact_id_a' => $individualID,
      'contact_id_b' => $this->_contactID,
    ], 1);
  }

  /**
   * Create an address with a master ID and relationship creation disabled.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateAddressWithoutMasterRelationshipOrganization($version) {
    $this->_apiversion = $version;
    $address = $this->callAPISuccess('address', 'create', $this->_params);
    $individualID = $this->individualCreate();
    $individualParams = [
      'contact_id' => $individualID,
      'master_id' => $address['id'],
      'update_current_employer' => 0,
    ];
    $this->callAPISuccess('address', 'create', array_merge($this->_params, $individualParams));
    $this->callAPISuccess('relationship', 'getcount', [
      'contact_id_a' => $individualID,
      'contact_id_b' => $this->_contactID,
    ], 0);
  }

  /**
   * Create an address with a master ID and ensure that a relationship is created.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateAddressWithMasterRelationshipChangingOrganization($version) {
    $this->_apiversion = $version;
    $address = $this->callAPISuccess('address', 'create', $this->_params);
    $organisation2ID = $this->organizationCreate();
    $address2 = $this->callAPISuccess('address', 'create', array_merge($this->_params, ['contact_id' => $organisation2ID]));
    $individualID = $this->individualCreate();
    $individualParams = array_merge($this->_params, [
      'contact_id' => $individualID,
      'master_id' => $address['id'],
    ]);
    $individualAddress = $this->callAPISuccess('address', 'create', $individualParams);
    $individualParams['master_id'] = $address2['id'];
    $individualParams['id'] = $individualAddress['id'];
    $this->callAPISuccess('address', 'create', $individualParams);
    $this->callAPISuccessGetCount('relationship', ['contact_id_a' => $individualID], 2);
    $this->markTestIncomplete('Remainder of test checks that employer relationship is disabled when new one is created but turns out to be not happening - by design?');
    $this->callAPISuccessGetCount('relationship', ['contact_id_a' => $individualID, 'is_active' => FALSE], 1);
    $this->callAPISuccessGetCount('relationship', [
      'contact_id_a' => $individualID,
      'is_active' => TRUE,
      'contact_id_b' => $organisation2ID,
    ], 1);

  }

  /**
   * Is_primary should be set as a default.
   *
   * ie. create the address, unset the params & recreate.
   * is_primary should be 0 before & after the update. ie - having no other address
   * is_primary is invalid.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateAddressTestDefaultWithID($version) {
    $this->_apiversion = $version;
    $params = $this->_params;
    $params['is_primary'] = 0;
    $result = $this->callAPISuccess('address', 'create', $params);
    unset($params['is_primary']);
    $params['id'] = $result['id'];
    $this->callAPISuccess('address', 'create', $params);
    $result = $this->callAPISuccess('address', 'get', ['contact_id' => $params['contact_id']]);
    $this->assertEquals(1, $result['count']);
    $this->assertEquals(1, $result['values'][$result['id']]['is_primary']);
    $this->getAndCheck($params, $result['id'], 'address', __FUNCTION__);
  }

  /**
   * test address deletion.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDeleteAddress($version) {
    $this->_apiversion = $version;
    //check there are no address to start with
    $get = $this->callAPISuccess('address', 'get', [
      'location_type_id' => $this->_locationType->id,
    ]);
    $this->assertEquals(0, $get['count'], 'Contact already exists ');

    //create one
    $create = $this->callAPISuccess('address', 'create', $this->_params);

    $result = $this->callAPIAndDocument('address', 'delete', ['id' => $create['id']], __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $get = $this->callAPISuccess('address', 'get', [
      'location_type_id' => $this->_locationType->id,
    ]);
    $this->assertEquals(0, $get['count'], 'Contact not successfully deleted In line ' . __LINE__);
  }

  /**
   * Test civicrm_address_get - success expected.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetAddress($version) {
    $this->_apiversion = $version;
    $address = $this->callAPISuccess('address', 'create', $this->_params);

    $params = [
      'contact_id' => $this->_contactID,
      'street_name' => $address['values'][$address['id']]['street_name'],
    ];
    $result = $this->callAPIAndDocument('Address', 'Get', $params, __FUNCTION__, __FILE__);
    $this->callAPISuccess('Address', 'delete', ['id' => $result['id']]);
    $this->assertEquals($address['values'][$address['id']]['location_type_id'], $result['values'][$address['id']]['location_type_id']);
    $this->assertEquals($address['values'][$address['id']]['is_primary'], $result['values'][$address['id']]['is_primary']);
    $this->assertEquals($address['values'][$address['id']]['street_address'], $result['values'][$address['id']]['street_address']);
  }

  /**
   * Test civicrm_address_get - success expected.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetSingleAddress($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess('address', 'create', $this->_params);
    $params = [
      'contact_id' => $this->_contactID,
    ];
    $address = $this->callAPISuccess('Address', 'getsingle', ($params));
    $this->assertEquals($address['location_type_id'], $this->_params['location_type_id']);
    $this->callAPISuccess('address', 'delete', ['id' => $address['id']]);
  }

  /**
   * Test civicrm_address_get with sort option- success expected.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetAddressSort($version) {
    $this->_apiversion = $version;
    $create = $this->callAPISuccess('address', 'create', $this->_params);
    $this->callAPISuccess('address', 'create', array_merge($this->_params, ['street_address' => 'yzy']));
    $subfile = "AddressSort";
    $description = "Demonstrates Use of sort filter.";
    $params = [
      'options' => [
        'sort' => 'street_address DESC',
        'limit' => 2,
      ],
      'sequential' => 1,
    ];
    $result = $this->callAPIAndDocument('Address', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(2, $result['count']);
    $this->assertEquals('Ambachtstraat 23', $result['values'][1]['street_address']);
    $this->callAPISuccess('address', 'delete', ['id' => $create['id']]);
  }

  /**
   * Test civicrm_address_get with sort option- success expected.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetAddressLikeSuccess($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess('address', 'create', $this->_params);
    $subfile = "AddressLike";
    $description = "Demonstrates Use of Like.";
    $params = [
      'street_address' => ['LIKE' => '%mb%'],
      'sequential' => 1,
    ];
    $result = $this->callAPIAndDocument('Address', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(1, $result['count']);
    $this->assertEquals('Ambachtstraat 23', $result['values'][0]['street_address']);
    $this->callAPISuccess('address', 'delete', ['id' => $result['id']]);
  }

  /**
   * Test civicrm_address_get with sort option- success expected.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetAddressLikeFail($version) {
    $this->_apiversion = $version;
    $create = $this->callAPISuccess('address', 'create', $this->_params);
    $params = [
      'street_address' => ['LIKE' => "'%xy%'"],
      'sequential' => 1,
    ];
    $result = $this->callAPISuccess('Address', 'Get', ($params));
    $this->assertEquals(0, $result['count']);
    $this->callAPISuccess('address', 'delete', ['id' => $create['id']]);
  }

  /**
   * FIXME: Api4 custom address fields broken?
   */
  public function testGetWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = $this->callAPISuccess($this->_entity, 'create', $params);

    $getParams = ['id' => $result['id'], 'return' => ['custom']];
    $check = $this->callAPISuccess($this->_entity, 'get', $getParams);

    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
    $this->callAPISuccess('address', 'delete', ['id' => $result['id']]);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateAddressPrimaryHandlingChangeToPrimary($version) {
    $this->_apiversion = $version;
    $params = $this->_params;
    unset($params['is_primary']);
    $address1 = $this->callAPISuccess('address', 'create', $params);
    $this->assertApiSuccess($address1);
    //now we check & make sure it has been set to primary
    $check = $this->callAPISuccess('address', 'getcount', [
      'is_primary' => 1,
      'id' => $address1['id'],
    ]);
    $this->assertEquals(1, $check);
    $this->callAPISuccess('address', 'delete', ['id' => $address1['id']]);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateAddressPrimaryHandlingChangeExisting($version) {
    $this->_apiversion = $version;
    $address1 = $this->callAPISuccess('address', 'create', $this->_params);
    $this->callAPISuccess('address', 'create', $this->_params);
    $check = $this->callAPISuccess('address', 'getcount', [
      'is_primary' => 1,
      'contact_id' => $this->_contactID,
    ]);
    $this->assertEquals(1, $check);
    $this->callAPISuccess('address', 'delete', ['id' => $address1['id']]);
  }

  /**
   * Test Creating address of same type alreay ind the database
   * This is legacy API v3 behaviour and not correct behaviour
   * however we are too far down the path wiwth v3 to fix this
   * @link https://chat.civicrm.org/civicrm/pl/zcq3jkg69jdt5g4aqze6bbe9pc
   * FIXME: Api4
   */
  public function testCreateDuplicateLocationTypes() {
    $address1 = $this->callAPISuccess('address', 'create', $this->_params);
    $address2 = $this->callAPISuccess('address', 'create', [
      'location_type_id' => $this->_locationType->id,
      'street_address' => '1600 Pensilvania Avenue',
      'city' => 'Washington DC',
      'is_primary' => 0,
      'is_billing' => 0,
      'contact_id' => $this->_contactID,
    ]);
    $check = $this->callAPISuccess('address', 'getcount', [
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
    ]);
    $this->assertEquals(2, $check);
    $this->callAPISuccess('address', 'delete', ['id' => $address1['id']]);
    $this->callAPISuccess('address', 'delete', ['id' => $address2['id']]);
  }

  public function testGetWithJoin() {
    $cid = $this->individualCreate([
      'api.Address.create' => [
        'street_address' => __FUNCTION__,
        'location_type_id' => $this->_locationType->id,
      ],
    ]);
    $result = $this->callAPISuccess('address', 'getsingle', [
      'check_permissions' => TRUE,
      'contact_id' => $cid,
      'street_address' => __FUNCTION__,
      'return' => 'contact_id.contact_type',
    ]);
    $this->assertEquals('Individual', $result['contact_id.contact_type']);
  }

  /**
   * Test Address create with a state name that at least two countries have, e.g. Maryland, United States vs. Maryland, Liberia
   *
   * @see https://lab.civicrm.org/dev/core/issues/725
   */
  public function testCreateAddressStateProvinceIDCorrectForCountry() {
    $params = $this->_params;
    $params['sequential'] = 1;
    // United States country id
    $params['country_id'] = '1228';
    $params['state_province_id'] = 'Maryland';
    $params['city'] = 'Baltimore';
    $params['street_address'] = '600 N Charles St.';
    $params['postal_code'] = '21201';
    unset($params['street_name']);
    unset($params['street_number']);
    $address1 = $this->callAPISuccess('address', 'create', $params);
    // should find state_province_id of 1019, Maryland, United States ... NOT 3497, Maryland, Liberia
    $this->assertEquals('1019', $address1['values'][0]['state_province_id']);

    // Now try it in Liberia
    $params = $this->_params;
    $params['sequential'] = 1;
    // Liberia country id
    $params['country_id'] = '1122';
    $params['state_province_id'] = 'Maryland';
    $address2 = $this->callAPISuccess('address', 'create', $params);
    $this->assertEquals('3497', $address2['values'][0]['state_province_id']);
  }

  public function getSymbolicCountryStateExamples() {
    return [
      // [mixed $inputCountry, mixed $inputState, int $expectCountry, int $expectState]
      [1228, 1004, 1228, 1004],
      //['US', 'CA', 1228, 1004],
      //['US', 'TX', 1228, 1042],
      ['US', 'California', 1228, 1004],
      [1228, 'Texas', 1228, 1042],
      // Don't think these have been supported?
      // ['United States', 1004, 1228, 1004] ,
      // ['United States', 'TX', 1228, 1042],
    ];
  }

  /**
   * @param mixed $inputCountry
   *   Ex: 1228 or 'US'
   * @param mixed $inputState
   *   Ex: 1004 or 'CA'
   * @param int $expectCountry
   * @param int $expectState
   * @dataProvider getSymbolicCountryStateExamples
   */
  public function testCreateAddressSymbolicCountryAndState($inputCountry, $inputState, $expectCountry, $expectState) {
    $cid = $this->individualCreate();
    $r = $this->callAPISuccess('Address', 'create', [
      'contact_id' => $cid,
      'location_type_id' => 1,
      'street_address' => '123 Some St',
      'city' => 'Hereville',
      //'US',
      'country_id' => $inputCountry,
      // 'California',
      'state_province_id' => $inputState,
      'postal_code' => '94100',
    ]);
    $created = CRM_Utils_Array::first($r['values']);
    $this->assertEquals($expectCountry, $created['country_id']);
    $this->assertEquals($expectState, $created['state_province_id']);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testBuildStateProvinceOptionsWithDodgyProvinceLimit($version) {
    $this->_apiversion = $version;
    $provinceLimit = [1228, "abcd;ef"];
    $this->callAPISuccess('setting', 'create', [
      'provinceLimit' => $provinceLimit,
    ]);
    $result = $this->callAPIFailure('address', 'getoptions', ['field' => 'state_province_id']);
    // confirm that we hit our error not a SQLI.
    $this->assertEquals('Province limit or default country setting is incorrect', $result['error_message']);
    $this->callAPISuccess('setting', 'create', [
      'provinceLimit' => [1228],
    ]);
    // Now confirm with a correct province setting it works fine
    $this->callAPISuccess('address', 'getoptions', ['field' => 'state_province_id']);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testBuildCountryWithDodgyCountryLimitSetting($version) {
    $this->_apiversion = $version;
    $countryLimit = [1228, "abcd;ef"];
    $this->callAPISuccess('setting', 'create', [
      'countryLimit' => $countryLimit,
    ]);
    $result = $this->callAPIFailure('address', 'getoptions', ['field' => 'country_id']);
    // confirm that we hit our error not a SQLI.
    $this->assertEquals('Available Country setting is incorrect', $result['error_message']);
    $this->callAPISuccess('setting', 'create', [
      'countryLimit' => [1228],
    ]);
    // Now confirm with a correct province setting it works fine
    $this->callAPISuccess('address', 'getoptions', ['field' => 'country_id']);
  }

  public function testBuildCountyWithDodgeStateProvinceFiltering() {
    $result = $this->callAPIFailure('Address', 'getoptions', [
      'field' => 'county_id',
      'state_province_id' => "abcd;ef",
    ]);
    $this->assertEquals('Can only accept Integers for state_province_id filtering', $result['error_message']);
    $goodResult = $this->callAPISuccess('Address', 'getoptions', [
      'field' => 'county_id',
      'state_province_id' => 1004,
    ]);
    $this->assertEquals('San Francisco', $goodResult['values'][4]);
  }

  public function testGetOptionsAbbr() {
    $result = $this->callAPISuccess('Address', 'getoptions', [
      'field' => 'country_id',
      'context' => "abbreviate",
    ]);
    $this->assertContains('US', $result['values']);
    $this->assertNotContains('United States', $result['values']);
    $result = $this->callAPISuccess('Address', 'getoptions', [
      'field' => 'state_province_id',
      'context' => "abbreviate",
    ]);
    $this->assertContains('AL', $result['values']);
    $this->assertNotContains('Alabama', $result['values']);
  }

}
