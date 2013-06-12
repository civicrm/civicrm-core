<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
  protected $_apiversion;
  protected $_cId_a;
  protected $_cId_b;
  protected $_cId_b2;// second org
  protected $_relTypeID;
  protected $_ids = array();
  protected $_customGroupId = NULL;
  protected $_customFieldId = NULL;
  protected $_params;
  public $_eNoticeCompliant = FALSE;
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
    $this->_apiversion = 3;
    $this->_cId_a      = $this->individualCreate(NULL);
    $this->_cId_b      = $this->organizationCreate();
    $this->_cId_b2      = $this->organizationCreate(array('organization_name' => ' Org 2'));
    $this->_entity     = 'relationship';
    //Create a relationship type
    $relTypeParams = array(
      'name_a_b' => 'Relation 1 for delete',
      'name_b_a' => 'Relation 2 for delete',
      'description' => 'Testing relationship type',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
      'version' => $this->_apiversion,
    );
    $this->_relTypeID = $this->relationshipTypeCreate($relTypeParams);
    $this->_params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
      'version' => $this->_apiversion,
    );

  }

  function tearDown() {
    $this->quickCleanup(array('civicrm_relationship'));
    $this->relationshipTypeDelete($this->_relTypeID);
    $this->contactDelete($this->_cId_a);
    $this->contactDelete($this->_cId_b);
  }

  ///////////////// civicrm_relationship_create methods

  /**
   * check with empty array
   */
  function testRelationshipCreateEmpty() {
    $params = array('version' => $this->_apiversion);
    $result = civicrm_api('relationship', 'create', $params);
    $this->assertAPIFailure($result);
  }

  /**
   * check with No array
   */
  function testRelationshipCreateParamsNotArray() {
    $params = 'relationship_type_id = 5';
    $result = civicrm_api('relationship', 'create', $params);
    $this->assertAPIFailure($result);
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

    $result = civicrm_api('relationship', 'create', $params);
    $this->assertAPIFailure($result);
  }

  /**
   * check with incorrect required fields
   */
  function testRelationshipCreateWithIncorrectData() {

    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => 'Breaking Relationship',
      'version' => 3,
    );

    $result = civicrm_api('relationship', 'create', $params);
    $this->assertAPIFailure($result);

    //contact id is not an integer
    $params = array(
      'contact_id_a' => 'invalid',
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'is_active' => 1,
    );
    $result = civicrm_api('relationship', 'create', $params);
    $this->assertAPIFailure($result);

    //contact id does not exists
    $params['contact_id_a'] = 999;
    $result = civicrm_api('relationship', 'create', $params);
    $this->assertAPIFailure($result);

    //invalid date
    $params['contact_id_a'] = $this->_cId_a;
    $params['start_date'] = array('d' => '1', 'M' => '1');
    $result = civicrm_api('relationship', 'create', $params);
    $this->assertAPIFailure($result);
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
      'version' => 3,
    );

    $result = civicrm_api('relationship', 'create', $params);
    $this->assertAPIFailure($result);

    // both the contact of type Organization
    $params = array(
      'contact_id_a' => $this->_cId_b,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-01-10',
      'is_active' => 1,
      'version' => 3,
    );

    $result = civicrm_api('relationship', 'create', $params);
    $this->assertAPIFailure($result);
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
      'version' => $this->_apiversion,
    );
    $relationship = civicrm_api('relationship', 'create', $params);

    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 1,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('relationship', 'create', $params);

    $this->assertAPIFailure($result);
    $this->assertEquals($result['error_message'], 'Relationship already exists');

    $params['id'] = $relationship['id'];
    $result = civicrm_api('relationship', 'delete', $params);
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
      'version' => $this->_apiversion,
    );
    $relationship = civicrm_api('relationship', 'create', $params);

    $params = array(
      'id' => $relationship['id'],
      'is_active' => 0,
      'version' => $this->_apiversion,
      'debug' => 1,
    );
    $result = civicrm_api('relationship', 'create', $params);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    $result = civicrm_api('relationship', 'get', $params);
    $this->assertEquals(0, $result['values'][$result['id']]['is_active'], 'in line ' . __LINE__);
    $params['id'] = $relationship['id'];
    $result = civicrm_api('relationship', 'delete', $params);
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
      'end_date' => NULL,
      'is_active' => 1,
      'is_permission_a_b' => 1,
      'description' => 'my desc',
      'version' => $this->_apiversion,
    );
    $relationship = civicrm_api('relationship', 'create', $params);

    $updateparams = array(
      'id' => $relationship['id'],
      'version' => $this->_apiversion,
      'relationship_type_id' => $this->_relTypeID,
    );
    $result = civicrm_api('relationship', 'create', $updateparams);

    $this->assertAPISuccess($result, 'in line ' . __LINE__);
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
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('relationship', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'in line ' . __LINE__);
    $this->assertNotNull($result['id'], 'in line ' . __LINE__);
    $relationParams = array(
      'id' => $result['id'],
    );

    // assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Contact_DAO_Relationship', $result['id'], $relationParams);
    $result = civicrm_api('relationship', 'get', array('version' => 3, 'id' => $result['id']));
    $values = $result['values'][$result['id']];
    foreach ($params as $key => $value) {
      if ($key == 'version' || $key == 'note') {
        continue;
      }
      $this->assertEquals($value, $values[$key], $key . " doesn't match " . print_r($values, TRUE) . 'in line' . __LINE__);
    }
    $params['id'] = $result['id'];
    civicrm_api('relationship', 'delete', $params);
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
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('relationship', 'create', $params);

    $this->assertEquals(0, $result['is_error'], 'in line ' . __LINE__);
    $this->assertNotNull($result['id'], 'in line ' . __LINE__);
    $relationParams = array(
      'id' => $result['id'],
    );

    // assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Contact_DAO_Relationship', $result['id'], $relationParams);
    $result = civicrm_api('relationship', 'get', array('version' => 3, 'id' => $result['id']));
    $values = $result['values'][$result['id']];
    foreach ($params as $key => $value) {
      if ($key == 'version' || $key == 'note') {
        continue;
      }
      if($key == 'end_date'){
        $this->assertTrue(empty($values[$key]));
        continue;
      }
      $this->assertEquals($value, $values[$key], $key . " doesn't match " . print_r($values, TRUE) . 'in line' . __LINE__);
    }
    $params['id'] = $result['id'];
    civicrm_api('relationship', 'delete', $params);
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
      'version' => $this->_apiversion,
    );
    $params = array_merge($params, $custom_params);
    $result = civicrm_api('relationship', 'create', $params);

    $this->assertNotNull($result['id']);
    $relationParams = array(
      'id' => $result['id'],
    );
    // assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Contact_DAO_Relationship', $result['id'], $relationParams);

    $params['id'] = $result['id'];
    $result = civicrm_api('relationship', 'delete', $params);
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

    $result = civicrm_api($this->_entity, 'create', $params);
    $this->assertEquals($result['id'], $result['values'][$result['id']]['id']);

    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $getParams = array('version' => 3, 'id' => $result['id']);
    $check = civicrm_api($this->_entity, 'get', $getParams);
    $this->documentMe($getParams, $check, __FUNCTION__, __FILE__);
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
      'version' => $this->_apiversion,
    );
    $customGroup = civicrm_api('custom_group', 'create', $params);
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
      'version' => $this->_apiversion,
    );


    $result = civicrm_api('CustomField', 'create', $params);

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
      'version' => $this->_apiversion,
    );

    $customField = civicrm_api('custom_field', 'create', $params);
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
      'version' => $this->_apiversion,
    );

    $customField = civicrm_api('custom_field', 'create', $params);

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
      'version' => $this->_apiversion,
    );

    $customField = civicrm_api('custom_field', 'create', $params);
    $ids[] = $customField['id'];
    return $ids;
  }

  ///////////////// civicrm_relationship_delete methods

  /**
   * check with empty array
   */
  function testRelationshipDeleteEmpty() {
    $params = array('version' => $this->_apiversion);
    $result = civicrm_api('relationship', 'delete', $params);
    $this->assertAPIFailure($result);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: id');
  }

  /**
   * check with No array
   */
  function testRelationshipDeleteParamsNotArray() {
    $params = 'relationship_type_id = 5';
    $result = civicrm_api('relationship', 'delete', $params);
    $this->assertAPIFailure($result);
    $this->assertEquals($result['error_message'], 'Input variable `params` is not an array');
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

    $result = civicrm_api('relationship', 'delete', $params);
    $this->assertAPIFailure($result);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: version, id');
  }

  /**
   * check with incorrect required fields
   */
  function testRelationshipDeleteWithIncorrectData() {
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => 'Breaking Relationship',
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('relationship', 'delete', $params);
    $this->assertAPIFailure($result, 'in line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: id', 'in line ' . __LINE__);

    $params['id'] = "Invalid";
    $result = civicrm_api('relationship', 'delete', $params);
    $this->assertAPIFailure($result, 'in line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Invalid value for relationship ID', 'in line ' . __LINE__);
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
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('relationship', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['id']);

    //Delete relationship
    $params = array();
    $params['id'] = $result['id'];

    $result = civicrm_api('relationship', 'delete', $params);
    $this->relationshipTypeDelete($this->_relTypeID);
  }

  ///////////////// civicrm_relationship_update methods

  /**
   * check with empty array
   */
  function testRelationshipUpdateEmpty() {
    $params = array('version' => 3);
    $result = civicrm_api('relationship', 'create', $params);
    $this->assertAPIFailure($result);
    $this->assertEquals('Mandatory key(s) missing from params array: contact_id_a, contact_id_b, relationship_type_id', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * check with No array
   */
  function testRelationshipUpdateParamsNotArray() {
    $params = 'relationship_type_id = 5';
    $result = civicrm_api('relationship', 'create', $params);
    $this->assertAPIFailure($result);
    $this->assertEquals('Input variable `params` is not an array', $result['error_message'], 'In line ' . __LINE__);
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
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('relationship', 'create', $relParams);

    $this->assertNotNull($result['id'], 'In line ' . __LINE__);
    $this->_relationID = $result['id'];

    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '20081214',
      'end_date' => '20091214', 'is_active' => 0,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('relationship', 'create', $params);

    $this->assertAPIFailure($result, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Relationship already exists', 'In line ' . __LINE__);

    //delete created relationship
    $params = array(
      'id' => $this->_relationID,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('relationship', 'delete', $params);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);

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
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('relationship', 'create', $relParams);

    //get relationship
    $params = array(
      'contact_id' => $this->_cId_b,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('relationship', 'get', $params);

    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    $this->assertEquals($result['count'], 1, 'in line ' . __LINE__);
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('relationship', 'get', $params);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    $this->assertEquals($result['count'], 1, 'in line ' . __LINE__);
    // contact_id_a is wrong so should be no matches
    $params = array(
      'contact_id_a' => $this->_cId_b,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('relationship', 'get', $params);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    $this->assertEquals($result['count'], 0, 'in line ' . __LINE__);
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
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('relationship', 'create', $relParams);

    //get relationship
    $params = array(
      'contact_id_b' => $this->_cId_b,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('relationship', 'get', $params);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
  }

  function testGetIsCurrent() {
    $rel2Params =array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b2,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => '2008-12-20',
      'is_active' => 0,
      'version' => $this->_apiversion,
    );
    $rel2 = civicrm_api('relationship', 'create', $rel2Params);
    $this->assertAPISuccess($rel2);
    $rel1 = civicrm_api('relationship', 'create', $this->_params);
    $this->assertAPISuccess($rel1);
    $getParams = array(
      'version' => $this->_apiversion,
      'filters' => array('is_current' => 1)
    );
    $description = "demonstrates is_current filter";
    $subfile = 'filterIsCurrent';
    //no relationship has been created
    $result = civicrm_api('relationship', 'get', $getParams);
    $this->documentMe($getParams, $result, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 1);
    $this->AssertEquals($rel1['id'], $result['id']);

    // now try not started
    $rel2Params['is_active'] =1;
    $rel2Params['start_date'] ='tomorrow';
    $rel2 = civicrm_api('relationship', 'create', $rel2Params);
    $result = civicrm_api('relationship', 'get', $getParams);
    $this->assertEquals($result['count'], 1);
    $this->AssertEquals($rel1['id'], $result['id']);

    // now try finished
    $rel2Params['is_active'] =1;
    $rel2Params['start_date'] ='last week';
    $rel2Params['end_date'] ='yesterday';
    $rel2 = civicrm_api('relationship', 'create', $rel2Params);
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
        'version' => $this->_apiversion,
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
        'version' => $this->_apiversion,
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
        'version' => $this->_apiversion,
    );
    $relationType4 = $this->relationshipTypeCreate($relTypeParams);

    $rel1 = civicrm_api('relationship', 'create', $this->_params);
    $this->assertAPISuccess($rel1);
    $rel2 = civicrm_api('relationship', 'create', array_merge($this->_params,
      array('relationship_type_id' => $relationType2,)));
    $this->assertAPISuccess($rel2);
    $rel3 = civicrm_api('relationship', 'create', array_merge($this->_params,
        array('relationship_type_id' => $relationType3,)));
    $this->assertAPISuccess($rel3);
    $rel4 = civicrm_api('relationship', 'create', array_merge($this->_params,
        array('relationship_type_id' => $relationType4,)));
    $this->assertAPISuccess($rel4);

    $getParams = array(
        'version' => $this->_apiversion,
        'relationship_type_id' => array('IN' => array($relationType2, $relationType3))
    );


    $description = "demonstrates use of IN filter";
    $subfile = 'INRelationshipType';

    $result = civicrm_api('relationship', 'get', $getParams);
    $this->documentMe($getParams, $result, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 2);
    $this->AssertEquals(array($rel2['id'], $rel3['id']), array_keys($result['values']));

    $description = "demonstrates use of NOT IN filter";
    $subfile = 'NotInRelationshipType';
    $getParams = array(
        'version' => $this->_apiversion,
        'relationship_type_id' => array('NOT IN' => array($relationType2, $relationType3))
    );
    $result = civicrm_api('relationship', 'get', $getParams);
    $this->documentMe($getParams, $result, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 2);
    $this->AssertEquals(array($rel1['id'], $rel4['id']), array_keys($result['values']));

    $description = "demonstrates use of BETWEEN filter";
    $subfile = 'BetweenRelationshipType';
    $getParams = array(
        'version' => $this->_apiversion,
        'relationship_type_id' => array('BETWEEN' => array($relationType2, $relationType4))
    );
    $result = civicrm_api('relationship', 'get', $getParams);
    $this->documentMe($getParams, $result, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['count'], 3);
    $this->AssertEquals(array($rel2['id'], $rel3['id'], $rel4['id']), array_keys($result['values']));

    $description = "demonstrates use of Not BETWEEN filter";
    $subfile = 'NotBetweenRelationshipType';
    $getParams = array(
        'version' => $this->_apiversion,
        'relationship_type_id' => array('NOT BETWEEN' => array($relationType2, $relationType4))
    );
    $result = civicrm_api('relationship', 'get', $getParams);
    $this->documentMe($getParams, $result, __FUNCTION__, __FILE__, $description, $subfile);
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
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('relationship_type', 'create', $relTypeParams);
    $this->assertAPIFailure($result, 'in line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Invalid value for relationship type ID', 'in line ' . __LINE__);
  }

  ///////////////// civicrm_get_relationships

  /**
   * check with invalid data
   */
  function testGetRelationshipInvalidData() {
    $contact_a = array('contact_id' => $this->_cId_a);
    $contact_b = array('contact_id' => $this->_cId_b);

    //no relationship has been created
    $result = civicrm_api('relationship', 'get', $contact_a, $contact_b, NULL, 'asc');
    $this->assertAPIFailure($result);
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
      'version' => $this->_apiversion,
    );

    $relationship = civicrm_api('relationship', 'create', $relParams);

    $contacts = array(
      'contact_id' => $this->_cId_a,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('relationship', 'get', $contacts);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    $this->assertGreaterThan(0, $result['count'], 'in line ' . __LINE__);
    $params = array(
      'id' => $relationship['id'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('relationship', 'delete', $params);
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
      'version' => $this->_apiversion,
    );

    $relationship = civicrm_api('relationship', 'create', $relParams);

    $contact_a = array(
      'contact_id' => $this->_cId_a,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('relationship', 'get', $contact_a);

    $this->assertAPISuccess($result, 'in line ' . __LINE__);

    $params = array(
      'id' => $relationship['id'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('relationship', 'delete', $params);
    $this->relationshipTypeDelete($this->_relTypeID);
  }
}

