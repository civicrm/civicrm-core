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
 * Class contains api test cases for "civicrm_relationship_type"
 *
 * @group headless
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
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipTypeCreateWithoutName($version) {
    $this->_apiversion = $version;
    $relTypeParams = array(
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
    );
    $result = $this->callAPIFailure('relationship_type', 'create', $relTypeParams);
  }

  /**
   * Create relationship type.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipTypeCreate($version) {
    $this->_apiversion = $version;
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
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipTypeCreateExample($version) {
    $this->_apiversion = $version;
    require_once 'api/v3/examples/RelationshipType/Create.php';
    $result = relationship_type_create_example();
    $expectedResult = relationship_type_create_expectedresult();
    $this->assertAPISuccess($result);
  }

  /**
   * Check if required fields are not passed.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipTypeDeleteWithoutRequired($version) {
    $this->_apiversion = $version;
    $params = array(
      'name_b_a' => 'Relation 2 delete without required',
      'contact_type_b' => 'Individual',
      'is_reserved' => 0,
      'is_active' => 0,
    );

    $result = $this->callAPIFailure('relationship_type', 'delete', $params);
    if ($version == 3) {
      $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: id');
    }
    else {
      $this->assertEquals($result['error_message'], 'Parameter "where" is required.');
    }
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
      'id is not a valid integer'
    );
  }

  /**
   * Check relationship type delete.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipTypeDelete($version) {
    $this->_apiversion = $version;
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
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipTypeUpdateEmpty($version) {
    $this->_apiversion = $version;
    $params = array();
    $result = $this->callAPIFailure('relationship_type', 'create', $params);
    $this->assertContains('name_a_b', $result['error_message']);
    $this->assertContains('name_b_a', $result['error_message']);
  }

  /**
   * Check with no contact type.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipTypeUpdateWithoutContactType($version) {
    $this->_apiversion = $version;
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
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipTypeUpdate($version) {
    $this->_apiversion = $version;
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
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipTypesGetEmptyParams($version) {
    $this->_apiversion = $version;
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
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipTypesGetParamsNotArray($version) {
    $this->_apiversion = $version;

    $results = $this->callAPIFailure('relationship_type', 'get', 'string');
  }

  /**
   * Check with valid params array.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRelationshipTypesGet($version) {
    $this->_apiversion = $version;
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
