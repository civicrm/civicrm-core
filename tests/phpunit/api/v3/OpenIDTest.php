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

  protected $_apiversion = 3;
  protected $_params;
  protected $id;
  protected $_entity;

  public $DBResetRequired = FALSE;

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->_entity = 'OpenID';
    $this->_contactID = $this->organizationCreate();
    $this->_params = [
      'contact_id' => $this->_contactID,
      'openid' => 'My OpenID handle',
      'location_type_id' => 1,
    ];
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateOpenID($version) {
    $this->_apiversion = $version;
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->getAndCheck($this->_params, $result['id'], $this->_entity);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
  }

  /**
   * If no location is specified when creating a new openid, it should default to
   * the LocationType default
   *
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateOpenIDDefaultLocation($version) {
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
  public function testGetOpenID($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPIAndDocument($this->_entity, 'get', $this->_params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $result['id']]);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDeleteOpenID($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $deleteParams = ['id' => $result['id']];
    $result = $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', []);
    $this->assertEquals(0, $checkDeleted['count'], 'In line ' . __LINE__);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDeleteOpenIDInvalid($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $deleteParams = ['id' => 600];
    $result = $this->callAPIFailure($this->_entity, 'delete', $deleteParams);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', []);
    $this->assertEquals(1, $checkDeleted['count'], 'In line ' . __LINE__);
  }

}
