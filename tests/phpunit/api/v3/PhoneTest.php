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
 *  Test APIv3 civicrm_phone* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
 */
class api_v3_PhoneTest extends CiviUnitTestCase {
  protected $_contactID;
  protected $_locationType;
  protected $_params;
  protected $_entity;

  public function setUp() {
    $this->_entity = 'Phone';
    parent::setUp();
    $this->useTransaction();

    $this->_contactID = $this->organizationCreate();
    $loc = $this->locationTypeCreate();
    $this->_locationType = $loc->id;
    CRM_Core_PseudoConstant::flush();
    $this->_params = [
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType,
      'phone' => '(123) 456-7890',
      'is_primary' => 1,
      'phone_type_id' => 1,
    ];
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreatePhone($version) {
    $this->_apiversion = $version;

    $result = $this->callAPIAndDocument('phone', 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);

    $this->callAPISuccess('phone', 'delete', ['id' => $result['id']]);
  }

  /**
   * If no location is specified when creating a new phone, it should default to
   * the LocationType default
   *
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreatePhoneDefaultLocation($version) {
    $this->_apiversion = $version;
    $params = $this->_params;
    unset($params['location_type_id']);
    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(CRM_Core_BAO_LocationType::getDefault()->id, $result['values'][$result['id']]['location_type_id']);
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $result['id']]);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDeletePhone($version) {
    $this->_apiversion = $version;
    //create one
    $create = $this->callAPISuccess('phone', 'create', $this->_params);

    $result = $this->callAPIAndDocument('phone', 'delete', ['id' => $create['id']], __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $get = $this->callAPISuccess('phone', 'get', [
      'id' => $create['id'],
      'location_type_id' => $this->_locationType,
    ]);
    $this->assertEquals(0, $get['count'], 'Phone not successfully deleted In line ' . __LINE__);
  }

  /**
   * Test civicrm_phone_get with empty params.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetEmptyParams($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess('Phone', 'Get', []);
  }

  /**
   * Test civicrm_phone_get with wrong params.
   */
  public function testGetWrongParams() {
    $this->callAPIFailure('Phone', 'Get', ['contact_id' => 'abc']);
    $this->callAPIFailure('Phone', 'Get', ['location_type_id' => 'abc']);
    $this->callAPIFailure('Phone', 'Get', ['phone_type_id' => 'abc']);
  }

  /**
   * Test civicrm_phone_get - success expected.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGet($version) {
    $this->_apiversion = $version;
    $phone = $this->callAPISuccess('phone', 'create', $this->_params);
    $params = [
      'contact_id' => $this->_params['contact_id'],
      'phone' => $phone['values'][$phone['id']]['phone'],
    ];
    $result = $this->callAPIAndDocument('Phone', 'Get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($phone['values'][$phone['id']]['location_type_id'], $result['values'][$phone['id']]['location_type_id']);
    $this->assertEquals($phone['values'][$phone['id']]['phone_type_id'], $result['values'][$phone['id']]['phone_type_id']);
    $this->assertEquals($phone['values'][$phone['id']]['is_primary'], $result['values'][$phone['id']]['is_primary']);
    $this->assertEquals($phone['values'][$phone['id']]['phone'], $result['values'][$phone['id']]['phone']);
  }

  ///////////////// civicrm_phone_create methods

  /**
   * Ensure numeric_phone field is correctly populated (this happens via sql trigger)
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testNumericPhone($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess('phone', 'create', $this->_params);
    $id = $result['id'];
    $params = ['id' => $id, 'return.phone_numeric' => 1];
    $result = $this->callAPISuccess('phone', 'get', $params);
    $this->assertEquals('1234567890', $result['values'][$id]['phone_numeric']);
  }

  /**
   * If a new phone is set to is_primary the prev should no longer be.
   *
   * If is_primary is not set then it should become is_primary is no others exist
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreatePhonePrimaryHandlingChangeToPrimary($version) {
    $this->_apiversion = $version;
    $params = $this->_params;
    unset($params['is_primary']);
    $phone1 = $this->callAPISuccess('phone', 'create', $params);
    //now we check & make sure it has been set to primary
    $check = $this->callAPISuccess('phone', 'getcount', [
      'is_primary' => 1,
      'id' => $phone1['id'],
    ]);
    $this->assertEquals(1, $check);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreatePhonePrimaryHandlingChangeExisting($version) {
    $this->_apiversion = $version;
    $phone1 = $this->callAPISuccess('phone', 'create', $this->_params);
    $phone2 = $this->callAPISuccess('phone', 'create', $this->_params);
    $check = $this->callAPISuccess('phone', 'getcount', [
      'is_primary' => 1,
      'contact_id' => $this->_contactID,
    ]);
    $this->assertEquals(1, $check);
  }

}
