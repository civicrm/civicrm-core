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
 *  Test APIv3 civicrm_custom_group* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_CustomGroup
 * @group headless
 */
class api_v3_CustomGroupTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_entity;
  protected $_params;

  public $DBResetRequired = TRUE;

  public function setUp() {
    $this->_entity = 'CustomGroup';
    $this->_params = [
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'extends' => 'Individual',
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
    ];
    parent::setUp();
  }

  public function tearDown() {
    $tablesToTruncate = ['civicrm_custom_group', 'civicrm_custom_field'];
    // true tells quickCleanup to drop any tables that might have been created in the test
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  ///////////////// civicrm_custom_group_create methods

  /**
   * Check with empty array.
   * note that these tests are of marginal value so should not be included in copy & paste
   * code. The SyntaxConformance is capable of testing this for all entities on create
   * & delete (& it would be easy to add if not there)
   */
  public function testCustomGroupCreateNoParam() {
    $customGroup = $this->callAPIFailure('custom_group', 'create', [],
      'Mandatory key(s) missing from params array: title, extends'
    );
  }

  /**
   * Check with empty array.
   */
  public function testCustomGroupCreateNoExtends() {
    $params = [
      'domain_id' => 1,
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Tab',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
    ];

    $customGroup = $this->callAPIFailure('custom_group', 'create', $params);
    $this->assertEquals($customGroup['error_message'], 'Mandatory key(s) missing from params array: extends');
    $this->assertAPIFailure($customGroup);
  }

  /**
   * Check with empty array.
   */
  public function testCustomGroupCreateInvalidExtends() {
    $params = [
      'domain_id' => 1,
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Tab',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
      'extends' => [],
    ];

    $customGroup = $this->callAPIFailure('custom_group', 'create', $params);
    $this->assertEquals($customGroup['error_message'], 'Mandatory key(s) missing from params array: extends');
  }

  /**
   * Check with a string instead of array for extends.
   */
  public function testCustomGroupCreateExtendsString() {
    $params = [
      'domain_id' => 1,
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Tab',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
      'extends' => 'Individual',
    ];

    $customGroup = $this->callAPISuccess('custom_group', 'create', $params);
  }

  /**
   * Check with valid array.
   */
  public function testCustomGroupCreate() {
    $params = [
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'extends' => ['Individual'],
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
    ];

    $result = $this->callAPIAndDocument('custom_group', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['id']);
    $this->assertEquals($result['values'][$result['id']]['extends'], 'Individual');
  }

  /**
   * Check with valid array.
   */
  public function testCustomGroupGetFields() {
    $params = [
      'options' => ['get_options' => 'style'],
    ];

    $result = $this->callAPISuccess('custom_group', 'getfields', $params);
    $expected = [
      'Tab' => 'Tab',
      'Inline' => 'Inline',
      'Tab with table' => 'Tab with table',
    ];
    $this->assertEquals($expected, $result['values']['style']['options']);
  }

  /**
   * Check with extends array length greater than 1
   */
  public function testCustomGroupExtendsMultipleCreate() {
    $params = [
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'extends' => ['Individual', 'Household'],
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
    ];

    $result = $this->callAPIFailure('custom_group', 'create', $params,
      'implode(): Invalid arguments passed');
  }

  /**
   * Check with style missing from params array.
   */
  public function testCustomGroupCreateNoStyle() {
    $params = [
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'extends' => ['Individual'],
      'weight' => 4,
      'collapse_display' => 1,
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
    ];

    $customGroup = $this->callAPISuccess('custom_group', 'create', $params);
    $this->assertNotNull($customGroup['id']);
    $this->assertEquals($customGroup['values'][$customGroup['id']]['style'], 'Inline');
  }

  /**
   * Check with not array.
   */
  public function testCustomGroupCreateNotArray() {
    $params = NULL;
    $customGroup = $this->callAPIFailure('custom_group', 'create', $params);
    $this->assertEquals($customGroup['error_message'], 'Input variable `params` is not an array');
  }

  /**
   * Check without title.
   */
  public function testCustomGroupCreateNoTitle() {
    $params = [
      'extends' => ['Contact'],
      'weight' => 5,
      'collapse_display' => 1,
      'style' => 'Tab',
      'help_pre' => 'This is Pre Help For Test Group 2',
      'help_post' => 'This is Post Help For Test Group 2',
    ];

    $customGroup = $this->callAPIFailure('custom_group', 'create', $params,
      'Mandatory key(s) missing from params array: title');
  }

  /**
   * Check for household without weight.
   */
  public function testCustomGroupCreateHouseholdNoWeight() {
    $params = [
      'title' => 'Test_Group_3',
      'name' => 'test_group_3',
      'extends' => ['Household'],
      'collapse_display' => 1,
      'style' => 'Tab',
      'help_pre' => 'This is Pre Help For Test Group 3',
      'help_post' => 'This is Post Help For Test Group 3',
      'is_active' => 1,
    ];

    $customGroup = $this->callAPISuccess('custom_group', 'create', $params);
    $this->assertNotNull($customGroup['id']);
    $this->assertEquals($customGroup['values'][$customGroup['id']]['extends'], 'Household');
    $this->assertEquals($customGroup['values'][$customGroup['id']]['style'], 'Tab');
  }

  /**
   * Check for Contribution Donation.
   */
  public function testCustomGroupCreateContributionDonation() {
    $params = [
      'title' => 'Test_Group_6',
      'name' => 'test_group_6',
      'extends' => ['Contribution', [1]],
      'weight' => 6,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 6',
      'help_post' => 'This is Post Help For Test Group 6',
      'is_active' => 1,
    ];

    $customGroup = $this->callAPISuccess('custom_group', 'create', $params);
    $this->assertNotNull($customGroup['id']);
    $this->assertEquals($customGroup['values'][$customGroup['id']]['extends'], 'Contribution');
  }

  /**
   * Check with valid array.
   */
  public function testCustomGroupCreateGroup() {
    $params = [
      'domain_id' => 1,
      'title' => 'Test_Group_8',
      'name' => 'test_group_8',
      'extends' => ['Group'],
      'weight' => 7,
      'collapse_display' => 1,
      'is_active' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 8',
      'help_post' => 'This is Post Help For Test Group 8',
    ];

    $customGroup = $this->callAPISuccess('CustomGroup', 'create', $params);
    $this->assertNotNull($customGroup['id']);
    $this->assertEquals($customGroup['values'][$customGroup['id']]['extends'], 'Group');
  }

  /**
   * Test an empty update does not trigger e-notices when is_multiple has been set.
   */
  public function testCustomGroupEmptyUpdate() {
    $customGroup = $this->callAPISuccess('CustomGroup', 'create', array_merge($this->_params, ['is_multiple' => 1]));
    $this->callAPISuccess('CustomGroup', 'create', ['id' => $customGroup['id']]);
  }

  /**
   * Test an update when is_multiple is an emtpy string this can occur in form submissions for custom groups that extend activites.
   * dev/core#227.
   */
  public function testCustomGroupEmptyisMultipleUpdate() {
    $customGroup = $this->callAPISuccess('CustomGroup', 'create', array_merge($this->_params, ['is_multiple' => 0]));
    $this->callAPISuccess('CustomGroup', 'create', ['id' => $customGroup['id'], 'is_multiple' => '']);
  }

  /**
   * Check with Activity - Meeting Type
   */
  public function testCustomGroupCreateActivityMeeting() {
    $params = [
      'title' => 'Test_Group_10',
      'name' => 'test_group_10',
      'extends' => ['Activity', [1]],
      'weight' => 8,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 10',
      'help_post' => 'This is Post Help For Test Group 10',
    ];

    $customGroup = $this->callAPISuccess('custom_group', 'create', $params);
    $this->assertNotNull($customGroup['id']);
    $this->assertEquals($customGroup['values'][$customGroup['id']]['extends'], 'Activity');
  }

  ///////////////// civicrm_custom_group_delete methods

  /**
   * Check without GroupID.
   */
  public function testCustomGroupDeleteWithoutGroupID() {
    $customGroup = $this->callAPIFailure('custom_group', 'delete', []);
    $this->assertEquals($customGroup['error_message'], 'Mandatory key(s) missing from params array: id');
  }

  /**
   * Check with no array.
   */
  public function testCustomGroupDeleteNoArray() {
    $params = NULL;
    $customGroup = $this->callAPIFailure('custom_group', 'delete', $params);
    $this->assertEquals($customGroup['error_message'], 'Input variable `params` is not an array');
  }

  /**
   * Check with valid custom group id.
   */
  public function testCustomGroupDelete() {
    $customGroup = $this->customGroupCreate(['extends' => 'Individual', 'title' => 'test_group']);
    $params = [
      'id' => $customGroup['id'],
    ];
    $result = $this->callAPIAndDocument('custom_group', 'delete', $params, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);
  }

  /**
   * Main success get function.
   */
  public function testGetCustomGroupSuccess() {

    $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $params = [];
    $result = $this->callAPIAndDocument($this->_entity, 'get', $params, __FUNCTION__, __FILE__);
    $values = $result['values'][$result['id']];
    foreach ($this->_params as $key => $value) {
      if ($key == 'weight') {
        continue;
      }
      $this->assertEquals($value, $values[$key], $key . " doesn't match " . print_r($values, TRUE) . 'in line' . __LINE__);
    }
  }

  public function testUpdateCustomGroup() {
    $customGroup = $this->customGroupCreate();
    $customGroupId = $customGroup['id'];

    //update is_active
    $params = ['id' => $customGroupId, 'is_active' => 0];
    $result = $this->callAPISuccess('CustomGroup', 'create', $params);
    $result = array_shift($result['values']);

    $this->assertEquals(0, $result['is_active']);
    $this->customGroupDelete($customGroupId);
  }

}
