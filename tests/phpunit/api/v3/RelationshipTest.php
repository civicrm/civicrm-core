<?php
/**
 * +--------------------------------------------------------------------+
 * | CiviCRM version 5                                                  |
 * +--------------------------------------------------------------------+
 * | Copyright CiviCRM LLC (c) 2004-2019                                |
 * +--------------------------------------------------------------------+
 * | This file is a part of CiviCRM.                                    |
 * |                                                                    |
 * | CiviCRM is free software; you can copy, modify, and distribute it  |
 * | under the terms of the GNU Affero General Public License           |
 * | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 * |                                                                    |
 * | CiviCRM is distributed in the hope that it will be useful, but     |
 * | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 * | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 * | See the GNU Affero General Public License for more details.        |
 * |                                                                    |
 * | You should have received a copy of the GNU Affero General Public   |
 * | License and the CiviCRM Licensing Exception along                  |
 * | with this program; if not, contact CiviCRM LLC                     |
 * | at info[AT]civicrm[DOT]org. If you have questions about the        |
 * | GNU Affero General Public License or the licensing of CiviCRM,     |
 * | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 * +--------------------------------------------------------------------+
 */

/**
 * Class contains api test cases for "civicrm_relationship"
 * @group headless
 */
class api_v3_RelationshipTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  protected $_cId_a;
  /**
   * Second individual.
   *
   * @var int
   */
  protected $_cId_a_2;
  protected $_cId_b;
  /**
   * Second organization contact.
   *
   * @var  int
   */
  protected $_cId_b2;
  protected $_relTypeID;
  protected $_ids = [];
  protected $_customFieldId = NULL;
  protected $_params;

  protected $entity;

  /**
   * Set up function.
   */
  public function setUp() {
    parent::setUp();
    $this->_cId_a = $this->individualCreate();
    $this->_cId_a_2 = $this->individualCreate([
      'last_name' => 'c2',
      'email' => 'c@w.com',
      'contact_type' => 'Individual',
    ]);
    $this->_cId_b = $this->organizationCreate();
    $this->_cId_b2 = $this->organizationCreate(['organization_name' => ' Org 2']);
    $this->entity = 'Relationship';
    //Create a relationship type.
    $relTypeParams = [
      'name_a_b' => 'Relation 1 for delete',
      'name_b_a' => 'Relation 2 for delete',
      'description' => 'Testing relationship type',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
    ];

    $this->_relTypeID = $this->relationshipTypeCreate($relTypeParams);
    $this->_params = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
    ];

  }

  /**
   * Tear down function.
   *
   * @throws \Exception
   */
  public function tearDown() {
    $this->contactDelete($this->_cId_a);
    $this->contactDelete($this->_cId_a_2);
    $this->contactDelete($this->_cId_b);
    $this->contactDelete($this->_cId_b2);
    $this->quickCleanup(['civicrm_relationship'], TRUE);
    $this->relationshipTypeDelete($this->_relTypeID);
    parent::tearDown();
  }

  /**
   * Check with empty array.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipCreateEmpty($version) {
    $this->_apiversion = $version;
    $this->callAPIFailure('relationship', 'create', []);
  }

  /**
   * Test Current Employer is correctly set.
   */
  public function testCurrentEmployerRelationship() {
    $employerRelationshipID = $this->callAPISuccessGetValue('RelationshipType', [
      'return' => "id",
      'name_b_a' => "Employer Of",
    ]);
    $employerRelationship = $this->callAPISuccess('Relationship', 'create', [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $employerRelationshipID,
    ]);
    $params = [$this->_cId_a => $this->_cId_b];
    CRM_Contact_BAO_Contact_Utils::setCurrentEmployer($params);

    //Check if current employer is correctly set.
    $employer = $this->callAPISuccessGetValue('Contact', [
      'return' => "current_employer",
      'id' => $this->_cId_a,
    ]);
    $organisation = $this->callAPISuccessGetValue('Contact', [
      'return' => "sort_name",
      'id' => $this->_cId_b,
    ]);
    $this->assertEquals($employer, $organisation);

    //Update relationship type
    $update = $this->callAPISuccess('Relationship', 'create', [
      'id' => $employerRelationship['id'],
      'relationship_type_id' => $this->_relTypeID,
    ]);
    $employeeContact = $this->callAPISuccessGetSingle('Contact', [
      'return' => ["current_employer"],
      'id' => $this->_cId_a,
    ]);
    //current employer should be removed.
    $this->assertEmpty($employeeContact['current_employer']);
  }

  /**
   * Check if required fields are not passed.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipCreateWithoutRequired($version) {
    $this->_apiversion = $version;
    $params = [
      'start_date' => ['d' => '10', 'M' => '1', 'Y' => '2008'],
      'end_date' => ['d' => '10', 'M' => '1', 'Y' => '2009'],
      'is_active' => 1,
    ];

    $this->callAPIFailure('relationship', 'create', $params);
  }

  /**
   * Check with incorrect required fields.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipCreateWithIncorrectData($version) {
    $this->_apiversion = $version;

    $params = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => 'Breaking Relationship',
    ];

    $this->callAPIFailure('relationship', 'create', $params);

    //contact id is not an integer
    $params = [
      'contact_id_a' => 'invalid',
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => ['d' => '10', 'M' => '1', 'Y' => '2008'],
      'is_active' => 1,
    ];
    $this->callAPIFailure('relationship', 'create', $params);

    // Contact id does not exist.
    $params['contact_id_a'] = 999;
    $this->callAPIFailure('relationship', 'create', $params);

    //invalid date
    $params['contact_id_a'] = $this->_cId_a;
    $params['start_date'] = ['d' => '1', 'M' => '1'];
    $this->callAPIFailure('relationship', 'create', $params);
  }

  /**
   * Check relationship creation with invalid Relationship.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipCreateInvalidRelationship($version) {
    $this->_apiversion = $version;
    // Both have the contact type Individual.
    $params = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_a,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-01-10',
      'is_active' => 1,
    ];

    $this->callAPIFailure('relationship', 'create', $params);

    // both the contact of type Organization
    $params = [
      'contact_id_a' => $this->_cId_b,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-01-10',
      'is_active' => 1,
    ];

    $this->callAPIFailure('relationship', 'create', $params);
  }

  /**
   * Check relationship already exists.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipCreateAlreadyExists($version) {
    $this->_apiversion = $version;
    $params = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'end_date' => NULL,
      'is_active' => 1,
    ];
    $relationship = $this->callAPISuccess('relationship', 'create', $params);

    $params = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
    ];
    $this->callAPIFailure('relationship', 'create', $params, 'Duplicate Relationship');

    $params['id'] = $relationship['id'];
    $this->callAPISuccess('relationship', 'delete', $params);
  }

  /**
   * Check relationship already exists.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipCreateUpdateAlreadyExists($version) {
    $this->_apiversion = $version;
    $params = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'end_date' => NULL,
      'is_active' => 1,

    ];
    $relationship = $this->callAPISuccess('relationship', 'create', $params);
    $params = [
      'id' => $relationship['id'],
      'is_active' => 0,
      'debug' => 1,
    ];
    $this->callAPISuccess('relationship', 'create', $params);
    $this->callAPISuccess('relationship', 'get', $params);
    $params['id'] = $relationship['id'];
    $this->callAPISuccess('relationship', 'delete', $params);
  }

  /**
   * Check update doesn't reset stuff badly - CRM-11789.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipCreateUpdateDoesNotMangle($version) {
    $this->_apiversion = $version;
    $params = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
      'is_permission_a_b' => 1,
      'description' => 'my desc',
    ];
    $relationship = $this->callAPISuccess('relationship', 'create', $params);

    $updateParams = [
      'id' => $relationship['id'],
      'relationship_type_id' => $this->_relTypeID,
    ];
    $this->callAPISuccess('relationship', 'create', $updateParams);

    //make sure the orig params didn't get changed
    $this->getAndCheck($params, $relationship['id'], 'relationship');

  }

  /**
   * Check relationship creation.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipCreate($version) {
    $this->_apiversion = $version;
    $params = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2010-10-30',
      'end_date' => '2010-12-30',
      'is_active' => 1,
      'note' => 'note',
    ];

    $result = $this->callAPIAndDocument('relationship', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['id']);
    $relationParams = [
      'id' => $result['id'],
    ];

    // assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Contact_DAO_Relationship', $result['id'], $relationParams);
    $result = $this->callAPISuccess('relationship', 'get', ['id' => $result['id']]);
    $values = $result['values'][$result['id']];
    foreach ($params as $key => $value) {
      if ($key == 'note') {
        continue;
      }
      $this->assertEquals($value, $values[$key], $key . " doesn't match " . print_r($values, TRUE));
    }
    $params['id'] = $result['id'];
    $this->callAPISuccess('relationship', 'delete', $params);
  }

  /**
   * Ensure disabling works.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipUpdate($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess('relationship', 'create', $this->_params);
    $relID = $result['id'];
    $result = $this->callAPISuccess('relationship', 'create', ['id' => $relID, 'description' => 'blah']);
    $this->assertEquals($relID, $result['id']);

    $this->assertEquals('blah', $result['values'][$result['id']]['description']);

    $result = $this->callAPISuccess('relationship', 'create', ['id' => $relID, 'is_permission_b_a' => 1]);
    $this->assertEquals(1, $result['values'][$result['id']]['is_permission_b_a']);
    $result = $this->callAPISuccess('relationship', 'create', ['id' => $result['id'], 'is_active' => 0]);
    $result = $this->callAPISuccess('relationship', 'get', ['id' => $result['id']]);
    $this->assertEquals(0, $result['values'][$result['id']]['is_active']);
    $this->assertEquals('blah', $result['values'][$result['id']]['description']);
    $this->assertEquals(1, $result['values'][$result['id']]['is_permission_b_a']);
  }

  /**
   * Check relationship creation.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipCreateEmptyEndDate($version) {
    $this->_apiversion = $version;
    $params = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2010-10-30',
      'end_date' => '',
      'is_active' => 1,
      'note' => 'note',
    ];

    $result = $this->callAPISuccess('relationship', 'create', $params);
    $this->assertNotNull($result['id']);
    $relationParams = [
      'id' => $result['id'],
    ];

    // assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Contact_DAO_Relationship', $result['id'], $relationParams);
    $result = $this->callAPISuccess('relationship', 'get', ['id' => $result['id']]);
    $values = $result['values'][$result['id']];
    foreach ($params as $key => $value) {
      if ($key == 'note') {
        continue;
      }
      if ($key == 'end_date') {
        $this->assertTrue(empty($values[$key]));
        continue;
      }
      $this->assertEquals($value, $values[$key], $key . " doesn't match " . print_r($values, TRUE) . 'in line' . __LINE__);
    }
    $params['id'] = $result['id'];
    $this->callAPISuccess('relationship', 'delete', $params);
  }

  /**
   * Check relationship creation with custom data.
   * FIXME: Api4
   */
  public function testRelationshipCreateEditWithCustomData() {
    $this->createCustomGroupWithFieldsOfAllTypes();
    //few custom Values for comparing
    $custom_params = [
      $this->getCustomFieldName('text') => 'Hello! this is custom data for relationship',
      $this->getCustomFieldName('select_string') => 'Y',
      $this->getCustomFieldName('select_date') => '2009-07-11 00:00:00',
      $this->getCustomFieldName('link') => 'http://example.com',
    ];

    $params = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
    ];
    $params = array_merge($params, $custom_params);
    $result = $this->callAPISuccess('relationship', 'create', $params);

    $relationParams = ['id' => $result['id']];
    $this->assertDBState('CRM_Contact_DAO_Relationship', $result['id'], $relationParams);

    //Test Edit of custom field from the form.
    $getParams = ['id' => $result['id']];
    $updateParams = array_merge($getParams, [
      $this->getCustomFieldName('text') => 'Edited Text Value',
      'relationship_type_id' => $this->_relTypeID . '_b_a',
      'related_contact_id' => $this->_cId_a,
    ]);
    $reln = new CRM_Contact_Form_Relationship();
    $reln->_action = CRM_Core_Action::UPDATE;
    $reln->_relationshipId = $result['id'];
    $reln->submit($updateParams);

    $check = $this->callAPISuccess('relationship', 'get', $getParams);
    $this->assertEquals("Edited Text Value", $check['values'][$check['id']][$this->getCustomFieldName('text')]);

    $params['id'] = $result['id'];
    $this->callAPISuccess('relationship', 'delete', $params);
    $this->relationshipTypeDelete($this->_relTypeID);
  }

  /**
   * Check with complete array + custom field
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   * FIXME: Api4
   */
  public function testGetWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = $this->callAPISuccess($this->entity, 'create', $params);
    $this->assertEquals($result['id'], $result['values'][$result['id']]['id']);

    $getParams = ['id' => $result['id']];
    $check = $this->callAPIAndDocument($this->entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * Check if required fields are not passed.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipDeleteWithoutRequired($version) {
    $this->_apiversion = $version;
    $params = [
      'start_date' => '2008-12-20',
      'end_date' => '2009-12-20',
      'is_active' => 1,
    ];

    $this->callAPIFailure('relationship', 'delete', $params);
  }

  /**
   * Check with incorrect required fields.
   */
  public function testRelationshipDeleteWithIncorrectData() {
    $params = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => 'Breaking Relationship',
    ];

    $this->callAPIFailure('relationship', 'delete', $params, 'Mandatory key(s) missing from params array: id');

    $params['id'] = "Invalid";
    $this->callAPIFailure('relationship', 'delete', $params, 'id is not a valid integer');
  }

  /**
   * Check relationship creation.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipDelete($version) {
    $this->_apiversion = $version;
    $params = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
    ];

    $result = $this->callAPISuccess('relationship', 'create', $params);
    $params = ['id' => $result['id']];
    $this->callAPIAndDocument('relationship', 'delete', $params, __FUNCTION__, __FILE__);
    $this->relationshipTypeDelete($this->_relTypeID);
  }

  ///////////////// civicrm_relationship_update methods

  /**
   * Check with empty array.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipUpdateEmpty($version) {
    $this->_apiversion = $version;
    $this->callAPIFailure('relationship', 'create', [],
      'contact_id_a, contact_id_b, relationship_type_id');
  }

  /**
   * Check if required fields are not passed.
   */

  /**
   * Check relationship update.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipCreateDuplicate($version) {
    $this->_apiversion = $version;
    $relParams = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '20081214',
      'end_date' => '20091214',
      'is_active' => 1,
    ];

    $result = $this->callAPISuccess('relationship', 'create', $relParams);

    $this->assertNotNull($result['id']);

    $params = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '20081214',
      'end_date' => '20091214',
      'is_active' => 0,
    ];

    $this->callAPIFailure('relationship', 'create', $params, 'Duplicate Relationship');

    $this->callAPISuccess('relationship', 'delete', ['id' => $result['id']]);
    $this->relationshipTypeDelete($this->_relTypeID);
  }

  /**
   * CRM-13725 - Two relationships of same type with same start and end date
   * should be OK if the custom field values differ.
   * FIXME: Api4
   */
  public function testRelationshipCreateDuplicateWithCustomFields() {
    $this->createCustomGroupWithFieldsOfAllTypes();

    $custom_params_1 = [
      $this->getCustomFieldName('text') => 'Hello! this is custom data for relationship',
      $this->getCustomFieldName('select_string') => 'Y',
      $this->getCustomFieldName('select_date') => '2009-07-11 00:00:00',
      $this->getCustomFieldName('link') => 'http://example.com',
    ];

    $custom_params_2 = [
      $this->getCustomFieldName('text') => 'Hello! this is other custom data',
      $this->getCustomFieldName('select_string') => 'Y',
      $this->getCustomFieldName('select_date') => '2009-07-11 00:00:00',
      $this->getCustomFieldName('link') => 'http://example.org',
    ];

    $params = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
    ];

    $params_1 = array_merge($params, $custom_params_1);
    $params_2 = array_merge($params, $custom_params_2);

    $result_1 = $this->callAPISuccess('relationship', 'create', $params_1);
    $result_2 = $this->callAPISuccess('relationship', 'create', $params_2);

    $this->assertNotNull($result_2['id']);
    $this->assertEquals(0, $result_2['is_error']);

    $this->relationshipTypeDelete($this->_relTypeID);
  }

  /**
   * CRM-13725 - Two relationships of same type with same start and end date
   * should be OK if the custom field values differ. In this case, the
   * existing relationship does not have custom values, but the new one
   * does.
   * FIXME: Api4
   */
  public function testRelationshipCreateDuplicateWithCustomFields2() {
    $this->createCustomGroupWithFieldsOfAllTypes();

    $custom_params_2 = [
      $this->getCustomFieldName('text') => 'Hello! this is other custom data',
      $this->getCustomFieldName('select_string') => 'Y',
      $this->getCustomFieldName('select_date') => '2009-07-11 00:00:00',
      $this->getCustomFieldName('link') => 'http://example.org',
    ];

    $params_1 = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
    ];

    $params_2 = array_merge($params_1, $custom_params_2);

    $this->callAPISuccess('relationship', 'create', $params_1);
    $result_2 = $this->callAPISuccess('relationship', 'create', $params_2);

    $this->assertNotNull($result_2['id']);
    $this->assertEquals(0, $result_2['is_error']);

    $this->relationshipTypeDelete($this->_relTypeID);
  }

  /**
   * CRM-13725 - Two relationships of same type with same start and end date
   * should be OK if the custom field values differ. In this case, the
   * existing relationship does have custom values, but the new one
   * does not.
   * FIXME: Api4
   */
  public function testRelationshipCreateDuplicateWithCustomFields3() {
    $this->createCustomGroupWithFieldsOfAllTypes();

    $custom_params_1 = [
      $this->getCustomFieldName('text') => 'Hello! this is other custom data',
      $this->getCustomFieldName('select_string') => 'Y',
      $this->getCustomFieldName('select_date') => '2009-07-11 00:00:00',
      $this->getCustomFieldName('link') => 'http://example.org',
    ];

    $params_2 = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
    ];

    $params_1 = array_merge($params_2, $custom_params_1);

    $this->callAPISuccess('relationship', 'create', $params_1);
    $result_2 = $this->callAPISuccess('relationship', 'create', $params_2);

    $this->assertNotNull($result_2['id']);
    $this->assertEquals(0, $result_2['is_error']);

    $this->relationshipTypeDelete($this->_relTypeID);
  }

  /**
   * Check with valid params array.
   */
  public function testRelationshipsGet() {
    $relParams = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2011-01-01',
      'end_date' => '2013-01-01',
      'is_active' => 1,
    ];

    $this->callAPISuccess('relationship', 'create', $relParams);

    //get relationship
    $params = [
      'contact_id' => $this->_cId_b,
    ];
    $result = $this->callAPISuccess('relationship', 'get', $params);
    $this->assertEquals($result['count'], 1);
    $params = [
      'contact_id_a' => $this->_cId_a,
    ];
    $result = $this->callAPISuccess('relationship', 'get', $params);
    $this->assertEquals($result['count'], 1);
    // contact_id_a is wrong so should be no matches
    $params = [
      'contact_id_a' => $this->_cId_b,
    ];
    $result = $this->callAPISuccess('relationship', 'get', $params);
    $this->assertEquals($result['count'], 0);
  }

  /**
   * Chain Relationship.get and to Contact.get.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipGetWithChainedCall($version) {
    $this->_apiversion = $version;
    // Create a relationship.
    $createResult = $this->callAPISuccess('relationship', 'create', $this->_params);
    $id = $createResult['id'];

    // Try to retrieve it using chaining.
    $params = [
      'relationship_type_id' => $this->_relTypeID,
      'id' => $id,
      'api.Contact.get' => [
        'id' => '$value.contact_id_b',
      ],
    ];

    $result = $this->callAPISuccess('relationship', 'get', $params);

    $this->assertEquals(1, $result['count']);
    $relationship = CRM_Utils_Array::first($result['values']);
    $this->assertEquals(1, $relationship['api.Contact.get']['count']);
    $contact = CRM_Utils_Array::first($relationship['api.Contact.get']['values']);
    $this->assertEquals($this->_cId_b, $contact['id']);
  }

  /**
   * Chain Contact.get to Relationship.get and again to Contact.get.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipGetInChainedCall($version) {
    $this->_apiversion = $version;
    // Create a relationship.
    $this->callAPISuccess('relationship', 'create', $this->_params);

    // Try to retrieve it using chaining.
    $params = [
      'id' => $this->_cId_a,
      'api.Relationship.get' => [
        'relationship_type_id' => $this->_relTypeID,
        'contact_id_a' => '$value.id',
        'api.Contact.get' => [
          'id' => '$value.contact_id_b',
        ],
      ],
    ];

    $result = $this->callAPISuccess('contact', 'get', $params);
    $this->assertEquals(1, $result['count']);
    $contact = CRM_Utils_Array::first($result['values']);
    $this->assertEquals(1, $contact['api.Relationship.get']['count']);
    $relationship = CRM_Utils_Array::first($contact['api.Relationship.get']['values']);
    $this->assertEquals(1, $relationship['api.Contact.get']['count']);
    $contact = CRM_Utils_Array::first($relationship['api.Contact.get']['values']);
    $this->assertEquals($this->_cId_b, $contact['id']);
  }

  /**
   * Check with valid params array.
   * (The get function will behave differently without 'contact_id' passed
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipsGetGeneric($version) {
    $this->_apiversion = $version;
    $relParams = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2011-01-01',
      'end_date' => '2013-01-01',
      'is_active' => 1,
    ];

    $this->callAPISuccess('relationship', 'create', $relParams);

    //get relationship
    $params = [
      'contact_id_b' => $this->_cId_b,
    ];
    $this->callAPISuccess('relationship', 'get', $params);
  }

  /**
   * Test retrieving only current relationships.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetIsCurrent($version) {
    $this->_apiversion = $version;
    $rel2Params = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b2,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 0,
    ];
    $rel0 = $this->callAPISuccess('relationship', 'create', $rel2Params);
    $rel1 = $this->callAPISuccess('relationship', 'create', $this->_params);

    $getParams = ['filters' => ['is_current' => 1]];
    $description = "Demonstrates is_current filter.";
    $subfile = 'filterIsCurrent';
    $result = $this->callAPIAndDocument('relationship', 'get', $getParams, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 1);
    $this->AssertEquals($rel1['id'], $result['id']);

    // now try not started
    $rel2Params['is_active'] = 1;
    $rel2Params['start_date'] = 'tomorrow';
    $rel2 = $this->callAPISuccess('relationship', 'create', $rel2Params);

    // now try finished
    $rel2Params['start_date'] = 'last week';
    $rel2Params['end_date'] = 'yesterday';
    $rel3 = $this->callAPISuccess('relationship', 'create', $rel2Params);

    $result = $this->callAPISuccess('relationship', 'get', $getParams);
    $this->assertEquals($result['count'], 1);
    $this->AssertEquals($rel1['id'], $result['id']);

    foreach ([$rel0, $rel1, $rel2, $rel3] as $rel) {
      $this->callAPISuccess('Relationship', 'delete', $rel);
    }
  }

  /**
   * Test using various operators.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetTypeOperators($version) {
    $this->_apiversion = $version;
    $relTypeParams = [
      'name_a_b' => 'Relation 3 for delete',
      'name_b_a' => 'Relation 6 for delete',
      'description' => 'Testing relationship type 2',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
    ];
    $relationType2 = $this->relationshipTypeCreate($relTypeParams);
    $relTypeParams = [
      'name_a_b' => 'Relation 8 for delete',
      'name_b_a' => 'Relation 9 for delete',
      'description' => 'Testing relationship type 7',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
    ];
    $relationType3 = $this->relationshipTypeCreate($relTypeParams);

    $relTypeParams = [
      'name_a_b' => 'Relation 6 for delete',
      'name_b_a' => 'Relation 88for delete',
      'description' => 'Testing relationship type 00',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
    ];
    $relationType4 = $this->relationshipTypeCreate($relTypeParams);

    $rel1 = $this->callAPISuccess('relationship', 'create', $this->_params);
    $rel2 = $this->callAPISuccess('relationship', 'create', array_merge($this->_params,
      ['relationship_type_id' => $relationType2]));
    $rel3 = $this->callAPISuccess('relationship', 'create', array_merge($this->_params,
      ['relationship_type_id' => $relationType3]));
    $rel4 = $this->callAPISuccess('relationship', 'create', array_merge($this->_params,
      ['relationship_type_id' => $relationType4]));

    $getParams = [
      'relationship_type_id' => ['IN' => [$relationType2, $relationType3]],
    ];

    $description = "Demonstrates use of IN filter.";
    $subfile = 'INRelationshipType';

    $result = $this->callAPIAndDocument('relationship', 'get', $getParams, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 2);
    $this->AssertEquals([$rel2['id'], $rel3['id']], array_keys($result['values']));

    $description = "Demonstrates use of NOT IN filter.";
    $subfile = 'NotInRelationshipType';
    $getParams = [
      'relationship_type_id' => ['NOT IN' => [$relationType2, $relationType3]],
    ];
    $result = $this->callAPIAndDocument('relationship', 'get', $getParams, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 2);
    $this->AssertEquals([$rel1['id'], $rel4['id']], array_keys($result['values']));

    $description = "Demonstrates use of BETWEEN filter.";
    $subfile = 'BetweenRelationshipType';
    $getParams = [
      'relationship_type_id' => ['BETWEEN' => [$relationType2, $relationType4]],
    ];
    $result = $this->callAPIAndDocument('relationship', 'get', $getParams, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 3);
    $this->AssertEquals([$rel2['id'], $rel3['id'], $rel4['id']], array_keys($result['values']));

    $description = "Demonstrates use of Not BETWEEN filter.";
    $subfile = 'NotBetweenRelationshipType';
    $getParams = [
      'relationship_type_id' => ['NOT BETWEEN' => [$relationType2, $relationType4]],
    ];
    $result = $this->callAPIAndDocument('relationship', 'get', $getParams, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 1);
    $this->AssertEquals([$rel1['id']], array_keys($result['values']));

    foreach ([$relationType2, $relationType3, $relationType4] as $id) {
      $this->callAPISuccess('RelationshipType', 'delete', ['id' => $id]);
    }
  }

  /**
   * Check with invalid relationshipType Id.
   */
  public function testRelationshipTypeAddInvalidId() {
    $relTypeParams = [
      'id' => 'invalid',
      'name_a_b' => 'Relation 1 for delete',
      'name_b_a' => 'Relation 2 for delete',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
    ];
    $this->callAPIFailure('relationship_type', 'create', $relTypeParams,
      'id is not a valid integer');
  }

  /**
   * Check with valid data with contact_b.
   */
  public function testGetRelationshipWithContactB() {
    $relParams = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2011-01-01',
      'end_date' => '2013-01-01',
      'is_active' => 1,
    ];

    $relationship = $this->callAPISuccess('relationship', 'create', $relParams);

    $contacts = [
      'contact_id' => $this->_cId_a,
    ];

    $result = $this->callAPISuccess('relationship', 'get', $contacts);
    $this->assertGreaterThan(0, $result['count']);
    $params = [
      'id' => $relationship['id'],
    ];
    $this->callAPISuccess('relationship', 'delete', $params);
    $this->relationshipTypeDelete($this->_relTypeID);
  }

  /**
   * Check with valid data with relationshipTypes.
   */
  public function testGetRelationshipWithRelTypes() {
    $relParams = [
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2011-01-01',
      'end_date' => '2013-01-01',
      'is_active' => 1,
    ];

    $relationship = $this->callAPISuccess('relationship', 'create', $relParams);

    $contact_a = [
      'contact_id' => $this->_cId_a,
    ];
    $this->callAPISuccess('relationship', 'get', $contact_a);

    $params = [
      'id' => $relationship['id'],
    ];
    $this->callAPISuccess('relationship', 'delete', $params);
    $this->relationshipTypeDelete($this->_relTypeID);
  }

  /**
   * Checks that passing in 'contact_id' + a relationship type
   * will filter by relationship type (relationships go in both directions)
   * as relationship api does a reciprocal check if contact_id provided
   *
   * We should get 1 result without or with correct relationship type id & 0 with
   * an incorrect one
   */
  public function testGetRelationshipByTypeReciprocal() {
    $created = $this->callAPISuccess($this->entity, 'create', $this->_params);
    $result = $this->callAPISuccess($this->entity, 'get', [
      'contact_id' => $this->_cId_a,
      'relationship_type_id' => $this->_relTypeID,
    ]);
    $this->assertEquals(1, $result['count']);
    $result = $this->callAPISuccess($this->entity, 'get', [
      'contact_id' => $this->_cId_a,
      'relationship_type_id' => $this->_relTypeID + 1,
    ]);
    $this->assertEquals(0, $result['count']);
    $this->callAPISuccess($this->entity, 'delete', ['id' => $created['id']]);
  }

  /**
   * Checks that passing in 'contact_id_b' + a relationship type
   * will filter by relationship type for contact b
   *
   * We should get 1 result without or with correct relationship type id & 0 with
   * an incorrect one
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetRelationshipByTypeDAO($version) {
    $this->_apiversion = $version;
    $this->_ids['relationship'] = $this->callAPISuccess($this->entity, 'create', ['format.only_id' => TRUE] +
      $this->_params);
    $this->callAPISuccess($this->entity, 'getcount', [
      'contact_id_a' => $this->_cId_a,
    ], 1);
    $result = $this->callAPISuccess($this->entity, 'get', [
      'contact_id_a' => $this->_cId_a,
      'relationship_type_id' => $this->_relTypeID,
    ]);
    $this->assertEquals(1, $result['count']);
    $result = $this->callAPISuccess($this->entity, 'get', [
      'contact_id_a' => $this->_cId_a,
      'relationship_type_id' => $this->_relTypeID + 1,
    ]);
    $this->assertEquals(0, $result['count']);
  }

  /**
   * Checks that passing in 'contact_id_b' + a relationship type
   * will filter by relationship type for contact b
   *
   * We should get 1 result without or with correct relationship type id & 0 with
   * an incorrect one
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetRelationshipByTypeArrayDAO($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess($this->entity, 'create', $this->_params);
    $org3 = $this->organizationCreate();
    // lets just assume built in ones aren't being messed with!
    $relType2 = 5;
    // lets just assume built in ones aren't being messed with!
    $relType3 = 6;

    // Relationship 2.
    $this->callAPISuccess($this->entity, 'create',
      array_merge($this->_params, [
        'relationship_type_id' => $relType2,
        'contact_id_b' => $this->_cId_b2,
      ])
    );

    // Relationship 3.
    $this->callAPISuccess($this->entity, 'create',
      array_merge($this->_params, [
        'relationship_type_id' => $relType3,
        'contact_id_b' => $org3,
      ])
    );

    $result = $this->callAPISuccess($this->entity, 'get', [
      'contact_id_a' => $this->_cId_a,
      'relationship_type_id' => ['IN' => [$this->_relTypeID, $relType3]],
    ]);

    $this->assertEquals(2, $result['count']);
    foreach ($result['values'] as $key => $value) {
      $this->assertTrue(in_array($value['relationship_type_id'], [$this->_relTypeID, $relType3]));
    }
  }

  /**
   * Checks that passing in 'contact_id_b' + a relationship type
   * will filter by relationship type for contact b
   *
   * We should get 1 result without or with correct relationship type id & 0 with
   * an incorrect one
   */
  public function testGetRelationshipByTypeArrayReciprocal() {
    $this->callAPISuccess($this->entity, 'create', $this->_params);
    $org3 = $this->organizationCreate();
    // lets just assume built in ones aren't being messed with!
    $relType2 = 5;
    $relType3 = 6;

    // Relationship 2.
    $this->callAPISuccess($this->entity, 'create',
      array_merge($this->_params, [
        'relationship_type_id' => $relType2,
        'contact_id_b' => $this->_cId_b2,
      ])
    );

    // Relationship 3.
    $this->callAPISuccess($this->entity, 'create',
      array_merge($this->_params, [
        'relationship_type_id' => $relType3,
        'contact_id_b' => $org3,
      ])
    );

    $result = $this->callAPISuccess($this->entity, 'get', [
      'contact_id' => $this->_cId_a,
      'relationship_type_id' => ['IN' => [$this->_relTypeID, $relType3]],
    ]);

    $this->assertEquals(2, $result['count']);
    foreach ($result['values'] as $key => $value) {
      $this->assertTrue(in_array($value['relationship_type_id'], [$this->_relTypeID, $relType3]));
    }
  }

  /**
   * Test relationship get by membership type.
   *
   * Checks that passing in 'contact_id_b' + a relationship type
   * will filter by relationship type for contact b
   *
   * We should get 1 result without or with correct relationship type id & 0 with
   * an incorrect one
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetRelationshipByMembershipTypeDAO($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess($this->entity, 'create', $this->_params);
    $org3 = $this->organizationCreate();

    // lets just assume built in ones aren't being messed with!
    $relType2 = 5;
    // lets just assume built in ones aren't being messed with!
    $relType3 = 6;
    $relType1 = 1;
    $memberType = $this->membershipTypeCreate([
      'relationship_type_id' => CRM_Core_DAO::VALUE_SEPARATOR . $relType1 . CRM_Core_DAO::VALUE_SEPARATOR . $relType3 . CRM_Core_DAO::VALUE_SEPARATOR,
      'relationship_direction' => CRM_Core_DAO::VALUE_SEPARATOR . 'a_b' . CRM_Core_DAO::VALUE_SEPARATOR . 'b_a' . CRM_Core_DAO::VALUE_SEPARATOR,
    ]);

    // Relationship 2.
    $this->callAPISuccess($this->entity, 'create',
      array_merge($this->_params, [
        'relationship_type_id' => $relType2,
        'contact_id_b' => $this->_cId_b2,
      ])
    );

    // Relationship 3.
    $this->callAPISuccess($this->entity, 'create',
      array_merge($this->_params, [
        'relationship_type_id' => $relType3,
        'contact_id_b' => $org3,
      ])
    );

    // Relationship 4 with reversal.
    $this->callAPISuccess($this->entity, 'create',
      array_merge($this->_params, [
        'relationship_type_id' => $relType1,
        'contact_id_a' => $this->_cId_a,
        'contact_id_b' => $this->_cId_a_2,
      ])
    );

    $result = $this->callAPISuccess($this->entity, 'get', [
      'contact_id_a' => $this->_cId_a,
      'membership_type_id' => $memberType,
    ]);
    // although our contact has more than one relationship we have passed them in as contact_id_a & can't get reciprocal
    $this->assertEquals(1, $result['count']);
    foreach ($result['values'] as $key => $value) {
      $this->assertTrue(in_array($value['relationship_type_id'], [$relType1]));
    }
  }

  /**
   * Checks that passing in 'contact_id_b' + a relationship type
   * will filter by relationship type for contact b
   *
   * We should get 1 result without or with correct relationship type id & 0 with
   * an incorrect one
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetRelationshipByMembershipTypeReciprocal($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess($this->entity, 'create', $this->_params);
    $org3 = $this->organizationCreate();

    // Let's just assume built in ones aren't being messed with!
    $relType2 = 5;
    $relType3 = 6;
    $relType1 = 1;
    $memberType = $this->membershipTypeCreate([
      'relationship_type_id' => CRM_Core_DAO::VALUE_SEPARATOR . $relType1 . CRM_Core_DAO::VALUE_SEPARATOR . $relType3 . CRM_Core_DAO::VALUE_SEPARATOR,
      'relationship_direction' => CRM_Core_DAO::VALUE_SEPARATOR . 'a_b' . CRM_Core_DAO::VALUE_SEPARATOR . 'b_a' . CRM_Core_DAO::VALUE_SEPARATOR,
    ]);

    // Relationship 2.
    $this->callAPISuccess($this->entity, 'create',
      array_merge($this->_params, [
        'relationship_type_id' => $relType2,
        'contact_id_b' => $this->_cId_b2,
      ])
    );

    // Relationship 4.
    $this->callAPISuccess($this->entity, 'create',
      array_merge($this->_params, [
        'relationship_type_id' => $relType3,
        'contact_id_b' => $org3,
      ])
    );

    // Relationship 4 with reversal.
    $this->callAPISuccess($this->entity, 'create',
      array_merge($this->_params, [
        'relationship_type_id' => $relType1,
        'contact_id_a' => $this->_cId_a,
        'contact_id_b' => $this->_cId_a_2,
      ])
    );

    $result = $this->callAPISuccess($this->entity, 'get', [
      'contact_id' => $this->_cId_a,
      'membership_type_id' => $memberType,
    ]);
    // Although our contact has more than one relationship we have passed them in as contact_id_a & can't get reciprocal
    $this->assertEquals(2, $result['count']);

    foreach ($result['values'] as $key => $value) {
      $this->assertTrue(in_array($value['relationship_type_id'], [$relType1, $relType3]));
    }
  }

  /**
   * Check for e-notices on enable & disable as reported in CRM-14350
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testSetActive($version) {
    $this->_apiversion = $version;
    $relationship = $this->callAPISuccess($this->entity, 'create', $this->_params);
    $this->callAPISuccess($this->entity, 'create', ['id' => $relationship['id'], 'is_active' => 0]);
    $this->callAPISuccess($this->entity, 'create', ['id' => $relationship['id'], 'is_active' => 1]);
  }

  /**
   * Test creating related memberships.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateRelatedMembership($version) {
    $this->_apiversion = $version;
    $relatedMembershipType = $this->callAPISuccess('MembershipType', 'create', [
      'name' => 'Membership with Related',
      'member_of_contact_id' => 1,
      'financial_type_id' => 1,
      'minimum_fee' => 0.00,
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'relationship_type_id' => $this->_relTypeID,
      'relationship_direction' => 'b_a',
      'visibility' => 'Public',
      'auto_renew' => 0,
      'is_active' => 1,
      'domain_id' => CRM_Core_Config::domainID(),
    ]);
    $originalMembership = $this->callAPISuccess('Membership', 'create', [
      'membership_type_id' => $relatedMembershipType['id'],
      'contact_id' => $this->_cId_b,
    ]);
    $this->callAPISuccess('Relationship', 'create', [
      'relationship_type_id' => $this->_relTypeID,
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
    ]);
    $contactAMembership = $this->callAPISuccessGetSingle('membership', ['contact_id' => $this->_cId_a]);
    $this->assertEquals($originalMembership['id'], $contactAMembership['owner_membership_id']);

    // Adding a relationship with a future start date should NOT create a membership
    $this->callAPISuccess('Relationship', 'create', [
      'relationship_type_id' => $this->_relTypeID,
      'contact_id_a' => $this->_cId_a_2,
      'contact_id_b' => $this->_cId_b,
      'start_date' => 'now + 1 week',
    ]);
    $this->callAPISuccessGetCount('membership', ['contact_id' => $this->_cId_a_2], 0);

    // Deleting the organization should cause the related membership to be deleted.
    $this->callAPISuccess('contact', 'delete', ['id' => $this->_cId_b]);
    $this->callAPISuccessGetCount('membership', ['contact_id' => $this->_cId_a], 0);
  }

}
