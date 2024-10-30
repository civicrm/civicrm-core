<?php

/**
 * Class CRM_Contact_BAO_ContactType_ContactSearchTest
 * @group headless
 */
class CRM_Contact_BAO_ContactType_ContactSearchTest extends CiviUnitTestCase {

  /**
   * @var string
   */
  private $student;

  /**
   * @var string
   */
  private $parent;

  /**
   * @var string
   */
  private $sponsor;

  /**
   * @var array
   */
  private $individualParams;

  /**
   * @var string
   */
  private $individual;

  /**
   * @var array
   */
  private $individualParentParams;

  /**
   * @var string
   */
  private $individualParent;

  /**
   * @var array
   */
  private $individualStudentParams;

  /**
   * @var string
   */
  private $individualStudent;

  /**
   * @var array
   */
  private $organizationSponsorParams;

  /**
   * @var string
   */
  private $organizationSponsor;

  /**
   * @var array
   */
  private $organizationParams;

  /**
   * @var string
   */
  private $organization;

  /**
   * @var array
   */
  private $householdParams;

  /**
   * @var string
   */
  private $household;

  public function setUp(): void {
    parent::setUp();
    $students = 'indivi_student' . substr(sha1(rand()), 0, 7);
    $params = [
      'label' => $students,
      'name' => $students,
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    ];
    CRM_Contact_BAO_ContactType::writeRecord($params);
    $this->student = $params['name'];

    $parents = 'indivi_parent' . substr(sha1(rand()), 0, 7);
    $params = [
      'label' => $parents,
      'name' => $parents,
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    ];
    CRM_Contact_BAO_ContactType::writeRecord($params);
    $this->parent = $params['name'];

    $organizations = 'org_sponsor' . substr(sha1(rand()), 0, 7);
    $params = [
      'label' => $organizations,
      'name' => $organizations,
      // Organization
      'parent_id' => 3,
      'is_active' => 1,
    ];
    CRM_Contact_BAO_ContactType::writeRecord($params);
    $this->sponsor = $params['name'];

    $this->individualParams = [
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
    ];
    $this->individual = $this->individualCreate($this->individualParams);

    $this->individualStudentParams = [
      'first_name' => 'Bill',
      'last_name' => 'Adams',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->student,
    ];
    $this->individualStudent = $this->individualCreate($this->individualStudentParams);

    $this->individualParentParams = [
      'first_name' => 'Alen',
      'last_name' => 'Adams',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->parent,
    ];
    $this->individualParent = $this->individualCreate($this->individualParentParams);

    $this->organizationParams = [
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
    ];
    $this->organization = $this->organizationCreate($this->organizationParams);

    $this->organizationSponsorParams = [
      'organization_name' => 'Conservation Corp',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->sponsor,
    ];
    $this->organizationSponsor = $this->organizationCreate($this->organizationSponsorParams);

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
  public function testSearchWithType(): void {

    // for type:Individual
    $params = ['contact_type' => 'Individual', 'version' => 3];
    $result = civicrm_api('contact', 'get', $params);

    $individual = $result['values'][$this->individual];
    $individualStudent = $result['values'][$this->individualStudent];
    $individualParent = $result['values'][$this->individualParent];

    //asserts for type:Individual
    $this->assertEquals($individual['contact_id'], $this->individual);
    $this->assertEquals($individual['first_name'], $this->individualParams['first_name']);
    $this->assertEquals($individual['contact_type'], $this->individualParams['contact_type']);
    $this->assertNotContains('contact_sub_type', $individual);

    //asserts for type:Individual subtype:Student
    $this->assertEquals($individualStudent['contact_id'], $this->individualStudent);
    $this->assertEquals($individualStudent['first_name'], $this->individualStudentParams['first_name']);
    $this->assertEquals($individualStudent['contact_type'], $this->individualStudentParams['contact_type']);
    $this->assertEquals(end($individualStudent['contact_sub_type']), $this->individualStudentParams['contact_sub_type']);

    //asserts for type:Individual subtype:Parent
    $this->assertEquals($individualParent['contact_id'], $this->individualParent);
    $this->assertEquals($individualParent['first_name'], $this->individualParentParams['first_name']);
    $this->assertEquals($individualParent['contact_type'], $this->individualParentParams['contact_type']);
    $this->assertEquals(end($individualParent['contact_sub_type']), $this->individualParentParams['contact_sub_type']);

    // for type:Organization
    $params = ['contact_type' => 'Organization', 'version' => 3];
    $result = civicrm_api('contact', 'get', $params);

    $organization = $result['values'][$this->organization];
    $organizationSponsor = $result['values'][$this->organizationSponsor];

    //asserts for type:Organization
    $this->assertEquals($organization['contact_id'], $this->organization);
    $this->assertEquals($organization['organization_name'], $this->organizationParams['organization_name']);
    $this->assertEquals($organization['contact_type'], $this->organizationParams['contact_type']);
    $this->assertNotContains('contact_sub_type', $organization);

    //asserts for type:Organization subtype:Sponsor
    $this->assertEquals($organizationSponsor['contact_id'], $this->organizationSponsor);
    $this->assertEquals($organizationSponsor['organization_name'], $this->organizationSponsorParams['organization_name']);
    $this->assertEquals($organizationSponsor['contact_type'], $this->organizationSponsorParams['contact_type']);
    $this->assertEquals(end($organizationSponsor['contact_sub_type']), $this->organizationSponsorParams['contact_sub_type']);

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
  public function testSearchWithSubype(): void {

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
    $this->assertNotContains($this->individualParent, $result['values']);
    $this->assertNotContains($this->organization, $result['values']);
    $this->assertNotContains($this->organizationSponsor, $result['values']);
    $this->assertNotContains($this->household, $result['values']);

    // for subtype:Sponsor
    $params = ['contact_sub_type' => $this->sponsor, 'version' => 3];
    $result = civicrm_api('contact', 'get', $params);

    $organizationSponsor = $result['values'][$this->organizationSponsor];

    //asserts for type:Organization subtype:Sponsor
    $this->assertEquals($organizationSponsor['contact_id'], $this->organizationSponsor);
    $this->assertEquals($organizationSponsor['organization_name'], $this->organizationSponsorParams['organization_name']);
    $this->assertEquals($organizationSponsor['contact_type'], $this->organizationSponsorParams['contact_type']);
    $this->assertEquals(end($organizationSponsor['contact_sub_type']), $this->organizationSponsorParams['contact_sub_type']);

    //all other contact(rather than subtype:Sponsor) should not
    //exists
    $this->assertNotContains($this->individual, $result['values']);
    $this->assertNotContains($this->individualStudent, $result['values']);
    $this->assertNotContains($this->individualParent, $result['values']);
    $this->assertNotContains($this->organization, $result['values']);
    $this->assertNotContains($this->household, $result['values']);
  }

  /**
   * Search with type as well as subtype.
   *
   * Success expected.
   */
  public function testSearchWithTypeSubype(): void {

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
    $this->assertNotContains($this->individualParent, $result['values']);
    $this->assertNotContains($this->organization, $result['values']);
    $this->assertNotContains($this->organizationSponsor, $result['values']);
    $this->assertNotContains($this->household, $result['values']);

    // for type:Organization subtype:Sponsor
    $params = ['contact_sub_type' => $this->sponsor, 'version' => 3];
    $result = civicrm_api('contact', 'get', $params);

    $organizationSponsor = $result['values'][$this->organizationSponsor];

    //asserts for type:Organization subtype:Sponsor
    $this->assertEquals($organizationSponsor['contact_id'], $this->organizationSponsor);
    $this->assertEquals($organizationSponsor['organization_name'], $this->organizationSponsorParams['organization_name']);
    $this->assertEquals($organizationSponsor['contact_type'], $this->organizationSponsorParams['contact_type']);
    $this->assertEquals(end($organizationSponsor['contact_sub_type']), $this->organizationSponsorParams['contact_sub_type']);

    //all other contact(rather than subtype:Sponsor) should not
    //exists
    $this->assertNotContains($this->individual, $result['values']);
    $this->assertNotContains($this->individualStudent, $result['values']);
    $this->assertNotContains($this->individualParent, $result['values']);
    $this->assertNotContains($this->organization, $result['values']);
    $this->assertNotContains($this->household, $result['values']);
  }

}
