<?php

/**
 * Class CRM_Contact_BAO_ContactType_ContactTest
 * @group headless
 */
class CRM_Contact_BAO_ContactType_ContactTest extends CiviUnitTestCase {

  /**
   * @var string
   */
  protected $student;

  /**
   * @var string
   */
  protected $parent;

  /**
   * @var string
   */
  protected $sponsor;

  /**
   * @var string
   */
  protected $team;

  public function setUp(): void {
    parent::setUp();

    $params = [
      'label' => 'indiv_student',
      'name' => 'indiv_student',
      // Individual
      'parent_id' => $this->getContactTypeID('Individual'),
      'is_active' => 1,
    ];
    CRM_Contact_BAO_ContactType::writeRecord($params);
    $this->student = $params['name'];

    $params = [
      'label' => 'indiv_parent',
      'name' => 'indiv_parent',
      // Individual
      'parent_id' => $this->getContactTypeID('Individual'),
      'is_active' => 1,
    ];
    CRM_Contact_BAO_ContactType::writeRecord($params);
    $this->parent = $params['name'];

    $params = [
      'label' => 'org_sponsor',
      'name' => 'org_sponsor',
      // Organization
      'parent_id' => $this->getContactTypeID('Organization'),
      'is_active' => 1,
    ];
    CRM_Contact_BAO_ContactType::writeRecord($params);
    $this->sponsor = $params['name'];

    $params = [
      'label' => 'org_team',
      'name' => 'org_team',
      // Organization
      'parent_id' => $this->getContactTypeID('Organization'),
      'is_active' => 1,
    ];
    CRM_Contact_BAO_ContactType::writeRecord($params);
    $this->team = $params['name'];
  }

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_contact']);
    $query = "
DELETE FROM civicrm_contact_type
      WHERE name IN ('{$this->student}','{$this->parent}','{$this->sponsor}', '{$this->team}');";
    CRM_Core_DAO::executeQuery($query);
    parent::tearDown();
  }

  /**
   * Methods create Contact with valid data.
   *
   * Success expected
   */
  public function testCreateContact(): void {
    //check for Type:Individual
    $params = [
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
    ];
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception $expected) {
    }
    $this->assertSame('Anne', $contact->first_name);
    $this->assertSame('Individual', $contact->contact_type);
    $this->contactDelete($contact->id);

    //check for Type:Organization
    $params = [
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
    ];
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception $expected) {
    }
    $this->assertSame('Compumentor', $contact->organization_name);
    $this->assertSame('Organization', $contact->contact_type);
    $this->contactDelete($contact->id);

    //check for Type:Household
    $params = [
      'household_name' => 'John Does home',
      'contact_type' => 'Household',
    ];
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception $expected) {
    }
    $this->assertSame('John Does home', $contact->household_name);
    $this->assertSame('Household', $contact->contact_type);
    $this->contactDelete($contact->id);

    //check for Type:Individual, Subtype:Student
    $params = [
      'first_name' => 'Bill',
      'last_name' => 'Adams',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->student,
    ];
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception $expected) {
    }
    $this->assertSame('Bill', $contact->first_name);
    $this->assertSame('Individual', $contact->contact_type);
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $contact->contact_sub_type), $this->student);
    $this->contactDelete($contact->id);

    //check for Type:Organization, Subtype:Sponsor
    $params = [
      'organization_name' => 'Conservation Corp',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->sponsor,
    ];
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception $expected) {
    }
    $this->assertSame('Conservation Corp', $contact->organization_name);
    $this->assertSame('Organization', $contact->contact_type);
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $contact->contact_sub_type), $this->sponsor);
    $this->contactDelete($contact->id);
  }

  /**
   * Update the contact with no subtype to a valid subtype.
   *
   * Success expected.
   */
  public function testUpdateContactNoSubtypeToValid(): void {
    $params = [
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
    ];
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception $expected) {
    }
    $updateParams = [
      'contact_sub_type' => $this->student,
      'contact_type' => 'Individual',
      'contact_id' => $contact->id,
    ];
    try {
      $updatedContact = CRM_Contact_BAO_Contact::add($updateParams);
    }
    catch (Exception $expected) {
    }
    $this->assertEquals($updatedContact->id, $contact->id);
    $this->assertSame('Individual', $updatedContact->contact_type);
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $updatedContact->contact_sub_type), $this->student);
    $this->contactDelete($contact->id);

    $params = [
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
    ];
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception $expected) {
    }

    $updateParams = [
      'contact_sub_type' => $this->sponsor,
      'contact_type' => 'Organization',
      'contact_id' => $contact->id,
    ];
    try {
      $updatedContact = CRM_Contact_BAO_Contact::add($updateParams);
    }
    catch (Exception $expected) {
    }
    $this->assertEquals($updatedContact->id, $contact->id);
    $this->assertSame('Organization', $updatedContact->contact_type);
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $updatedContact->contact_sub_type), $this->sponsor);
    $this->contactDelete($contact->id);
  }

  /**
   * Update the contact with subtype to another valid subtype.
   * success expected
   */
  public function testUpdateContactSubtype(): void {
    $params = [
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->student,
    ];
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception $expected) {
    }

    $updateParams = [
      'contact_sub_type' => $this->parent,
      'contact_type' => 'Individual',
      'contact_id' => $contact->id,
    ];
    try {
      $updatedContact = CRM_Contact_BAO_Contact::add($updateParams);
    }
    catch (Exception $expected) {
    }
    $this->assertEquals($updatedContact->id, $contact->id);
    $this->assertSame('Individual', $updatedContact->contact_type);
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $updatedContact->contact_sub_type), $this->parent);
    $this->contactDelete($contact->id);

    $params = [
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->sponsor,
    ];
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception $expected) {
    }

    $updateParams = [
      'contact_sub_type' => $this->team,
      'contact_type' => 'Organization',
      'contact_id' => $contact->id,
    ];
    try {
      $updatedContact = CRM_Contact_BAO_Contact::add($updateParams);
    }
    catch (Exception $expected) {
    }

    $this->assertEquals($updatedContact->id, $contact->id);
    $this->assertSame('Organization', $updatedContact->contact_type);
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $updatedContact->contact_sub_type), $this->team);
    $this->contactDelete($contact->id);

    $params = [
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->student,
    ];
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception $expected) {
    }

    $updateParams = [
      'contact_sub_type' => 'null',
      'contact_type' => 'Individual',
      'contact_id' => $contact->id,
    ];
    try {
      $updatedContact = CRM_Contact_BAO_Contact::add($updateParams);
    }
    catch (Exception $expected) {
    }

    $this->assertEquals($updatedContact->id, $contact->id);
    $this->assertSame('Individual', $updatedContact->contact_type);
    $this->assertSame('null', $updatedContact->contact_sub_type);
    $this->contactDelete($contact->id);
  }

  /**
   * Unit test to ensure that removing any subtype from CustomGroup's
   * extend_for setting, won't delete any custom data of contact
   *
   * Success expected
   */
  public function testCRM19133(): void {
    $subtypesToPreserve = [$this->student, $this->parent];

    // Create custom group that extends student and parent subtype
    $apiParams = [
      'title' => 'custom group',
      'extends' => ['Individual', $subtypesToPreserve],
      'is_active' => TRUE,
    ];
    $result = civicrm_api3('customGroup', 'create', $apiParams);
    $customGroupId = $result['id'];

    // Create desired custom field
    $apiParams = [
      'debug' => 1,
      'custom_group_id' => $result['id'],
      'label' => 'custom field',
      'html_type' => 'Text',
      'data_type' => 'String',
      'is_active' => TRUE,
    ];
    $result = civicrm_api3('custom_field', 'create', $apiParams);
    $customFieldId = $result['id'];

    // Create contact of subtype parent and student
    $params = [
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'contact_sub_type' => [$this->student, $this->parent],
    ];
    $contact = CRM_Contact_BAO_Contact::add($params);

    // Record custom value for desired customGroup
    $this->callAPISuccess('CustomValue', 'create', [
      'entity_id' => $contact->id,
      'custom_' . $customFieldId => 'value 1',
    ]);

    // Subtype to be removed from customGroup setting
    $subtypesToBeRemoved = [$this->student];
    CRM_Contact_BAO_ContactType::deleteCustomRowsOfSubtype($customGroupId, $subtypesToBeRemoved, $subtypesToPreserve);

    // Check with correct value to assert that custom data is not deleted
    $result = $this->callAPISuccess('Contact', 'Get', ['custom_' . $customFieldId => 'value 1']);
    $this->assertSame(1, $result['count']);
    $this->assertEquals($contact->id, $result['id']);

    //Check with incorrect custom value that our previous assertion was correct
    $result = $this->callAPISuccess('Contact', 'Get', ['custom_' . $customFieldId => 'wrong value']);
    $this->assertSame(0, $result['count']);
  }

}
