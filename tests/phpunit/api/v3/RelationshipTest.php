<?php
/**
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class contains api test cases for "civicrm_relationship"
 *
 */
class api_v3_RelationshipTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_cId_a;
  /**
   * second individual
   * @var integer
   */
  protected $_cId_a_2;
  protected $_cId_b;
  protected $_cId_b2;// second org
  protected $_relTypeID;
  protected $_ids = array();
  protected $_customGroupId = NULL;
  protected $_customFieldId = NULL;
  protected $_params;

  protected $_entity;
  function get_info() {
    return array(
      'name' => 'Relationship Create',
      'description' => 'Test all Relationship Create API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();
    $this->_cId_a = $this->individualCreate();
    $this->_cId_a_2 = $this->individualCreate(array('last_name' => 'c2', 'email' => 'c@w.com', 'contact_type' => 'Individual'));
    $this->_cId_b = $this->organizationCreate();
    $this->_cId_b2 = $this->organizationCreate(array('organization_name' => ' Org 2'));
    $this->_entity = 'relationship';
    //Create a relationship type
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

  function tearDown() {
    $this->contactDelete($this->_cId_a);
    $this->contactDelete($this->_cId_a_2);
    $this->contactDelete($this->_cId_b);
    $this->contactDelete($this->_cId_b2);
    $this->quickCleanup(array('civicrm_relationship'), TRUE);
    $this->relationshipTypeDelete($this->_relTypeID);
  }

  ///////////////// civicrm_relationship_create methods

  /**
   * check with empty array
   */
  function testRelationshipCreateEmpty() {
    $this->callAPIFailure('relationship', 'create', array());
  }

  /**
   * check if required fields are not passed
   */
  function testRelationshipCreateWithoutRequired() {
    $params = array(
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'end_date' => array('d' => '10', 'M' => '1', 'Y' => '2009'),
      'is_active' => 1,
    );

    $this->callAPIFailure('relationship', 'create', $params);
  }

  /**
   * check with incorrect required fields
   */
  function testRelationshipCreateWithIncorrectData() {

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

    //contact id does not exists
    $params['contact_id_a'] = 999;
    $this->callAPIFailure('relationship', 'create', $params);

    //invalid date
    $params['contact_id_a'] = $this->_cId_a;
    $params['start_date'] = array('d' => '1', 'M' => '1');
    $this->callAPIFailure('relationship', 'create', $params);
  }

  /**
   * check relationship creation with invalid Relationship
   */
  function testRelationshipCreatInvalidRelationship() {
    // both the contact of type Individual
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
   * check relationship already exists
   */
  function testRelationshipCreateAlreadyExists() {
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20', 'end_date' => NULL,
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
    $result = $this->callAPIFailure('relationship', 'create', $params, 'Relationship already exists');

    $params['id'] = $relationship['id'];
    $result = $this->callAPISuccess('relationship', 'delete', $params);
  }

  /**
   * check relationship already exists
   */
  function testRelationshipCreateUpdateAlreadyExists() {
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
    $result = $this->callAPISuccess('relationship', 'create', $params);
    $result = $this->callAPISuccess('relationship', 'get', $params);
    $params['id'] = $relationship['id'];
    $result = $this->callAPISuccess('relationship', 'delete', $params);
  }

  /**
   * checkupdate doesn't reset stuff badly - CRM-11789
   */
  function testRelationshipCreateUpdateDoesntMangle() {
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

    $updateparams = array(
      'id' => $relationship['id'],
      'relationship_type_id' => $this->_relTypeID,
    );
    $result = $this->callAPISuccess('relationship', 'create', $updateparams);

    //make sure the orig params didn't get changed
    $this->getAndCheck($params, $relationship['id'], 'relationship');

  }



  /**
   * check relationship creation
   */
  function testRelationshipCreate() {
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
   * ensure disabling works
   */
  function testRelationshipUpdate() {
    $result = $this->callAPISuccess('relationship', 'create', $this->_params);
    $relID = $result['id'];
    $result = $this->callAPISuccess('relationship', 'create', array('id' => $relID, 'description' => 'blah'));
    $this->assertEquals($relID, $result['id']);
    $this->assertEquals('blah', $result['values'][$result['id']]['description']);
    $result = $this->callAPISuccess('relationship', 'create', array('id' => $relID, 'is_permission_b_a' => 1));
    $this->assertEquals(1, $result['values'][$result['id']]['is_permission_b_a']);
    $result = $this->callAPISuccess('relationship', 'create', array('id' => $result['id'], 'is_active' => 0));
    $this->assertEquals(0, $result['values'][$result['id']]['is_active']);
    $this->assertEquals('blah', $result['values'][$result['id']]['description']);
    $this->assertEquals(1, $result['values'][$result['id']]['is_permission_b_a']);
  }
  /**
   * check relationship creation
   */
  function testRelationshipCreateEmptyEndDate() {
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
      if($key == 'end_date'){
        $this->assertTrue(empty($values[$key]));
        continue;
      }
      $this->assertEquals($value, $values[$key], $key . " doesn't match " . print_r($values, TRUE) . 'in line' . __LINE__);
    }
    $params['id'] = $result['id'];
    $this->callAPISuccess('relationship', 'delete', $params);
  }

  /**
   * check relationship creation with custom data
   */
  function testRelationshipCreateWithCustomData() {
    $customGroup = $this->createCustomGroup();
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
    // assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Contact_DAO_Relationship', $result['id'], $relationParams);

    $params['id'] = $result['id'];
    $result = $this->callAPISuccess('relationship', 'delete', $params);
    $this->relationshipTypeDelete($this->_relTypeID);
  }

  /**
   * check with complete array + custom field
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  function testGetWithCustom() {
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

  function createCustomGroup() {
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

  function createCustomField() {
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


    $result = $this->callAPISuccess('CustomField', 'create', $params);

    $customField = NULL;
    $ids[] = $customField['result']['customFieldId'];

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

  ///////////////// civicrm_relationship_delete methods

  /**
   * check with empty array
   */
  function testRelationshipDeleteEmpty() {
    $params = array();
    $result = $this->callAPIFailure('relationship', 'delete', $params, 'Mandatory key(s) missing from params array: id');
  }

  /**
   * check if required fields are not passed
   */
  function testRelationshipDeleteWithoutRequired() {
    $params = array(
      'start_date' => '2008-12-20',
      'end_date' => '2009-12-20',
      'is_active' => 1,
    );

    $result = $this->callAPIFailure('relationship', 'delete', $params, 'Mandatory key(s) missing from params array: id');
  }

  /**
   * check with incorrect required fields
   */
  function testRelationshipDeleteWithIncorrectData() {
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => 'Breaking Relationship',
    );

    $result = $this->callAPIFailure('relationship', 'delete', $params, 'Mandatory key(s) missing from params array: id');

    $params['id'] = "Invalid";
    $result = $this->callAPIFailure('relationship', 'delete', $params, 'Invalid value for relationship ID');
  }

  /**
   * check relationship creation
   */
  function testRelationshipDelete() {
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
    );

    $result = $this->callAPISuccess('relationship', 'create', $params);

    //Delete relationship
    $params = array();
    $params['id'] = $result['id'];

    $result = $this->callAPIAndDocument('relationship', 'delete', $params, __FUNCTION__, __FILE__);
    $this->relationshipTypeDelete($this->_relTypeID);
  }

  ///////////////// civicrm_relationship_update methods

  /**
   * check with empty array
   */
  function testRelationshipUpdateEmpty() {
    $result = $this->callAPIFailure('relationship', 'create', array(),
      'Mandatory key(s) missing from params array: contact_id_a, contact_id_b, relationship_type_id');
  }

  /**
   * check if required fields are not passed
   */

  /**
   * check relationship update
   */
  function testRelationshipCreateDuplicate() {
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
    $this->_relationID = $result['id'];

    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '20081214',
      'end_date' => '20091214',
      'is_active' => 0,
    );

    $result = $this->callAPIFailure('relationship', 'create', $params, 'Relationship already exists');

    //delete created relationship
    $params = array(
      'id' => $this->_relationID,
    );

    $result = $this->callAPISuccess('relationship', 'delete', $params);

    //delete created relationship type
    $this->relationshipTypeDelete($this->_relTypeID);
  }

  /**
   * check with valid params array.
   */
  function testRelationshipsGet() {
    $relParams = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2011-01-01',
      'end_date' => '2013-01-01',
      'is_active' => 1,
    );

    $result = $this->callAPISuccess('relationship', 'create', $relParams);

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
   * check with valid params array.
   * (The get function will behave differently without 'contact_id' passed
   */
  function testRelationshipsGetGeneric() {
    $relParams = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2011-01-01',
      'end_date' => '2013-01-01',
      'is_active' => 1,
    );

    $result = $this->callAPISuccess('relationship', 'create', $relParams);

    //get relationship
    $params = array(
      'contact_id_b' => $this->_cId_b,
    );
    $result = $this->callAPISuccess('relationship', 'get', $params);
  }

  function testGetIsCurrent() {
    $rel2Params =array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b2,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 0,
    );
    $rel2 = $this->callAPISuccess('relationship', 'create', $rel2Params);
    $rel1 = $this->callAPISuccess('relationship', 'create', $this->_params);

    $getParams = array(
      'filters' => array('is_current' => 1)
    );
    $description = "demonstrates is_current filter";
    $subfile = 'filterIsCurrent';
    //no relationship has been created
    $result = $this->callAPIAndDocument('relationship', 'get', $getParams, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 1);
    $this->AssertEquals($rel1['id'], $result['id']);

    // now try not started
    $rel2Params['is_active'] =1;
    $rel2Params['start_date'] ='tomorrow';
    $rel2 = $this->callAPISuccess('relationship', 'create', $rel2Params);
    $result = $this->callAPISuccess('relationship', 'get', $getParams);
    $this->assertEquals($result['count'], 1);
    $this->AssertEquals($rel1['id'], $result['id']);

    // now try finished
    $rel2Params['is_active'] =1;
    $rel2Params['start_date'] ='last week';
    $rel2Params['end_date'] ='yesterday';
    $rel2 = $this->callAPISuccess('relationship', 'create', $rel2Params);
  }
  /*
   * Test using various operators
   */
  function testGetTypeOperators() {
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
      array('relationship_type_id' => $relationType2,)));
    $rel3 = $this->callAPISuccess('relationship', 'create', array_merge($this->_params,
        array('relationship_type_id' => $relationType3,)));
    $rel4 = $this->callAPISuccess('relationship', 'create', array_merge($this->_params,
        array('relationship_type_id' => $relationType4,)));

    $getParams = array(
      'relationship_type_id' => array('IN' => array($relationType2, $relationType3))
    );

    $description = "demonstrates use of IN filter";
    $subfile = 'INRelationshipType';

    $result = $this->callAPIAndDocument('relationship', 'get', $getParams, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 2);
    $this->AssertEquals(array($rel2['id'], $rel3['id']), array_keys($result['values']));

    $description = "demonstrates use of NOT IN filter";
    $subfile = 'NotInRelationshipType';
    $getParams = array(
        'relationship_type_id' => array('NOT IN' => array($relationType2, $relationType3))
    );
    $result = $this->callAPIAndDocument('relationship', 'get', $getParams, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 2);
    $this->AssertEquals(array($rel1['id'], $rel4['id']), array_keys($result['values']));

    $description = "demonstrates use of BETWEEN filter";
    $subfile = 'BetweenRelationshipType';
    $getParams = array(
        'relationship_type_id' => array('BETWEEN' => array($relationType2, $relationType4))
    );
    $result = $this->callAPIAndDocument('relationship', 'get', $getParams, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 3);
    $this->AssertEquals(array($rel2['id'], $rel3['id'], $rel4['id']), array_keys($result['values']));

    $description = "demonstrates use of Not BETWEEN filter";
    $subfile = 'NotBetweenRelationshipType';
    $getParams = array(
        'relationship_type_id' => array('NOT BETWEEN' => array($relationType2, $relationType4))
    );
    $result = $this->callAPIAndDocument('relationship', 'get', $getParams, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 1);
    $this->AssertEquals(array($rel1['id'],), array_keys($result['values']));

  }
  /**
   * check with invalid relationshipType Id
   */
  function testRelationshipTypeAddInvalidId() {
    $relTypeParams = array(
      'id' => 'invalid',
      'name_a_b' => 'Relation 1 for delete',
      'name_b_a' => 'Relation 2 for delete',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
    );
    $result = $this->callAPIFailure('relationship_type', 'create', $relTypeParams,
      'id is not a valid integer');
  }

  /**
   * check with valid data with contact_b
   */
  function testGetRelationshipWithContactB() {
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
    $result = $this->callAPISuccess('relationship', 'delete', $params);
    $this->relationshipTypeDelete($this->_relTypeID);
  }

  /**
   * check with valid data with relationshipTypes
   */
  function testGetRelationshipWithRelTypes() {
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
    $result = $this->callAPISuccess('relationship', 'get', $contact_a);

    $params = array(
      'id' => $relationship['id'],
    );
    $result = $this->callAPISuccess('relationship', 'delete', $params);
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
  function testGetRelationshipByTypeReciprocal() {
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
  function testGetRelationshipByTypeDAO() {
    $this->ids['relationship'] = $this->callAPISuccess($this->_entity, 'create', array('format.only_id' => TRUE,)  + $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'getcount', array(
      'contact_id_a' => $this->_cId_a,),
    1);
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
  function testGetRelationshipByTypeArrayDAO() {
    $created = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $org3 = $this->organizationCreate();
    $relType2 = 5; // lets just assume built in ones aren't being messed with!
    $relType3 = 6; // lets just assume built in ones aren't being messed with!

    //relationshp 2
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType2,
        'contact_id_b' => $this->_cId_b2))
    );

    //relationshp 3
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType3,
        'contact_id_b' => $org3))
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
  function testGetRelationshipByTypeArrayReciprocal() {
    $created = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $org3 = $this->organizationCreate();
    $relType2 = 5; // lets just assume built in ones aren't being messed with!
    $relType3 = 6; // lets just assume built in ones aren't being messed with!

    //relationshp 2
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType2,
        'contact_id_b' => $this->_cId_b2))
    );

    //relationshp 3
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType3,
        'contact_id_b' => $org3))
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
   * Checks that passing in 'contact_id_b' + a relationship type
   * will filter by relationship type for contact b
   *
   * We should get 1 result without or with correct relationship type id & 0 with
   * an incorrect one
   */
  function testGetRelationshipByMembershipTypeDAO() {
    $created = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $org3 = $this->organizationCreate();

    $relType2 = 5; // lets just assume built in ones aren't being messed with!
    $relType3 = 6; // lets just assume built in ones aren't being messed with!
    $relType1 = 1;
    $memberType = $this->membershipTypeCreate(array(
      'relationship_type_id' => CRM_Core_DAO::VALUE_SEPARATOR . $relType1 . CRM_Core_DAO::VALUE_SEPARATOR . $relType3 . CRM_Core_DAO::VALUE_SEPARATOR,
      'relationship_direction' => CRM_Core_DAO::VALUE_SEPARATOR . 'a_b' . CRM_Core_DAO::VALUE_SEPARATOR . 'b_a' . CRM_Core_DAO::VALUE_SEPARATOR,
    ));

    //relationshp 2
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType2,
        'contact_id_b' => $this->_cId_b2))
    );

    //relationshp 3
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType3,
        'contact_id_b' => $org3))
    );

    //relationshp 4 with reveral
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType1,
        'contact_id_a' => $this->_cId_a,
        'contact_id_b' => $this->_cId_a_2))
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
  function testGetRelationshipByMembershipTypeReciprocal() {
      $created = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $org3 = $this->organizationCreate();

    $relType2 = 5; // lets just assume built in ones aren't being messed with!
    $relType3 = 6; // lets just assume built in ones aren't being messed with!
    $relType1 = 1;
    $memberType = $this->membershipTypeCreate(array(
      'relationship_type_id' => CRM_Core_DAO::VALUE_SEPARATOR . $relType1 . CRM_Core_DAO::VALUE_SEPARATOR . $relType3 . CRM_Core_DAO::VALUE_SEPARATOR,
      'relationship_direction' => CRM_Core_DAO::VALUE_SEPARATOR . 'a_b' . CRM_Core_DAO::VALUE_SEPARATOR . 'b_a' . CRM_Core_DAO::VALUE_SEPARATOR,
    ));

    //relationshp 2
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType2,
        'contact_id_b' => $this->_cId_b2))
    );

    //relationshp 3
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType3,
        'contact_id_b' => $org3))
    );

    //relationshp 4 with reveral
    $this->callAPISuccess($this->_entity, 'create',
      array_merge($this->_params, array(
        'relationship_type_id' => $relType1,
        'contact_id_a' => $this->_cId_a,
        'contact_id_b' => $this->_cId_a_2))
    );

    $result = $this->callAPISuccess($this->_entity, 'get', array(
      'contact_id' => $this->_cId_a,
      'membership_type_id' => $memberType,
    ));
    // although our contact has more than one relationship we have passed them in as contact_id_a & can't get reciprocal
    $this->assertEquals(2, $result['count']);

    foreach ($result['values'] as $key => $value) {
      $this->assertTrue(in_array($value['relationship_type_id'], array($relType1, $relType3)));
    }
  }
}
