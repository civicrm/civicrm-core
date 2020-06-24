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
 *  Test APIv3 civicrm_im_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
 */
class api_v3_ImTest extends CiviUnitTestCase {
  protected $_params;
  protected $id;
  protected $_entity;

  public $DBResetRequired = FALSE;

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->_entity = 'im';
    $this->_contactID = $this->organizationCreate();
    $this->_params = [
      'contact_id' => $this->_contactID,
      'name' => 'My Yahoo IM Handle',
      'location_type_id' => 1,
      'provider_id' => 1,
    ];
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   *
   * @throws \Exception
   */
  public function testCreateIm($version) {
    $this->_apiversion = $version;
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->getAndCheck($this->_params, $result['id'], $this->_entity);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  /**
   * If no location is specified when creating a new IM, it should default to
   * the LocationType default
   *
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateImDefaultLocation($version) {
    $this->_apiversion = $version;
    $params = $this->_params;
    unset($params['location_type_id']);
    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(CRM_Core_BAO_LocationType::getDefault()->id, $result['values'][$result['id']]['location_type_id']);
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $result['id']]);
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetIm($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPIAndDocument($this->_entity, 'get', $this->_params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $result['id']]);
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testDeleteIm($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $deleteParams = ['id' => $result['id']];
    $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', []);
    $this->assertEquals(0, $checkDeleted['count']);
  }

  /**
   * Skip api4 test - delete behaves differently
   */
  public function testDeleteImInvalid() {
    $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $deleteParams = ['id' => 600];
    $this->callAPIFailure($this->_entity, 'delete', $deleteParams);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', []);
    $this->assertEquals(1, $checkDeleted['count']);
  }

}
