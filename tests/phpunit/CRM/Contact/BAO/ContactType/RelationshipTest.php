<?php

/**
 * Class CRM_Contact_BAO_ContactType_RelationshipTest
 * @group headless
 */
class CRM_Contact_BAO_ContactType_RelationshipTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();

    //create contact subtypes
    $params = [
      'label' => 'indivi_student',
      'name' => 'indivi_student',
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->student = $params['name'];

    $params = [
      'label' => 'indivi_parent',
      'name' => 'indivi_parent',
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->parent = $params['name'];

    $params = [
      'label' => 'org_sponsor',
      'name' => 'org_sponsor',
      // Organization
      'parent_id' => 3,
      'is_active' => 1,
    ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->sponsor = $params['name'];

    //create contacts
    $params = [
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
    ];
    $this->individual = $this->individualCreate($params);

    $params = [
      'first_name' => 'Bill',
      'last_name' => 'Adams',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->student,
    ];
    $this->indivi_student = $this->individualCreate($params);

    $params = [
      'first_name' => 'Alen',
      'last_name' => 'Adams',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->parent,
    ];
    $this->indivi_parent = $this->individualCreate($params);

    $params = [
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
    ];
    $this->organization = $this->organizationCreate($params);

    $params = [
      'organization_name' => 'Conservation Corp',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->sponsor,
    ];
    $this->organization_sponsor = $this->organizationCreate($params);
  }

  public function tearDown() {
    $this->quickCleanup(['civicrm_contact']);

    $query = "
DELETE FROM civicrm_contact_type
      WHERE name IN ('{$this->student}','{$this->parent}','{$this->sponsor}');
    ";
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Methods create relationshipType with valid data.
   * success expected
   */
  public function testRelationshipTypeAddIndiviParent() {
    //check Individual to Parent RelationshipType
    $params = [
      'name_a_b' => 'indivToparent',
      'name_b_a' => 'parentToindiv',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
      'contact_sub_type_b' => $this->parent,
    ];
    $result = CRM_Contact_BAO_RelationshipType::add($params);
    $this->assertEquals($result->name_a_b, 'indivToparent');
    $this->assertEquals($result->contact_type_a, 'Individual');
    $this->assertEquals($result->contact_type_b, 'Individual');
    $this->assertEquals($result->contact_sub_type_b, $this->parent);
    $this->relationshipTypeDelete($result->id);
  }

  public function testRelationshipTypeAddSponcorIndivi() {
    //check Sponcor to Individual RelationshipType
    $params = [
      'name_a_b' => 'SponsorToIndiv',
      'name_b_a' => 'IndivToSponsor',
      'contact_type_a' => 'Organization',
      'contact_sub_type_a' => $this->sponsor,
      'contact_type_b' => 'Individual',
    ];
    $result = CRM_Contact_BAO_RelationshipType::add($params);
    $this->assertEquals($result->name_a_b, 'SponsorToIndiv');
    $this->assertEquals($result->contact_type_a, 'Organization');
    $this->assertEquals($result->contact_sub_type_a, $this->sponsor);
    $this->assertEquals($result->contact_type_b, 'Individual');
    $this->relationshipTypeDelete($result->id);
  }

  public function testRelationshipTypeAddStudentSponcor() {
    //check Student to Sponcer RelationshipType
    $params = [
      'name_a_b' => 'StudentToSponser',
      'name_b_a' => 'SponsorToStudent',
      'contact_type_a' => 'Individual',
      'contact_sub_type_a' => $this->student,
      'contact_type_b' => 'Organization',
      'contact_sub_type_b' => $this->sponsor,
    ];
    $result = CRM_Contact_BAO_RelationshipType::add($params);
    $this->assertEquals($result->name_a_b, 'StudentToSponser');
    $this->assertEquals($result->contact_type_a, 'Individual');
    $this->assertEquals($result->contact_sub_type_a, $this->student);
    $this->assertEquals($result->contact_type_b, 'Organization');
    $this->assertEquals($result->contact_sub_type_b, $this->sponsor);
    $this->relationshipTypeDelete($result->id);
  }

  /**
   * Methods create relationshipe within same contact type with invalid Relationships.
   */
  public function testRelationshipCreateInvalidWithinSameType() {
    //check for Individual to Parent
    $relTypeParams = [
      'name_a_b' => 'indivToparent',
      'name_b_a' => 'parentToindiv',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
      'contact_sub_type_b' => $this->parent,
    ];
    $relType = CRM_Contact_BAO_RelationshipType::add($relTypeParams);
    $params = [
      'relationship_type_id' => $relType->id . '_a_b',
      'contact_check' => [$this->indivi_student => 1],
    ];
    $ids = ['contact' => $this->individual];

    list($valid, $invalid, $duplicate, $saved, $relationshipIds) = CRM_Contact_BAO_Relationship::legacyCreateMultiple($params, $ids);

    $this->assertEquals($invalid, 1);
    $this->assertEquals(empty($relationshipIds), TRUE);
    $this->relationshipTypeDelete($relType->id);
  }

  /**
   * Methods create relationshipe within diff contact type with invalid Relationships.
   */
  public function testRelCreateInvalidWithinDiffTypeSpocorIndivi() {
    //check for Sponcer to Individual
    $relTypeParams = [
      'name_a_b' => 'SponsorToIndiv',
      'name_b_a' => 'IndivToSponsor',
      'contact_type_a' => 'Organization',
      'contact_sub_type_a' => $this->sponsor,
      'contact_type_b' => 'Individual',
    ];
    $relType = CRM_Contact_BAO_RelationshipType::add($relTypeParams);
    $params = [
      'relationship_type_id' => $relType->id . '_a_b',
      'contact_check' => [$this->individual => 1],
    ];
    $ids = ['contact' => $this->indivi_parent];

    list($valid, $invalid, $duplicate, $saved, $relationshipIds) = CRM_Contact_BAO_Relationship::legacyCreateMultiple($params, $ids);

    $this->assertEquals($invalid, 1);
    $this->assertEquals(empty($relationshipIds), TRUE);
    $this->relationshipTypeDelete($relType->id);
  }

  public function testRelCreateInvalidWithinDiffTypeStudentSponcor() {
    //check for Student to Sponcer
    $relTypeParams = [
      'name_a_b' => 'StudentToSponser',
      'name_b_a' => 'SponsorToStudent',
      'contact_type_a' => 'Individual',
      'contact_sub_type_a' => $this->student,
      'contact_type_b' => 'Organization',
      'contact_sub_type_b' => 'Sponser',
    ];
    $relType = CRM_Contact_BAO_RelationshipType::add($relTypeParams);
    $params = [
      'relationship_type_id' => $relType->id . '_a_b',
      'contact_check' => [$this->individual => 1],
    ];
    $ids = ['contact' => $this->indivi_parent];

    list($valid, $invalid, $duplicate, $saved, $relationshipIds) = CRM_Contact_BAO_Relationship::legacyCreateMultiple($params, $ids);

    $this->assertEquals($invalid, 1);
    $this->assertEquals(empty($relationshipIds), TRUE);
    $this->relationshipTypeDelete($relType->id);
  }

  /**
   * Methods create relationshipe within same contact type with valid data.
   * success expected
   */
  public function testRelationshipCreateWithinSameType() {
    //check for Individual to Parent
    $relTypeParams = [
      'name_a_b' => 'indivToparent',
      'name_b_a' => 'parentToindiv',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
      'contact_sub_type_b' => $this->parent,
    ];
    $relType = CRM_Contact_BAO_RelationshipType::add($relTypeParams);
    $params = [
      'relationship_type_id' => $relType->id . '_a_b',
      'is_active' => 1,
      'contact_check' => [$this->indivi_parent => $this->indivi_parent],
    ];
    $ids = ['contact' => $this->individual];
    list($valid, $invalid, $duplicate, $saved, $relationshipIds) = CRM_Contact_BAO_Relationship::legacyCreateMultiple($params, $ids);

    $this->assertEquals($valid, 1);
    $this->assertEquals(empty($relationshipIds), FALSE);
    $this->relationshipTypeDelete($relType->id);
  }

  /**
   * Methods create relationshipe within different contact type with valid data.
   * success expected
   */
  public function testRelCreateWithinDiffTypeSponsorIndivi() {
    //check for Sponcer to Individual
    $relTypeParams = [
      'name_a_b' => 'SponsorToIndiv',
      'name_b_a' => 'IndivToSponsor',
      'contact_type_a' => 'Organization',
      'contact_sub_type_a' => $this->sponsor,
      'contact_type_b' => 'Individual',
    ];
    $relType = CRM_Contact_BAO_RelationshipType::add($relTypeParams);
    $params = [
      'relationship_type_id' => $relType->id . '_a_b',
      'is_active' => 1,
      'contact_check' => [$this->indivi_student => 1],
    ];
    $ids = ['contact' => $this->organization_sponsor];
    list($valid, $invalid, $duplicate, $saved, $relationshipIds) = CRM_Contact_BAO_Relationship::legacyCreateMultiple($params, $ids);

    $this->assertEquals($valid, 1);
    $this->assertEquals(empty($relationshipIds), FALSE);
    $this->relationshipTypeDelete($relType->id);
  }

  public function testRelCreateWithinDiffTypeStudentSponsor() {
    //check for Student to Sponcer
    $relTypeParams = [
      'name_a_b' => 'StudentToSponsor',
      'name_b_a' => 'SponsorToStudent',
      'contact_type_a' => 'Individual',
      'contact_sub_type_a' => $this->student,
      'contact_type_b' => 'Organization',
      'contact_sub_type_b' => $this->sponsor,
    ];
    $relType = CRM_Contact_BAO_RelationshipType::add($relTypeParams);
    $params = [
      'relationship_type_id' => $relType->id . '_a_b',
      'is_active' => 1,
      'contact_check' => [$this->organization_sponsor => 1],
    ];
    $ids = ['contact' => $this->indivi_student];
    list($valid, $invalid, $duplicate, $saved, $relationshipIds) = CRM_Contact_BAO_Relationship::legacyCreateMultiple($params, $ids);

    $this->assertEquals($valid, 1);
    $this->assertEquals(empty($relationshipIds), FALSE);
    $this->relationshipTypeDelete($relType->id);
  }

  public function testGetAnyToAnyRelTypes() {
    // Create an any to any relationship.
    $relTypeParams = [
      'name_a_b' => 'MookieIs',
      'name_b_a' => 'MookieOf',
      'contact_type_a' => '',
      'contact_type_b' => '',
    ];
    $relType = CRM_Contact_BAO_RelationshipType::add($relTypeParams);
    $indTypes = CRM_Contact_BAO_Relationship::getRelationType('Individual');
    $orgTypes = CRM_Contact_BAO_Relationship::getRelationType('Organization');

    $this->assertContains('MookieIs', $indTypes);
    $this->assertContains('MookieIs', $orgTypes);
    $this->relationshipTypeDelete($relType->id);

  }

}
