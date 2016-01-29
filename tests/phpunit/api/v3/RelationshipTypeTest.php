<?php
/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2015                                |
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
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Class contains api test cases for "civicrm_relationship_type"
 *
 */
class api_v3_RelationshipTypeTest extends CiviUnitTestCase {
  protected $_cId_a;
  protected $_cId_b;
  protected $_relTypeID;
  protected $_apiversion = 3;

  public function setUp() {

    parent::setUp();
    $this->_cId_a = $this->individualCreate();
    $this->_cId_b = $this->organizationCreate();
  }

  public function tearDown() {

    $tablesToTruncate = array(
      'civicrm_contact',
      'civicrm_relationship_type',
    );
    $this->quickCleanup($tablesToTruncate);
  }

  ///////////////// civicrm_relationship_type_add methods

  /**
   * Check with no name.
   */
  public function testRelationshipTypeCreateWithoutName() {
    $relTypeParams = array(
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
    );
    $result = $this->callAPIFailure('relationship_type', 'create', $relTypeParams,
      'Mandatory key(s) missing from params array: name_a_b, name_b_a'
    );
  }

  /**
   * Check with no contact type.
   */
  public function testRelationshipTypeCreateWithoutContactType() {
    $relTypeParams = array(
      'name_a_b' => 'Relation 1 without contact type',
      'name_b_a' => 'Relation 2 without contact type',
    );
    $result = $this->callAPIFailure('relationship_type', 'create', $relTypeParams,
      'Mandatory key(s) missing from params array: contact_type_a, contact_type_b'
    );
  }

  /**
   * Create relationship type.
   */
  public function testRelationshipTypeCreate() {
    $params = array(
      'name_a_b' => 'Relation 1 for relationship type create',
      'name_b_a' => 'Relation 2 for relationship type create',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
      'sequential' => 1,
    );
    $result = $this->callAPIAndDocument('relationship_type', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['values'][0]['id']);
    unset($params['sequential']);
    //assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Contact_DAO_RelationshipType', $result['id'], $params);
  }

  /**
   * Test  using example code.
   */
  public function testRelationshipTypeCreateExample() {
    require_once 'api/v3/examples/RelationshipType/Create.php';
    $result = relationship_type_create_example();
    $expectedResult = relationship_type_create_expectedresult();
    $this->assertAPISuccess($result);
  }

  /**
   * Check if required fields are not passed.
   */
  public function testRelationshipTypeDeleteWithoutRequired() {
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
   * Check with incorrect required fields.
   */
  public function testRelationshipTypeDeleteWithIncorrectData() {
    $params = array(
      'id' => 'abcd',
      'name_b_a' => 'Relation 2 delete with incorrect',
      'description' => 'Testing relationship type',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
      'is_reserved' => 0,
      'is_active' => 0,
    );
    $result = $this->callAPIFailure('relationship_type', 'delete', $params,
      'Invalid value for relationship type ID'
    );
  }

  /**
   * Check relationship type delete.
   */
  public function testRelationshipTypeDelete() {
    $id = $this->_relationshipTypeCreate();
    // create sample relationship type.
    $params = array(
      'id' => $id,
    );
    $result = $this->callAPIAndDocument('relationship_type', 'delete', $params, __FUNCTION__, __FILE__);
    $this->assertAPIDeleted('relationship_type', $id);
  }

  ///////////////// civicrm_relationship_type_update

  /**
   * Check with empty array.
   */
  public function testRelationshipTypeUpdateEmpty() {
    $params = array();
    $result = $this->callAPIFailure('relationship_type', 'create', $params);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: name_a_b, name_b_a, contact_type_a, contact_type_b');
  }

  /**
   * Check with no contact type.
   */
  public function testRelationshipTypeUpdateWithoutContactType() {
    // create sample relationship type.
    $this->_relTypeID = $this->_relationshipTypeCreate();

    $relTypeParams = array(
      'id' => $this->_relTypeID,
      'name_a_b' => 'Test 1',
      'name_b_a' => 'Test 2',
      'description' => 'Testing relationship type',
      'is_reserved' => 1,
      'is_active' => 0,
    );

    $result = $this->callAPISuccess('relationship_type', 'create', $relTypeParams);
    $this->assertNotNull($result['id']);
    // assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Contact_DAO_RelationshipType', $result['id'], $relTypeParams);
  }

  /**
   * Check with all parameters.
   */
  public function testRelationshipTypeUpdate() {
    // create sample relationship type.
    $this->_relTypeID = $this->_relationshipTypeCreate();

    $params = array(
      'id' => $this->_relTypeID,
      'name_a_b' => 'Test 1 for update',
      'name_b_a' => 'Test 2 for update',
      'description' => 'SUNIL PAWAR relationship type',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
      'is_reserved' => 0,
      'is_active' => 0,
    );

    $result = $this->callAPISuccess('relationship_type', 'create', $params);
    $this->assertNotNull($result['id']);

    // assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Contact_DAO_RelationshipType', $result['id'], $params);
  }

  ///////////////// civicrm_relationship_types_get methods

  /**
   * Check with empty array.
   */
  public function testRelationshipTypesGetEmptyParams() {
    $firstRelTypeParams = array(
      'name_a_b' => 'Relation 27 for create',
      'name_b_a' => 'Relation 28 for create',
      'description' => 'Testing relationship type',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
    );

    $first = $this->callAPISuccess('RelationshipType', 'Create', $firstRelTypeParams);

    $secondRelTypeParams = array(
      'name_a_b' => 'Relation 25 for create',
      'name_b_a' => 'Relation 26 for create',
      'description' => 'Testing relationship type second',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 0,
      'is_active' => 1,
    );
    $second = $this->callAPISuccess('RelationshipType', 'Create', $secondRelTypeParams);
    $results = $this->callAPISuccess('relationship_type', 'get', array());

    $this->assertEquals(2, $results['count']);
  }

  /**
   * Check with params Not Array.
   */
  public function testRelationshipTypesGetParamsNotArray() {

    $results = $this->callAPIFailure('relationship_type', 'get', 'string');
  }

  /**
   * Check with valid params array.
   */
  public function testRelationshipTypesGet() {
    $firstRelTypeParams = array(
      'name_a_b' => 'Relation 30 for create',
      'name_b_a' => 'Relation 31 for create',
      'description' => 'Testing relationship type',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
    );

    $first = $this->callAPISuccess('RelationshipType', 'Create', $firstRelTypeParams);

    $secondRelTypeParams = array(
      'name_a_b' => 'Relation 32 for create',
      'name_b_a' => 'Relation 33 for create',
      'description' => 'Testing relationship type second',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 0,
      'is_active' => 1,
    );
    $second = $this->callAPISuccess('RelationshipType', 'Create', $secondRelTypeParams);

    $params = array(
      'name_a_b' => 'Relation 32 for create',
      'name_b_a' => 'Relation 33 for create',
      'description' => 'Testing relationship type second',
    );
    $results = $this->callAPISuccess('relationship_type', 'get', $params);

    $this->assertEquals(1, $results['count'], ' in line ' . __LINE__);
    $this->assertEquals(1, $results['values'][$results['id']]['is_active'], ' in line ' . __LINE__);
  }

  /**
   * Create relationship type.
   * @param null $params
   * @return mixed
   */
  public function _relationshipTypeCreate($params = NULL) {
    if (!is_array($params) || empty($params)) {
      $params = array(
        'name_a_b' => 'Relation 1 for create',
        'name_b_a' => 'Relation 2 for create',
        'description' => 'Testing relationship type',
        'contact_type_a' => 'Individual',
        'contact_type_b' => 'Organization',
        'is_reserved' => 1,
        'is_active' => 1,
      );
    }

    return $this->relationshipTypeCreate($params);
  }

}
