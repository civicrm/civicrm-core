<?php

/**
 * Class CRM_Contact_BAO_ContactType_ContactTest
 * @group headless
 */
class CRM_Contact_BAO_ContactType_ContactTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();

    $params = array(
      'label' => 'indiv_student',
      'name' => 'indiv_student',
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->student = $params['name'];

    $params = array(
      'label' => 'indiv_parent',
      'name' => 'indiv_parent',
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->parent = $params['name'];

    $params = array(
      'label' => 'org_sponsor',
      'name' => 'org_sponsor',
      // Organization
      'parent_id' => 3,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->sponsor = $params['name'];

    $params = array(
      'label' => 'org_team',
      'name' => 'org_team',
      // Organization
      'parent_id' => 3,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->team = $params['name'];
  }

  public function tearDown() {
    $this->quickCleanup(array('civicrm_contact'));
    $query = "
DELETE FROM civicrm_contact_type
      WHERE name IN ('{$this->student}','{$this->parent}','{$this->sponsor}', '{$this->team}');";
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Methods create Contact with valid data.
   *
   * Success expected
   */
  public function testCreateContact() {
    //check for Type:Individual
    $params = array(
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception$expected) {
    }
    $this->assertEquals($contact->first_name, 'Anne');
    $this->assertEquals($contact->contact_type, 'Individual');
    CRM_Contact_BAO_Contact::deleteContact($contact->id);

    //check for Type:Organization
    $params = array(
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception$expected) {
    }
    $this->assertEquals($contact->organization_name, 'Compumentor');
    $this->assertEquals($contact->contact_type, 'Organization');
    CRM_Contact_BAO_Contact::deleteContact($contact->id);

    //check for Type:Household
    $params = array(
      'household_name' => 'John Does home',
      'contact_type' => 'Household',
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception$expected) {
    }
    $this->assertEquals($contact->household_name, 'John Does home');
    $this->assertEquals($contact->contact_type, 'Household');
    CRM_Contact_BAO_Contact::deleteContact($contact->id);

    //check for Type:Individual, Subtype:Student
    $params = array(
      'first_name' => 'Bill',
      'last_name' => 'Adams',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->student,
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception$expected) {
    }
    $this->assertEquals($contact->first_name, 'Bill');
    $this->assertEquals($contact->contact_type, 'Individual');
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $contact->contact_sub_type), $this->student);
    CRM_Contact_BAO_Contact::deleteContact($contact->id);

    //check for Type:Organization, Subtype:Sponsor
    $params = array(
      'organization_name' => 'Conservation Corp',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->sponsor,
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception$expected) {
    }
    $this->assertEquals($contact->organization_name, 'Conservation Corp');
    $this->assertEquals($contact->contact_type, 'Organization');
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $contact->contact_sub_type), $this->sponsor);
    CRM_Contact_BAO_Contact::deleteContact($contact->id);
  }

  /**
   * Update the contact with no subtype to a valid subtype.
   *
   * Success expected.
   */
  public function testUpdateContactNoSubtypeToValid() {
    $params = array(
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception$expected) {
    }
    $updateParams = array(
      'contact_sub_type' => $this->student,
      'contact_type' => 'Individual',
      'contact_id' => $contact->id,
    );
    try {
      $updatedContact = CRM_Contact_BAO_Contact::add($updateParams);
    }
    catch (Exception$expected) {
    }
    $this->assertEquals($updatedContact->id, $contact->id);
    $this->assertEquals($updatedContact->contact_type, 'Individual');
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $updatedContact->contact_sub_type), $this->student);
    CRM_Contact_BAO_Contact::deleteContact($contact->id);

    $params = array(
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception$expected) {
    }

    $updateParams = array(
      'contact_sub_type' => $this->sponsor,
      'contact_type' => 'Organization',
      'contact_id' => $contact->id,
    );
    try {
      $updatedContact = CRM_Contact_BAO_Contact::add($updateParams);
    }
    catch (Exception$expected) {
    }
    $this->assertEquals($updatedContact->id, $contact->id);
    $this->assertEquals($updatedContact->contact_type, 'Organization');
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $updatedContact->contact_sub_type), $this->sponsor);
    CRM_Contact_BAO_Contact::deleteContact($contact->id);
  }

  /**
   * Update the contact with subtype to another valid subtype.
   * success expected
   */
  public function testUpdateContactSubtype() {
    $params = array(
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->student,
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception$expected) {
    }

    $updateParams = array(
      'contact_sub_type' => $this->parent,
      'contact_type' => 'Individual',
      'contact_id' => $contact->id,
    );
    try {
      $updatedContact = CRM_Contact_BAO_Contact::add($updateParams);
    }
    catch (Exception$expected) {
    }
    $this->assertEquals($updatedContact->id, $contact->id);
    $this->assertEquals($updatedContact->contact_type, 'Individual');
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $updatedContact->contact_sub_type), $this->parent);
    CRM_Contact_BAO_Contact::deleteContact($contact->id);

    $params = array(
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->sponsor,
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception$expected) {
    }

    $updateParams = array(
      'contact_sub_type' => $this->team,
      'contact_type' => 'Organization',
      'contact_id' => $contact->id,
    );
    try {
      $updatedContact = CRM_Contact_BAO_Contact::add($updateParams);
    }
    catch (Exception$expected) {
    }

    $this->assertEquals($updatedContact->id, $contact->id);
    $this->assertEquals($updatedContact->contact_type, 'Organization');
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $updatedContact->contact_sub_type), $this->team);
    CRM_Contact_BAO_Contact::deleteContact($contact->id);

    $params = array(
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->student,
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch (Exception$expected) {
    }

    $updateParams = array(
      'contact_sub_type' => NULL,
      'contact_type' => 'Individual',
      'contact_id' => $contact->id,
    );
    try {
      $updatedContact = CRM_Contact_BAO_Contact::add($updateParams);
    }
    catch (Exception$expected) {
    }

    $this->assertEquals($updatedContact->id, $contact->id);
    $this->assertEquals($updatedContact->contact_type, 'Individual');
    $this->assertEquals($updatedContact->contact_sub_type, 'null');
    CRM_Contact_BAO_Contact::deleteContact($contact->id);
  }

  /**
   * Unit test to ensure that removing any subtype from CustomGroup's
   * extend_for setting, won't delete any custom data of contact
   *
   * Success expected
   */
  public function testCRM19133() {
    $customGroupName = md5(microtime());
    $subtypesToPreserve = array($this->student, $this->parent);

    // Create custom group that extends student and parent subtype
    $apiParams = array(
      'title' => $customGroupName,
      'extends' => array('Individual', $subtypesToPreserve),
      'is_active' => TRUE,
    );
    $result = civicrm_api3('customGroup', 'create', $apiParams);
    $customGroupId = $result['id'];

    // Create desired custom field
    $apiParams = array(
      'debug' => 1,
      'custom_group_id' => $result['id'],
      'label' => $customGroupName,
      'html_type' => 'Text',
      'data_type' => 'String',
      'is_active' => TRUE,
    );
    $result = civicrm_api3('custom_field', 'create', $apiParams);
    $customFieldId = $result['id'];

    // Create contact of subtype parent and student
    $params = array(
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'contact_sub_type' => array($this->student, $this->parent),
    );
    $contact = CRM_Contact_BAO_Contact::add($params);

    // Record custom value for desired customGroup
    $this->callAPISuccess('CustomValue', 'create', array('entity_id' => $contact->id, 'custom_' . $customFieldId => 'value 1'));

    // Subtype to be removed from customGroup setting
    $subtypesToBeRemoved = array($this->student);
    CRM_Contact_BAO_ContactType::deleteCustomRowsOfSubtype($customGroupId, $subtypesToBeRemoved, $subtypesToPreserve);

    // Check with correct value to assert that custom data is not deleted
    $result = $this->callAPISuccess('Contact', 'Get', array('custom_' . $customFieldId => 'value 1'));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals($contact->id, $result['id']);

    //Check with incorrect custom value that our previous assertion was correct
    $result = $this->callAPISuccess('Contact', 'Get', array('custom_' . $customFieldId => 'wrong value'));
    $this->assertEquals(0, $result['count']);
  }

}
