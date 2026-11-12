<?php

use Civi\Api4\Contact;

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
    $students = 'indivi_student' . bin2hex(random_bytes(4));
    $params = [
      'label' => $students,
      'name' => $students,
      // Individual
      'parent_id' => $this->getContactTypeID('Individual'),
      'is_active' => 1,
    ];
    CRM_Contact_BAO_ContactType::writeRecord($params);
    $this->student = $params['name'];

    $parents = 'indivi_parent' . bin2hex(random_bytes(4));
    $params = [
      'label' => $parents,
      'name' => $parents,
      // Individual
      'parent_id' => $this->getContactTypeID('Individual'),
      'is_active' => 1,
    ];
    CRM_Contact_BAO_ContactType::writeRecord($params);
    $this->parent = $params['name'];

    $organizations = 'org_sponsor' . bin2hex(random_bytes(4));
    $params = [
      'label' => $organizations,
      'name' => $organizations,
      // Organization
      'parent_id' => $this->getContactTypeID('Organization'),
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
    $result = Contact::get(FALSE)->addWhere('contact_type', '=', 'Individual')->execute()->indexBy('id');

    $individual = $result[$this->individual];
    $individualStudent = $result[$this->individualStudent];
    $individualParent = $result[$this->individualParent];

    //asserts for type:Individual
    $this->assertEquals($this->individual, $individual['id']);
    $this->assertEquals($this->individualParams['first_name'], $individual['first_name']);
    $this->assertEquals($this->individualParams['contact_type'], $individual['contact_type']);
    $this->assertEmpty($individual['contact_sub_type']);

    //asserts for type:Individual subtype:Student
    $this->assertEquals($this->individualStudent, $individualStudent['id']);
    $this->assertEquals($this->individualStudentParams['first_name'], $individualStudent['first_name']);
    $this->assertEquals($this->individualStudentParams['contact_type'], $individualStudent['contact_type']);
    $this->assertEquals($this->individualStudentParams['contact_sub_type'], end($individualStudent['contact_sub_type']));

    //asserts for type:Individual subtype:Parent
    $this->assertEquals($this->individualParent, $individualParent['id']);
    $this->assertEquals($this->individualParentParams['first_name'], $individualParent['first_name']);
    $this->assertEquals($this->individualParentParams['contact_type'], $individualParent['contact_type']);
    $this->assertEquals($this->individualParentParams['contact_sub_type'], end($individualParent['contact_sub_type']));

    // for type:Organization
    $result = Contact::get(FALSE)->addWhere('contact_type', '=', 'Organization')->execute()->indexBy('id');

    $organization = $result[$this->organization];
    $organizationSponsor = $result[$this->organizationSponsor];

    //asserts for type:Organization
    $this->assertEquals($this->organization, $organization['id']);
    $this->assertEquals($this->organizationParams['organization_name'], $organization['organization_name']);
    $this->assertEquals($this->organizationParams['contact_type'], $organization['contact_type']);
    $this->assertEmpty($organization['contact_sub_type']);

    //asserts for type:Organization subtype:Sponsor
    $this->assertEquals($this->organizationSponsor, $organizationSponsor['id']);
    $this->assertEquals($this->organizationSponsorParams['organization_name'], $organizationSponsor['organization_name']);
    $this->assertEquals($this->organizationSponsorParams['contact_type'], $organizationSponsor['contact_type']);
    $this->assertEquals($this->organizationSponsorParams['contact_sub_type'], end($organizationSponsor['contact_sub_type']));

    // for type:Household
    $result = Contact::get(FALSE)->addWhere('contact_type', '=', 'Household')->execute()->indexBy('id');

    $household = $result[$this->household];

    //asserts for type:Household
    $this->assertEquals($this->household, $household['id']);
    $this->assertEquals($this->householdParams['household_name'], $household['household_name']);
    $this->assertEquals($this->householdParams['contact_type'], $household['contact_type']);
    $this->assertEmpty($household['contact_sub_type']);
  }

  /**
   * Search with only subtype.
   *
   * Success expected.
   */
  public function testSearchWithSubype(): void {

    // for subtype:Student
    $result = Contact::get(FALSE)->addWhere('contact_sub_type', 'CONTAINS', $this->student)->execute()->indexBy('id');

    $individualStudent = $result[$this->individualStudent];

    //asserts for type:Individual subtype:Student
    $this->assertEquals($this->individualStudent, $individualStudent['id']);
    $this->assertEquals($this->individualStudentParams['first_name'], $individualStudent['first_name']);
    $this->assertEquals($this->individualStudentParams['contact_type'], $individualStudent['contact_type']);
    $this->assertEquals($this->individualStudentParams['contact_sub_type'], end($individualStudent['contact_sub_type']));

    //all other contact(rather than subtype:student) should not
    //exists
    $this->assertArrayNotHasKey($this->individual, $result);
    $this->assertArrayNotHasKey($this->individualParent, $result);
    $this->assertArrayNotHasKey($this->organization, $result);
    $this->assertArrayNotHasKey($this->organizationSponsor, $result);
    $this->assertArrayNotHasKey($this->household, $result);

    // for subtype:Sponsor
    $result = Contact::get(FALSE)->addWhere('contact_sub_type', 'CONTAINS', $this->sponsor)->execute()->indexBy('id');

    $organizationSponsor = $result[$this->organizationSponsor];

    //asserts for type:Organization subtype:Sponsor
    $this->assertEquals($this->organizationSponsor, $organizationSponsor['id']);
    $this->assertEquals($this->organizationSponsorParams['organization_name'], $organizationSponsor['organization_name']);
    $this->assertEquals($this->organizationSponsorParams['contact_type'], $organizationSponsor['contact_type']);
    $this->assertEquals($this->organizationSponsorParams['contact_sub_type'], end($organizationSponsor['contact_sub_type']));

    //all other contact(rather than subtype:Sponsor) should not
    //exists
    $this->assertArrayNotHasKey($this->individual, $result);
    $this->assertArrayNotHasKey($this->individualStudent, $result);
    $this->assertArrayNotHasKey($this->individualParent, $result);
    $this->assertArrayNotHasKey($this->organization, $result);
    $this->assertArrayNotHasKey($this->household, $result);
  }

  /**
   * Search with type as well as subtype.
   *
   * Success expected.
   */
  public function testSearchWithTypeSubype(): void {

    // for type:individual subtype:Student
    $result = Contact::get(FALSE)
      ->addWhere('contact_type', '=', 'Individual')
      ->addWhere('contact_sub_type', 'CONTAINS', $this->student)
      ->execute()->indexBy('id');

    $individualStudent = $result[$this->individualStudent];

    //asserts for type:Individual subtype:Student
    $this->assertEquals($this->individualStudent, $individualStudent['id']);
    $this->assertEquals($this->individualStudentParams['first_name'], $individualStudent['first_name']);
    $this->assertEquals($this->individualStudentParams['contact_type'], $individualStudent['contact_type']);
    $this->assertEquals($this->individualStudentParams['contact_sub_type'], end($individualStudent['contact_sub_type']));

    //all other contact(rather than subtype:student) should not
    //exists
    $this->assertArrayNotHasKey($this->individual, $result);
    $this->assertArrayNotHasKey($this->individualParent, $result);
    $this->assertArrayNotHasKey($this->organization, $result);
    $this->assertArrayNotHasKey($this->organizationSponsor, $result);
    $this->assertArrayNotHasKey($this->household, $result);

    // for type:Organization subtype:Sponsor
    $result = Contact::get(FALSE)
      ->addWhere('contact_type', '=', 'Organization')
      ->addWhere('contact_sub_type', 'CONTAINS', $this->sponsor)
      ->execute()->indexBy('id');

    $organizationSponsor = $result[$this->organizationSponsor];

    //asserts for type:Organization subtype:Sponsor
    $this->assertEquals($this->organizationSponsor, $organizationSponsor['id']);
    $this->assertEquals($this->organizationSponsorParams['organization_name'], $organizationSponsor['organization_name']);
    $this->assertEquals($this->organizationSponsorParams['contact_type'], $organizationSponsor['contact_type']);
    $this->assertEquals($this->organizationSponsorParams['contact_sub_type'], end($organizationSponsor['contact_sub_type']));

    //all other contact(rather than subtype:Sponsor) should not
    //exists
    $this->assertArrayNotHasKey($this->individual, $result);
    $this->assertArrayNotHasKey($this->individualStudent, $result);
    $this->assertArrayNotHasKey($this->individualParent, $result);
    $this->assertArrayNotHasKey($this->organization, $result);
    $this->assertArrayNotHasKey($this->household, $result);
  }

}
