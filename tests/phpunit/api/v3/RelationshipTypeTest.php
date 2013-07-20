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
 * Class contains api test cases for "civicrm_relationship_type"
 *
 */
class api_v3_RelationshipTypeTest extends CiviUnitTestCase {
  protected $_cId_a;
  protected $_cId_b;
  protected $_relTypeID;
  protected $_apiversion;
  public $_eNoticeCompliant = TRUE;
  function get_info() {
    return array(
      'name' => 'RelationshipType Create',
      'description' => 'Test all RelationshipType Create API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {

    parent::setUp();
    $this->_apiversion = 3;
    $this->_cId_a      = $this->individualCreate(NULL);
    $this->_cId_b      = $this->organizationCreate(NULL);
  }

  function tearDown() {

    $tablesToTruncate = array(
      'civicrm_contact',
      'civicrm_relationship_type',
    );
    $this->quickCleanup($tablesToTruncate);
  }

  ///////////////// civicrm_relationship_type_add methods

  /**
   * check with no name
   */
  function testRelationshipTypeCreateWithoutName() {
    $relTypeParams = array(
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'version' => $this->_apiversion,
    );
    $result = $this->callAPIFailure('relationship_type', 'create', $relTypeParams);
    $this->assertEquals($result['error_message'],
      'Mandatory key(s) missing from params array: name_a_b, name_b_a'
    );
  }

  /**
   * check with no contact type
   */
  function testRelationshipTypeCreateWithoutContactType() {
    $relTypeParams = array(
      'name_a_b' => 'Relation 1 without contact type',
      'name_b_a' => 'Relation 2 without contact type',
      'version' => $this->_apiversion,
    );
    $result = $this->callAPIFailure('relationship_type', 'create', $relTypeParams);
    $this->assertEquals($result['error_message'],
      'Mandatory key(s) missing from params array: contact_type_a, contact_type_b'
    );
  }

  /**
   * create relationship type
   */
  function testRelationshipTypeCreate() {
    $params = array(
      'name_a_b' => 'Relation 1 for relationship type create',
      'name_b_a' => 'Relation 2 for relationship type create',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
      'version' => $this->_apiversion,
      'sequential' => 1,
    );
    $result = civicrm_api('relationship_type', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['values'][0]['id'], 'in line ' . __LINE__);
    unset($params['version']);
    unset($params['sequential']);
    //assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Contact_DAO_RelationshipType', $result['id'], $params);
  }

  /**
   *  Test  using example code
   */
  function testRelationshipTypeCreateExample() {
    require_once 'api/v3/examples/RelationshipTypeCreate.php';
    $result = relationship_type_create_example();
    $expectedResult = relationship_type_create_expectedresult();
    $this->assertAPISuccess($result);
  }

  ///////////////// civicrm_relationship_type_delete methods

  /**
   * check with empty array
   */
  function testRelationshipTypeDeleteEmpty() {
    $params = array();
    $result = $this->callAPIFailure('relationship_type', 'delete', $params);
  }

  /**
   * check with No array
   */
  function testRelationshipTypeDeleteParamsNotArray() {
    $params = 'name_a_b = Test1';
    $result = $this->callAPIFailure('relationship_type', 'delete', $params);
  }

  /**
   * check if required fields are not passed
   */
  function testRelationshipTypeDeleteWithoutRequired() {
    $params = array(
      'name_b_a' => 'Relation 2 delete without required',
      'contact_type_b' => 'Individual',
      'is_reserved' => 0,
      'is_active' => 0,
    );

    $result = $this->callAPIFailure('relationship_type', 'delete', $params);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: id');
  }

  /**
   * check with incorrect required fields
   */
  function testRelationshipTypeDeleteWithIncorrectData() {
    $params = array(
      'id' => 'abcd',
      'name_b_a' => 'Relation 2 delete with incorrect',
      'description' => 'Testing relationship type',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
      'is_reserved' => 0,
      'is_active' => 0,
      'version' => $this->_apiversion,
    );

    $result = $this->callAPIFailure('relationship_type', 'delete', $params);
    $this->assertEquals($result['error_message'], 'Invalid value for relationship type ID');
  }

  /**
   * check relationship type delete
   */
  function testRelationshipTypeDelete() {
    $rel = $this->_relationshipTypeCreate();
    // create sample relationship type.
    $params = array(
      'id' => $rel,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('relationship_type', 'delete', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);
  }

  ///////////////// civicrm_relationship_type_update

  /**
   * check with empty array
   */
  function testRelationshipTypeUpdateEmpty() {
    $params = array();
    $result = $this->callAPIFailure('relationship_type', 'create', $params);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: name_a_b, name_b_a, contact_type_a, contact_type_b');
  }

  /**
   * check with No array
   */
  function testRelationshipTypeUpdateParamsNotArray() {
    $params = 'name_a_b = Relation 1';
    $result = $this->callAPIFailure('relationship_type', 'create', $params);
    $this->assertEquals($result['error_message'], 'Input variable `params` is not an array');
  }

  /**
   * check with no contact type
   */
  function testRelationshipTypeUpdateWithoutContactType() {
    // create sample relationship type.
    $this->_relTypeID = $this->_relationshipTypeCreate(NULL);

    $relTypeParams = array(
      'id' => $this->_relTypeID,
      'name_a_b' => 'Test 1',
      'name_b_a' => 'Test 2',
      'description' => 'Testing relationship type',
      'is_reserved' => 1,
      'is_active' => 0,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('relationship_type', 'create', $relTypeParams);
    $this->assertNotNull($result['id']);
    unset($relTypeParams['version']);
    // assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Contact_DAO_RelationshipType', $result['id'], $relTypeParams);
  }

  /**
   * check with all parameters
   */
  function testRelationshipTypeUpdate() {
    // create sample relationship type.
    $this->_relTypeID = $this->_relationshipTypeCreate(NULL);

    $params = array(
      'id' => $this->_relTypeID,
      'name_a_b' => 'Test 1 for update',
      'name_b_a' => 'Test 2 for update',
      'description' => 'SUNIL PAWAR relationship type',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
      'is_reserved' => 0,
      'is_active' => 0,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('relationship_type', 'create', $params);
    $this->assertNotNull($result['id']);
    unset($params['version']);
    // assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Contact_DAO_RelationshipType', $result['id'], $params);
  }

  ///////////////// civicrm_relationship_types_get methods

  /**
   * check with empty array
   */
  function testRelationshipTypesGetEmptyParams() {
    $firstRelTypeParams = array(
      'name_a_b' => 'Relation 27 for create',
      'name_b_a' => 'Relation 28 for create',
      'description' => 'Testing relationship type',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
      'version' => $this->_apiversion,
    );

    $first = civicrm_api('RelationshipType', 'Create', $firstRelTypeParams);

    $secondRelTypeParams = array(
      'name_a_b' => 'Relation 25 for create',
      'name_b_a' => 'Relation 26 for create',
      'description' => 'Testing relationship type second',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 0,
      'is_active' => 1,
      'version' => $this->_apiversion,
    );
    $second = civicrm_api('RelationshipType', 'Create', $secondRelTypeParams);
    $results = civicrm_api('relationship_type', 'get', array(
      'version' => $this->_apiversion,
      ));

    $this->assertEquals(2, $results['count']);
    $this->assertEquals(0, $results['is_error']);
  }

  /**
   * check with params Not Array.
   */
  function testRelationshipTypesGetParamsNotArray() {

    $results = $this->callAPIFailure('relationship_type', 'get', 'string');
  }

  /**
   * check with valid params array.
   */
  function testRelationshipTypesGet() {
    $firstRelTypeParams = array(
      'name_a_b' => 'Relation 30 for create',
      'name_b_a' => 'Relation 31 for create',
      'description' => 'Testing relationship type',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
      'version' => $this->_apiversion,
    );

    $first = civicrm_api('RelationshipType', 'Create', $firstRelTypeParams);

    $secondRelTypeParams = array(
      'name_a_b' => 'Relation 32 for create',
      'name_b_a' => 'Relation 33 for create',
      'description' => 'Testing relationship type second',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 0,
      'is_active' => 1,
      'version' => $this->_apiversion,
    );
    $second = civicrm_api('RelationshipType', 'Create', $secondRelTypeParams);

    $params = array(
      'name_a_b' => 'Relation 32 for create',
      'name_b_a' => 'Relation 33 for create',
      'description' => 'Testing relationship type second',
      'version' => $this->_apiversion,
    );
    $results = civicrm_api('relationship_type', 'get', $params);

    $this->assertEquals(0, $results['is_error'], ' in line ' . __LINE__);
    $this->assertEquals(1, $results['count'], ' in line ' . __LINE__);
    $this->assertEquals(1, $results['values'][$results['id']]['is_active'], ' in line ' . __LINE__);
  }

  /**
   * create relationship type.
   */
  function _relationshipTypeCreate($params = NULL) {
    if (!is_array($params) || empty($params)) {
      $params = array(
        'name_a_b' => 'Relation 1 for create',
        'name_b_a' => 'Relation 2 for create',
        'description' => 'Testing relationship type',
        'contact_type_a' => 'Individual',
        'contact_type_b' => 'Organization',
        'is_reserved' => 1,
        'is_active' => 1,
        'version' => API_LATEST_VERSION,
      );
    }

    return $this->relationshipTypeCreate($params);
  }
}

