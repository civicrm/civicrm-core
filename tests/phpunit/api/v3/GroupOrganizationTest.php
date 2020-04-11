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
 * Test class for GroupOrganization API - civicrm_group_organization_*
 *
 * @package   CiviCRM
 * @group headless
 */
class api_v3_GroupOrganizationTest extends CiviUnitTestCase {
  protected $_apiversion;

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->_groupID = $this->groupCreate();

    $this->_orgID = $this->organizationCreate(NULL);
  }

  ///////////////// civicrm_group_organization_get methods

  /**
   * Test civicrm_group_organization_get with valid params.
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGroupOrganizationGet() {

    $params = [
      'organization_id' => $this->_orgID,
      'group_id' => $this->_groupID,
    ];
    $result = $this->callAPISuccess('group_organization', 'create', $params);
    $paramsGet = [
      'organization_id' => $result['id'],
    ];
    $result = $this->callAPIAndDocument('group_organization', 'get', $paramsGet, __FUNCTION__, __FILE__);
  }

  /**
   * Test civicrm_group_organization_get with group_id.
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGroupOrganizationGetWithGroupId() {
    $createParams = [
      'organization_id' => $this->_orgID,
      'group_id' => $this->_groupID,
    ];
    $createResult = $this->callAPISuccess('group_organization', 'create', $createParams);

    $getParams = [
      'group_id' => $this->_groupID,
      'sequential' => 1,
    ];
    $getResult = $this->callAPISuccess('group_organization', 'get', $getParams);
    $this->assertEquals($createResult['values'][$createResult['id']], $getResult['values'][0]);
  }

  /**
   * Test civicrm_group_organization_get with empty params.
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGroupOrganizationGetWithEmptyParams() {
    $params = [];
    $result = $this->callAPISuccess('group_organization', 'get', $params);

    $this->assertAPISuccess($result);
  }

  /**
   * Test civicrm_group_organization_get invalid keys.
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGroupOrganizationGetWithInvalidKeys() {
    $params = [
      'invalid_key' => 1,
    ];
    $result = $this->callAPISuccess('group_organization', 'get', $params);

    $this->assertAPISuccess($result);
  }

  ///////////////// civicrm_group_organization_create methods

  /**
   * Check with valid params.
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGroupOrganizationCreate() {
    $params = [
      'organization_id' => $this->_orgID,
      'group_id' => $this->_groupID,
    ];
    $result = $this->callAPIAndDocument('group_organization', 'create', $params, __FUNCTION__, __FILE__);
  }

  /**
   * CRM-13841 - Load Group Org before save
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGroupOrganizationCreateTwice() {
    $params = [
      'organization_id' => $this->_orgID,
      'group_id' => $this->_groupID,
    ];
    $result = $this->callAPISuccess('group_organization', 'create', $params);
    $result2 = $this->callAPISuccess('group_organization', 'create', $params);
    $this->assertEquals($result['values'], $result2['values']);
  }

  /**
   * Check with empty params array.
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGroupOrganizationCreateWithEmptyParams() {
    $params = [];
    $result = $this->callAPIFailure('group_organization', 'create', $params);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: group_id, organization_id');
  }

  /**
   * Check with invalid params keys.
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGroupOrganizationCreateWithInvalidKeys() {
    $params = [
      'invalid_key' => 1,
    ];
    $result = $this->callAPIFailure('group_organization', 'create', $params);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: group_id, organization_id');
  }

  ///////////////// civicrm_group_organization_remove methods

  /**
   * Test civicrm_group_organization_remove with empty params.
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGroupOrganizationDeleteWithEmptyParams() {
    $params = [];
    $result = $this->callAPIFailure('group_organization', 'delete', $params);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: id');
  }

  /**
   * Test civicrm_group_organization_remove with valid params.
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGroupOrganizationDelete() {
    $paramsC = [
      'organization_id' => $this->_orgID,
      'group_id' => $this->_groupID,
    ];
    $result = $this->callAPISuccess('group_organization', 'create', $paramsC);

    $params = [
      'id' => $result['id'],
    ];
    $result = $this->callAPIAndDocument('group_organization', 'delete', $params, __FUNCTION__, __FILE__);
  }

  /**
   * Test civicrm_group_organization_remove with invalid params key.
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGroupOrganizationDeleteWithInvalidKey() {
    $paramsDelete = [
      'invalid_key' => 1,
    ];
    $result = $this->callAPIFailure('group_organization', 'delete', $paramsDelete);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: id');
  }

}
