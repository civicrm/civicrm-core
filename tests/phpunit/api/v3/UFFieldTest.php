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
 * Test class for UFField API
 *
 * @package   CiviCRM
 * @group headless
 */
class api_v3_UFFieldTest extends CiviUnitTestCase {

  /**
   * ids from the uf_group_test.xml fixture
   *
   * @var int
   */
  protected $_ufGroupId = 11;

  protected $_ufFieldId;

  protected $_contactId = 69;

  protected $_params;

  protected $_entity = 'uf_field';

  /**
   * Set up for test.
   *
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();
    $this->quickCleanup(
      [
        'civicrm_group',
        'civicrm_contact',
        'civicrm_uf_group',
        'civicrm_uf_field',
        'civicrm_uf_join',
        'civicrm_uf_match',
      ]
    );

    $this->loadXMLDataSet(dirname(__FILE__) . '/dataset/uf_group_test.xml');

    $this->callAPISuccess('uf_field', 'getfields', ['cache_clear' => 1]);

    $this->_params = [
      'field_name' => 'phone',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 1,
      'label' => 'Test Phone',
      'is_searchable' => 1,
      'is_active' => 1,
      'location_type_id' => 1,
      'phone_type_id' => 1,
      'uf_group_id' => $this->_ufGroupId,
    ];
  }

  /**
   * Tear down function.
   *
   * @throws \Exception
   */
  public function tearDown() {
    $this->quickCleanup(
      [
        'civicrm_group',
        'civicrm_contact',
        'civicrm_uf_group',
        'civicrm_uf_join',
        'civicrm_uf_match',
      ]
    );
  }

  /**
   * Create / updating field.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateUFField($version) {
    $this->_apiversion = $version;
    $params = $this->_params;
    $ufField = $this->callAPIAndDocument('uf_field', 'create', $params, __FUNCTION__, __FILE__);
    unset($params['uf_group_id']);
    $this->_ufFieldId = $ufField['id'];
    foreach ($params as $key => $value) {
      $this->assertEquals($ufField['values'][$ufField['id']][$key], $params[$key]);
    }
  }

  /**
   * Failure test for field_name.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateUFFieldWithBadFieldName($version) {
    $this->_apiversion = $version;
    $params = $this->_params;
    $params['field_name'] = 'custom_98789';
    $this->callAPIFailure('uf_field', 'create', $params);
  }

  /**
   * Failure test for bad parameters.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateUFFieldWithWrongParams($version) {
    $this->_apiversion = $version;
    $this->callAPIFailure('uf_field', 'create', ['field_name' => 'test field']);
    $this->callAPIFailure('uf_field', 'create', ['label' => 'name-less field']);
  }

  /**
   * Create a field with 'weight=1' and then a second with 'weight=1'.
   *
   * The second field winds up with weight=1, and the first field gets bumped to 'weight=2'.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateUFFieldWithDefaultAutoWeight($version) {
    $this->_apiversion = $version;
    $params1 = $this->_params;
    $ufField1 = $this->callAPISuccess('uf_field', 'create', $params1);
    $this->assertEquals(1, $ufField1['values'][$ufField1['id']]['weight']);
    $this->assertDBQuery(1, 'SELECT weight FROM civicrm_uf_field WHERE id = %1', [
      1 => [$ufField1['id'], 'Int'],
    ]);

    $params2 = $this->_params;
    // needs to be a different field
    $params2['location_type_id'] = 2;
    $ufField2 = $this->callAPISuccess('uf_field', 'create', $params2);
    $this->assertEquals(1, $ufField2['values'][$ufField2['id']]['weight']);
    $this->assertDBQuery(1, 'SELECT weight FROM civicrm_uf_field WHERE id = %1', [
      1 => [$ufField2['id'], 'Int'],
    ]);
    $this->assertDBQuery(2, 'SELECT weight FROM civicrm_uf_field WHERE id = %1', [
      1 => [$ufField1['id'], 'Int'],
    ]);
  }

  /**
   * Deleting field.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDeleteUFField($version) {
    $this->_apiversion = $version;
    $ufField = $this->callAPISuccess('uf_field', 'create', $this->_params);
    $params = [
      'field_id' => $ufField['id'],
    ];
    $this->callAPIAndDocument('uf_field', 'delete', $params, __FUNCTION__, __FILE__);
  }

  /**
   * Test getting ufField.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetUFFieldSuccess($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPIAndDocument($this->_entity, 'get', [], __FUNCTION__, __FILE__);
    $this->getAndCheck($this->_params, $result['id'], $this->_entity);
  }

  /**
   * Create / updating field.
   */
  public function testReplaceUFFields() {
    $baseFields = [];
    $baseFields[] = [
      'field_name' => 'first_name',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 3,
      'label' => 'Test First Name',
      'is_searchable' => 1,
      'is_active' => 1,
    ];
    $baseFields[] = [
      'field_name' => 'country',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 2,
      'label' => 'Test Country',
      'is_searchable' => 1,
      'is_active' => 1,
      'location_type_id' => 1,
    ];
    $baseFields[] = [
      'field_name' => 'phone',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 1,
      'label' => 'Test Phone',
      'is_searchable' => 1,
      'is_active' => 1,
      'location_type_id' => 1,
      'phone_type_id' => 1,
    ];

    $params = [
      'uf_group_id' => $this->_ufGroupId,
      'option.autoweight' => FALSE,
      'values' => $baseFields,
      'check_permissions' => TRUE,
    ];

    $result = $this->callAPIAndDocument('uf_field', 'replace', $params, __FUNCTION__, __FILE__);
    $inputsByName = CRM_Utils_Array::index(['field_name'], $params['values']);
    $this->assertEquals(count($params['values']), count($result['values']));
    foreach ($result['values'] as $outUfField) {
      $this->assertTrue(is_string($outUfField['field_name']));
      $inUfField = $inputsByName[$outUfField['field_name']];
      foreach ($inUfField as $key => $inValue) {
        $this->assertEquals($inValue, $outUfField[$key],
          sprintf("field_name=[%s] key=[%s] expected=[%s] actual=[%s]",
            $outUfField['field_name'],
            $key,
            $inValue,
            $outUfField[$key]
          )
        );
      }
    }
  }

  /**
   * Check Profile API permission without ACL.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testProfilesWithoutACL($version) {
    $this->_apiversion = $version;
    $this->createLoggedInUser();
    $baseFields[] = [
      'field_name' => 'first_name',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 3,
      'label' => 'Test First Name',
      'is_searchable' => 1,
      'is_active' => 1,
    ];
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $params = [
      'uf_group_id' => $this->_ufGroupId,
      'option.autoweight' => FALSE,
      'values' => $baseFields,
      'check_permissions' => TRUE,
    ];
    $this->_loggedInUser = CRM_Core_Session::singleton()->get('userID');
    $this->callAPIFailure('uf_field', 'replace', $params);
  }

  /**
   * Check Profile ACL for API permission.
   */
  public function testACLPermissionforProfiles() {
    $this->createLoggedInUser();
    $this->_permissionedGroup = $this->groupCreate([
      'title' => 'Edit Profiles',
      'is_active' => 1,
      'name' => 'edit-profiles',
    ]);
    $this->setupACL(TRUE);
    $this->testReplaceUFFields();
  }

}
