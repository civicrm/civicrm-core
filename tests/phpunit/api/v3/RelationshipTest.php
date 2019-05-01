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
  protected $_apiversion = 3;
  protected $_cId_a;
  /**
   * Second individual.
   * @var integer
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
  protected $_ids = array();
  protected $_customGroupId = NULL;
  protected $_customFieldId = NULL;
  protected $_params;

  protected $_entity;

  /**
   * Set up function.
   */
  public function setUp() {
    parent::setUp();
    $this->_cId_a = $this->individualCreate();
    $this->_cId_a_2 = $this->individualCreate(array(
      'last_name' => 'c2',
      'email' => 'c@w.com',
      'contact_type' => 'Individual',
    ));
    $this->_cId_b = $this->organizationCreate();
    $this->_cId_b2 = $this->organizationCreate(array('organization_name' => ' Org 2'));
    $this->_entity = 'relationship';
    //Create a relationship type.
    $relTypeParams = array(
      'name_a_b' => 'Relation 1 for delete',
      'name_b_a' => 'Relation 2 for delete',
      'description' => 'Testing relationship type',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
    );

    $this->_relTypeID = $this->relationshipTypeCreate($relTypeParams);
    $this->_params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
    );

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
    $this->quickCleanup(array('civicrm_relationship'), TRUE);
    $this->relationshipTypeDelete($this->_relTypeID);
  }

  /**
   * Check with empty array.
   */
  public function testRelationshipCreateEmpty() {
    $this->callAPIFailure('relationship', 'create', array());
  }

  /**
   * Test Current Employer is correctly set.
   */
  public function testCurrentEmployerRelationship() {
    $employerRelationshipID = $this->callAPISuccessGetValue('RelationshipType', array(
      'return' => "id",
      'name_b_a' => "Employer Of",
    ));
    $employerRelationship = $this->callAPISuccess('Relationship', 'create', array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $employerRelationshipID,
    ));
    $params = array($this->_cId_a => $this->_cId_b);
    CRM_Contact_BAO_Contact_Utils::setCurrentEmployer($params);

    //Check if current employer is correctly set.
    $employer = $this->callAPISuccessGetValue('Contact', array(
      'return' => "current_employer",
      'id' => $this->_cId_a,
    ));
    $organisation = $this->callAPISuccessGetValue('Contact', array(
      'return' => "sort_name",
      'id' => $this->_cId_b,
    ));
    $this->assertEquals($employer, $organisation);

    //Update relationship type
    $update = $this->callAPISuccess('Relationship', 'create', array(
      'id' => $employerRelationship['id'],
      'relationship_type_id' => $this->_relTypeID,
    ));
    $employeeContact = $this->callAPISuccessGetSingle('Contact', array(
      'return' => array("current_employer"),
      'id' => $this->_cId_a,
    ));
    //current employer should be removed.
    $this->assertEmpty($employeeContact['current_employer']);
  }

  /**
   * Check if required fields are not passed.
   */
  public function testRelationshipCreateWithoutRequired() {
    $params = array(
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'end_date' => array('d' => '10', 'M' => '1', 'Y' => '2009'),
      'is_active' => 1,
    );

    $this->callAPIFailure('relationship', 'create', $params);
  }

  /**
   * Check with incorrect required fields.
   */
  public function testRelationshipCreateWithIncorrectData() {

    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => 'Breaking Relationship',
    );

    $this->callAPIFailure('relationship', 'create', $params);

    //contact id is not an integer
    $params = array(
      'contact_id_a' => 'invalid',
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'is_active' => 1,
    );
    $this->callAPIFailure('relationship', 'create', $params);

    // Contact id does not exist.
    $params['contact_id_a'] = 999;
    $this->callAPIFailure('relationship', 'create', $params);

    //invalid date
    $params['contact_id_a'] = $this->_cId_a;
    $params['start_date'] = array('d' => '1', 'M' => '1');
    $this->callAPIFailure('relationship', 'create', $params);
  }

  /**
   * Check relationship creation with invalid Relationship.
   */
  public function testRelationshipCreateInvalidRelationship() {
    // Both have the contact type Individual.
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_a,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-01-10',
      'is_active' => 1,
    );

    $this->callAPIFailure('relationship', 'create', $params);

    // both the contact of type Organization
    $params = array(
      'contact_id_a' => $this->_cId_b,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-01-10',
      'is_active' => 1,
    );

    $this->callAPIFailure('relationship', 'create', $params);
  }

  /**
   * Check relationship already exists.
   */
  public function testRelationshipCreateAlreadyExists() {
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'end_date' => NULL,
      'is_active' => 1,
    );
    $relationship = $this->callAPISuccess('relationship', 'create', $params);

    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
    );
    $this->callAPIFailure('relationship', 'create', $params, 'Duplicate Relationship');

    $params['id'] = $relationship['id'];
    $this->callAPISuccess('relationship', 'delete', $params);
  }

  /**
   * Check relationship already exists.
   */
  public function testRelationshipCreateUpdateAlreadyExists() {
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'end_date' => NULL,
      'is_active' => 1,

    );
    $relationship = $this->callAPISuccess('relationship', 'create', $params);
    $params = array(
      'id' => $relationship['id'],
      'is_active' => 0,
      'debug' => 1,
    );
    $this->callAPISuccess('relationship', 'create', $params);
    $this->callAPISuccess('relationship', 'get', $params);
    $params['id'] = $relationship['id'];
    $this->callAPISuccess('relationship', 'delete', $params);
  }

  /**
   * Check update doesn't reset stuff badly - CRM-11789.
   */
  public function testRelationshipCreateUpdateDoesNotMangle() {
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
      'is_permission_a_b' => 1,
      'description' => 'my desc',
    );
    $relationship = $this->callAPISuccess('relationship', 'create', $params);

    $updateParams = array(
      'id' => $relationship['id'],
      'relationship_type_id' => $this->_relTypeID,
    );
    $this->callAPISuccess('relationship', 'create', $updateParams);

    //make sure the orig params didn't get changed
    $this->getAndCheck($params, $relationship['id'], 'relationship');

  }

  /**
   * Check relationship creation.
   */
  public function testRelationshipCreate() {
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2010-10-30',
      'end_date' => '2010-12-30',
      'is_active' => 1,
      'note' => 'note',
    );

    $result = $this->callAPIAndDocument('relationship', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['id']);
    $relationParams = array(
      'id' => $result['id'],
    );

    // assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Contact_DAO_Relationship', $result['id'], $relationParams);
    $result = $this->callAPISuccess('relationship', 'get', array('id' => $result['id']));
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
   */
  public function testRelationshipUpdate() {
    $result = $this->callAPISuccess('relationship', 'create', $this->_params);
    $relID = $result['id'];
    $result = $this->callAPISuccess('relationship', 'create', array('id' => $relID, 'description' => 'blah'));
    $this->assertEquals($relID, $result['id']);

    $this->assertEquals('blah', $result['values'][$result['id']]['description']);

    $result = $this->callAPISuccess('relationship', 'create', array('id' => $relID, 'is_permission_b_a' => 1));
    $this->assertEquals(1, $result['values'][$result['id']]['is_permission_b_a']);
    $result = $this->callAPISuccess('relationship', 'create', array('id' => $result['id'], 'is_active' => 0));
    $result = $this->callAPISuccess('relationship', 'get', array('id' => $result['id']));
    $this->assertEquals(0, $result['values'][$result['id']]['is_active']);
    $this->assertEquals('blah', $result['values'][$result['id']]['description']);
    $this->assertEquals(1, $result['values'][$result['id']]['is_permission_b_a']);
  }

  /**
   * Check relationship creation.
   */
  public function testRelationshipCreateEmptyEndDate() {
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2010-10-30',
      'end_date' => '',
      'is_active' => 1,
      'note' => 'note',
    );

    $result = $this->callAPISuccess('relationship', 'create', $params);
    $this->assertNotNull($result['id']);
    $relationParams = array(
      'id' => $result['id'],
    );

    // assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Contact_DAO_Relationship', $result['id'], $relationParams);
    $result = $this->callAPISuccess('relationship', 'get', array('id' => $result['id']));
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
   */
  public function testRelationshipCreateEditWithCustomData() {
    $this->createCustomGroup();
    $this->_ids = $this->createCustomField();
    //few custom Values for comparing
    $custom_params = array(
      "custom_{$this->_ids[0]}" => 'Hello! this is custom data for relationship',
      "custom_{$this->_ids[1]}" => 'Y',
      "custom_{$this->_ids[2]}" => '2009-07-11 00:00:00',
      "custom_{$this->_ids[3]}" => 'http://example.com',
    );

    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
    );
    $params = array_merge($params, $custom_params);
    $result = $this->callAPISuccess('relationship', 'create', $params);

    $relationParams = array(
      'id' => $result['id'],
    );
    $this->assertDBState('CRM_Contact_DAO_Relationship', $result['id'], $relationParams);

    //Test Edit of custom field from the form.
    $getParams = array('id' => $result['id']);
    $updateParams = array_merge($getParams, array(
      "custom_{$this->_ids[0]}" => 'Edited Text Value',
      'relationship_type_id' => $this->_relTypeID . '_b_a',
      'related_contact_id' => $this->_cId_a,
    ));
    $reln = new CRM_Contact_Form_Relationship();
    $reln->_action = CRM_Core_Action::UPDATE;
    $reln->_relationshipId = $result['id'];
    $reln->submit($updateParams);

    $check = $this->callAPISuccess('relationship', 'get', $getParams);
    $this->assertEquals("Edited Text Value", $check['values'][$check['id']]["custom_{$this->_ids[0]}"]);

    $params['id'] = $result['id'];
    $this->callAPISuccess('relationship', 'delete', $params);
    $this->relationshipTypeDelete($this->_relTypeID);
  }

  /**
   * Check with complete array + custom field
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  public function testGetWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = $this->callAPISuccess($this->_entity, 'create', $params);
    $this->assertEquals($result['id'], $result['values'][$result['id']]['id']);

    $getParams = array('id' => $result['id']);
    $check = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * @return mixed
   */
  public function createCustomGroup() {
    $params = array(
      'title' => 'Custom Group',
      'extends' => array('Relationship'),
      'weight' => 5,
      'style' => 'Inline',
      'is_active' => 1,
      'max_multiple' => 0,
    );
    $customGroup = $this->callAPISuccess('custom_group', 'create', $params);
    $this->_customGroupId = $customGroup['id'];
    return $customGroup['id'];
  }

  /**
   * @return array
   */
  public function createCustomField() {
    $ids = array();
    $params = array(
      'custom_group_id' => $this->_customGroupId,
      'label' => 'Enter text about relationship',
      'html_type' => 'Text',
      'data_type' => 'String',
      'default_value' => 'xyz',
      'weight' => 1,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    );

    $customField = $this->callAPISuccess('CustomField', 'create', $params);
    $ids[] = $customField['id'];

    $optionValue[] = array(
      'label' => 'Red',
      'value' => 'R',
      'weight' => 1,
      'is_active' => 1,
    );
    $optionValue[] = array(
      'label' => 'Yellow',
      'value' => 'Y',
      'weight' => 2,
      'is_active' => 1,
    );
    $optionValue[] = array(
      'label' => 'Green',
      'value' => 'G',
      'weight' => 3,
      'is_active' => 1,
    );

    $params = array(
      'label' => 'Pick Color',
      'html_type' => 'Select',
      'data_type' => 'String',
      'weight' => 2,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'option_values' => $optionValue,
      'custom_group_id' => $this->_customGroupId,
    );

    $customField = $this->callAPISuccess('custom_field', 'create', $params);
    $ids[] = $customField['id'];

    $params = array(
      'custom_group_id' => $this->_customGroupId,
      'name' => 'test_date',
      'label' => 'test_date',
      'html_type' => 'Select Date',
      'data_type' => 'Date',
      'default_value' => '20090711',
      'weight' => 3,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    );

    $customField = $this->callAPISuccess('custom_field', 'create', $params);

    $ids[] = $customField['id'];
    $params = array(
      'custom_group_id' => $this->_customGroupId,
      'name' => 'test_link',
      'label' => 'test_link',
      'html_type' => 'Link',
      'data_type' => 'Link',
      'default_value' => 'http://civicrm.org',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    );

    $customField = $this->callAPISuccess('custom_field', 'create', $params);
    $ids[] = $customField['id'];
    return $ids;
  }

  /**
   * Check with empty array.
   */
  public function testRelationshipDeleteEmpty() {
    $this->callAPIFailure('relationship', 'delete', array(), 'Mandatory key(s) missing from params array: id');
  }

  /**
   * Check if required fields are not passed.
   */
  public function testRelationshipDeleteWithoutRequired() {
    $params = array(
      'start_date' => '2008-12-20',
      'end_date' => '2009-12-20',
      'is_active' => 1,
    );

    $this->callAPIFailure('relationship', 'delete', $params, 'Mandatory key(s) missing from params array: id');
  }

  /**
   * Check with incorrect required fields.
   */
  public function testRelationshipDeleteWithIncorrectData() {
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => 'Breaking Relationship',
    );

    $this->callAPIFailure('relationship', 'delete', $params, 'Mandatory key(s) missing from params array: id');

    $params['id'] = "Invalid";
    $this->callAPIFailure('relationship', 'delete', $params, 'id is not a valid integer');
  }

  /**
   * Check relationship creation.
   */
  public function testRelationshipDelete() {
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
    );

    $result = $this->callAPISuccess('relationship', 'create', $params);
    $params = array('id' => $result['id']);
    $this->callAPIAndDocument('relationship', 'delete', $params, __FUNCTION__, __FILE__);
    $this->relationshipTypeDelete($this->_relTypeID);
  }

  ///////////////// civicrm_relationship_update methods

  /**
   * Check with empty array.
   */
  public function testRelationshipUpdateEmpty() {
    $this->callAPIFailure('relationship', 'create', array(),
      'Mandatory key(s) missing from params array: contact_id_a, contact_id_b, relationship_type_id');
  }

  /**
   * Check if required fields are not passed.
   */

  /**
   * Check relationship update.
   */
  public function testRelationshipCreateDuplicate() {
    $relParams = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '20081214',
      'end_date' => '20091214',
      'is_active' => 1,
    );

    $result = $this->callAPISuccess('relationship', 'create', $relParams);

    $this->assertNotNull($result['id']);

    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '20081214',
      'end_date' => '20091214',
      'is_active' => 0,
    );

    $this->callAPIFailure('relationship', 'create', $params, 'Duplicate Relationship');

    $this->callAPISuccess('relationship', 'delete', array('id' => $result['id']));
    $this->relationshipTypeDelete($this->_relTypeID);
  }

  /**
   * CRM-13725 - Two relationships of same type with same start and end date
   * should be OK if the custom field values differ.
   */
  public function testRelationshipCreateDuplicateWithCustomFields() {
    $this->createCustomGroup();
    $this->_ids = $this->createCustomField();

    $custom_params_1 = array(
      "custom_{$this->_ids[0]}" => 'Hello! this is custom data for relationship',
      "custom_{$this->_ids[1]}" => 'Y',
      "custom_{$this->_ids[2]}" => '2009-07-11 00:00:00',
      "custom_{$this->_ids[3]}" => 'http://example.com',
    );

    $custom_params_2 = array(
      "custom_{$this->_ids[0]}" => 'Hello! this is other custom data',
      "custom_{$this->_ids[1]}" => 'Y',
      "custom_{$this->_ids[2]}" => '2009-07-11 00:00:00',
      "custom_{$this->_ids[3]}" => 'http://example.org',
    );

    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
    );

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
   */
  public function testRelationshipCreateDuplicateWithCustomFields2() {
    $this->createCustomGroup();
    $this->_ids = $this->createCustomField();

    $custom_params_2 = array(
      "custom_{$this->_ids[0]}" => 'Hello! this is other custom data',
      "custom_{$this->_ids[1]}" => 'Y',
      "custom_{$this->_ids[2]}" => '2009-07-11 00:00:00',
      "custom_{$this->_ids[3]}" => 'http://example.org',
    );

    $params_1 = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
    );

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
   */
  public function testRelationshipCreateDuplicateWithCustomFields3() {
    $this->createCustomGroup();
    $this->_ids = $this->createCustomField();

    $custom_params_1 = array(
      "custom_{$this->_ids[0]}" => 'Hello! this is other custom data',
      "custom_{$this->_ids[1]}" => 'Y',
      "custom_{$this->_ids[2]}" => '2009-07-11 00:00:00',
      "custom_{$this->_ids[3]}" => 'http://example.org',
    );

    $params_2 = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
    );

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
    $relParams = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2011-01-01',
      'end_date' => '2013-01-01',
      'is_active' => 1,
    );

    $this->callAPISuccess('relationship', 'create', $relParams);

    //get relationship
    $params = array(
      'contact_id' => $this->_cId_b,
    );
    $result = $this->callAPISuccess('relationship', 'get', $params);
    $this->assertEquals($result['count'], 1);
    $params = array(
      'contact_id_a' => $this->_cId_a,
    );
    $result = $this->callAPISuccess('relationship', 'get', $params);
    $this->assertEquals($result['count'], 1);
    // contact_id_a is wrong so should be no matches
    $params = array(
      'contact_id_a' => $this->_cId_b,
    );
    $result = $this->callAPISuccess('relationship', 'get', $params);
    $this->assertEquals($result['count'], 0);
  }

  /**
   * Chain Relationship.get and to Contact.get.
   */
  public function testRelationshipGetWithChainedCall() {
    // Create a relationship.
    $createResult = $this->callAPISuccess('relationship', 'create', $this->_params);
    $id = $createResult['id'];

    // Try to retrieve it using chaining.
    $params = array(
      'relationship_type_id' => $this->_relTypeID,
      'id' => $id,
      'api.Contact.get' => array(
        'id' => '$value.contact_id_b',
      ),
    );

    $result = $this->callAPISuccess('relationship', 'get', $params);

    $this->assertEquals(1, $result['count']);
    $relationship = CRM_Utils_Array::first($result['values']);
    $this->assertEquals(1, $relationship['api.Contact.get']['count']);
    $contact = CRM_Utils_Array::first($relationship['api.Contact.get']['values']);
    $this->assertEquals($this->_cId_b, $contact['id']);
  }

  /**
   * Chain Contact.get to Relationship.get and again to Contact.get.
   */
  public function testRelationshipGetInChainedCall() {
    // Create a relationship.
    $this->callAPISuccess('relationship', 'create', $this->_params);

    // Try to retrieve it using chaining.
    $params = array(
      'id' => $this->_cId_a,
      'api.Relationship.get' => array(
        'relationship_type_id' => $this->_relTypeID,
        'contact_id_a' => '$value.id',
        'api.Contact.get' => array(
          'id' => '$value.contact_id_b',
        ),
      ),
    );

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
   */
  public function testRelationshipsGetGeneric() {
    $relParams = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2011-01-01',
      'end_date' => '2013-01-01',
      'is_active' => 1,
    );

    $this->callAPISuccess('relationship', 'create', $relParams);

    //get relationship
    $params = array(
      'contact_id_b' => $this->_cId_b,
    );
    $this->callAPISuccess('relationship', 'get', $params);
  }

  /**
   * Test retrieving only current relationships.
   */
  public function testGetIsCurrent() {
    $rel2Params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b2,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 0,
    );
    $this->callAPISuccess('relationship', 'create', $rel2Params);
    $rel1 = $this->callAPISuccess('relationship', 'create', $this->_params);

    $getParams = array(
      'filters' => array('is_current' => 1),
    );
    $description = "Demonstrates is_current filter.";
    $subfile = 'filterIsCurrent';
    //no relationship has been created
    $result = $this->callAPIAndDocument('relationship', 'get', $getParams, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 1);
    $this->AssertEquals($rel1['id'], $result['id']);

    // now try not started
    $rel2Params['is_active'] = 1;
    $rel2Params['start_date'] = 'tomorrow';
    $this->callAPISuccess('relationship', 'create', $rel2Params);
    $result = $this->callAPISuccess('relationship', 'get', $getParams);
    $this->assertEquals($result['count'], 1);
    $this->AssertEquals($rel1['id'], $result['id']);

    // now try finished
    $rel2Params['is_active'] = 1;
    $rel2Params['start_date'] = 'last week';
    $rel2Params['end_date'] = 'yesterday';
    $this->callAPISuccess('relationship', 'create', $rel2Params);
  }

  /**
   * Test using various operators.
   */
  public function testGetTypeOperators() {
    $relTypeParams = array(
      'name_a_b' => 'Relation 3 for delete',
      'name_b_a' => 'Relation 6 for delete',
      'description' => 'Testing relationship type 2',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
    );
    $relationType2 = $this->relationshipTypeCreate($relTypeParams);
    $relTypeParams = array(
      'name_a_b' => 'Relation 8 for delete',
      'name_b_a' => 'Relation 9 for delete',
      'description' => 'Testing relationship type 7',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
    );
    $relationType3 = $this->relationshipTypeCreate($relTypeParams);

    $relTypeParams = array(
      'name_a_b' => 'Relation 6 for delete',
      'name_b_a' => 'Relation 88for delete',
      'description' => 'Testing relationship type 00',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
    );
    $relationType4 = $this->relationshipTypeCreate($relTypeParams);

    $rel1 = $this->callAPISuccess('relationship', 'create', $this->_params);
    $rel2 = $this->callAPISuccess('relationship', 'create', array_merge($this->_params,
      array('relationship_type_id' => $relationType2)));
    $rel3 = $this->callAPISuccess('relationship', 'create', array_merge($this->_params,
      array('relationship_type_id' => $relationType3)));
    $rel4 = $this->callAPISuccess('relationship', 'create', array_merge($this->_params,
      array('relationship_type_id' => $relationType4)));

    $getParams = array(
      'relationship_type_id' => array('IN' => array($relationType2, $relationType3)),
    );

    $description = "Demonstrates use of IN filter.";
    $subfile = 'INRelationshipType';

    $result = $this->callAPIAndDocument('relationship', 'get', $getParams, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 2);
    $this->AssertEquals(array($rel2['id'], $rel3['id']), array_keys($result['values']));

    $description = "Demonstrates use of NOT IN filter.";
    $subfile = 'NotInRelationshipType';
    $getParams = array(
      'relationship_type_id' => array('NOT IN' => array($relationType2, $relationType3)),
    );
    $result = $this->callAPIAndDocument('relationship', 'get', $getParams, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 2);
    $this->AssertEquals(array($rel1['id'], $rel4['id']), array_keys($result['values']));

    $description = "Demonstrates use of BETWEEN filter.";
    $subfile = 'BetweenRelationshipType';
    $getParams = array(
      'relationship_type_id' => array('BETWEEN' => array($relationType2, $relationType4)),
    );
    $result = $this->callAPIAndDocument('relationship', 'get', $getParams, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 3);
    $this->AssertEquals(array($rel2['id'], $rel3['id'], $rel4['id']), array_keys($result['values']));

    $description = "Demonstrates use of Not BETWEEN filter.";
    $subfile = 'NotBetweenRelationshipType';
    $getParams = array(
      'relationship_type_id' => array('NOT BETWEEN' => array($relationType2, $relationType4)),
    );
    $result = $this->callAPIAndDocument('relationship', 'get', $getParams, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 1);
    $this->AssertEquals(array($rel1['id']), array_keys($result['values']));

  }

  /**
   * Check with invalid relationshipType Id.
   */
  public function testRelationshipTypeAddInvalidId() {
    $relTypeParams = array(
      'id' => 'invalid',
      'name_a_b' => 'Relation 1 for delete',
      'name_b_a' => 'Relation 2 for delete',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
    );
    $this->callAPIFailure('relationship_type', 'create', $relTypeParams,
      'id is not a valid integer');
  }

  /**
   * Check with valid data with contact_b.
   */
  public function testGetRelationshipWithContactB() {
    $relParams = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2011-01-01',
      'end_date' => '2013-01-01',
      'is_active' => 1,
    );

    $relationship = $this->callAPISuccess('relationship', 'create', $relParams);

    $contacts = array(
      'contact_id' => $this->_cId_a,
    );

    $result = $this->callAPISuccess('relationship', 'get', $contacts);
    $this->assertGreaterThan(0, $result['count']);
    $params = array(
      'id' => $relationship['id'],
    );
    $this->callAPISuccess('relationship', 'delete', $params);
    $this->relationshipTypeDelete($this->_relTypeID);
  }

  /**
   * Check with valid data with relationshipTypes.
   */
  public function testGetRelationshipWithRelTypes() {
    $relParams = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2011-01-01',
      'end_date' => '2013-01-01',
      'is_active' => 1,
    );

    $relationship = $this->callAPISuccess('relationship', 'create', $relParams);

    $contact_a = array(
      'contact_id' => $this->_cId_a,
    );
    $this->callAPISuccess('relationship', 'get', $contact_a);

    $params = array(
      'id' => $relationship['id'],
    );
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
    $created = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'get', array(
      'contact_id' => $this->_cId_a,
      'relationship_type_id' => $this->_relTypeID,
    ));
    $this->assertEquals(1, $result['count']);
    $result = $this->callAPISuccess($this->_entity, 'get', array(
      'contact_id' => $this->_cId_a,
      'relationship_type_id' => $this->_relTypeID + 1,
    ));
    $this->assertEquals(0, $result['count']);
    $this->callAPISuccess($this->_entity, 'delete', array('id' => $created['id']));
  }

  /**
   * Checks that passing in 'contact_id_b' + a relationship type
   * will filter by relationship type for contact b
   *
   * We should get 1 result without or with correct relationship type id & 0 with
   * an incorrect one
   */
  public function testGetRelationshipByTypeDAO() {
    $this->_ids['relationship'] = $this->callAPISuccess($this->_entity, 'create', array('format.only_id' => TRUE) +
      $this->_params);
    $this->callAPISuccess($this->_entity, 'getcount', array(
      'contact_id_a' => $this->_cId_a,
    ), 1);
    $result = $this->callAPISuccess($this->_entity, 'get', array(
      'contact_id_a' => $this->_cId_a,
      'relationship_type_id' => $this->_relTypeID,
    ));
    $this->assertEquals(1, $result['count']);
    $result = $this->callAPISuccess($this->_entity, 'get', array(
      'contact_id_a' => $this->_cId_a,
      'relationship_type_id' => $this->_relTypeID + 1,
    ));
    $this->assertEquals(0, $result['count']);
  }

  /**
   * Checks that passing in 'contact_id_b' + a relationship type
   * will filter by relationship type for contact b
   *
   * We should get 1 result without or with correct relationship type id & 0 with
   * an incorrect one
   */
  public function testGetRelationshipByTypeArrayDAO() {
    $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $org3 = $this->organizationCreate();
    // lets just assume built in ones aren't being messed with!
    $relType2 = 5;
    // lets just assume built in ones aren't being messed with!
    $relType3 = 6;

    // Relationship 2.
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType2,
        'contact_id_b' => $this->_cId_b2,
      ))
    );

    // Relationship 3.
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType3,
        'contact_id_b' => $org3,
      ))
    );

    $result = $this->callAPISuccess($this->_entity, 'get', array(
      'contact_id_a' => $this->_cId_a,
      'relationship_type_id' => array('IN' => array($this->_relTypeID, $relType3)),
    ));

    $this->assertEquals(2, $result['count']);
    foreach ($result['values'] as $key => $value) {
      $this->assertTrue(in_array($value['relationship_type_id'], array($this->_relTypeID, $relType3)));
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
    $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $org3 = $this->organizationCreate();
    // lets just assume built in ones aren't being messed with!
    $relType2 = 5;
    $relType3 = 6;

    // Relationship 2.
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType2,
        'contact_id_b' => $this->_cId_b2,
      ))
    );

    // Relationship 3.
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType3,
        'contact_id_b' => $org3,
      ))
    );

    $result = $this->callAPISuccess($this->_entity, 'get', array(
      'contact_id' => $this->_cId_a,
      'relationship_type_id' => array('IN' => array($this->_relTypeID, $relType3)),
    ));

    $this->assertEquals(2, $result['count']);
    foreach ($result['values'] as $key => $value) {
      $this->assertTrue(in_array($value['relationship_type_id'], array($this->_relTypeID, $relType3)));
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
   */
  public function testGetRelationshipByMembershipTypeDAO() {
    $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $org3 = $this->organizationCreate();

    // lets just assume built in ones aren't being messed with!
    $relType2 = 5;
    // lets just assume built in ones aren't being messed with!
    $relType3 = 6;
    $relType1 = 1;
    $memberType = $this->membershipTypeCreate(array(
      'relationship_type_id' => CRM_Core_DAO::VALUE_SEPARATOR . $relType1 . CRM_Core_DAO::VALUE_SEPARATOR . $relType3 . CRM_Core_DAO::VALUE_SEPARATOR,
      'relationship_direction' => CRM_Core_DAO::VALUE_SEPARATOR . 'a_b' . CRM_Core_DAO::VALUE_SEPARATOR . 'b_a' . CRM_Core_DAO::VALUE_SEPARATOR,
    ));

    // Relationship 2.
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType2,
        'contact_id_b' => $this->_cId_b2,
      ))
    );

    // Relationship 3.
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType3,
        'contact_id_b' => $org3,
      ))
    );

    // Relationship 4 with reversal.
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType1,
        'contact_id_a' => $this->_cId_a,
        'contact_id_b' => $this->_cId_a_2,
      ))
    );

    $result = $this->callAPISuccess($this->_entity, 'get', array(
      'contact_id_a' => $this->_cId_a,
      'membership_type_id' => $memberType,
    ));
    // although our contact has more than one relationship we have passed them in as contact_id_a & can't get reciprocal
    $this->assertEquals(1, $result['count']);
    foreach ($result['values'] as $key => $value) {
      $this->assertTrue(in_array($value['relationship_type_id'], array($relType1)));
    }
  }

  /**
   * Checks that passing in 'contact_id_b' + a relationship type
   * will filter by relationship type for contact b
   *
   * We should get 1 result without or with correct relationship type id & 0 with
   * an incorrect one
   */
  public function testGetRelationshipByMembershipTypeReciprocal() {
    $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $org3 = $this->organizationCreate();

    // Let's just assume built in ones aren't being messed with!
    $relType2 = 5;
    $relType3 = 6;
    $relType1 = 1;
    $memberType = $this->membershipTypeCreate(array(
      'relationship_type_id' => CRM_Core_DAO::VALUE_SEPARATOR . $relType1 . CRM_Core_DAO::VALUE_SEPARATOR . $relType3 . CRM_Core_DAO::VALUE_SEPARATOR,
      'relationship_direction' => CRM_Core_DAO::VALUE_SEPARATOR . 'a_b' . CRM_Core_DAO::VALUE_SEPARATOR . 'b_a' . CRM_Core_DAO::VALUE_SEPARATOR,
    ));

    // Relationship 2.
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType2,
        'contact_id_b' => $this->_cId_b2,
      ))
    );

    // Relationship 4.
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType3,
        'contact_id_b' => $org3,
      ))
    );

    // Relationship 4 with reversal.
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType1,
        'contact_id_a' => $this->_cId_a,
        'contact_id_b' => $this->_cId_a_2,
      ))
    );

    $result = $this->callAPISuccess($this->_entity, 'get', array(
      'contact_id' => $this->_cId_a,
      'membership_type_id' => $memberType,
    ));
    // Although our contact has more than one relationship we have passed them in as contact_id_a & can't get reciprocal
    $this->assertEquals(2, $result['count']);

    foreach ($result['values'] as $key => $value) {
      $this->assertTrue(in_array($value['relationship_type_id'], array($relType1, $relType3)));
    }
  }

  /**
   * Check for e-notices on enable & disable as reported in CRM-14350
   */
  public function testSetActive() {
    $relationship = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $this->callAPISuccess($this->_entity, 'create', array('id' => $relationship['id'], 'is_active' => 0));
    $this->callAPISuccess($this->_entity, 'create', array('id' => $relationship['id'], 'is_active' => 1));
  }

  /**
   * Test creating related memberships.
   */
  public function testCreateRelatedMembership() {
    $relatedMembershipType = $this->callAPISuccess('MembershipType', 'create', array(
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
    ));
    $originalMembership = $this->callAPISuccess('Membership', 'create', array(
      'membership_type_id' => $relatedMembershipType['id'],
      'contact_id' => $this->_cId_b,
    ));
    $this->callAPISuccess('Relationship', 'create', array(
      'relationship_type_id' => $this->_relTypeID,
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
    ));
    $contactAMembership = $this->callAPISuccessGetSingle('membership', array('contact_id' => $this->_cId_a));
    $this->assertEquals($originalMembership['id'], $contactAMembership['owner_membership_id']);

    // Adding a relationship with a future start date should NOT create a membership
    $this->callAPISuccess('Relationship', 'create', array(
      'relationship_type_id' => $this->_relTypeID,
      'contact_id_a' => $this->_cId_a_2,
      'contact_id_b' => $this->_cId_b,
      'start_date' => 'now + 1 week',
    ));
    $this->callAPISuccessGetCount('membership', array('contact_id' => $this->_cId_a_2), 0);

    // Deleting the organization should cause the related membership to be deleted.
    $this->callAPISuccess('contact', 'delete', array('id' => $this->_cId_b));
    $this->callAPISuccessGetCount('membership', array('contact_id' => $this->_cId_a), 0);
  }

}
