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
 * Test class for CRM_Contact_BAO_Relationship
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_BAO_RelationshipTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   *
   * This method is called after a test is executed.
   *
   * @throws \CRM_Core_Exception
   */
  protected function tearDown() {
    $this->quickCleanup([
      'civicrm_relationship_type',
      'civicrm_relationship',
      'civicrm_contact',
    ]);

    parent::tearDown();
  }

  /**
   * Test Relationship Type Options Will Return Specified Type
   *
   * @throws \CRM_Core_Exception
   */
  public function testRelationshipTypeOptionsWillReturnSpecifiedType() {
    $orgToOrgType = 'A_B_relationship';
    $orgToOrgReverseType = 'B_A_relationship';
    $this->callAPISuccess('RelationshipType', 'create', [
      'name_a_b' => $orgToOrgType,
      'name_b_a' => $orgToOrgReverseType,
      'contact_type_a' => 'Organization',
      'contact_type_b' => 'Organization',
    ]);

    $result = CRM_Contact_BAO_Relationship::buildRelationshipTypeOptions(
      ['contact_type' => 'Organization']
    );
    $this->assertContains($orgToOrgType, $result);
    $this->assertContains($orgToOrgReverseType, $result);

    $result = CRM_Contact_BAO_Relationship::buildRelationshipTypeOptions(
      ['contact_type' => 'Individual']
    );

    $this->assertNotContains($orgToOrgType, $result);
    $this->assertNotContains($orgToOrgReverseType, $result);
  }

  public function testContactIdAndRelationshipIdWillBeUsedInFilter() {
    $individual = civicrm_api3('Contact', 'create', [
      'display_name' => 'Individual A',
      'contact_type' => 'Individual',
    ]);
    $organization = civicrm_api3('Contact', 'create', [
      'organization_name' => 'Organization B',
      'contact_type' => 'Organization',
    ]);

    $personToOrgType = 'A_B_relationship';
    $orgToPersonType = 'B_A_relationship';

    $orgToPersonTypeId = civicrm_api3('RelationshipType', 'create', [
      'name_a_b' => $personToOrgType,
      'name_b_a' => $orgToPersonType,
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
    ])['id'];

    $personToPersonType = 'A_B_alt_relationship';
    $personToPersonReverseType = 'B_A_alt_relationship';

    civicrm_api3('RelationshipType', 'create', [
      'name_a_b' => $personToPersonType,
      'name_b_a' => $personToPersonReverseType,
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
    ]);

    // create a relationship individual => organization
    $relationship = civicrm_api3('Relationship', 'create', [
      'contact_id_a' => $individual['id'],
      'contact_id_b' => $organization['id'],
      'relationship_type_id' => $orgToPersonTypeId,
    ]);

    $options = CRM_Contact_BAO_Relationship::buildRelationshipTypeOptions([
      'relationship_id' => (string) $relationship['id'],
      'contact_id' => $individual['id'],
    ]);

    // for this relationship only individual=>organization is possible
    $this->assertContains($personToOrgType, $options);
    $this->assertNotContains($orgToPersonType, $options);

    // by passing relationship ID we know that the "B" side is an organization
    $this->assertNotContains($personToPersonType, $options);
    $this->assertNotContains($personToPersonReverseType, $options);

    $options = CRM_Contact_BAO_Relationship::buildRelationshipTypeOptions([
      'contact_id' => $individual['id'],
    ]);

    // for this result we only know that "A" must be an individual
    $this->assertContains($personToOrgType, $options);
    $this->assertNotContains($orgToPersonType, $options);

    // unlike when we pass relationship type ID there is no filter by "B" type
    $this->assertContains($personToPersonType, $options);
    $this->assertContains($personToPersonReverseType, $options);
  }

  /**
   * Test removeRelationshipTypeDuplicates method.
   *
   * @dataProvider getRelationshipTypeDuplicates
   */
  public function testRemoveRelationshipTypeDuplicates($relationshipTypeList, $suffix = NULL, $expected, $description) {
    $result = CRM_Contact_BAO_Relationship::removeRelationshipTypeDuplicates($relationshipTypeList, $suffix);
    $this->assertEquals($expected, $result, "Failure on set '$description'");
  }

  public function getRelationshipTypeDuplicates() {
    $relationshipTypeList = [
      '1_a_b' => 'duplicate one',
      '1_b_a' => 'duplicate one',
      '2_a_b' => 'two a',
      '2_b_a' => 'two b',
    ];
    $data = [
      [
        $relationshipTypeList,
        'a_b',
        [
          '1_a_b' => 'duplicate one',
          '2_a_b' => 'two a',
          '2_b_a' => 'two b',
        ],
        'With suffix a_b',
      ],
      [
        $relationshipTypeList,
        'b_a',
        [
          '1_b_a' => 'duplicate one',
          '2_a_b' => 'two a',
          '2_b_a' => 'two b',
        ],
        'With suffix b_a',
      ],
      [
        $relationshipTypeList,
        NULL,
        [
          '1_a_b' => 'duplicate one',
          '2_a_b' => 'two a',
          '2_b_a' => 'two b',
        ],
        'With suffix NULL',
      ],
      [
        $relationshipTypeList,
        NULL,
        [
          '1_a_b' => 'duplicate one',
          '2_a_b' => 'two a',
          '2_b_a' => 'two b',
        ],
        'With suffix "" (empty string)',
      ],
    ];
    return $data;
  }

  /**
   * Test that two similar memberships are not created for two relationships
   *
   * @throws \CRM_Core_Exception
   */
  public function testSingleMembershipForTwoRelationships() {
    $individualID = $this->individualCreate(['display_name' => 'Individual A']);
    $organisationID = $this->organizationCreate(['organization_name' => 'Organization B']);
    $membershipOrganisationID = $this->organizationCreate(['organization_name' => 'Membership Organization']);
    $orgToPersonTypeId1 = $this->relationshipTypeCreate(['name_a_b' => 'Inherited_Relationship_1_A_B', 'name_b_a' => 'Inherited_Relationship_1_B_A']);
    $orgToPersonTypeId2 = $this->relationshipTypeCreate(['name_a_b' => 'Inherited_Relationship_2_A_B', 'name_b_a' => 'Inherited_Relationship_2_B_A']);

    $membershipType = $this->callAPISuccess('MembershipType', 'create', [
      'member_of_contact_id' => $membershipOrganisationID,
      'financial_type_id' => 'Member Dues',
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'name' => 'Inherited Membership',
      'relationship_type_id' => [$orgToPersonTypeId1, $orgToPersonTypeId2],
      'relationship_direction' => ['b_a', 'b_a'],
    ]);
    $membershipType = $this->callAPISuccessGetSingle('MembershipType', ['id' => $membershipType['id']]);
    // Check the metadata worked....
    $this->assertEquals([$orgToPersonTypeId1, $orgToPersonTypeId2], $membershipType['relationship_type_id']);
    $this->assertEquals(['b_a', 'b_a'], $membershipType['relationship_direction']);

    $this->callAPISuccess('Membership', 'create', [
      'membership_type_id' => $membershipType['id'],
      'contact_id' => $organisationID,
      'start_date' => '2019-08-19',
      'join_date' => '2019-07-19',
    ]);

    $relationshipOne = $this->callAPISuccess('Relationship', 'create', [
      'contact_id_a' => $individualID,
      'contact_id_b' => $organisationID,
      'relationship_type_id' => $orgToPersonTypeId1,
    ]);
    $this->callAPISuccessGetCount('Membership', ['contact_id' => $individualID], 1);
    $relationshipTwo = $this->callAPISuccess('Relationship', 'create', [
      'contact_id_a' => $individualID,
      'contact_id_b' => $organisationID,
      'relationship_type_id' => $orgToPersonTypeId2,
    ]);
    $this->callAPISuccessGetCount('Membership', ['contact_id' => $individualID], 1);

    $inheritedMembership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $individualID]);
    $this->assertEquals('2019-08-19', $inheritedMembership['start_date']);
    $this->assertEquals('2019-07-19', $inheritedMembership['join_date']);

    $this->callAPISuccessGetCount('Membership', ['contact_id' => $organisationID], 1);
    // Disable the relationship & check the membership is not removed because the other relationship is still valid.
    $relationshipOne['is_active'] = 0;
    $this->callAPISuccess('Relationship', 'create', array_merge($relationshipOne, ['is_active' => 0]));
    $this->callAPISuccessGetCount('Membership', ['contact_id' => $individualID], 1);

    $relationshipTwo['is_active'] = 0;
    $this->callAPISuccess('Relationship', 'create', $relationshipTwo);
    $this->callAPISuccessGetCount('Membership', ['contact_id' => $individualID], 0);

    $relationshipOne['is_active'] = 1;
    $this->callAPISuccess('Relationship', 'create', $relationshipOne);
    $this->callAPISuccessGetCount('Membership', ['contact_id' => $individualID], 1);

    $relationshipTwo['is_active'] = 1;
    $this->callAPISuccess('Relationship', 'create', $relationshipTwo);
    $this->callAPISuccessGetCount('Membership', ['contact_id' => $individualID], 1);
    $this->callAPISuccess('Relationship', 'delete', ['id' => $relationshipTwo['id']]);
    $this->callAPISuccessGetCount('Membership', ['contact_id' => $individualID], 1);
    $this->callAPISuccess('Relationship', 'delete', ['id' => $relationshipOne['id']]);
    $this->callAPISuccessGetCount('Membership', ['contact_id' => $individualID], 0);

  }

  /**
   * Test CRM_Contact_BAO_Relationship::add() function directly.
   *
   * In general it's preferred to use the Relationship-create api since it does
   * checks and such before calling add(). There are already some good tests
   * for the api, but since it does some more business logic after too the
   * tests might not be checking exactly the same thing.
   */
  public function testBAOAdd() {
    // add a new type
    $relationship_type_id_1 = $this->relationshipTypeCreate([
      'name_a_b' => 'Food poison tester is',
      'name_b_a' => 'Food poison tester for',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
    ]);

    // add some people
    $contact_id_1 = $this->individualCreate();
    $contact_id_2 = $this->individualCreate([], 1);

    // create new relationship (using BAO)
    $params = [
      'relationship_type_id' => $relationship_type_id_1,
      'contact_id_a' => $contact_id_1,
      'contact_id_b' => $contact_id_2,
    ];
    $relationshipObj = CRM_Contact_BAO_Relationship::add($params);
    $this->assertEquals($relationshipObj->relationship_type_id, $relationship_type_id_1);
    $this->assertEquals($relationshipObj->contact_id_a, $contact_id_1);
    $this->assertEquals($relationshipObj->contact_id_b, $contact_id_2);
    $this->assertEquals($relationshipObj->is_active, 1);

    // demonstrate PR 15103 - should fail before the patch and pass after
    $today = date('Ymd');
    $params = [
      'id' => $relationshipObj->id,
      'end_date' => $today,
    ];
    $relationshipObj = CRM_Contact_BAO_Relationship::add($params);
    $this->assertEquals($relationshipObj->relationship_type_id, $relationship_type_id_1);
    $this->assertEquals($relationshipObj->contact_id_a, $contact_id_1);
    $this->assertEquals($relationshipObj->contact_id_b, $contact_id_2);
    $this->assertEquals($relationshipObj->is_active, 1);
    $this->assertEquals($relationshipObj->end_date, $today);
  }

}
