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
   * Test civicrm_group_organization_get with wrong params.
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGroupOrganizationGetWithWrongParams() {
    $params = 'groupOrg';
    $result = $this->callAPIFailure('group_organization', 'get', $params);
    $this->assertEquals($result['error_message'], 'Input variable `params` is not an array');
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
   * Check with invalid params.
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGroupOrganizationCreateParamsNotArray() {
    $params = 'group_org';
    $result = $this->callAPIFailure('group_organization', 'create', $params);
    $this->assertEquals($result['error_message'], 'Input variable `params` is not an array');
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
   * Test civicrm_group_organization_remove with params not an array.
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGroupOrganizationDeleteParamsNotArray() {
    $params = 'delete';
    $result = $this->callAPIFailure('group_organization', 'delete', $params);
    $this->assertEquals($result['error_message'], 'Input variable `params` is not an array');
  }

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
