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
 *  Test APIv3 civicrm_openid_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
 */
class api_v3_OpenIDTest extends CiviUnitTestCase {

  /**
   * Should location types be checked to ensure primary addresses are correctly assigned after each test.
   *
   * @var bool
   */
  protected $isLocationTypesOnPostAssert = TRUE;

  protected $_params;
  protected $id;
  protected $_entity;

  public $DBResetRequired = FALSE;

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();

    $this->_entity = 'OpenID';
    $this->_params = [
      'contact_id' => $this->organizationCreate(),
      'openid' => 'My OpenID handle',
      'location_type_id' => 1,
      'sequential' => 1,
    ];
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testCreateOpenID($version) {
    $this->_apiversion = $version;
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->_params, __FUNCTION__, __FILE__)['values'];
    $this->assertCount(1, $result);
    unset($this->_params['sequential']);
    $this->getAndCheck($this->_params, $result[0]['id'], $this->_entity);
  }

  /**
   * If no location is specified when creating a new openid, it should default to
   * the LocationType default
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testCreateOpenIDDefaultLocation($version) {
    $this->_apiversion = $version;
    $params = $this->_params;
    unset($params['location_type_id']);
    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__)['values'];
    $this->assertEquals(CRM_Core_BAO_LocationType::getDefault()->id, $result[0]['location_type_id']);
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $result[0]['id']]);
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testGetOpenID($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPIAndDocument($this->_entity, 'get', $this->_params, __FUNCTION__, __FILE__)['values'];
    $this->assertCount(1, $result);
    $this->assertNotNull($result[0]['id']);
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $result[0]['id']]);
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testDeleteOpenID($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $deleteParams = ['id' => $result['id']];
    $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get');
    $this->assertEquals(0, $checkDeleted['count']);
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testDeleteOpenIDInvalid($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $deleteParams = ['id' => 600];
    $this->callAPIFailure($this->_entity, 'delete', $deleteParams);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get');
    $this->assertEquals(1, $checkDeleted['count']);
  }

}
