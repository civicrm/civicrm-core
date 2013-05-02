<?php
require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';
class CRM_Contact_BAO_ContactType_ContactSearchTest extends CiviUnitTestCase {
  function get_info() {
    return array(
      'name' => 'Contact Serach Subtype',
      'description' => 'Test Contact for subtype.',
      'group' => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();
    $students = 'indivi_student'.substr(sha1(rand()), 0, 7);
    $params = array(
      'label' => $students,
      'name' => $students,
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->student = $params['name'];

    $parents = 'indivi_parent'.substr(sha1(rand()), 0, 7);
    $params = array(
      'label' => $parents,
      'name' => $parents,
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->parent = $params['name'];

    $orgs = 'org_sponsor'.substr(sha1(rand()), 0, 7);
    $params = array(
      'label' => $orgs,
      'name' => $orgs,
      // Organization
      'parent_id' => 3,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->sponsor = $params['name'];


    $this->indiviParams = array(
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
    );
    $this->individual = Contact::create($this->indiviParams);

    $this->indiviStudentParams = array(
      'first_name' => 'Bill',
      'last_name' => 'Adams',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->student,
    );
    $this->indiviStudent = Contact::create($this->indiviStudentParams);

    $this->indiviParentParams = array(
      'first_name' => 'Alen',
      'last_name' => 'Adams',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->parent,
    );
    $this->indiviParent = Contact::create($this->indiviParentParams);

    $this->organizationParams = array(
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
    );
    $this->organization = Contact::create($this->organizationParams);

    $this->orgSponsorParams = array(
      'organization_name' => 'Conservation Corp',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->sponsor,
    );
    $this->orgSponsor = Contact::create($this->orgSponsorParams);

    $this->householdParams = array(
      'household_name' => "John Doe's home",
      'contact_type' => 'Household',
    );
    $this->household = Contact::create($this->householdParams);
  }

  /*
     * search with only type
     * success expected.
     */
  function testSearchWithType() {

    /*
         * for type:Individual
         */

    $defaults = array();
    $params   = array('contact_type' => 'Individual', 'version' => 3);
    $result   = civicrm_api('contact', 'get', $params);

    $individual    = $result['values'][$this->individual];
    $indiviStudent = $result['values'][$this->indiviStudent];
    $indiviParent  = $result['values'][$this->indiviParent];

    //asserts for type:Individual
    $this->assertEquals($individual['contact_id'], $this->individual, 'In line ' . __LINE__);
    $this->assertEquals($individual['first_name'], $this->indiviParams['first_name'], 'In line ' . __LINE__);
    $this->assertEquals($individual['contact_type'], $this->indiviParams['contact_type'], 'In line ' . __LINE__);
    $this->assertNotContains('contact_sub_type', $individual);

    //asserts for type:Individual subtype:Student
    $this->assertEquals($indiviStudent['contact_id'], $this->indiviStudent, 'In line ' . __LINE__);
    $this->assertEquals($indiviStudent['first_name'], $this->indiviStudentParams['first_name'], 'In line ' . __LINE__);
    $this->assertEquals($indiviStudent['contact_type'], $this->indiviStudentParams['contact_type'], 'In line ' . __LINE__);
    $this->assertEquals(end($indiviStudent['contact_sub_type']), $this->indiviStudentParams['contact_sub_type'], 'In line ' . __LINE__);

    //asserts for type:Individual subtype:Parent
    $this->assertEquals($indiviParent['contact_id'], $this->indiviParent, 'In line ' . __LINE__);
    $this->assertEquals($indiviParent['first_name'], $this->indiviParentParams['first_name'], 'In line ' . __LINE__);
    $this->assertEquals($indiviParent['contact_type'], $this->indiviParentParams['contact_type'], 'In line ' . __LINE__);
    $this->assertEquals(end($indiviParent['contact_sub_type']), $this->indiviParentParams['contact_sub_type'], 'In line ' . __LINE__);

    /*
         * for type:Organization
         */

    $params = array('contact_type' => 'Organization', 'version' => 3);
    $result = civicrm_api('contact', 'get', $params);

    $organization = $result['values'][$this->organization];
    $orgSponsor = $result['values'][$this->orgSponsor];

    //asserts for type:Organization
    $this->assertEquals($organization['contact_id'], $this->organization, 'In line ' . __LINE__);
    $this->assertEquals($organization['organization_name'], $this->organizationParams['organization_name'], 'In line ' . __LINE__);
    $this->assertEquals($organization['contact_type'], $this->organizationParams['contact_type'], 'In line ' . __LINE__);
    $this->assertNotContains('contact_sub_type', $organization);

    //asserts for type:Organization subtype:Sponsor
    $this->assertEquals($orgSponsor['contact_id'], $this->orgSponsor, 'In line ' . __LINE__);
    $this->assertEquals($orgSponsor['organization_name'], $this->orgSponsorParams['organization_name'], 'In line ' . __LINE__);
    $this->assertEquals($orgSponsor['contact_type'], $this->orgSponsorParams['contact_type'], 'In line ' . __LINE__);
    $this->assertEquals(end($orgSponsor['contact_sub_type']), $this->orgSponsorParams['contact_sub_type'], 'In line ' . __LINE__);

    /*
         * for type:Household
         */

    $params = array('contact_type' => 'Household', 'version' => 3);
    $result = civicrm_api('contact', 'get', $params);

    $household = $result['values'][$this->household];

    //asserts for type:Household
    $this->assertEquals($household['contact_id'], $this->household, 'In line ' . __LINE__);
    $this->assertEquals($household['household_name'], $this->householdParams['household_name'], 'In line ' . __LINE__);
    $this->assertEquals($household['contact_type'], $this->householdParams['contact_type'], 'In line ' . __LINE__);
    $this->assertNotContains('contact_sub_type', $household);
  }

  /*
     * search with only subtype
     * success expected.
     */
  function testSearchWithSubype() {

    /*
         * for subtype:Student
         */

    $defaults = array();
    $params   = array('contact_sub_type' => $this->student, 'version' => 3);
    $result   = civicrm_api('contact', 'get', $params);

    $indiviStudent = $result['values'][$this->indiviStudent];

    //asserts for type:Individual subtype:Student
    $this->assertEquals($indiviStudent['contact_id'], $this->indiviStudent, 'In line ' . __LINE__);
    $this->assertEquals($indiviStudent['first_name'], $this->indiviStudentParams['first_name'], 'In line ' . __LINE__);
    $this->assertEquals($indiviStudent['contact_type'], $this->indiviStudentParams['contact_type'], 'In line ' . __LINE__);
    $this->assertEquals(end($indiviStudent['contact_sub_type']), $this->indiviStudentParams['contact_sub_type'], 'In line ' . __LINE__);

    //all other contact(rather than subtype:student) should not
    //exists
    $this->assertNotContains($this->individual, $result['values']);
    $this->assertNotContains($this->indiviParent, $result['values']);
    $this->assertNotContains($this->organization, $result['values']);
    $this->assertNotContains($this->orgSponsor, $result['values']);
    $this->assertNotContains($this->household, $result['values']);

    /*
         * for subtype:Sponsor
         */

    $params = array('contact_sub_type' => $this->sponsor, 'version' => 3);
    $result = civicrm_api('contact', 'get', $params);

    $orgSponsor = $result['values'][$this->orgSponsor];

    //asserts for type:Organization subtype:Sponsor
    $this->assertEquals($orgSponsor['contact_id'], $this->orgSponsor, 'In line ' . __LINE__);
    $this->assertEquals($orgSponsor['organization_name'], $this->orgSponsorParams['organization_name'], 'In line ' . __LINE__);
    $this->assertEquals($orgSponsor['contact_type'], $this->orgSponsorParams['contact_type'], 'In line ' . __LINE__);
    $this->assertEquals(end($orgSponsor['contact_sub_type']), $this->orgSponsorParams['contact_sub_type'], 'In line ' . __LINE__);

    //all other contact(rather than subtype:Sponsor) should not
    //exists
    $this->assertNotContains($this->individual, $result['values']);
    $this->assertNotContains($this->indiviStudent, $result['values']);
    $this->assertNotContains($this->indiviParent, $result['values']);
    $this->assertNotContains($this->organization, $result['values']);
    $this->assertNotContains($this->household, $result['values']);
  }

  /*
     * search with type as well as subtype
     * success expected.
     */
  function testSearchWithTypeSubype() {

    /*
         * for type:individual subtype:Student
         */

    $defaults = array();
    $params   = array('contact_sub_type' => $this->student, 'version' => 3);
    $result   = civicrm_api('contact', 'get', $params);

    $indiviStudent = $result['values'][$this->indiviStudent];

    //asserts for type:Individual subtype:Student
    $this->assertEquals($indiviStudent['contact_id'], $this->indiviStudent, 'In line ' . __LINE__);
    $this->assertEquals($indiviStudent['first_name'], $this->indiviStudentParams['first_name'], 'In line ' . __LINE__);
    $this->assertEquals($indiviStudent['contact_type'], $this->indiviStudentParams['contact_type'], 'In line ' . __LINE__);
    $this->assertEquals(end($indiviStudent['contact_sub_type']), $this->indiviStudentParams['contact_sub_type'], 'In line ' . __LINE__);

    //all other contact(rather than subtype:student) should not
    //exists
    $this->assertNotContains($this->individual, $result['values']);
    $this->assertNotContains($this->indiviParent, $result['values']);
    $this->assertNotContains($this->organization, $result['values']);
    $this->assertNotContains($this->orgSponsor, $result['values']);
    $this->assertNotContains($this->household, $result['values']);

    /*
         * for type:Organization subtype:Sponsor
         */

    $params = array('contact_sub_type' => $this->sponsor, 'version' => 3);
    $result = civicrm_api('contact', 'get', $params);

    $orgSponsor = $result['values'][$this->orgSponsor];

    //asserts for type:Organization subtype:Sponsor
    $this->assertEquals($orgSponsor['contact_id'], $this->orgSponsor, 'In line ' . __LINE__);
    $this->assertEquals($orgSponsor['organization_name'], $this->orgSponsorParams['organization_name'], 'In line ' . __LINE__);
    $this->assertEquals($orgSponsor['contact_type'], $this->orgSponsorParams['contact_type'], 'In line ' . __LINE__);
    $this->assertEquals(end($orgSponsor['contact_sub_type']), $this->orgSponsorParams['contact_sub_type'], 'In line ' . __LINE__);

    //all other contact(rather than subtype:Sponsor) should not
    //exists
    $this->assertNotContains($this->individual, $result['values']);
    $this->assertNotContains($this->indiviStudent, $result['values']);
    $this->assertNotContains($this->indiviParent, $result['values']);
    $this->assertNotContains($this->organization, $result['values']);
    $this->assertNotContains($this->household, $result['values']);
  }

  /*
     * search with invalid type or subtype
     */
  function testSearchWithInvalidData() {

    // for invalid type
    $defaults = array();
    $params   = array('contact_type' => 'Invalid' . CRM_Core_DAO::VALUE_SEPARATOR . 'Invalid', 'version' => 3);
    $result   = civicrm_api('contact', 'get', $params);
    $this->assertEquals(empty($result['values']), TRUE, 'In line ' . __LINE__);


    // for invalid subtype
    $params = array('contact_sub_type' => 'Invalid', 'version' => 3);
    $result = civicrm_api('contact', 'get', $params);
    $this->assertEquals(empty($result['values']), TRUE, 'In line ' . __LINE__);


    // for invalid contact type as well as subtype
    $params = array('contact_type' => 'Invalid' . CRM_Core_DAO::VALUE_SEPARATOR . 'Invalid', 'version' => 3);
    $result = civicrm_api('contact', 'get', $params);
    $this->assertEquals(empty($result['values']), TRUE, 'In line ' . __LINE__);


    // for valid type and invalid subtype
    $params = array('contact_type' => 'Individual' . CRM_Core_DAO::VALUE_SEPARATOR . 'Invalid', 'version' => 3);
    $result = civicrm_api('contact', 'get', $params);
    $this->assertEquals(empty($result['values']), TRUE, 'In line ' . __LINE__);


    // for invalid type and valid subtype
    $params = array('contact_type' => 'Invalid' . CRM_Core_DAO::VALUE_SEPARATOR . 'indivi_student', 'version' => 3);
    $result = civicrm_api('contact', 'get', $params);
    $this->assertEquals(empty($result['values']), TRUE, 'In line ' . __LINE__);
  }

  /* search with wrong type or subtype
     *
     */
  function testSearchWithWrongdData() {

    // for type:Individual subtype:Sponsor
    $defaults = array();
    $params   = array('contact_type' => 'Individual' . CRM_Core_DAO::VALUE_SEPARATOR . $this->sponsor, 'version' => 3);
    $result   = civicrm_api('contact', 'get', $params);
    $this->assertEquals(empty($result['values']), TRUE, 'In line ' . __LINE__);

    // for type:Orgaization subtype:Parent
    $params = array('contact_type' => 'Orgaization' . CRM_Core_DAO::VALUE_SEPARATOR . $this->parent, 'version' => 3);
    $result = civicrm_api('contact', 'get', $params, $defaults);
    $this->assertEquals(empty($result['values']), TRUE, 'In line ' . __LINE__);


    // for type:Household subtype:Sponsor
    $params = array('contact_type' => 'Household' . CRM_Core_DAO::VALUE_SEPARATOR . $this->sponsor, 'version' => 3);
    $result = civicrm_api('contact', 'get', $params, $defaults);
    $this->assertEquals(empty($result['values']), TRUE, 'In line ' . __LINE__);


    // for type:Household subtype:Student
    $params = array('contact_type' => 'Household' . CRM_Core_DAO::VALUE_SEPARATOR . $this->student, 'version' => 3);
    $result = civicrm_api('contact', 'get', $params, $defaults);
    $this->assertEquals(empty($result['values']), TRUE, 'In line ' . __LINE__);
  }
}



