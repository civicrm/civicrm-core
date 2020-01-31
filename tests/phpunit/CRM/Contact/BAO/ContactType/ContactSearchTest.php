<?php

/**
 * Class CRM_Contact_BAO_ContactType_ContactSearchTest
 * @group headless
 */
class CRM_Contact_BAO_ContactType_ContactSearchTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $students = 'indivi_student' . substr(sha1(rand()), 0, 7);
    $params = [
      'label' => $students,
      'name' => $students,
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    ];
    CRM_Contact_BAO_ContactType::add($params);
    $this->student = $params['name'];

    $parents = 'indivi_parent' . substr(sha1(rand()), 0, 7);
    $params = [
      'label' => $parents,
      'name' => $parents,
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    ];
    CRM_Contact_BAO_ContactType::add($params);
    $this->parent = $params['name'];

    $orgs = 'org_sponsor' . substr(sha1(rand()), 0, 7);
    $params = [
      'label' => $orgs,
      'name' => $orgs,
      // Organization
      'parent_id' => 3,
      'is_active' => 1,
    ];
    CRM_Contact_BAO_ContactType::add($params);
    $this->sponsor = $params['name'];

    $this->indiviParams = [
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
    ];
    $this->individual = $this->individualCreate($this->indiviParams);

    $this->individualStudentParams = [
      'first_name' => 'Bill',
      'last_name' => 'Adams',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->student,
    ];
    $this->individualStudent = $this->individualCreate($this->individualStudentParams);

    $this->indiviParentParams = [
      'first_name' => 'Alen',
      'last_name' => 'Adams',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->parent,
    ];
    $this->indiviParent = $this->individualCreate($this->indiviParentParams);

    $this->organizationParams = [
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
    ];
    $this->organization = $this->organizationCreate($this->organizationParams);

    $this->orgSponsorParams = [
      'organization_name' => 'Conservation Corp',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->sponsor,
    ];
    $this->orgSponsor = $this->organizationCreate($this->orgSponsorParams);

    $this->householdParams = [
      'household_name' => "John Doe's home",
      'contact_type' => 'Household',
    ];
    $this->household = $this->householdCreate($this->householdParams);
  }

  /**
   * Search with only type.
   *
   * Success expected.
   */
  public function testSearchWithType() {

    // for type:Individual
    $params = ['contact_type' => 'Individual', 'version' => 3];
    $result = civicrm_api('contact', 'get', $params);

    $individual = $result['values'][$this->individual];
    $individualStudent = $result['values'][$this->individualStudent];
    $indiviParent = $result['values'][$this->indiviParent];

    //asserts for type:Individual
    $this->assertEquals($individual['contact_id'], $this->individual);
    $this->assertEquals($individual['first_name'], $this->indiviParams['first_name']);
    $this->assertEquals($individual['contact_type'], $this->indiviParams['contact_type']);
    $this->assertNotContains('contact_sub_type', $individual);

    //asserts for type:Individual subtype:Student
    $this->assertEquals($individualStudent['contact_id'], $this->individualStudent);
    $this->assertEquals($individualStudent['first_name'], $this->individualStudentParams['first_name']);
    $this->assertEquals($individualStudent['contact_type'], $this->individualStudentParams['contact_type']);
    $this->assertEquals(end($individualStudent['contact_sub_type']), $this->individualStudentParams['contact_sub_type']);

    //asserts for type:Individual subtype:Parent
    $this->assertEquals($indiviParent['contact_id'], $this->indiviParent);
    $this->assertEquals($indiviParent['first_name'], $this->indiviParentParams['first_name']);
    $this->assertEquals($indiviParent['contact_type'], $this->indiviParentParams['contact_type']);
    $this->assertEquals(end($indiviParent['contact_sub_type']), $this->indiviParentParams['contact_sub_type']);

    // for type:Organization
    $params = ['contact_type' => 'Organization', 'version' => 3];
    $result = civicrm_api('contact', 'get', $params);

    $organization = $result['values'][$this->organization];
    $orgSponsor = $result['values'][$this->orgSponsor];

    //asserts for type:Organization
    $this->assertEquals($organization['contact_id'], $this->organization);
    $this->assertEquals($organization['organization_name'], $this->organizationParams['organization_name']);
    $this->assertEquals($organization['contact_type'], $this->organizationParams['contact_type']);
    $this->assertNotContains('contact_sub_type', $organization);

    //asserts for type:Organization subtype:Sponsor
    $this->assertEquals($orgSponsor['contact_id'], $this->orgSponsor);
    $this->assertEquals($orgSponsor['organization_name'], $this->orgSponsorParams['organization_name']);
    $this->assertEquals($orgSponsor['contact_type'], $this->orgSponsorParams['contact_type']);
    $this->assertEquals(end($orgSponsor['contact_sub_type']), $this->orgSponsorParams['contact_sub_type']);

    // for type:Household
    $params = ['contact_type' => 'Household', 'version' => 3];
    $result = civicrm_api('contact', 'get', $params);

    $household = $result['values'][$this->household];

    //asserts for type:Household
    $this->assertEquals($household['contact_id'], $this->household);
    $this->assertEquals($household['household_name'], $this->householdParams['household_name']);
    $this->assertEquals($household['contact_type'], $this->householdParams['contact_type']);
    $this->assertNotContains('contact_sub_type', $household);
  }

  /**
   * Search with only subtype.
   *
   * Success expected.
   */
  public function testSearchWithSubype() {

    // for subtype:Student
    $params = ['contact_sub_type' => $this->student, 'version' => 3];
    $result = civicrm_api('contact', 'get', $params);

    $individualStudent = $result['values'][$this->individualStudent];

    //asserts for type:Individual subtype:Student
    $this->assertEquals($individualStudent['contact_id'], $this->individualStudent);
    $this->assertEquals($individualStudent['first_name'], $this->individualStudentParams['first_name']);
    $this->assertEquals($individualStudent['contact_type'], $this->individualStudentParams['contact_type']);
    $this->assertEquals(end($individualStudent['contact_sub_type']), $this->individualStudentParams['contact_sub_type']);

    //all other contact(rather than subtype:student) should not
    //exists
    $this->assertNotContains($this->individual, $result['values']);
    $this->assertNotContains($this->indiviParent, $result['values']);
    $this->assertNotContains($this->organization, $result['values']);
    $this->assertNotContains($this->orgSponsor, $result['values']);
    $this->assertNotContains($this->household, $result['values']);

    // for subtype:Sponsor
    $params = ['contact_sub_type' => $this->sponsor, 'version' => 3];
    $result = civicrm_api('contact', 'get', $params);

    $orgSponsor = $result['values'][$this->orgSponsor];

    //asserts for type:Organization subtype:Sponsor
    $this->assertEquals($orgSponsor['contact_id'], $this->orgSponsor);
    $this->assertEquals($orgSponsor['organization_name'], $this->orgSponsorParams['organization_name']);
    $this->assertEquals($orgSponsor['contact_type'], $this->orgSponsorParams['contact_type']);
    $this->assertEquals(end($orgSponsor['contact_sub_type']), $this->orgSponsorParams['contact_sub_type']);

    //all other contact(rather than subtype:Sponsor) should not
    //exists
    $this->assertNotContains($this->individual, $result['values']);
    $this->assertNotContains($this->individualStudent, $result['values']);
    $this->assertNotContains($this->indiviParent, $result['values']);
    $this->assertNotContains($this->organization, $result['values']);
    $this->assertNotContains($this->household, $result['values']);
  }

  /**
   * Search with type as well as subtype.
   *
   * Success expected.
   */
  public function testSearchWithTypeSubype() {

    // for type:individual subtype:Student
    $params = ['contact_sub_type' => $this->student, 'version' => 3];
    $result = civicrm_api('contact', 'get', $params);

    $individualStudent = $result['values'][$this->individualStudent];

    //asserts for type:Individual subtype:Student
    $this->assertEquals($individualStudent['contact_id'], $this->individualStudent);
    $this->assertEquals($individualStudent['first_name'], $this->individualStudentParams['first_name']);
    $this->assertEquals($individualStudent['contact_type'], $this->individualStudentParams['contact_type']);
    $this->assertEquals(end($individualStudent['contact_sub_type']), $this->individualStudentParams['contact_sub_type']);

    //all other contact(rather than subtype:student) should not
    //exists
    $this->assertNotContains($this->individual, $result['values']);
    $this->assertNotContains($this->indiviParent, $result['values']);
    $this->assertNotContains($this->organization, $result['values']);
    $this->assertNotContains($this->orgSponsor, $result['values']);
    $this->assertNotContains($this->household, $result['values']);

    // for type:Organization subtype:Sponsor
    $params = ['contact_sub_type' => $this->sponsor, 'version' => 3];
    $result = civicrm_api('contact', 'get', $params);

    $orgSponsor = $result['values'][$this->orgSponsor];

    //asserts for type:Organization subtype:Sponsor
    $this->assertEquals($orgSponsor['contact_id'], $this->orgSponsor);
    $this->assertEquals($orgSponsor['organization_name'], $this->orgSponsorParams['organization_name']);
    $this->assertEquals($orgSponsor['contact_type'], $this->orgSponsorParams['contact_type']);
    $this->assertEquals(end($orgSponsor['contact_sub_type']), $this->orgSponsorParams['contact_sub_type']);

    //all other contact(rather than subtype:Sponsor) should not
    //exists
    $this->assertNotContains($this->individual, $result['values']);
    $this->assertNotContains($this->individualStudent, $result['values']);
    $this->assertNotContains($this->indiviParent, $result['values']);
    $this->assertNotContains($this->organization, $result['values']);
    $this->assertNotContains($this->household, $result['values']);
  }

  /**
   * Search with invalid type or subtype.
   */
  public function testSearchWithInvalidData() {
    // for invalid type
    $params = [
      'contact_type' => 'Invalid' . CRM_Core_DAO::VALUE_SEPARATOR . 'Invalid',
      'version' => 3,
    ];
    $result = civicrm_api('contact', 'get', $params);
    $this->assertEquals(empty($result['values']), TRUE);

    // for invalid subtype
    $params = ['contact_sub_type' => 'Invalid', 'version' => 3];
    $result = civicrm_api('contact', 'get', $params);
    $this->assertEquals(empty($result['values']), TRUE);

    // for invalid contact type as well as subtype
    $params = [
      'contact_type' => 'Invalid' . CRM_Core_DAO::VALUE_SEPARATOR . 'Invalid',
      'version' => 3,
    ];
    $result = civicrm_api('contact', 'get', $params);
    $this->assertEquals(empty($result['values']), TRUE);

    // for valid type and invalid subtype
    $params = [
      'contact_type' => 'Individual' . CRM_Core_DAO::VALUE_SEPARATOR . 'Invalid',
      'version' => 3,
    ];
    $result = civicrm_api('contact', 'get', $params);
    $this->assertEquals(empty($result['values']), TRUE);

    // for invalid type and valid subtype
    $params = [
      'contact_type' => 'Invalid' . CRM_Core_DAO::VALUE_SEPARATOR . 'indivi_student',
      'version' => 3,
    ];
    $result = civicrm_api('contact', 'get', $params);
    $this->assertEquals(empty($result['values']), TRUE);
  }

  /**
   * Search with wrong type or subtype.
   */
  public function testSearchWithWrongdData() {

    // for type:Individual subtype:Sponsor
    $defaults = [];
    $params = [
      'contact_type' => 'Individual' . CRM_Core_DAO::VALUE_SEPARATOR . $this->sponsor,
      'version' => 3,
    ];
    $result = civicrm_api('contact', 'get', $params);
    $this->assertEquals(empty($result['values']), TRUE);

    // for type:Orgaization subtype:Parent
    $params = [
      'contact_type' => 'Orgaization' . CRM_Core_DAO::VALUE_SEPARATOR . $this->parent,
      'version' => 3,
    ];
    $result = civicrm_api('contact', 'get', $params, $defaults);
    $this->assertEquals(empty($result['values']), TRUE);

    // for type:Household subtype:Sponsor
    $params = [
      'contact_type' => 'Household' . CRM_Core_DAO::VALUE_SEPARATOR . $this->sponsor,
      'version' => 3,
    ];
    $result = civicrm_api('contact', 'get', $params, $defaults);
    $this->assertEquals(empty($result['values']), TRUE);

    // for type:Household subtype:Student
    $params = [
      'contact_type' => 'Household' . CRM_Core_DAO::VALUE_SEPARATOR . $this->student,
      'version' => 3,
    ];
    $result = civicrm_api('contact', 'get', $params, $defaults);
    $this->assertEquals(empty($result['values']), TRUE);
  }

}
